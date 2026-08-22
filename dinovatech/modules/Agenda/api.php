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

/**
 * Sincronização direta multi-calendário no Google Agenda (Profissional e Cliente)
 */
function syncGoogleCompleto($link, $id_vet, $idCliente, $agendamentoData, $id_agendamento = null, $currEventVet = null, $currEventCliente = null)
{
    $ret = [
        'event_id_vet' => $currEventVet,
        'event_id_cliente' => $currEventCliente
    ];

    if (!class_exists('GoogleCalendarHelper')) {
        return $ret;
    }

    $gData = [
        'summary' => $agendamentoData['titulo'],
        'description' => $agendamentoData['descricao'] ?? '',
        'start' => $agendamentoData['data_inicio'], // ISO 8601
        'end' => $agendamentoData['data_fim']
    ];

    // 1. Sincroniza no Calendário do Profissional / Veterinário
    if (!empty($id_vet)) {
        $id_vet_safe = mysqli_real_escape_string($link, $id_vet);
        $qVet = "SELECT google_calendar_id FROM Veterinarios WHERE id_vet = '$id_vet_safe'";
        $resVet = DBExecute($link, $qVet);
        if ($resVet && $vet = mysqli_fetch_assoc($resVet)) {
            if (!empty($vet['google_calendar_id'])) {
                try {
                    $google = new GoogleCalendarHelper($vet['google_calendar_id'], $id_agendamento);
                    if ($currEventVet) {
                        $upd = $google->updateEvent($currEventVet, $gData);
                        $ret['event_id_vet'] = $upd ?: $currEventVet;
                    } else {
                        $ret['event_id_vet'] = $google->createEvent($gData);
                    }
                } catch (Exception $e) {
                    error_log("Google Sync Profissional Failed: " . $e->getMessage());
                }
            }
        }
    }

    // 2. Sincroniza no Calendário do Cliente (se configurado com permissão)
    if (!empty($idCliente)) {
        $id_cli_safe = (int) $idCliente;
        $qCli = "SELECT google_calendar_id FROM Clientes WHERE id_cliente = $id_cli_safe";
        $resCli = DBExecute($link, $qCli);
        if ($resCli && $cli = mysqli_fetch_assoc($resCli)) {
            if (!empty($cli['google_calendar_id'])) {
                try {
                    $googleCli = new GoogleCalendarHelper($cli['google_calendar_id'], $id_agendamento);
                    if ($currEventCliente) {
                        $updC = $googleCli->updateEvent($currEventCliente, $gData);
                        $ret['event_id_cliente'] = $updC ?: $currEventCliente;
                    } else {
                        $ret['event_id_cliente'] = $googleCli->createEvent($gData);
                    }
                } catch (Exception $e) {
                    error_log("Google Sync Cliente Failed: " . $e->getMessage());
                }
            }
        }
    }

    return $ret;
}

/**
 * Exclusão direta nos calendários Google do Profissional e do Cliente
 */
function deleteGoogleCompleto($link, $id_vet, $idCliente, $currEventVet, $currEventCliente, $id_agendamento = null)
{
    if (!class_exists('GoogleCalendarHelper')) {
        return;
    }

    // 1. Deletar do Profissional
    if (!empty($id_vet) && !empty($currEventVet)) {
        $id_vet_safe = mysqli_real_escape_string($link, $id_vet);
        $resVet = DBExecute($link, "SELECT google_calendar_id FROM Veterinarios WHERE id_vet = '$id_vet_safe'");
        if ($resVet && $vet = mysqli_fetch_assoc($resVet)) {
            if (!empty($vet['google_calendar_id'])) {
                try {
                    $google = new GoogleCalendarHelper($vet['google_calendar_id'], $id_agendamento);
                    $google->deleteEvent($currEventVet);
                } catch (Exception $e) {
                    error_log("Google Delete Profissional Failed: " . $e->getMessage());
                }
            }
        }
    }

    // 2. Deletar do Cliente
    if (!empty($idCliente) && !empty($currEventCliente)) {
        $id_cli_safe = (int) $idCliente;
        $resCli = DBExecute($link, "SELECT google_calendar_id FROM Clientes WHERE id_cliente = $id_cli_safe");
        if ($resCli && $cli = mysqli_fetch_assoc($resCli)) {
            if (!empty($cli['google_calendar_id'])) {
                try {
                    $googleCli = new GoogleCalendarHelper($cli['google_calendar_id'], $id_agendamento);
                    $googleCli->deleteEvent($currEventCliente);
                } catch (Exception $e) {
                    error_log("Google Delete Cliente Failed: " . $e->getMessage());
                }
            }
        }
    }
}

