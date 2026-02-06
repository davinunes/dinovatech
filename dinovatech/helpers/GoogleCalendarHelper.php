<?php
// dinovatech/helpers/GoogleCalendarHelper.php

// Adjust path if necessary based on where you installed google/api-php-client
require_once __DIR__ . '/../../google/api-php-client/autoload.php';

use Google\Client;
use Google\Service\Calendar;
use Google\Service\Calendar\Event;

class GoogleCalendarHelper
{
    private $client;
    private $service;
    private $calendarId;

    public function __construct($calendarId = 'primary')
    {
        $this->calendarId = $calendarId;
        $this->auth();
    }

    private function auth()
    {
        // 1. Fetch encrypted JSON from DB
        $link = DBConnect();
        // Assuming ConfiguracoesEmissor has the field 'google_service_account_json'
        // We fetch the first record as usual
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

        // 3. Setup Google Client
        $this->client = new Client();
        $this->client->setAuthConfig($credentials);
        $this->client->addScope(Calendar::CALENDAR_EVENTS); // Read/Write events
        $this->client->setApplicationName('DinoVet App');

        // 4. Init Service
        $this->service = new Calendar($this->client);
    }

    /*
     * List events between dates (RFC3339 format)
     */
    public function listEvents($startDateTime, $endDateTime)
    {
        $optParams = array(
            'orderBy' => 'startTime',
            'singleEvents' => true,
            'timeMin' => $startDateTime, // e.g., '2026-02-05T10:00:00-03:00'
            'timeMax' => $endDateTime,
        );

        try {
            $results = $this->service->events->listEvents($this->calendarId, $optParams);
            return $results->getItems();
        } catch (Exception $e) {
            error_log("GoogleCalendarHelper Error (listEvents): " . $e->getMessage());
            return [];
        }
    }

    /*
     * Create Event
     * $data = ['summary' => '', 'description' => '', 'start' => 'ISO', 'end' => 'ISO']
     */
    public function createEvent($data)
    {
        $event = new Event(array(
            'summary' => $data['summary'],
            'description' => $data['description'] ?? '',
            'start' => array(
                'dateTime' => $data['start'],
                'timeZone' => 'America/Sao_Paulo',
            ),
            'end' => array(
                'dateTime' => $data['end'],
                'timeZone' => 'America/Sao_Paulo',
            ),
        ));

        try {
            $event = $this->service->events->insert($this->calendarId, $event);
            return $event->id;
        } catch (Exception $e) {
            error_log("GoogleCalendarHelper Error (createEvent): " . $e->getMessage());
            return null;
        }
    }

    public function updateEvent($eventId, $data)
    {
        try {
            $event = $this->service->events->get($this->calendarId, $eventId);

            $event->setSummary($data['summary']);
            if (isset($data['description']))
                $event->setDescription($data['description']);

            if (isset($data['start'])) {
                $start = new Google\Service\Calendar\EventDateTime();
                $start->setDateTime($data['start']);
                $start->setTimeZone('America/Sao_Paulo');
                $event->setStart($start);
            }

            if (isset($data['end'])) {
                $end = new Google\Service\Calendar\EventDateTime();
                $end->setDateTime($data['end']);
                $end->setTimeZone('America/Sao_Paulo');
                $event->setEnd($end);
            }

            $updatedEvent = $this->service->events->update($this->calendarId, $eventId, $event);
            return $updatedEvent->id;
        } catch (Exception $e) {
            error_log("GoogleCalendarHelper Error (updateEvent): " . $e->getMessage());
            return false;
        }
    }

    public function deleteEvent($eventId)
    {
        try {
            $this->service->events->delete($this->calendarId, $eventId);
            return true;
        } catch (Exception $e) {
            error_log("GoogleCalendarHelper Error (deleteEvent): " . $e->getMessage());
            return false;
        }
    }
}
?>