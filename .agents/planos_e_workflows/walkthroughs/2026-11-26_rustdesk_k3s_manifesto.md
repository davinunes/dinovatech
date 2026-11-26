# Walkthrough / Guia de Implantação: Servidor RustDesk no K3s e Automação Dinovatech

- **Data de Entrega**: 2026-11-26
- **Status**: Concluído (Manifesto + Migração SQL + Script API + SaasProvisionerHelper)
- **Arquivos Criados/Modificados**:
  - [`devops/k8s/03-rustdesk-server.yaml`](file:///d:/dev/github/dinovatech/devops/k8s/03-rustdesk-server.yaml)
  - [`database/migrations/20261126_0001_register_rustdesk_service.sql`](file:///d:/dev/github/dinovatech/database/migrations/20261126_0001_register_rustdesk_service.sql)
  - [`dinovatech/helpers/SaasProvisionerHelper.php`](file:///d:/dev/github/dinovatech/dinovatech/helpers/SaasProvisionerHelper.php)
  - [`scripts/deploy_rustdesk_k3s.php`](file:///d:/dev/github/dinovatech/scripts/deploy_rustdesk_k3s.php)

---

## 1. O que foi Implementado

1. **Provisionamento 100% via API REST do K3s**:
   - `SaasProvisionerHelper::provisionRustDesk`: Método especializado que envia requisições HTTP REST diretamente para a porta 6443 do Kubernetes, criando o Namespace, o Volume PVC (1Gi local-path), o Deployment (hbbs + hbbr) e o Service LoadBalancer (portas 21115 a 21119).
   - Não depende de conexão SSH manual ou execução de `kubectl apply`.

2. **Migração do Banco Dinovatech (`20261126_0001_register_rustdesk_service.sql`)**:
   - Permite que a coluna `id_recorrencia` em `saas_tenants` aceite `NULL` (para serviços de infraestrutura sem contrato comercial).
   - Registra o tenant `rustdesk` na tabela para que ele seja exibido e gerenciado diretamente na Central de Instâncias SaaS (`saas_tenants.php`).

3. **Script CLI de Automação (`scripts/deploy_rustdesk_k3s.php`)**:
   - Executa a migração, conecta no K3s via API REST e provisiona todo o ambiente de uma vez só.

4. **Garantia de Isolamento do "Modo Vet" (`app_mode_vet`)**:
   - O campo `app_mode_vet` é definido como `0` **exclusivamente para a linha do RustDesk**.
   - **Nenhum tenant de clínica veterinária existente é afetado**, mantendo o `app_mode_vet = 1` e `APP_MODE_VET=true` nos seus pods intactos.
   - O RustDesk roda em um namespace isolado (`app-rustdesk`), sem PHP e sem contato com os dados ou secrets dos outros clientes.

---

## 2. Como Executar a Implantação

Você tem duas formas extremamente simples de rodar a implantação:

### Opção 1: Via Script PHP (1 Comando no Servidor Remoto)
No terminal da sua VPS (onde roda o Dinovatech):
```bash
php scripts/deploy_rustdesk_k3s.php
```
Este script executará a migração de banco e chamará a API do K3s automaticamente.

### Opção 2: Pela Interface Web do Dinovatech
1. Execute a migração do banco (via `php scripts/migrate.php` ou rodando o SQL `database/migrations/20261126_0001_register_rustdesk_service.sql`).
2. Acesse a tela **Central de Instâncias SaaS** (`saas_tenants.php`) no navegador.
3. Você verá a linha **RustDesk Server Privado** com o status `PENDING`.
4. Clique no botão roxo **"Deploy"**: o Dinovatech chamará a API do K3s em segundo plano, subirá os pods e o status mudará para `RUNNING`!

---

## 3. Como Obter a Chave Pública e Conectar

Após o pod subir, pegue a chave pública para o cliente:
```bash
kubectl -n app-rustdesk exec -it deploy/rustdesk-server -c hbbs -- cat /root/id_ed25519.pub
```

No seu cliente RustDesk (Windows/Mac/Linux/Android):
* **ID Server**: `137.131.208.19`
* **Relay Server**: `137.131.208.19`
* **Key**: A chave obtida no comando acima.
