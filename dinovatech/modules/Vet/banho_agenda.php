<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../../login.php");
    exit();
}
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../helpers/AppHelper.php';
if (!AppHelper::isVetMode()) {
    header("Location: ../../dashboard.php");
    exit();
}
include "../../../database.php";

$link = DBConnect();

// Fetch Colaboradores / Banhistas / Tosadores
$colaboradores = [];
$res = DBExecute($link, "SELECT id_vet, nome, crmv FROM Veterinarios ORDER BY nome");
while ($row = mysqli_fetch_assoc($res)) {
    $colaboradores[] = $row;
}

// Fetch Services available for Banho e Tosa
$servicos_banho = [];
$resS = DBExecute($link, "SELECT id_servico, nome_servico, duracao_minutos, valor_sugerido, icone_servico 
                          FROM Servicos 
                          WHERE disponivel_banho = 1 OR (disponivel_clinica = 0 AND disponivel_banho = 0)
                          ORDER BY nome_servico ASC");
while ($s = mysqli_fetch_assoc($resS)) {
    $servicos_banho[] = $s;
}

// Fetch Pets with Porte & Pelagem
$pets = [];
$resP = DBExecute($link, "SELECT p.id_pet, p.nome, p.id_cliente, p.porte, p.tipo_pelagem, p.preferencias_banho, c.nome as nome_tutor 
                          FROM Pets p 
                          JOIN Clientes c ON p.id_cliente = c.id_cliente 
                          WHERE c.ativo = 1 
                          ORDER BY p.nome ASC");
while ($p = mysqli_fetch_assoc($resP)) {
    $pets[] = $p;
}

// Fetch Clientes
$clientes = [];
$resC = DBExecute($link, "SELECT id_cliente, nome, telefone, email FROM Clientes WHERE ativo = 1 ORDER BY nome ASC");
while ($c = mysqli_fetch_assoc($resC)) {
    $clientes[] = $c;
}

DBClose($link);
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <title>Agenda de Banho & Tosa - DinoVet</title>
    <?php include '../../components/layout_head.php'; ?>
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/main.min.css' rel='stylesheet' />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.10/locales-all.global.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .fc-event {
            cursor: pointer;
            border-radius: 6px;
            padding: 2px 4px;
            font-size: 0.82rem;
            border: none;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .select2-container .select2-selection--single {
            height: 42px;
            padding-top: 6px;
            border-color: #e5e7eb;
            border-radius: 0.5rem;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 40px;
        }
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
                <div>
                    <h2 class="text-3xl font-bold text-gray-800 flex items-center">
                        <span class="material-icons text-teal-600 mr-2">shower</span> Agenda de Banho & Tosa
                    </h2>
                    <p class="text-gray-500 text-sm">Grade de agendamentos por colaborador, com cálculo de tempo por porte e saldo de pacotes.</p>
                </div>

                <div class="flex flex-wrap items-center gap-3 w-full md:w-auto">
                    <a href="banho_producao.php" 
                        class="bg-slate-800 hover:bg-slate-900 text-white font-medium py-2 px-4 rounded-lg flex items-center transition shadow-sm text-sm">
                        <span class="material-icons text-sm mr-1.5 text-teal-400">view_kanban</span> Esteira
                    </a>

                    <button type="button" onclick="novoAgendamentoBanho()"
                        class="bg-teal-600 hover:bg-teal-700 text-white font-semibold py-2 px-4 rounded-lg flex items-center transition shadow-sm text-sm">
                        <span class="material-icons text-sm mr-1.5">add</span> Novo Agendamento
                    </button>

                    <div class="w-full md:w-56">
                        <select id="filterColaborador" class="w-full border rounded-lg p-2 text-sm bg-white">
                            <option value="">Todos os Colaboradores</option>
                            <?php foreach ($colaboradores as $colab): ?>
                                <option value="<?= $colab['id_vet'] ?>">
                                    <?= htmlspecialchars($colab['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Calendar Container -->
            <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 h-[calc(100vh-190px)]">
                <div id="calendar" class="h-full"></div>
            </div>

        </main>
    </div>

    <!-- Modal de Agendamento de Banho & Tosa -->
    <div id="modalBanhoAgendamento" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-xl w-full p-6 shadow-2xl overflow-y-auto max-h-[90vh]">
            <div class="flex justify-between items-center mb-4 border-b pb-3">
                <div class="flex items-center gap-2">
                    <span class="material-icons text-teal-600">shower</span>
                    <h3 class="text-lg font-bold text-gray-800" id="modalTitle">Agendar Banho / Tosa</h3>
                </div>
                <button type="button" onclick="fecharModalBanho()" class="text-gray-400 hover:text-gray-600">
                    <span class="material-icons">close</span>
                </button>
            </div>

            <form id="formBanhoAgendamento" class="space-y-4">
                <input type="hidden" name="id_agendamento" id="agendamento_id">
                <input type="hidden" name="tipo_agenda" value="banho_tosa">

                <!-- Tutor / Cliente -->
                <div>
                    <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Tutor (Cliente) *</label>
                    <select name="id_cliente" id="modal_id_cliente" class="w-full select2-cliente" required>
                        <option value="">Selecione o tutor...</option>
                        <?php foreach ($clientes as $c): ?>
                            <option value="<?= $c['id_cliente'] ?>">
                                <?= htmlspecialchars($c['nome']) ?> (<?= $c['telefone'] ?: 'Sem tel' ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Pet -->
                <div>
                    <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Pet *</label>
                    <select name="id_pet" id="modal_id_pet" class="w-full border-gray-300 rounded-lg p-2.5 border text-sm" required>
                        <option value="">Selecione o pet...</option>
                        <?php foreach ($pets as $p): ?>
                            <option value="<?= $p['id_pet'] ?>" 
                                data-cliente="<?= $p['id_cliente'] ?>" 
                                data-porte="<?= $p['porte'] ?>" 
                                data-pelagem="<?= $p['tipo_pelagem'] ?>" 
                                data-preferencias="<?= htmlspecialchars($p['preferencias_banho'] ?? '') ?>">
                                <?= htmlspecialchars($p['nome']) ?> (Porte <?= $p['porte'] ?> • <?= $p['tipo_pelagem'] ?> - Tutor: <?= htmlspecialchars($p['nome_tutor']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Tags / Ficha de Preferências do Pet Selecionado -->
                <div id="boxPreferenciasPet" class="hidden bg-teal-50 border border-teal-200 rounded-xl p-3 text-xs text-teal-900">
                    <div class="font-bold flex items-center gap-1 mb-1">
                        <span class="material-icons text-sm text-teal-600">info</span> Cuidados & Preferências deste Pet:
                    </div>
                    <p id="textoPreferenciasPet" class="font-medium"></p>
                </div>

                <!-- Serviço -->
                <div>
                    <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Serviço de Banho/Tosa *</label>
                    <select name="id_servico" id="modal_id_servico" class="w-full border-gray-300 rounded-lg p-2.5 border text-sm" required>
                        <option value="">Selecione o serviço...</option>
                        <?php foreach ($servicos_banho as $sb): ?>
                            <option value="<?= $sb['id_servico'] ?>" data-duracao="<?= (int)$sb['duracao_minutos'] ?>" data-valor="<?= (float)$sb['valor_sugerido'] ?>">
                                <?= htmlspecialchars($sb['nome_servico']) ?> (<?= (int)$sb['duracao_minutos'] ?> min • R$ <?= number_format($sb['valor_sugerido'], 2, ',', '.') ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Banner de Status na Esteira de Produção -->
                <div id="boxStatusEsteira" class="hidden bg-slate-900 text-white rounded-xl p-3 text-xs flex items-center justify-between shadow-sm">
                    <div class="flex items-center gap-2">
                        <span class="material-icons text-teal-400 text-base">view_kanban</span>
                        <div>
                            <span class="text-gray-400 block text-[10px] uppercase font-bold tracking-wider">Status na Linha de Produção</span>
                            <span id="textoStatusEsteira" class="font-extrabold text-sm text-teal-300"></span>
                        </div>
                    </div>
                    <a href="banho_producao.php" class="bg-teal-600 hover:bg-teal-700 text-white text-xs px-3 py-1.5 rounded-lg font-bold transition flex items-center gap-1">
                        <span>Ver Esteira</span>
                        <span class="material-icons text-xs">arrow_forward</span>
                    </a>
                </div>

                <!-- Saldo de Pacote do Cliente (Detecção Inteligente) -->
                <div id="boxSaldoPacote" class="hidden bg-amber-50 border border-amber-200 rounded-xl p-3 text-xs text-amber-900">
                    <div class="flex items-center justify-between mb-1">
                        <span class="font-bold flex items-center gap-1 text-amber-800">
                            <span class="material-icons text-sm text-amber-600">card_giftcard</span> Pacote Ativo Encontrado!
                        </span>
                        <span class="badge-saldo bg-amber-200 text-amber-900 px-2 py-0.5 rounded-full font-bold"></span>
                    </div>
                    <label class="flex items-center space-x-2 cursor-pointer mt-1 font-medium">
                        <input type="checkbox" name="usar_saldo_pacote" id="modal_usar_saldo_pacote" value="1" checked
                            class="h-4 w-4 text-amber-600 focus:ring-amber-500 border-gray-300 rounded">
                        <span>Consumir 1 crédito do pacote para este agendamento (Valor: R$ 0,00)</span>
                    </label>
                    <input type="hidden" name="id_cliente_pacote" id="modal_id_cliente_pacote">
                </div>

                <!-- Profissional / Banhista -->
                <div>
                    <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Colaborador / Banhista *</label>
                    <select name="id_vet" id="modal_id_vet" class="w-full border-gray-300 rounded-lg p-2.5 border text-sm" required>
                        <option value="">Selecione o profissional...</option>
                        <?php foreach ($colaboradores as $c): ?>
                            <option value="<?= $c['id_vet'] ?>">
                                <?= htmlspecialchars($c['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Data, Hora Início e Fim (Calculada) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Data & Hora de Início *</label>
                        <input type="datetime-local" name="data_inicio" id="modal_data_inicio" required
                            class="w-full border-gray-300 rounded-lg p-2.5 border text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Data & Hora de Término</label>
                        <input type="datetime-local" name="data_fim" id="modal_data_fim" required
                            class="w-full border-gray-300 rounded-lg p-2.5 border text-sm bg-gray-50">
                        <span class="text-[11px] text-gray-400" id="tempoCalculadoHint">Calculado por duração + porte</span>
                    </div>
                </div>

                <!-- Status & Observações -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Status</label>
                        <select name="status" id="modal_status" class="w-full border-gray-300 rounded-lg p-2.5 border text-sm">
                            <option value="Agendado">Agendado</option>
                            <option value="Confirmado">Confirmado</option>
                            <option value="Realizado">Realizado</option>
                            <option value="Cancelado">Cancelado</option>
                            <option value="Falta">Falta</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Observações do Agendamento</label>
                        <input type="text" name="descricao" id="modal_descricao" placeholder="Ex: Tosa na tesoura 2cm, corte de unha..."
                            class="w-full border-gray-300 rounded-lg p-2.5 border text-sm">
                    </div>
                </div>

                <div id="modalMessage" class="text-xs font-medium text-center hidden"></div>

                <div class="flex justify-between items-center pt-3 border-t">
                    <button type="button" id="btnExcluirAgendamento" onclick="excluirAgendamentoBanho()"
                        class="text-red-600 hover:text-red-800 text-xs font-semibold flex items-center gap-1 hidden">
                        <span class="material-icons text-sm">delete</span> Excluir
                    </button>
                    <div class="flex gap-2 ml-auto">
                        <button type="button" onclick="fecharModalBanho()"
                            class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200">Cancelar</button>
                        <button type="submit"
                            class="px-5 py-2 bg-teal-600 hover:bg-teal-700 text-white rounded-lg text-sm font-semibold shadow transition">Salvar Agendamento</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <?php include '../../components/layout_scripts.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        let calendar;

        document.addEventListener('DOMContentLoaded', function () {
            const calendarEl = document.getElementById('calendar');

            calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: window.innerWidth < 768 ? 'timeGridDay' : 'timeGridWeek',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
                },
                locale: 'pt-br',
                slotMinTime: '07:00:00',
                slotMaxTime: '20:00:00',
                allDaySlot: false,
                slotDuration: '00:15:00',
                slotLabelInterval: '00:30:00',
                nowIndicator: true,
                events: function (fetchInfo, successCallback, failureCallback) {
                    const idVet = $('#filterColaborador').val();
                    $.ajax({
                        url: '../Agenda/api.php?action=get_events',
                        type: 'GET',
                        data: {
                            start: fetchInfo.startStr,
                            end: fetchInfo.endStr,
                            id_vet: idVet,
                            tipo_agenda: 'banho_tosa'
                        },
                        success: function (data) {
                            successCallback(data);
                        },
                        error: function () {
                            failureCallback();
                        }
                    });
                },
                dateClick: function (info) {
                    novoAgendamentoBanho(info.dateStr);
                },
                eventClick: function (info) {
                    abrirEdicaoBanho(info.event);
                }
            });

            calendar.render();

            $('#filterColaborador').on('change', function () {
                calendar.refetchEvents();
            });

            // Recalculate duration when Service or Pet changes
            $('#modal_id_servico, #modal_id_pet, #modal_data_inicio').on('change', function () {
                recalcularHorarioFim();
            });

            // Auto-filter pets when client is selected
            $('#modal_id_cliente').on('change', function () {
                const idCli = $(this).val();
                $('#modal_id_pet option').each(function () {
                    const petCli = $(this).data('cliente');
                    if (!idCli || petCli == idCli || $(this).val() === "") {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });

                // Check packages for this client and service
                verificarPacotesCliente();
            });

            $('#modal_id_pet').on('change', function () {
                const opt = $(this).find('option:selected');
                const prefs = opt.data('preferencias');
                if (prefs && prefs.trim() !== '') {
                    $('#textoPreferenciasPet').text(prefs);
                    $('#boxPreferenciasPet').removeClass('hidden');
                } else {
                    $('#boxPreferenciasPet').addClass('hidden');
                }

                const idCli = opt.data('cliente');
                if (idCli && $('#modal_id_cliente').val() != idCli) {
                    $('#modal_id_cliente').val(idCli).trigger('change');
                }
            });

            $('#modal_id_servico').on('change', function () {
                verificarPacotesCliente();
            });

            // Form Submit
            $('#formBanhoAgendamento').on('submit', function (e) {
                e.preventDefault();
                const btn = $(this).find('button[type="submit"]');
                btn.prop('disabled', true).text('Salvando...');

                const formData = $(this).serialize();
                $.ajax({
                    url: '../Agenda/api.php?action=save',
                    type: 'POST',
                    data: formData,
                    dataType: 'json',
                    success: function (res) {
                        if (res.success) {
                            fecharModalBanho();
                            calendar.refetchEvents();
                        } else {
                            $('#modalMessage').removeClass('hidden text-green-600').addClass('text-red-600').text(res.message);
                            btn.prop('disabled', false).text('Salvar Agendamento');
                        }
                    },
                    error: function () {
                        alert('Erro ao salvar agendamento.');
                        btn.prop('disabled', false).text('Salvar Agendamento');
                    }
                });
            });
        });

        function recalcularHorarioFim() {
            const dtInicioStr = $('#modal_data_inicio').val();
            if (!dtInicioStr) return;

            const servicoOpt = $('#modal_id_servico option:selected');
            const duracaoBase = parseInt(servicoOpt.data('duracao')) || 30;

            const petOpt = $('#modal_id_pet option:selected');
            const porte = petOpt.data('porte') || 'P';
            const pelagem = petOpt.data('pelagem') || 'Curto';

            // Multiplier by Porte
            let multPorte = 1.0;
            if (porte === 'M') multPorte = 1.2;
            if (porte === 'G') multPorte = 1.5;
            if (porte === 'GG') multPorte = 2.0;

            let duracaoFinal = Math.round(duracaoBase * multPorte);

            // Additional for dense/long hair
            if (pelagem === 'Longo' || pelagem === 'Dupla Pelagem') {
                duracaoFinal += 15;
            }

            const dtInicio = new Date(dtInicioStr);
            const dtFim = new Date(dtInicio.getTime() + duracaoFinal * 60000);

            // Format YYYY-MM-DDTHH:MM
            const pad = (n) => n < 10 ? '0' + n : n;
            const fimFormatted = dtFim.getFullYear() + '-' + pad(dtFim.getMonth() + 1) + '-' + pad(dtFim.getDate()) + 'T' + pad(dtFim.getHours()) + ':' + pad(dtFim.getMinutes());
            
            $('#modal_data_fim').val(fimFormatted);
            $('#tempoCalculadoHint').text(`Duração ajustada: ${duracaoFinal} min (Base: ${duracaoBase}m • Porte ${porte} • ${pelagem})`);
        }

        function verificarPacotesCliente() {
            const idCliente = $('#modal_id_cliente').val();
            const idServico = $('#modal_id_servico').val();

            if (!idCliente || !idServico) {
                $('#boxSaldoPacote').addClass('hidden');
                return;
            }

            $.getJSON('../../app.php', {
                action: 'get_cliente_pacotes_saldo',
                id_cliente: idCliente,
                id_servico: idServico
            }, function (res) {
                if (res.success && res.saldos && res.saldos.length > 0) {
                    const saldo = res.saldos[0];
                    $('#boxSaldoPacote').removeClass('hidden');
                    $('.badge-saldo').text(`${saldo.saldo_restante} crédito(s) restante(s) do ${saldo.nome_pacote}`);
                    $('#modal_id_cliente_pacote').val(saldo.id_cliente_pacote);
                    $('#modal_usar_saldo_pacote').prop('checked', true);
                } else {
                    $('#boxSaldoPacote').addClass('hidden');
                    $('#modal_id_cliente_pacote').val('');
                }
            });
        }

        function novoAgendamentoBanho(dateStr = null) {
            $('#formBanhoAgendamento')[0].reset();
            $('#agendamento_id').val('');
            $('#modalTitle').text('Agendar Banho / Tosa');
            $('#btnExcluirAgendamento').addClass('hidden');
            $('#boxPreferenciasPet, #boxSaldoPacote, #boxStatusEsteira, #modalMessage').addClass('hidden');

            if (dateStr) {
                let startStr = dateStr;
                if (startStr.length === 10) startStr += 'T09:00';
                $('#modal_data_inicio').val(startStr.substring(0, 16));
                recalcularHorarioFim();
            } else {
                const now = new Date();
                const pad = (n) => n < 10 ? '0' + n : n;
                const nowStr = now.getFullYear() + '-' + pad(now.getMonth() + 1) + '-' + pad(now.getDate()) + 'T09:00';
                $('#modal_data_inicio').val(nowStr);
                recalcularHorarioFim();
            }

            $('#modalBanhoAgendamento').removeClass('hidden');
            $('.select2-cliente').select2({
                dropdownParent: $('#modalBanhoAgendamento'),
                placeholder: "Selecione o tutor...",
                width: '100%'
            });
        }

        function abrirEdicaoBanho(event) {
            const props = event.extendedProps || {};
            $('#agendamento_id').val(event.id);
            $('#modalTitle').text('Editar Agendamento de Banho');
            $('#modal_descricao').val(props.descricao || '');
            $('#modal_status').val(props.status || 'Agendado');
            $('#modal_id_vet').val(props.id_vet || '');
            $('#modal_id_cliente').val(props.id_cliente || '').trigger('change');
            $('#modal_id_pet').val(props.id_pet || '').trigger('change');
            $('#modal_id_servico').val(props.id_servico || '').trigger('change');

            // Renderizar status na esteira se existir
            if (props.esteira_etapa) {
                const mapEtapas = {
                    aguardando: '⏳ 1. Recepção / Aguardando',
                    em_banho: '🛁 2. Em Banho & Hidratação',
                    secagem: '💨 3. Secagem & Soprador',
                    tosa_finalizacao: '✂️ 4. Tosa / Finalização',
                    pronto: '🐾 5. Pronto para Retirada',
                    finalizado: '✅ Entregue ao Tutor'
                };
                $('#textoStatusEsteira').text(mapEtapas[props.esteira_etapa] || props.esteira_etapa);
                $('#boxStatusEsteira').removeClass('hidden');
            } else {
                $('#boxStatusEsteira').addClass('hidden');
            }

            const pad = (n) => n < 10 ? '0' + n : n;
            if (event.start) {
                const s = event.start;
                $('#modal_data_inicio').val(s.getFullYear() + '-' + pad(s.getMonth() + 1) + '-' + pad(s.getDate()) + 'T' + pad(s.getHours()) + ':' + pad(s.getMinutes()));
            }
            if (event.end) {
                const e = event.end;
                $('#modal_data_fim').val(e.getFullYear() + '-' + pad(e.getMonth() + 1) + '-' + pad(e.getDate()) + 'T' + pad(e.getHours()) + ':' + pad(e.getMinutes()));
            }

            $('#btnExcluirAgendamento').removeClass('hidden');
            $('#modalBanhoAgendamento').removeClass('hidden');
            $('.select2-cliente').select2({
                dropdownParent: $('#modalBanhoAgendamento'),
                placeholder: "Selecione o tutor...",
                width: '100%'
            });
        }

        function fecharModalBanho() {
            $('#modalBanhoAgendamento').addClass('hidden');
        }

        function excluirAgendamentoBanho() {
            const id = $('#agendamento_id').val();
            if (!id) return;
            if (confirm('Tem certeza que deseja cancelar este agendamento?')) {
                $.post('../Agenda/api.php?action=delete', { id: id }, function (res) {
                    if (res.success) {
                        fecharModalBanho();
                        calendar.refetchEvents();
                    } else {
                        alert(res.message || 'Erro ao excluir.');
                    }
                }, 'json');
            }
        }
    </script>
</body>

</html>
