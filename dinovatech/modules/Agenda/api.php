<?php
// dinovatech/modules/Agenda/api.php
session_start();
header('Content-Type: application/json');

include '../../../database.php';
include '../../config.php';

// Include helper if exists
if (file_exists('../../helpers/GoogleCalendarHelper.php')) {
    include '../../helpers/GoogleCalendarHelper.php';
}

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit;
}

$link = DBConnect();
$action = $_REQUEST['action'] ?? '';

// Função auxiliar para sync Google
function syncGoogle($link, $id_vet, $agendamentoData, $eventId = null)
{
    // 1. Check if vet has google_calendar_id
    $qVet = "SELECT google_calendar_id FROM Veterinarios WHERE id_vet = '$id_vet'";
    $resVet = DBExecute($link, $qVet);
    $vet = mysqli_fetch_assoc($resVet);

    if ($vet && !empty($vet['google_calendar_id'])) {
        try {
            $google = new GoogleCalendarHelper($vet['google_calendar_id']);

            $attendees = [];
            $idCliente = (int) ($agendamentoData['id_cliente'] ?? 0);
            if ($idCliente > 0) {
                $resC = DBExecute($link, "SELECT email, google_calendar_id FROM Clientes WHERE id_cliente = '$idCliente'");
                if ($resC && $rowC = mysqli_fetch_assoc($resC)) {
                    $clientTarget = !empty($rowC['google_calendar_id']) ? $rowC['google_calendar_id'] : ($rowC['email'] ?? '');
                    if (!empty($clientTarget)) {
                        $attendees[] = ['email' => $clientTarget];
                    }
                }
            }

            $gData = [
                'summary' => $agendamentoData['titulo'],
                'description' => $agendamentoData['descricao'] ?? '',
                'start' => $agendamentoData['data_inicio'], // ISO 8601
                'end' => $agendamentoData['data_fim'],
                'attendees' => $attendees
            ];

            if ($eventId) {
                // Update
                $res = $google->updateEvent($eventId, $gData);
                return $res;
            } else {
                // Create
                $res = $google->createEvent($gData);
                return $res;
            }
        } catch (Exception $e) {
            $err = "Google Sync Failed: " . $e->getMessage();
            error_log($err);
            return null;
        }
    } else {
        // Vet has no Calendar ID
    }
    return null;
}

function deleteGoogle($link, $id_vet, $eventId)
{
    if (!$eventId)
        return;
    $qVet = "SELECT google_calendar_id FROM Veterinarios WHERE id_vet = '$id_vet'";
    $resVet = DBExecute($link, $qVet);
    $vet = mysqli_fetch_assoc($resVet);

    if ($vet && !empty($vet['google_calendar_id'])) {
        try {
            $google = new GoogleCalendarHelper($vet['google_calendar_id']);
            $google->deleteEvent($eventId);
        } catch (Exception $e) {
            error_log("Google Delete Failed: " . $e->getMessage());
        }
    }
}


