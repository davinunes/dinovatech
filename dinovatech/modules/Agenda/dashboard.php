<?php
// dinovatech/modules/Agenda/dashboard.php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../../login.php");
    exit();
}
include '../../../database.php';
$link = DBConnect();

// Fetch Vets for filter
$vets = [];
$res = DBExecute($link, "SELECT id_vet, nome FROM Veterinarios ORDER BY nome");
while ($row = mysqli_fetch_assoc($res)) {
    $vets[] = $row;
}
DBClose($link);
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <title>Agenda - DinoVet</title>
    <?php include '../../components/layout_head.php'; ?>
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/main.min.css' rel='stylesheet' />
    <!-- jQuery (Required for FullCalendar/Bootstrap) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- FullCalendar JS -->
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.10/locales-all.global.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .fc-event {
            cursor: pointer;
        }

        .select2-container .select2-selection--single {
            height: 42px;
            padding-top: 6px;
            border-color: #e5e7eb;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 40px;
        }

        /* Responsive FullCalendar Header */
        @media (max-width: 768px) {
            .fc .fc-header-toolbar {
                flex-direction: column;
                gap: 10px;
            }
            .fc .fc-toolbar-chunk {
                display: flex;
                flex-wrap: wrap;
                justify-content: center;
                gap: 5px;
            }
        }
    </style>
</head>

