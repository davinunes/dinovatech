<?php
// dinovatech/helpers/GmailHelper.php

require_once __DIR__ . '/../../google/api-php-client/autoload.php';
require_once __DIR__ . '/EncryptionHelper.php';

use GuzzleHttp\Client as GuzzleClient;

class GmailHelper
{
    /**
     * Obtém um novo access_token a partir do refresh_token armazenado.
     */
    private static function getAccessToken($clientId, $clientSecretDecrypted, $refreshTokenDecrypted)
    {
        $client = new GuzzleClient();
        try {
            $response = $client->post('https://oauth2.googleapis.com/token', [
                'form_params' => [
                    'client_id' => $clientId,
                    'client_secret' => $clientSecretDecrypted,
                    'refresh_token' => $refreshTokenDecrypted,
                    'grant_type' => 'refresh_token'
                ]
            ]);

            $data = json_decode((string)$response->getBody(), true);
            if (!empty($data['access_token'])) {
                return $data['access_token'];
            }
            throw new Exception("Access token não retornado pelo Google.");
        } catch (Exception $e) {
            $msg = $e->getMessage();
            if (method_exists($e, 'getResponse') && $e->getResponse()) {
                $msg .= " | Body: " . (string) $e->getResponse()->getBody();
            }
            throw new Exception("Falha ao atualizar token do Gmail: " . $msg);
        }
    }

    /**
     * Constrói uma mensagem MIME RFC 822 compatível com anexos.
     */
    private static function buildMimeMessage($to, $subject, $htmlBody, $attachments = [])
    {
        $boundary = uniqid('np', true);
        $subBoundary = uniqid('sub', true);

        $headers = [
            "MIME-Version: 1.0",
            "To: $to",
            "Subject: =?utf-8?B?" . base64_encode($subject) . "?=",
            "Content-Type: multipart/mixed; boundary=\"$boundary\""
        ];

        $mime = implode("\r\n", $headers) . "\r\n\r\n";

        // Corpo em HTML
        $mime .= "--$boundary\r\n";
        $mime .= "Content-Type: multipart/alternative; boundary=\"$subBoundary\"\r\n\r\n";

        $mime .= "--$subBoundary\r\n";
        $mime .= "Content-Type: text/html; charset=\"utf-8\"\r\n";
        $mime .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $mime .= chunk_split(base64_encode($htmlBody)) . "\r\n";
        $mime .= "--$subBoundary--\r\n";

        // Anexos físicos (dados binários)
        foreach ($attachments as $att) {
            $filename = $att['name'];
            $data = $att['data'];
            $mimeType = $att['mime'] ?? 'application/octet-stream';

            $mime .= "--$boundary\r\n";
            $mime .= "Content-Type: $mimeType; name=\"$filename\"\r\n";
            $mime .= "Content-Disposition: attachment; filename=\"$filename\"\r\n";
            $mime .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $mime .= chunk_split(base64_encode($data)) . "\r\n";
        }

        $mime .= "--$boundary--";
        return $mime;
    }

    /**
     * Envia um e-mail utilizando a API do Gmail e as credenciais OAuth configuradas.
     */
    public static function sendEmail($to, $subject, $htmlBody, $attachments = [])
    {
        // 1. Conecta e busca configurações
        $link = DBConnect();
        $query = "SELECT google_oauth_client_id, google_oauth_client_secret, google_oauth_refresh_token FROM ConfiguracoesEmissor LIMIT 1";
        $res = DBExecute($link, $query);
        $row = mysqli_fetch_assoc($res);
        DBClose($link);

        if (!$row || empty($row['google_oauth_client_id']) || empty($row['google_oauth_refresh_token'])) {
            throw new Exception("Integração com Gmail não configurada ou não autorizada nas configurações.");
        }

        // 2. Descriptografa segredos
        $clientId = $row['google_oauth_client_id'];
        $clientSecret = EncryptionHelper::decrypt($row['google_oauth_client_secret']);
        $refreshToken = EncryptionHelper::decrypt($row['google_oauth_refresh_token']);

        if (!$clientSecret || !$refreshToken) {
            throw new Exception("Falha ao descriptografar segredos do Google OAuth.");
        }

        // 3. Atualiza token de acesso
        $accessToken = self::getAccessToken($clientId, $clientSecret, $refreshToken);

        // 4. Constrói mensagem MIME
        $rawMime = self::buildMimeMessage($to, $subject, $htmlBody, $attachments);

        // 5. Codifica em Base64URL
        $base64url = rtrim(strtr(base64_encode($rawMime), '+/', '-_'), '=');

        // 6. Envia via API do Gmail
        $client = new GuzzleClient();
        try {
            $response = $client->post('https://www.googleapis.com/gmail/v1/users/me/messages/send', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type' => 'application/json'
                ],
                'json' => [
                    'raw' => $base64url
                ]
            ]);

            $data = json_decode((string)$response->getBody(), true);
            return $data['id'] ?? true;
        } catch (Exception $e) {
            $msg = $e->getMessage();
            if (method_exists($e, 'getResponse') && $e->getResponse()) {
                $msg .= " | Body: " . (string) $e->getResponse()->getBody();
            }
            throw new Exception("Erro ao enviar e-mail via Gmail API: " . $msg);
        }
    }
}
