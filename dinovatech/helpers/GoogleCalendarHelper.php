<?php
// dinovatech/helpers/GoogleCalendarHelper.php

// Adjust path if necessary based on where you installed google/api-php-client
require_once __DIR__ . '/../../google/api-php-client/autoload.php';

use Google\Auth\Credentials\ServiceAccountCredentials;
use GuzzleHttp\Client as GuzzleClient;

class GoogleCalendarHelper
{
    private $httpClient;
    private $calendarId;
    private $idAgendamento = null;
    private $baseUri = 'https://www.googleapis.com/calendar/v3/calendars/';

    public function __construct($calendarId = 'primary', $idAgendamento = null)
    {
        $this->calendarId = trim($calendarId);
        $this->idAgendamento = $idAgendamento ? (int) $idAgendamento : null;
        $this->auth();
    }

    public function setIdAgendamento($id)
    {
        $this->idAgendamento = $id ? (int) $id : null;
    }

    private function auth()
    {
        // 1. Fetch encrypted JSON from DB
        $link = DBConnect();
        $query = "SELECT google_service_account_json FROM ConfiguracoesEmissor LIMIT 1";
        $res = DBExecute($link, $query);
        $row = mysqli_fetch_assoc($res);
        DBClose($link);

        if (!$row || empty($row['google_service_account_json'])) {
            throw new Exception("Configurações do Google Service Account não encontradas no banco de dados.");
        }

        // 2. Decrypt
        require_once __DIR__ . '/EncryptionHelper.php';
        $jsonContent = EncryptionHelper::decrypt($row['google_service_account_json']);

        if (!$jsonContent) {
            throw new Exception("Falha ao descriptografar as credenciais do Google.");
        }

        $credentials = json_decode($jsonContent, true);
        if (!$credentials) {
            throw new Exception("JSON das credenciais do Google inválido.");
        }

        // 3. Setup Authenticated Guzzle Client using Google Auth directly
        $sa = new ServiceAccountCredentials(
            'https://www.googleapis.com/auth/calendar',
            $credentials
        );

        $middleware = new Google\Auth\Middleware\AuthTokenMiddleware($sa);
        $stack = GuzzleHttp\HandlerStack::create();
        $stack->push($middleware);

        $this->httpClient = new GuzzleClient([
            'handler' => $stack,
            'auth' => 'google_auth'
        ]);
    }

    /*
     * Helper to log synchronization events to GoogleSyncLogs
     */
    public static function logSync($idAgendamento, $calendarId, $tipoOperacao, $status, $httpCode, $mensagem, $payloadResumo = null)
    {
        try {
            $link = DBConnect();
            if (!$link)
                return;

            $idAgVal = !empty($idAgendamento) ? (int) $idAgendamento : "NULL";
            $calSafe = mysqli_real_escape_string($link, (string) $calendarId);
            $opSafe = mysqli_real_escape_string($link, (string) $tipoOperacao);
            $stSafe = mysqli_real_escape_string($link, (string) $status);
            $codeVal = !empty($httpCode) ? (int) $httpCode : "NULL";
            $msgSafe = mysqli_real_escape_string($link, (string) $mensagem);
            $paySafe = !empty($payloadResumo) ? "'" . mysqli_real_escape_string($link, is_array($payloadResumo) ? json_encode($payloadResumo, JSON_UNESCAPED_UNICODE) : (string) $payloadResumo) . "'" : "NULL";

            $query = "INSERT INTO GoogleSyncLogs (data_hora, id_agendamento, calendar_id, tipo_operacao, status, http_code, mensagem, payload_resumo)
                      VALUES (NOW(), $idAgVal, '$calSafe', '$opSafe', '$stSafe', $codeVal, '$msgSafe', $paySafe)";
            @DBExecute($link, $query);
            DBClose($link);
        } catch (Exception $e) {
            error_log("Falha ao registrar GoogleSyncLog: " . $e->getMessage());
        }
    }

    /*
     * Extract human-readable error from Google Calendar API response
     */
    private function parseGoogleError($e)
    {
        $code = 0;
        $bodyStr = '';
        if (method_exists($e, 'getResponse') && $e->getResponse()) {
            $code = $e->getResponse()->getStatusCode();
            $bodyStr = (string) $e->getResponse()->getBody();
        }

        $detailMsg = $e->getMessage();
        if (!empty($bodyStr)) {
            $json = json_decode($bodyStr, true);
            if (!empty($json['error']['message'])) {
                $detailMsg = $json['error']['message'];
            }
        }

        $explicacao = $detailMsg;
        if ($code === 404) {
            $explicacao = "Agenda não encontrada no Google Calendar ($this->calendarId). Verifique se o ID ou e-mail está correto.";
        } elseif ($code === 403) {
            if (stripos($detailMsg, 'attendees') !== false) {
                $explicacao = "Contas de serviço não podem enviar convidados (attendees) diretamente. Use sincronização por agenda compartilhada.";
            } else {
                $explicacao = "Acesso negado à agenda ($this->calendarId). Verifique se a agenda foi compartilhada com a Service Account com permissão de 'Fazer alterações nos eventos'.";
            }
        } elseif ($code === 401) {
            $explicacao = "Falha de autenticação OAuth2 com o Google. Credenciais da Service Account inválidas.";
        }

        return [
            'code' => $code,
            'message' => $explicacao,
            'raw' => $bodyStr ?: $e->getMessage()
        ];
    }

