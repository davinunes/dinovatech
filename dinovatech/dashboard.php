<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}
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

            // Init
            loadDashboard();

            // Filter Button
            $('#btnFiltrar').click(function () {
                loadDashboard();
            });
        });
    </script>
</body>

</html>