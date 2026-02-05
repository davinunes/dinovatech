<?php
session_set_cookie_params(0, '/');
session_start();
$cliente_logado = isset($_SESSION['cliente_id']);
$nome_cliente = $_SESSION['cliente_nome'] ?? '';
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <title>Área do Cliente - Dinovatech</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>

<body class="bg-gray-50 min-h-screen flex flex-col">

    <!-- Header -->
    <header class="bg-white shadow-sm border-b border-gray-100">
        <div class="container mx-auto px-4 h-16 flex justify-between items-center">
            <h1 class="text-xl font-bold text-gray-800 tracking-tight"><a href="../index.php">Área do <span
                        class="text-cyan-600">Cliente</span></a></h1>
            <?php if ($cliente_logado): ?>
                <div class="flex items-center gap-4">
                    <span class="text-sm text-gray-600 hidden md:inline">Olá, <strong>
                            <?= htmlspecialchars($nome_cliente) ?>
                        </strong></span>
                    <button id="btnLogout" class="text-sm font-medium text-red-500 hover:text-red-700">Sair</button>
                </div>
            <?php endif; ?>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1 container mx-auto px-4 py-8">

        <?php if (!$cliente_logado): ?>
            <!-- Login Section -->
            <div id="loginSection" class="max-w-md mx-auto bg-white p-8 rounded-xl shadow-sm border border-gray-100 mt-10">
                <h2 class="text-2xl font-bold text-center text-gray-800 mb-2">Acesse suas Faturas</h2>
                <p class="text-center text-gray-500 mb-8">Digite seu CPF ou CNPJ para continuar.</p>

                <form id="loginForm" class="space-y-4">
                    <div>
                        <label for="cpfCnpjLogin" class="block text-sm font-medium text-gray-700 mb-1">CPF / CNPJ</label>
                        <input type="text" id="cpfCnpjLogin" name="cpf_cnpj" placeholder="000.000.000-00" required
                            class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition">
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" id="rememberMe" name="remember_me"
                            class="h-4 w-4 text-cyan-600 focus:ring-cyan-500 border-gray-300 rounded">
                        <label for="rememberMe" class="ml-2 block text-sm text-gray-600">Lembrar meus dados</label>
                    </div>

                    <button type="submit"
                        class="w-full bg-cyan-600 hover:bg-cyan-700 text-white font-bold py-3 rounded-lg transition-colors shadow-md">Entrar</button>
                </form>
                <div id="loginMessage" class="mt-4 text-center text-sm font-medium"></div>
            </div>
        <?php else: ?>
            <!-- Dashboard Section -->
            <div id="dashboardSection">

                <!-- Tabs -->
                <div class="flex border-b border-gray-200 mb-6">
                    <button
                        class="tab-btn px-6 py-3 font-medium text-cyan-600 border-b-2 border-cyan-600 focus:outline-none transition-colors"
                        data-target="abertas">
                        Faturas em Aberto
                    </button>
                    <button
                        class="tab-btn px-6 py-3 font-medium text-gray-500 hover:text-gray-700 focus:outline-none transition-colors"
                        data-target="pagas">
                        Histórico / Pagas
                    </button>
                </div>

                <!-- Open Invoices Content -->
                <div id="abertas" class="tab-content">
                    <div id="listAbertas" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <p class="col-span-full text-center py-8 text-gray-500">Carregando faturas...</p>
                    </div>
                </div>

                <!-- Paid Invoices Content -->
                <div id="pagas" class="tab-content hidden">
                    <div id="listPagas" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <p class="col-span-full text-center py-8 text-gray-500">Carregando histórico...</p>
                    </div>
                </div>

            </div>
        <?php endif; ?>

    </main>

    <script>
        $(document).ready(function () {
            // Mask for CPF/CNPJ
            $('#cpfCnpjLogin').on('input', function () {
                this.value = this.value.replace(/[^0-9]/g, '');
            });

            // Tab Switching
            $('.tab-btn').click(function () {
                $('.tab-btn').removeClass('text-cyan-600 border-b-2 border-cyan-600').addClass('text-gray-500 hover:text-gray-700');
                $(this).removeClass('text-gray-500 hover:text-gray-700').addClass('text-cyan-600 border-b-2 border-cyan-600');

                const target = $(this).data('target');
                $('.tab-content').addClass('hidden');
                $('#' + target).removeClass('hidden');
            });

            // Login Logic
            $('#loginForm').on('submit', function (e) {
                e.preventDefault();
                const cpfCnpj = $('#cpfCnpjLogin').val();
                const rememberMe = $('#rememberMe').is(':checked');
                const btn = $(this).find('button[type="submit"]');

                btn.prop('disabled', true).text('Verificando...');

                $.ajax({
                    url: '../dinovatech/app.php', type: 'POST', dataType: 'json',
                    data: { action: 'validar_cpf_cnpj', cpf_cnpj: cpfCnpj },
                    success: function (response) {
                        if (response.success) {
                            if (rememberMe) localStorage.setItem('dinovatech_cpf_cnpj', cpfCnpj);
                            else localStorage.removeItem('dinovatech_cpf_cnpj');
                            window.location.reload(); // Reload to pick up PHP session
                        } else {
                            $('#loginMessage').removeClass('text-green-600').addClass('text-red-600').text(response.message);
                            btn.prop('disabled', false).text('Entrar');
                        }
                    },
                    error: function () {
                        alert('Erro ao conectar.');
                        btn.prop('disabled', false).text('Entrar');
                    }
                });
            });

            // Logout
            $('#btnLogout').click(function () {
                $.post('logout.php', function () {
                    localStorage.removeItem('dinovatech_cpf_cnpj');
                    window.location.reload();
                });
            });

            // Auto-fill from LocalStorage
            const savedCpf = localStorage.getItem('dinovatech_cpf_cnpj');
            if (savedCpf && $('#cpfCnpjLogin').length) {
                $('#cpfCnpjLogin').val(savedCpf);
                $('#rememberMe').prop('checked', true);
            }

            // Load Faturas if logged in
            <?php if ($cliente_logado): ?>
                loadFaturas();
            <?php endif; ?>

            function loadFaturas() {
                $.ajax({
                    url: '../dinovatech/app.php', type: 'POST', dataType: 'json',
                    data: { action: 'buscar_faturas_cliente', id_cliente: '<?= $_SESSION['cliente_id'] ?? '' ?>' },
                    success: function (response) {
                        if (response.success && response.data.length > 0) {
                            renderFaturas(response.data);
                        } else {
                            $('#listAbertas').html('<p class="col-span-full text-center py-8 text-gray-500">Nenhuma fatura em aberto.</p>');
                            $('#listPagas').html('<p class="col-span-full text-center py-8 text-gray-500">Nenhuma fatura paga.</p>');
                        }
                    }
                });
            }

            function renderFaturas(faturas) {
                const abertasContainer = $('#listAbertas');
                const pagasContainer = $('#listPagas');
                abertasContainer.empty();
                pagasContainer.empty();

                let hasAbertas = false;
                let hasPagas = false;

                faturas.forEach(fatura => {
                    const isLiquidada = fatura.status === 'Liquidada';
                    const hoje = new Date().toISOString().split('T')[0];
                    const isAtrasada = !isLiquidada && fatura.data_vencimento < hoje;

                    const statusLabel = isLiquidada ? 'Paga' : (isAtrasada ? 'Atrasada' : 'Em Aberto');
                    const statusClass = isLiquidada ? 'bg-green-100 text-green-700' : (isAtrasada ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700');
                    const icon = isLiquidada ? 'check_circle' : (isAtrasada ? 'warning' : 'pending');

                    const total = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(fatura.valor_total_fatura);
                    const vencimento = new Date(fatura.data_vencimento).toLocaleDateString('pt-BR');

                    const html = `
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow p-6 flex flex-col justify-between">
                            <div>
                                <div class="flex justify-between items-start mb-4">
                                    <div class="p-2 rounded-lg ${statusClass} bg-opacity-20 text-opacity-100">
                                        <span class="material-icons text-2xl">${icon}</span>
                                    </div>
                                    <span class="px-3 py-1 rounded-full text-xs font-bold uppercase ${statusClass}">${statusLabel}</span>
                                </div>
                                <h3 class="text-2xl font-bold text-gray-800 mb-1">${total}</h3>
                                <p class="text-sm text-gray-500">Vencimento: ${vencimento}</p>
                                <p class="text-xs text-gray-400 mt-2">Fatura #${fatura.id_fatura}</p>
                            </div>
                            <div class="mt-6">
                                <a href="fatura.php?id=${fatura.id_fatura}" class="block w-full text-center py-3 rounded-lg font-medium transition-colors ${isLiquidada ? 'bg-gray-100 text-gray-700 hover:bg-gray-200' : 'bg-cyan-600 text-white hover:bg-cyan-700'}">
                                    ${isLiquidada ? 'Ver Detalhes' : 'Pagar Agora'}
                                </a>
                            </div>
                        </div>
                    `;

                    if (isLiquidada) {
                        pagasContainer.append(html);
                        hasPagas = true;
                    } else {
                        abertasContainer.append(html);
                        hasAbertas = true;
                    }
                });

                if (!hasAbertas) abertasContainer.html('<p class="col-span-full text-center py-8 text-gray-500">Nenhuma fatura em aberto.</p>');
                if (!hasPagas) pagasContainer.html('<p class="col-span-full text-center py-8 text-gray-500">Nenhuma fatura paga.</p>');
            }
        });
    </script>
</body>

</html>