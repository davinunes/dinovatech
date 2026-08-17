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

// Check if check-in photo is enabled in config
$checkin_foto_ativo = false;
$resConfig = DBExecute($link, "SELECT banho_checkin_foto_ativo FROM ConfiguracoesEmissor WHERE id_config = 1");
if ($resConfig && $cfg = mysqli_fetch_assoc($resConfig)) {
    $checkin_foto_ativo = ($cfg['banho_checkin_foto_ativo'] == 1);
}

// Fetch Colaboradores for filter/assignment
$colaboradores = [];
$resColab = DBExecute($link, "SELECT id_vet, nome FROM Veterinarios ORDER BY nome");
if ($resColab) {
    while ($c = mysqli_fetch_assoc($resColab)) {
        $colaboradores[] = $c;
    }
}

// Fetch Pets for direct check-in
$pets = [];
$resPets = DBExecute($link, "SELECT p.id_pet, p.nome, p.porte, p.tipo_pelagem, p.preferencias_banho, c.nome as nome_tutor, c.telefone as telefone_tutor, c.email as email_tutor 
                             FROM Pets p 
                             JOIN Clientes c ON p.id_cliente = c.id_cliente 
                             WHERE c.ativo = 1 
                             ORDER BY p.nome ASC");
if ($resPets) {
    while ($p = mysqli_fetch_assoc($resPets)) {
        $pets[] = $p;
    }
}

DBClose($link);
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <title>Linha de Produção Banho & Tosa - DinoVet</title>
    <?php include '../../components/layout_head.php'; ?>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .kanban-col {
            min-height: calc(100vh - 240px);
        }
        .modo-tv-active {
            padding: 1rem !important;
            background-color: #0f172a !important;
            color: #fff !important;
        }
        .modo-tv-active .kanban-card {
            background-color: #1e293b !important;
            color: #f1f5f9 !important;
            border-color: #334155 !important;
        }
        .modo-tv-active .kanban-header {
            background-color: #1e293b !important;
            color: #94a3b8 !important;
            border-color: #334155 !important;
        }
        .pulse-live {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: .5; }
        }
    </style>
</head>

