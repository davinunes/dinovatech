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
</head>

<body class="bg-gray-50 flex">

    <?php include 'components/sidebar.php'; ?>

    <div class="flex-1 flex flex-col lg:ml-64 min-h-screen transition-all duration-300">
        <!-- Content Area -->
        <main class="flex-1 p-6 mt-16 lg:mt-0">

            <div class="mb-8">
                <h2 class="text-3xl font-bold text-gray-800">Dashboard Financeiro</h2>
                <p class="text-gray-500">Visão geral da saúde financeira.</p>
            </div>

            <!-- Cards Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">

                <!-- Card Total Faturado -->
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
                        <span class="material-icons text-3xl">attach_money</span>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm font-medium">Total Recebido</p>
                        <h3 class="text-2xl font-bold text-gray-800" id="statTotalFaturado">R$ 0,00</h3>
                    </div>
                </div>

                <!-- Card A Receber -->
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-4">
                        <span class="material-icons text-3xl">pending_actions</span>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm font-medium">A Receber</p>
                        <h3 class="text-2xl font-bold text-gray-800" id="statTotalAberto">R$ 0,00</h3>
                    </div>
                </div>

                <!-- Card Em Atraso -->
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center">
                    <div class="p-3 rounded-full bg-red-100 text-red-600 mr-4">
                        <span class="material-icons text-3xl">warning</span>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm font-medium">Em Atraso</p>
                        <h3 class="text-2xl font-bold text-red-600" id="statTotalAtrasado">R$ 0,00</h3>
                    </div>
                </div>
            </div>

            <!-- Recent Invoices Table -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-6 border-b border-gray-100">
                    <h3 class="text-lg font-semibold text-gray-800">Faturas Recentes</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 text-gray-600 text-sm uppercase tracking-wider">
                                <th class="p-4 font-medium">ID</th>
                                <th class="p-4 font-medium">Cliente</th>
                                <th class="p-4 font-medium">Valor</th>
                                <th class="p-4 font-medium">Vencimento</th>
                                <th class="p-4 font-medium">Status</th>
                            </tr>
                        </thead>
                        <tbody id="listaFaturasRecentes" class="text-gray-700 text-sm">
                            <tr>
                                <td colspan="5" class="p-4 text-center">Carregando...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

    <?php include 'components/layout_scripts.php'; ?>
    <script>
        $(document).ready(function () {
            // Fetch Dashboard Stats
            $.ajax({
                url: 'app.php',
                type: 'POST',
                dataType: 'json',
                data: { action: 'get_dashboard_stats' },
                success: function (response) {
                    if (response.success) {
                        const data = response.data;

                        $('#statTotalFaturado').text(formatCurrency(data.total_faturado));
                        $('#statTotalAberto').text(formatCurrency(data.total_aberto));
                        $('#statTotalAtrasado').text(formatCurrency(data.total_atrasado));

                        let html = '';
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

                                html += `
                                    <tr class="border-b border-gray-50 hover:bg-gray-50 transition">
                                        <td class="p-4">#${fatura.id_fatura}</td>
                                        <td class="p-4 font-medium">${fatura.nome}</td>
                                        <td class="p-4">${formatCurrency(fatura.valor_total_fatura)}</td>
                                        <td class="p-4">${formatDate(fatura.data_vencimento)}</td>
                                        <td class="p-4"><span class="px-3 py-1 rounded-full text-xs font-semibold ${statusClass}">${fatura.status}</span></td>
                                    </tr>
                                `;
                            });
                        } else {
                            html = '<tr><td colspan="5" class="p-4 text-center">Nenhuma fatura recente encontrada.</td></tr>';
                        }
                        $('#listaFaturasRecentes').html(html);
                    } else {
                        console.error('Erro ao carregar dashboard:', response.message);
                    }
                },
                error: function (err) {
                    console.error('AJAX Error:', err);
                }
            });

            function formatCurrency(value) {
                return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value);
            }

            function formatDate(dateString) {
                if (!dateString) return '-';
                const [year, month, day] = dateString.split('-');
                return `${day}/${month}/${year}`;
            }
        });
    </script>
</body>

</html>