<body class="bg-gray-50 flex">
    <?php include '../../components/sidebar.php'; ?>

    <div class="flex-1 flex flex-col lg:ml-64 min-h-screen transition-all duration-300">
        <main class="flex-1 p-6 mt-16 lg:mt-0">

            <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
                <h2 class="text-3xl font-bold text-gray-800 flex items-center">
                    <span class="material-icons text-cyan-600 mr-2">calendar_month</span> Agenda
                </h2>

                <div class="flex items-center gap-3 w-full md:w-auto">
                    <button type="button" onclick="openGoogleDiagModal()" class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-cyan-500 transition-colors" title="Diagnóstico e Logs da Integração Google">
                        <span class="material-icons text-cyan-600 mr-1.5 text-base">sync_alt</span> Diagnóstico Google
                    </button>
                    <div class="w-full md:w-64">
                        <select id="filterVet" class="w-full border rounded-lg p-2">
                            <option value="">Todos os <?= AppHelper::isVetMode() ? 'Veterinários' : 'Colaboradores' ?>
                            </option>
                            <?php foreach ($vets as $v): ?>
                                <option value="<?= $v['id_vet'] ?>">
                                    <?= htmlspecialchars($v['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 h-[calc(100vh-180px)]">
                <div id="calendar" class="h-full"></div>
            </div>

        </main>
    </div>

    <!-- Modal Diagnóstico Google Agenda -->
    <div id="googleDiagModal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-black bg-opacity-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-2xl w-full shadow-2xl overflow-hidden animate__animated animate__fadeInDown animate__faster">
            <div class="px-6 py-4 bg-gradient-to-r from-cyan-600 to-blue-700 text-white flex justify-between items-center">
                <div class="flex items-center gap-2">
                    <span class="material-icons">sync</span>
                    <h3 class="text-lg font-bold">Diagnóstico Google Agenda</h3>
                </div>
                <button type="button" onclick="closeGoogleDiagModal()" class="text-white hover:text-gray-200 focus:outline-none">
                    <span class="material-icons">close</span>
                </button>
            </div>

            <div class="p-6 space-y-6">
                <!-- Abas -->
                <div class="flex border-b border-gray-200 text-sm font-medium">
                    <button type="button" id="tabBtnDiagTest" onclick="switchDiagTab('test')" class="px-4 py-2 border-b-2 border-cyan-600 text-cyan-600 font-semibold focus:outline-none">
                        Testar Conexão / Agenda
                    </button>
                    <button type="button" id="tabBtnDiagLogs" onclick="switchDiagTab('logs')" class="px-4 py-2 border-b-2 border-transparent text-gray-500 hover:text-gray-700 focus:outline-none">
                        Logs de Sincronização
                    </button>
                </div>

                <!-- Conteúdo Aba Teste -->
                <div id="tabContentDiagTest" class="space-y-4">
                    <p class="text-sm text-gray-600">
                        Valide se a <strong>Service Account</strong> está com credenciais ativas e se possui permissão de leitura/escrita no e-mail ou ID de calendário informado.
                    </p>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">ID da Agenda Google / E-mail (Opcional):</label>
                        <div class="flex gap-2">
                            <input type="text" id="diagCalendarId" class="flex-1 border rounded-lg p-2.5 text-sm focus:ring-cyan-500 focus:border-cyan-500" placeholder="ex: seu_email@gmail.com ou ID de agenda compartilhada">
                            <button type="button" onclick="runGoogleDiagnostic()" id="btnRunDiag" class="px-4 py-2.5 bg-cyan-600 hover:bg-cyan-700 text-white font-medium rounded-lg text-sm transition-colors flex items-center gap-1.5">
                                <span class="material-icons text-sm">play_arrow</span> Testar
                            </button>
                        </div>
                        <p class="text-xs text-gray-400 mt-1">Deixe em branco para testar apenas a autenticação do arquivo JSON da Service Account.</p>
                    </div>

                    <div id="diagResultContainer" class="hidden p-4 rounded-xl text-sm border transition-all"></div>
                </div>

                <!-- Conteúdo Aba Logs -->
                <div id="tabContentDiagLogs" class="hidden space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-xs text-gray-500">Últimos registros de sincronização (Banco de Dados):</span>
                        <button type="button" onclick="loadGoogleLogs()" class="text-xs text-cyan-600 hover:underline flex items-center gap-1 font-medium">
                            <span class="material-icons text-xs">refresh</span> Atualizar Logs
                        </button>
                    </div>

                    <div class="max-h-72 overflow-y-auto border border-gray-100 rounded-xl divide-y divide-gray-100 bg-gray-50 p-2" id="diagLogsList">
                        <div class="p-4 text-center text-gray-400 text-sm">Carregando logs...</div>
                    </div>
                </div>
            </div>

            <div class="px-6 py-3 bg-gray-50 border-t border-gray-100 flex justify-end">
                <button type="button" onclick="closeGoogleDiagModal()" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 text-sm font-medium rounded-lg transition-colors">
                    Fechar
                </button>
            </div>
        </div>
    </div>

    <!-- Event Modal (To be implemented in separate file and included) -->
    <?php include 'form_modal.php'; ?>

    <?php include '../../components/layout_scripts.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        let calendar;

        document.addEventListener('DOMContentLoaded', function () {
            var calendarEl = document.getElementById('calendar');

            calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: window.innerWidth < 768 ? 'listWeek' : 'timeGridWeek',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
                },
                windowResize: function (arg) {
                    if (window.innerWidth < 768) {
                        if (calendar.view.type !== 'listWeek' && calendar.view.type !== 'listMonth' && calendar.view.type !== 'listDay') {
                            calendar.changeView('listWeek');
                        }
                    } else {
                        if (calendar.view.type === 'listWeek') {
                            calendar.changeView('timeGridWeek');
                        }
                    }
                },
                timeZone: 'UTC', // Force absolute time rendering (WYSIWYG)
                locale: 'pt-br',
                slotDuration: '00:30:00',
                slotMinTime: '06:00:00',
                slotMaxTime: '22:00:00',
                navLinks: true, // can click day/week names
                editable: true,
                selectable: true,
                dayMaxEvents: true, // allow "more" link when too many events
                events: {
                    url: 'api.php',
                    extraParams: function () {
                        return {
                            action: 'get_events',
                            id_vet: $('#filterVet').val(),
                            _: new Date().getTime() // Cache buster
                        };
                    },
                    success: function (rawEvents) {
                        console.log("🔥 Raw Events from API:", rawEvents);
                        rawEvents.forEach(e => {
                            console.log(`Event: ${e.title} | Start: ${e.start} | End: ${e.end}`);
                        });
                    }
                },
                select: function (info) {
                    openEventModal({
                        start: info.startStr,
                        end: info.endStr
                    });
                },
                eventClick: function (info) {
                    openEventModal(info.event);
                },
                eventDrop: function (info) {
                    // Update DB on drag & drop
                    $.post('api.php', {
                        action: 'update_drop',
                        id: info.event.id,
                        start: formatDateLocal(info.event.start),
                        end: formatDateLocal(info.event.end)
                    }).fail(function () {
                        info.revert();
                    });
                },
                eventResize: function (info) {
                    $.post('api.php', {
                        action: 'update_drop',
                        id: info.event.id,
                        start: formatDateLocal(info.event.start),
                        end: formatDateLocal(info.event.end)
                    }).fail(function () {
                        info.revert();
                    });
                }
            });

            calendar.render();

            // Filter Change
            const filterPlaceholder = "<?= AppHelper::isVetMode() ? 'Filtrar por Veterinário' : 'Filtrar por Colaborador' ?>";
            $('#filterVet').select2({ placeholder: filterPlaceholder, allowClear: true });
            $('#filterVet').on('change', function () {
                calendar.refetchEvents();
            });
        });

        // Funções de Diagnóstico Google Agenda
        function openGoogleDiagModal() {
            $('#googleDiagModal').removeClass('hidden');
            switchDiagTab('test');
        }

        function closeGoogleDiagModal() {
            $('#googleDiagModal').addClass('hidden');
        }

        function switchDiagTab(tab) {
            if (tab === 'test') {
                $('#tabBtnDiagTest').addClass('border-cyan-600 text-cyan-600 font-semibold').removeClass('border-transparent text-gray-500');
                $('#tabBtnDiagLogs').removeClass('border-cyan-600 text-cyan-600 font-semibold').addClass('border-transparent text-gray-500');
                $('#tabContentDiagTest').removeClass('hidden');
                $('#tabContentDiagLogs').addClass('hidden');
            } else {
                $('#tabBtnDiagLogs').addClass('border-cyan-600 text-cyan-600 font-semibold').removeClass('border-transparent text-gray-500');
                $('#tabBtnDiagTest').removeClass('border-cyan-600 text-cyan-600 font-semibold').addClass('border-transparent text-gray-500');
                $('#tabContentDiagLogs').removeClass('hidden');
                $('#tabContentDiagTest').addClass('hidden');
                loadGoogleLogs();
            }
        }

        function runGoogleDiagnostic() {
            const calId = $('#diagCalendarId').val();
            const btn = $('#btnRunDiag');
            const resBox = $('#diagResultContainer');

            btn.prop('disabled', true).html('<span class="material-icons text-sm animate-spin">refresh</span> Testando...');
            resBox.removeClass('hidden bg-green-50 bg-red-50 text-green-800 text-red-800 border-green-200 border-red-200').addClass('bg-gray-50 text-gray-700 border-gray-200').html('Consultando API do Google...');

            $.getJSON('api.php', { action: 'test_google_sync', calendar_id: calId }, function (res) {
                btn.prop('disabled', false).html('<span class="material-icons text-sm">play_arrow</span> Testar');

                if (res.success) {
                    resBox.removeClass('bg-gray-50 text-gray-700 border-gray-200').addClass('bg-emerald-50 text-emerald-800 border-emerald-200');
                    let html = `<div class="flex items-start gap-2">
                        <span class="material-icons text-emerald-600 mt-0.5">check_circle</span>
                        <div>
                            <p class="font-bold">${res.message}</p>
                            ${res.service_email ? `<p class="text-xs text-emerald-700 mt-1">E-mail da Service Account: <strong>${res.service_email}</strong></p>` : ''}
                        </div>
                    </div>`;
                    resBox.html(html);
                } else {
                    resBox.removeClass('bg-gray-50 text-gray-700 border-gray-200').addClass('bg-red-50 text-red-800 border-red-200');
                    let html = `<div class="flex items-start gap-2">
                        <span class="material-icons text-red-600 mt-0.5">error</span>
                        <div>
                            <p class="font-bold">${res.message}</p>
                            ${res.service_email ? `<p class="text-xs text-red-600 mt-1">E-mail da Service Account: <strong>${res.service_email}</strong></p>` : ''}
                            ${res.details && typeof res.details === 'string' ? `<pre class="mt-2 p-2 bg-red-100/50 rounded text-xs text-red-900 overflow-x-auto whitespace-pre-wrap">${res.details}</pre>` : ''}
                        </div>
                    </div>`;
                    resBox.html(html);
                }
            }).fail(function (xhr) {
                btn.prop('disabled', false).html('<span class="material-icons text-sm">play_arrow</span> Testar');
                resBox.removeClass('bg-gray-50 text-gray-700 border-gray-200').addClass('bg-red-50 text-red-800 border-red-200').html(`
                    <div class="flex items-center gap-2">
                        <span class="material-icons text-red-600">error</span>
                        <div><strong>Erro de comunicação com o servidor:</strong> ${xhr.responseText || 'Falha HTTP'}</div>
                    </div>
                `);
            });
        }

        function loadGoogleLogs() {
            const list = $('#diagLogsList');
            list.html('<div class="p-4 text-center text-gray-400 text-sm">Carregando logs...</div>');

            $.getJSON('api.php', { action: 'get_google_logs', limit: 30 }, function (res) {
                if (res.success && res.logs && res.logs.length > 0) {
                    let html = '';
                    res.logs.forEach(log => {
                        let statusColor = log.status === 'sucesso' ? 'text-emerald-700 bg-emerald-100 border-emerald-200' : (log.status === 'aviso' ? 'text-amber-700 bg-amber-100 border-amber-200' : 'text-red-700 bg-red-100 border-red-200');
                        let statusIcon = log.status === 'sucesso' ? 'check' : (log.status === 'aviso' ? 'warning' : 'close');

                        html += `<div class="p-2.5 bg-white rounded-lg border border-gray-100 text-xs flex flex-col gap-1 mb-1.5 shadow-sm">
                            <div class="flex items-center justify-between">
                                <span class="font-mono text-gray-400 text-[10px]">${log.data_hora}</span>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold border uppercase flex items-center gap-0.5 ${statusColor}">
                                    <span class="material-icons text-[12px]">${statusIcon}</span> ${log.tipo_operacao}
                                </span>
                            </div>
                            <div class="text-gray-800 font-medium">${log.mensagem}</div>
                            ${log.calendar_id ? `<div class="text-gray-500 text-[11px]">Agenda: <code class="bg-gray-50 px-1 py-0.5 rounded text-gray-600">${log.calendar_id}</code></div>` : ''}
                        </div>`;
                    });
                    list.html(html);
                } else {
                    list.html('<div class="p-6 text-center text-gray-400 text-sm">Nenhum log de sincronização registrado ainda.</div>');
                }
            }).fail(function () {
                list.html('<div class="p-4 text-center text-red-500 text-sm">Falha ao carregar logs. Certifique-se de executar as migrations do banco.</div>');
            });
        }
    </script>
</body>

</html>