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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between cursor-pointer hover:shadow-md hover:border-teal-300 transition group" onclick="abrirModalMeusPets()">
                                <div class="flex items-center">
                                    <div class="p-3 rounded-full bg-teal-100 text-teal-600 mr-4 group-hover:bg-teal-600 group-hover:text-white transition">
                                        <span class="material-icons text-3xl">pets</span>
                                    </div>
                                    <div>
                                        <p class="text-gray-500 text-xs font-medium uppercase tracking-wider">Meus Pets</p>
                                        <h3 class="text-xl font-bold text-gray-800" id="dashCountPets">0</h3>
                                        <p class="text-xs text-teal-600 font-semibold flex items-center gap-0.5 mt-0.5">Estatísticas & Peso <span class="material-icons text-xs">chevron_right</span></p>
                                    </div>
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

                    <!-- Assinaturas & Contratos (Seção na Dashboard) -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-8">
                        <div class="flex items-center justify-between mb-4 border-b border-gray-100 pb-3">
                            <div class="flex items-center gap-2">
                                <span class="material-icons text-purple-600">auto_renew</span>
                                <h3 class="font-bold text-gray-800 text-lg">Minhas Assinaturas & Contratos</h3>
                            </div>
                            <span class="text-xs text-gray-500 font-semibold" id="dashCountRecorrenciasTag">0 contratos</span>
                        </div>
                        <div id="dashListaRecorrencias" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <p class="col-span-full text-center text-gray-500 py-4 text-sm">Carregando assinaturas...</p>
                        </div>
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
                        </form>
                    </div>

                    <!-- Bloco de Assinaturas e Contratos -->
                    <div class="mt-8 bg-white p-6 md:p-8 rounded-xl shadow-sm border border-gray-100">
                        <div class="flex items-center gap-3 border-b pb-4 mb-6">
                            <span class="material-icons text-purple-600 text-2xl">history_edu</span>
                            <div>
                                <h3 class="text-xl font-bold text-gray-800">Minhas Assinaturas & Termos Contratuais</h3>
                                <p class="text-xs text-gray-500">Consulte suas assinaturas ativas e visualize os documentos/termos vinculados.</p>
                            </div>
                        </div>

                        <div id="listaRecorrenciasFull" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <p class="col-span-full text-center text-gray-500 py-8">Carregando assinaturas...</p>
                        </div>
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

    <!-- Modal Detalhes do Atendimento Clínico -->
    <div id="modalAtendimentoDetalhes" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <!-- Backdrop -->
        <div id="modalAtendimentoBackdrop" class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity"></div>

        <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
            <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-3xl border border-gray-100 w-full">
                
                <!-- Modal Header -->
                <div class="bg-gradient-to-r from-teal-700 to-cyan-800 p-6 text-white flex justify-between items-start">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <span class="material-icons text-teal-200">medical_services</span>
                            <span class="text-xs uppercase tracking-wider font-semibold text-teal-200" id="mAtendData">--/--/----</span>
                        </div>
                        <h3 class="text-2xl font-bold" id="mAtendPetNome">Atendimento Clínico</h3>
                        <p class="text-xs text-teal-100 mt-1" id="mAtendPetDetalhes">--</p>
                    </div>
                    <button type="button" id="btnCloseModalAtendimento" class="text-white/80 hover:text-white bg-white/10 hover:bg-white/20 p-2 rounded-full transition">
                        <span class="material-icons">close</span>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-6 space-y-6 max-h-[75vh] overflow-y-auto">

                    <!-- Info Vet -->
                    <div class="bg-teal-50/50 p-4 rounded-xl border border-teal-100 flex flex-col sm:flex-row justify-between sm:items-center text-sm gap-2">
                        <div>
                            <span class="text-xs text-gray-500 font-medium uppercase block">Veterinário Responsável</span>
                            <strong class="text-teal-900 text-base" id="mAtendVetNome">--</strong>
                        </div>
                        <div class="text-xs text-teal-800 bg-teal-100 px-3 py-1.5 rounded-lg font-semibold w-fit" id="mAtendVetCrmv">
                            CRMV: --
                        </div>
                    </div>

                    <!-- Tabs de Conteúdo do Atendimento -->
                    <div class="border-b border-gray-200 flex gap-4 text-sm font-semibold">
                        <button type="button" class="m-tab-btn active border-b-2 border-teal-600 text-teal-700 pb-2 flex items-center gap-1" data-mtarget="m-prontuario">
                            <span class="material-icons text-base">assignment</span> Prontuário Clínico
                        </button>
                        <button type="button" class="m-tab-btn text-gray-500 hover:text-gray-700 pb-2 flex items-center gap-1" data-mtarget="m-receitas">
                            <span class="material-icons text-base">receipt</span> Receitas (<span id="mCountReceitas">0</span>)
                        </button>
                        <button type="button" class="m-tab-btn text-gray-500 hover:text-gray-700 pb-2 flex items-center gap-1" data-mtarget="m-anexos">
                            <span class="material-icons text-base">attach_file</span> Exames & Anexos (<span id="mCountAnexos">0</span>)
                        </button>
                    </div>

                    <!-- M-TAB 1: Prontuário -->
                    <div id="m-prontuario" class="m-tab-content space-y-4">
                        <div class="bg-gray-50 p-4 rounded-lg border border-gray-100">
                            <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Motivo / Queixa Principal</h4>
                            <p class="text-sm text-gray-800 whitespace-pre-line" id="mAtendQueixa">--</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="bg-gray-50 p-4 rounded-lg border border-gray-100">
                                <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Anamnese / Histórico</h4>
                                <p class="text-sm text-gray-800 whitespace-pre-line" id="mAtendAnamnese">--</p>
                            </div>
                            <div class="bg-gray-50 p-4 rounded-lg border border-gray-100">
                                <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Exame Físico</h4>
                                <p class="text-sm text-gray-800 whitespace-pre-line" id="mAtendExameFisico">--</p>
                            </div>
                        </div>

                        <div class="bg-amber-50/60 p-4 rounded-lg border border-amber-200">
                            <h4 class="text-xs font-bold text-amber-900 uppercase tracking-wider mb-1">Diagnóstico</h4>
                            <p class="text-sm text-amber-950 font-medium whitespace-pre-line" id="mAtendDiagnostico">--</p>
                        </div>

                        <div class="bg-emerald-50/60 p-4 rounded-lg border border-emerald-200">
                            <h4 class="text-xs font-bold text-emerald-900 uppercase tracking-wider mb-1">Conduta & Tratamento Recomendado</h4>
                            <p class="text-sm text-emerald-950 whitespace-pre-line" id="mAtendTratamento">--</p>
                        </div>
                    </div>

                    <!-- M-TAB 2: Receitas Emitidas -->
                    <div id="m-receitas" class="m-tab-content hidden space-y-4">
                        <div id="mListaReceitas" class="space-y-4">
                            <p class="text-center text-gray-500 py-4 text-sm">Carregando receitas...</p>
                        </div>
                    </div>

                    <!-- M-TAB 3: Anexos e Exames -->
                    <div id="m-anexos" class="m-tab-content hidden space-y-4">
                        <div id="mListaAnexos" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <p class="col-span-full text-center text-gray-500 py-4 text-sm">Carregando anexos...</p>
                        </div>
                    </div>

                </div>

                <!-- Modal Footer -->
                <div class="bg-gray-50 px-6 py-4 border-t border-gray-100 flex justify-between items-center">
                    <span class="text-xs text-gray-400" id="mAtendIdTag">ID Atendimento: #--</span>
                    <button type="button" id="btnFecharModalBottom" class="px-5 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg text-sm font-semibold transition">
                        Fechar
                    </button>
                </div>

            </div>
        </div>
    </div>

    <!-- Modal Meus Pets - Saúde e Estatísticas -->
    <div id="modalMeusPetsDetalhes" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <!-- Backdrop -->
        <div id="modalMeusPetsBackdrop" class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity"></div>

        <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
            <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-4xl border border-gray-100 w-full">
                
                <!-- Modal Header -->
                <div class="bg-gradient-to-r from-teal-800 to-cyan-900 p-6 text-white flex justify-between items-center">
                    <div class="flex items-center gap-3">
                        <div class="p-2.5 bg-white/10 rounded-xl">
                            <span class="material-icons text-3xl text-teal-200">pets</span>
                        </div>
                        <div>
                            <h3 class="text-2xl font-bold">Meus Pets — Saúde & Estatísticas</h3>
                            <p class="text-xs text-teal-100">Acompanhe a evolução do peso e o histórico médico dos seus pets.</p>
                        </div>
                    </div>
                    <button type="button" id="btnCloseModalPets" class="text-white/80 hover:text-white bg-white/10 hover:bg-white/20 p-2 rounded-full transition">
                        <span class="material-icons">close</span>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-6 space-y-6 max-h-[80vh] overflow-y-auto bg-gray-50/50">
                    <div id="mListaMeusPetsCards" class="space-y-6">
                        <p class="text-center text-gray-500 py-8 text-sm">Carregando pets...</p>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="bg-white px-6 py-4 border-t border-gray-100 flex justify-end">
                    <button type="button" id="btnFecharModalPetsBottom" class="px-5 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg text-sm font-semibold transition">
                        Fechar
                    </button>
                </div>

            </div>
        </div>
    </div>

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

                // Lista de Assinaturas e Contratos
                renderRecorrenciasList(data.recorrencias || []);

                // Atendimentos ou Faturas Breve
                if (data.is_vet_mode) {
                    renderAtendimentosList(data.atendimentos || []);
                    renderVacinasBreveList(data.vacinas || []);
                } else {
                    renderFaturasBreveList(data.faturas || []);
                }
            }

            function renderRecorrenciasList(recorrencias) {
                const dashContainer = $('#dashListaRecorrencias');
                const fullContainer = $('#listaRecorrenciasFull');
                dashContainer.empty();
                if (fullContainer.length) fullContainer.empty();

                $('#dashCountRecorrenciasTag').text(`${recorrencias.length} contrato(s)`);

                if (recorrencias.length === 0) {
                    const emptyMsg = '<p class="col-span-full text-center text-gray-500 py-6 text-sm italic">Nenhuma assinatura ou contrato ativo encontrado.</p>';
                    dashContainer.html(emptyMsg);
                    if (fullContainer.length) fullContainer.html(emptyMsg);
                    return;
                }

                let htmlContent = '';

                recorrencias.forEach(rec => {
                    const servicoNome = escapeHtml(rec.nome_servico || rec.descricao_personalizada || 'Assinatura');
                    const valorTotal = (parseFloat(rec.valor_sugerido_recorrencia || 0) * (parseInt(rec.quantidade) || 1));
                    const valorFormatado = formatCurrency(valorTotal);
                    const periodo = escapeHtml(rec.tipo_periodo || 'Mês').toLowerCase();
                    const dataInicio = formatDate(rec.data_inicio_cobranca);
                    const hojeStr = new Date().toISOString().split('T')[0];
                    const isExpirado = rec.data_fim_cobranca && rec.data_fim_cobranca < hojeStr;
                    const isCancelado = rec.status && (rec.status.toLowerCase() === 'cancelada' || rec.status.toLowerCase() === 'cancelado' || rec.status.toLowerCase() === 'inativa');

                    let statusLabel = 'Ativa';
                    let statusClass = 'bg-green-100 text-green-800';

                    if (isCancelado) {
                        statusLabel = 'Cancelado';
                        statusClass = 'bg-gray-100 text-gray-700';
                    } else if (isExpirado) {
                        statusLabel = 'Vencido';
                        statusClass = 'bg-red-100 text-red-800';
                    }

                    // Documentos vinculados
                    const documentos = rec.documentos || [];
                    let docsHtml = '';

                    if (documentos.length > 0) {
                        docsHtml += '<div class="space-y-2 mt-3 pt-3 border-t border-gray-100">';
                        docsHtml += '<span class="text-xs font-bold text-gray-600 uppercase tracking-wider block mb-1">Documentos & Termos:</span>';
                        documentos.forEach(doc => {
                            docsHtml += `
                                <div class="p-2.5 bg-purple-50/60 rounded-lg border border-purple-100 flex items-center justify-between text-xs">
                                    <div class="flex items-center gap-2 truncate mr-2">
                                        <span class="material-icons text-purple-700 text-base">description</span>
                                        <span class="font-semibold text-purple-950 truncate">${escapeHtml(doc.titulo)}</span>
                                    </div>
                                    <a href="../dinovatech/modules/Vet/documento_view.php?id=${doc.id_documento_emitido}" target="_blank" class="px-2.5 py-1 bg-white border border-purple-200 rounded hover:bg-purple-100 text-purple-800 font-bold transition shrink-0 flex items-center gap-1 shadow-sm">
                                        <span class="material-icons text-xs">visibility</span> Visualizar
                                    </a>
                                </div>
                            `;
                        });
                        docsHtml += '</div>';
                    } else {
                        docsHtml = '<div class="mt-3 pt-2 border-t border-gray-100 text-xs text-gray-400 italic">Nenhum documento/termo anexado a este contrato.</div>';
                    }

                    const cardHtml = `
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition p-5 flex flex-col justify-between">
                            <div>
                                <div class="flex justify-between items-start mb-2">
                                    <div class="flex items-center gap-2">
                                        <div class="p-2 rounded-lg ${isExpirado ? 'bg-red-100 text-red-700' : 'bg-purple-100 text-purple-700'}">
                                            <span class="material-icons">auto_renew</span>
                                        </div>
                                        <div>
                                            <h4 class="font-bold text-gray-800 text-base">${servicoNome}</h4>
                                            <span class="text-xs text-gray-400">Contrato #${rec.id_recorrencia}</span>
                                        </div>
                                    </div>
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold ${statusClass}">${statusLabel}</span>
                                </div>
                                <div class="my-3">
                                    <div class="text-2xl font-bold text-gray-800">${valorFormatado} <span class="text-xs font-normal text-gray-500">/ ${periodo}</span></div>
                                    <div class="text-xs text-gray-500 mt-1">Início: <strong>${dataInicio}</strong> • Fim: <strong class="${isExpirado ? 'text-red-600 font-bold' : ''}">${dataFim}</strong></div>
                                </div>
                                ${docsHtml}
                            </div>
                        </div>
                    `;

                    htmlContent += cardHtml;
                });

                dashContainer.html(htmlContent);
                if (fullContainer.length) fullContainer.html(htmlContent);
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
                        <div class="p-3.5 bg-teal-50/50 hover:bg-teal-100/60 rounded-xl border border-teal-100 text-sm cursor-pointer transition shadow-sm hover:shadow" onclick="abrirModalAtendimento(${at.id_atendimento})">
                            <div class="flex justify-between items-start mb-1">
                                <span class="font-bold text-teal-900 flex items-center gap-1">
                                    <span class="material-icons text-xs text-teal-600">pets</span> ${escapeHtml(at.pet_nome || 'Pet')}
                                </span>
                                <span class="text-xs text-gray-500 font-medium">${dataFormatada}</span>
                            </div>
                            <div class="text-xs text-gray-700 truncate"><strong>Motivo:</strong> ${queixa}</div>
                            <div class="flex justify-between items-center mt-2 pt-2 border-t border-teal-100 text-xs">
                                <span class="text-gray-500">${at.vet_nome ? 'Vet: ' + escapeHtml(at.vet_nome) : ''}</span>
                                <span class="text-teal-700 font-semibold flex items-center hover:underline">Ver Prontuário Completo <span class="material-icons text-xs">chevron_right</span></span>
                            </div>
                        </div>
                    `);
                });
            }

            window.abrirModalAtendimento = function(idAtendimento) {
                $('#mAtendPetNome').text('Carregando...');
                $('#mAtendPetDetalhes').text('');
                $('#mAtendVetNome').text('Carregando...');
                $('#mAtendVetCrmv').text('CRMV: --');
                $('#mAtendQueixa').text('Carregando...');
                $('#mAtendAnamnese').text('Carregando...');
                $('#mAtendExameFisico').text('Carregando...');
                $('#mAtendDiagnostico').text('Carregando...');
                $('#mAtendTratamento').text('Carregando...');
                $('#mListaReceitas').html('<p class="text-center text-gray-500 py-4 text-sm">Carregando receitas...</p>');
                $('#mListaAnexos').html('<p class="col-span-full text-center text-gray-500 py-4 text-sm">Carregando anexos...</p>');
                
                // Reset tab active
                $('.m-tab-btn').removeClass('border-b-2 border-teal-600 text-teal-700 active').addClass('text-gray-500 hover:text-gray-700');
                $('.m-tab-btn[data-mtarget="m-prontuario"]').addClass('border-b-2 border-teal-600 text-teal-700 active').removeClass('text-gray-500 hover:text-gray-700');
                $('.m-tab-content').addClass('hidden');
                $('#m-prontuario').removeClass('hidden');

                $('#modalAtendimentoDetalhes').removeClass('hidden');

                $.ajax({
                    url: '../dinovatech/app.php',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'get_atendimento_detalhes_cliente',
                        id_atendimento: idAtendimento
                    },
                    success: function(response) {
                        if (response.success && response.data) {
                            const at = response.data.atendimento;
                            const arquivos = response.data.arquivos || [];
                            const receitas = response.data.receitas || [];

                            $('#mAtendIdTag').text(`ID Atendimento: #${at.id_atendimento}`);
                            $('#mAtendData').text(formatDateTime(at.data_atendimento));
                            $('#mAtendPetNome').text(at.pet_nome || 'Pet');
                            $('#mAtendPetDetalhes').text(`${at.pet_especie || ''} ${at.pet_raca ? '• ' + at.pet_raca : ''} ${at.pet_peso ? '• ' + at.pet_peso + ' kg' : ''}`);
                            
                            $('#mAtendVetNome').text(at.vet_nome || 'Veterinário Não Especificado');
                            $('#mAtendVetCrmv').text(`CRMV: ${at.vet_crmv || 'N/A'}${at.vet_uf_crmv ? '/' + at.vet_uf_crmv : ''}`);

                            $('#mAtendQueixa').text(at.queixa_principal || 'Não informada');
                            $('#mAtendAnamnese').text(at.anamnese || 'Não informada');
                            $('#mAtendExameFisico').text(at.exame_fisico || 'Não informado');
                            $('#mAtendDiagnostico').text(at.diagnostico || 'Sem diagnóstico registrado');
                            $('#mAtendTratamento').text(at.conduta_tratamento || 'Sem conduta médica registrada');

                            $('#mCountReceitas').text(receitas.length);
                            $('#mCountAnexos').text(arquivos.length);

                            // Render Receitas
                            let rHtml = '';
                            if (receitas.length > 0) {
                                receitas.forEach(rec => {
                                    let itensHtml = '';
                                    if (rec.itens && rec.itens.length > 0) {
                                        rec.itens.forEach(it => {
                                            itensHtml += `
                                                <div class="p-2.5 bg-white rounded border border-gray-200 text-xs mb-2">
                                                    <div class="font-bold text-gray-800">${escapeHtml(it.nome_medicamento || 'Medicamento')}</div>
                                                    <div class="text-gray-600 mt-0.5"><strong>Posologia:</strong> ${escapeHtml(it.posologia || 'Não informada')}</div>
                                                </div>
                                            `;
                                        });
                                    } else {
                                        itensHtml = '<p class="text-xs text-gray-400 italic">Sem itens na receita.</p>';
                                    }

                                    rHtml += `
                                        <div class="bg-gray-50 p-4 rounded-xl border border-gray-200">
                                            <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-2 mb-3">
                                                <div>
                                                    <span class="text-xs text-gray-500 font-semibold uppercase">Receita #${rec.id_receita}</span>
                                                    <div class="text-xs text-gray-400">${formatDateTime(rec.data_receita)}</div>
                                                </div>
                                                <a href="../dinovatech/modules/Vet/receita_print.php?id=${rec.id_receita}" target="_blank" class="px-3 py-1.5 bg-cyan-600 hover:bg-cyan-700 text-white rounded text-xs font-bold transition flex items-center gap-1 shadow-sm w-fit">
                                                    <span class="material-icons text-xs">print</span> Imprimir / Visualizar Receita
                                                </a>
                                            </div>
                                            ${rec.observacoes ? `<div class="text-xs text-gray-600 mb-3 bg-white p-2 rounded border border-gray-100"><strong>Observações:</strong> ${escapeHtml(rec.observacoes)}</div>` : ''}
                                            <div class="space-y-1">${itensHtml}</div>
                                        </div>
                                    `;
                                });
                            } else {
                                rHtml = '<p class="text-center text-gray-500 py-6 text-sm italic">Nenhuma receita prescrita neste atendimento.</p>';
                            }
                            $('#mListaReceitas').html(rHtml);

                            // Render Anexos
                            let aHtml = '';
                            if (arquivos.length > 0) {
                                arquivos.forEach(arq => {
                                    aHtml += `
                                        <div class="p-3 bg-gray-50 rounded-lg border border-gray-200 flex items-center justify-between text-xs">
                                            <div class="flex items-center gap-2 truncate mr-2">
                                                <span class="material-icons text-cyan-600 text-base">insert_drive_file</span>
                                                <span class="font-medium text-gray-800 truncate" title="${escapeHtml(arq.nome_original)}">${escapeHtml(arq.nome_original)}</span>
                                            </div>
                                            <a href="${arq.url_publica}" target="_blank" class="px-2.5 py-1 bg-white border border-gray-300 rounded hover:bg-gray-100 text-gray-700 font-medium flex items-center gap-1 transition shrink-0">
                                                <span class="material-icons text-xs">open_in_new</span> Abrir
                                            </a>
                                        </div>
                                    `;
                                });
                            } else {
                                aHtml = '<p class="col-span-full text-center text-gray-500 py-6 text-sm italic">Nenhum exame ou arquivo anexado.</p>';
                            }
                            $('#mListaAnexos').html(aHtml);

                        } else {
                            alert(response.message || 'Erro ao carregar detalhes.');
                            $('#modalAtendimentoDetalhes').addClass('hidden');
                        }
                    },
                    error: function() {
                        alert('Erro ao conectar ao servidor.');
                        $('#modalAtendimentoDetalhes').addClass('hidden');
                    }
                });
            };

            // Modal Tabs & Close Handlers
            $(document).on('click', '.m-tab-btn', function() {
                $('.m-tab-btn').removeClass('border-b-2 border-teal-600 text-teal-700 active').addClass('text-gray-500 hover:text-gray-700');
                $(this).removeClass('text-gray-500 hover:text-gray-700').addClass('border-b-2 border-teal-600 text-teal-700 active');

                const target = $(this).data('mtarget');
                $('.m-tab-content').addClass('hidden');
                $('#' + target).removeClass('hidden');
            });

            $(document).on('click', '#btnCloseModalAtendimento, #btnFecharModalBottom, #modalAtendimentoBackdrop', function() {
                $('#modalAtendimentoDetalhes').addClass('hidden');
            });

            let activePetCharts = {};

            window.abrirModalMeusPets = function() {
                if (!globalDashboardData || !globalDashboardData.pets) {
                    alert('Dados dos pets não carregados.');
                    return;
                }

                const pets = globalDashboardData.pets;
                const container = $('#mListaMeusPetsCards');
                container.empty();

                // Destroy previous Chart instances
                Object.keys(activePetCharts).forEach(key => {
                    if (activePetCharts[key]) {
                        try { activePetCharts[key].destroy(); } catch(e){}
                    }
                });
                activePetCharts = {};

                if (pets.length === 0) {
                    container.html('<p class="text-center text-gray-500 py-8">Nenhum pet cadastrado.</p>');
                    $('#modalMeusPetsDetalhes').removeClass('hidden');
                    return;
                }

                pets.forEach(pet => {
                    const especieRaca = [pet.especie, pet.raca].filter(Boolean).join(' • ') || 'Espécie/Raça não informada';
                    const sexoBadge = pet.sexo === 'M' 
                        ? '<span class="px-2.5 py-1 rounded text-xs font-semibold bg-blue-100 text-blue-800">Macho</span>' 
                        : (pet.sexo === 'F' ? '<span class="px-2.5 py-1 rounded text-xs font-semibold bg-pink-100 text-pink-800">Fêmea</span>' : '');

                    const totalConsultas = pet.total_atendimentos || 0;
                    const dataUltimo = pet.ultimo_atendimento ? formatDateTime(pet.ultimo_atendimento) : 'Sem registros';
                    const queixaUltimo = pet.ultimo_atendimento_queixa ? escapeHtml(pet.ultimo_atendimento_queixa) : 'Nenhuma queixa';

                    // Vacinas do Pet
                    const vacinasPet = (globalDashboardData.vacinas || []).filter(v => v.id_pet == pet.id_pet);
                    const hoje = new Date().toISOString().split('T')[0];
                    const temVencidas = vacinasPet.some(v => v.data_vencimento < hoje);

                    let vacinaStatusBadge = '';
                    if (vacinasPet.length === 0) {
                        vacinaStatusBadge = '<span class="text-gray-500 font-medium">Nenhuma vacina cadastrada</span>';
                    } else if (temVencidas) {
                        vacinaStatusBadge = '<span class="text-red-700 font-bold flex items-center gap-1"><span class="material-icons text-xs">warning</span> Vacina Vencida / Pendente</span>';
                    } else {
                        vacinaStatusBadge = '<span class="text-green-700 font-bold flex items-center gap-1"><span class="material-icons text-xs">check_circle</span> Imunização Em Dia (' + vacinasPet.length + ')</span>';
                    }

                    // Histórico de Peso
                    const historicoPeso = pet.historico_peso || [];
                    let blocoPesoHtml = '';
                    const canvasId = `chartPesoPet_${pet.id_pet}`;

                    if (historicoPeso.length >= 2) {
                        blocoPesoHtml = `
                            <div class="mt-4 pt-4 border-t border-gray-100">
                                <div class="flex justify-between items-center mb-2">
                                    <span class="text-xs font-bold text-gray-700 uppercase tracking-wider flex items-center gap-1">
                                        <span class="material-icons text-teal-600 text-base">show_chart</span> Evolução do Peso (kg)
                                    </span>
                                    <span class="text-xs text-gray-400 font-medium">${historicoPeso.length} pesagens registradas</span>
                                </div>
                                <div class="bg-white p-3 rounded-lg border border-gray-200 h-52 relative">
                                    <canvas id="${canvasId}"></canvas>
                                </div>
                            </div>
                        `;
                    } else if (historicoPeso.length === 1) {
                        const pUnico = historicoPeso[0];
                        blocoPesoHtml = `
                            <div class="mt-4 pt-4 border-t border-gray-100 flex items-center justify-between bg-teal-50/50 p-3 rounded-lg border border-teal-100">
                                <div class="flex items-center gap-2">
                                    <span class="material-icons text-teal-600">monitor_weight</span>
                                    <span class="text-xs font-semibold text-teal-900">Última Pesagem Registrada:</span>
                                </div>
                                <span class="text-sm font-bold text-teal-950">${pUnico.peso.toFixed(2)} kg <span class="text-xs font-normal text-gray-500">(${formatDate(pUnico.data)})</span></span>
                            </div>
                        `;
                    } else {
                        blocoPesoHtml = `
                            <div class="mt-4 pt-4 border-t border-gray-100 text-xs text-gray-400 italic">
                                Nenhuma pesagem registrada nos atendimentos.
                            </div>
                        `;
                    }

                    container.append(`
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                            <!-- Pet Header -->
                            <div class="p-4 bg-teal-50/60 border-b border-teal-100 flex flex-col sm:flex-row justify-between sm:items-center gap-2">
                                <div class="flex items-center gap-3">
                                    <div class="p-3 bg-teal-600 text-white rounded-full shadow-sm">
                                        <span class="material-icons text-2xl">pets</span>
                                    </div>
                                    <div>
                                        <h4 class="text-lg font-bold text-gray-800">${escapeHtml(pet.nome)}</h4>
                                        <span class="text-xs text-gray-500">${escapeHtml(especieRaca)}</span>
                                    </div>
                                </div>
                                <div>${sexoBadge}</div>
                            </div>

                            <!-- Pet Metrics -->
                            <div class="p-5 space-y-4">
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
                                    <div class="p-3 bg-gray-50 rounded-lg border border-gray-100">
                                        <span class="text-gray-400 block font-medium uppercase mb-0.5">Consultas Realizadas</span>
                                        <strong class="text-base text-gray-800">${totalConsultas} atendimento(s)</strong>
                                    </div>
                                    <div class="p-3 bg-gray-50 rounded-lg border border-gray-100">
                                        <span class="text-gray-400 block font-medium uppercase mb-0.5">Última Consulta</span>
                                        <strong class="text-gray-800">${dataUltimo}</strong>
                                        <div class="text-gray-500 truncate mt-0.5" title="${queixaUltimo}">${queixaUltimo}</div>
                                    </div>
                                    <div class="p-3 bg-teal-50/70 hover:bg-teal-100/80 rounded-lg border border-teal-200 cursor-pointer transition flex flex-col justify-between group" onclick="irParaCarteiraVacinas()">
                                        <div>
                                            <span class="text-teal-800 block font-semibold uppercase mb-0.5 text-[10px]">Carteira de Vacinação</span>
                                            <div class="text-xs">${vacinaStatusBadge}</div>
                                        </div>
                                        <span class="text-xs text-teal-700 font-bold group-hover:underline flex items-center gap-0.5 mt-1">Ver Carteira <span class="material-icons text-xs">arrow_forward</span></span>
                                    </div>
                                </div>

                                ${blocoPesoHtml}
                            </div>
                        </div>
                    `);
                });

                $('#modalMeusPetsDetalhes').removeClass('hidden');

                // Render Chart.js charts for pets with 2+ weight entries
                setTimeout(() => {
                    pets.forEach(pet => {
                        const historicoPeso = pet.historico_peso || [];
                        if (historicoPeso.length >= 2) {
                            const canvasId = `chartPesoPet_${pet.id_pet}`;
                            const ctx = document.getElementById(canvasId);
                            if (ctx) {
                                const labels = historicoPeso.map(h => formatDate(h.data));
                                const dataPoints = historicoPeso.map(h => h.peso);

                                activePetCharts[pet.id_pet] = new Chart(ctx, {
                                    type: 'line',
                                    data: {
                                        labels: labels,
                                        datasets: [{
                                            label: 'Peso (kg)',
                                            data: dataPoints,
                                            borderColor: '#0d9488',
                                            backgroundColor: 'rgba(13, 148, 136, 0.1)',
                                            borderWidth: 3,
                                            pointBackgroundColor: '#0f766e',
                                            pointRadius: 5,
                                            pointHoverRadius: 7,
                                            tension: 0.3,
                                            fill: true
                                        }]
                                    },
                                    options: {
                                        responsive: true,
                                        maintainAspectRatio: false,
                                        plugins: {
                                            legend: { display: false },
                                            tooltip: {
                                                callbacks: {
                                                    label: function(context) {
                                                        return `Peso: ${context.parsed.y.toFixed(2)} kg`;
                                                    }
                                                }
                                            }
                                        },
                                        scales: {
                                            y: {
                                                beginAtZero: false,
                                                ticks: {
                                                    callback: function(val) { return val + ' kg'; }
                                                }
                                            }
                                        }
                                    }
                                });
                            }
                        }
                    });
                }, 150);
            };

            $(document).on('click', '#btnCloseModalPets, #btnFecharModalPetsBottom, #modalMeusPetsBackdrop', function() {
                $('#modalMeusPetsDetalhes').addClass('hidden');
            });

            window.irParaCarteiraVacinas = function() {
                $('#modalMeusPetsDetalhes').addClass('hidden');
                const btnVac = $('.tab-btn[data-target="vacinas"]');
                if (btnVac.length) {
                    btnVac.trigger('click');
                }
            };

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