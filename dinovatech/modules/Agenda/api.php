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
        $tipo_agenda = $_GET['tipo_agenda'] ?? 'clinica';

        $start = mysqli_real_escape_string($link, $start);
        $end = mysqli_real_escape_string($link, $end);

        $where = "WHERE A.data_inicio BETWEEN '$start' AND '$end'";
        if (!empty($id_vet)) {
            $id_vet = mysqli_real_escape_string($link, $id_vet);
            $where .= " AND A.id_vet = '$id_vet'";
        }

        if ($tipo_agenda === 'banho_tosa') {
            $where .= " AND A.tipo_agenda = 'banho_tosa'";
        } else {
            $where .= " AND (A.tipo_agenda = 'clinica' OR A.tipo_agenda IS NULL)";
        }

        $query = "SELECT A.*, V.nome as nome_vet, C.nome as nome_cliente, P.nome as nome_pet, S.nome_servico,
                         f.etapa as esteira_etapa, f.id_fila
                  FROM Agendamentos A
                  LEFT JOIN Veterinarios V ON A.id_vet = V.id_vet
                  LEFT JOIN Clientes C ON A.id_cliente = C.id_cliente
                  LEFT JOIN Pets P ON A.id_pet = P.id_pet
                  LEFT JOIN Servicos S ON A.id_servico = S.id_servico
                  LEFT JOIN BanhoProducaoFila f ON A.id_agendamento = f.id_agendamento
                  $where";

        $result = DBExecute($link, $query);
        $events = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $color = '#3788d8'; // Default Blue
            $prefixEtapa = '';

            if ($row['tipo_agenda'] === 'banho_tosa') {
                $color = '#0d9488'; // Teal
                if ($row['esteira_etapa'] === 'aguardando') {
                    $prefixEtapa = '⏳ [Fila] ';
                    $color = '#d97706';
                } elseif ($row['esteira_etapa'] === 'em_banho') {
                    $prefixEtapa = '🛁 [Banho] ';
                    $color = '#0891b2';
                } elseif ($row['esteira_etapa'] === 'secagem') {
                    $prefixEtapa = '💨 [Secagem] ';
                    $color = '#2563eb';
                } elseif ($row['esteira_etapa'] === 'tosa_finalizacao') {
                    $prefixEtapa = '✂️ [Tosa] ';
                    $color = '#9333ea';
                } elseif ($row['esteira_etapa'] === 'pronto') {
                    $prefixEtapa = '🐾 [Pronto] ';
                    $color = '#059669';
                }
            }

            if ($row['status'] == 'Realizado' || $row['status'] == 'Concluído')
                $color = '#10b981'; // Green
            if ($row['status'] == 'Cancelado')
                $color = '#ef4444'; // Red
            if ($row['status'] == 'Falta')
                $color = '#f59e0b'; // Orange

            $events[] = [
                'id' => $row['id_agendamento'],
                'title' => $prefixEtapa . $row['titulo'] . ($row['nome_cliente'] ? ' - ' . $row['nome_cliente'] : ''),
                'start' => str_replace(' ', 'T', $row['data_inicio']),
                'end' => str_replace(' ', 'T', $row['data_fim']),
                'color' => $color,
                'extendedProps' => [
                    'descricao' => $row['descricao'],
                    'id_vet' => $row['id_vet'],
                    'id_cliente' => $row['id_cliente'],
                    'id_pet' => $row['id_pet'],
                    'id_servico' => $row['id_servico'],
                    'id_cliente_pacote' => $row['id_cliente_pacote'],
                    'tipo_agenda' => $row['tipo_agenda'],
                    'esteira_etapa' => $row['esteira_etapa'],
                    'id_fila' => $row['id_fila'],
                    'status' => $row['status']
                ]
            ];
        }

        echo json_encode($events);
        break;

    case 'save':
        $id = $_POST['id'] ?? $_POST['id_agendamento'] ?? '';
        $titulo = $_POST['titulo'] ?? '';
        $start = $_POST['start'] ?? $_POST['data_inicio'] ?? '';
        $end = $_POST['end'] ?? $_POST['data_fim'] ?? '';
        $descricao = mysqli_real_escape_string($link, $_POST['descricao'] ?? '');
        $status = $_POST['status'] ?? 'Agendado';
        $tipo_agenda = $_POST['tipo_agenda'] ?? 'clinica';

        // Nullable fields
        $id_vet = !empty($_POST['id_vet']) ? "'" . mysqli_real_escape_string($link, $_POST['id_vet']) . "'" : 'NULL';
        $id_vet_val = !empty($_POST['id_vet']) ? $_POST['id_vet'] : null;

        $id_cliente = !empty($_POST['id_cliente']) ? (int)$_POST['id_cliente'] : 'NULL';
        $id_pet = !empty($_POST['id_pet']) ? (int)$_POST['id_pet'] : 'NULL';
        $id_servico = !empty($_POST['id_servico']) ? (int)$_POST['id_servico'] : 'NULL';
        $id_cliente_pacote = !empty($_POST['id_cliente_pacote']) ? (int)$_POST['id_cliente_pacote'] : 'NULL';
        $usar_saldo_pacote = isset($_POST['usar_saldo_pacote']) && $_POST['usar_saldo_pacote'] == 1;

        if (empty($titulo)) {
            // Auto generate title if empty
            if ($tipo_agenda === 'banho_tosa' && $id_pet !== 'NULL') {
                $resPet = DBExecute($link, "SELECT p.nome as pet_nome, s.nome_servico FROM Pets p LEFT JOIN Servicos s ON s.id_servico = $id_servico WHERE p.id_pet = $id_pet");
                if ($resPet && $pRow = mysqli_fetch_assoc($resPet)) {
                    $titulo = "Banho/Tosa: " . $pRow['pet_nome'] . ($pRow['nome_servico'] ? " (" . $pRow['nome_servico'] . ")" : "");
                } else {
                    $titulo = "Banho e Tosa";
                }
            } else {
                $titulo = "Agendamento";
            }
        }
        $titulo = mysqli_real_escape_string($link, $titulo);

        if (empty($id)) {
            // Insert
            $query = "INSERT INTO Agendamentos (id_vet, id_cliente, id_pet, id_servico, id_cliente_pacote, tipo_agenda, titulo, descricao, data_inicio, data_fim, status)
                      VALUES ($id_vet, $id_cliente, $id_pet, $id_servico, $id_cliente_pacote, '$tipo_agenda', '$titulo', '$descricao', '$start', '$end', '$status')";
            if (DBExecute($link, $query)) {
                $newId = mysqli_insert_id($link);

                // If Banho e Tosa, automatically enqueue into BanhoProducaoFila
                if ($tipo_agenda === 'banho_tosa' && $id_pet !== 'NULL') {
                    $colabVal = $id_vet !== 'NULL' ? $id_vet : 'NULL';
                    $obsFila = $descricao;
                    DBExecute($link, "INSERT INTO BanhoProducaoFila (id_agendamento, id_pet, id_colaborador, etapa, horario_entrada, observacoes_estetica) 
                                      VALUES ($newId, $id_pet, $colabVal, 'aguardando', NOW(), '$obsFila')");
                }

                // If using package balance, consume 1 credit
                if ($usar_saldo_pacote && $id_cliente_pacote !== 'NULL' && $id_servico !== 'NULL' && $id_pet !== 'NULL') {
                    $qSaldo = "SELECT id_saldo, qtd_utilizada, qtd_total FROM ClientePacoteSaldos WHERE id_cliente_pacote = $id_cliente_pacote AND id_servico = $id_servico";
                    $rSaldo = DBExecute($link, $qSaldo);
                    if ($rSaldo && $sRow = mysqli_fetch_assoc($rSaldo)) {
                        if ($sRow['qtd_utilizada'] < $sRow['qtd_total']) {
                            $nextUtil = $sRow['qtd_utilizada'] + 1;
                            DBExecute($link, "UPDATE ClientePacoteSaldos SET qtd_utilizada = $nextUtil WHERE id_saldo = " . (int)$sRow['id_saldo']);
                            DBExecute($link, "INSERT INTO ClientePacoteConsumo (id_cliente_pacote, id_servico, id_pet, id_agendamento, observacao) 
                                              VALUES ($id_cliente_pacote, $id_servico, $id_pet, $newId, 'Agendado via Agenda Banho')");
                        }
                    }
                }

                // Google Sync (Only if Vet is selected)
                if ($id_vet_val) {
                    $gEventId = syncGoogle($link, $id_vet_val, [
                        'id_cliente' => $id_cliente !== 'NULL' ? $id_cliente : null,
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
                        id_servico = $id_servico,
                        id_cliente_pacote = $id_cliente_pacote,
                        tipo_agenda = '$tipo_agenda',
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
                        'id_cliente' => $id_cliente !== 'NULL' ? $id_cliente : null,
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