<?php

/**
 * Script de Automação: Provisionamento do RustDesk Server via API K3s e Migração no Dinovatech
 * 
 * Uso:
 *   php scripts/deploy_rustdesk_k3s.php
 * 
 * O que este script faz:
 *   1. Executa a migração no banco de dados para registrar o tenant 'rustdesk' em saas_tenants.
 *   2. Conecta diretamente na API REST do K3s (https://137.131.208.19:6443) via K3sClientHelper.
 *   3. Provisiona o Namespace (app-rustdesk), PVC (1Gi local-path), Deployment (hbbs + hbbr) e Service (LoadBalancer).
 *   4. Atualiza o status para RUNNING no painel do Dinovatech.
 * 
 * SEGURANÇA / MODO VET:
 *   - O campo app_mode_vet é definido como 0 estritamente para o RustDesk.
 *   - Nenhum tenant existente tem seu app_mode_vet ou configuração alterada.
 */

require_once __DIR__ . '/../dinovatech/config.php';
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../dinovatech/helpers/K3sClientHelper.php';
require_once __DIR__ . '/../dinovatech/helpers/SaasProvisionerHelper.php';

echo "===================================================================\n";
echo " Dinovatech: Provisionador do RustDesk Server via API K3s\n";
echo "===================================================================\n\n";

$link = DBConnect();
if (!$link) {
    fwrite(STDERR, "[ERRO] Falha ao conectar no banco de dados: " . mysqli_connect_error() . "\n");
    exit(1);
}

// -----------------------------------------------------------------------------
// 1. Executa o Migrate para registrar o RustDesk no Dinovatech
// -----------------------------------------------------------------------------
echo "[1/3] Executando migração de banco de dados...\n";

// Garante que id_recorrencia aceite NULL
DBExecute($link, "ALTER TABLE saas_tenants MODIFY COLUMN id_recorrencia INT NULL COMMENT 'Vínculo com o contrato/recorrência (NULL para infraestrutura interna)'");

// Insere ou atualiza o registro do RustDesk preservando app_mode_vet = 0 apenas nele
$sqlTenant = "
INSERT INTO saas_tenants (
    id_cliente, id_recorrencia, slug_instancia, 
    subdominio, namespace_k8s, db_name, db_user, db_password, 
    db_host, db_port, app_mode_vet, versao_imagem, status_pod, 
    limite_memoria, limite_cpu
) VALUES (
    1, NULL, 'rustdesk', 
    'remote.dinovatech.com.br', 'app-rustdesk', 'sqlite', 'root', '', 
    '127.0.0.1', 3306, 0, 'rustdesk/rustdesk-server:latest', 'PENDING', 
    '384Mi', '500m'
) ON DUPLICATE KEY UPDATE 
    namespace_k8s = 'app-rustdesk',
    versao_imagem = 'rustdesk/rustdesk-server:latest',
    app_mode_vet = 0";

if (!DBExecute($link, $sqlTenant)) {
    fwrite(STDERR, "[ERRO] Falha ao registrar tenant RustDesk no banco: " . mysqli_error($link) . "\n");
    DBClose($link);
    exit(1);
}

// Busca o registro atualizado
$res = DBExecute($link, "SELECT * FROM saas_tenants WHERE slug_instancia = 'rustdesk' LIMIT 1");
$tenant = mysqli_fetch_assoc($res);
echo "  > Registro de tenant sincronizado (ID Tenant: {$tenant['id_tenant']}, Modo Vet: {$tenant['app_mode_vet']}).\n";

// -----------------------------------------------------------------------------
// 2. Valida conexão com a API REST do K3s
// -----------------------------------------------------------------------------
echo "\n[2/3] Conectando à API REST do K3s (" . K3S_API_URL . ")...\n";
$k3s = new K3sClientHelper();
$health = $k3s->checkHealth();

if (!$health['healthy']) {
    fwrite(STDERR, "[ERRO] Cluster K3s indisponível: " . ($health['error'] ?? 'HTTP ' . $health['http_code']) . "\n");
    DBClose($link);
    exit(1);
}
echo "  > Conexão bem-sucedida! Latência da API: {$health['latency_ms']} ms\n";

// -----------------------------------------------------------------------------
// 3. Provisiona os recursos no Kubernetes via SaasProvisionerHelper
// -----------------------------------------------------------------------------
echo "\n[3/3] Criando recursos no K3s (Namespace, PVC, Deployment e Service LB)...\n";
$provRes = SaasProvisionerHelper::provisionRustDesk($tenant, $k3s, $link);

if (!$provRes['success']) {
    fwrite(STDERR, "[ERRO] " . $provRes['message'] . "\n");
    DBClose($link);
    exit(1);
}

echo "  > " . $provRes['message'] . "\n\n";

echo "===================================================================\n";
echo " [SUCESSO] RustDesk Server provisionado com sucesso no K3s!\n";
echo "===================================================================\n\n";
echo "Portas expostas no nó (137.131.208.19):\n";
echo "  - 21115 (TCP): Teste de NAT\n";
echo "  - 21116 (TCP/UDP): Registro de ID e Heartbeat\n";
echo "  - 21117 (TCP): Serviço de Relay\n";
echo "  - 21118/21119 (TCP): Clientes Web / WebSocket\n\n";
echo "Como obter sua chave pública para configurar o cliente RustDesk:\n";
echo "  kubectl -n app-rustdesk exec -it deploy/rustdesk-server -c hbbs -- cat /root/id_ed25519.pub\n\n";
echo "No Dinovatech:\n";
echo "  Acesse 'Central de Instâncias SaaS' (saas_tenants.php) para monitorar o status,\n";
echo "  ver logs em tempo real e reiniciar o serviço sempre que precisar.\n";

DBClose($link);
exit(0);