switch ($action) {
    case 'get_events':
        $start = $_GET['start'] ?? date('Y-m-01');
        $end = $_GET['end'] ?? date('Y-m-t');
        $id_vet = $_GET['id_vet'] ?? '';

        $start = mysqli_real_escape_string($link, $start);
        $end = mysqli_real_escape_string($link, $end);

        $where = "WHERE data_inicio BETWEEN '$start' AND '$end'";
        if (!empty($id_vet)) {
            $id_vet = mysqli_real_escape_string($link, $id_vet);
            $where .= " AND A.id_vet = '$id_vet'";
        }

        $query = "SELECT A.*, V.nome as nome_vet, C.nome as nome_cliente, P.nome as nome_pet 
                  FROM Agendamentos A
                  JOIN Veterinarios V ON A.id_vet = V.id_vet
                  LEFT JOIN Clientes C ON A.id_cliente = C.id_cliente
                  LEFT JOIN Pets P ON A.id_pet = P.id_pet
                  $where";

        $result = DBExecute($link, $query);
        $events = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $color = '#3788d8'; // Default Blue
            if ($row['status'] == 'Realizado')
                $color = '#10b981'; // Green
            if ($row['status'] == 'Cancelado')
                $color = '#ef4444'; // Red
            if ($row['status'] == 'Falta')
                $color = '#f59e0b'; // Orange

            $events[] = [
                'id' => $row['id_agendamento'],
                'title' => $row['titulo'] . ($row['nome_cliente'] ? ' - ' . $row['nome_cliente'] : ''),
                'start' => str_replace(' ', 'T', $row['data_inicio']), // Raw DB value
                'end' => str_replace(' ', 'T', $row['data_fim']),
                'color' => $color,
                'extendedProps' => [
                    'descricao' => $row['descricao'],
                    'id_vet' => $row['id_vet'],
                    'id_cliente' => $row['id_cliente'],
                    'id_pet' => $row['id_pet'],
                    'status' => $row['status']
                ]
            ];
        }
        $json = json_encode($events);
        error_log("API Debug Events: " . $json); // DEBUG TIMEZONE

        echo $json;
        break;

    case 'save':
        $id = $_POST['id'] ?? '';
        $titulo = mysqli_real_escape_string($link, $_POST['titulo']);
        $start = $_POST['start']; // Format: Y-m-d H:i:s
        $end = $_POST['end'];
        error_log("API Debug SAVE Input: ID=$id Start=$start End=$end"); // DEBUG INPUT

        $descricao = mysqli_real_escape_string($link, $_POST['descricao'] ?? '');
        $status = $_POST['status'] ?? 'Agendado';

        // Nullable fields
        $id_vet = !empty($_POST['id_vet']) ? "'" . mysqli_real_escape_string($link, $_POST['id_vet']) . "'" : 'NULL';
        $id_vet_val = !empty($_POST['id_vet']) ? $_POST['id_vet'] : null;

        $id_cliente = !empty($_POST['id_cliente']) ? $_POST['id_cliente'] : 'NULL';
        $id_pet = !empty($_POST['id_pet']) ? $_POST['id_pet'] : 'NULL';

        if (empty($id)) {
            // Insert
            $query = "INSERT INTO Agendamentos (id_vet, id_cliente, id_pet, titulo, descricao, data_inicio, data_fim, status)
                      VALUES ($id_vet, $id_cliente, $id_pet, '$titulo', '$descricao', '$start', '$end', '$status')";
            if (DBExecute($link, $query)) {
                $newId = mysqli_insert_id($link);

                // Google Sync (Only if Vet is selected)
                if ($id_vet_val) {
                    $gEventId = syncGoogle($link, $id_vet_val, [
                        'titulo' => $titulo,
                        'descricao' => $descricao,
                        'data_inicio' => (new DateTime($start, new DateTimeZone('America/Sao_Paulo')))->format('Y-m-d\TH:i:s'),
                        'data_fim' => (new DateTime($end, new DateTimeZone('America/Sao_Paulo')))->format('Y-m-d\TH:i:s')
                    ]);

                    if ($gEventId) {
                        DBExecute($link, "UPDATE Agendamentos SET google_event_id = '$gEventId' WHERE id_agendamento = $newId");
                    }
                }

                echo json_encode(['success' => true, 'id' => $newId]);
            } else {
                echo json_encode(['success' => false, 'message' => mysqli_error($link)]);
            }
        } else {
            // Update
            $query = "UPDATE Agendamentos SET 
                        id_vet = $id_vet,
                        id_cliente = $id_cliente,
                        id_pet = $id_pet,
                        titulo = '$titulo',
                        descricao = '$descricao',
                        data_inicio = '$start',
                        data_fim = '$end',
                        status = '$status'
                      WHERE id_agendamento = '$id'";
            if (DBExecute($link, $query)) {

                if ($id_vet_val) {
                    // Get current Google ID
                    $curr = mysqli_fetch_assoc(DBExecute($link, "SELECT google_event_id FROM Agendamentos WHERE id_agendamento = '$id'"));
                    $gEventId = $curr['google_event_id'] ?? null;

                    $newGEventId = syncGoogle($link, $id_vet_val, [
                        'titulo' => $titulo,
                        'descricao' => $descricao,
                        'data_inicio' => (new DateTime($start, new DateTimeZone('America/Sao_Paulo')))->format('Y-m-d\TH:i:s'),
                        'data_fim' => (new DateTime($end, new DateTimeZone('America/Sao_Paulo')))->format('Y-m-d\TH:i:s')
                    ], $gEventId);

                    if (!$gEventId && $newGEventId) {
                        DBExecute($link, "UPDATE Agendamentos SET google_event_id = '$newGEventId' WHERE id_agendamento = $id");
                    }
                }

                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => mysqli_error($link)]);
            }
        }
        break;

    case 'update_drop':
        // Just updating time from Drag & Drop
        $id = $_POST['id'];
        $start = $_POST['start'];
        $end = $_POST['end'];
        error_log("API Debug DROP Input: ID=$id Start=$start End=$end"); // DEBUG INPUT


        $qGet = "SELECT * FROM Agendamentos WHERE id_agendamento = '$id'";
        $row = mysqli_fetch_assoc(DBExecute($link, $qGet));

        $query = "UPDATE Agendamentos SET data_inicio = '$start', data_fim = '$end' WHERE id_agendamento = '$id'";
        if (DBExecute($link, $query)) {
            // Sync Google
            if ($row['google_event_id']) {
                syncGoogle($link, $row['id_vet'], [
                    'titulo' => $row['titulo'], // Keep original title
                    'descricao' => $row['descricao'],
                    'data_inicio' => (new DateTime($start, new DateTimeZone('America/Sao_Paulo')))->format('Y-m-d\TH:i:s'),
                    'data_fim' => (new DateTime($end, new DateTimeZone('America/Sao_Paulo')))->format('Y-m-d\TH:i:s')
                ], $row['google_event_id']);
            }
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false]);
        }
        break;

    case 'delete':
        $id = $_POST['id'];
        // Get Google ID before delete
        $curr = mysqli_fetch_assoc(DBExecute($link, "SELECT id_vet, google_event_id FROM Agendamentos WHERE id_agendamento = '$id'"));

        if (DBExecute($link, "DELETE FROM Agendamentos WHERE id_agendamento = '$id'")) {
            if ($curr && $curr['google_event_id']) {
                deleteGoogle($link, $curr['id_vet'], $curr['google_event_id']);
            }
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Action required']);
}

DBClose($link);
?>