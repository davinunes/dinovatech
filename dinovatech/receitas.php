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

// Verifica integração Inter
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
    <title>Receitas & Contas a Receber - Dinovatech</title>
    <?php include 'components/layout_head.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body class="bg-gray-50 flex">

    <?php include 'components/sidebar.php'; ?>

    <div class="flex-1 flex flex-col lg:ml-64 min-h-screen transition-all duration-300">
        <main class="flex-1 p-6 mt-16 lg:mt-0">

            <!-- Cabeçalho -->
            <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <span class="p-2.5 bg-emerald-100 text-emerald-600 rounded-xl">
                        <span class="material-icons text-2xl">arrow_circle_up</span>
                    </span>
                    <div>
                        <h2 class="text-2xl lg:text-3xl font-bold text-gray-800">Contas a Receber & Faturamento</h2>
                        <p class="text-gray-500 text-sm">Acompanhamento de entradas, faturas pagas, recebimentos em aberto e inadimplência.</p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <a href="servico_form.php" class="bg-cyan-600 hover:bg-cyan-700 text-white px-4 py-2 rounded-lg text-sm font-semibold shadow-sm transition flex items-center">
                        <span class="material-icons text-base mr-1.5">add_shopping_cart</span>
                        Nova Venda / Fatura
                    </a>
                </div>
            </div>

            <!-- Filtros -->
            <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-200 mb-6 flex flex-wrap items-end gap-3">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Mês de Referência</label>
                    <input type="month" id="filtroMes" value="<?= date('Y-m') ?>" class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm font-medium focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Cliente</label>
                    <input type="text" id="filtroClienteNome" placeholder="Todos os clientes..." class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm w-48 focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    <input type="hidden" id="filtroClienteId">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Serviço</label>
                    <input type="text" id="filtroServicoNome" placeholder="Todos os serviços..." class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm w-44 focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    <input type="hidden" id="filtroServicoId">
                </div>

                <div>
                    <button id="btnFiltrar" class="bg-slate-800 hover:bg-slate-900 text-white px-4 py-1.5 rounded-lg text-sm font-medium transition">
                        Filtrar
                    </button>
                </div>

                <?php if ($interConfigurado): ?>
                    <div>
                        <button id="btnExtratoInter" type="button" class="bg-orange-500 hover:bg-orange-600 text-white px-3 py-1.5 rounded-lg text-sm font-medium flex items-center shadow-sm transition gap-1.5" title="Consultar Extrato Banco Inter">
                            <span class="material-icons text-base">account_balance</span>
                            <span>Extrato Inter</span>
                        </button>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Cards de Indicadores de Receita -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                <!-- Total Recebido -->
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 flex items-center">
                    <div class="p-3 rounded-full bg-emerald-100 text-emerald-600 mr-4">
                        <span class="material-icons text-3xl">attach_money</span>
                    </div>
                    <div>
                        <p class="text-gray-500 text-xs font-semibold uppercase tracking-wider" id="lblTotalFaturado">Total Recebido (Mês)</p>
                        <h3 class="text-2xl font-bold text-emerald-600" id="statTotalFaturado">R$ 0,00</h3>
                        <span class="text-xs text-gray-400">Faturas já liquidadas</span>
                    </div>
                </div>

                <!-- A Receber -->
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-4">
                        <span class="material-icons text-3xl">pending_actions</span>
                    </div>
                    <div>
                        <p class="text-gray-500 text-xs font-semibold uppercase tracking-wider" id="lblTotalAberto">A Receber (Mês)</p>
                        <h3 class="text-2xl font-bold text-blue-600" id="statTotalAberto">R$ 0,00</h3>
                        <span class="text-xs text-gray-400">Vencendo no mês selecionado</span>
                    </div>
                </div>

                <!-- Em Atraso -->
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 flex items-center">
                    <div class="p-3 rounded-full bg-rose-100 text-rose-600 mr-4">
                        <span class="material-icons text-3xl">warning</span>
                    </div>
                    <div>
                        <p class="text-gray-500 text-xs font-semibold uppercase tracking-wider">Em Atraso (Geral)</p>
                        <h3 class="text-2xl font-bold text-rose-600" id="statTotalAtrasado">R$ 0,00</h3>
                        <span class="text-xs text-rose-500 font-medium">Inadimplência acumulada</span>
                    </div>
                </div>
            </div>

            <!-- Gráfico de Receitas & Faturamento -->
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 mb-8">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold text-gray-800">Evolução de Receitas Realizadas (Últimos Meses)</h3>
                    <span class="text-xs text-gray-400">Valores em Reais (R$)</span>
                </div>
                <div class="relative h-64 w-full">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>

            <!-- Listagem de Faturas Recentes -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="p-4 border-b border-gray-200 flex items-center justify-between bg-slate-50">
                    <div class="flex items-center gap-2">
                        <span class="material-icons text-gray-600">receipt</span>
                        <h3 class="font-bold text-gray-800 text-base">Faturas do Período</h3>
                    </div>
                    <span class="text-xs text-gray-400">Clique na linha para abrir a fatura completa</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-gray-600">
                        <thead class="bg-gray-100 text-xs text-gray-500 uppercase font-semibold border-b border-gray-200">
                            <tr>
                                <th class="p-4">Fatura</th>
                                <th class="p-4">Cliente</th>
                                <th class="p-4">Valor</th>
                                <th class="p-4">Vencimento</th>
                                <th class="p-4">Status</th>
                                <th class="p-4 text-right">Ação</th>
                            </tr>
                        </thead>
                        <tbody id="listaFaturasRecentes" class="divide-y divide-gray-100">
                            <tr>
                                <td colspan="6" class="p-6 text-center text-gray-400">
                                    <span class="material-icons animate-spin text-2xl mb-1">refresh</span>
                                    <p>Carregando faturas...</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- MODAL EXTRATO BANCO INTER -->
            <div id="modalExtratoInter" class="fixed inset-0 z-50 hidden overflow-y-auto bg-black bg-opacity-50 flex items-center justify-center p-2 sm:p-4">
                <div class="bg-white rounded-2xl shadow-2xl max-w-5xl w-full max-h-[94vh] flex flex-col overflow-hidden animate-fade-in border border-gray-100">
                    <!-- Modal Header -->
                    <div class="px-4 sm:px-6 py-3.5 sm:py-4 bg-gradient-to-r from-orange-500 to-amber-500 text-white flex items-center justify-between shadow-sm">
                        <div class="flex items-center gap-2.5 sm:gap-3">
                            <div class="p-2 bg-white bg-opacity-20 rounded-xl">
                                <span class="material-icons text-xl sm:text-2xl">account_balance</span>
                            </div>
                            <div>
                                <h3 class="text-base sm:text-lg font-bold">Extrato Banco Inter</h3>
                                <p class="text-[11px] sm:text-xs text-orange-100" id="extratoPeriodoInfo">Período: -</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <button id="btnExportarPdfExtrato" type="button" class="bg-white bg-opacity-20 hover:bg-opacity-30 text-white text-xs px-2.5 sm:px-3 py-1.5 rounded-lg font-medium transition flex items-center gap-1 shadow-sm">
                                <span class="material-icons text-sm">picture_as_pdf</span>
                                <span class="hidden sm:inline">Exportar PDF</span>
                            </button>
                            <button type="button" onclick="fecharModalExtratoInter()" class="text-white hover:text-orange-200 p-1 rounded-lg transition">
                                <span class="material-icons text-2xl">close</span>
                            </button>
                        </div>
                    </div>

                    <!-- Modal Body -->
                    <div class="p-3 sm:p-6 overflow-y-auto flex-1 space-y-4 sm:space-y-6">
                        <!-- Cards de Resumo -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 sm:gap-4">
                            <div class="bg-emerald-50 border border-emerald-100 rounded-xl p-3 sm:p-4 flex items-center gap-3 shadow-sm">
                                <div class="p-2.5 bg-emerald-100 text-emerald-600 rounded-xl">
                                    <span class="material-icons text-lg sm:text-xl">arrow_downward</span>
                                </div>
                                <div>
                                    <p class="text-[10px] sm:text-xs font-semibold text-emerald-600 uppercase tracking-wider">Entradas / Créditos</p>
                                    <h4 class="text-base sm:text-lg font-bold text-emerald-900" id="extratoTotalEntradas">R$ 0,00</h4>
                                </div>
                            </div>
                            <div class="bg-rose-50 border border-rose-100 rounded-xl p-3 sm:p-4 flex items-center gap-3 shadow-sm">
                                <div class="p-2.5 bg-rose-100 text-rose-600 rounded-xl">
                                    <span class="material-icons text-lg sm:text-xl">arrow_upward</span>
                                </div>
                                <div>
                                    <p class="text-[10px] sm:text-xs font-semibold text-rose-600 uppercase tracking-wider">Saídas / Débitos</p>
                                    <h4 class="text-base sm:text-lg font-bold text-rose-900" id="extratoTotalSaidas">R$ 0,00</h4>
                                </div>
                            </div>
                            <div class="bg-slate-50 border border-slate-200 rounded-xl p-3 sm:p-4 flex items-center gap-3 shadow-sm">
                                <div class="p-2.5 bg-slate-200 text-slate-700 rounded-xl">
                                    <span class="material-icons text-lg sm:text-xl">receipt_long</span>
                                </div>
                                <div>
                                    <p class="text-[10px] sm:text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Transações</p>
                                    <h4 class="text-base sm:text-lg font-bold text-slate-800" id="extratoTotalTransacoes">0</h4>
                                </div>
                            </div>
                        </div>

                        <!-- Filtro de busca rápida no extrato -->
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5">
                            <div class="relative flex-1 sm:max-w-xs w-full">
                                <span class="material-icons absolute left-3 top-2.5 text-gray-400 text-sm">search</span>
                                <input type="text" id="filtroTextoExtrato" placeholder="Buscar no extrato..."
                                    class="w-full pl-9 pr-3 py-2 text-xs border border-gray-300 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500 shadow-sm">
                            </div>
                            <span class="text-[11px] text-gray-400 italic">Extrato Oficial Banco Inter • Banking v2</span>
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

                        <!-- Container de Transações (Desktop e Mobile) -->
                        <div id="extratoTabelaContainer" class="hidden space-y-3">
                            <!-- Visualização Desktop: Tabela de 100% largura sem scroll horizontal -->
                            <div class="hidden md:block border border-gray-200 rounded-xl overflow-hidden shadow-sm">
                                <table class="w-full text-xs table-fixed divide-y divide-gray-200">
                                    <thead class="bg-gray-50 text-gray-600">
                                        <tr>
                                            <th class="w-28 px-3 py-3 text-left font-semibold">Data / Hora</th>
                                            <th class="w-28 px-3 py-3 text-left font-semibold">Tipo</th>
                                            <th class="px-4 py-3 text-left font-semibold">Título / Descrição</th>
                                            <th class="w-60 px-3 py-3 text-left font-semibold">Pagador / Detalhes</th>
                                            <th class="w-32 px-4 py-3 text-right font-semibold">Valor</th>
                                        </tr>
                                    </thead>
                                    <tbody id="extratoListaTransacoes" class="divide-y divide-gray-100 bg-white">
                                        <!-- Dynamic Table Rows -->
                                    </tbody>
                                </table>
                            </div>

                            <!-- Visualização Mobile: Feed de Cards Sem Rolagem Lateral -->
                            <div id="extratoMobileListaTransacoes" class="md:hidden space-y-2.5">
                                <!-- Dynamic Mobile Cards -->
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="px-4 sm:px-6 py-3 bg-gray-50 border-t border-gray-100 flex items-center justify-between text-xs text-gray-500">
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
        let revenueChart = null;

        $(document).ready(function () {
            loadDashboard();

            $('#btnFiltrar').click(function () {
                loadDashboard();
            });

            $('#filtroMes').change(function () {
                loadDashboard();
            });
        });

        function loadDashboard() {
            const mes = $('#filtroMes').val();
            const clienteId = $('#filtroClienteId').val();
            const servicoId = $('#filtroServicoId').val();

            $.ajax({
                url: 'app.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'get_dashboard_stats',
                    mes: mes,
                    id_cliente: clienteId,
                    id_servico: servicoId
                },
                success: function (response) {
                    if (response.success) {
                        const data = response.data;
                        $('#statTotalFaturado').text(formatCurrency(data.total_faturado));
                        $('#statTotalAberto').text(formatCurrency(data.total_aberto));
                        $('#statTotalAtrasado').text(formatCurrency(data.total_atrasado));

                        let html = '';
                        if (data.faturas_recentes && data.faturas_recentes.length > 0) {
                            data.faturas_recentes.forEach(f => {
                                let statusClass = 'text-gray-600 bg-gray-100';
                                if (f.status === 'Liquidada') statusClass = 'text-emerald-700 bg-emerald-100 font-bold';
                                else if (f.status === 'Em Aberto') statusClass = 'text-amber-700 bg-amber-100 font-bold';
                                
                                const hoje = new Date().toISOString().split('T')[0];
                                if (f.status === 'Em Aberto' && f.data_vencimento < hoje) {
                                    statusClass = 'text-rose-700 bg-rose-100 font-bold';
                                    f.status = 'Atrasada';
                                }

                                html += `
                                    <tr class="border-b border-gray-100 hover:bg-gray-50 transition cursor-pointer" onclick="window.location.href='fatura_view.php?id=${f.id_fatura}'">
                                        <td class="p-4 font-bold text-gray-800">#${f.id_fatura}</td>
                                        <td class="p-4 font-medium text-gray-900">${escapeHtml(f.nome)}</td>
                                        <td class="p-4 font-bold text-gray-900">${formatCurrency(f.valor_total_fatura)}</td>
                                        <td class="p-4">${formatDate(f.data_vencimento)}</td>
                                        <td class="p-4"><span class="px-2.5 py-1 rounded-full text-xs ${statusClass}">${f.status}</span></td>
                                        <td class="p-4 text-right">
                                            <span class="material-icons text-cyan-600 text-sm hover:text-cyan-800">open_in_new</span>
                                        </td>
                                    </tr>
                                `;
                            });
                        } else {
                            html = '<tr><td colspan="6" class="p-8 text-center text-gray-400">Nenhuma fatura encontrada para este período.</td></tr>';
                        }
                        $('#listaFaturasRecentes').html(html);

                        if (data.grafico) {
                            renderChart(data.grafico);
                        }
                    }
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
                        backgroundColor: 'rgba(16, 185, 129, 0.7)',
                        borderColor: 'rgba(16, 185, 129, 1)',
                        borderWidth: 1,
                        borderRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function (value) { return 'R$ ' + value; }
                            }
                        }
                    }
                }
            });
        }

        function formatCurrency(value) {
            return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value || 0);
        }

        function formatDate(dateString) {
            if (!dateString) return '-';
            if (dateString.includes('/')) return dateString;
            const clean = String(dateString).split('T')[0].split(' ')[0];
            const parts = clean.split('-');
            if (parts.length === 3) {
                return `${parts[2]}/${parts[1]}/${parts[0]}`;
            }
            return dateString;
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

        // ==========================================
        // LOGICA MODAL EXTRATO BANCO INTER
        // ==========================================
        let extratoTransacoesCache = [];
        let extratoDatasAtuais = { dataInicio: '', dataFim: '' };

        window.fecharModalExtratoInter = function () {
            $('#modalExtratoInter').addClass('hidden');
        };

        $(document).on('click', '#btnExtratoInter', function () {
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

            // Se o mês for o mês corrente, evita enviar data futura
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
            $('#extratoMobileListaTransacoes').empty();
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
                    if (res && res.success && res.data) {
                        let transacoes = [];
                        const d = res.data;

                        if (Array.isArray(d.transacoes)) {
                            transacoes = d.transacoes;
                        } else if (d.transacoes && typeof d.transacoes === 'object') {
                            transacoes = Object.values(d.transacoes);
                        } else if (Array.isArray(d)) {
                            transacoes = d;
                        } else if (typeof d === 'object') {
                            transacoes = Object.values(d);
                        } else if (typeof d === 'string') {
                            try {
                                const parsed = JSON.parse(d);
                                if (Array.isArray(parsed.transacoes)) transacoes = parsed.transacoes;
                                else if (parsed.transacoes && typeof parsed.transacoes === 'object') transacoes = Object.values(parsed.transacoes);
                                else if (Array.isArray(parsed)) transacoes = parsed;
                                else if (typeof parsed === 'object') transacoes = Object.values(parsed);
                            } catch (e) {
                                console.error('Erro ao interpretar JSON de transações:', e);
                            }
                        }

                        extratoTransacoesCache = Array.isArray(transacoes) ? transacoes : [];
                        renderizarExtratoTransacoes(extratoTransacoesCache);
                        $('#extratoTabelaContainer').removeClass('hidden');
                    } else {
                        $('#extratoErroMsg').text((res && res.message) ? res.message : 'Erro ao carregar transações do Inter.');
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
            let tableHtml = '';
            let mobileHtml = '';

            const filtro = ($('#filtroTextoExtrato').val() || '').toLowerCase().trim();

            if (!Array.isArray(transacoes)) {
                if (transacoes && typeof transacoes === 'object') {
                    transacoes = Object.values(transacoes);
                } else {
                    transacoes = [];
                }
            }

            const filtradas = transacoes.filter(t => {
                if (!t) return false;
                if (!filtro) return true;
                const detalhes = t.detalhes || {};
                const titulo = String(t.titulo || '').toLowerCase();
                const desc = String(t.descricao || '').toLowerCase();
                const tipo = String(t.tipoTransacao || '').toLowerCase();
                const nomePagador = String(detalhes.nomePagador || '').toLowerCase();
                const txId = String(detalhes.txId || '').toLowerCase();
                const doc = String(t.numeroDocumento || '').toLowerCase();
                const endToEndId = String(detalhes.endToEndId || '').toLowerCase();
                const clienteNome = (t.cliente_vinculado && t.cliente_vinculado.nome) ? String(t.cliente_vinculado.nome).toLowerCase() : '';
                const clienteCpf = (t.cliente_vinculado && t.cliente_vinculado.cpf_cnpj) ? String(t.cliente_vinculado.cpf_cnpj).toLowerCase() : '';
                return titulo.includes(filtro) || desc.includes(filtro) || tipo.includes(filtro) || nomePagador.includes(filtro) || txId.includes(filtro) || doc.includes(filtro) || endToEndId.includes(filtro) || clienteNome.includes(filtro) || clienteCpf.includes(filtro);
            });

            transacoes.forEach(t => {
                if (!t) return;
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
                    if (!t) return;
                    const valor = Math.abs(parseFloat(t.valor) || 0);
                    const isCredito = (t.tipoOperacao === 'C');
                    const valorClass = isCredito ? 'text-emerald-600 font-bold' : 'text-rose-600 font-bold';
                    const valorSinal = isCredito ? '+ ' : '- ';
                    const badgeOp = isCredito 
                        ? '<span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-100 text-emerald-800">Crédito</span>'
                        : '<span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-rose-100 text-rose-800">Débito</span>';

                    const dataFormatada = t.dataTransacao ? formatDate(t.dataTransacao) : (t.dataInclusao ? formatDate(String(t.dataInclusao).substring(0, 10)) : '-');
                    const horaFormatada = t.dataInclusao && String(t.dataInclusao).length >= 19 ? String(t.dataInclusao).substring(11, 19) : '';
                    const detalhes = t.detalhes || {};
                    const cli = t.cliente_vinculado || null;
                    const cliNome = (cli && cli.nome) ? String(cli.nome) : '';
                    const cliId = (cli && cli.id_cliente) ? cli.id_cliente : '';

                    // Bloco da Coluna Pagador / Detalhes (Desktop)
                    let pagadorColHtml = '';
                    if (cli && cliNome) {
                        const avatarHtml = cli.foto_url 
                            ? `<img src="${escapeHtml(cli.foto_url)}" alt="${escapeHtml(cliNome)}" class="w-8 h-8 rounded-full object-cover border border-orange-300 shadow-sm shrink-0">`
                            : `<div class="w-8 h-8 rounded-full bg-orange-100 text-orange-700 font-bold text-xs flex items-center justify-center border border-orange-200 shadow-sm shrink-0" title="${escapeHtml(cliNome)}">${escapeHtml(cliNome.charAt(0).toUpperCase())}</div>`;
                        
                        const nomeDiferente = detalhes.nomePagador && String(detalhes.nomePagador).toLowerCase().trim() !== cliNome.toLowerCase().trim();

                        pagadorColHtml = `
                            <div class="flex items-start gap-2.5">
                                <a href="cliente_detalhes.php?id=${encodeURIComponent(cliId)}" target="_blank" class="shrink-0 hover:opacity-85 transition transform hover:scale-105 inline-block" title="Abrir perfil de ${escapeHtml(cliNome)}">
                                    ${avatarHtml}
                                </a>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-1">
                                        <a href="cliente_detalhes.php?id=${encodeURIComponent(cliId)}" target="_blank" class="font-bold text-xs text-gray-900 hover:text-orange-600 transition truncate max-w-[175px] block leading-tight" title="Cliente cadastrado: ${escapeHtml(cliNome)}">
                                            ${escapeHtml(cliNome)}
                                        </a>
                                        <span class="material-icons text-[14px] text-emerald-500 shrink-0 select-none" title="Cliente vinculado ao cadastro">verified</span>
                                    </div>
                                    ${nomeDiferente ? `<div class="text-[10px] text-gray-400 truncate max-w-[175px] leading-tight mt-0.5" title="Nome no Extrato: ${escapeHtml(detalhes.nomePagador)}"><span class="font-medium">Banco:</span> ${escapeHtml(detalhes.nomePagador)}</div>` : ''}
                                    <div class="text-[10px] text-gray-500 font-mono leading-tight mt-0.5">${escapeHtml(detalhes.cpfCnpjPagador || cli.cpf_cnpj || '')}</div>
                                    ${detalhes.txId ? `<div class="text-[10px] text-orange-700 font-mono truncate max-w-[175px] leading-tight mt-0.5" title="${escapeHtml(detalhes.txId)}">txId: ${escapeHtml(detalhes.txId)}</div>` : ''}
                                    ${detalhes.descricaoPix ? `<div class="text-[10px] text-teal-700 italic truncate max-w-[175px] leading-tight mt-0.5" title="${escapeHtml(detalhes.descricaoPix)}">${escapeHtml(detalhes.descricaoPix)}</div>` : ''}
                                </div>
                            </div>
                        `;
                    } else {
                        pagadorColHtml = `
                            <div class="space-y-0.5">
                                ${detalhes.nomePagador ? `<div class="font-medium text-gray-800 truncate" title="${escapeHtml(detalhes.nomePagador)}">${escapeHtml(detalhes.nomePagador)}</div>` : '<div class="text-xs text-gray-400 italic">-</div>'}
                                ${detalhes.cpfCnpjPagador ? `<div class="text-[10px] text-gray-500 font-mono">${escapeHtml(detalhes.cpfCnpjPagador)}</div>` : ''}
                                ${detalhes.txId ? `<div class="text-[10px] text-orange-700 font-mono truncate" title="${escapeHtml(detalhes.txId)}">txId: ${escapeHtml(detalhes.txId)}</div>` : ''}
                                ${detalhes.descricaoPix ? `<div class="text-[10px] text-teal-700 italic truncate" title="${escapeHtml(detalhes.descricaoPix)}">${escapeHtml(detalhes.descricaoPix)}</div>` : ''}
                            </div>
                        `;
                    }

                    // Bloco Pagador / Detalhes (Mobile)
                    let pagadorMobileHtml = '';
                    if (cli && cliNome) {
                        const avatarMobileHtml = cli.foto_url 
                            ? `<img src="${escapeHtml(cli.foto_url)}" alt="${escapeHtml(cliNome)}" class="w-7 h-7 rounded-full object-cover border border-orange-300 shadow-sm shrink-0">`
                            : `<div class="w-7 h-7 rounded-full bg-orange-100 text-orange-700 font-bold text-[11px] flex items-center justify-center border border-orange-200 shadow-sm shrink-0" title="${escapeHtml(cliNome)}">${escapeHtml(cliNome.charAt(0).toUpperCase())}</div>`;
                        
                        const nomeDiferente = detalhes.nomePagador && String(detalhes.nomePagador).toLowerCase().trim() !== cliNome.toLowerCase().trim();

                        pagadorMobileHtml = `
                            <div class="flex items-center gap-2 pt-2 border-t border-gray-100">
                                <a href="cliente_detalhes.php?id=${encodeURIComponent(cliId)}" target="_blank" class="shrink-0 hover:opacity-85 transition" title="Ver detalhes do cliente">
                                    ${avatarMobileHtml}
                                </a>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-1">
                                        <a href="cliente_detalhes.php?id=${encodeURIComponent(cliId)}" target="_blank" class="text-xs font-bold text-gray-900 hover:text-orange-600 transition truncate">
                                            ${escapeHtml(cliNome)}
                                        </a>
                                        <span class="material-icons text-xs text-emerald-500 shrink-0" title="Cliente cadastrado">verified</span>
                                    </div>
                                    ${nomeDiferente ? `<p class="text-[10px] text-gray-400 truncate"><span class="font-medium">Banco:</span> ${escapeHtml(detalhes.nomePagador)}</p>` : ''}
                                    ${(detalhes.cpfCnpjPagador || cli.cpf_cnpj) ? `<p class="text-[10px] text-gray-400 font-mono">${escapeHtml(detalhes.cpfCnpjPagador || cli.cpf_cnpj)}</p>` : ''}
                                </div>
                            </div>
                        `;
                    } else if (detalhes.nomePagador || detalhes.cpfCnpjPagador) {
                        pagadorMobileHtml = `
                            <div class="pt-1.5 border-t border-gray-100">
                                ${detalhes.nomePagador ? `<p class="text-xs text-gray-700 font-medium truncate"><span class="text-gray-400 font-normal">Pagador:</span> ${escapeHtml(detalhes.nomePagador)}</p>` : ''}
                                ${detalhes.cpfCnpjPagador ? `<p class="text-[10px] text-gray-400 font-mono">${escapeHtml(detalhes.cpfCnpjPagador)}</p>` : ''}
                            </div>
                        `;
                    }

                    // 1. Linha da Tabela Desktop
                    tableHtml += `
                        <tr class="hover:bg-orange-50/50 transition">
                            <td class="px-3 py-3 text-gray-600">
                                <div class="font-medium text-gray-800">${dataFormatada}</div>
                                ${horaFormatada ? `<div class="text-[10px] text-gray-400 font-mono">${horaFormatada}</div>` : ''}
                            </td>
                            <td class="px-3 py-3">
                                <div class="flex flex-col items-start gap-1">
                                    <span class="px-1.5 py-0.5 rounded bg-gray-100 text-gray-700 font-mono font-semibold text-[10px] truncate max-w-full">${escapeHtml(t.tipoTransacao || 'OUTRO')}</span>
                                    ${badgeOp}
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-semibold text-gray-900 truncate" title="${escapeHtml(t.titulo || 'Transação')}">${escapeHtml(t.titulo || 'Transação')}</div>
                                <div class="text-gray-500 truncate" title="${escapeHtml(t.descricao || '')}">${escapeHtml(t.descricao || '-')}</div>
                                ${t.numeroDocumento ? `<div class="text-[10px] text-gray-400 font-mono truncate">Doc: ${escapeHtml(t.numeroDocumento)}</div>` : ''}
                            </td>
                            <td class="px-3 py-3">
                                ${pagadorColHtml}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <span class="${valorClass} text-sm">${valorSinal}${formatCurrency(valor)}</span>
                            </td>
                        </tr>
                    `;

                    // 2. Card para Mobile (Feed de Transações)
                    mobileHtml += `
                        <div class="bg-white border border-gray-200 rounded-xl p-3.5 shadow-sm space-y-2.5 hover:border-orange-200 transition">
                            <div class="flex items-center justify-between gap-2 border-b border-gray-100 pb-2">
                                <div class="flex items-center gap-1.5">
                                    <span class="px-2 py-0.5 rounded bg-gray-100 text-gray-700 font-mono font-bold text-[10px]">${escapeHtml(t.tipoTransacao || 'OUTRO')}</span>
                                    ${badgeOp}
                                </div>
                                <div class="text-[11px] text-gray-500 font-medium flex items-center gap-1">
                                    <span class="material-icons text-xs text-gray-400">schedule</span>
                                    <span>${dataFormatada}</span>
                                    ${horaFormatada ? `<span class="font-mono text-gray-400 text-[10px]">${horaFormatada}</span>` : ''}
                                </div>
                            </div>

                            <div class="flex items-start justify-between gap-3">
                                <div class="space-y-0.5 flex-1 min-w-0">
                                    <h5 class="font-bold text-gray-900 text-xs truncate" title="${escapeHtml(t.titulo || 'Transação')}">${escapeHtml(t.titulo || 'Transação')}</h5>
                                    ${t.descricao ? `<p class="text-xs text-gray-600 truncate" title="${escapeHtml(t.descricao)}">${escapeHtml(t.descricao)}</p>` : ''}
                                    ${detalhes.descricaoPix ? `<p class="text-[11px] text-teal-700 bg-teal-50 px-2 py-0.5 rounded border border-teal-100 font-medium inline-block truncate max-w-full">Pix: ${escapeHtml(detalhes.descricaoPix)}</p>` : ''}
                                </div>
                                <div class="text-right shrink-0">
                                    <span class="${valorClass} text-sm whitespace-nowrap block">${valorSinal}${formatCurrency(valor)}</span>
                                    ${t.numeroDocumento ? `<span class="text-[9px] text-gray-400 font-mono block">Doc #${escapeHtml(t.numeroDocumento)}</span>` : ''}
                                </div>
                            </div>

                            ${pagadorMobileHtml}
                        </div>
                    `;
                });
            } else {
                tableHtml = `<tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">Nenhuma transação encontrada no período selecionado.</td></tr>`;
                mobileHtml = `<div class="p-6 bg-gray-50 border border-gray-200 rounded-xl text-center text-xs text-gray-500">Nenhuma transação encontrada no período selecionado.</div>`;
            }

            $('#extratoListaTransacoes').html(tableHtml);
            $('#extratoMobileListaTransacoes').html(mobileHtml);
        }

        $(document).on('input', '#filtroTextoExtrato', function () {
            renderizarExtratoTransacoes(extratoTransacoesCache);
        });

        $(document).on('click', '#btnExportarPdfExtrato', function () {
            if (extratoDatasAtuais.dataInicio && extratoDatasAtuais.dataFim) {
                window.open(`../inter/endpoint.php?action=exportar_extrato_pdf&dataInicio=${extratoDatasAtuais.dataInicio}&dataFim=${extratoDatasAtuais.dataFim}&download=1`, '_blank');
            }
        });
    </script>
</body>
</html>