    /*
     * List events between dates (RFC3339 format)
     */
    public function listEvents($startDateTime, $endDateTime)
    {
        $query = [
            'orderBy' => 'startTime',
            'singleEvents' => 'true',
            'timeMin' => $startDateTime,
            'timeMax' => $endDateTime,
        ];

        try {
            $response = $this->httpClient->get($this->baseUri . urlencode($this->calendarId) . '/events', [
                'query' => $query
            ]);
            $data = json_decode($response->getBody(), true);
            return $data['items'] ?? [];
        } catch (Exception $e) {
            $err = $this->parseGoogleError($e);
            self::logSync($this->idAgendamento, $this->calendarId, 'list', 'erro', $err['code'], $err['message']);
            error_log("GoogleCalendarHelper Error (listEvents): " . $err['message']);
            return [];
        }
    }

    /*
     * Create Event
     * Note: 'attendees' is intentionally excluded to prevent Google 403 Forbidden for Service Accounts
     */
    public function createEvent($data)
    {
        $payload = [
            'summary' => $data['summary'],
            'description' => $data['description'] ?? '',
            'start' => [
                'dateTime' => $data['start'],
                'timeZone' => 'America/Sao_Paulo',
            ],
            'end' => [
                'dateTime' => $data['end'],
                'timeZone' => 'America/Sao_Paulo',
            ],
        ];

        try {
            $response = $this->httpClient->post($this->baseUri . urlencode($this->calendarId) . '/events', [
                'json' => $payload
            ]);
            $body = (string) $response->getBody();
            $event = json_decode($body, true);
            $eventId = $event['id'] ?? null;

            if ($eventId) {
                self::logSync(
                    $this->idAgendamento,
                    $this->calendarId,
                    'create',
                    'sucesso',
                    $response->getStatusCode(),
                    "Evento criado com sucesso (ID: $eventId)",
                    ['titulo' => $data['summary'], 'inicio' => $data['start'], 'fim' => $data['end']]
                );
            }

            return $eventId;
        } catch (Exception $e) {
            $err = $this->parseGoogleError($e);
            self::logSync(
                $this->idAgendamento,
                $this->calendarId,
                'create',
                'erro',
                $err['code'],
                $err['message'],
                ['titulo' => $data['summary'] ?? '', 'erro_bruto' => $err['raw']]
            );
            error_log("GoogleCalendarHelper Error (createEvent): " . $err['message']);
            return null;
        }
    }

    /*
     * Update Event
     * Note: 'attendees' is intentionally excluded to prevent Google 403 Forbidden for Service Accounts
     */
    public function updateEvent($eventId, $data)
    {
        $payload = ['summary' => $data['summary']];
        if (isset($data['description'])) {
            $payload['description'] = $data['description'];
        }

        if (isset($data['start'])) {
            $payload['start'] = [
                'dateTime' => $data['start'],
                'timeZone' => 'America/Sao_Paulo',
            ];
        }
        if (isset($data['end'])) {
            $payload['end'] = [
                'dateTime' => $data['end'],
                'timeZone' => 'America/Sao_Paulo',
            ];
        }

        try {
            $response = $this->httpClient->patch($this->baseUri . urlencode($this->calendarId) . '/events/' . urlencode($eventId), [
                'json' => $payload
            ]);
            $event = json_decode($response->getBody(), true);
            $updatedId = $event['id'] ?? $eventId;

            self::logSync(
                $this->idAgendamento,
                $this->calendarId,
                'update',
                'sucesso',
                $response->getStatusCode(),
                "Evento atualizado com sucesso (ID: $updatedId)",
                ['eventId' => $eventId, 'titulo' => $data['summary']]
            );

            return $updatedId;
        } catch (Exception $e) {
            $err = $this->parseGoogleError($e);
            self::logSync(
                $this->idAgendamento,
                $this->calendarId,
                'update',
                'erro',
                $err['code'],
                $err['message'],
                ['eventId' => $eventId, 'erro_bruto' => $err['raw']]
            );
            error_log("GoogleCalendarHelper Error (updateEvent): " . $err['message']);
            return false;
        }
    }

