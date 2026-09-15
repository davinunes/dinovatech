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
    </script>
</body>
</html>
