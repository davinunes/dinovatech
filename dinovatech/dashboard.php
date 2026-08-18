<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

require_once '../database.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers/AppHelper.php';
$linkDB = DBConnect();

// --- Lembretes de Aniversário (Próximos 30 dias) ---
$hojeAniversario = date('m-d');
$daqui30Aniversario = date('m-d', strtotime('+30 days'));
if ($daqui30Aniversario < $hojeAniversario) {
    $whereNascimento = "(DATE_FORMAT(data_nascimento, '%m-%d') >= '$hojeAniversario' OR DATE_FORMAT(data_nascimento, '%m-%d') <= '$daqui30Aniversario')";
} else {
    $whereNascimento = "DATE_FORMAT(data_nascimento, '%m-%d') BETWEEN '$hojeAniversario' AND '$daqui30Aniversario'";
}
$queryBirthdays = "
    SELECT nome, data_nascimento, 'Colaborador' AS tipo FROM Veterinarios WHERE data_nascimento IS NOT NULL AND $whereNascimento
    UNION ALL
    SELECT nome, data_nascimento, 'Cliente' AS tipo FROM Clientes WHERE data_nascimento IS NOT NULL AND $whereNascimento
    ORDER BY DATE_FORMAT(data_nascimento, '%m-%d') ASC LIMIT 10
";
$resBirthdays = DBExecute($linkDB, $queryBirthdays);
$aniversariantes = [];
if ($resBirthdays) {
    while ($row = mysqli_fetch_assoc($resBirthdays)) {
        $aniversariantes[] = $row;
    }
}

