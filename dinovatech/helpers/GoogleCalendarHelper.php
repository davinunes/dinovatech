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
    private $baseUri = 'https://www.googleapis.com/calendar/v3/calendars/';

    public function __construct($calendarId = 'primary')
    {
        $this->calendarId = $calendarId;
        $this->auth();
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
        // This avoids missing Google\Client wrapper
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
            error_log("GoogleCalendarHelper Error (listEvents): " . $e->getMessage());
            return [];
        }
    }

    /*
     * Create Event
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
            $event = json_decode($response->getBody(), true);
            return $event['id'] ?? null;
        } catch (Exception $e) {
            error_log("GoogleCalendarHelper Error (createEvent): " . $e->getMessage());
            return null;
        }
    }

    public function updateEvent($eventId, $data)
    {
        // First we might need to get the event to patch it, or just PATCH/PUT
        // We'll use PATCH logic if possible, but here we just construct the body from what we have.
        // Google Calendar API supports PATCH to update only fields present.

        $payload = ['summary' => $data['summary']];
        if (isset($data['description']))
            $payload['description'] = $data['description'];

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
            $response = $this->httpClient->patch($this->baseUri . urlencode($this->calendarId) . '/events/' . $eventId, [
                'json' => $payload
            ]);
            $event = json_decode($response->getBody(), true);
            return $event['id'] ?? null;
        } catch (Exception $e) {
            error_log("GoogleCalendarHelper Error (updateEvent): " . $e->getMessage());
            return false;
        }
    }

    public function deleteEvent($eventId)
    {
        try {
            $this->httpClient->delete($this->baseUri . urlencode($this->calendarId) . '/events/' . $eventId);
            return true;
        } catch (Exception $e) {
            error_log("GoogleCalendarHelper Error (deleteEvent): " . $e->getMessage());
            return false;
        }
    }
}
?>