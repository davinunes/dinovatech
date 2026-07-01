<?php
// dinovatech/google_oauth_callback.php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

require_once __DIR__ . '/config.php';
include "../database.php";
require_once __DIR__ . '/helpers/EncryptionHelper.php';
require_once __DIR__ . '/../google/api-php-client/autoload.php';

use GuzzleHttp\Client as GuzzleClient;

$code = $_GET['code'] ?? null;
$error = $_GET['error'] ?? null;

if ($error) {
    header("Location: config_fiscal.php?error=" . urlencode("Erro na autorização do Google: " . $error));
    exit();
}

if (!$code) {
    header("Location: config_fiscal.php?error=" . urlencode("Nenhum código de autorização fornecido pelo Google."));
    exit();
}

try {
    $link = DBConnect();

    // 1. Busca credenciais de client ID e secret
    $query = "SELECT google_oauth_client_id, google_oauth_client_secret FROM ConfiguracoesEmissor LIMIT 1";
    $res = DBExecute($link, $query);
    $row = mysqli_fetch_assoc($res);

    if (!$row || empty($row['google_oauth_client_id']) || empty($row['google_oauth_client_secret'])) {
        DBClose($link);
        header("Location: config_fiscal.php?error=" . urlencode("Configurações de Client ID e Secret do Google não encontradas."));
        exit();
    }

    $clientId = $row['google_oauth_client_id'];
    $clientSecret = EncryptionHelper::decrypt($row['google_oauth_client_secret']);

    if (!$clientSecret) {
        DBClose($link);
        header("Location: config_fiscal.php?error=" . urlencode("Erro ao descriptografar o Google Client Secret."));
        exit();
    }

    // 2. Determina a URI de Redirecionamento correspondente (passada via state para evitar mismatch com proxies)
    $state = $_GET['state'] ?? null;
    if ($state) {
        $redirectUri = base64_decode(str_replace(['-', '_'], ['+', '/'], $state));
    } else {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
        if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
            $protocol = $_SERVER['HTTP_X_FORWARDED_PROTO'];
        }
        $host = $_SERVER['HTTP_HOST'];
        if (!empty($_SERVER['HTTP_X_FORWARDED_HOST'])) {
            $host = $_SERVER['HTTP_X_FORWARDED_HOST'];
        }
        $redirectUri = "$protocol://$host/dinovatech/google_oauth_callback.php";
    }

    // 3. Solicita a troca do código pelos tokens
    $client = new GuzzleClient();
    $tokenResponse = $client->post('https://oauth2.googleapis.com/token', [
        'form_params' => [
            'code' => $code,
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri' => $redirectUri,
            'grant_type' => 'authorization_code'
        ]
    ]);

    $tokenData = json_decode((string)$tokenResponse->getBody(), true);
    $accessToken = $tokenData['access_token'] ?? null;
    $refreshToken = $tokenData['refresh_token'] ?? null;

    if (!$accessToken) {
        throw new Exception("Access token não retornado no fluxo de autorização.");
    }

    // Nota: O Google só envia o refresh_token se as permissões foram requisitadas com prompt=consent e access_type=offline,
    // e apenas na primeira autorização. Se já estiver autorizado e vincular novamente, o refresh_token pode vir vazio.
    // Vamos tratar isso.
    if (!$refreshToken) {
        // Se o refresh_token veio nulo, tentamos ver se já existe um no banco. Se não existir, avisa que precisa refazer forçando consentimento.
        $qCheck = "SELECT google_oauth_refresh_token FROM ConfiguracoesEmissor LIMIT 1";
        $rCheck = DBExecute($link, $qCheck);
        $rowCheck = mysqli_fetch_assoc($rCheck);
        if ($rowCheck && !empty($rowCheck['google_oauth_refresh_token'])) {
            // Reaproveita o existente
            $refreshTokenEnc = $rowCheck['google_oauth_refresh_token'];
        } else {
            throw new Exception("Não foi possível obter o Refresh Token offline. Por favor, desvincule e tente novamente.");
        }
    } else {
        $refreshTokenEnc = EncryptionHelper::encrypt($refreshToken);
    }

    // 4. Obtém o e-mail associado à conta autorizada usando o access_token
    $userinfoResponse = $client->get('https://www.googleapis.com/oauth2/v2/userinfo', [
        'headers' => [
            'Authorization' => 'Bearer ' . $accessToken
        ]
    ]);

    $userinfoData = json_decode((string)$userinfoResponse->getBody(), true);
    $email = $userinfoData['email'] ?? null;

    if (!$email) {
        throw new Exception("Não foi possível obter o e-mail da conta autorizada.");
    }

    // 5. Salva no banco de dados
    $emailSafe = mysqli_real_escape_string($link, $email);
    $refreshTokenEncSafe = mysqli_real_escape_string($link, $refreshTokenEnc);

    $qUpdate = "UPDATE ConfiguracoesEmissor SET google_oauth_email = '$emailSafe', google_oauth_refresh_token = '$refreshTokenEncSafe'";
    DBExecute($link, $qUpdate);
    DBClose($link);

    header("Location: config_fiscal.php?tab=integracoes&success=" . urlencode("E-mail ($email) vinculado com sucesso para envio de faturas!"));
    exit();

} catch (Exception $e) {
    if (isset($link)) {
        DBClose($link);
    }
    $msg = $e->getMessage();
    if (method_exists($e, 'getResponse') && $e->getResponse()) {
        $msg .= " | Body: " . (string) $e->getResponse()->getBody();
    }
    header("Location: config_fiscal.php?error=" . urlencode("Erro no fluxo do token do Google: " . $msg));
    exit();
}
