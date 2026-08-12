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

// Fetch All Pets for New Hospitalization Modal
$pets_list = [];
$r_pets = DBExecute($link, "SELECT p.id_pet, p.nome as pet_nome, p.especie, p.raca, c.nome as tutor_nome 
                           FROM Pets p 
                           JOIN Clientes c ON p.id_cliente = c.id_cliente 
                           ORDER BY p.nome ASC");
if ($r_pets) {
    while ($p = mysqli_fetch_assoc($r_pets)) {
        $pets_list[] = $p;
    }
}

// Fetch Veterinarios
$vets_list = [];
$r_vets = DBExecute($link, "SELECT id_vet, nome, crmv FROM Veterinarios ORDER BY nome ASC");
if ($r_vets) {
    while ($v = mysqli_fetch_assoc($r_vets)) {
        $vets_list[] = $v;
    }
}

// Fetch Hospitalizations
$internacoes_list = [];
$q_int = "SELECT i.*, 
                 p.id_pet, p.nome as pet_nome, p.especie, p.raca, p.peso as pet_peso, p.sexo, p.data_nascimento,
                 c.id_cliente, c.nome as tutor_nome, c.telefone as tel_tutor,
                 v.nome as vet_nome, v.crmv as vet_crmv,
                 (SELECT COUNT(*) FROM InternacaoDias WHERE id_internacao = i.id_internacao) as qtd_dias
          FROM Internacoes i
          JOIN Pets p ON i.id_pet = p.id_pet
          JOIN Clientes c ON p.id_cliente = c.id_cliente
          LEFT JOIN Veterinarios v ON i.id_vet = v.id_vet
          ORDER BY (i.status = 'internado') DESC, i.data_internacao DESC";

$r_int = DBExecute($link, $q_int);
$count_internados = 0;
if ($r_int) {
    while ($row = mysqli_fetch_assoc($r_int)) {
        if ($row['status'] === 'internado') {
            $count_internados++;
        }
        $internacoes_list[] = $row;
    }
}

function calcularIdadeCard($data_nasc) {
    if (!$data_nasc) return "Não informada";
    $dob = new DateTime($data_nasc);
    $now = new DateTime();
    $diff = $now->diff($dob);
    $parts = [];
    if ($diff->y > 0) $parts[] = $diff->y . " ano" . ($diff->y > 1 ? 's' : '');
    if ($diff->m > 0) $parts[] = $diff->m . " mês" . ($diff->m > 1 ? 'es' : '');
    return empty($parts) ? "Menos de 1 mês" : implode(' e ', $parts);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <title>Sala de Internação - DinoVet</title>
    <?php include '../../components/layout_head.php'; ?>
</head>

<body class="bg-gray-50 flex">

    <?php include '../../components/sidebar.php'; ?>

    <div class="flex-1 flex flex-col lg:ml-64 min-h-screen transition-all duration-300">
        <main class="flex-1 p-4 sm:p-6 mt-16 lg:mt-0">

            <!-- Header Section -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                <div>
                    <div class="flex items-center gap-3">
                        <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 flex items-center">
                            <span class="material-icons text-rose-600 mr-2 text-3xl">local_hospital</span> Sala de Internação
                        </h1>
                        <span class="bg-rose-100 text-rose-800 text-xs font-bold px-3 py-1 rounded-full border border-rose-200">
                            <?= $count_internados ?> em andamento
                        </span>
                    </div>
                    <p class="text-xs sm:text-sm text-gray-500 mt-1">Painel prático de acompanhamento e ficha eletrônica para a sala de internação.</p>
                </div>
                <div>
                    <button onclick="openNovaInternacaoModal()"
                        class="w-full sm:w-auto bg-rose-600 text-white px-4 py-2.5 rounded-xl font-bold text-sm hover:bg-rose-700 transition shadow-md flex items-center justify-center gap-2">
                        <span class="material-icons text-lg">add</span> Nova Internação
                    </button>
                </div>
            </div>

            <!-- Search & Filter Controls -->
            <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 mb-6 flex flex-col md:flex-row items-center justify-between gap-4">
                <!-- Search Input -->
                <div class="relative w-full md:w-80">
                    <span class="material-icons absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-lg">search</span>
                    <input type="text" id="searchInput" onkeyup="filtrarCards()" placeholder="Buscar por pet, tutor, vet ou suspeita..."
                        class="w-full pl-10 pr-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-rose-500">
                </div>

                <!-- Status Filter Buttons -->
                <div class="flex items-center gap-1.5 overflow-x-auto w-full md:w-auto pb-1 md:pb-0">
                    <button type="button" onclick="filtrarStatus('internado')" id="btnFilter_internado"
                        class="filter-btn active-filter px-3 py-1.5 rounded-lg text-xs font-bold border transition bg-rose-600 text-white border-rose-600">
                        Em Internação (<?= $count_internados ?>)
                    </button>
                    <button type="button" onclick="filtrarStatus('alta')" id="btnFilter_alta"
                        class="filter-btn px-3 py-1.5 rounded-lg text-xs font-medium border transition bg-white text-gray-600 border-gray-200 hover:bg-gray-50">
                        Alta Médica
                    </button>
                    <button type="button" onclick="filtrarStatus('todos')" id="btnFilter_todos"
                        class="filter-btn px-3 py-1.5 rounded-lg text-xs font-medium border transition bg-white text-gray-600 border-gray-200 hover:bg-gray-50">
                        Todos (<?= count($internacoes_list) ?>)
                    </button>
                </div>
            </div>

            <!-- Hospitalizations Card Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="cardsGrid">
                <?php if (empty($internacoes_list)): ?>
                    <div class="col-span-full bg-white p-12 rounded-xl text-center text-gray-400 border border-gray-100">
                        <span class="material-icons text-5xl mb-2 opacity-30">local_hospital</span>
                        <p class="font-medium text-gray-600">Nenhuma internação registrada no sistema.</p>
                        <button onclick="openNovaInternacaoModal()" class="mt-3 bg-rose-600 text-white px-4 py-2 rounded-lg text-xs font-bold">
                            Registrar Internação
                        </button>
                    </div>
                <?php else: ?>
                    <?php foreach ($internacoes_list as $int): ?>
                        <?php
                        switch ($int['status']) {
                            case 'internado':
                                $status_class = 'bg-amber-100 text-amber-800 border-amber-200';
                                $status_label = 'Em Internação';
                                break;
                            case 'alta':
                                $status_class = 'bg-green-100 text-green-800 border-green-200';
                                $status_label = 'Alta Médica';
                                break;
                            case 'obito':
                                $status_class = 'bg-gray-100 text-gray-800 border-gray-200';
                                $status_label = 'Óbito';
                                break;
                            case 'cancelado':
                                $status_class = 'bg-red-100 text-red-800 border-red-200';
                                $status_label = 'Cancelado';
                                break;
                            default:
                                $status_class = 'bg-gray-100 text-gray-800';
                                $status_label = ucfirst($int['status']);
                                break;
                        }
                        $searchable_text = mb_strtolower($int['pet_nome'] . ' ' . $int['tutor_nome'] . ' ' . $int['vet_nome'] . ' ' . $int['suspeita_clinica']);
                        $int_json = json_encode($int, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
                        ?>
                        <div class="int-card bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden flex flex-col justify-between hover:shadow-md transition"
                            data-status="<?= $int['status'] ?>" data-search="<?= htmlspecialchars($searchable_text) ?>">
                            
                            <!-- Card Header -->
                            <div class="p-4 border-b border-gray-100 bg-gray-50/50 flex justify-between items-start">
                                <span class="px-2.5 py-1 rounded-full text-xs font-bold border <?= $status_class ?>">
                                    <?= $status_label ?>
                                </span>
                                <div class="text-right">
                                    <span class="block text-[11px] font-semibold text-gray-400">Entrada</span>
                                    <span class="text-xs text-gray-700 font-bold"><?= date('d/m/Y H:i', strtotime($int['data_internacao'])) ?></span>
                                </div>
                            </div>

                            <!-- Card Content -->
                            <div class="p-4 flex-1 space-y-3">
                                <!-- Pet Main Info -->
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 bg-rose-100 text-rose-600 rounded-full flex items-center justify-center font-bold shrink-0">
                                        <span class="material-icons text-2xl">pets</span>
                                    </div>
                                    <div>
                                        <h3 class="text-xl font-bold text-gray-900 leading-tight">
                                            <?= htmlspecialchars($int['pet_nome']) ?>
                                        </h3>
                                        <div class="text-xs text-gray-500 mt-0.5">
                                            <span class="uppercase font-semibold text-gray-700"><?= htmlspecialchars($int['especie']) ?></span> • 
                                            <?= htmlspecialchars($int['raca'] ?: 'S/R') ?> • 
                                            <?= calcularIdadeCard($int['data_nascimento']) ?> • 
                                            <span class="font-semibold text-gray-700"><?= $int['pet_peso'] ? number_format($int['pet_peso'], 2) . 'kg' : '-' ?></span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Tutor & Vet info -->
                                <div class="bg-gray-50 p-2.5 rounded-lg space-y-1 text-xs text-gray-600">
                                    <div class="flex justify-between items-center">
                                        <span><strong class="text-gray-700">Tutor:</strong> <?= htmlspecialchars($int['tutor_nome']) ?></span>
                                        <?php if ($int['tel_tutor']): ?>
                                            <a href="https://wa.me/55<?= preg_replace('/\D/', '', $int['tel_tutor']) ?>" target="_blank"
                                                class="text-green-600 hover:text-green-700 font-bold flex items-center gap-0.5">
                                                <span class="material-icons text-xs">whatsapp</span>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <strong class="text-gray-700">Vet Resp:</strong> Dr(a). <?= htmlspecialchars($int['vet_nome'] ?: 'Não atribuído') ?>
                                    </div>
                                </div>

                                <!-- Suspeita Clínica -->
                                <div class="bg-rose-50/70 border border-rose-100 p-2.5 rounded-lg text-xs">
                                    <span class="font-bold text-rose-900 uppercase block mb-0.5 text-[10px]">Suspeita / Diagnóstico:</span>
                                    <p class="text-rose-950 font-medium leading-relaxed">
                                        <?= htmlspecialchars($int['suspeita_clinica'] ?: 'Nenhuma suspeita registrada.') ?>
                                    </p>
                                </div>
                            </div>

                            <!-- Touch-Optimized Primary Action Bar -->
                            <div class="p-4 bg-gray-50 border-t border-gray-100 space-y-2">
                                <!-- Botão Principal: Ficha Eletrônica -->
                                <button onclick="openFichaDigital(<?= $int['id_internacao'] ?>)"
                                    class="w-full bg-rose-600 hover:bg-rose-700 active:bg-rose-800 text-white py-2.5 rounded-xl font-bold text-xs flex items-center justify-center gap-2 shadow-sm transition">
                                    <span class="material-icons text-base">edit_note</span> Ficha Eletrônica (Medicação & Soro)
                                </button>

                                <!-- Botão Secundário: PDF / Impressão -->
                                <div class="grid grid-cols-2 gap-2">
                                    <a href="internacao_print.php?id=<?= $int['id_internacao'] ?>" target="_blank"
                                        class="bg-white hover:bg-gray-100 text-gray-700 py-2 rounded-lg font-semibold text-xs flex items-center justify-center gap-1 border border-gray-300 transition shadow-xs">
                                        <span class="material-icons text-sm text-gray-500">print</span> Imprimir PDF
                                    </a>
                                    <a href="pet_detalhes.php?id=<?= $int['id_pet'] ?>"
                                        class="bg-white hover:bg-gray-100 text-gray-700 py-2 rounded-lg font-semibold text-xs flex items-center justify-center gap-1 border border-gray-300 transition shadow-xs">
                                        <span class="material-icons text-sm text-gray-500">folder_shared</span> Prontuário
                                    </a>
                                </div>

                                <!-- Action bar footer: Edit / Delete -->
                                <div class="flex justify-between items-center pt-1 border-t border-gray-200/60 text-[11px]">
                                    <span class="text-gray-400 font-medium"><?= $int['qtd_dias'] ?> dia(s) registrado(s)</span>
                                    <div class="flex gap-2">
                                        <button onclick='editInternacao(<?= $int_json ?>)' class="text-cyan-600 hover:text-cyan-800 font-semibold">Editar</button>
                                        <button onclick="deleteInternacao(<?= $int['id_internacao'] ?>)" class="text-red-600 hover:text-red-800 font-semibold">Excluir</button>
                                    </div>
                                </div>
                            </div>

                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        </main>
    </div>

    <!-- Modal Nova Internação (Com Busca de Pet) -->
    <div id="modalNovaInternacao" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 hidden p-4">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-lg p-6">
            <h3 class="text-xl font-bold text-gray-800 mb-4">Nova Internação Veterinária</h3>
            <form id="formNovaInternacao">
                <input type="hidden" name="action" value="save_internacao">

                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Selecionar Paciente (Pet) *</label>
                    <select name="id_pet" id="ni_id_pet" required class="w-full p-2.5 border rounded-lg text-sm bg-white">
                        <option value="">Selecione um pet...</option>
                        <?php foreach ($pets_list as $p): ?>
                            <option value="<?= $p['id_pet'] ?>">
                                <?= htmlspecialchars($p['pet_nome']) ?> (<?= htmlspecialchars($p['especie']) ?>) - Tutor: <?= htmlspecialchars($p['tutor_nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Médico Veterinário Responsável</label>
                    <select name="id_vet" id="ni_id_vet" class="w-full p-2.5 border rounded-lg text-sm bg-white">
                        <option value="">Selecione um veterinário...</option>
                        <?php foreach ($vets_list as $v): ?>
                            <option value="<?= $v['id_vet'] ?>">
                                <?= htmlspecialchars($v['nome']) ?> <?= $v['crmv'] ? '(CRMV: ' . htmlspecialchars($v['crmv']) . ')' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Data/Hora Entrada *</label>
                        <input type="datetime-local" name="data_internacao" id="ni_data_internacao" required class="w-full p-2 border rounded-lg text-sm" value="<?= date('Y-m-d\TH:i') ?>">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Status Inicial</label>
                        <select name="status" id="ni_status" class="w-full p-2 border rounded-lg text-sm bg-white">
                            <option value="internado">Em Internação</option>
                            <option value="alta">Alta Médica</option>
                        </select>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Suspeita Clínica / Motivo</label>
                    <textarea name="suspeita_clinica" id="ni_suspeita_clinica" rows="2" class="w-full p-2 border rounded-lg text-sm" placeholder="Ex: Gastroenterite, Cirurgia, Trauma..."></textarea>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Observações Iniciais</label>
                    <textarea name="observacoes" id="ni_observacoes" rows="2" class="w-full p-2 border rounded-lg text-sm"></textarea>
                </div>

                <div class="flex justify-end gap-2">
                    <button type="button" onclick="closeNovaInternacaoModal()" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-xs font-semibold">Cancelar</button>
                    <button type="submit" class="px-4 py-2 bg-rose-600 text-white rounded-lg hover:bg-rose-700 text-xs font-bold">Salvar e Abrir Ficha</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Editar Internação -->
    <div id="modalInternacao" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 hidden p-4">
        <div class="bg-white rounded-xl shadow-lg w-full max-w-lg p-6">
            <h3 class="text-xl font-bold text-gray-800 mb-4" id="modalInternacaoTitle">Editar Internação</h3>
            <form id="formInternacao">
                <input type="hidden" name="action" value="save_internacao">
                <input type="hidden" name="id_internacao" id="int_id_internacao" value="">
                <input type="hidden" name="id_pet" id="int_id_pet" value="">

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Médico Veterinário Responsável</label>
                    <select name="id_vet" id="int_id_vet" class="w-full p-2 border rounded-lg">
                        <option value="">Selecione um veterinário...</option>
                        <?php foreach ($vets_list as $v): ?>
                            <option value="<?= $v['id_vet'] ?>">
                                <?= htmlspecialchars($v['nome']) ?> <?= $v['crmv'] ? '(CRMV: ' . htmlspecialchars($v['crmv']) . ')' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Data/Hora Internação *</label>
                        <input type="datetime-local" name="data_internacao" id="int_data_internacao" required class="w-full p-2 border rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select name="status" id="int_status" class="w-full p-2 border rounded-lg">
                            <option value="internado">Em Internação</option>
                            <option value="alta">Alta Médica</option>
                            <option value="obito">Óbito</option>
                            <option value="cancelado">Cancelado</option>
                        </select>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Data/Hora da Alta (Opcional)</label>
                    <input type="datetime-local" name="data_alta" id="int_data_alta" class="w-full p-2 border rounded-lg">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Suspeita Clínica / Diagnóstico</label>
                    <textarea name="suspeita_clinica" id="int_suspeita_clinica" rows="2" class="w-full p-2 border rounded-lg"></textarea>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Observações Gerais</label>
                    <textarea name="observacoes" id="int_observacoes" rows="2" class="w-full p-2 border rounded-lg"></textarea>
                </div>

                <div class="flex justify-end gap-2">
                    <button type="button" onclick="closeInternacaoModal()" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">Cancelar</button>
                    <button type="submit" class="px-4 py-2 bg-rose-600 text-white rounded-lg hover:bg-rose-700 font-medium">Salvar Alterações</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Ficha Digital de Internação -->
    <div id="modalFichaDigital" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-60 hidden p-4 overflow-y-auto">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-5xl my-8 overflow-hidden flex flex-col max-h-[90vh]">
            <!-- Header -->
            <div class="p-4 bg-slate-800 text-white flex justify-between items-center">
                <div class="flex items-center space-x-2">
                    <span class="material-icons text-rose-400">edit_note</span>
                    <h3 class="text-lg font-bold">Ficha Digital de Internação</h3>
                    <span id="fd_vet_info" class="text-xs text-slate-300 ml-2"></span>
                </div>
                <button onclick="closeFichaDigital()" class="text-slate-400 hover:text-white transition">
                    <span class="material-icons">close</span>
                </button>
            </div>

            <!-- Body -->
            <div class="p-6 overflow-y-auto flex-1 bg-gray-50 space-y-6">
                <!-- Days Navigation Tabs -->
                <div class="flex items-center justify-between border-b border-gray-200 pb-3">
                    <div class="flex items-center gap-2 overflow-x-auto" id="fd_dias_tabs">
                        <!-- Loaded via JS -->
                    </div>
                    <button onclick="adicionarNovoDiaFicha()" class="bg-rose-600 hover:bg-rose-700 text-white px-3 py-1.5 rounded-lg text-xs font-medium flex items-center gap-1 shrink-0 shadow-sm">
                        <span class="material-icons text-xs">add</span> Adicionar Dia
                    </button>
                </div>

                <!-- Active Day Container -->
                <div id="fd_dia_container" class="space-y-6 hidden">
                    <!-- Soro & Fluidoterapia Card -->
                    <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
                        <div class="flex justify-between items-center mb-3">
                            <h4 class="font-bold text-gray-700 flex items-center text-sm">
                                <span class="material-icons text-cyan-600 text-base mr-1.5">water_drop</span> Soro & Fluidoterapia do Dia
                            </h4>
                            <span class="text-xs text-gray-400" id="fd_dia_data_label"></span>
                        </div>
                        <form id="formDiaFicha" class="grid grid-cols-1 md:grid-cols-4 gap-3">
                            <input type="hidden" name="action" value="save_internacao_dia">
                            <input type="hidden" name="id_dia" id="fd_id_dia">
                            <input type="hidden" name="id_internacao" id="fd_id_internacao">
                            <input type="hidden" name="data_dia" id="fd_data_dia">

                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Soro</label>
                                <input type="text" name="soro" id="fd_soro" class="w-full p-2 border text-sm rounded-lg" placeholder="Ex: Ringer Lactato">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Volume</label>
                                <input type="text" name="volume" id="fd_volume" class="w-full p-2 border text-sm rounded-lg" placeholder="Ex: 500 ml">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Frequência</label>
                                <input type="text" name="frequencia" id="fd_frequencia" class="w-full p-2 border text-sm rounded-lg" placeholder="Ex: 20 ml/h">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Observações</label>
                                <input type="text" name="observacoes" id="fd_observacoes_dia" class="w-full p-2 border text-sm rounded-lg" placeholder="Obs fluidoterapia">
                            </div>
                            <div class="md:col-span-4 flex justify-end">
                                <button type="submit" class="bg-cyan-600 hover:bg-cyan-700 text-white px-3 py-1.5 rounded-lg text-xs font-semibold transition">
                                    Salvar Fluidoterapia
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Medicações Card -->
                    <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
                        <div class="flex justify-between items-center mb-4">
                            <h4 class="font-bold text-gray-700 flex items-center text-sm">
                                <span class="material-icons text-purple-600 text-base mr-1.5">medication</span> Lista de Medicações Lançadas
                            </h4>
                            <button onclick="openMedicacaoFichaModal()" class="bg-purple-600 hover:bg-purple-700 text-white px-3 py-1.5 rounded-lg text-xs font-semibold flex items-center gap-1 shadow-sm">
                                <span class="material-icons text-xs">add</span> Lançar Medicação
                            </button>
                        </div>

                        <!-- Table of Medications -->
                        <div class="overflow-x-auto">
                            <table class="w-full text-xs text-left border-collapse" id="fd_table_meds">
                                <thead>
                                    <tr class="bg-gray-100 text-gray-600 font-bold border-b border-gray-200">
                                        <th class="p-2.5 w-1/3">MEDICAÇÃO</th>
                                        <th class="p-2.5">DOSE</th>
                                        <th class="p-2.5">VIA</th>
                                        <th class="p-2.5 text-center">HORÁRIOS & CHEQUES (6 SLOTS)</th>
                                        <th class="p-2.5 text-right">AÇÕES</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 text-gray-700">
                                    <!-- Rendered via JS -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Empty State if no days exist -->
                <div id="fd_no_days" class="p-12 text-center text-gray-400 hidden">
                    <span class="material-icons text-5xl mb-2 opacity-30">calendar_today</span>
                    <p class="font-medium text-gray-600">Nenhum dia cadastrado nesta internação.</p>
                    <button onclick="adicionarNovoDiaFicha()" class="mt-3 bg-rose-600 text-white px-4 py-2 rounded-lg text-xs font-semibold">
                        Adicionar Dia 1
                    </button>
                </div>
            </div>

            <!-- Footer -->
            <div class="p-4 bg-gray-100 border-t border-gray-200 flex justify-between items-center">
                <span class="text-xs text-gray-500">As medicações e checagens salvas aqui serão pré-preenchidas na Ficha de Impressão.</span>
                <div class="flex gap-2">
                    <a id="fd_btn_imprimir" href="#" target="_blank" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg text-xs font-semibold transition flex items-center gap-1">
                        <span class="material-icons text-xs">print</span> Visualizar Impressão
                    </a>
                    <button type="button" onclick="closeFichaDigital()" class="bg-slate-700 hover:bg-slate-800 text-white px-4 py-2 rounded-lg text-xs font-semibold">
                        Concluir
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Sub-Modal Formulário de Medicação da Ficha -->
    <div id="modalMedicacaoFicha" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 hidden p-4">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-lg p-6">
            <h4 class="text-lg font-bold text-gray-800 mb-4" id="modalMedicacaoFichaTitle">Adicionar Medicação à Ficha</h4>
            <form id="formMedicacaoFicha">
                <input type="hidden" name="action" value="save_internacao_medicacao">
                <input type="hidden" name="id_medicacao" id="mf_id_medicacao" value="">
                <input type="hidden" name="id_dia" id="mf_id_dia" value="">

                <div class="mb-3">
                    <label class="block text-xs font-semibold text-gray-700 mb-1">Nome da Medicação * (Digitação Livre)</label>
                    <input type="text" name="medicacao" id="mf_medicacao" required class="w-full p-2 border rounded-lg text-sm" placeholder="Ex: Dipirona, Zofran, Enrofloxacino...">
                </div>

                <div class="grid grid-cols-2 gap-3 mb-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Dose</label>
                        <input type="text" name="dose" id="mf_dose" class="w-full p-2 border rounded-lg text-sm" placeholder="Ex: 0.5ml, 1 comp">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Via</label>
                        <input type="text" name="via" id="mf_via" class="w-full p-2 border rounded-lg text-sm" placeholder="Ex: IV, SC, IM, VO">
                    </div>
                </div>

                <!-- 6 Horários Slots -->
                <div class="mb-5 bg-gray-50 p-3 rounded-lg border border-gray-200">
                    <label class="block text-xs font-semibold text-gray-700 mb-2">Horários programados e Checagem de Aplicação (até 6 slots)</label>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3" id="mf_horarios_container">
                        <?php for ($i = 0; $i < 6; $i++): ?>
                            <div class="bg-white p-2 border rounded-lg flex items-center justify-between">
                                <input type="text" id="mf_hora_<?= $i ?>" class="w-20 p-1 text-xs border rounded text-center" placeholder="Ex: 08:00">
                                <label class="flex items-center gap-1 cursor-pointer text-xs">
                                    <input type="checkbox" id="mf_check_<?= $i ?>" class="rounded text-rose-600 focus:ring-rose-500 h-4 w-4">
                                    <span class="text-[10px] font-semibold text-gray-500">Aplicado</span>
                                </label>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>

                <div class="flex justify-end gap-2">
                    <button type="button" onclick="closeMedicacaoFichaModal()" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-xs">Cancelar</button>
                    <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 text-xs font-semibold">Salvar Medicação</button>
                </div>
            </form>
        </div>
    </div>

    <?php include dirname(__DIR__, 2) . '/components/layout_scripts.php'; ?>
    <script>
        let currentFilterStatus = 'internado';

        function filtrarStatus(status) {
            currentFilterStatus = status;
            $('.filter-btn').removeClass('bg-rose-600 text-white border-rose-600').addClass('bg-white text-gray-600 border-gray-200 hover:bg-gray-50');
            $(`#btnFilter_${status}`).removeClass('bg-white text-gray-600 border-gray-200 hover:bg-gray-50').addClass('bg-rose-600 text-white border-rose-600');
            filtrarCards();
        }

        function filtrarCards() {
            const query = $('#searchInput').val().toLowerCase().trim();
            $('.int-card').each(function() {
                const cardStatus = $(this).data('status');
                const cardSearch = $(this).data('search') || '';

                const matchesStatus = (currentFilterStatus === 'todos') || (cardStatus === currentFilterStatus);
                const matchesSearch = !query || cardSearch.includes(query);

                if (matchesStatus && matchesSearch) {
                    $(this).removeClass('hidden');
                } else {
                    $(this).addClass('hidden');
                }
            });
        }

        // Apply initial filter
        $(document).ready(function() {
            filtrarCards();
        });

        // --- Nova Internação Modal ---
        function openNovaInternacaoModal() {
            $('#ni_id_pet').val('');
            $('#ni_id_vet').val('');
            const now = new Date();
            const nowISO = new Date(now.getTime() - (now.getTimezoneOffset() * 60000)).toISOString().slice(0,16);
            $('#ni_data_internacao').val(nowISO);
            $('#ni_status').val('internado');
            $('#ni_suspeita_clinica').val('');
            $('#ni_observacoes').val('');
            $('#modalNovaInternacao').removeClass('hidden');
        }

        function closeNovaInternacaoModal() {
            $('#modalNovaInternacao').addClass('hidden');
        }

        $('#formNovaInternacao').on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                url: '../../app.php',
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        location.reload();
                    } else {
                        alert('Erro: ' + res.message);
                    }
                },
                error: function() { alert('Erro ao salvar internação.'); }
            });
        });

        // --- Editar Internação Modal ---
        function closeInternacaoModal() {
            $('#modalInternacao').addClass('hidden');
        }

        function editInternacao(data) {
            $('#int_id_internacao').val(data.id_internacao);
            $('#int_id_pet').val(data.id_pet);
            $('#int_id_vet').val(data.id_vet || '');
            if (data.data_internacao) {
                const dt = new Date(data.data_internacao);
                const dtISO = new Date(dt.getTime() - (dt.getTimezoneOffset() * 60000)).toISOString().slice(0,16);
                $('#int_data_internacao').val(dtISO);
            }
            if (data.data_alta) {
                const da = new Date(data.data_alta);
                const daISO = new Date(da.getTime() - (da.getTimezoneOffset() * 60000)).toISOString().slice(0,16);
                $('#int_data_alta').val(daISO);
            } else {
                $('#int_data_alta').val('');
            }
            $('#int_status').val(data.status);
            $('#int_suspeita_clinica').val(data.suspeita_clinica || '');
            $('#int_observacoes').val(data.observacoes || '');
            $('#modalInternacao').removeClass('hidden');
        }

        function deleteInternacao(id_internacao) {
            if (confirm('Tem certeza que deseja excluir esta internação e seu histórico?')) {
                $.ajax({
                    url: '../../app.php',
                    type: 'POST',
                    data: { action: 'delete_internacao', id_internacao: id_internacao },
                    dataType: 'json',
                    success: function(res) {
                        if (res.success) location.reload();
                        else alert('Erro: ' + res.message);
                    },
                    error: function() { alert('Erro de conexão.'); }
                });
            }
        }

        $('#formInternacao').on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                url: '../../app.php',
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(res) {
                    if (res.success) location.reload();
                    else alert('Erro: ' + res.message);
                },
                error: function() { alert('Erro ao atualizar internação.'); }
            });
        });

        // --- Ficha Digital Operations ---
        let currentFichaData = null;
        let currentSelectedDiaIndex = 0;

        function openFichaDigital(id_internacao) {
            $('#fd_btn_imprimir').attr('href', 'internacao_print.php?id=' + id_internacao);
            $('#modalFichaDigital').removeClass('hidden');
            carregarFichaDigital(id_internacao);
        }

        function closeFichaDigital() {
            $('#modalFichaDigital').addClass('hidden');
        }

        function carregarFichaDigital(id_internacao, selectDiaId = null) {
            $.ajax({
                url: '../../app.php',
                type: 'POST',
                data: { action: 'get_internacao_details', id_internacao: id_internacao },
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        currentFichaData = res;
                        renderFichaDigitalTabs(selectDiaId);
                    } else {
                        alert('Erro: ' + res.message);
                        closeFichaDigital();
                    }
                },
                error: function() {
                    alert('Erro de conexão ao carregar Ficha Digital.');
                    closeFichaDigital();
                }
            });
        }

        function renderFichaDigitalTabs(selectDiaId = null) {
            if (!currentFichaData) return;
            const dias = currentFichaData.dias || [];
            const vetNome = currentFichaData.internacao.vet_nome ? 'Dr(a). ' + currentFichaData.internacao.vet_nome : '';
            $('#fd_vet_info').text(vetNome);
            
            const tabsContainer = $('#fd_dias_tabs');
            tabsContainer.empty();

            if (dias.length === 0) {
                $('#fd_dia_container').addClass('hidden');
                $('#fd_no_days').removeClass('hidden');
                return;
            }

            $('#fd_no_days').addClass('hidden');
            $('#fd_dia_container').removeClass('hidden');

            let targetIdx = 0;
            if (selectDiaId) {
                const foundIdx = dias.findIndex(d => d.id_dia == selectDiaId);
                if (foundIdx !== -1) targetIdx = foundIdx;
            } else if (currentSelectedDiaIndex < dias.length) {
                targetIdx = currentSelectedDiaIndex;
            } else {
                targetIdx = dias.length - 1;
            }

            dias.forEach((d, idx) => {
                const dtParts = d.data_dia.split('-');
                const dtFmt = dtParts.length === 3 ? `${dtParts[2]}/${dtParts[1]}` : d.data_dia;
                const active = idx === targetIdx;
                const activeClass = active 
                    ? 'bg-rose-600 text-white font-bold shadow-sm' 
                    : 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-200 font-medium';
                
                tabsContainer.append(`
                    <div class="flex items-center gap-1 rounded-lg p-1 border border-transparent">
                        <button type="button" onclick="selectDiaFichaIndex(${idx})" class="px-3 py-1.5 rounded-lg text-xs transition ${activeClass}">
                            Dia ${idx + 1} (${dtFmt})
                        </button>
                        ${dias.length > 1 ? `<button type="button" onclick="deleteDiaFicha(${d.id_dia})" class="text-gray-400 hover:text-red-600 p-1" title="Excluir este dia"><span class="material-icons text-xs">close</span></button>` : ''}
                    </div>
                `);
            });

            selectDiaFichaIndex(targetIdx);
        }

        function selectDiaFichaIndex(idx) {
            currentSelectedDiaIndex = idx;
            const dias = currentFichaData.dias || [];
            if (!dias[idx]) return;
            const dia = dias[idx];

            const dtParts = dia.data_dia.split('-');
            const dtFmt = dtParts.length === 3 ? `${dtParts[2]}/${dtParts[1]}/${dtParts[0]}` : dia.data_dia;
            $('#fd_dia_data_label').text('Data: ' + dtFmt);

            $('#fd_id_dia').val(dia.id_dia);
            $('#fd_id_internacao').val(currentFichaData.internacao.id_internacao);
            $('#fd_data_dia').val(dia.data_dia);
            $('#fd_soro').val(dia.soro || '');
            $('#fd_volume').val(dia.volume || '');
            $('#fd_frequencia').val(dia.frequencia || '');
            $('#fd_observacoes_dia').val(dia.observacoes || '');

            const tbody = $('#fd_table_meds tbody');
            tbody.empty();
            const meds = dia.medicacoes || [];

            if (meds.length === 0) {
                tbody.append(`
                    <tr>
                        <td colspan="5" class="p-6 text-center text-gray-400 italic">
                            Nenhuma medicação registrada para este dia. Clique em "+ Lançar Medicação".
                        </td>
                    </tr>
                `);
            } else {
                meds.forEach(m => {
                    let horariosArr = [];
                    if (m.horarios) {
                        try { horariosArr = JSON.parse(m.horarios); } catch(e){}
                    }

                    let slotsHtml = '<div class="flex items-center justify-center gap-1.5 flex-wrap">';
                    for (let h = 0; h < 6; h++) {
                        const slot = horariosArr[h] || {};
                        const hora = slot.hora || '';
                        const checked = !!slot.checked;
                        slotsHtml += `
                            <button type="button" onclick='toggleCheckSlot(${m.id_medicacao}, ${h})' 
                                class="px-2 py-1 rounded text-[11px] flex items-center gap-1 border transition ${checked ? 'bg-green-100 text-green-800 border-green-300 font-bold' : 'bg-gray-50 text-gray-600 border-gray-200'}">
                                <span class="material-icons text-xs">${checked ? 'check_box' : 'check_box_outline_blank'}</span>
                                ${hora || '--:--'}
                            </button>
                        `;
                    }
                    slotsHtml += '</div>';

                    const mJson = JSON.stringify(m).replace(/'/g, "&apos;");

                    tbody.append(`
                        <tr class="hover:bg-gray-50/80 transition">
                            <td class="p-2.5 font-bold text-gray-800">${escapeHtml(m.medicacao)}</td>
                            <td class="p-2.5 font-medium">${escapeHtml(m.dose || '-')}</td>
                            <td class="p-2.5 font-medium">${escapeHtml(m.via || '-')}</td>
                            <td class="p-2.5 text-center">${slotsHtml}</td>
                            <td class="p-2.5 text-right space-x-1">
                                <button type="button" onclick='editMedicacaoFicha(${mJson})' class="p-1 text-gray-500 hover:text-purple-600 rounded transition" title="Editar"><span class="material-icons text-base">edit</span></button>
                                <button type="button" onclick="deleteMedicacaoFicha(${m.id_medicacao})" class="p-1 text-gray-500 hover:text-red-600 rounded transition" title="Excluir"><span class="material-icons text-base">delete</span></button>
                            </td>
                        </tr>
                    `);
                });
            }
        }

        function escapeHtml(str) {
            if (!str) return '';
            return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }

        function adicionarNovoDiaFicha() {
            if (!currentFichaData) return;
            const intId = currentFichaData.internacao.id_internacao;
            const dias = currentFichaData.dias || [];
            
            let nextDate = new Date().toISOString().slice(0,10);
            if (dias.length > 0) {
                const lastDate = new Date(dias[dias.length - 1].data_dia + 'T00:00:00');
                lastDate.setDate(lastDate.getDate() + 1);
                nextDate = lastDate.toISOString().slice(0,10);
            }

            $.ajax({
                url: '../../app.php',
                type: 'POST',
                data: { action: 'save_internacao_dia', id_internacao: intId, data_dia: nextDate },
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        carregarFichaDigital(intId, res.id_dia);
                    } else {
                        alert('Erro: ' + res.message);
                    }
                },
                error: function() { alert('Erro de conexão ao adicionar dia.'); }
            });
        }

        function deleteDiaFicha(id_dia) {
            if (confirm('Tem certeza que deseja excluir este dia de internação?')) {
                $.ajax({
                    url: '../../app.php',
                    type: 'POST',
                    data: { action: 'delete_internacao_dia', id_dia: id_dia },
                    dataType: 'json',
                    success: function(res) {
                        if (res.success) {
                            carregarFichaDigital(currentFichaData.internacao.id_internacao);
                        } else {
                            alert('Erro: ' + res.message);
                        }
                    },
                    error: function() { alert('Erro ao excluir dia.'); }
                });
            }
        }

        $('#formDiaFicha').on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                url: '../../app.php',
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        carregarFichaDigital(currentFichaData.internacao.id_internacao, res.id_dia);
                    } else {
                        alert('Erro: ' + res.message);
                    }
                },
                error: function() { alert('Erro ao salvar fluidoterapia.'); }
            });
        });

        // --- Medicação Sub-Modal & Actions ---
        function openMedicacaoFichaModal() {
            if (!currentFichaData) return;
            const dias = currentFichaData.dias || [];
            const dia = dias[currentSelectedDiaIndex];
            if (!dia) return;

            $('#modalMedicacaoFichaTitle').text('Adicionar Medicação à Ficha');
            $('#formMedicacaoFicha input[name="action"]').val('save_internacao_medicacao');
            $('#mf_id_medicacao').val('');
            $('#mf_id_dia').val(dia.id_dia);
            $('#mf_medicacao').val('');
            $('#mf_dose').val('');
            $('#mf_via').val('');

            const defaultTimes = ['08:00', '12:00', '16:00', '20:00', '00:00', '04:00'];
            for (let i = 0; i < 6; i++) {
                $(`#mf_hora_${i}`).val(defaultTimes[i] || '');
                $(`#mf_check_${i}`).prop('checked', false);
            }

            $('#modalMedicacaoFicha').removeClass('hidden');
        }

        function closeMedicacaoFichaModal() {
            $('#modalMedicacaoFicha').addClass('hidden');
        }

        function editMedicacaoFicha(med) {
            $('#modalMedicacaoFichaTitle').text('Editar Medicação da Ficha');
            $('#formMedicacaoFicha input[name="action"]').val('save_internacao_medicacao');
            $('#mf_id_medicacao').val(med.id_medicacao);
            $('#mf_id_dia').val(med.id_dia);
            $('#mf_medicacao').val(med.medicacao);
            $('#mf_dose').val(med.dose || '');
            $('#mf_via').val(med.via || '');

            let horariosArr = [];
            if (med.horarios) {
                try { horariosArr = JSON.parse(med.horarios); } catch(e){}
            }

            for (let i = 0; i < 6; i++) {
                const slot = horariosArr[i] || {};
                $(`#mf_hora_${i}`).val(slot.hora || '');
                $(`#mf_check_${i}`).prop('checked', !!slot.checked);
            }

            $('#modalMedicacaoFicha').removeClass('hidden');
        }

        function toggleCheckSlot(id_medicacao, slotIndex) {
            const dias = currentFichaData.dias || [];
            const dia = dias[currentSelectedDiaIndex];
            if (!dia) return;
            const med = (dia.medicacoes || []).find(m => m.id_medicacao == id_medicacao);
            if (!med) return;

            let horariosArr = [];
            if (med.horarios) {
                try { horariosArr = JSON.parse(med.horarios); } catch(e){}
            }

            for (let i = 0; i < 6; i++) {
                if (!horariosArr[i]) horariosArr[i] = { hora: '', checked: 0 };
            }

            horariosArr[slotIndex].checked = horariosArr[slotIndex].checked ? 0 : 1;

            $.ajax({
                url: '../../app.php',
                type: 'POST',
                data: {
                    action: 'save_internacao_medicacao',
                    id_medicacao: med.id_medicacao,
                    id_dia: med.id_dia,
                    medicacao: med.medicacao,
                    dose: med.dose,
                    via: med.via,
                    horarios: JSON.stringify(horariosArr)
                },
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        carregarFichaDigital(currentFichaData.internacao.id_internacao, dia.id_dia);
                    } else {
                        alert('Erro: ' + res.message);
                    }
                },
                error: function() { alert('Erro ao alternar horário.'); }
            });
        }

        function deleteMedicacaoFicha(id_medicacao) {
            if (confirm('Remover esta medicação da ficha?')) {
                const dias = currentFichaData.dias || [];
                const dia = dias[currentSelectedDiaIndex];
                $.ajax({
                    url: '../../app.php',
                    type: 'POST',
                    data: { action: 'delete_internacao_medicacao', id_medicacao: id_medicacao },
                    dataType: 'json',
                    success: function(res) {
                        if (res.success) {
                            carregarFichaDigital(currentFichaData.internacao.id_internacao, dia ? dia.id_dia : null);
                        } else {
                            alert('Erro: ' + res.message);
                        }
                    },
                    error: function() { alert('Erro ao remover medicação.'); }
                });
            }
        }

        $('#formMedicacaoFicha').on('submit', function(e) {
            e.preventDefault();
            const slots = [];
            for (let i = 0; i < 6; i++) {
                slots.push({
                    hora: $(`#mf_hora_${i}`).val().trim(),
                    checked: $(`#mf_check_${i}`).is(':checked') ? 1 : 0
                });
            }

            const formData = {
                action: 'save_internacao_medicacao',
                id_medicacao: $('#mf_id_medicacao').val(),
                id_dia: $('#mf_id_dia').val(),
                medicacao: $('#mf_medicacao').val(),
                dose: $('#mf_dose').val(),
                via: $('#mf_via').val(),
                horarios: JSON.stringify(slots)
            };

            $.ajax({
                url: '../../app.php',
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        closeMedicacaoFichaModal();
                        carregarFichaDigital(currentFichaData.internacao.id_internacao, $('#mf_id_dia').val());
                    } else {
                        alert('Erro: ' + res.message);
                    }
                },
                error: function() { alert('Erro ao salvar medicação.'); }
            });
        });
    </script>
</body>

</html>