<body class="bg-gray-100 flex min-h-screen">

    <div id="sidebarWrapper">
        <?php include '../../components/sidebar.php'; ?>
    </div>

    <div id="mainContent" class="flex-1 flex flex-col lg:ml-64 min-h-screen transition-all duration-300">
        <main class="flex-1 p-4 lg:p-6 mt-16 lg:mt-0">

            <!-- Top Header & Actions -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                <div>
                    <div class="flex items-center gap-3">
                        <h2 class="text-2xl lg:text-3xl font-extrabold text-gray-800 flex items-center">
                            <span class="material-icons text-teal-600 mr-2 text-3xl">view_kanban</span> Esteira de Banho & Tosa
                        </h2>
                        <span class="flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-teal-100 text-teal-800 border border-teal-200">
                            <span class="w-2 h-2 rounded-full bg-teal-500 pulse-live"></span> AO VIVO
                        </span>
                    </div>
                    <p class="text-gray-500 text-sm mt-0.5">Acompanhamento operacional em tempo real e notificações automáticas aos tutores.</p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" onclick="openCheckinModal()"
                        class="bg-teal-600 hover:bg-teal-700 text-white font-semibold py-2 px-4 rounded-xl flex items-center transition shadow-sm text-sm">
                        <span class="material-icons text-sm mr-1.5">login</span> Novo Check-in / Entrada
                    </button>

                    <button type="button" onclick="toggleModoTV()" id="btnModoTV"
                        class="bg-slate-800 hover:bg-slate-900 text-white font-medium py-2 px-4 rounded-xl flex items-center transition shadow-sm text-sm">
                        <span class="material-icons text-sm mr-1.5 text-amber-400">tv</span> Modo TV (Tela Cheia)
                    </button>

                    <button type="button" onclick="carregarEsteira()" title="Atualizar agora"
                        class="bg-white hover:bg-gray-50 border border-gray-200 text-gray-700 p-2 rounded-xl transition shadow-sm">
                        <span class="material-icons text-lg text-gray-600">refresh</span>
                    </button>
                </div>
            </div>

            <!-- Kanban Board Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-4" id="kanbanBoard">

                <!-- 1. AGUARDANDO / RECEPÇÃO -->
                <div class="flex flex-col bg-slate-50 rounded-2xl p-3 border border-slate-200 shadow-sm kanban-col">
                    <div class="flex items-center justify-between p-2.5 mb-3 bg-white rounded-xl border border-slate-200 kanban-header">
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-amber-500"></span>
                            <h3 class="font-bold text-xs uppercase tracking-wider text-slate-700">1. Recepção / Fila</h3>
                        </div>
                        <span class="badge-count px-2 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800" id="count_aguardando">0</span>
                    </div>
                    <div class="flex-1 space-y-3 overflow-y-auto" id="col_aguardando">
                        <!-- Cards -->
                    </div>
                </div>

                <!-- 2. BANHO & HIDRATAÇÃO -->
                <div class="flex flex-col bg-cyan-50/50 rounded-2xl p-3 border border-cyan-200/60 shadow-sm kanban-col">
                    <div class="flex items-center justify-between p-2.5 mb-3 bg-white rounded-xl border border-cyan-200 kanban-header">
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-cyan-500"></span>
                            <h3 class="font-bold text-xs uppercase tracking-wider text-cyan-800">2. Em Banho</h3>
                        </div>
                        <span class="badge-count px-2 py-0.5 rounded-full text-xs font-bold bg-cyan-100 text-cyan-800" id="count_em_banho">0</span>
                    </div>
                    <div class="flex-1 space-y-3 overflow-y-auto" id="col_em_banho">
                        <!-- Cards -->
                    </div>
                </div>

                <!-- 3. SECAGEM & SOPRADOR -->
                <div class="flex flex-col bg-blue-50/50 rounded-2xl p-3 border border-blue-200/60 shadow-sm kanban-col">
                    <div class="flex items-center justify-between p-2.5 mb-3 bg-white rounded-xl border border-blue-200 kanban-header">
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-blue-500"></span>
                            <h3 class="font-bold text-xs uppercase tracking-wider text-blue-800">3. Secagem</h3>
                        </div>
                        <span class="badge-count px-2 py-0.5 rounded-full text-xs font-bold bg-blue-100 text-blue-800" id="count_secagem">0</span>
                    </div>
                    <div class="flex-1 space-y-3 overflow-y-auto" id="col_secagem">
                        <!-- Cards -->
                    </div>
                </div>

                <!-- 4. TOSA & ESTÉTICA -->
                <div class="flex flex-col bg-purple-50/50 rounded-2xl p-3 border border-purple-200/60 shadow-sm kanban-col">
                    <div class="flex items-center justify-between p-2.5 mb-3 bg-white rounded-xl border border-purple-200 kanban-header">
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-purple-500"></span>
                            <h3 class="font-bold text-xs uppercase tracking-wider text-purple-800">4. Tosa / Estética</h3>
                        </div>
                        <span class="badge-count px-2 py-0.5 rounded-full text-xs font-bold bg-purple-100 text-purple-800" id="count_tosa_finalizacao">0</span>
                    </div>
                    <div class="flex-1 space-y-3 overflow-y-auto" id="col_tosa_finalizacao">
                        <!-- Cards -->
                    </div>
                </div>

                <!-- 5. PRONTO PARA ENTREGA -->
                <div class="flex flex-col bg-emerald-50/50 rounded-2xl p-3 border border-emerald-200/60 shadow-sm kanban-col">
                    <div class="flex items-center justify-between p-2.5 mb-3 bg-white rounded-xl border border-emerald-200 kanban-header">
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-emerald-500"></span>
                            <h3 class="font-bold text-xs uppercase tracking-wider text-emerald-800">5. Pronto (Notificar)</h3>
                        </div>
                        <span class="badge-count px-2 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800" id="count_pronto">0</span>
                    </div>
                    <div class="flex-1 space-y-3 overflow-y-auto" id="col_pronto">
                        <!-- Cards -->
                    </div>
                </div>

            </div>

        </main>
    </div>

    <!-- Modal Novo Check-in / Entrada -->
    <div id="modalCheckin" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl overflow-y-auto max-h-[90vh]">
            <div class="flex justify-between items-center mb-4 border-b pb-3">
                <div class="flex items-center gap-2">
                    <span class="material-icons text-teal-600">login</span>
                    <h3 class="text-lg font-bold text-gray-800">Recepção de Pet (Check-in)</h3>
                </div>
                <button type="button" onclick="closeCheckinModal()" class="text-gray-400 hover:text-gray-600">
                    <span class="material-icons">close</span>
                </button>
            </div>

            <form id="formCheckin" enctype="multipart/form-data" class="space-y-4">
                <input type="hidden" name="action" value="criar_checkin_banho">

                <div>
                    <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Selecione o Pet *</label>
                    <select name="id_pet" id="checkin_id_pet" class="w-full select2-checkin" required>
                        <option value="">Buscar pet por nome ou tutor...</option>
                        <?php foreach ($pets as $p): ?>
                            <option value="<?= $p['id_pet'] ?>"
                                data-porte="<?= $p['porte'] ?>"
                                data-pelagem="<?= $p['tipo_pelagem'] ?>"
                                data-preferencias="<?= htmlspecialchars($p['preferencias_banho'] ?? '') ?>"
                                data-tutor="<?= htmlspecialchars($p['nome_tutor']) ?>">
                                <?= htmlspecialchars($p['nome']) ?> (Tutor: <?= htmlspecialchars($p['nome_tutor']) ?> • Porte <?= $p['porte'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Pet Preferences Alert -->
                <div id="checkinPreferenciasAlert" class="hidden bg-amber-50 border border-amber-200 rounded-xl p-3 text-xs text-amber-900">
                    <span class="font-bold flex items-center gap-1 mb-1 text-amber-800">
                        <span class="material-icons text-sm">warning</span> Preferências / Alertas Cadastrados:
                    </span>
                    <p id="checkinTextoPrefs" class="font-medium"></p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Colaborador / Banhista Responsável</label>
                    <select name="id_colaborador" id="checkin_id_colaborador" class="w-full border-gray-300 rounded-lg p-2.5 border text-sm">
                        <option value="">Qualquer colaborador disponível</option>
                        <?php foreach ($colaboradores as $c): ?>
                            <option value="<?= $c['id_vet'] ?>"><?= htmlspecialchars($c['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Observações da Recepção / Cortes</label>
                    <textarea name="observacoes_estetica" id="checkin_observacoes" rows="2"
                        placeholder="Ex: Tosa higiênica nas patinhas, tosar orelhas na máquina 4..."
                        class="w-full border-gray-300 rounded-lg p-2.5 border text-sm"></textarea>
                </div>

                <?php if ($checkin_foto_ativo): ?>
                    <!-- Check-in com Foto Ativo -->
                    <div class="bg-teal-50 border border-teal-200 rounded-xl p-4">
                        <label class="block text-xs font-bold text-teal-900 uppercase mb-1 flex items-center gap-1">
                            <span class="material-icons text-sm text-teal-600">photo_camera</span> Fotos de Vistoria / Nós / Avarias (Opcional)
                        </label>
                        <p class="text-xs text-teal-700 mb-2">Anexe fotos de nós intensos, lesões de pele ou ferimentos pré-existentes para resguardo da equipe.</p>
                        <input type="file" name="fotos_checkin[]" multiple accept="image/*"
                            class="w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-teal-600 file:text-white hover:file:bg-teal-700">
                    </div>
                <?php endif; ?>

                <div id="checkinMessage" class="text-xs font-medium text-center hidden"></div>

                <div class="flex justify-end gap-2 pt-3 border-t">
                    <button type="button" onclick="closeCheckinModal()"
                        class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200">Cancelar</button>
                    <button type="submit"
                        class="px-5 py-2 bg-teal-600 hover:bg-teal-700 text-white rounded-lg text-sm font-semibold shadow transition">Dar Entrada na Fila</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Visualizar Fotos do Pet -->
    <div id="modalFotos" class="fixed inset-0 bg-black bg-opacity-70 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-2xl w-full p-6 shadow-2xl">
            <div class="flex justify-between items-center mb-4 border-b pb-3">
                <div class="flex items-center gap-2">
                    <span class="material-icons text-teal-600">photo_library</span>
                    <h3 class="text-lg font-bold text-gray-800" id="modalFotosTitulo">Fotos de Vistoria</h3>
                </div>
                <button type="button" onclick="$('#modalFotos').addClass('hidden')" class="text-gray-400 hover:text-gray-600">
                    <span class="material-icons">close</span>
                </button>
            </div>
            <div id="fotosGrid" class="grid grid-cols-2 sm:grid-cols-3 gap-4 max-h-[60vh] overflow-y-auto p-1">
                <!-- Dynamic Images -->
            </div>
        </div>
    </div>

    <?php include '../../components/layout_scripts.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        let pollTimer = null;
        let isModoTV = false;

        $(document).ready(function () {
            carregarEsteira();

            // Auto polling every 15 seconds
            pollTimer = setInterval(carregarEsteira, 15000);

            $('#checkin_id_pet').on('change', function () {
                const opt = $(this).find('option:selected');
                const prefs = opt.data('preferencias');
                if (prefs && prefs.trim() !== '') {
                    $('#checkinTextoPrefs').text(prefs);
                    $('#checkinPreferenciasAlert').removeClass('hidden');
                } else {
                    $('#checkinPreferenciasAlert').addClass('hidden');
                }
            });

            $('#formCheckin').on('submit', function (e) {
                e.preventDefault();
                const btn = $(this).find('button[type="submit"]');
                btn.prop('disabled', true).text('Processando...');

                const formData = new FormData(this);

                $.ajax({
                    url: '../../app.php',
                    type: 'POST',
                    data: formData,
                    contentType: false,
                    processData: false,
                    dataType: 'json',
                    success: function (res) {
                        if (res.success) {
                            closeCheckinModal();
                            carregarEsteira();
                        } else {
                            $('#checkinMessage').removeClass('hidden text-green-600').addClass('text-red-600').text(res.message);
                            btn.prop('disabled', false).text('Dar Entrada na Fila');
                        }
                    },
                    error: function () {
                        alert('Erro de conexão ao criar check-in.');
                        btn.prop('disabled', false).text('Dar Entrada na Fila');
                    }
                });
            });
        });

        function carregarEsteira() {
            $.getJSON('../../app.php', { action: 'get_banho_producao_fila' }, function (res) {
                if (!res.success) return;

                // Clear columns
                $('#col_aguardando, #col_em_banho, #col_secagem, #col_tosa_finalizacao, #col_pronto').empty();
                $('#count_aguardando, #count_em_banho, #count_secagem, #count_tosa_finalizacao, #count_pronto').text('0');

                const counts = { aguardando: 0, em_banho: 0, secagem: 0, tosa_finalizacao: 0, pronto: 0 };

                res.fila.forEach(item => {
                    const etapa = item.etapa;
                    if (counts[etapa] !== undefined) {
                        counts[etapa]++;
                    }

                    const card = renderCard(item);
                    $(`#col_${etapa}`).append(card);
                });

                Object.keys(counts).forEach(k => {
                    $(`#count_${k}`).text(counts[k]);
                });
            });
        }

        function renderCard(item) {
            const hasFotos = item.total_fotos > 0;
            const prefs = item.preferencias_banho ? item.preferencias_banho.trim() : '';

            let badgesHtml = '';
            if (item.porte) {
                badgesHtml += `<span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-700">Porte ${item.porte}</span> `;
            }
            if (item.tipo_pelagem) {
                badgesHtml += `<span class="px-2 py-0.5 rounded text-[10px] font-medium bg-slate-100 text-slate-600">${item.tipo_pelagem}</span> `;
            }

            let alertPrefsHtml = '';
            if (prefs) {
                alertPrefsHtml = `
                    <div class="mt-2 p-2 bg-amber-50 rounded-lg border border-amber-200 text-[11px] text-amber-900 font-medium flex items-start gap-1">
                        <span class="material-icons text-amber-600 text-xs mt-0.5">warning</span>
                        <span>${prefs}</span>
                    </div>
                `;
            }

            let colabHtml = item.nome_colaborador ? `<span class="text-xs text-teal-700 font-medium">👤 ${item.nome_colaborador}</span>` : `<span class="text-xs text-gray-400 italic">Sem responsável</span>`;

            // Next and Prev Action buttons based on stage
            let actionsHtml = '';
            const idFila = item.id_fila;

            if (item.etapa === 'aguardando') {
                actionsHtml = `
                    <button onclick="moverEtapa(${idFila}, 'em_banho')" class="w-full py-1.5 px-3 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg text-xs font-semibold flex items-center justify-center gap-1 transition">
                        <span>Iniciar Banho</span> <span class="material-icons text-xs">arrow_forward</span>
                    </button>
                `;
            } else if (item.etapa === 'em_banho') {
                actionsHtml = `
                    <div class="grid grid-cols-2 gap-1.5">
                        <button onclick="moverEtapa(${idFila}, 'aguardando')" class="py-1 px-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-[11px] font-medium">Voltar</button>
                        <button onclick="moverEtapa(${idFila}, 'secagem')" class="py-1 px-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-[11px] font-semibold">Secagem ➔</button>
                    </div>
                `;
            } else if (item.etapa === 'secagem') {
                actionsHtml = `
                    <div class="grid grid-cols-2 gap-1.5">
                        <button onclick="moverEtapa(${idFila}, 'em_banho')" class="py-1 px-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-[11px] font-medium">Voltar</button>
                        <button onclick="moverEtapa(${idFila}, 'tosa_finalizacao')" class="py-1 px-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-[11px] font-semibold">Tosa ➔</button>
                    </div>
                `;
            } else if (item.etapa === 'tosa_finalizacao') {
                actionsHtml = `
                    <div class="grid grid-cols-2 gap-1.5">
                        <button onclick="moverEtapa(${idFila}, 'secagem')" class="py-1 px-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-[11px] font-medium">Voltar</button>
                        <button onclick="moverEtapa(${idFila}, 'pronto')" class="py-1 px-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-[11px] font-semibold">Finalizar ➔</button>
                    </div>
                `;
            } else if (item.etapa === 'pronto') {
                const telRaw = item.telefone_tutor ? item.telefone_tutor.replace(/\D/g, '') : '';
                const msgZap = encodeURIComponent(`Olá, ${item.nome_tutor}! Seu pet ${item.nome_pet} acabou de ficar pronto, limpinho e super cheiroso aqui no Banho e Tosa! 🐾 Você já pode vir buscá-lo.`);
                const linkZap = telRaw ? `https://api.whatsapp.com/send?phone=55${telRaw}&text=${msgZap}` : '#';

                actionsHtml = `
                    <div class="space-y-1.5">
                        <div class="grid grid-cols-2 gap-1.5">
                            <a href="${linkZap}" target="_blank" class="py-1.5 px-2 bg-emerald-500 hover:bg-emerald-600 text-white rounded-lg text-[11px] font-bold flex items-center justify-center gap-1 transition ${!telRaw ? 'opacity-50 pointer-events-none' : ''}">
                                <span>WhatsApp</span>
                            </a>
                            <button onclick="notificarEmailTutor(${idFila})" class="py-1.5 px-2 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg text-[11px] font-semibold flex items-center justify-center gap-1 transition">
                                <span class="material-icons text-xs">email</span> E-mail
                            </button>
                        </div>
                        <button onclick="moverEtapa(${idFila}, 'finalizado')" class="w-full py-1.5 px-2 bg-slate-800 hover:bg-slate-900 text-white rounded-lg text-[11px] font-bold flex items-center justify-center gap-1 transition">
                            <span class="material-icons text-xs">check_circle</span> Entregue ao Tutor
                        </button>
                    </div>
                `;
            }

            return `
                <div class="bg-white rounded-xl p-3.5 shadow-sm border border-slate-200/80 hover:shadow-md transition kanban-card">
                    <div class="flex justify-between items-start mb-2">
                        <div>
                            <h4 class="font-extrabold text-sm text-gray-900 leading-tight">${item.nome_pet}</h4>
                            <span class="text-xs text-gray-500">${item.nome_tutor}</span>
                        </div>
                        ${hasFotos ? `
                            <button onclick="abrirFotosModal(${idFila}, '${item.nome_pet}')" title="Ver fotos de vistoria"
                                class="p-1 rounded-lg bg-teal-50 text-teal-700 hover:bg-teal-100 transition">
                                <span class="material-icons text-sm">photo_camera</span>
                            </button>
                        ` : ''}
                    </div>

                    <div class="flex flex-wrap gap-1 mb-2">
                        ${badgesHtml}
                    </div>

                    ${alertPrefsHtml}

                    ${item.observacoes_estetica ? `
                        <p class="text-[11px] text-gray-600 bg-gray-50 p-2 rounded-lg my-2 border border-gray-100 italic">
                            "${item.observacoes_estetica}"
                        </p>
                    ` : ''}

                    <div class="flex items-center justify-between pt-2 border-t border-gray-100 mb-2.5">
                        ${colabHtml}
                        <span class="text-[10px] text-gray-400 font-mono">${item.horario_entrada_fmt || ''}</span>
                    </div>

                    ${actionsHtml}
                </div>
            `;
        }

        function moverEtapa(idFila, novaEtapa) {
            $.post('../../app.php', {
                action: 'update_etapa_banho',
                id_fila: idFila,
                nova_etapa: novaEtapa
            }, function (res) {
                if (res.success) {
                    carregarEsteira();
                } else {
                    alert(res.message || 'Erro ao mudar etapa.');
                }
            }, 'json');
        }

        function notificarEmailTutor(idFila) {
            if (confirm('Deseja disparar um e-mail ao tutor informando que o pet está pronto?')) {
                $.post('../../app.php', {
                    action: 'notificar_tutor_pronto_email',
                    id_fila: idFila
                }, function (res) {
                    alert(res.message);
                }, 'json');
            }
        }

        function abrirFotosModal(idFila, nomePet) {
            $('#modalFotosTitulo').text(`Fotos de Vistoria: ${nomePet}`);
            $('#fotosGrid').html('<div class="col-span-full text-center py-6 text-gray-400">Carregando fotos...</div>');
            $('#modalFotos').removeClass('hidden');

            $.getJSON('../../app.php', { action: 'get_fotos_banho_checkin', id_fila: idFila }, function (res) {
                if (res.success && res.fotos.length > 0) {
                    let html = '';
                    res.fotos.forEach(f => {
                        html += `
                            <div class="rounded-xl overflow-hidden border border-gray-200 shadow-sm bg-black flex items-center justify-center">
                                <img src="../../${f.foto_url}" alt="Foto" class="max-h-48 w-full object-cover cursor-pointer" onclick="window.open('../../${f.foto_url}', '_blank')">
                            </div>
                        `;
                    });
                    $('#fotosGrid').html(html);
                } else {
                    $('#fotosGrid').html('<div class="col-span-full text-center py-6 text-gray-400">Nenhuma foto encontrada.</div>');
                }
            });
        }

        function openCheckinModal() {
            $('#formCheckin')[0].reset();
            $('#checkinMessage, #checkinPreferenciasAlert').addClass('hidden');
            $('#modalCheckin').removeClass('hidden');
            $('.select2-checkin').select2({
                dropdownParent: $('#modalCheckin'),
                placeholder: "Buscar pet por nome ou tutor...",
                width: '100%'
            });
        }

        function closeCheckinModal() {
            $('#modalCheckin').addClass('hidden');
        }

        function toggleModoTV() {
            isModoTV = !isModoTV;
            if (isModoTV) {
                $('#sidebarWrapper').addClass('hidden');
                $('#mainContent').removeClass('lg:ml-64');
                $('body').addClass('modo-tv-active');
                $('#btnModoTV').html('<span class="material-icons text-sm mr-1.5 text-red-400">fullscreen_exit</span> Sair do Modo TV');
                if (document.documentElement.requestFullscreen) {
                    document.documentElement.requestFullscreen().catch(() => {});
                }
            } else {
                $('#sidebarWrapper').removeClass('hidden');
                $('#mainContent').addClass('lg:ml-64');
                $('body').removeClass('modo-tv-active');
                $('#btnModoTV').html('<span class="material-icons text-sm mr-1.5 text-amber-400">tv</span> Modo TV (Tela Cheia)');
                if (document.exitFullscreen) {
                    document.exitFullscreen().catch(() => {});
                }
            }
        }
    </script>
</body>

</html>
