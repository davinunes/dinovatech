<?php
session_set_cookie_params(0, '/');
session_start();
require_once __DIR__ . '/../dinovatech/config.php';
require_once __DIR__ . '/../dinovatech/helpers/AppHelper.php';

$cliente_logado = isset($_SESSION['cliente_id']);
$nome_cliente = $_SESSION['cliente_nome'] ?? '';
$is_vet = AppHelper::isVetMode();
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
    <header class="bg-white shadow-sm border-b border-gray-100 sticky top-0 z-30">
        <div class="container mx-auto px-4 h-16 flex justify-between items-center">
            <h1 class="text-xl font-bold text-gray-800 tracking-tight">
                <a href="../index.php">Área do <span class="text-cyan-600">Cliente</span></a>
            </h1>
            <?php if ($cliente_logado): ?>
                <div class="flex items-center gap-4">
                    <span class="text-sm text-gray-600 hidden md:inline">Olá, <strong><?= htmlspecialchars($nome_cliente) ?></strong></span>
                    <button id="btnLogout" class="text-sm font-medium text-red-500 hover:text-red-700 flex items-center gap-1">
                        <span class="material-icons text-sm">logout</span> Sair
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1 container mx-auto px-4 py-8">

        <?php if (!$cliente_logado): ?>
            <!-- Login Section -->
            <div id="loginSection" class="max-w-md mx-auto bg-white p-8 rounded-xl shadow-sm border border-gray-100 mt-10">
                <h2 class="text-2xl font-bold text-center text-gray-800 mb-2">Acesse suas Faturas e Dados</h2>
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
                        class="w-full bg-cyan-600 hover:bg-cyan-700 text-white font-bold py-3 rounded-lg transition-colors shadow-md flex items-center justify-center gap-2">
                        <span class="material-icons text-sm">login</span> Entrar
                    </button>
                </form>
                <div id="loginMessage" class="mt-4 text-center text-sm font-medium"></div>
            </div>
        <?php else: ?>
            <!-- Dashboard Section -->
            <div id="dashboardSection">

                <!-- Tabs -->
                <div class="flex flex-wrap border-b border-gray-200 mb-6 bg-white rounded-t-xl px-2 pt-2 shadow-sm gap-1">
                    <button
                        class="tab-btn px-5 py-3 font-medium text-cyan-600 border-b-2 border-cyan-600 focus:outline-none transition-colors flex items-center gap-2 text-sm"
                        data-target="dashboard">
                        <span class="material-icons text-lg">dashboard</span> Visão Geral
                    </button>
                    <button
                        class="tab-btn px-5 py-3 font-medium text-gray-500 hover:text-gray-700 focus:outline-none transition-colors flex items-center gap-2 text-sm"
                        data-target="abertas">
                        <span class="material-icons text-lg">pending_actions</span> Faturas em Aberto
                    </button>
                    <button
                        class="tab-btn px-5 py-3 font-medium text-gray-500 hover:text-gray-700 focus:outline-none transition-colors flex items-center gap-2 text-sm"
                        data-target="pagas">
                        <span class="material-icons text-lg">receipt_long</span> Histórico / Pagas
                    </button>
                    <button
                        class="tab-btn px-5 py-3 font-medium text-gray-500 hover:text-gray-700 focus:outline-none transition-colors flex items-center gap-2 text-sm"
                        data-target="meusdados">
                        <span class="material-icons text-lg">person</span> Meus Dados
                    </button>
                    <?php if ($is_vet): ?>
                        <button
                            class="tab-btn px-5 py-3 font-medium text-gray-500 hover:text-gray-700 focus:outline-none transition-colors flex items-center gap-2 text-sm"
                            data-target="vacinas">
                            <span class="material-icons text-lg text-teal-600">vaccines</span> Carteira de Vacinação
                        </button>
                    <?php endif; ?>
                </div>

                <!-- 1. DASHBOARD TAB -->
                <div id="dashboard" class="tab-content">
                    
                    <!-- KPI Cards Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center">
                            <div class="p-3 rounded-full bg-yellow-100 text-yellow-600 mr-4">
                                <span class="material-icons text-3xl">pending_actions</span>
                            </div>
                            <div>
                                <p class="text-gray-500 text-xs font-medium uppercase tracking-wider">Faturas em Aberto</p>
                                <h3 class="text-xl font-bold text-gray-800" id="dashTotalAberto">R$ 0,00</h3>
                                <p class="text-xs text-gray-400" id="dashCountAberto">0 pendentes</p>
                            </div>
                        </div>

                        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center">
                            <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
                                <span class="material-icons text-3xl">check_circle</span>
                            </div>
                            <div>
                                <p class="text-gray-500 text-xs font-medium uppercase tracking-wider">Total Pago</p>
                                <h3 class="text-xl font-bold text-gray-800" id="dashTotalPago">R$ 0,00</h3>
                                <p class="text-xs text-gray-400" id="dashCountPago">0 liquidadas</p>
                            </div>
                        </div>

                        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center">
                            <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-4">
                                <span class="material-icons text-3xl">event</span>
                            </div>
                            <div>
                                <p class="text-gray-500 text-xs font-medium uppercase tracking-wider">Agendamentos</p>
                                <h3 class="text-xl font-bold text-gray-800" id="dashCountAgendamentos">0</h3>
                                <p class="text-xs text-gray-400">compromissos</p>
                            </div>
                        </div>

                        <?php if ($is_vet): ?>
                            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center">
                                <div class="p-3 rounded-full bg-teal-100 text-teal-600 mr-4">
                                    <span class="material-icons text-3xl">pets</span>
                                </div>
                                <div>
                                    <p class="text-gray-500 text-xs font-medium uppercase tracking-wider">Meus Pets</p>
                                    <h3 class="text-xl font-bold text-gray-800" id="dashCountPets">0</h3>
                                    <p class="text-xs text-gray-400">cadastrados</p>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center">
                                <div class="p-3 rounded-full bg-cyan-100 text-cyan-600 mr-4">
                                    <span class="material-icons text-3xl">account_circle</span>
                                </div>
                                <div>
                                    <p class="text-gray-500 text-xs font-medium uppercase tracking-wider">Perfil do Cliente</p>
                                    <h3 class="text-sm font-bold text-gray-800 truncate max-w-[150px]" id="dashNomeCliente"><?= htmlspecialchars($nome_cliente) ?></h3>
                                    <p class="text-xs text-cyan-600 font-medium">Cadastrado</p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Highlight: Próxima Fatura a Vencer -->
                    <div id="dashProximaFaturaCard" class="hidden mb-8 bg-gradient-to-r from-cyan-600 to-blue-700 text-white rounded-xl shadow-md p-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                        <div>
                            <span class="bg-white/20 text-white text-xs font-semibold px-3 py-1 rounded-full uppercase tracking-wider mb-2 inline-block">Próximo Vencimento</span>
                            <h3 class="text-2xl font-bold" id="dashProximaFaturaValor">R$ 0,00</h3>
                            <p class="text-sm text-cyan-100" id="dashProximaFaturaData">Vencimento: --/--/----</p>
                        </div>
                        <div>
                            <a id="dashProximaFaturaBtn" href="#" class="bg-white text-cyan-700 hover:bg-cyan-50 font-bold px-6 py-3 rounded-lg shadow transition inline-flex items-center gap-2">
                                <span class="material-icons">payment</span> Pagar Agora
                            </a>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                        
                        <!-- Próximos Agendamentos -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden flex flex-col">
                            <div class="p-5 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                                <div class="flex items-center gap-2">
                                    <span class="material-icons text-blue-600">calendar_month</span>
                                    <h3 class="font-bold text-gray-800">Próximos Agendamentos</h3>
                                </div>
                            </div>
                            <div class="p-4 flex-1">
                                <div id="dashListaAgendamentos" class="space-y-3">
                                    <p class="text-center text-gray-500 py-4 text-sm">Carregando agendamentos...</p>
                                </div>
                            </div>
                        </div>

                        <?php if ($is_vet): ?>
                            <!-- Atendimentos Recentes (Modo Vet) -->
                            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden flex flex-col">
                                <div class="p-5 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                                    <div class="flex items-center gap-2">
                                        <span class="material-icons text-teal-600">medical_services</span>
                                        <h3 class="font-bold text-gray-800">Atendimentos Clínicos Recentes</h3>
                                    </div>
                                </div>
                                <div class="p-4 flex-1">
                                    <div id="dashListaAtendimentos" class="space-y-3">
                                        <p class="text-center text-gray-500 py-4 text-sm">Carregando atendimentos...</p>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <!-- Faturas Recentes Summary (Modo Padrão) -->
                            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden flex flex-col">
                                <div class="p-5 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                                    <div class="flex items-center gap-2">
                                        <span class="material-icons text-cyan-600">receipt</span>
                                        <h3 class="font-bold text-gray-800">Últimas Faturas</h3>
                                    </div>
                                </div>
                                <div class="p-4 flex-1">
                                    <div id="dashListaFaturasBreve" class="space-y-3">
                                        <p class="text-center text-gray-500 py-4 text-sm">Carregando faturas...</p>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                    </div>

                    <?php if ($is_vet): ?>
                        <!-- Vacinas Próximas (Modo Vet) -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-8">
                            <div class="flex items-center gap-2 mb-4">
                                <span class="material-icons text-teal-600">vaccines</span>
                                <h3 class="font-bold text-gray-800 text-lg">Vacinação dos Pets</h3>
                            </div>
                            <div id="dashListaVacinasBreve" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                <p class="col-span-full text-center text-gray-500 py-4 text-sm">Carregando vacinas...</p>
                            </div>
                        </div>
                    <?php endif; ?>

                </div>

                <!-- 2. OPEN INVOICES TAB -->
                <div id="abertas" class="tab-content hidden">
                    <div id="listAbertas" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <p class="col-span-full text-center py-8 text-gray-500">Carregando faturas...</p>
                    </div>
                </div>

                <!-- 3. PAID INVOICES TAB -->
                <div id="pagas" class="tab-content hidden">
                    <div id="listPagas" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <p class="col-span-full text-center py-8 text-gray-500">Carregando histórico...</p>
                    </div>
                </div>

                <!-- 4. MEUS DADOS TAB -->
                <div id="meusdados" class="tab-content hidden max-w-4xl mx-auto">
                    <div class="bg-white p-6 md:p-8 rounded-xl shadow-sm border border-gray-100">
                        <div class="flex items-center gap-3 border-b pb-4 mb-6">
                            <span class="material-icons text-cyan-600 text-2xl">manage_accounts</span>
                            <div>
                                <h3 class="text-xl font-bold text-gray-800">Meus Dados Cadastrais</h3>
                                <p class="text-xs text-gray-500">Mantenha seus dados de contato e endereço atualizados.</p>
                            </div>
                        </div>

                        <form id="meusDadosForm" class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Nome / Razão Social</label>
                                    <input type="text" id="cliNome" readonly disabled class="w-full p-2.5 bg-gray-100 border border-gray-300 rounded text-sm text-gray-600">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">CPF / CNPJ</label>
                                    <input type="text" id="cliCpfCnpj" readonly disabled class="w-full p-2.5 bg-gray-100 border border-gray-300 rounded text-sm text-gray-600">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">E-mail</label>
                                    <input type="email" id="cliEmail" name="email" placeholder="seuemail@exemplo.com" required
                                        class="w-full p-2.5 border border-gray-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Telefone / WhatsApp</label>
                                    <input type="text" id="cliTelefone" name="telefone" placeholder="(00) 00000-0000"
                                        class="w-full p-2.5 border border-gray-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500">
                                </div>
                            </div>

                            <div class="border-t pt-4">
                                <h4 class="font-bold text-gray-700 text-sm mb-3">Endereço</h4>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-500 mb-1">CEP</label>
                                        <input type="text" id="cliCep" name="cep" placeholder="00000-000"
                                            class="w-full p-2 border border-gray-300 rounded text-sm">
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="block text-xs font-medium text-gray-500 mb-1">Endereço (Rua/Av)</label>
                                        <input type="text" id="cliEndereco" name="endereco" placeholder="Rua..."
                                            class="w-full p-2 border border-gray-300 rounded text-sm">
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-500 mb-1">Número</label>
                                        <input type="text" id="cliNumero" name="numero" placeholder="123"
                                            class="w-full p-2 border border-gray-300 rounded text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-500 mb-1">Complemento</label>
                                        <input type="text" id="cliComplemento" name="complemento" placeholder="Apto, Bloco..."
                                            class="w-full p-2 border border-gray-300 rounded text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-500 mb-1">Bairro</label>
                                        <input type="text" id="cliBairro" name="bairro" placeholder="Bairro"
                                            class="w-full p-2 border border-gray-300 rounded text-sm">
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-500 mb-1">UF</label>
                                        <input type="text" id="cliUf" name="uf" placeholder="SP" maxlength="2"
                                            class="w-full p-2 border border-gray-300 rounded text-sm uppercase">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-500 mb-1">Código Município (IBGE)</label>
                                        <input type="text" id="cliCodigoMunicipio" name="codigo_municipio" placeholder="3550308"
                                            class="w-full p-2 border border-gray-300 rounded text-sm">
                                    </div>
                                </div>
                            </div>

                            <!-- Google Calendar Integration Section (Rendered dynamically if Google integration exists) -->
                            <div id="containerGoogleCalendarConfig" class="hidden border-t pt-6">
                                <div class="flex items-center gap-2 mb-3">
                                    <span class="material-icons text-blue-600">event</span>
                                    <h4 class="font-bold text-gray-800 text-sm">Sincronização com Google Agenda</h4>
                                </div>

                                <div class="mb-4">
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">ID da sua Agenda Google (Opcional)</label>
                                    <input type="text" id="cliGoogleCalendarId" name="google_calendar_id" placeholder="seu_email@gmail.com ou ID da agenda"
                                        class="w-full p-2.5 border border-gray-300 rounded text-sm font-mono focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>

                                <!-- Card Tutorial -->
                                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-xs text-blue-900 space-y-2">
                                    <div class="font-bold flex items-center gap-1 text-blue-800 text-sm">
                                        <span class="material-icons text-base">help_outline</span> Como configurar seu Google Agenda:
                                    </div>
                                    <ol class="list-decimal list-inside space-y-1 text-blue-800">
                                        <li>Acesse o <a href="https://calendar.google.com" target="_blank" class="underline font-bold">Google Agenda</a> e abra as configurações da agenda desejada.</li>
                                        <li>Em <strong>"Compartilhar com pessoas específicas"</strong>, adicione a conta de serviço do sistema:</li>
                                    </ol>
                                    <div class="my-2">
                                        <code id="googleServiceEmailHintText" class="select-all bg-white px-2.5 py-1.5 rounded border border-blue-300 text-xs font-mono text-blue-900 font-semibold block w-fit shadow-sm">
                                            --
                                        </code>
                                    </div>
                                    <p class="text-blue-800">
                                        3. Conceda a permissão <strong>"Fazer alterações em eventos"</strong> (ou "Ver todos os detalhes dos eventos").
                                    </p>
                                    <p class="text-blue-800">
                                        4. Cole no campo acima o e-mail/ID da sua agenda. Assim, todos os agendamentos e consultas marcadas para você serão automaticamente sincronizados no seu Google Calendar!
                                    </p>
                                </div>
                            </div>

                            <div class="pt-4 flex justify-end">
                                <button type="submit" id="btnSalvarDados"
                                    class="bg-cyan-600 hover:bg-cyan-700 text-white font-bold py-3 px-8 rounded-lg shadow transition flex items-center gap-2">
                                    <span class="material-icons">save</span> Salvar Meus Dados
                                </button>
                            </div>
                            <div id="meusDadosMsg" class="text-right text-sm font-semibold mt-2"></div>
                        </form>
                    </div>
                </div>

                <?php if ($is_vet): ?>
                    <!-- 5. CARTEIRA DE VACINAÇÃO TAB (Modo Vet) -->
                    <div id="vacinas" class="tab-content hidden">
                        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                            <div>
                                <h3 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                                    <span class="material-icons text-teal-600">health_and_safety</span> Carteira Virtual de Vacinação
                                </h3>
                                <p class="text-sm text-gray-500">Acompanhe o histórico de vacinas e imunização dos seus pets.</p>
                            </div>
                        </div>

                        <div id="listaCarteiraVacinasFull" class="space-y-6">
                            <p class="text-center text-gray-500 py-8">Carregando carteira de vacinação...</p>
                        </div>
                    </div>
                <?php endif; ?>

            </div>
        <?php endif; ?>

    </main>

    <script>
        $(document).ready(function () {
            let globalDashboardData = null;

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

                btn.prop('disabled', true).html('<span class="material-icons animate-spin text-sm">sync</span> Verificando...');

                $.ajax({
                    url: '../dinovatech/app.php', type: 'POST', dataType: 'json',
                    data: { action: 'validar_cpf_cnpj', cpf_cnpj: cpfCnpj },
                    success: function (response) {
                        if (response.success) {
                            if (rememberMe) localStorage.setItem('dinovatech_cpf_cnpj', cpfCnpj);
                            else localStorage.removeItem('dinovatech_cpf_cnpj');
                            window.location.reload();
                        } else {
                            $('#loginMessage').removeClass('text-green-600').addClass('text-red-600').text(response.message);
                            btn.prop('disabled', false).html('<span class="material-icons text-sm">login</span> Entrar');
                        }
                    },
                    error: function () {
                        alert('Erro ao conectar.');
                        btn.prop('disabled', false).html('<span class="material-icons text-sm">login</span> Entrar');
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

            // Save Meus Dados
            $('#meusDadosForm').on('submit', function (e) {
                e.preventDefault();
                const btn = $('#btnSalvarDados');
                btn.prop('disabled', true).html('<span class="material-icons animate-spin text-sm">sync</span> Salvando...');

                const formData = $(this).serializeArray();
                formData.push({ name: 'action', value: 'atualizar_dados_cliente' });

                $.ajax({
                    url: '../dinovatech/app.php',
                    type: 'POST',
                    dataType: 'json',
                    data: formData,
                    success: function (response) {
                        if (response.success) {
                            $('#meusDadosMsg').removeClass('text-red-600').addClass('text-green-600').text(response.message);
                            setTimeout(() => $('#meusDadosMsg').text(''), 4000);
                        } else {
                            $('#meusDadosMsg').removeClass('text-green-600').addClass('text-red-600').text(response.message);
                        }
                        btn.prop('disabled', false).html('<span class="material-icons">save</span> Salvar Meus Dados');
                    },
                    error: function () {
                        alert('Erro ao atualizar dados.');
                        btn.prop('disabled', false).html('<span class="material-icons">save</span> Salvar Meus Dados');
                    }
                });
            });

            // Load Full Dashboard Data if logged in
            <?php if ($cliente_logado): ?>
                loadClienteDashboard();
            <?php endif; ?>

            function loadClienteDashboard() {
                $.ajax({
                    url: '../dinovatech/app.php',
                    type: 'POST',
                    dataType: 'json',
                    data: { action: 'get_cliente_dashboard_data' },
                    success: function (response) {
                        if (response.success && response.data) {
                            globalDashboardData = response.data;
                            renderDashboard(response.data);
                            renderFaturasTabs(response.data.faturas || []);
                            populateMeusDados(response.data.cliente || {}, response.data.google_service_email_hint || '');
                            if (response.data.is_vet_mode) {
                                renderCarteiraVacinasFull(response.data.vacinas || [], response.data.pets || []);
                            }
                        }
                    },
                    error: function (err) {
                        console.error("Erro ao carregar dados do cliente:", err);
                    }
                });
            }

            function renderDashboard(data) {
                const summary = data.faturas_summary || {};
                
                // KPIs
                $('#dashTotalAberto').text(formatCurrency(summary.total_aberto || 0));
                $('#dashCountAberto').text(`${summary.count_aberto || 0} pendentes`);

                $('#dashTotalPago').text(formatCurrency(summary.total_pago || 0));
                $('#dashCountPago').text(`${summary.count_pago || 0} liquidadas`);

                $('#dashCountAgendamentos').text(data.agendamentos ? data.agendamentos.length : 0);
                if (data.pets) $('#dashCountPets').text(data.pets.length);

                // Highlight Card - Próxima Fatura
                if (summary.proxima_pendente) {
                    const prox = summary.proxima_pendente;
                    $('#dashProximaFaturaValor').text(formatCurrency(prox.valor_total_fatura));
                    $('#dashProximaFaturaData').text(`Vencimento: ${formatDate(prox.data_vencimento)}`);
                    $('#dashProximaFaturaBtn').attr('href', `fatura.php?id=${prox.id_fatura}`);
                    $('#dashProximaFaturaCard').removeClass('hidden');
                } else {
                    $('#dashProximaFaturaCard').addClass('hidden');
                }

                // Lista de Agendamentos
                renderAgendamentosList(data.agendamentos || []);

                // Atendimentos ou Faturas Breve
                if (data.is_vet_mode) {
                    renderAtendimentosList(data.atendimentos || []);
                    renderVacinasBreveList(data.vacinas || []);
                } else {
                    renderFaturasBreveList(data.faturas || []);
                }
            }

            function renderAgendamentosList(agendamentos) {
                const container = $('#dashListaAgendamentos');
                container.empty();

                if (agendamentos.length === 0) {
                    container.html('<p class="text-center text-gray-500 py-4 text-sm italic">Nenhum agendamento encontrado.</p>');
                    return;
                }

                agendamentos.forEach(ag => {
                    const dataFormatada = formatDateTime(ag.data_inicio);
                    const statusClass = ag.status === 'Realizado' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800';
                    const petInfo = ag.pet_nome ? `<span class="text-teal-700 font-semibold">• ${escapeHtml(ag.pet_nome)}</span>` : '';
                    const vetInfo = ag.vet_nome ? `• Vet: ${escapeHtml(ag.vet_nome)}` : '';

                    container.append(`
                        <div class="p-3 bg-gray-50 rounded-lg border border-gray-100 flex justify-between items-center text-sm">
                            <div>
                                <div class="font-bold text-gray-800">${escapeHtml(ag.titulo || 'Consulta')} ${petInfo}</div>
                                <div class="text-xs text-gray-500 mt-0.5">${dataFormatada} ${vetInfo}</div>
                            </div>
                            <span class="px-2.5 py-1 rounded-full text-xs font-bold ${statusClass}">${escapeHtml(ag.status)}</span>
                        </div>
                    `);
                });
            }

            function renderAtendimentosList(atendimentos) {
                const container = $('#dashListaAtendimentos');
                container.empty();

                if (atendimentos.length === 0) {
                    container.html('<p class="text-center text-gray-500 py-4 text-sm italic">Nenhum atendimento clínico registrado.</p>');
                    return;
                }

                atendimentos.forEach(at => {
                    const dataFormatada = formatDateTime(at.data_atendimento);
                    const queixa = at.queixa_principal ? escapeHtml(at.queixa_principal) : 'Consulta de rotina';

                    container.append(`
                        <div class="p-3 bg-teal-50/40 rounded-lg border border-teal-100 text-sm">
                            <div class="flex justify-between items-start mb-1">
                                <span class="font-bold text-teal-800">${escapeHtml(at.pet_nome || 'Pet')}</span>
                                <span class="text-xs text-gray-500">${dataFormatada}</span>
                            </div>
                            <div class="text-xs text-gray-600 truncate"><strong>Motivo:</strong> ${queixa}</div>
                            ${at.vet_nome ? `<div class="text-xs text-gray-500 mt-1"><strong>Vet:</strong> ${escapeHtml(at.vet_nome)}</div>` : ''}
                        </div>
                    `);
                });
            }

            function renderVacinasBreveList(vacinas) {
                const container = $('#dashListaVacinasBreve');
                container.empty();

                if (vacinas.length === 0) {
                    container.html('<p class="col-span-full text-center text-gray-500 py-4 text-sm italic">Nenhuma vacina registrada.</p>');
                    return;
                }

                const hoje = new Date().toISOString().split('T')[0];

                vacinas.slice(0, 6).forEach(vc => {
                    const isVencida = vc.data_vencimento < hoje;
                    const statusClass = isVencida ? 'border-red-200 bg-red-50 text-red-800' : 'border-green-200 bg-green-50 text-green-800';

                    container.append(`
                        <div class="p-3 rounded-lg border ${statusClass} text-xs">
                            <div class="font-bold text-sm mb-1">${escapeHtml(vc.pet_nome)}</div>
                            <div>Vacina: <strong>${escapeHtml(vc.vacina_nome)}</strong></div>
                            <div class="mt-1">Vencimento: <strong>${formatDate(vc.data_vencimento)}</strong></div>
                        </div>
                    `);
                });
            }

            function renderFaturasBreveList(faturas) {
                const container = $('#dashListaFaturasBreve');
                container.empty();

                if (faturas.length === 0) {
                    container.html('<p class="text-center text-gray-500 py-4 text-sm italic">Nenhuma fatura cadastrada.</p>');
                    return;
                }

                faturas.slice(0, 5).forEach(f => {
                    const isLiq = f.status === 'Liquidada';
                    const statusClass = isLiq ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700';

                    container.append(`
                        <div class="p-3 bg-gray-50 rounded-lg border border-gray-100 flex justify-between items-center text-sm">
                            <div>
                                <div class="font-bold text-gray-800">#${f.id_fatura} - ${formatCurrency(f.valor_total_fatura)}</div>
                                <div class="text-xs text-gray-500">Vencimento: ${formatDate(f.data_vencimento)}</div>
                            </div>
                            <a href="fatura.php?id=${f.id_fatura}" class="px-3 py-1 bg-white border rounded text-xs font-semibold hover:bg-gray-100 text-gray-700">Ver Fatura</a>
                        </div>
                    `);
                });
            }

            function populateMeusDados(cliente, googleHintEmail) {
                $('#cliNome').val(cliente.nome || '');
                $('#cliCpfCnpj').val(cliente.cpf_cnpj || '');
                $('#cliEmail').val(cliente.email || '');
                $('#cliTelefone').val(cliente.telefone || '');
                $('#cliEndereco').val(cliente.endereco || '');
                $('#cliNumero').val(cliente.numero || '');
                $('#cliComplemento').val(cliente.complemento || '');
                $('#cliBairro').val(cliente.bairro || '');
                $('#cliCep').val(cliente.cep || '');
                $('#cliUf').val(cliente.uf || '');
                $('#cliCodigoMunicipio').val(cliente.codigo_municipio || '');
                $('#cliGoogleCalendarId').val(cliente.google_calendar_id || '');

                if (googleHintEmail) {
                    $('#googleServiceEmailHintText').text(googleHintEmail);
                    $('#containerGoogleCalendarConfig').removeClass('hidden');
                } else {
                    $('#containerGoogleCalendarConfig').addClass('hidden');
                }
            }

            function renderFaturasTabs(faturas) {
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

                    const total = formatCurrency(fatura.valor_total_fatura);
                    const vencimento = formatDate(fatura.data_vencimento);

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

            function renderCarteiraVacinasFull(vacinas, pets) {
                const container = $('#listaCarteiraVacinasFull');
                if (!container.length) return;
                container.empty();

                if (!pets || pets.length === 0) {
                    container.html('<p class="text-center text-gray-500 py-8">Nenhum pet cadastrado.</p>');
                    return;
                }

                pets.forEach(pet => {
                    const petVacinas = vacinas.filter(v => v.id_pet == pet.id_pet);

                    let vacinasRows = '';
                    if (petVacinas.length > 0) {
                        petVacinas.forEach(vc => {
                            const hoje = new Date().toISOString().split('T')[0];
                            const isVencida = vc.data_vencimento < hoje;
                            const statusBadge = isVencida 
                                ? '<span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-red-100 text-red-800">Vencida</span>'
                                : '<span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-green-100 text-green-800">Em dia</span>';

                            vacinasRows += `
                                <tr class="border-b border-gray-50 hover:bg-gray-50 transition">
                                    <td class="p-3 font-semibold text-gray-800">${escapeHtml(vc.vacina_nome)}</td>
                                    <td class="p-3 text-xs text-gray-600">${formatDate(vc.data_aplicacao)}</td>
                                    <td class="p-3 text-xs font-medium text-gray-800">${formatDate(vc.data_vencimento)}</td>
                                    <td class="p-3 text-right">${statusBadge}</td>
                                </tr>
                            `;
                        });
                    } else {
                        vacinasRows = '<tr><td colspan="4" class="p-4 text-center text-gray-400 text-xs italic">Nenhuma vacina registrada para este pet.</td></tr>';
                    }

                    container.append(`
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                            <div class="p-4 bg-teal-50/50 border-b border-teal-100 flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-teal-100 text-teal-700 rounded-full">
                                        <span class="material-icons text-xl">pets</span>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-gray-800 text-lg">${escapeHtml(pet.nome)}</h4>
                                        <span class="text-xs text-gray-500">${escapeHtml(pet.especie || 'Pet')} ${pet.raca ? '• ' + escapeHtml(pet.raca) : ''}</span>
                                    </div>
                                </div>
                                <span class="text-xs text-teal-800 font-semibold bg-teal-100 px-3 py-1 rounded-full">${petVacinas.length} Vacinas</span>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full text-left text-sm">
                                    <thead>
                                        <tr class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                                            <th class="p-3">Vacina</th>
                                            <th class="p-3">Data Aplicação</th>
                                            <th class="p-3">Próximo Vencimento</th>
                                            <th class="p-3 text-right">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${vacinasRows}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    `);
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

            function formatDateTime(dtStr) {
                if (!dtStr) return '-';
                const parts = dtStr.split(' ');
                const datePart = parts[0] ? formatDate(parts[0]) : '-';
                const timePart = parts[1] ? parts[1].substring(0, 5) : '';
                return timePart ? `${datePart} às ${timePart}` : datePart;
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
        });
    </script>
</body>

</html>