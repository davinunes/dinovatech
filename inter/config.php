<?php
// inter/config.php
require_once __DIR__ . '/../dinovatech/database.php';
require_once __DIR__ . '/../dinovatech/config.php';
require_once __DIR__ . '/../dinovatech/helpers/EncryptionHelper.php';

// Fetch Config from DB
$link = DBConnect();
$dbConfig = null;
if ($link) {
    $q = "SELECT * FROM ConfiguracoesEmissor LIMIT 1";
    $r = DBExecute($link, $q);
    if ($r && mysqli_num_rows($r) > 0) {
        $dbConfig = mysqli_fetch_assoc($r);
    }
    DBClose($link);
}

// --- Configuração de Ambientes ---
// O ambiente padrão pode vir do banco também, mas por segurança/testes, as vezes é setado no código ou env
// Vamos usar o do banco se disponível, senão 'sandbox'
$ambiente = 'sandbox'; // default
if ($dbConfig && !empty($dbConfig['ambiente_padrao'])) {
    // ambiente_padrao no banco é 'homologacao' ou 'producao'
    // Mapear para 'sandbox' ou 'production'
    if ($dbConfig['ambiente_padrao'] === 'producao') {
        $ambiente = 'production';
    } else {
        $ambiente = 'sandbox';
    }
}

// Decrypt Secret
$clientSecret = '';
if ($dbConfig && !empty($dbConfig['api_inter_client_secret'])) {
    try {
        $decrypted = EncryptionHelper::decrypt($dbConfig['api_inter_client_secret']);
        if ($decrypted)
            $clientSecret = $decrypted;
    } catch (Exception $e) {
        // error_log("Erro ao descriptografar secret Inter: " . $e->getMessage());
    }
}

$certFile = $dbConfig['api_inter_cert_path'] ? __DIR__ . '/../' . $dbConfig['api_inter_cert_path'] : '';
$keyFile = $dbConfig['api_inter_key_path'] ? __DIR__ . '/../' . $dbConfig['api_inter_key_path'] : '';

// Array de configuração
$configuracoes = [
    'sandbox' => [
        'url_token' => 'https://cdpj-sandbox.partners.uatinter.co/oauth/v2/token',
        'url_pix_base' => 'https://cdpj-sandbox.partners.uatinter.co/pix/v2',
        'client_id' => $dbConfig['api_inter_client_id'] ?? '',
        'client_secret' => $clientSecret,
        'conta_corrente' => $dbConfig['api_inter_conta_corrente'] ?? '',
        'chave_pix' => $dbConfig['api_inter_chave_pix'] ?? '',
        'scope' => 'cob.write cob.read pix.write pix.read cobv.write cobv.read lotecobv.write lotecobv.read webhook.write webhook.read boleto-cobranca.read boleto-cobranca.write extrato.read pagamento-pix.write pagamento-pix.read extrato-usend.read pagamento-boleto.read pagamento-boleto.write pagamento-darf.write pagamento-lote.write pagamento-lote.read webhook-banking.read webhook-banking.write',
        // Certs now come from absolute/relative paths from root
        'cert_path_abs' => $certFile,
        'key_path_abs' => $keyFile,
        'token_validity_seconds' => 3600,
        'debug_mode' => true,
    ],
    'production' => [
        'url_token' => 'https://cdpj.partners.bancointer.com.br/oauth/v2/token',
        'url_pix_base' => 'https://cdpj.partners.bancointer.com.br/pix/v2',
        'client_id' => $dbConfig['api_inter_client_id'] ?? '',
        'client_secret' => $clientSecret,
        'conta_corrente' => $dbConfig['api_inter_conta_corrente'] ?? '',
        'chave_pix' => $dbConfig['api_inter_chave_pix'] ?? '',
        'scope' => 'cob.write cob.read pix.write',
        'cert_path_abs' => $certFile,
        'key_path_abs' => $keyFile,
        'token_validity_seconds' => 3600,
        'debug_mode' => false,
    ],
];

// Carrega as configurações do ambiente selecionado
$ambienteConfig = $configuracoes[$ambiente];

// Exporta variáveis globais para manter compatibilidade com código existente (se houver)
$sslCertFile = $ambienteConfig['cert_path_abs'];
$sslKeyFile = $ambienteConfig['key_path_abs'];
// CA File não é estritamente necessário para cURL modernos com certificados válidos na loja do sistema, mas se o Inter exigir CA específico (cadeia), precisaria ser gerenciado também. 
// Por enquanto, vamos assumir que não precisa forçar CA bundle path se o ambiente tiver atualizado. Se precisar, podemos adicionar campo no futuro.
$caInfoFile = '';