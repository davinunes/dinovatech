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

                <div class="flex items-center gap-4 w-full md:w-auto">
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

    <!-- Event Modal (To be implemented in separate file and included) -->
    <?php include 'form_modal.php'; ?>

    <?php include '../../components/layout_scripts.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        let calendar;

        document.addEventListener('DOMContentLoaded', function () {
            var calendarEl = document.getElementById('calendar');

            calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'timeGridWeek',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
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
            $('#filterVet').select2({ placeholder: "Filtrar por Veterinário", allowClear: true });
            $('#filterVet').on('change', function () {
                calendar.refetchEvents();
            });
        });

    </script>
</body>

</html>