    /*
     * Delete Event
     */
    public function deleteEvent($eventId)
    {
        try {
            $response = $this->httpClient->delete($this->baseUri . urlencode($this->calendarId) . '/events/' . urlencode($eventId));
            $code = $response ? $response->getStatusCode() : 200;

            self::logSync(
                $this->idAgendamento,
                $this->calendarId,
                'delete',
                'sucesso',
                $code,
                "Evento removido da agenda Google (ID: $eventId)"
            );
            return true;
        } catch (Exception $e) {
            $err = $this->parseGoogleError($e);
            // If already 404 or 410, it is already deleted
            if ($err['code'] === 404 || $err['code'] === 410) {
                self::logSync(
                    $this->idAgendamento,
                    $this->calendarId,
                    'delete',
                    'aviso',
                    $err['code'],
                    "Evento já não existia na agenda Google (ID: $eventId)"
                );
                return true;
            }

            self::logSync(
                $this->idAgendamento,
                $this->calendarId,
                'delete',
                'erro',
                $err['code'],
                $err['message'],
                ['eventId' => $eventId, 'erro_bruto' => $err['raw']]
            );
            error_log("GoogleCalendarHelper Error (deleteEvent): " . $err['message']);
            return false;
        }
    }

    /*
     * Diagnostic tool: Test Service Account configuration and access to a calendar
     */
    public static function testDiagnostics($targetCalendarId = null)
    {
        $result = [
            'success' => false,
            'service_email' => '',
            'calendar_id' => $targetCalendarId,
            'message' => '',
            'details' => ''
        ];

        try {
            $link = DBConnect();
            $query = "SELECT google_service_account_json FROM ConfiguracoesEmissor LIMIT 1";
            $res = DBExecute($link, $query);
            $row = mysqli_fetch_assoc($res);
            DBClose($link);

            if (!$row || empty($row['google_service_account_json'])) {
                $result['message'] = "JSON da Service Account não configurado nas Configurações da Empresa.";
                return $result;
            }

            require_once __DIR__ . '/EncryptionHelper.php';
            $jsonContent = EncryptionHelper::decrypt($row['google_service_account_json']);
            if (!$jsonContent) {
                $result['message'] = "Falha ao descriptografar o JSON da Service Account.";
                return $result;
            }

            $credentials = json_decode($jsonContent, true);
            if (!$credentials || empty($credentials['client_email'])) {
                $result['message'] = "JSON inválido ou campo 'client_email' não encontrado.";
                return $result;
            }

            $result['service_email'] = $credentials['client_email'];

            if (!empty($targetCalendarId)) {
                $helper = new self($targetCalendarId);
                // Try reading calendar metadata
                $res = $helper->httpClient->get($helper->baseUri . urlencode($targetCalendarId));
                $calData = json_decode($res->getBody(), true);

                $result['success'] = true;
                $result['message'] = "Conexão estabelecida com sucesso! Acesso concedido à agenda '{$calData['summary']}'.";
                $result['details'] = [
                    'calendar_summary' => $calData['summary'] ?? '',
                    'time_zone' => $calData['timeZone'] ?? '',
                    'service_email' => $result['service_email']
                ];

                self::logSync(null, $targetCalendarId, 'test', 'sucesso', 200, $result['message']);
            } else {
                $result['success'] = true;
                $result['message'] = "Credenciais da Service Account validadas com sucesso ($result[service_email]).";
                self::logSync(null, 'primary', 'test', 'sucesso', 200, $result['message']);
            }
        } catch (Exception $e) {
            $code = 0;
            $bodyStr = '';
            if (method_exists($e, 'getResponse') && $e->getResponse()) {
                $code = $e->getResponse()->getStatusCode();
                $bodyStr = (string) $e->getResponse()->getBody();
            }

            $errMsg = $e->getMessage();
            if ($code === 404) {
                $errMsg = "Agenda '$targetCalendarId' não encontrada. Verifique se o ID ou e-mail está correto.";
            } elseif ($code === 403) {
                $errMsg = "Acesso negado à agenda '$targetCalendarId'. Certifique-se de compartilhar sua agenda do Google com o e-mail '{$result['service_email']}' dando permissão para 'Fazer alterações nos eventos'.";
            }

            $result['success'] = false;
            $result['message'] = $errMsg;
            $result['details'] = $bodyStr ?: $e->getMessage();

            self::logSync(null, $targetCalendarId ?: 'N/A', 'test', 'erro', $code, $errMsg, ['raw' => $result['details']]);
        }

        return $result;
    }
}
?>