// Wrapper legado de compatibilidade
function syncGoogle($link, $id_vet, $agendamentoData, $eventId = null)
{
    $idCliente = $agendamentoData['id_cliente'] ?? null;
    $res = syncGoogleCompleto($link, $id_vet, $idCliente, $agendamentoData, null, $eventId, null);
    return $res['event_id_vet'] ?? null;
}

// Wrapper legado de compatibilidade
function deleteGoogle($link, $id_vet, $eventId)
{
    deleteGoogleCompleto($link, $id_vet, null, $eventId, null);
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
                    'status' => $row['status'],
                    'google_event_id' => $row['google_event_id'] ?? null,
                    'google_event_id_cliente' => $row['google_event_id_cliente'] ?? null
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

        $id_cliente_val = !empty($_POST['id_cliente']) ? (int)$_POST['id_cliente'] : null;
        $id_cliente = $id_cliente_val ? $id_cliente_val : 'NULL';
        $id_pet = !empty($_POST['id_pet']) ? (int)$_POST['id_pet'] : 'NULL';
        $id_servico = !empty($_POST['id_servico']) ? (int)$_POST['id_servico'] : 'NULL';
        $id_cliente_pacote = !empty($_POST['id_cliente_pacote']) ? (int)$_POST['id_cliente_pacote'] : 'NULL';
        $usar_saldo_pacote = isset($_POST['usar_saldo_pacote']) && $_POST['usar_saldo_pacote'] == 1;

        if (empty($titulo)) {
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

        $startIso = (new DateTime($start, new DateTimeZone('America/Sao_Paulo')))->format('Y-m-d\TH:i:s');
        $endIso = (new DateTime($end, new DateTimeZone('America/Sao_Paulo')))->format('Y-m-d\TH:i:s');

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

                // Google Sync (Profissional e Cliente)
                $syncRes = syncGoogleCompleto($link, $id_vet_val, $id_cliente_val, [
                    'titulo' => $titulo,
                    'descricao' => $descricao,
                    'data_inicio' => $startIso,
                    'data_fim' => $endIso
                ], $newId);

                $gVetVal = !empty($syncRes['event_id_vet']) ? "'" . mysqli_real_escape_string($link, $syncRes['event_id_vet']) . "'" : "NULL";
                $gCliVal = !empty($syncRes['event_id_cliente']) ? "'" . mysqli_real_escape_string($link, $syncRes['event_id_cliente']) . "'" : "NULL";

                if ($gVetVal !== "NULL" || $gCliVal !== "NULL") {
                    DBExecute($link, "UPDATE Agendamentos SET google_event_id = $gVetVal, google_event_id_cliente = $gCliVal WHERE id_agendamento = $newId");
                }

                echo json_encode(['success' => true, 'id' => $newId]);
            } else {
                echo json_encode(['success' => false, 'message' => mysqli_error($link)]);
            }
        } else {
            // Update
            $idSafe = (int) $id;
            $curr = mysqli_fetch_assoc(DBExecute($link, "SELECT google_event_id, google_event_id_cliente, id_vet, id_cliente FROM Agendamentos WHERE id_agendamento = $idSafe"));
            $currVetEvent = $curr['google_event_id'] ?? null;
            $currCliEvent = $curr['google_event_id_cliente'] ?? null;

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
                      WHERE id_agendamento = '$idSafe'";
            if (DBExecute($link, $query)) {

                $syncRes = syncGoogleCompleto($link, $id_vet_val, $id_cliente_val, [
                    'titulo' => $titulo,
                    'descricao' => $descricao,
                    'data_inicio' => $startIso,
                    'data_fim' => $endIso
                ], $idSafe, $currVetEvent, $currCliEvent);

                $gVetVal = !empty($syncRes['event_id_vet']) ? "'" . mysqli_real_escape_string($link, $syncRes['event_id_vet']) . "'" : "NULL";
                $gCliVal = !empty($syncRes['event_id_cliente']) ? "'" . mysqli_real_escape_string($link, $syncRes['event_id_cliente']) . "'" : "NULL";

                DBExecute($link, "UPDATE Agendamentos SET google_event_id = $gVetVal, google_event_id_cliente = $gCliVal WHERE id_agendamento = $idSafe");

                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => mysqli_error($link)]);
            }
        }
        break;

    case 'update_drop':
        $id = (int) ($_POST['id'] ?? 0);
        $start = $_POST['start'] ?? '';
        $end = $_POST['end'] ?? '';

        $qGet = "SELECT * FROM Agendamentos WHERE id_agendamento = $id";
        $row = mysqli_fetch_assoc(DBExecute($link, $qGet));

        if ($row) {
            $query = "UPDATE Agendamentos SET data_inicio = '$start', data_fim = '$end' WHERE id_agendamento = $id";
            if (DBExecute($link, $query)) {
                $startIso = (new DateTime($start, new DateTimeZone('America/Sao_Paulo')))->format('Y-m-d\TH:i:s');
                $endIso = (new DateTime($end, new DateTimeZone('America/Sao_Paulo')))->format('Y-m-d\TH:i:s');

                syncGoogleCompleto($link, $row['id_vet'], $row['id_cliente'], [
                    'titulo' => $row['titulo'],
                    'descricao' => $row['descricao'],
                    'data_inicio' => $startIso,
                    'data_fim' => $endIso
                ], $id, $row['google_event_id'], $row['google_event_id_cliente'] ?? null);

                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => mysqli_error($link)]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Agendamento não encontrado']);
        }
        break;

    case 'delete':
        $id = (int) ($_POST['id'] ?? 0);
        $curr = mysqli_fetch_assoc(DBExecute($link, "SELECT id_vet, id_cliente, google_event_id, google_event_id_cliente FROM Agendamentos WHERE id_agendamento = $id"));

        if (DBExecute($link, "DELETE FROM Agendamentos WHERE id_agendamento = $id")) {
            if ($curr) {
                deleteGoogleCompleto($link, $curr['id_vet'], $curr['id_cliente'], $curr['google_event_id'] ?? null, $curr['google_event_id_cliente'] ?? null, $id);
            }
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => mysqli_error($link)]);
        }
        break;

    case 'test_google_sync':
        if (!class_exists('GoogleCalendarHelper')) {
            echo json_encode(['success' => false, 'message' => 'Helper do Google Calendar não encontrado no servidor.']);
            break;
        }
        $targetCalendar = trim($_REQUEST['calendar_id'] ?? '');
        $diag = GoogleCalendarHelper::testDiagnostics($targetCalendar ?: null);
        echo json_encode($diag);
        break;

    case 'get_google_logs':
        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 50;
        if ($limit > 100)
            $limit = 100;
        $idAg = isset($_GET['id_agendamento']) ? (int) $_GET['id_agendamento'] : null;
        $where = $idAg ? "WHERE id_agendamento = $idAg" : "";

        $resLogs = @DBExecute($link, "SELECT * FROM GoogleSyncLogs $where ORDER BY id_log DESC LIMIT $limit");
        $logs = [];
        if ($resLogs) {
            while ($l = mysqli_fetch_assoc($resLogs)) {
                $logs[] = $l;
            }
        }
        echo json_encode(['success' => true, 'logs' => $logs]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Action required']);
}

DBClose($link);
?>