// --- Lembretes de Vacinas (Próximos 30 dias) ---
$vacinasProximas = [];
if (AppHelper::isVetMode()) {
    $hojeVacina = date('Y-m-d');
    $daqui30Vacina = date('Y-m-d', strtotime('+30 days'));
    $queryVaccines = "
        SELECT cv.data_vencimento, v.nome as vacina_nome, p.nome as pet_nome, c.nome as cliente_nome, c.telefone
        FROM CarteiraVacinas cv
        JOIN Vacinas v ON cv.id_vacina = v.id_vacina
        JOIN Pets p ON cv.id_pet = p.id_pet
        JOIN Clientes c ON p.id_cliente = c.id_cliente
        WHERE cv.data_vencimento BETWEEN '$hojeVacina' AND '$daqui30Vacina'
        ORDER BY cv.data_vencimento ASC
        LIMIT 5
    ";
    $resVaccines = DBExecute($linkDB, $queryVaccines);
    if ($resVaccines) {
        while ($row = mysqli_fetch_assoc($resVaccines)) {
            $vacinasProximas[] = $row;
        }
    }

    // --- Banho & Tosa (Esteira e Pacotes Ativos) ---
    $banhoFilaAtivos = [];
    $resBanho = DBExecute($linkDB, "
        SELECT f.id_fila, f.etapa, f.horario_entrada, p.nome as pet_nome, p.porte, c.nome as tutor_nome, v.nome as colab_nome 
        FROM BanhoProducaoFila f
        JOIN Pets p ON f.id_pet = p.id_pet
        JOIN Clientes c ON p.id_cliente = c.id_cliente
        LEFT JOIN Veterinarios v ON f.id_colaborador = v.id_vet
        WHERE f.etapa != 'finalizado'
        ORDER BY f.horario_entrada ASC LIMIT 6
    ");
    if ($resBanho) {
        while ($bRow = mysqli_fetch_assoc($resBanho)) {
            $banhoFilaAtivos[] = $bRow;
        }
    }

    $resPacotesAtivosCount = DBExecute($linkDB, "SELECT COUNT(*) as total FROM ClientePacotes WHERE status = 'ativo'");
    $totalPacotesAtivos = ($resPacotesAtivosCount && $rPA = mysqli_fetch_assoc($resPacotesAtivosCount)) ? (int)$rPA['total'] : 0;
}

// --- Verifica se a integração com Banco Inter está configurada ---
$interConfigurado = false;
$resConfigInter = DBExecute($linkDB, "SELECT api_inter_client_id, api_inter_client_secret, api_inter_cert_base64, api_inter_cert_path FROM ConfiguracoesEmissor LIMIT 1");
if ($resConfigInter && $rowInter = mysqli_fetch_assoc($resConfigInter)) {
    $hasClientId = !empty($rowInter['api_inter_client_id']);
    $hasSecret = !empty($rowInter['api_inter_client_secret']);
    $hasCert = !empty($rowInter['api_inter_cert_base64']) || (!empty($rowInter['api_inter_cert_path']) && file_exists(__DIR__ . '/../' . $rowInter['api_inter_cert_path']));
    
    if ($hasClientId && $hasSecret && $hasCert) {
        $interConfigurado = true;
    }
}

DBClose($linkDB);
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <title>Dashboard - Dinovatech</title>
    <?php include 'components/layout_head.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body class="bg-gray-50 flex">

    <?php include 'components/sidebar.php'; ?>

    <div class="flex-1 flex flex-col lg:ml-64 min-h-screen transition-all duration-300">
        <!-- Content Area -->
        <main class="flex-1 p-6 mt-16 lg:mt-0">

            <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between">
                <div>
                    <h2 class="text-3xl font-bold text-gray-800">Dashboard Financeiro</h2>
                    <p class="text-gray-500">Visão geral da saúde financeira.</p>
                </div>

                <!-- Filters -->
                <div
                    class="mt-4 md:mt-0 flex flex-wrap gap-2 items-end bg-white p-4 rounded-lg shadow-sm border border-gray-100">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Mês</label>
                        <input type="month" id="filtroMes" value="<?= date('Y-m') ?>"
                            class="border border-gray-300 rounded px-2 py-1 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Cliente</label>
                        <input type="text" id="filtroClienteNome" placeholder="Todos"
                            class="border border-gray-300 rounded px-2 py-1 text-sm w-32">
                        <input type="hidden" id="filtroClienteId">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Serviço</label>
                        <input type="text" id="filtroServicoNome" placeholder="Todos"
                            class="border border-gray-300 rounded px-2 py-1 text-sm w-32">
                        <input type="hidden" id="filtroServicoId">
                    </div>
                    <div>
                        <button id="btnFiltrar"
                            class="bg-blue-600 text-white px-4 py-1.5 rounded text-sm hover:bg-blue-700 transition">Filtrar</button>
                    </div>
                    <?php if ($interConfigurado): ?>
                    <div>
                        <button id="btnExtratoInter" type="button"
                            class="bg-orange-500 hover:bg-orange-600 text-white px-3 py-1.5 rounded text-sm font-medium flex items-center shadow-sm transition gap-1.5"
                            title="Consultar Extrato Banco Inter do mês selecionado">
                            <span class="material-icons text-base">account_balance</span>
                            <span>Extrato Inter</span>
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Cards Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">

                <!-- Card Total Faturado -->
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
                        <span class="material-icons text-3xl">attach_money</span>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm font-medium" id="lblTotalFaturado">Total Recebido (Mês)</p>
                        <h3 class="text-2xl font-bold text-gray-800" id="statTotalFaturado">R$ 0,00</h3>
                    </div>
                </div>

                <!-- Card A Receber -->
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-4">
                        <span class="material-icons text-3xl">pending_actions</span>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm font-medium" id="lblTotalAberto">A Receber (Mês)</p>
                        <h3 class="text-2xl font-bold text-gray-800" id="statTotalAberto">R$ 0,00</h3>
                    </div>
                </div>

                <!-- Card Em Atraso -->
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center">
                    <div class="p-3 rounded-full bg-red-100 text-red-600 mr-4">
                        <span class="material-icons text-3xl">warning</span>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm font-medium">Em Atraso (Geral)</p>
                        <h3 class="text-2xl font-bold text-red-600" id="statTotalAtrasado">R$ 0,00</h3>
                    </div>
                </div>
            </div>

            <!-- Lembretes (Aniversários e Vacinas) -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <!-- Aniversariantes -->
                <div
                    class="bg-white rounded-xl shadow-sm border border-blue-100 overflow-hidden flex flex-col <?= AppHelper::isVetMode() ? '' : 'lg:col-span-2' ?>">
                    <div class="p-4 border-b border-blue-100 bg-blue-50 flex items-center">
                        <span class="material-icons text-blue-500 mr-2">cake</span>
                        <h3 class="font-semibold text-blue-900">Aniversariantes (Próximos 30 dias)</h3>
                    </div>
                    <div class="p-4 flex-1">
                        <?php if (empty($aniversariantes)): ?>
                            <p class="text-gray-500 text-sm italic">Nenhum aniversariante próximo.</p>
                        <?php else: ?>
                            <ul class="space-y-3">
                                <?php foreach ($aniversariantes as $aniv):
                                    $dataParts = explode('-', $aniv['data_nascimento']);
                                    $diaMes = $dataParts[2] . '/' . $dataParts[1];
                                    $tipoItem = $aniv['tipo'];
                                    ?>
                                    <li
                                        class="flex justify-between items-center text-sm border-b pb-2 border-gray-50 last:border-0 last:pb-0">
                                        <span class="font-medium text-gray-800">
                                            <?= htmlspecialchars($aniv['nome']) ?>
                                            <span class="text-xs text-gray-400 font-normal ml-1">(<?= $tipoItem ?>)</span>
                                        </span>
                                        <span
                                            class="bg-blue-100 text-blue-800 px-2 py-0.5 rounded-full text-xs font-bold"><?= $diaMes ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (AppHelper::isVetMode()): ?>
                    <!-- Vacinas Próximas -->
                    <div class="bg-white rounded-xl shadow-sm border border-green-100 overflow-hidden flex flex-col">
                        <div class="p-4 border-b border-green-100 bg-green-50 flex items-center">
                            <span class="material-icons text-green-500 mr-2">vaccines</span>
                            <h3 class="font-semibold text-green-900">Vacinas Próximas Vencendo (30 dias)</h3>
                        </div>
                        <div class="p-4 flex-1">
                            <?php if (empty($vacinasProximas)): ?>
                                <p class="text-gray-500 text-sm italic">Nenhuma vacina prevista para os próximos 30 dias.</p>
                            <?php else: ?>
                                <ul class="space-y-3">
                                    <?php foreach ($vacinasProximas as $vac):
                                        $dataBR = date('d/m/Y', strtotime($vac['data_vencimento']));
                                        ?>
                                        <li class="flex flex-col text-sm border-b pb-2 border-gray-50 last:border-0 last:pb-0">
                                            <div class="flex justify-between items-center mb-1">
                                                <span class="font-medium text-gray-800">Pet:
                                                    <?= htmlspecialchars($vac['pet_nome']) ?>
                                                    (<?= htmlspecialchars($vac['cliente_nome']) ?>)</span>
                                                <span
                                                    class="bg-green-100 text-green-800 px-2 py-0.5 rounded-full text-xs font-bold"><?= $dataBR ?></span>
                                            </div>
                                            <div
                                                class="flex justify-between items-center text-gray-500 text-xs text-left w-full gap-2 mt-1">
                                                <span>Vacina: <strong
                                                        class="text-gray-600"><?= htmlspecialchars($vac['vacina_nome']) ?></strong></span>
                                                <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $vac['telefone']) ?>"
                                                    target="_blank"
                                                    class="text-green-600 hover:underline flex items-center gap-1"><span
                                                        class="material-icons" style="font-size: 14px;">chat</span> Avisar</a>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (AppHelper::isVetMode()): ?>
                <!-- Banho & Tosa: Esteira e Pacotes Section -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                    <!-- Card Esteira de Banho -->
                    <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-teal-100 overflow-hidden flex flex-col">
                        <div class="p-4 border-b border-teal-100 bg-gradient-to-r from-teal-50 to-white flex justify-between items-center">
                            <div class="flex items-center">
                                <span class="material-icons text-teal-600 mr-2">view_kanban</span>
                                <div>
                                    <h3 class="font-semibold text-teal-900">Esteira de Banho & Tosa (Ao Vivo)</h3>
                                    <p class="text-[11px] text-teal-700">Pets atualmente na linha de produção hoje.</p>
                                </div>
                            </div>
                            <a href="modules/Vet/banho_producao.php"
                                class="text-xs font-semibold text-teal-700 hover:text-teal-900 bg-teal-100 hover:bg-teal-200 px-3 py-1.5 rounded-lg flex items-center gap-1 transition">
                                Ver Linha Completa <span class="material-icons text-xs">arrow_forward</span>
                            </a>
                        </div>
                        <div class="p-4 flex-1">
                            <?php if (empty($banhoFilaAtivos)): ?>
                                <p class="text-gray-400 text-sm italic text-center py-4">Nenhum pet na esteira no momento.</p>
                            <?php else: ?>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <?php foreach ($banhoFilaAtivos as $b):
                                        $etapaBadge = 'bg-amber-100 text-amber-800';
                                        $etapaNome = '1. Recepção';
                                        if ($b['etapa'] === 'em_banho') { $etapaBadge = 'bg-cyan-100 text-cyan-800'; $etapaNome = '2. Em Banho'; }
                                        elseif ($b['etapa'] === 'secagem') { $etapaBadge = 'bg-blue-100 text-blue-800'; $etapaNome = '3. Secagem'; }
                                        elseif ($b['etapa'] === 'tosa_finalizacao') { $etapaBadge = 'bg-purple-100 text-purple-800'; $etapaNome = '4. Tosa'; }
                                        elseif ($b['etapa'] === 'pronto') { $etapaBadge = 'bg-emerald-100 text-emerald-800 font-bold'; $etapaNome = '5. Pronto! 🐾'; }
                                    ?>
                                        <div class="p-3 bg-gray-50 rounded-xl border border-gray-100 flex items-center justify-between">
                                            <div>
                                                <div class="flex items-center gap-2">
                                                    <span class="font-bold text-sm text-gray-800"><?= htmlspecialchars($b['pet_nome']) ?></span>
                                                    <span class="text-[10px] bg-white px-1.5 py-0.5 rounded border border-gray-200 text-gray-500 font-semibold">Porte <?= $b['porte'] ?></span>
                                                </div>
                                                <span class="text-xs text-gray-500 block">Tutor: <?= htmlspecialchars($b['tutor_nome']) ?></span>
                                            </div>
                                            <span class="px-2 py-1 rounded text-xs <?= $etapaBadge ?>"><?= $etapaNome ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Card Pacotes Ativos Widget -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex flex-col justify-between">
                        <div>
                            <div class="flex items-center gap-2 mb-3">
                                <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center">
                                    <span class="material-icons text-xl">card_giftcard</span>
                                </div>
                                <div>
                                    <h3 class="font-bold text-gray-800 text-base">Pacotes & Combos</h3>
                                    <p class="text-xs text-gray-500">Planos de estética ativos</p>
                                </div>
                            </div>
                            <div class="bg-amber-50/60 rounded-xl p-4 border border-amber-100 mb-4">
                                <span class="text-xs text-amber-800 font-medium block">Total de Tutores com Pacote:</span>
                                <span class="text-3xl font-extrabold text-amber-900"><?= $totalPacotesAtivos ?></span>
                                <span class="text-[11px] text-amber-700 block mt-1">Créditos disponíveis para consumo na agenda e esteira.</span>
                            </div>
                        </div>
                        <a href="modules/Vet/pacotes.php"
                            class="w-full bg-slate-800 hover:bg-slate-900 text-white text-xs font-semibold py-2.5 rounded-xl text-center transition flex items-center justify-center gap-1">
                            <span class="material-icons text-sm">settings</span> Gerenciar Pacotes & Saldos
                        </a>
                    </div>
                </div>

                <!-- Atendimentos Recentes Section -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden flex flex-col mb-8">
                    <div class="p-6 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2 bg-gradient-to-r from-teal-50/50 to-white">
                        <div class="flex items-center">
                            <span class="material-icons text-teal-600 mr-2">medical_services</span>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-800">Atendimentos Recentes</h3>
                                <p class="text-xs text-gray-500">Histórico de consultas e atendimentos clínicos registrados.</p>
                            </div>
                        </div>
                        <span id="atendimentosTotalBadge" class="text-xs font-semibold bg-teal-100 text-teal-800 px-3 py-1 rounded-full self-start sm:self-auto">
                            0 Atendimentos
                        </span>
                    </div>

                    <!-- Desktop Table -->
                    <div class="hidden md:block overflow-x-auto flex-1">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 text-gray-600 text-xs uppercase tracking-wider">
                                    <th class="p-4 font-medium">ID</th>
                                    <th class="p-4 font-medium">Data / Hora</th>
                                    <th class="p-4 font-medium">Pet</th>
                                    <th class="p-4 font-medium">Tutor</th>
                                    <th class="p-4 font-medium">Veterinário</th>
                                    <th class="p-4 font-medium">Motivo / Queixa</th>
                                    <th class="p-4 font-medium text-right w-16">Ação</th>
                                </tr>
                            </thead>
                            <tbody id="listaAtendimentosRecentes" class="text-gray-700 text-sm">
                                <tr>
                                    <td colspan="7" class="p-4 text-center text-gray-500">Carregando atendimentos...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Mobile Cards -->
                    <div id="listaAtendimentosRecentesCards" class="md:hidden space-y-4 p-4 bg-gray-50">
                        <div class="text-center text-gray-500 py-4">Carregando atendimentos...</div>
                    </div>

                    <!-- Pagination Footer -->
                    <div id="paginacaoAtendimentosRecentes" class="p-4 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-4 bg-gray-50/50">
                        <div class="text-xs text-gray-500 text-center sm:text-left" id="atendimentosPaginacaoInfo">
                            Mostrando 0 de 0
                        </div>
                        <div class="flex items-center space-x-2" id="atendimentosPaginacaoBotoes">
                            <!-- Page buttons inserted by JS -->
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <!-- Chart Section -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Evolução do Faturamento (6 Meses)</h3>
                    <div class="relative h-64 w-full">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>

                <!-- Recent Invoices Table -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden flex flex-col">
                    <div class="p-6 border-b border-gray-100">
                        <h3 class="text-lg font-semibold text-gray-800">Faturas Recentes</h3>
                    </div>

                    <!-- Desktop Table -->
                    <div class="hidden md:block overflow-x-auto flex-1">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 text-gray-600 text-sm uppercase tracking-wider">
                                    <th class="p-4 font-medium">ID</th>
                                    <th class="p-4 font-medium">Cliente</th>
                                    <th class="p-4 font-medium">Valor</th>
                                    <th class="p-4 font-medium">Vencimento</th>
                                    <th class="p-4 font-medium">Status</th>
                                    <th class="p-4 font-medium w-8"></th>
                                </tr>
                            </thead>
                            <tbody id="listaFaturasRecentes" class="text-gray-700 text-sm">
                                <tr>
                                    <td colspan="6" class="p-4 text-center">Carregando...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Mobile Cards -->
                    <div id="listaFaturasRecentesCards" class="md:hidden space-y-4 p-4 bg-gray-50">
                        <div class="text-center text-gray-500 py-4">Carregando...</div>
                    </div>
                </div>
            </div>

            <!-- MODAL EXTRATO BANCO INTER -->
            <div id="modalExtratoInter" class="fixed inset-0 z-50 hidden overflow-y-auto bg-black bg-opacity-50 flex items-center justify-center p-4">
                <div class="bg-white rounded-2xl shadow-2xl max-w-5xl w-full max-h-[90vh] flex flex-col overflow-hidden animate-fade-in border border-gray-100">
                    <!-- Modal Header -->
                    <div class="px-6 py-4 bg-gradient-to-r from-orange-500 to-amber-500 text-white flex items-center justify-between shadow-sm">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-white bg-opacity-20 rounded-lg">
                                <span class="material-icons text-2xl">account_balance</span>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold">Extrato Banco Inter</h3>
                                <p class="text-xs text-orange-100" id="extratoPeriodoInfo">Período: -</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <button id="btnExportarPdfExtrato" type="button" class="bg-white bg-opacity-20 hover:bg-opacity-30 text-white text-xs px-3 py-1.5 rounded-lg font-medium transition flex items-center gap-1 shadow-sm">
                                <span class="material-icons text-sm">picture_as_pdf</span>
                                <span>Exportar PDF</span>
                            </button>
                            <button type="button" onclick="fecharModalExtratoInter()" class="text-white hover:text-orange-200 p-1 rounded-lg transition">
                                <span class="material-icons text-2xl">close</span>
                            </button>
                        </div>
                    </div>

                    <!-- Modal Body -->
                    <div class="p-6 overflow-y-auto flex-1 space-y-6">
                        <!-- Cards de Resumo -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div class="bg-emerald-50 border border-emerald-100 rounded-xl p-4 flex items-center gap-3 shadow-sm">
                                <div class="p-2.5 bg-emerald-100 text-emerald-600 rounded-lg">
                                    <span class="material-icons text-xl">arrow_downward</span>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold text-emerald-600 uppercase tracking-wider">Entradas / Créditos</p>
                                    <h4 class="text-lg font-bold text-emerald-900" id="extratoTotalEntradas">R$ 0,00</h4>
                                </div>
                            </div>
                            <div class="bg-rose-50 border border-rose-100 rounded-xl p-4 flex items-center gap-3 shadow-sm">
                                <div class="p-2.5 bg-rose-100 text-rose-600 rounded-lg">
                                    <span class="material-icons text-xl">arrow_upward</span>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold text-rose-600 uppercase tracking-wider">Saídas / Débitos</p>
                                    <h4 class="text-lg font-bold text-rose-900" id="extratoTotalSaidas">R$ 0,00</h4>
                                </div>
                            </div>
                            <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 flex items-center gap-3 shadow-sm">
                                <div class="p-2.5 bg-slate-200 text-slate-700 rounded-lg">
                                    <span class="material-icons text-xl">receipt_long</span>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Transações</p>
                                    <h4 class="text-lg font-bold text-slate-800" id="extratoTotalTransacoes">0</h4>
                                </div>
                            </div>
                        </div>

                        <!-- Filtro de busca rápida na tabela do extrato -->
                        <div class="flex items-center justify-between gap-3">
                            <div class="relative flex-1 max-w-xs">
                                <span class="material-icons absolute left-3 top-2.5 text-gray-400 text-sm">search</span>
                                <input type="text" id="filtroTextoExtrato" placeholder="Buscar no extrato..."
                                    class="w-full pl-9 pr-3 py-1.5 text-xs border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                            </div>
                            <span class="text-xs text-gray-400 italic">Extrato Completo do Banco Inter (Banking v2)</span>
                        </div>

                        <!-- Estado Loading -->
                        <div id="extratoLoading" class="hidden py-12 flex flex-col items-center justify-center space-y-3">
                            <div class="w-10 h-10 border-4 border-orange-200 border-t-orange-500 rounded-full animate-spin"></div>
                            <p class="text-sm font-medium text-gray-600">Consultando extrato na API do Banco Inter...</p>
                        </div>

                        <!-- Estado Erro -->
                        <div id="extratoErro" class="hidden p-4 bg-red-50 border border-red-200 rounded-xl text-sm text-red-700 flex items-start gap-3">
                            <span class="material-icons text-red-500 mt-0.5">error_outline</span>
                            <div id="extratoErroMsg">Erro ao consultar extrato.</div>
                        </div>

                        <!-- Tabela de Transações -->
                        <div id="extratoTabelaContainer" class="overflow-x-auto border border-gray-200 rounded-xl">
                            <table class="min-w-full divide-y divide-gray-200 text-xs">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Data</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Tipo</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Título / Descrição</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Pagador / Detalhes</th>
                                        <th class="px-4 py-3 text-right font-semibold text-gray-600">Valor</th>
                                    </tr>
                                </thead>
                                <tbody id="extratoListaTransacoes" class="divide-y divide-gray-100 bg-white">
                                    <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">Nenhuma consulta realizada.</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="px-6 py-3 bg-gray-50 border-t border-gray-100 flex items-center justify-between text-xs text-gray-500">
                        <span>API Banco Inter • Banking v2</span>
                        <button type="button" onclick="fecharModalExtratoInter()" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg font-medium transition">
                            Fechar
                        </button>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <?php include 'components/layout_scripts.php'; ?>
    <script>
        $(document).ready(function () {
            // ... (rest of search/autocomplete code remains unchanged)
            let revenueChart = null;

            // Initialize Autocompletes
            $("#filtroClienteNome").autocomplete({
                source: function (request, response) {
                    $.ajax({
                        url: "app.php",
                        type: "POST",
                        dataType: "json",
                        data: {
                            action: "buscar_clientes",
                            termo: request.term
                        },
                        success: function (resp) {
                            if (resp.success && resp.data) {
                                response($.map(resp.data, function (item) {
                                    return {
                                        label: item.nome + (item.cpf_cnpj ? ' (' + item.cpf_cnpj + ')' : ''),
                                        value: item.nome,
                                        id: item.id_cliente
                                    };
                                }));
                            } else {
                                response([]); // No results
                            }
                        }
                    });
                },
                select: function (event, ui) {
                    $("#filtroClienteId").val(ui.item.id);
                },
                change: function (event, ui) {
                    if (!ui.item) {
                        $("#filtroClienteId").val(""); // Clear ID if text cleared
                    }
                }
            });

            $("#filtroServicoNome").autocomplete({
                source: function (request, response) {
                    $.ajax({
                        url: "app.php",
                        type: "POST",
                        dataType: "json",
                        data: {
                            action: "buscar_servicos",
                            termo: request.term
                        },
                        success: function (resp) {
                            if (resp.success && resp.data) {
                                response($.map(resp.data, function (item) {
                                    return {
                                        label: item.nome_servico,
                                        value: item.nome_servico, // Correct property
                                        id: item.id_servico
                                    };
                                }));
                            } else {
                                response([]);
                            }
                        }
                    });
                },
                select: function (event, ui) {
                    $("#filtroServicoId").val(ui.item.id);
                },
                change: function (event, ui) {
                    if (!ui.item) {
                        $("#filtroServicoId").val("");
                    }
                }
            });

            // Load Data
            function loadDashboard() {
                const mes = $('#filtroMes').val();
                const id_cliente = $('#filtroClienteId').val();
                const id_servico = $('#filtroServicoId').val();

                $.ajax({
                    url: 'app.php',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'get_dashboard_stats',
                        mes: mes,
                        id_cliente: id_cliente,
                        id_servico: id_servico
                    },
                    success: function (response) {
                        if (response.success) {
                            const data = response.data;

                            $('#statTotalFaturado').text(formatCurrency(data.total_faturado));
                            $('#statTotalAberto').text(formatCurrency(data.total_aberto));
                            $('#statTotalAtrasado').text(formatCurrency(data.total_atrasado));

                            // Update Titles if available
                            if (data.titulo_faturado) $('#lblTotalFaturado').text(data.titulo_faturado);
                            if (data.titulo_aberto) $('#lblTotalAberto').text(data.titulo_aberto);

                            // Render List & Cards
                            let html = '';
                            let htmlCards = '';

                            if (data.faturas_recentes.length > 0) {
                                data.faturas_recentes.forEach(fatura => {
                                    let statusClass = '';
                                    if (fatura.status === 'Liquidada') statusClass = 'text-green-600 bg-green-100';
                                    else if (fatura.status === 'Em Aberto') statusClass = 'text-yellow-600 bg-yellow-100';
                                    else statusClass = 'text-gray-600 bg-gray-100';

                                    // Check for overdue visually
                                    const hoje = new Date().toISOString().split('T')[0];
                                    if (fatura.status === 'Em Aberto' && fatura.data_vencimento < hoje) {
                                        statusClass = 'text-red-600 bg-red-100';
                                        fatura.status = 'Atrasada';
                                    }

                                    // Table Row
                                    html += `
                                        <tr class="border-b border-gray-50 hover:bg-gray-100 transition cursor-pointer" onclick="window.location.href='fatura_view.php?id=${fatura.id_fatura}'" title="Clique para ver a fatura">
                                            <td class="p-4">#${fatura.id_fatura}</td>
                                            <td class="p-4 font-medium">${fatura.nome}</td>
                                            <td class="p-4">${formatCurrency(fatura.valor_total_fatura)}</td>
                                            <td class="p-4">${formatDate(fatura.data_vencimento)}</td>
                                            <td class="p-4"><span class="px-3 py-1 rounded-full text-xs font-semibold ${statusClass}">${fatura.status}</span></td>
                                            <td class="p-4 text-right"><span class="material-icons text-gray-400 text-sm">open_in_new</span></td>
                                        </tr>
                                    `;

                                    // Card
                                    htmlCards += `
                                        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 mb-3 cursor-pointer hover:shadow-md transition hover:border-blue-200" onclick="window.location.href='fatura_view.php?id=${fatura.id_fatura}'">
                                            <div class="flex justify-between items-start mb-2">
                                               <div>
                                                   <span class="text-xs text-gray-400">#${fatura.id_fatura}</span>
                                                   <h4 class="font-bold text-gray-800">${fatura.nome}</h4>
                                               </div>
                                               <span class="px-2 py-0.5 rounded-full text-xs font-semibold ${statusClass}">${fatura.status}</span>
                                            </div>
                                            <div class="flex justify-between items-end mt-2">
                                               <div class="text-sm text-gray-500">
                                                   Venc: ${formatDate(fatura.data_vencimento)}
                                               </div>
                                               <div class="flex items-center">
                                                   <div class="text-lg font-bold text-gray-800 mr-2">
                                                       ${formatCurrency(fatura.valor_total_fatura)}
                                                   </div>
                                                   <span class="material-icons text-gray-400 text-sm">chevron_right</span>
                                               </div>
                                            </div>
                                        </div>
                                    `;
                                });
                            } else {
                                html = '<tr><td colspan="6" class="p-4 text-center">Nenhuma fatura recente encontrada.</td></tr>';
                                htmlCards = '<div class="text-center text-gray-500 py-4">Nenhuma fatura recente encontrada.</div>';
                            }
                            $('#listaFaturasRecentes').html(html);
                            $('#listaFaturasRecentesCards').html(htmlCards);

                            // Render Chart
                            renderChart(data.grafico);
                        } else {
                            console.error('Erro ao carregar dashboard:', response.message);
                        }
                    },
                    error: function (err) {
                        console.error('AJAX Error:', err);
                    }
                });
            }

            function renderChart(graficoData) {
                const ctx = document.getElementById('revenueChart').getContext('2d');

                if (revenueChart) {
                    revenueChart.destroy();
                }

                revenueChart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: graficoData.labels,
                        datasets: [{
                            label: 'Recebido (R$)',
                            data: graficoData.values,
                            backgroundColor: 'rgba(37, 99, 235, 0.6)', // Blue-600
                            borderColor: 'rgba(37, 99, 235, 1)',
                            borderWidth: 1,
                            borderRadius: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function (value) {
                                        return 'R$ ' + value; // Simple formatting
                                    }
                                }
                            }
                        }
                    }
                });
            }

            function formatCurrency(value) {
                return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value);
            }

            function formatDate(dateString) {
                if (!dateString) return '-';
                const [year, month, day] = dateString.split('-');
                return `${day}/${month}/${year}`;
            }

            function escapeHtml(text) {
                if (!text) return '';
                return String(text)
                    .replace(/&/g, "&amp;")
                    .replace(/</g, "&lt;")
                    .replace(/>/g, "&gt;")
                    .replace(/"/g, "&quot;")
                    .replace(/'/g, "&#039;");
            }

            // Atendimentos Recentes (Modo Clínico)
            <?php if (AppHelper::isVetMode()): ?>
            window.loadAtendimentosRecentes = function(page = 1) {
                $.ajax({
                    url: 'app.php',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'get_atendimentos_recentes',
                        page: page,
                        limit: 10
                    },
                    success: function (response) {
                        if (response.success) {
                            const data = response.data;
                            const items = data.items || [];
                            const total = data.total || 0;
                            const totalPages = data.total_pages || 1;
                            const currentPage = data.page || 1;

                            $('#atendimentosTotalBadge').text(`${total} Atendimento${total !== 1 ? 's' : ''}`);

                            let htmlTable = '';
                            let htmlCards = '';

                            if (items.length > 0) {
                                items.forEach(atend => {
                                    const dataFormatada = formatDateTime(atend.data_atendimento);
                                    const queixa = atend.queixa_principal ? escapeHtml(atend.queixa_principal) : '<span class="text-gray-400 italic">Não informada</span>';
                                    const petNome = escapeHtml(atend.pet_nome || 'N/A');
                                    const tutorNome = escapeHtml(atend.tutor_nome || 'N/A');
                                    const vetNome = escapeHtml(atend.vet_nome || 'N/A');
                                    const linkForm = `modules/Vet/atendimento_form.php?id=${atend.id_atendimento}&pet_id=${atend.id_pet || ''}`;

                                    // Table row
                                    htmlTable += `
                                        <tr class="border-b border-gray-50 hover:bg-gray-100 transition cursor-pointer" onclick="window.location.href='${linkForm}'" title="Ver / Editar Atendimento">
                                            <td class="p-4 font-semibold text-gray-700">#${atend.id_atendimento}</td>
                                            <td class="p-4 text-xs font-medium text-gray-600">${dataFormatada}</td>
                                            <td class="p-4 font-semibold text-teal-700">${petNome}</td>
                                            <td class="p-4 text-gray-600">${tutorNome}</td>
                                            <td class="p-4 text-gray-600">${vetNome}</td>
                                            <td class="p-4 text-xs text-gray-500 max-w-xs truncate">${queixa}</td>
                                            <td class="p-4 text-right">
                                                <span class="material-icons text-teal-600 text-sm hover:text-teal-800">visibility</span>
                                            </td>
                                        </tr>
                                    `;

                                    // Card Mobile
                                    htmlCards += `
                                        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 mb-3 cursor-pointer hover:shadow-md transition hover:border-teal-200" onclick="window.location.href='${linkForm}'">
                                            <div class="flex justify-between items-start mb-2">
                                                <div>
                                                    <span class="text-xs text-gray-400">#${atend.id_atendimento} • ${dataFormatada}</span>
                                                    <h4 class="font-bold text-teal-700">${petNome}</h4>
                                                    <p class="text-xs text-gray-500">Tutor: ${tutorNome}</p>
                                                </div>
                                                <span class="material-icons text-teal-600 text-sm">chevron_right</span>
                                            </div>
                                            <div class="mt-2 pt-2 border-t border-gray-100 text-xs text-gray-600">
                                                <div><strong>Vet:</strong> ${vetNome}</div>
                                                <div class="mt-1 text-gray-500 truncate"><strong>Queixa:</strong> ${queixa}</div>
                                            </div>
                                        </div>
                                    `;
                                });
                            } else {
                                htmlTable = '<tr><td colspan="7" class="p-4 text-center text-gray-500">Nenhum atendimento registrado.</td></tr>';
                                htmlCards = '<div class="text-center text-gray-500 py-4">Nenhum atendimento registrado.</div>';
                            }

                            $('#listaAtendimentosRecentes').html(htmlTable);
                            $('#listaAtendimentosRecentesCards').html(htmlCards);

                            // Render Pagination
                            renderAtendimentosPaginacao(currentPage, totalPages, total);
                        }
                    },
                    error: function (err) {
                        console.error('Erro ao carregar atendimentos recentes:', err);
                    }
                });
            };

            function renderAtendimentosPaginacao(currentPage, totalPages, total) {
                const perPage = 10;
                const startItem = total === 0 ? 0 : (currentPage - 1) * perPage + 1;
                const endItem = Math.min(currentPage * perPage, total);

                $('#atendimentosPaginacaoInfo').text(`Mostrando ${startItem} - ${endItem} de ${total} atendimentos`);

                let botoesHtml = '';

                // Botão Anterior
                if (currentPage > 1) {
                    botoesHtml += `<button onclick="loadAtendimentosRecentes(${currentPage - 1})" class="px-3 py-1 rounded text-xs border border-gray-300 bg-white hover:bg-gray-100 text-gray-700 font-medium transition flex items-center gap-1"><span class="material-icons text-xs">chevron_left</span> Anterior</button>`;
                } else {
                    botoesHtml += `<button disabled class="px-3 py-1 rounded text-xs border border-gray-200 bg-gray-100 text-gray-400 cursor-not-allowed font-medium flex items-center gap-1"><span class="material-icons text-xs">chevron_left</span> Anterior</button>`;
                }

                // Indicador de Página
                botoesHtml += `<span class="px-3 py-1 text-xs font-semibold text-gray-700">Página ${currentPage} de ${totalPages}</span>`;

                // Botão Próximo
                if (currentPage < totalPages) {
                    botoesHtml += `<button onclick="loadAtendimentosRecentes(${currentPage + 1})" class="px-3 py-1 rounded text-xs border border-gray-300 bg-white hover:bg-gray-100 text-gray-700 font-medium transition flex items-center gap-1">Próximo <span class="material-icons text-xs">chevron_right</span></button>`;
                } else {
                    botoesHtml += `<button disabled class="px-3 py-1 rounded text-xs border border-gray-200 bg-gray-100 text-gray-400 cursor-not-allowed font-medium flex items-center gap-1">Próximo <span class="material-icons text-xs">chevron_right</span></button>`;
                }

                $('#atendimentosPaginacaoBotoes').html(botoesHtml);
            }

            function formatDateTime(dtStr) {
                if (!dtStr) return '-';
                const parts = dtStr.split(' ');
                const datePart = parts[0] ? formatDate(parts[0]) : '-';
                const timePart = parts[1] ? parts[1].substring(0, 5) : '';
                return timePart ? `${datePart} às ${timePart}` : datePart;
            }

            loadAtendimentosRecentes(1);
            <?php endif; ?>

            // Init
            loadDashboard();

            // Filter Button
            $('#btnFiltrar').click(function () {
                loadDashboard();
            });

            // ==========================================
            // LOGICA MODAL EXTRATO BANCO INTER
            // ==========================================
            let extratoTransacoesCache = [];
            let extratoDatasAtuais = { dataInicio: '', dataFim: '' };

            window.fecharModalExtratoInter = function () {
                $('#modalExtratoInter').addClass('hidden');
            };

            $('#btnExtratoInter').click(function () {
                abrirExtratoInter();
            });

            function abrirExtratoInter() {
                const mesVal = $('#filtroMes').val() || '<?= date("Y-m") ?>';
                const parts = mesVal.split('-');
                const ano = parseInt(parts[0]);
                const mes = parseInt(parts[1]);

                const ultimoDia = new Date(ano, mes, 0).getDate();
                const dataInicio = `${mesVal}-01`;
                let dataFim = `${mesVal}-${String(ultimoDia).padStart(2, '0')}`;

                // Se o mês for o mês corrente, evita enviar data futura (ex: hoje é dia 17 e o mês tem 31)
                const hoje = new Date();
                const hojeStr = hoje.getFullYear() + '-' + String(hoje.getMonth() + 1).padStart(2, '0') + '-' + String(hoje.getDate()).padStart(2, '0');
                const mesAtualStr = hojeStr.substring(0, 7);
                if (mesVal === mesAtualStr && dataFim > hojeStr) {
                    dataFim = hojeStr;
                }

                extratoDatasAtuais = { dataInicio, dataFim };

                const dataInicioBr = formatDate(dataInicio);
                const dataFimBr = formatDate(dataFim);
                $('#extratoPeriodoInfo').text(`Período: ${dataInicioBr} até ${dataFimBr}`);

                $('#modalExtratoInter').removeClass('hidden');
                $('#extratoLoading').removeClass('hidden');
                $('#extratoErro').addClass('hidden');
                $('#extratoTabelaContainer').addClass('hidden');
                $('#extratoListaTransacoes').empty();
                $('#filtroTextoExtrato').val('');

                $.ajax({
                    url: '../inter/endpoint.php',
                    type: 'GET',
                    dataType: 'json',
                    data: {
                        action: 'consultar_extrato_completo',
                        dataInicio: dataInicio,
                        dataFim: dataFim,
                        tamanhoPagina: 100
                    },
                    success: function (res) {
                        $('#extratoLoading').addClass('hidden');
                        if (res.success && res.data) {
                            let transacoes = [];
                            if (Array.isArray(res.data.transacoes)) {
                                transacoes = res.data.transacoes;
                            } else if (Array.isArray(res.data)) {
                                transacoes = res.data;
                            } else if (typeof res.data === 'string') {
                                try {
                                    const parsed = JSON.parse(res.data);
                                    transacoes = parsed.transacoes || (Array.isArray(parsed) ? parsed : []);
                                } catch (e) {
                                    console.error('Erro ao interpretar JSON de transações:', e);
                                }
                            }
                            extratoTransacoesCache = transacoes;
                            renderizarExtratoTransacoes(extratoTransacoesCache);
                            $('#extratoTabelaContainer').removeClass('hidden');
                        } else {
                            $('#extratoErroMsg').text(res.message || 'Erro ao carregar transações do Inter.');
                            $('#extratoErro').removeClass('hidden');
                        }
                    },
                    error: function (xhr) {
                        $('#extratoLoading').addClass('hidden');
                        let msg = 'Erro de comunicação ao consultar o extrato no Banco Inter.';
                        if (xhr.responseText) {
                            try {
                                const parsed = JSON.parse(xhr.responseText);
                                if (parsed && parsed.message) msg = parsed.message;
                            } catch (e) {}
                        }
                        $('#extratoErroMsg').text(msg);
                        $('#extratoErro').removeClass('hidden');
                    }
                });
            }

            function renderizarExtratoTransacoes(transacoes) {
                let totalEntradas = 0;
                let totalSaidas = 0;
                let html = '';

                const filtro = ($('#filtroTextoExtrato').val() || '').toLowerCase().trim();

                if (!Array.isArray(transacoes)) {
                    transacoes = [];
                }

                const filtradas = transacoes.filter(t => {
                    if (!filtro) return true;
                    const detalhes = t.detalhes || {};
                    const titulo = (t.titulo || '').toLowerCase();
                    const desc = (t.descricao || '').toLowerCase();
                    const tipo = (t.tipoTransacao || '').toLowerCase();
                    const nomePagador = (detalhes.nomePagador || '').toLowerCase();
                    const txId = (detalhes.txId || '').toLowerCase();
                    const doc = (t.numeroDocumento || '').toLowerCase();
                    const endToEndId = (detalhes.endToEndId || '').toLowerCase();
                    return titulo.includes(filtro) || desc.includes(filtro) || tipo.includes(filtro) || nomePagador.includes(filtro) || txId.includes(filtro) || doc.includes(filtro) || endToEndId.includes(filtro);
                });

                transacoes.forEach(t => {
                    const valor = Math.abs(parseFloat(t.valor) || 0);
                    if (t.tipoOperacao === 'C') {
                        totalEntradas += valor;
                    } else if (t.tipoOperacao === 'D') {
                        totalSaidas += valor;
                    }
                });

                $('#extratoTotalEntradas').text(formatCurrency(totalEntradas));
                $('#extratoTotalSaidas').text(formatCurrency(totalSaidas));
                $('#extratoTotalTransacoes').text(transacoes.length);

                if (filtradas.length > 0) {
                    filtradas.forEach(t => {
                        const valor = Math.abs(parseFloat(t.valor) || 0);
                        const isCredito = (t.tipoOperacao === 'C');
                        const valorClass = isCredito ? 'text-emerald-600 font-bold' : 'text-rose-600 font-bold';
                        const valorSinal = isCredito ? '+ ' : '- ';
                        const badgeOp = isCredito 
                            ? '<span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-100 text-emerald-800">Crédito</span>'
                            : '<span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-rose-100 text-rose-800">Débito</span>';

                        const dataFormatada = t.dataTransacao ? formatDate(t.dataTransacao) : (t.dataInclusao ? formatDate(t.dataInclusao.substring(0, 10)) : '-');
                        const horaFormatada = t.dataInclusao && t.dataInclusao.length >= 19 ? t.dataInclusao.substring(11, 19) : '';
                        const detalhes = t.detalhes || {};

                        html += `
                            <tr class="hover:bg-orange-50/50 transition">
                                <td class="px-4 py-3 whitespace-nowrap text-gray-600">
                                    <div class="font-medium text-gray-800">${dataFormatada}</div>
                                    ${horaFormatada ? `<div class="text-[10px] text-gray-400 font-mono">${horaFormatada}</div>` : ''}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="flex flex-col items-start gap-1">
                                        <span class="px-2 py-0.5 rounded bg-gray-100 text-gray-700 font-mono font-semibold text-[10px]">${escapeHtml(t.tipoTransacao || 'OUTRO')}</span>
                                        ${badgeOp}
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-gray-900">${escapeHtml(t.titulo || 'Transação')}</div>
                                    <div class="text-gray-500 truncate max-w-xs" title="${escapeHtml(t.descricao || '')}">${escapeHtml(t.descricao || '-')}</div>
                                    ${t.numeroDocumento ? `<div class="text-[10px] text-gray-400 font-mono">Doc: ${escapeHtml(t.numeroDocumento)}</div>` : ''}
                                </td>
                                <td class="px-4 py-3">
                                    ${detalhes.nomePagador ? `<div class="font-medium text-gray-800">${escapeHtml(detalhes.nomePagador)}</div>` : ''}
                                    ${detalhes.cpfCnpjPagador ? `<div class="text-[10px] text-gray-500 font-mono">Doc: ${escapeHtml(detalhes.cpfCnpjPagador)}</div>` : ''}
                                    ${detalhes.txId ? `<div class="text-[10px] text-orange-700 font-mono truncate max-w-xs" title="${escapeHtml(detalhes.txId)}">txId: ${escapeHtml(detalhes.txId)}</div>` : ''}
                                    ${detalhes.descricaoPix ? `<div class="text-[10px] text-gray-500 italic truncate max-w-xs">${escapeHtml(detalhes.descricaoPix)}</div>` : ''}
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <span class="${valorClass}">${valorSinal}${formatCurrency(valor)}</span>
                                </td>
                            </tr>
                        `;
                    });
                } else {
                    html = `<tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">Nenhuma transação encontrada no período selecionado.</td></tr>`;
                }

                $('#extratoListaTransacoes').html(html);
            }

            $('#filtroTextoExtrato').on('input', function () {
                renderizarExtratoTransacoes(extratoTransacoesCache);
            });

            $('#btnExportarPdfExtrato').click(function () {
                if (extratoDatasAtuais.dataInicio && extratoDatasAtuais.dataFim) {
                    window.open(`../inter/endpoint.php?action=exportar_extrato_pdf&dataInicio=${extratoDatasAtuais.dataInicio}&dataFim=${extratoDatasAtuais.dataFim}&download=1`, '_blank');
                }
            });
        });
    </script>
</body>

</html>