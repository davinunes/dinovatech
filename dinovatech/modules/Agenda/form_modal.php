<?php
// dinovatech/modules/Agenda/form_modal.php
include_once __DIR__ . '/../../helpers/AppHelper.php';

// Re-open DB link to fetch data for dropdowns
$link = DBConnect();
$clients = [];
$resC = DBExecute($link, "SELECT id_cliente, nome FROM Clientes ORDER BY nome ASC");
while ($r = mysqli_fetch_assoc($resC))
    $clients[] = $r;

$pets = []; // We might load pets via AJAX based on client, but let's load all for now or empty
// Loading all pets might be too much. Let's load none and use AJAX or depend on client selection.
// For MVP, allow picking any pet if list is small, or just load all.
// Let's assume list is manageable or implement simple dependent dropdown.
$resP = DBExecute($link, "SELECT id_pet, nome, id_cliente FROM Pets ORDER BY nome ASC");
while ($r = mysqli_fetch_assoc($resP))
    $pets[] = $r;

DBClose($link);
?>

<!-- Modal Backdrop -->
<div id="eventModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 hidden z-50 flex justify-center items-center">
    <div class="bg-white rounded-xl shadow-lg w-full max-w-lg p-6 relative">
        <button onclick="closeEventModal()" class="absolute top-4 right-4 text-gray-500 hover:text-red-500">
            <span class="material-icons">close</span>
        </button>

        <h3 id="modalTitle" class="text-xl font-bold text-gray-800 mb-6">Novo Agendamento</h3>

        <form id="eventForm" class="space-y-4">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" id="eventId">

            <div>
                <label class="block text-sm font-medium text-gray-700">Título</label>
                <input type="text" name="titulo" id="eventTitle" required
                    class="w-full border rounded-lg p-2 focus:ring-2 focus:ring-cyan-500">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Início</label>
                    <input type="datetime-local" name="start" id="eventStart" required
                        class="w-full border rounded-lg p-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Fim</label>
                    <input type="datetime-local" name="end" id="eventEnd" required class="w-full border rounded-lg p-2">
                </div>
            </div>

            <div>
                <label
                    class="block text-sm font-medium text-gray-700"><?= AppHelper::isVetMode() ? "Veterinário" : "Colaborador" ?></label>
                <select name="id_vet" id="eventVet" class="w-full border rounded-lg p-2 select2-modal">
                    <option value="">Selecione...</option>
                    <?php foreach ($vets as $v): ?>
                        <option value="<?= $v['id_vet'] ?>">
                            <?= htmlspecialchars($v['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Cliente</label>
                <select name="id_cliente" id="eventClient" class="w-full border rounded-lg p-2 select2-modal"
                    onchange="filterPets()">
                    <option value="">(Opcional)</option>
                    <?php foreach ($clients as $c): ?>
                        <option value="<?= $c['id_cliente'] ?>">
                            <?= htmlspecialchars($c['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php if (AppHelper::isVetMode()): ?>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Pet</label>
                    <select name="id_pet" id="eventPet" class="w-full border rounded-lg p-2 select2-modal">
                        <option value="">(Opcional)</option>
                        <!-- Options populated via JS or PHP -->
                        <?php foreach ($pets as $p): ?>
                            <option value="<?= $p['id_pet'] ?>" data-client="<?= $p['id_cliente'] ?>">
                                <?= htmlspecialchars($p['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <div>
                <label class="block text-sm font-medium text-gray-700">Status</label>
                <select name="status" id="eventStatus" class="w-full border rounded-lg p-2">
                    <option value="Agendado">Agendado</option>
                    <option value="Confirmado">Confirmado</option>
                    <option value="Realizado">Realizado</option>
                    <option value="Cancelado">Cancelado</option>
                    <option value="Falta">Falta</option>
                </select>
            </div>

            <div>
                <div class="flex items-center justify-between mb-1">
                    <label class="block text-sm font-medium text-gray-700">Descrição / Obs</label>
                    <div id="badgeNotasContrato" class="hidden">
                        <button type="button" onclick="injetarNotasContratoNoAgendamento()" 
                            class="text-[11px] font-semibold text-cyan-700 bg-cyan-50 hover:bg-cyan-100 border border-cyan-200 px-2 py-0.5 rounded-full flex items-center gap-1 transition shadow-sm">
                            <span class="material-icons text-[13px]">engineering</span> Injetar Notas do Contrato
                        </button>
                    </div>
                </div>
                <textarea name="descricao" id="eventDesc" rows="3" class="w-full border rounded-lg p-2 text-sm focus:ring-2 focus:ring-cyan-500"></textarea>
            </div>

            <div class="flex justify-between items-center pt-4">
                <div class="flex gap-4">
                    <button type="button" id="btnDelete" onclick="deleteEvent()"
                        class="text-red-500 hover:text-red-700 font-medium hidden">Excluir</button>
                    <?php if (AppHelper::isVetMode()): ?>
                        <a href="#" id="btnStartConsultation"
                            class="text-green-600 hover:text-green-800 font-medium hidden flex items-center">
                            <span class="material-icons text-sm mr-1">medical_services</span> Iniciar Atendimento
                        </a>
                    <?php endif; ?>
                </div>
                <div class="flex justify-end gap-2 w-full">
                    <button type="button" onclick="closeEventModal()"
                        class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg">Cancelar</button>
                    <button type="submit"
                        class="px-4 py-2 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg shadow-md">Salvar</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    // Store pets for filtering
    const allPets = <?= json_encode($pets) ?>;

    let notasContratosClienteAtual = [];

    function carregarNotasContratosCliente(clientId) {
        notasContratosClienteAtual = [];
        $('#badgeNotasContrato').addClass('hidden');

        if (!clientId) return;

        $.get('api.php', {
            action: 'get_cliente_contrato_notas',
            id_cliente: clientId
        }, function (res) {
            if (res.success && res.notas && res.notas.length > 0) {
                notasContratosClienteAtual = res.notas;
                $('#badgeNotasContrato').removeClass('hidden');

                // Se for criação de agendamento e o campo de descrição estiver vazio, injeta automaticamente!
                const isNew = !$('#eventId').val();
                const descAtual = $.trim($('#eventDesc').val());
                if (isNew && !descAtual) {
                    injetarNotasContratoNoAgendamento();
                }
            }
        }, 'json');
    }

    function injetarNotasContratoNoAgendamento() {
        if (!notasContratosClienteAtual || notasContratosClienteAtual.length === 0) return;

        let textoInject = "";
        notasContratosClienteAtual.forEach(n => {
            textoInject += `[Contrato: ${n.servico}]\n${n.plain}\n\n`;
        });

        const descAtual = $.trim($('#eventDesc').val());
        if (descAtual && descAtual.indexOf('[Contrato:') === -1) {
            $('#eventDesc').val(descAtual + "\n\n" + $.trim(textoInject));
        } else {
            $('#eventDesc').val($.trim(textoInject));
        }
    }

    function filterPets() {
        const clientId = $('#eventClient').val();
        const petSelect = $('#eventPet');
        petSelect.empty().append('<option value="">(Opcional)</option>');

        allPets.forEach(pet => {
            if (!clientId || pet.id_cliente == clientId) {
                petSelect.append(new Option(pet.nome, pet.id_pet));
            }
        });
        petSelect.trigger('change');

        // Dispara busca das notas de contrato do cliente
        carregarNotasContratosCliente(clientId);
    }

    function openEventModal(eventOrDate) {
        // Reset form
        $('#eventForm')[0].reset();
        $('#eventId').val('');
        $('#btnDelete').addClass('hidden');
        $('#btnStartConsultation').addClass('hidden'); // Hide start button
        $('#modalTitle').text('Novo Agendamento');
        $('#eventVet').val('').trigger('change');
        $('#eventClient').val('').trigger('change');
        $('#eventPet').val('').trigger('change');

        if (eventOrDate.id) { // Edit
            $('#modalTitle').text('Editar Agendamento');
            $('#eventId').val(eventOrDate.id);
            $('#eventTitle').val(eventOrDate.title.split(' - ')[0]); // Extract title part
            $('#eventStart').val(formatDateLocal(eventOrDate.start));
            $('#eventEnd').val(formatDateLocal(eventOrDate.end));

            // Extended Props
            const props = eventOrDate.extendedProps;
            $('#eventVet').val(props.id_vet).trigger('change');
            $('#eventClient').val(props.id_cliente).trigger('change');

            // Wait for client change to filter pets, then set pet
            setTimeout(() => {
                $('#eventPet').val(props.id_pet).trigger('change');
            }, 100);

            $('#eventStatus').val(props.status);
            $('#eventDesc').val(props.descricao);

            $('#eventDesc').val(props.descricao);

            $('#btnDelete').removeClass('hidden');

            // Show Start Consultation if not new
            const startBtn = $('#btnStartConsultation');
            startBtn.removeClass('hidden');
            startBtn.attr('href', `../Vet/atendimento_form.php?id_agendamento=${eventOrDate.id}`);
        } else { // Create (Date Click)
            let start = eventOrDate.start || new Date(); // ISO String or Date
            let end = eventOrDate.end;

            // FullCalendar returns ISO strings often without time if Day view
            // If we get simple date string, set default time
            if (typeof start === 'string' && start.length === 10) {
                start += "T09:00:00";
            }
            if (end && typeof end === 'string' && end.length === 10) {
                end += "T10:00:00";
            } else if (!end) {
                // Default 1 hour
                let d = new Date(start);
                d.setHours(d.getHours() + 1);
                end = d.toISOString();
            }

            $('#eventStart').val(formatDateLocal(start));
            $('#eventEnd').val(formatDateLocal(end));

            // Default Vet to filter
            const filterVet = $('#filterVet').val();
            if (filterVet) {
                $('#eventVet').val(filterVet).trigger('change');
            }
        }

        $('#eventModal').removeClass('hidden');
        $('.select2-modal').select2({ dropdownParent: $('#eventModal'), width: '100%' });
    }

    function closeEventModal() {
        $('#eventModal').addClass('hidden');
    }

    // Helper to format Date for input[type=datetime-local] (YYYY-MM-DDTHH:mm)
    function formatDateLocal(dateInput) {
        if (!dateInput) return '';
        // In UTC mode, the date object/string is already the desired "clock time".
        let date = (dateInput instanceof Date) ? dateInput : new Date(dateInput);
        return date.toISOString().slice(0, 16);
    }

    function deleteEvent() {
        if (!confirm('Deseja realmente excluir este agendamento?')) return;

        $.post('api.php', {
            action: 'delete',
            id: $('#eventId').val()
        }, function (res) {
            if (res.success) {
                calendar.refetchEvents();
                closeEventModal();
            } else {
                alert('Erro ao excluir.');
            }
        }, 'json');
    }

    $('#eventForm').submit(function (e) {
        e.preventDefault();

        $.post('api.php', $(this).serialize(), function (res) {
            if (res.success) {
                calendar.refetchEvents();
                closeEventModal();
            } else {
                alert('Erro ao salvar: ' + (res.message || 'Erro desconhecido'));
            }
        }, 'json');
    });
</script>