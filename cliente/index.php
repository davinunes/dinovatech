<?php
session_set_cookie_params(0, '/');
session_start();
require_once __DIR__ . '/../dinovatech/config.php';
require_once __DIR__ . '/../dinovatech/helpers/AppHelper.php';
require_once __DIR__ . '/../database.php';

$cliente_logado = isset($_SESSION['cliente_id']);
$nome_cliente   = $_SESSION['cliente_nome'] ?? '';
$is_vet         = AppHelper::isVetMode();

// Busca nome da empresa com validação (não exibe se CNPJ ausente/inválido)
$empresa_nome   = '';
$empresa_logo   = '';
$link_cfg = DBConnect();
$rCfg = DBExecute($link_cfg, "SELECT nome_fantasia, razao_social, cnpj, logo_url FROM ConfiguracoesEmissor LIMIT 1");
if ($rCfg && $cfg = mysqli_fetch_assoc($rCfg)) {
    $cnpj_raw = preg_replace('/\D/', '', $cfg['cnpj'] ?? '');
    if (strlen($cnpj_raw) === 14) { // CNPJ válido
        $empresa_nome = $cfg['nome_fantasia'] ?: $cfg['razao_social'] ?: '';
        $empresa_logo = $cfg['logo_url'] ?? '';
        if (!empty($empresa_logo) && !preg_match('~^(https?://|/)~i', $empresa_logo)) {
            $empresa_logo = '../dinovatech/' . $empresa_logo;
        }
    }
}

// Busca foto do perfil do cliente logado
$foto_cliente = '';
if ($cliente_logado) {
    $id_cli_safe = (int)$_SESSION['cliente_id'];
    $rCli = DBExecute($link_cfg, "SELECT foto_url FROM Clientes WHERE id_cliente = '$id_cli_safe' LIMIT 1");
    if ($rCli && $cli = mysqli_fetch_assoc($rCli)) {
        $foto_cliente = $cli['foto_url'] ?? '';
        if (!empty($foto_cliente) && !preg_match('~^(https?://|/)~i', $foto_cliente)) {
            $foto_cliente = '../dinovatech/' . $foto_cliente;
        }
    }
}
DBClose($link_cfg);

$nome_inicial = strtok($nome_cliente, ' ');
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <title><?= $is_vet ? 'Portal do Tutor' : 'Área do Cliente' ?> <?= $empresa_nome ? '— ' . htmlspecialchars($empresa_nome) : '' ?></title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="<?= $is_vet ? '#065f46' : '#0c4a6e' ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --font: 'Inter', sans-serif;
            <?php if ($is_vet): ?>
            --brand:        #059669;
            --brand-dark:   #065f46;
            --brand-light:  #d1fae5;
            --brand-mid:    #10b981;
            --accent:       #f59e0b;
            --accent-light: #fef3c7;
            --tab-active:   #059669;
            --btn-primary:  #059669;
            --btn-hover:    #047857;
            --header-from:  #064e3b;
            --header-to:    #065f46;
            <?php else: ?>
            --brand:        #0284c7;
            --brand-dark:   #0c4a6e;
            --brand-light:  #e0f2fe;
            --brand-mid:    #38bdf8;
            --accent:       #6366f1;
            --accent-light: #ede9fe;
            --tab-active:   #0284c7;
            --btn-primary:  #0284c7;
            --btn-hover:    #0369a1;
            --header-from:  #0c4a6e;
            --header-to:    #0369a1;
            <?php endif; ?>
        }

        * { box-sizing: border-box; }
        body { font-family: var(--font); }

        /* Scrollbar suave (sem barra cinza) */
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

        /* ======= ANIMAÇÕES ======= */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(16px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to   { opacity: 1; }
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(100%); }
            to   { opacity: 1; transform: translateY(0); }
        }
        @keyframes scaleIn {
            from { opacity: 0; transform: scale(0.92); }
            to   { opacity: 1; transform: scale(1); }
        }
        @keyframes pulse-ring {
            0%   { box-shadow: 0 0 0 0 rgba(16,185,129,.4); }
            70%  { box-shadow: 0 0 0 10px rgba(16,185,129,0); }
            100% { box-shadow: 0 0 0 0 rgba(16,185,129,0); }
        }

        .animate-fadein    { animation: fadeIn .3s ease both; }
        .animate-fadeInUp  { animation: fadeInUp .4s ease both; }
        .animate-scaleIn   { animation: scaleIn .25s cubic-bezier(.16,1,.3,1) both; }
        .animate-slideUp   { animation: slideUp .35s cubic-bezier(.16,1,.3,1) both; }
        .delay-1 { animation-delay: .06s; }
        .delay-2 { animation-delay: .12s; }
        .delay-3 { animation-delay: .18s; }
        .delay-4 { animation-delay: .24s; }

        /* ======= HEADER ======= */
        #app-header {
            background: linear-gradient(135deg, var(--header-from), var(--header-to));
        }

        /* ======= BOTTOM NAV (Mobile) ======= */
        #bottom-nav {
            animation: slideUp .4s cubic-bezier(.16,1,.3,1) both;
        }
        .bnav-btn.active .bnav-icon { color: var(--brand); }
        .bnav-btn.active .bnav-label { color: var(--brand); font-weight: 700; }
        .bnav-btn.active .bnav-pill {
            background: var(--brand-light);
            transform: scaleX(1);
        }
        .bnav-pill {
            width: 32px; height: 4px; border-radius: 9999px;
            background: transparent;
            transform: scaleX(0);
            transition: transform .2s, background .2s;
            margin: 0 auto 2px;
        }
        .bnav-icon { transition: color .2s; }
        .bnav-label { font-size: 10px; letter-spacing: .01em; transition: color .2s; }

        /* ======= DESKTOP TABS ======= */
        .dtab-btn {
            padding: .5rem 1.25rem;
            border-radius: 9999px;
            font-size: .813rem;
            font-weight: 500;
            color: #6b7280;
            transition: background .2s, color .2s;
            display: flex; align-items: center; gap: .375rem;
            white-space: nowrap;
        }
        .dtab-btn:hover { background: #f3f4f6; color: #374151; }
        .dtab-btn.active {
            background: var(--brand-light);
            color: var(--brand-dark);
            font-weight: 700;
        }
        .dtab-btn.active .material-icons-round { color: var(--brand); }

        /* ======= KPI CARDS ======= */
        .kpi-card {
            border-radius: 1.25rem;
            padding: 1rem 1.125rem;
            display: flex;
            align-items: center;
            gap: .875rem;
            transition: transform .2s, box-shadow .2s;
        }
        .kpi-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px -4px rgba(0,0,0,.12); }
        .kpi-icon {
            width: 48px; height: 48px; border-radius: .875rem;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }

        /* ======= PRÓXIMA FATURA CARD ======= */
        .fatura-urgente { animation: pulse-ring 1.8s ease infinite; }

        /* ======= BOTÃO PRIMÁRIO ======= */
        .btn-primary {
            background: var(--btn-primary);
            color: #fff;
            border-radius: .75rem;
            font-weight: 700;
            transition: background .2s, transform .15s;
        }
        .btn-primary:hover { background: var(--btn-hover); transform: translateY(-1px); }
        .btn-primary:active { transform: translateY(0); }

        /* ======= MODAL ======= */
        .modal-overlay {
            background: rgba(0,0,0,.65);
            backdrop-filter: blur(6px);
        }
        .modal-box {
            animation: scaleIn .25s cubic-bezier(.16,1,.3,1) both;
        }

        /* ======= STATUS DA FATURA ======= */
        .status-pago     { background: #dcfce7; color: #166534; }
        .status-aberto   { background: #fef9c3; color: #854d0e; }
        .status-atrasado { background: #fee2e2; color: #991b1b; }

        /* ======= FATURA CARD LISTA ======= */
        .fatura-item {
            display: flex; align-items: center;
            padding: .875rem 1rem;
            border-radius: 1rem;
            background: #fff;
            border: 1px solid #f3f4f6;
            gap: .75rem;
            transition: box-shadow .2s, border-color .2s;
        }
        .fatura-item:hover { box-shadow: 0 4px 12px -2px rgba(0,0,0,.1); border-color: var(--brand-light); }

        /* ======= VACINA CARD ======= */
        .vacina-card {
            border-radius: 1rem;
            padding: 1rem;
            position: relative;
            overflow: hidden;
        }
        .vacina-progress {
            height: 6px; border-radius: 9999px;
            background: rgba(0,0,0,.08);
        }
        .vacina-progress-bar {
            height: 6px; border-radius: 9999px;
            transition: width .6s cubic-bezier(.16,1,.3,1);
        }

        /* ======= TIMELINE BANHO ======= */
        .timeline-step {
            display: flex; flex-direction: column; align-items: center; flex: 1;
        }
        .timeline-dot {
            width: 36px; height: 36px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 1rem;
            border: 3px solid #e5e7eb;
            background: #f9fafb;
            transition: all .4s;
        }
        .timeline-dot.done  { background: var(--brand); border-color: var(--brand); color: #fff; }
        .timeline-dot.active { background: #fff; border-color: var(--brand); box-shadow: 0 0 0 4px var(--brand-light); color: var(--brand); animation: pulse-ring 1.4s ease infinite; }
        .timeline-line { flex: 1; height: 3px; background: #e5e7eb; align-self: center; min-width: 20px; transition: background .4s; }
        .timeline-line.done { background: var(--brand); }

        /* ======= AVATAR PET ======= */
        .pet-avatar {
            width: 56px; height: 56px; border-radius: 50%;
            object-fit: cover;
            border: 3px solid #fff;
            box-shadow: 0 2px 8px rgba(0,0,0,.15);
        }
        .pet-avatar-placeholder {
            width: 56px; height: 56px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            border: 3px solid #fff;
            box-shadow: 0 2px 8px rgba(0,0,0,.15);
        }
        .upload-foto-pet-btn {
            position: absolute; bottom: -2px; right: -2px;
            width: 22px; height: 22px; border-radius: 50%;
            background: var(--brand); color: #fff;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; transition: transform .15s;
            border: 2px solid #fff;
        }
        .upload-foto-pet-btn:hover { transform: scale(1.1); }

        /* ======= LOGIN CARD ======= */
        .login-card {
            background: rgba(255,255,255,.96);
            border-radius: 1.75rem;
            box-shadow: 0 24px 64px -12px rgba(0,0,0,.25);
            border: 1px solid rgba(255,255,255,.6);
        }

        /* ======= BOTTOM NAV PADDING ======= */
        @media (max-width: 767px) {
            .page-content { padding-bottom: 72px; }
        }

        /* ======= SAFE AREA (iOS notch) ======= */
        #bottom-nav {
            padding-bottom: env(safe-area-inset-bottom, 0);
        }

        /* ======= SKELETON LOADER ======= */
        .skel {
            background: linear-gradient(90deg, #f0f0f0 25%, #e8e8e8 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: shimmer 1.4s infinite;
            border-radius: .5rem;
        }
        @keyframes shimmer {
            from { background-position: 200% 0; }
            to   { background-position: -200% 0; }
        }
    </style>
</head>

<body class="bg-gray-50 min-h-screen flex flex-col">

    <!-- ============================================================ -->
    <!-- HEADER -->
    <!-- ============================================================ -->
    <header id="app-header" class="sticky top-0 z-30 shadow-lg">
        <div class="container mx-auto px-4 h-14 sm:h-16 flex justify-between items-center gap-3">
            <!-- Logo / Nome -->
            <a href="../index.php" class="flex items-center gap-2.5 min-w-0">
                <?php if (!empty($empresa_logo)): ?>
                    <img src="<?= htmlspecialchars($empresa_logo) ?>" alt="Logo" class="h-8 w-auto object-contain shrink-0">
                <?php else: ?>
                    <div class="w-8 h-8 rounded-xl bg-white/15 flex items-center justify-center shrink-0">
                        <span class="material-icons-round text-white text-xl"><?= $is_vet ? 'pets' : 'computer' ?></span>
                    </div>
                <?php endif; ?>
                <div class="min-w-0 hidden xs:block">
                    <?php if ($empresa_nome): ?>
                        <p class="text-white/90 text-xs font-medium truncate leading-none"><?= $is_vet ? 'Portal do Tutor' : 'Área do Cliente' ?></p>
                        <p class="text-white font-bold text-sm truncate leading-tight"><?= htmlspecialchars($empresa_nome) ?></p>
                    <?php else: ?>
                        <p class="text-white font-bold text-base truncate"><?= $is_vet ? 'Portal do Tutor' : 'Área do Cliente' ?></p>
                    <?php endif; ?>
                </div>
            </a>

            <?php if ($cliente_logado): ?>
            <!-- User info + logout -->
            <div class="flex items-center gap-2 sm:gap-3 shrink-0">
                <!-- Greeting (desktop) -->
                <span class="hidden md:flex items-center gap-2 text-white/90 text-sm">
                    <?php if (!empty($foto_cliente)): ?>
                        <img src="<?= htmlspecialchars($foto_cliente) ?>" id="headerAvatarImgDesktop" class="w-8 h-8 rounded-full object-cover border border-white/30 shadow-sm shrink-0" alt="Avatar">
                    <?php else: ?>
                        <span id="headerAvatarInitialDesktop" class="inline-flex w-8 h-8 rounded-full bg-white/15 items-center justify-center font-bold text-white text-sm shrink-0">
                            <?= mb_strtoupper(mb_substr($nome_inicial ?: 'C', 0, 1)) ?>
                        </span>
                    <?php endif; ?>
                    Olá, <strong><?= htmlspecialchars($nome_inicial ?: $nome_cliente) ?></strong>! 👋
                </span>
                <!-- Avatar (mobile) -->
                <?php if (!empty($foto_cliente)): ?>
                    <img src="<?= htmlspecialchars($foto_cliente) ?>" id="headerAvatarImgMobile" class="flex md:hidden w-8 h-8 rounded-full object-cover border border-white/30 shadow-sm shrink-0" alt="Avatar">
                <?php else: ?>
                    <span id="headerAvatarInitialMobile" class="flex md:hidden w-8 h-8 rounded-full bg-white/20 items-center justify-center font-bold text-white text-sm shrink-0">
                        <?= mb_strtoupper(mb_substr($nome_inicial ?: 'C', 0, 1)) ?>
                    </span>
                <?php endif; ?>
                <button id="btnLogout"
                    class="text-xs sm:text-sm font-semibold text-white/90 hover:text-white bg-white/10 hover:bg-white/20 px-3 py-1.5 rounded-lg transition flex items-center gap-1">
                    <span class="material-icons-round text-base">logout</span>
                    <span class="hidden sm:inline">Sair</span>
                </button>
            </div>
            <?php endif; ?>
        </div>
    </header>

    <!-- ============================================================ -->
    <!-- MAIN -->
    <!-- ============================================================ -->
    <main class="flex-1 page-content">

        <?php if (!$cliente_logado): ?>
        <!-- ===== TELA DE LOGIN ===== -->
        <div class="min-h-[calc(100vh-56px)] flex items-center justify-center p-4"
             style="background: linear-gradient(135deg, var(--header-from) 0%, var(--brand) 60%, var(--brand-mid) 100%);">

            <div class="login-card w-full max-w-sm p-7 sm:p-8 animate-scaleIn">
                <!-- Ícone / Logo -->
                <div class="flex flex-col items-center mb-6">
                    <?php if (!empty($empresa_logo)): ?>
                        <img src="<?= htmlspecialchars($empresa_logo) ?>" alt="Logo" class="h-14 object-contain mb-3">
                    <?php else: ?>
                        <div class="w-14 h-14 rounded-2xl flex items-center justify-center mb-3"
                             style="background: linear-gradient(135deg, var(--brand), var(--brand-mid));">
                            <span class="material-icons-round text-white text-3xl"><?= $is_vet ? 'pets' : 'computer' ?></span>
                        </div>
                    <?php endif; ?>
                    <h1 class="text-xl font-extrabold text-gray-900 text-center leading-tight">
                        <?= $is_vet ? '🐾 Portal do Tutor' : 'Área do Cliente' ?>
                    </h1>
                    <?php if ($empresa_nome): ?>
                        <p class="text-sm font-semibold mt-0.5" style="color: var(--brand)"><?= htmlspecialchars($empresa_nome) ?></p>
                    <?php endif; ?>
                    <p class="text-xs text-gray-500 mt-1 text-center">
                        <?= $is_vet
                            ? 'Acesse o histórico clínico, vacinas e agende banhos para seus pets!'
                            : 'Consulte suas faturas, contratos e dados cadastrais.' ?>
                    </p>
                </div>

                <form id="loginForm" class="space-y-4">
                    <div>
                        <label for="cpfCnpjLogin" class="block text-xs font-semibold text-gray-700 mb-1.5">CPF / CNPJ</label>
                        <input type="text" id="cpfCnpjLogin" name="cpf_cnpj"
                            placeholder="Digite apenas números"
                            inputmode="numeric" required
                            class="w-full p-3 border border-gray-200 bg-gray-50 rounded-xl focus:outline-none focus:ring-2 focus:border-transparent transition text-sm font-medium"
                            style="--tw-ring-color: var(--brand)">
                    </div>
                    <label class="flex items-center gap-2 cursor-pointer select-none">
                        <input type="checkbox" id="rememberMe" name="remember_me"
                            class="h-4 w-4 rounded border-gray-300 transition"
                            style="accent-color: var(--brand)">
                        <span class="text-xs text-gray-600">Lembrar meus dados</span>
                    </label>
                    <button type="submit"
                        class="btn-primary w-full py-3 rounded-xl flex items-center justify-center gap-2 text-sm">
                        <span class="material-icons-round text-base">login</span> Acessar Portal
                    </button>
                </form>
                <div id="loginMessage" class="mt-3 text-center text-xs font-semibold"></div>
            </div>
        </div>

        <?php else: ?>
        <!-- ===== DASHBOARD (LOGADO) ===== -->

        <!-- DESKTOP TABS (md+) -->
        <div class="hidden md:block sticky top-16 z-20 bg-white border-b border-gray-100 shadow-sm">
            <div class="container mx-auto px-4">
                <div class="flex items-center gap-1 py-2 overflow-x-auto no-scrollbar">
                    <button class="dtab-btn active" data-target="dashboard">
                        <span class="material-icons-round text-base">home</span> Início
                    </button>
                    <button class="dtab-btn" data-target="abertas">
                        <span class="material-icons-round text-base">pending_actions</span> Em Aberto
                    </button>
                    <button class="dtab-btn" data-target="pagas">
                        <span class="material-icons-round text-base">receipt_long</span> Histórico
                    </button>
                    <button class="dtab-btn" data-target="meusdados">
                        <span class="material-icons-round text-base">manage_accounts</span> Meus Dados
                    </button>
                    <?php if ($is_vet): ?>
                    <button class="dtab-btn" data-target="vacinas">
                        <span class="material-icons-round text-base">vaccines</span> Vacinas
                    </button>
                    <button class="dtab-btn" data-target="banhotosa">
                        <span class="material-icons-round text-base">shower</span> Banho & Tosa
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- CONTEÚDO DAS TABS -->
        <div class="container mx-auto px-3 sm:px-4 py-5 sm:py-7">

            <!-- ===== TAB: INÍCIO (DASHBOARD) ===== -->
            <div id="dashboard" class="tab-content space-y-5 sm:space-y-6">

                <!-- KPI GRID -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 animate-fadeInUp">

                    <!-- Faturas Abertas -->
                    <div class="kpi-card bg-white shadow-sm border border-gray-100">
                        <div class="kpi-icon bg-amber-50">
                            <span class="material-icons-round text-amber-500 text-2xl">pending_actions</span>
                        </div>
                        <div class="min-w-0">
                            <p class="text-gray-400 text-[10px] font-semibold uppercase tracking-wider truncate">Em Aberto</p>
                            <h3 class="text-base sm:text-lg font-extrabold text-gray-800 truncate" id="dashTotalAberto">—</h3>
                            <p class="text-[10px] text-amber-600 font-medium truncate" id="dashCountAberto">0 pendentes</p>
                        </div>
                    </div>

                    <!-- Total Pago -->
                    <div class="kpi-card bg-white shadow-sm border border-gray-100 delay-1">
                        <div class="kpi-icon bg-emerald-50">
                            <span class="material-icons-round text-emerald-500 text-2xl">check_circle</span>
                        </div>
                        <div class="min-w-0">
                            <p class="text-gray-400 text-[10px] font-semibold uppercase tracking-wider truncate">Total Pago</p>
                            <h3 class="text-base sm:text-lg font-extrabold text-gray-800 truncate" id="dashTotalPago">—</h3>
                            <p class="text-[10px] text-emerald-600 font-medium truncate" id="dashCountPago">0 liquidadas</p>
                        </div>
                    </div>

                    <!-- Agendamentos -->
                    <div class="kpi-card bg-white shadow-sm border border-gray-100 delay-2">
                        <div class="kpi-icon bg-blue-50">
                            <span class="material-icons-round text-blue-500 text-2xl">calendar_month</span>
                        </div>
                        <div class="min-w-0">
                            <p class="text-gray-400 text-[10px] font-semibold uppercase tracking-wider truncate">Agendamentos</p>
                            <h3 class="text-base sm:text-lg font-extrabold text-gray-800 truncate" id="dashCountAgendamentos">—</h3>
                            <p class="text-[10px] text-blue-600 font-medium truncate">compromissos</p>
                        </div>
                    </div>

                    <?php if ($is_vet): ?>
                    <!-- Meus Pets (VET) -->
                    <div class="kpi-card bg-white shadow-sm border border-gray-100 hover:border-emerald-200 cursor-pointer transition group delay-3"
                         onclick="abrirModalMeusPets()">
                        <div class="kpi-icon bg-emerald-50 group-hover:bg-emerald-600 transition">
                            <span class="material-icons-round text-emerald-500 group-hover:text-white text-2xl transition">pets</span>
                        </div>
                        <div class="min-w-0">
                            <p class="text-gray-400 text-[10px] font-semibold uppercase tracking-wider truncate">Meus Pets</p>
                            <h3 class="text-base sm:text-lg font-extrabold text-gray-800 truncate" id="dashCountPets">—</h3>
                            <p class="text-[10px] font-semibold flex items-center gap-0.5 mt-0.5 truncate" style="color:var(--brand)">
                                Saúde & Histórico <span class="material-icons-round text-xs">chevron_right</span>
                            </p>
                        </div>
                    </div>
                    <?php else: ?>
                    <!-- Perfil (TI) -->
                    <div class="kpi-card bg-white shadow-sm border border-gray-100 delay-3">
                        <div class="kpi-icon" style="background: var(--brand-light)">
                            <span class="material-icons-round text-2xl" style="color: var(--brand)">account_circle</span>
                        </div>
                        <div class="min-w-0">
                            <p class="text-gray-400 text-[10px] font-semibold uppercase tracking-wider truncate">Perfil</p>
                            <h3 class="text-xs sm:text-sm font-extrabold text-gray-800 truncate" id="dashNomeCliente"><?= htmlspecialchars($nome_cliente) ?></h3>
                            <p class="text-[10px] font-medium truncate" style="color: var(--brand)">Cadastrado ✓</p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- CARD PRÓXIMA FATURA (dinâmico) -->
                <div id="dashProximaFaturaCard" class="hidden animate-fadeInUp delay-1">
                    <!-- Preenchido via JS -->
                </div>

                <?php if ($is_vet): ?>
                <!-- BANNER BANHO & TOSA (VET) -->
                <div class="rounded-2xl p-5 shadow-md flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 animate-fadeInUp delay-2"
                     style="background: linear-gradient(135deg, #064e3b, #065f46);">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-white/10 rounded-2xl shrink-0">
                            <span class="material-icons-round text-3xl text-emerald-300">shower</span>
                        </div>
                        <div>
                            <h3 class="text-lg font-extrabold text-white">Agendamento Online — Banho & Tosa</h3>
                            <p class="text-xs text-emerald-100 mt-0.5">Selecione seu pet, veja os horários disponíveis em tempo real e solicite!</p>
                        </div>
                    </div>
                    <button type="button" onclick="abrirModalAgendarBanhoCliente()"
                        class="bg-white font-bold px-5 py-2.5 rounded-xl shadow transition text-sm flex items-center gap-2 shrink-0 hover:bg-emerald-50"
                        style="color: var(--brand-dark)">
                        <span class="material-icons-round text-base" style="color:var(--brand)">calendar_month</span> Solicitar
                    </button>
                </div>
                <?php endif; ?>

                <!-- GRID 2 COL: Agendamentos + (Atendimentos/Faturas) -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 animate-fadeInUp delay-2">

                    <!-- Eventos -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden flex flex-col">
                        <div class="px-5 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                            <div class="flex items-center gap-2">
                                <span class="material-icons-round text-blue-500">calendar_month</span>
                                <h3 class="font-bold text-gray-800 text-sm">Eventos</h3>
                            </div>
                            <?php if ($is_vet): ?>
                            <button type="button" onclick="abrirModalAgendarBanhoCliente()"
                                class="text-xs font-bold px-3 py-1.5 rounded-lg transition flex items-center gap-1"
                                style="background: var(--brand-light); color: var(--brand-dark)">
                                <span class="material-icons-round text-xs">add</span> Novo
                            </button>
                            <?php endif; ?>
                        </div>
                        <div class="p-4 flex-1">
                            <div id="dashListaAgendamentos" class="space-y-2">
                                <div class="skel h-12 w-full"></div>
                                <div class="skel h-12 w-full"></div>
                            </div>
                        </div>
                    </div>

                    <?php if ($is_vet): ?>
                    <!-- Atendimentos Clínicos (VET) -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden flex flex-col">
                        <div class="px-5 py-4 border-b border-gray-100 bg-gray-50/50">
                            <div class="flex items-center gap-2">
                                <span class="material-icons-round" style="color: var(--brand)">medical_services</span>
                                <h3 class="font-bold text-gray-800 text-sm">Atendimentos Clínicos Recentes</h3>
                            </div>
                        </div>
                        <div class="p-4 flex-1">
                            <div id="dashListaAtendimentos" class="space-y-2">
                                <div class="skel h-16 w-full"></div>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                    <!-- Últimas Faturas (TI) -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden flex flex-col">
                        <div class="px-5 py-4 border-b border-gray-100 bg-gray-50/50">
                            <div class="flex items-center gap-2">
                                <span class="material-icons-round" style="color: var(--brand)">receipt</span>
                                <h3 class="font-bold text-gray-800 text-sm">Últimas Faturas</h3>
                            </div>
                        </div>
                        <div class="p-4 flex-1">
                            <div id="dashListaFaturasBreve" class="space-y-2">
                                <div class="skel h-12 w-full"></div>
                                <div class="skel h-12 w-full"></div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- ASSINATURAS & CONTRATOS -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 sm:p-6 animate-fadeInUp delay-3">
                    <div class="flex items-center justify-between mb-4 border-b border-gray-100 pb-3">
                        <div class="flex items-center gap-2">
                            <span class="material-icons-round text-purple-500">auto_renew</span>
                            <h3 class="font-bold text-gray-800">Assinaturas & Contratos</h3>
                        </div>
                        <span class="text-xs text-gray-400 font-semibold" id="dashCountRecorrenciasTag">—</span>
                    </div>
                    <div id="dashListaRecorrencias" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="skel h-24 w-full rounded-xl"></div>
                    </div>
                </div>

                <?php if ($is_vet): ?>
                <!-- VACINAÇÃO (VET) - dashboard preview -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 sm:p-6 animate-fadeInUp delay-3">
                    <div class="flex items-center gap-2 mb-4">
                        <span class="material-icons-round" style="color: var(--brand)">vaccines</span>
                        <h3 class="font-bold text-gray-800">Vacinação dos Pets</h3>
                    </div>
                    <div id="dashListaVacinasBreve" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                        <div class="skel h-20 w-full rounded-xl"></div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- ===== TAB: EM ABERTO ===== -->
            <div id="abertas" class="tab-content hidden">
                <div id="listAbertas" class="space-y-3">
                    <div class="skel h-20 w-full rounded-xl"></div>
                </div>
            </div>

            <!-- ===== TAB: HISTÓRICO ===== -->
            <div id="pagas" class="tab-content hidden">
                <div id="listPagas" class="space-y-3">
                    <div class="skel h-20 w-full rounded-xl"></div>
                </div>
            </div>

            <!-- ===== TAB: MEUS DADOS ===== -->
            <div id="meusdados" class="tab-content hidden max-w-3xl mx-auto space-y-5">

                <!-- Avatar / Foto de Perfil -->
                <div class="bg-white p-5 sm:p-6 rounded-2xl shadow-sm border border-gray-100 flex flex-col sm:flex-row items-center gap-4 text-center sm:text-left">
                    <div class="relative group shrink-0">
                        <div id="containerPerfilAvatarCliente" class="w-20 h-20 rounded-full overflow-hidden border-4 border-white shadow-md flex items-center justify-center" style="background: var(--brand)">
                            <?php if (!empty($foto_cliente)): ?>
                                <img src="<?= htmlspecialchars($foto_cliente) ?>" id="imgPerfilCliente" class="w-full h-full object-cover" alt="Foto de Perfil">
                            <?php else: ?>
                                <span id="initialPerfilCliente" class="text-white font-extrabold text-3xl"><?= mb_strtoupper(mb_substr($nome_inicial ?: 'C', 0, 1)) ?></span>
                            <?php endif; ?>
                        </div>
                        <label for="inputFotoPerfilCliente" class="absolute bottom-0 right-0 w-7 h-7 rounded-full bg-white text-gray-700 shadow-md border border-gray-200 flex items-center justify-center cursor-pointer hover:bg-gray-50 transition" title="Alterar Foto de Perfil">
                            <span class="material-icons-round text-sm" style="color: var(--brand)">photo_camera</span>
                        </label>
                        <input type="file" id="inputFotoPerfilCliente" class="hidden" accept="image/jpeg,image/jfif,image/pjpeg,image/png,image/webp,.jfif">
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-800"><?= htmlspecialchars($nome_cliente) ?></h3>
                        <p class="text-xs text-gray-400 mt-0.5">Clique no ícone de câmera para enviar uma nova foto de perfil</p>
                        <span id="msgUploadFotoCliente" class="text-xs font-bold mt-1 block"></span>
                    </div>
                </div>

                <!-- Dados de Contato -->
                <div class="bg-white p-5 sm:p-6 rounded-2xl shadow-sm border border-gray-100">
                    <div class="flex items-center gap-3 border-b pb-4 mb-5">
                        <div class="p-2 rounded-xl" style="background: var(--brand-light)">
                            <span class="material-icons-round" style="color: var(--brand)">manage_accounts</span>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-gray-800">Meus Dados Cadastrais</h3>
                            <p class="text-xs text-gray-400">Mantenha seus dados de contato atualizados.</p>
                        </div>
                    </div>
                    <form id="meusDadosForm" class="space-y-5">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Nome / Razão Social</label>
                                <input type="text" id="cliNome" readonly disabled
                                    class="w-full p-2.5 bg-gray-100 border border-gray-200 rounded-xl text-sm text-gray-500">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">CPF / CNPJ</label>
                                <input type="text" id="cliCpfCnpj" readonly disabled
                                    class="w-full p-2.5 bg-gray-100 border border-gray-200 rounded-xl text-sm text-gray-500">
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">E-mail</label>
                                <input type="email" id="cliEmail" name="email" placeholder="seuemail@exemplo.com" required
                                    class="w-full p-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:border-transparent transition"
                                    style="--tw-ring-color: var(--brand)">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Telefone / WhatsApp</label>
                                <input type="text" id="cliTelefone" name="telefone" placeholder="(00) 00000-0000"
                                    class="w-full p-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:border-transparent transition"
                                    style="--tw-ring-color: var(--brand)">
                            </div>
                        </div>

                        <!-- Endereço (collapsible) -->
                        <div class="border-t pt-4">
                            <button type="button" id="btnToggleEndereco"
                                class="flex items-center gap-2 text-xs font-bold text-gray-600 uppercase mb-3 hover:text-gray-800 transition">
                                <span class="material-icons-round text-base text-gray-400">place</span>
                                Endereço
                                <span class="material-icons-round text-base text-gray-400 ml-auto" id="iconEnderecoToggle">expand_more</span>
                            </button>
                            <div id="blocoEndereco" class="space-y-4">
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-500 mb-1">CEP</label>
                                        <input type="text" id="cliCep" name="cep" placeholder="00000-000"
                                            class="w-full p-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none">
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label class="block text-xs font-medium text-gray-500 mb-1">Endereço (Rua/Av)</label>
                                        <input type="text" id="cliEndereco" name="endereco" placeholder="Rua..."
                                            class="w-full p-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none">
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-500 mb-1">Número</label>
                                        <input type="text" id="cliNumero" name="numero" placeholder="123"
                                            class="w-full p-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-500 mb-1">Complemento</label>
                                        <input type="text" id="cliComplemento" name="complemento" placeholder="Apto..."
                                            class="w-full p-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-500 mb-1">Bairro</label>
                                        <input type="text" id="cliBairro" name="bairro" placeholder="Bairro"
                                            class="w-full p-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-500 mb-1">UF</label>
                                        <input type="text" id="cliUf" name="uf" placeholder="SP" maxlength="2"
                                            class="w-full p-2.5 border border-gray-200 rounded-xl text-sm uppercase focus:outline-none">
                                    </div>
                                </div>
                                <div class="sm:max-w-xs">
                                    <label class="block text-xs font-medium text-gray-500 mb-1">Código Município (IBGE)</label>
                                    <input type="text" id="cliCodigoMunicipio" name="codigo_municipio" placeholder="3550308"
                                        class="w-full p-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none">
                                </div>
                            </div>
                        </div>

                        <!-- Google Agenda Integration -->
                        <div id="containerGoogleCalendarConfig" class="hidden border-t pt-4">
                            <div class="flex items-center gap-2 mb-3">
                                <span class="material-icons-round text-blue-500">event</span>
                                <h4 class="font-bold text-gray-800 text-sm">Sincronização com Google Agenda</h4>
                            </div>
                            <div class="mb-3">
                                <label class="block text-xs font-semibold text-gray-700 mb-1">ID da sua Agenda Google (Opcional)</label>
                                <input type="text" id="cliGoogleCalendarId" name="google_calendar_id"
                                    placeholder="seu_email@gmail.com ou ID da agenda"
                                    class="w-full p-2.5 border border-gray-200 rounded-xl text-sm font-mono focus:outline-none focus:ring-2 focus:ring-blue-400">
                            </div>
                            <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 text-xs text-blue-900 space-y-2">
                                <div class="font-bold flex items-center gap-1 text-blue-800 text-sm">
                                    <span class="material-icons-round text-base">help_outline</span> Como configurar seu Google Agenda:
                                </div>
                                <ol class="list-decimal list-inside space-y-1 text-blue-800">
                                    <li>Acesse o <a href="https://calendar.google.com" target="_blank" class="underline font-bold">Google Agenda</a> e abra as configurações da agenda desejada.</li>
                                    <li>Em <strong>"Compartilhar com pessoas específicas"</strong>, adicione a conta de serviço do sistema:</li>
                                </ol>
                                <code id="googleServiceEmailHintText" class="select-all bg-white px-2.5 py-1.5 rounded border border-blue-300 text-xs font-mono text-blue-900 font-semibold block w-fit shadow-sm">--</code>
                                <p class="text-blue-800">3. Conceda a permissão <strong>"Fazer alterações em eventos"</strong>.</p>
                                <p class="text-blue-800">4. Cole no campo acima o e-mail/ID da sua agenda para sincronizar automaticamente.</p>
                            </div>
                        </div>

                        <p id="meusDadosMsg" class="text-xs font-semibold text-center"></p>
                        <div class="pt-2 flex justify-end">
                            <button type="submit" id="btnSalvarDados" class="btn-primary px-6 py-2.5 flex items-center gap-2 text-sm">
                                <span class="material-icons-round text-base">save</span> Salvar Dados
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Assinaturas & Termos -->
                <div class="bg-white p-5 sm:p-6 rounded-2xl shadow-sm border border-gray-100">
                    <div class="flex items-center gap-3 border-b pb-4 mb-5">
                        <div class="p-2 rounded-xl bg-purple-50">
                            <span class="material-icons-round text-purple-500">history_edu</span>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-gray-800">Assinaturas & Termos Contratuais</h3>
                            <p class="text-xs text-gray-400">Consulte suas assinaturas ativas e documentos vinculados.</p>
                        </div>
                    </div>
                    <div id="listaRecorrenciasFull" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="skel h-24 w-full rounded-xl"></div>
                    </div>
                </div>
            </div>

            <?php if ($is_vet): ?>
            <!-- ===== TAB: CARTEIRA DE VACINAS (VET) ===== -->
            <div id="vacinas" class="tab-content hidden">
                <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 mb-5 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                    <div>
                        <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                            <span class="material-icons-round" style="color: var(--brand)">health_and_safety</span> Carteira Virtual de Vacinação
                        </h3>
                        <p class="text-sm text-gray-400 mt-0.5">Histórico de vacinas e imunização dos seus pets.</p>
                    </div>
                </div>
                <div id="listaCarteiraVacinasFull" class="space-y-5">
                    <div class="skel h-32 w-full rounded-2xl"></div>
                </div>
            </div>

            <!-- ===== TAB: BANHO & TOSA (VET) ===== -->
            <div id="banhotosa" class="tab-content hidden space-y-5">
                <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                    <div>
                        <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                            <span class="material-icons-round" style="color: var(--brand)">shower</span> Estética, Banhos & Pacotes
                        </h3>
                        <p class="text-sm text-gray-400 mt-0.5">Acompanhe créditos de pacotes e agende banhos.</p>
                    </div>
                    <button type="button" onclick="abrirModalAgendarBanhoCliente()"
                        class="btn-primary px-5 py-2.5 flex items-center gap-2 text-sm shrink-0">
                        <span class="material-icons-round text-base">calendar_month</span> Agendar Banho / Tosa
                    </button>
                </div>

                <!-- Status Ao Vivo -->
                <div id="containerBanhoAoVivo" class="hidden">
                    <div class="rounded-2xl p-5 shadow-lg border border-white/10"
                         style="background: linear-gradient(135deg, #064e3b, #1e293b);">
                        <div class="flex items-center gap-2 mb-4">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-ping"></span>
                            <span class="text-xs font-bold uppercase tracking-wider text-emerald-300">Acompanhamento em Tempo Real</span>
                        </div>
                        <div id="listaPetsAoVivo" class="grid grid-cols-1 md:grid-cols-2 gap-3"></div>
                    </div>
                </div>

                <!-- Pacotes -->
                <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100">
                    <div class="flex items-center gap-2 border-b pb-4 mb-4">
                        <span class="material-icons-round text-amber-500">card_giftcard</span>
                        <h4 class="font-bold text-gray-800">Meus Pacotes & Créditos Ativos</h4>
                    </div>
                    <div id="listaPacotesCliente" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div class="skel h-32 w-full rounded-xl"></div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

        </div><!-- /container -->
        <?php endif; ?>
    </main>

    <?php if ($cliente_logado): ?>
    <!-- ============================================================ -->
    <!-- BOTTOM NAV (Mobile Only) -->
    <!-- ============================================================ -->
    <nav id="bottom-nav" class="fixed bottom-0 left-0 right-0 z-30 bg-white border-t border-gray-100 shadow-lg md:hidden">
        <div class="flex items-stretch h-16">
            <button class="bnav-btn active flex-1 flex flex-col items-center justify-center pt-2 pb-1" data-target="dashboard">
                <div class="bnav-pill"></div>
                <span class="material-icons-round bnav-icon text-gray-400 text-[22px]">home</span>
                <span class="bnav-label text-gray-400 mt-0.5">Início</span>
            </button>
            <button class="bnav-btn flex-1 flex flex-col items-center justify-center pt-2 pb-1" data-target="abertas">
                <div class="bnav-pill"></div>
                <span class="material-icons-round bnav-icon text-gray-400 text-[22px]">pending_actions</span>
                <span class="bnav-label text-gray-400 mt-0.5">Faturas</span>
            </button>
            <?php if ($is_vet): ?>
            <button class="bnav-btn flex-1 flex flex-col items-center justify-center pt-2 pb-1" data-target="vacinas">
                <div class="bnav-pill"></div>
                <span class="material-icons-round bnav-icon text-gray-400 text-[22px]">vaccines</span>
                <span class="bnav-label text-gray-400 mt-0.5">Vacinas</span>
            </button>
            <button class="bnav-btn flex-1 flex flex-col items-center justify-center pt-2 pb-1" data-target="banhotosa">
                <div class="bnav-pill"></div>
                <span class="material-icons-round bnav-icon text-gray-400 text-[22px]">shower</span>
                <span class="bnav-label text-gray-400 mt-0.5">Banhos</span>
            </button>
            <?php endif; ?>
            <button class="bnav-btn flex-1 flex flex-col items-center justify-center pt-2 pb-1" data-target="meusdados">
                <div class="bnav-pill"></div>
                <span class="material-icons-round bnav-icon text-gray-400 text-[22px]">person</span>
                <span class="bnav-label text-gray-400 mt-0.5">Dados</span>
            </button>
        </div>
    </nav>
    <?php endif; ?>

    <!-- ============================================================ -->
    <!-- MODAL: Atendimento Clínico (Vet) -->
    <!-- ============================================================ -->
    <div id="modalAtendimentoDetalhes" class="fixed inset-0 z-50 hidden modal-overlay overflow-y-auto flex items-center justify-center p-2 sm:p-4" role="dialog" aria-modal="true">
        <div class="modal-box relative rounded-2xl bg-white text-left shadow-2xl sm:my-8 max-w-3xl w-full max-h-[94vh] flex flex-col overflow-hidden border border-gray-100">
            <div class="p-4 sm:p-6 text-white flex justify-between items-start shrink-0 shadow-sm"
                 style="background: linear-gradient(135deg, var(--header-from), var(--brand))">
                <div>
                    <div class="flex items-center gap-1.5 sm:gap-2 mb-1">
                        <span class="material-icons-round text-white/70 text-sm">medical_services</span>
                        <span class="text-[11px] sm:text-xs uppercase tracking-wider font-semibold text-white/80" id="mAtendData">--/--/----</span>
                    </div>
                    <h3 class="text-xl sm:text-2xl font-bold" id="mAtendPetNome">Atendimento Clínico</h3>
                    <p class="text-xs text-white/70 mt-0.5" id="mAtendPetDetalhes">--</p>
                </div>
                <button type="button" id="btnCloseModalAtendimento" class="text-white/80 hover:text-white bg-white/10 hover:bg-white/20 p-1.5 sm:p-2 rounded-xl transition">
                    <span class="material-icons-round text-xl sm:text-2xl">close</span>
                </button>
            </div>
            <div class="p-3.5 sm:p-6 space-y-4 sm:space-y-5 overflow-y-auto flex-1">
                <div class="p-3.5 sm:p-4 rounded-xl border flex flex-col sm:flex-row justify-between sm:items-center text-xs sm:text-sm gap-2"
                     style="background: var(--brand-light); border-color: rgba(0,0,0,.06)">
                    <div>
                        <span class="text-[10px] text-gray-500 font-medium uppercase block">Veterinário Responsável</span>
                        <strong class="text-sm sm:text-base" style="color: var(--brand-dark)" id="mAtendVetNome">--</strong>
                    </div>
                    <div class="text-xs px-2.5 py-1 rounded-lg font-semibold w-fit"
                         style="background: var(--brand); color:#fff" id="mAtendVetCrmv">CRMV: --</div>
                </div>
                <div class="border-b border-gray-200 flex flex-nowrap overflow-x-auto no-scrollbar gap-2 sm:gap-4 text-xs sm:text-sm font-semibold pb-1">
                    <button type="button" class="m-tab-btn active border-b-2 pb-2 flex items-center gap-1.5 shrink-0 whitespace-nowrap" style="border-color: var(--brand); color: var(--brand)" data-mtarget="m-prontuario">
                        <span class="material-icons-round text-sm">assignment</span> Prontuário Clínico
                    </button>
                    <button type="button" class="m-tab-btn text-gray-500 hover:text-gray-700 pb-2 flex items-center gap-1.5 shrink-0 whitespace-nowrap" data-mtarget="m-receitas">
                        <span class="material-icons-round text-sm">receipt</span> Receitas (<span id="mCountReceitas">0</span>)
                    </button>
                    <button type="button" class="m-tab-btn text-gray-500 hover:text-gray-700 pb-2 flex items-center gap-1.5 shrink-0 whitespace-nowrap" data-mtarget="m-anexos">
                        <span class="material-icons-round text-sm">attach_file</span> Exames & Anexos (<span id="mCountAnexos">0</span>)
                    </button>
                </div>
                <div id="m-prontuario" class="m-tab-content space-y-3.5 sm:space-y-4">
                    <div class="bg-gray-50 p-3.5 sm:p-4 rounded-xl border border-gray-100">
                        <h4 class="text-[10px] sm:text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Motivo / Queixa Principal</h4>
                        <p class="text-xs sm:text-sm text-gray-800 whitespace-pre-line leading-relaxed" id="mAtendQueixa">--</p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 sm:gap-4">
                        <div class="bg-gray-50 p-3.5 sm:p-4 rounded-xl border border-gray-100">
                            <h4 class="text-[10px] sm:text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Anamnese / Histórico</h4>
                            <p class="text-xs sm:text-sm text-gray-800 whitespace-pre-line leading-relaxed" id="mAtendAnamnese">--</p>
                        </div>
                        <div class="bg-gray-50 p-3.5 sm:p-4 rounded-xl border border-gray-100">
                            <h4 class="text-[10px] sm:text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Exame Físico</h4>
                            <p class="text-xs sm:text-sm text-gray-800 whitespace-pre-line leading-relaxed" id="mAtendExameFisico">--</p>
                        </div>
                    </div>
                    <div class="bg-amber-50/60 p-3.5 sm:p-4 rounded-xl border border-amber-200">
                        <h4 class="text-[10px] sm:text-xs font-bold text-amber-900 uppercase tracking-wider mb-1">Diagnóstico</h4>
                        <p class="text-xs sm:text-sm text-amber-950 font-medium whitespace-pre-line leading-relaxed" id="mAtendDiagnostico">--</p>
                    </div>
                    <div class="bg-emerald-50/60 p-3.5 sm:p-4 rounded-xl border border-emerald-200">
                        <h4 class="text-[10px] sm:text-xs font-bold text-emerald-900 uppercase tracking-wider mb-1">Conduta & Tratamento</h4>
                        <p class="text-xs sm:text-sm text-emerald-950 whitespace-pre-line leading-relaxed" id="mAtendTratamento">--</p>
                    </div>
                </div>
                <div id="m-receitas" class="m-tab-content hidden space-y-3">
                    <div id="mListaReceitas"><p class="text-center text-gray-400 py-4 text-sm">Carregando...</p></div>
                </div>
                <div id="m-anexos" class="m-tab-content hidden">
                    <div id="mListaAnexos" class="grid grid-cols-1 sm:grid-cols-2 gap-2.5"></div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 sm:px-6 py-3 sm:py-4 border-t border-gray-100 flex justify-between items-center shrink-0">
                <span class="text-[11px] text-gray-400 font-mono" id="mAtendIdTag">ID: #--</span>
                <button type="button" id="btnFecharModalBottom" class="px-4 sm:px-5 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-xl text-xs sm:text-sm font-semibold transition">Fechar</button>
            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- MODAL: Meus Pets — Saúde & Histórico (Vet) -->
    <!-- ============================================================ -->
    <div id="modalMeusPetsDetalhes" class="fixed inset-0 z-50 hidden modal-overlay overflow-y-auto flex items-center justify-center p-2 sm:p-4" role="dialog" aria-modal="true">
        <div class="modal-box relative rounded-2xl bg-white shadow-2xl sm:my-8 max-w-4xl w-full max-h-[94vh] flex flex-col overflow-hidden border border-gray-100">
            <div class="p-4 sm:p-6 text-white flex justify-between items-center shrink-0 shadow-sm"
                 style="background: linear-gradient(135deg, var(--header-from), var(--brand))">
                <div class="flex items-center gap-2.5 sm:gap-3">
                    <div class="p-2 sm:p-2.5 bg-white/10 rounded-xl">
                        <span class="material-icons-round text-2xl sm:text-3xl text-white/80">pets</span>
                    </div>
                    <div>
                        <h3 class="text-lg sm:text-2xl font-bold">Meus Pets — Saúde & Histórico</h3>
                        <p class="text-[11px] sm:text-xs text-white/70">Evolução do peso e prontuário médico dos seus pets.</p>
                    </div>
                </div>
                <button type="button" id="btnCloseModalPets" class="text-white/80 hover:text-white bg-white/10 hover:bg-white/20 p-1.5 sm:p-2 rounded-xl transition">
                    <span class="material-icons-round text-xl sm:text-2xl">close</span>
                </button>
            </div>
            <div class="p-3 sm:p-6 space-y-5 overflow-y-auto flex-1 bg-gray-50/50">
                <div id="mListaMeusPetsCards" class="space-y-4 sm:space-y-5">
                    <div class="skel h-48 w-full rounded-2xl"></div>
                </div>
            </div>
            <div class="bg-white px-4 sm:px-6 py-3 sm:py-4 border-t border-gray-100 flex justify-end shrink-0">
                <button type="button" id="btnFecharModalPetsBottom" class="px-5 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-xl text-xs sm:text-sm font-semibold transition">Fechar</button>
            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- MODAL: Extrato do Pacote (Vet) -->
    <!-- ============================================================ -->
    <div id="modalExtratoPacoteCliente" class="fixed inset-0 z-50 hidden modal-overlay flex items-center justify-center p-2 sm:p-4">
        <div class="modal-box bg-white rounded-2xl max-w-lg w-full p-4 sm:p-6 shadow-2xl overflow-y-auto max-h-[92vh] flex flex-col border border-gray-100">
            <div class="flex justify-between items-center mb-4 border-b border-gray-100 pb-3">
                <div class="flex items-center gap-2">
                    <div class="p-2 rounded-xl" style="background: var(--brand-light)">
                        <span class="material-icons-round text-xl" style="color: var(--brand)">receipt_long</span>
                    </div>
                    <h3 class="text-base sm:text-lg font-bold text-gray-800">Extrato do seu Pacote</h3>
                </div>
                <button type="button" onclick="$('#modalExtratoPacoteCliente').addClass('hidden')" class="text-gray-400 hover:text-gray-600 p-1.5 rounded-xl hover:bg-gray-100 transition">
                    <span class="material-icons-round text-xl">close</span>
                </button>
            </div>
            <div id="extratoClienteConteudo" class="space-y-4 overflow-y-auto flex-1"></div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- MODAL: Agendar Banho / Tosa (Vet) -->
    <!-- ============================================================ -->
    <div id="modalAgendarBanho" class="fixed inset-0 z-50 hidden modal-overlay flex items-center justify-center p-2 sm:p-4">
        <div class="modal-box bg-white rounded-2xl max-w-xl w-full p-4 sm:p-6 shadow-2xl overflow-y-auto max-h-[92vh] flex flex-col border border-gray-100">
            <div class="flex justify-between items-center mb-4 border-b border-gray-100 pb-3">
                <div class="flex items-center gap-2">
                    <div class="p-2 rounded-xl" style="background: var(--brand-light)">
                        <span class="material-icons-round text-xl" style="color: var(--brand)">shower</span>
                    </div>
                    <div>
                        <h3 class="text-base sm:text-lg font-bold text-gray-800">Agendar Banho & Tosa</h3>
                        <p class="text-[11px] text-gray-400">Escolha o pet, serviço e o melhor horário</p>
                    </div>
                </div>
                <button type="button" onclick="fecharModalAgendarBanhoCliente()" class="text-gray-400 hover:text-gray-600 p-1.5 rounded-xl hover:bg-gray-100 transition">
                    <span class="material-icons-round text-xl">close</span>
                </button>
            </div>
            <form id="formAgendarBanhoCliente" class="space-y-4 overflow-y-auto flex-1">
                <input type="hidden" name="hora_selecionada" id="modalAgendarHoraSelecionada" value="">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">1. Escolha seu Pet *</label>
                        <select name="id_pet" id="modalAgendarPet"
                            class="w-full border border-gray-200 rounded-xl p-2.5 text-sm font-medium focus:ring-2 focus:outline-none"
                            style="--tw-ring-color: var(--brand)" required>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">2. Serviço Desejado *</label>
                        <select name="id_servico" id="modalAgendarServico"
                            class="w-full border border-gray-200 rounded-xl p-2.5 text-sm font-medium focus:ring-2 focus:outline-none"
                            style="--tw-ring-color: var(--brand)" required>
                        </select>
                    </div>
                </div>
                <div id="boxAgendarSaldoBadge" class="hidden bg-amber-50 border border-amber-200 rounded-xl p-3 text-xs text-amber-900">
                    <span class="font-bold flex items-center gap-1 text-amber-800 mb-1">
                        <span class="material-icons-round text-sm text-amber-600">card_giftcard</span> Crédito de Pacote Disponível!
                    </span>
                    <p id="textoAgendarSaldoBadge" class="text-amber-800 font-medium"></p>
                    <label class="flex items-center space-x-2 cursor-pointer mt-2">
                        <input type="checkbox" name="usar_saldo_pacote" id="modalAgendarUsarSaldo" value="1" checked class="h-4 w-4 rounded border-gray-300">
                        <span class="font-bold text-amber-900">Utilizar 1 crédito do meu pacote (sem custo adicional)</span>
                    </label>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">3. Escolha o Dia *</label>
                    <input type="date" name="data_dia" id="modalAgendarDataDia" required
                        class="w-full border border-gray-200 rounded-xl p-2.5 text-sm font-medium focus:ring-2 focus:outline-none"
                        style="--tw-ring-color: var(--brand)">
                </div>
                <div>
                    <div class="flex justify-between items-center mb-1.5">
                        <label class="block text-xs font-bold text-gray-700 uppercase">4. Horários Disponíveis *</label>
                        <span id="labelDuracaoEstimada" class="text-[11px] font-semibold" style="color: var(--brand)"></span>
                    </div>
                    <div id="containerSlotsHorarios" class="bg-gray-50 p-3 sm:p-3.5 rounded-xl border border-gray-200 min-h-[90px] flex items-center justify-center">
                        <p class="text-xs text-gray-400 italic">Selecione o pet, serviço e o dia para ver os horários.</p>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Observações / Preferências de Corte</label>
                    <textarea name="observacoes" id="modalAgendarObservacoes" rows="2"
                        placeholder="Ex: Não cortar unhas, tosa higiênica baixa..."
                        class="w-full border border-gray-200 rounded-xl p-2.5 text-sm focus:ring-2 focus:outline-none"
                        style="--tw-ring-color: var(--brand)"></textarea>
                </div>
                <div id="modalAgendarMsg" class="text-xs font-bold text-center hidden"></div>
                <div class="flex justify-end gap-2 pt-3 border-t border-gray-100">
                    <button type="button" onclick="fecharModalAgendarBanhoCliente()"
                        class="px-4 py-2.5 bg-gray-100 text-gray-700 rounded-xl text-xs sm:text-sm font-semibold hover:bg-gray-200 transition">Cancelar</button>
                    <button type="submit" id="btnConfirmarAgendamento" disabled
                        class="btn-primary px-5 sm:px-6 py-2.5 text-xs sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                        Confirmar Agendamento
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- JAVASCRIPT -->
    <!-- ============================================================ -->
    <script>
        $(document).ready(function () {
            let globalDashboardData = null;

            // ── CPF/CNPJ Mask ──
            $('#cpfCnpjLogin').on('input', function () {
                this.value = this.value.replace(/[^0-9]/g, '');
            });

            // ── Collapsible Endereço ──
            $('#btnToggleEndereco').on('click', function () {
                $('#blocoEndereco').slideToggle(200);
                const icon = $('#iconEnderecoToggle');
                icon.text(icon.text() === 'expand_more' ? 'expand_less' : 'expand_more');
            });

            // ── Navigation: unifica bottom-nav (mobile) + desktop tabs ──
            function activateTab(target) {
                // Desktop tabs
                $('.dtab-btn').removeClass('active');
                $(`.dtab-btn[data-target="${target}"]`).addClass('active');
                // Mobile bottom nav
                $('.bnav-btn').removeClass('active');
                $(`.bnav-btn[data-target="${target}"]`).addClass('active');
                // Content
                $('.tab-content').addClass('hidden');
                $(`#${target}`).removeClass('hidden').addClass('animate-fadeInUp');
                // Scroll to top
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }

            $('.dtab-btn, .bnav-btn').on('click', function () {
                activateTab($(this).data('target'));
            });

            // ── Login ──
            $('#loginForm').on('submit', function (e) {
                e.preventDefault();
                const cpfCnpj = $('#cpfCnpjLogin').val();
                const rememberMe = $('#rememberMe').is(':checked');
                const btn = $(this).find('button[type="submit"]');
                btn.prop('disabled', true).html('<span class="material-icons-round animate-spin text-base">sync</span> Verificando...');
                $.ajax({
                    url: '../dinovatech/app.php', type: 'POST', dataType: 'json',
                    data: { action: 'validar_cpf_cnpj', cpf_cnpj: cpfCnpj },
                    success: function (response) {
                        if (response.success) {
                            if (rememberMe) localStorage.setItem('dinovatech_cpf_cnpj', cpfCnpj);
                            else localStorage.removeItem('dinovatech_cpf_cnpj');
                            window.location.reload();
                        } else {
                            $('#loginMessage').css('color', '#dc2626').text(response.message);
                            btn.prop('disabled', false).html('<span class="material-icons-round text-base">login</span> Acessar Portal');
                        }
                    },
                    error: function () {
                        alert('Erro ao conectar.');
                        btn.prop('disabled', false).html('<span class="material-icons-round text-base">login</span> Acessar Portal');
                    }
                });
            });

            // ── Logout ──
            $('#btnLogout').click(function () {
                $.post('logout.php', function () {
                    localStorage.removeItem('dinovatech_cpf_cnpj');
                    window.location.reload();
                });
            });

            // ── Auto-fill login ──
            const savedCpf = localStorage.getItem('dinovatech_cpf_cnpj');
            if (savedCpf && $('#cpfCnpjLogin').length) {
                $('#cpfCnpjLogin').val(savedCpf);
                $('#rememberMe').prop('checked', true);
            }

            // ── Salvar Meus Dados ──
            $('#meusDadosForm').on('submit', function (e) {
                e.preventDefault();
                const btn = $('#btnSalvarDados');
                btn.prop('disabled', true).html('<span class="material-icons-round animate-spin text-base">sync</span> Salvando...');
                const formData = $(this).serializeArray();
                formData.push({ name: 'action', value: 'atualizar_dados_cliente' });
                $.ajax({
                    url: '../dinovatech/app.php', type: 'POST', dataType: 'json', data: formData,
                    success: function (response) {
                        const msg = $('#meusDadosMsg');
                        if (response.success) {
                            msg.css('color', '#059669').text(response.message);
                            setTimeout(() => msg.text(''), 4000);
                        } else {
                            msg.css('color', '#dc2626').text(response.message);
                        }
                        btn.prop('disabled', false).html('<span class="material-icons-round text-base">save</span> Salvar Dados');
                    },
                    error: function () {
                        alert('Erro ao atualizar dados.');
                        btn.prop('disabled', false).html('<span class="material-icons-round text-base">save</span> Salvar Dados');
                    }
                });
            });

            // ── Load Dashboard ──
            <?php if ($cliente_logado): ?>
                loadClienteDashboard();
            <?php endif; ?>

            function loadClienteDashboard() {
                $.ajax({
                    url: '../dinovatech/app.php', type: 'POST', dataType: 'json',
                    data: { action: 'get_cliente_dashboard_data' },
                    success: function (response) {
                        if (response.success && response.data) {
                            globalDashboardData = response.data;
                            renderDashboard(response.data);
                            renderFaturasTabs(response.data.faturas || []);
                            populateMeusDados(response.data.cliente || {}, response.data.google_service_email_hint || '');
                            if (response.data.is_vet_mode) {
                                renderCarteiraVacinasFull(response.data.vacinas || [], response.data.pets || []);
                                renderBanhoTosaSection(response.data);
                            }
                        }
                    },
                    error: function (err) { console.error('Erro ao carregar dashboard:', err); }
                });
            }

            // ── Render Dashboard ──
            function renderDashboard(data) {
                const summary = data.faturas_summary || {};
                $('#dashTotalAberto').text(formatCurrency(summary.total_aberto || 0));
                $('#dashCountAberto').text(`${summary.count_aberto || 0} pendentes`);
                $('#dashTotalPago').text(formatCurrency(summary.total_pago || 0));
                $('#dashCountPago').text(`${summary.count_pago || 0} liquidadas`);
                $('#dashCountAgendamentos').text(data.agendamentos ? data.agendamentos.length : 0);
                if (data.pets) $('#dashCountPets').text(data.pets.length);

                // Próxima Fatura Card
                if (summary.proxima_pendente) {
                    const prox = summary.proxima_pendente;
                    const hoje = new Date().toISOString().split('T')[0];
                    const isAtrasada = prox.data_vencimento < hoje;
                    const diasRestantes = Math.ceil((new Date(prox.data_vencimento) - new Date()) / 86400000);
                    const isUrgente = !isAtrasada && diasRestantes <= 3;

                    let bgClass = 'from-sky-600 to-blue-700';
                    let badge = '<span class="text-xs font-bold bg-white/20 text-white px-3 py-1 rounded-full uppercase tracking-wider">Próximo Vencimento</span>';
                    let urgBtn = '';

                    if (isAtrasada) {
                        bgClass = 'from-red-600 to-rose-700';
                        badge = '<span class="text-xs font-bold bg-white/20 text-white px-3 py-1 rounded-full uppercase tracking-wider animate-pulse">⚠️ Fatura em Atraso</span>';
                    } else if (isUrgente) {
                        bgClass = 'from-amber-500 to-orange-600';
                        badge = `<span class="text-xs font-bold bg-white/20 text-white px-3 py-1 rounded-full uppercase tracking-wider">Vence em ${diasRestantes} dia(s)</span>`;
                    }

                    $('#dashProximaFaturaCard').html(`
                        <div class="bg-gradient-to-r ${bgClass} text-white rounded-2xl shadow-md p-4 sm:p-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 sm:gap-4 ${isAtrasada ? 'fatura-urgente' : ''}">
                            <div>
                                ${badge}
                                <h3 class="text-2xl sm:text-3xl font-extrabold mt-2">${formatCurrency(prox.valor_total_fatura)}</h3>
                                <p class="text-sm text-white/80 mt-0.5">Vencimento: ${formatDate(prox.data_vencimento)}</p>
                            </div>
                            <a href="fatura.php?id=${prox.id_fatura}" class="bg-white hover:bg-gray-50 font-bold px-6 py-3 rounded-xl shadow transition inline-flex items-center gap-2 text-sm shrink-0" style="color: var(--brand-dark)">
                                <span class="material-icons-round text-base">payment</span> ${isAtrasada ? 'Regularizar Agora' : 'Pagar Agora'}
                            </a>
                        </div>
                    `).removeClass('hidden');
                } else {
                    $('#dashProximaFaturaCard').addClass('hidden');
                }

                renderAgendamentosList(data.agendamentos || []);
                renderRecorrenciasList(data.recorrencias || []);

                if (data.is_vet_mode) {
                    renderAtendimentosList(data.atendimentos || []);
                    renderVacinasBreveList(data.vacinas || []);
                } else {
                    renderFaturasBreveList(data.faturas || []);
                }
            }

            // ── Recorrências ──
            function renderRecorrenciasList(recorrencias) {
                const dashContainer = $('#dashListaRecorrencias');
                const fullContainer = $('#listaRecorrenciasFull');
                dashContainer.empty();
                if (fullContainer.length) fullContainer.empty();
                $('#dashCountRecorrenciasTag').text(`${recorrencias.length} contrato(s)`);

                if (recorrencias.length === 0) {
                    const emptyMsg = '<p class="col-span-full text-center text-gray-400 py-6 text-sm italic">Nenhuma assinatura ou contrato ativo encontrado.</p>';
                    dashContainer.html(emptyMsg);
                    if (fullContainer.length) fullContainer.html(emptyMsg);
                    return;
                }

                let htmlContent = '';
                recorrencias.forEach(rec => {
                    const servicoNome = escapeHtml(rec.nome_servico || rec.descricao_personalizada || 'Assinatura');
                    const valorTotal = (parseFloat(rec.valor_sugerido_recorrencia || 0) * (parseInt(rec.quantidade) || 1));
                    const periodo = escapeHtml(rec.tipo_periodo || 'Mês').toLowerCase();
                    const dataInicio = formatDate(rec.data_inicio_cobranca);
                    const dataFim = rec.data_fim_cobranca ? formatDate(rec.data_fim_cobranca) : 'Indeterminado';
                    const hojeStr = new Date().toISOString().split('T')[0];
                    const isExpirado = rec.data_fim_cobranca && rec.data_fim_cobranca < hojeStr;
                    const isCancelado = rec.status && /cancelad|inativ/i.test(rec.status);

                    let statusLabel = 'Ativa';
                    let statusClass = 'bg-emerald-100 text-emerald-800';
                    if (isCancelado) { statusLabel = 'Cancelado'; statusClass = 'bg-gray-100 text-gray-700'; }
                    else if (isExpirado) { statusLabel = 'Vencido'; statusClass = 'bg-red-100 text-red-800'; }

                    const hasPixRec = (rec.pix_recorrencia_status === 'APROVADA');
                    const pixBadge = hasPixRec
                        ? `<div class="inline-flex items-center gap-1.5 bg-emerald-50 text-emerald-800 border border-emerald-200 px-2.5 py-1 rounded-lg text-[11px] font-bold mt-2">
                            <span class="material-icons-round text-sm text-emerald-600">bolt</span> Débito Automático Pix Ativo</div>` : '';

                    const documentos = rec.documentos || [];
                    let docsHtml = '';
                    if (documentos.length > 0) {
                        docsHtml = '<div class="space-y-2 mt-3 pt-3 border-t border-gray-100"><span class="text-xs font-bold text-gray-500 uppercase tracking-wider block mb-1">Documentos & Termos:</span>';
                        documentos.forEach(doc => {
                            docsHtml += `<div class="p-2.5 bg-purple-50/60 rounded-xl border border-purple-100 flex items-center justify-between gap-2 text-xs">
                                <div class="flex items-center gap-2 min-w-0">
                                    <span class="material-icons-round text-purple-600 text-base shrink-0">description</span>
                                    <span class="font-semibold text-purple-950 truncate">${escapeHtml(doc.titulo)}</span>
                                </div>
                                <a href="../dinovatech/modules/Vet/documento_view.php?id=${doc.id_documento_emitido}" target="_blank" class="px-2.5 py-1 bg-white border border-purple-200 rounded-lg hover:bg-purple-100 text-purple-800 font-bold transition shrink-0 flex items-center gap-1 shadow-sm text-[11px]">
                                    <span class="material-icons-round text-xs">visibility</span> Ver</a>
                            </div>`;
                        });
                        docsHtml += '</div>';
                    }

                    htmlContent += `
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition p-5 flex flex-col justify-between overflow-hidden animate-fadeInUp">
                            <div>
                                <!-- Header do Card -->
                                <div class="flex justify-between items-start gap-2 mb-3">
                                    <div class="min-w-0 flex-1 pr-1">
                                        <h4 class="font-extrabold text-gray-900 text-sm sm:text-base leading-snug break-words" title="${servicoNome}">${servicoNome}</h4>
                                        <div class="text-[11px] text-gray-400 font-medium mt-0.5">Contrato #${rec.id_recorrencia}</div>
                                    </div>
                                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-extrabold shrink-0 whitespace-nowrap ${statusClass}">${statusLabel}</span>
                                </div>

                                <!-- Valor e Período -->
                                <div class="my-3 py-2.5 px-3 bg-gray-50/70 rounded-xl border border-gray-100">
                                    <div class="text-xl font-extrabold text-gray-900 flex flex-wrap items-baseline gap-1">
                                        <span>${formatCurrency(valorTotal)}</span>
                                        <span class="text-xs font-normal text-gray-500">/ ${periodo}</span>
                                    </div>
                                    <div class="text-xs text-gray-500 mt-1 flex flex-wrap items-center gap-x-2 gap-y-0.5">
                                        <span>Início: <strong class="text-gray-700">${dataInicio}</strong></span>
                                        <span>•</span>
                                        <span>Fim: <strong class="${isExpirado ? 'text-red-600 font-bold' : 'text-gray-700'}">${dataFim}</strong></span>
                                    </div>
                                    ${pixBadge}
                                </div>

                                ${docsHtml}
                            </div>
                        </div>`;
                });

                dashContainer.html(htmlContent);
                if (fullContainer.length) fullContainer.html(htmlContent);
            }

            // ── Agendamentos ──
            function renderAgendamentosList(agendamentos) {
                const container = $('#dashListaAgendamentos');
                container.empty();
                if (agendamentos.length === 0) {
                    container.html('<p class="text-center text-gray-400 py-4 text-sm italic">Nenhum evento encontrado.</p>');
                    return;
                }
                agendamentos.forEach(ag => {
                    const statusClass = ag.status === 'Realizado' ? 'bg-emerald-100 text-emerald-800' : 'bg-blue-100 text-blue-800';
                    const petInfo = ag.pet_nome ? `<span style="color: var(--brand)" class="font-semibold">• ${escapeHtml(ag.pet_nome)}</span>` : '';
                    const vetInfo = ag.vet_nome ? `• Vet: ${escapeHtml(ag.vet_nome)}` : '';
                    container.append(`
                        <div class="p-3 bg-gray-50 rounded-xl border border-gray-100 flex justify-between items-center text-sm">
                            <div>
                                <div class="font-bold text-gray-800">${escapeHtml(ag.titulo || 'Consulta')} ${petInfo}</div>
                                <div class="text-xs text-gray-400 mt-0.5">${formatDateTime(ag.data_inicio)} ${vetInfo}</div>
                            </div>
                            <span class="px-2.5 py-1 rounded-full text-xs font-bold ${statusClass} shrink-0 ml-2">${escapeHtml(ag.status)}</span>
                        </div>`);
                });
            }

            // ── Atendimentos Clínicos ──
            function renderAtendimentosList(atendimentos) {
                const container = $('#dashListaAtendimentos');
                container.empty();
                if (atendimentos.length === 0) {
                    container.html('<p class="text-center text-gray-400 py-4 text-sm italic">Nenhum atendimento clínico registrado.</p>');
                    return;
                }
                atendimentos.forEach(at => {
                    const queixa = at.queixa_principal ? escapeHtml(at.queixa_principal) : 'Consulta de rotina';
                    container.append(`
                        <div class="p-3.5 rounded-xl border cursor-pointer transition hover:shadow-sm text-sm"
                             style="background: var(--brand-light); border-color: rgba(0,0,0,.06)"
                             onclick="abrirModalAtendimento(${at.id_atendimento})">
                            <div class="flex justify-between items-start mb-1">
                                <span class="font-bold flex items-center gap-1" style="color: var(--brand-dark)">
                                    <span class="material-icons-round text-xs" style="color: var(--brand)">pets</span> ${escapeHtml(at.pet_nome || 'Pet')}
                                </span>
                                <span class="text-xs text-gray-400">${formatDateTime(at.data_atendimento)}</span>
                            </div>
                            <div class="text-xs text-gray-600 truncate"><strong>Motivo:</strong> ${queixa}</div>
                            <div class="flex justify-between items-center mt-2 pt-2 border-t text-xs" style="border-color: rgba(0,0,0,.06)">
                                <span class="text-gray-400">${at.vet_nome ? 'Vet: ' + escapeHtml(at.vet_nome) : ''}</span>
                                <span class="font-semibold flex items-center hover:underline" style="color: var(--brand)">Ver Prontuário <span class="material-icons-round text-xs">chevron_right</span></span>
                            </div>
                        </div>`);
                });
            }

            // ── Modal Atendimento ──
            window.abrirModalAtendimento = function (idAtendimento) {
                ['mAtendPetNome','mAtendQueixa','mAtendAnamnese','mAtendExameFisico','mAtendDiagnostico','mAtendTratamento','mAtendVetNome']
                    .forEach(id => $(`#${id}`).text('Carregando...'));
                $('#mAtendVetCrmv').text('CRMV: --');
                $('#mListaReceitas').html('<p class="text-center text-gray-400 py-4 text-sm">Carregando receitas...</p>');
                $('#mListaAnexos').html('<p class="col-span-full text-center text-gray-400 py-4 text-sm">Carregando...</p>');
                $('.m-tab-btn').removeClass('border-b-2 active').css('color','').addClass('text-gray-500 hover:text-gray-700');
                $('.m-tab-btn[data-mtarget="m-prontuario"]').addClass('border-b-2 active').css({'border-color':'var(--brand)','color':'var(--brand)'}).removeClass('text-gray-500 hover:text-gray-700');
                $('.m-tab-content').addClass('hidden');
                $('#m-prontuario').removeClass('hidden');
                $('#modalAtendimentoDetalhes').removeClass('hidden');
                $.ajax({
                    url: '../dinovatech/app.php', type: 'POST', dataType: 'json',
                    data: { action: 'get_atendimento_detalhes_cliente', id_atendimento: idAtendimento },
                    success: function (response) {
                        if (response.success && response.data) {
                            const at = response.data.atendimento;
                            const arquivos = response.data.arquivos || [];
                            const receitas = response.data.receitas || [];
                            $('#mAtendIdTag').text(`ID: #${at.id_atendimento}`);
                            $('#mAtendData').text(formatDateTime(at.data_atendimento));
                            $('#mAtendPetNome').text(at.pet_nome || 'Pet');
                            $('#mAtendPetDetalhes').text(`${at.pet_especie || ''} ${at.pet_raca ? '• '+at.pet_raca : ''} ${at.pet_peso ? '• '+at.pet_peso+' kg' : ''}`);
                            $('#mAtendVetNome').text(at.vet_nome || 'Veterinário Não Especificado');
                            $('#mAtendVetCrmv').text(`CRMV: ${at.vet_crmv || 'N/A'}${at.vet_uf_crmv ? '/'+at.vet_uf_crmv : ''}`);
                            $('#mAtendQueixa').text(at.queixa_principal || 'Não informada');
                            $('#mAtendAnamnese').text(at.anamnese || 'Não informada');
                            $('#mAtendExameFisico').text(at.exame_fisico || 'Não informado');
                            $('#mAtendDiagnostico').text(at.diagnostico || 'Sem diagnóstico');
                            $('#mAtendTratamento').text(at.conduta_tratamento || 'Sem conduta médica');
                            $('#mCountReceitas').text(receitas.length);
                            $('#mCountAnexos').text(arquivos.length);

                            // Receitas
                            let rHtml = '';
                            if (receitas.length > 0) {
                                receitas.forEach(rec => {
                                    let itensHtml = '';
                                    (rec.itens || []).forEach(it => {
                                        itensHtml += `<div class="p-2.5 bg-white rounded border border-gray-200 text-xs mb-2"><div class="font-bold text-gray-800">${escapeHtml(it.nome_medicamento)}</div><div class="text-gray-500 mt-0.5"><strong>Posologia:</strong> ${escapeHtml(it.posologia||'Não informada')}</div></div>`;
                                    });
                                    rHtml += `<div class="bg-gray-50 p-4 rounded-xl border border-gray-200">
                                        <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-2 mb-3">
                                            <div><span class="text-xs text-gray-400 font-semibold uppercase">Receita #${rec.id_receita}</span><div class="text-xs text-gray-400">${formatDateTime(rec.data_receita)}</div></div>
                                            <a href="../dinovatech/modules/Vet/receita_print.php?id=${rec.id_receita}" target="_blank" class="px-3 py-1.5 text-white rounded text-xs font-bold transition flex items-center gap-1 shadow-sm w-fit" style="background: var(--brand)">
                                                <span class="material-icons-round text-xs">print</span> Imprimir</a>
                                        </div>
                                        <div class="space-y-1">${itensHtml || '<p class="text-xs text-gray-400 italic">Sem itens.</p>'}</div></div>`;
                                });
                            } else {
                                rHtml = '<p class="text-center text-gray-400 py-6 text-sm italic">Nenhuma receita prescrita.</p>';
                            }
                            $('#mListaReceitas').html(rHtml);

                            // Anexos
                            let aHtml = '';
                            if (arquivos.length > 0) {
                                arquivos.forEach(arq => {
                                    aHtml += `<div class="p-3 bg-gray-50 rounded-lg border border-gray-200 flex items-center justify-between text-xs">
                                        <div class="flex items-center gap-2 truncate mr-2"><span class="material-icons-round text-base" style="color: var(--brand)">insert_drive_file</span><span class="font-medium text-gray-800 truncate">${escapeHtml(arq.nome_original)}</span></div>
                                        <a href="${arq.url_publica}" target="_blank" class="px-2.5 py-1 bg-white border border-gray-300 rounded hover:bg-gray-100 text-gray-700 font-medium flex items-center gap-1 transition shrink-0">
                                            <span class="material-icons-round text-xs">open_in_new</span> Abrir</a></div>`;
                                });
                            } else {
                                aHtml = '<p class="col-span-full text-center text-gray-400 py-6 text-sm italic">Nenhum exame ou arquivo anexado.</p>';
                            }
                            $('#mListaAnexos').html(aHtml);
                        } else {
                            alert(response.message || 'Erro ao carregar.');
                            $('#modalAtendimentoDetalhes').addClass('hidden');
                        }
                    }
                });
            };

            $(document).on('click', '.m-tab-btn', function () {
                $('.m-tab-btn').removeClass('border-b-2 active').css('color','').addClass('text-gray-500');
                $(this).removeClass('text-gray-500').addClass('border-b-2 active').css({'border-color':'var(--brand)','color':'var(--brand)'});
                const target = $(this).data('mtarget');
                $('.m-tab-content').addClass('hidden');
                $(`#${target}`).removeClass('hidden');
            });

            $(document).on('click', '#btnCloseModalAtendimento, #btnFecharModalBottom', function () {
                $('#modalAtendimentoDetalhes').addClass('hidden');
            });

            // ── Modal Meus Pets ──
            let activePetCharts = {};
            window.abrirModalMeusPets = function () {
                if (!globalDashboardData || !globalDashboardData.pets) { alert('Dados dos pets não carregados.'); return; }
                const pets = globalDashboardData.pets;
                const container = $('#mListaMeusPetsCards');
                container.empty();
                Object.keys(activePetCharts).forEach(key => { try { activePetCharts[key].destroy(); } catch(e){} });
                activePetCharts = {};

                if (pets.length === 0) {
                    container.html('<p class="text-center text-gray-400 py-8">Nenhum pet cadastrado.</p>');
                    $('#modalMeusPetsDetalhes').removeClass('hidden');
                    return;
                }

                pets.forEach(pet => {
                    const especieRaca = [pet.especie, pet.raca].filter(Boolean).join(' • ') || 'Espécie/Raça não informada';
                    const sexoBadge = pet.sexo === 'M'
                        ? '<span class="px-2.5 py-1 rounded text-xs font-semibold bg-blue-100 text-blue-800">♂ Macho</span>'
                        : (pet.sexo === 'F' ? '<span class="px-2.5 py-1 rounded text-xs font-semibold bg-pink-100 text-pink-800">♀ Fêmea</span>' : '');

                    const totalConsultas = pet.total_atendimentos || 0;
                    const dataUltimo = pet.ultimo_atendimento ? formatDateTime(pet.ultimo_atendimento) : 'Sem registros';
                    const queixaUltimo = pet.ultimo_atendimento_queixa ? escapeHtml(pet.ultimo_atendimento_queixa) : 'Nenhuma queixa';

                    const vacinasPet = (globalDashboardData.vacinas || []).filter(v => v.id_pet == pet.id_pet);
                    const hoje = new Date().toISOString().split('T')[0];
                    const temVencidas = vacinasPet.some(v => v.data_vencimento < hoje);

                    let vacinaStatusBadge = '<span class="text-gray-400 font-medium">Nenhuma vacina cadastrada</span>';
                    if (vacinasPet.length > 0) {
                        vacinaStatusBadge = temVencidas
                            ? '<span class="text-red-600 font-bold flex items-center gap-1"><span class="material-icons-round text-xs">warning</span> Vacina Vencida</span>'
                            : `<span class="text-emerald-700 font-bold flex items-center gap-1"><span class="material-icons-round text-xs">check_circle</span> Em Dia (${vacinasPet.length})</span>`;
                    }

                    // Avatar
                    let fotoUrl = pet.foto_url || '';
                    if (fotoUrl && !fotoUrl.startsWith('http') && !fotoUrl.startsWith('/')) {
                        fotoUrl = '../dinovatech/' + fotoUrl;
                    }
                    const avatarHtml = fotoUrl
                        ? `<img src="${fotoUrl}" class="pet-avatar" alt="Foto de ${escapeHtml(pet.nome)}">`
                        : `<div class="pet-avatar-placeholder" style="background: var(--brand)">
                             <span class="material-icons-round text-white text-2xl">pets</span>
                           </div>`;

                    // Peso
                    const historicoPeso = pet.historico_peso || [];
                    const canvasId = `chartPesoPet_${pet.id_pet}`;
                    let blocoPesoHtml = '';
                    if (historicoPeso.length >= 2) {
                        blocoPesoHtml = `<div class="mt-4 pt-4 border-t border-gray-100">
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-xs font-bold text-gray-600 uppercase tracking-wider flex items-center gap-1">
                                    <span class="material-icons-round text-base" style="color: var(--brand)">show_chart</span> Evolução do Peso (kg)</span>
                                <span class="text-xs text-gray-400">${historicoPeso.length} pesagens</span>
                            </div>
                            <div class="bg-white p-3 rounded-xl border border-gray-200 h-48 relative"><canvas id="${canvasId}"></canvas></div>
                        </div>`;
                    } else if (historicoPeso.length === 1) {
                        const pU = historicoPeso[0];
                        blocoPesoHtml = `<div class="mt-4 pt-4 border-t border-gray-100 flex items-center justify-between rounded-xl p-3 border" style="background: var(--brand-light)">
                            <div class="flex items-center gap-2">
                                <span class="material-icons-round" style="color: var(--brand)">monitor_weight</span>
                                <span class="text-xs font-semibold" style="color: var(--brand-dark)">Última Pesagem:</span>
                            </div>
                            <span class="text-sm font-bold" style="color: var(--brand-dark)">${pU.peso.toFixed(2)} kg <span class="text-xs font-normal text-gray-400">(${formatDate(pU.data)})</span></span>
                        </div>`;
                    } else {
                        blocoPesoHtml = '<div class="mt-4 pt-4 border-t border-gray-100 text-xs text-gray-400 italic">Nenhuma pesagem registrada.</div>';
                    }

                    container.append(`
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden animate-fadeInUp">
                            <div class="p-4 border-b flex flex-col sm:flex-row justify-between sm:items-center gap-3" style="background: var(--brand-light); border-color: rgba(0,0,0,.06)">
                                <div class="flex items-center gap-3">
                                    <div class="relative shrink-0">
                                        ${avatarHtml}
                                        <label class="upload-foto-pet-btn" title="Alterar foto do pet" for="inputFotoPet_${pet.id_pet}">
                                            <span class="material-icons-round text-[11px]">photo_camera</span>
                                        </label>
                                        <input type="file" id="inputFotoPet_${pet.id_pet}" class="hidden foto-pet-input"
                                               accept="image/jpeg,image/jfif,image/pjpeg,image/png,image/webp,.jfif" data-pet-id="${pet.id_pet}">
                                    </div>
                                    <div>
                                        <h4 class="text-lg font-bold text-gray-800">${escapeHtml(pet.nome)}</h4>
                                        <span class="text-xs text-gray-500">${escapeHtml(especieRaca)}</span>
                                    </div>
                                </div>
                                <div>${sexoBadge}</div>
                            </div>
                            <div class="p-5 space-y-4">
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
                                    <div class="p-3 bg-gray-50 rounded-xl border border-gray-100">
                                        <span class="text-gray-400 block font-medium uppercase mb-0.5">Consultas</span>
                                        <strong class="text-base text-gray-800">${totalConsultas} atend.</strong>
                                    </div>
                                    <div class="p-3 bg-gray-50 rounded-xl border border-gray-100">
                                        <span class="text-gray-400 block font-medium uppercase mb-0.5">Última Consulta</span>
                                        <strong class="text-gray-800">${dataUltimo}</strong>
                                        <div class="text-gray-400 truncate mt-0.5">${queixaUltimo}</div>
                                    </div>
                                    <div class="p-3 rounded-xl border cursor-pointer transition group hover:shadow-sm" style="background: var(--brand-light); border-color: rgba(0,0,0,.06)" onclick="irParaCarteiraVacinas()">
                                        <span class="block font-semibold uppercase mb-0.5 text-[10px]" style="color: var(--brand)">Vacinação</span>
                                        <div class="text-xs">${vacinaStatusBadge}</div>
                                        <span class="text-xs font-bold group-hover:underline flex items-center gap-0.5 mt-1" style="color: var(--brand)">Ver Carteira <span class="material-icons-round text-xs">arrow_forward</span></span>
                                    </div>
                                </div>
                                ${blocoPesoHtml}
                            </div>
                        </div>`);
                });

                $('#modalMeusPetsDetalhes').removeClass('hidden');

                // Gráficos de peso
                setTimeout(() => {
                    pets.forEach(pet => {
                        const historicoPeso = pet.historico_peso || [];
                        if (historicoPeso.length >= 2) {
                            const ctx = document.getElementById(`chartPesoPet_${pet.id_pet}`);
                            if (ctx) {
                                const brandColor = getComputedStyle(document.documentElement).getPropertyValue('--brand').trim() || '#059669';
                                activePetCharts[pet.id_pet] = new Chart(ctx, {
                                    type: 'line',
                                    data: {
                                        labels: historicoPeso.map(h => formatDate(h.data)),
                                        datasets: [{
                                            label: 'Peso (kg)',
                                            data: historicoPeso.map(h => h.peso),
                                            borderColor: brandColor,
                                            backgroundColor: `${brandColor}18`,
                                            borderWidth: 3,
                                            pointBackgroundColor: brandColor,
                                            pointRadius: 5, pointHoverRadius: 7,
                                            tension: 0.35, fill: true
                                        }]
                                    },
                                    options: {
                                        responsive: true, maintainAspectRatio: false,
                                        plugins: { legend: { display: false },
                                            tooltip: { callbacks: { label: ctx => `Peso: ${ctx.parsed.y.toFixed(2)} kg` } } },
                                        scales: { y: { beginAtZero: false, ticks: { callback: v => v + ' kg' } } }
                                    }
                                });
                            }
                        }
                    });
                }, 150);
            };

            // ── Compressão de Imagens Client-Side (JS Canvas) ──
            function compressImage(file, maxWidth, maxHeight, quality, callback) {
                if (!file || !file.type || !file.type.startsWith('image/')) {
                    callback(file);
                    return;
                }
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = new Image();
                    img.onload = function() {
                        let width = img.width;
                        let height = img.height;
                        if (width > maxWidth || height > maxHeight) {
                            if (width / height > maxWidth / maxHeight) {
                                height = Math.round((height * maxWidth) / width);
                                width = maxWidth;
                            } else {
                                width = Math.round((width * maxHeight) / height);
                                height = maxHeight;
                            }
                        }
                        const canvas = document.createElement('canvas');
                        canvas.width = width;
                        canvas.height = height;
                        const ctx = canvas.getContext('2d');
                        ctx.drawImage(img, 0, 0, width, height);

                        canvas.toBlob(function(blob) {
                            if (blob) {
                                const compressedFile = new File([blob], file.name || 'image.jpg', {
                                    type: 'image/jpeg',
                                    lastModified: Date.now()
                                });
                                callback(compressedFile);
                            } else {
                                callback(file);
                            }
                        }, 'image/jpeg', quality);
                    };
                    img.onerror = function() { callback(file); };
                    img.src = e.target.result;
                };
                reader.onerror = function() { callback(file); };
                reader.readAsDataURL(file);
            }

            // ── Upload Foto Pet (Comprimido no navegador) ──
            $(document).on('change', '.foto-pet-input', function () {
                const rawFile = this.files[0];
                const idPet = $(this).data('pet-id');
                if (!rawFile || !idPet) return;

                const label = $(this).prev('.upload-foto-pet-btn');
                label.html('<span class="material-icons-round animate-spin text-[11px]">sync</span>');

                compressImage(rawFile, 800, 800, 0.8, function(file) {
                    const formData = new FormData();
                    formData.append('action', 'upload_foto_pet');
                    formData.append('id_pet', idPet);
                    formData.append('foto', file);

                    $.ajax({
                        url: '../dinovatech/app.php', type: 'POST',
                        data: formData, processData: false, contentType: false,
                        success: function (res) {
                            label.html('<span class="material-icons-round text-[11px]">photo_camera</span>');
                            if (res.success && res.url) {
                                let finalUrl = res.url;
                                if (!finalUrl.startsWith('http') && !finalUrl.startsWith('/')) {
                                    finalUrl = '../dinovatech/' + finalUrl;
                                }
                                const wrapperRelative = label.closest('.relative');
                                wrapperRelative.find('img.pet-avatar').remove();
                                wrapperRelative.find('.pet-avatar-placeholder').remove();
                                wrapperRelative.prepend(`<img src="${finalUrl}?t=${Date.now()}" class="pet-avatar" alt="Foto do pet">`);
                                if (globalDashboardData && globalDashboardData.pets) {
                                    const p = globalDashboardData.pets.find(p => p.id_pet == idPet);
                                    if (p) p.foto_url = res.url;
                                }
                            } else {
                                alert(res.message || 'Erro ao enviar foto.');
                            }
                        },
                        error: function() {
                            label.html('<span class="material-icons-round text-[11px]">photo_camera</span>');
                            alert('Erro de comunicação.');
                        },
                        dataType: 'json'
                    });
                });
            });

            // ── Upload Foto Perfil Cliente (Comprimido no navegador) ──
            $(document).on('change', '#inputFotoPerfilCliente', function () {
                const rawFile = this.files[0];
                if (!rawFile) return;

                const msgEl = $('#msgUploadFotoCliente');
                msgEl.attr('class', 'text-xs font-bold text-blue-600 animate-pulse').text('Processando e comprimindo foto...');

                compressImage(rawFile, 800, 800, 0.8, function(file) {
                    const formData = new FormData();
                    formData.append('action', 'upload_foto_cliente');
                    formData.append('foto', file);

                    $.ajax({
                        url: '../dinovatech/app.php', type: 'POST',
                        data: formData, processData: false, contentType: false,
                        success: function (res) {
                            if (res.success && res.url) {
                                msgEl.attr('class', 'text-xs font-bold text-emerald-600').text('Foto de perfil atualizada!');
                                setTimeout(() => msgEl.text(''), 4000);

                                let finalUrl = res.url;
                                if (!finalUrl.startsWith('http') && !finalUrl.startsWith('/')) {
                                    finalUrl = '../dinovatech/' + finalUrl;
                                }
                                const cacheBustUrl = finalUrl + '?t=' + Date.now();

                                // Atualiza avatar na tab Meus Dados
                                $('#containerPerfilAvatarCliente').html(`<img src="${cacheBustUrl}" id="imgPerfilCliente" class="w-full h-full object-cover" alt="Foto de Perfil">`);

                                // Atualiza avatar no Header
                                if ($('#headerAvatarImgDesktop').length) {
                                    $('#headerAvatarImgDesktop').attr('src', cacheBustUrl);
                                } else if ($('#headerAvatarInitialDesktop').length) {
                                    $('#headerAvatarInitialDesktop').replaceWith(`<img src="${cacheBustUrl}" id="headerAvatarImgDesktop" class="w-8 h-8 rounded-full object-cover border border-white/30 shadow-sm shrink-0" alt="Avatar">`);
                                }

                                if ($('#headerAvatarImgMobile').length) {
                                    $('#headerAvatarImgMobile').attr('src', cacheBustUrl);
                                } else if ($('#headerAvatarInitialMobile').length) {
                                    $('#headerAvatarInitialMobile').replaceWith(`<img src="${cacheBustUrl}" id="headerAvatarImgMobile" class="flex md:hidden w-8 h-8 rounded-full object-cover border border-white/30 shadow-sm shrink-0" alt="Avatar">`);
                                }
                            } else {
                                msgEl.attr('class', 'text-xs font-bold text-red-600').text(res.message || 'Erro ao atualizar foto.');
                            }
                        },
                        error: function () {
                            msgEl.attr('class', 'text-xs font-bold text-red-600').text('Erro de comunicação.');
                        },
                        dataType: 'json'
                    });
                });
            });

            $(document).on('click', '#btnCloseModalPets, #btnFecharModalPetsBottom', function () {
                $('#modalMeusPetsDetalhes').addClass('hidden');
            });

            window.irParaCarteiraVacinas = function () {
                $('#modalMeusPetsDetalhes').addClass('hidden');
                activateTab('vacinas');
            };

            // ── Vacinas Preview ──
            function renderVacinasBreveList(vacinas) {
                const container = $('#dashListaVacinasBreve');
                container.empty();
                if (vacinas.length === 0) {
                    container.html('<p class="col-span-full text-center text-gray-400 py-4 text-sm italic">Nenhuma vacina registrada.</p>');
                    return;
                }
                const hoje = new Date().toISOString().split('T')[0];
                vacinas.slice(0, 6).forEach(vc => {
                    const isVencida = vc.data_vencimento < hoje;
                    const bg = isVencida ? 'bg-red-50 border-red-200 text-red-800' : 'border-gray-100';
                    const statusIcon = isVencida ? '🔴' : '🟢';
                    container.append(`
                        <div class="vacina-card border ${bg} bg-white">
                            <div class="flex items-center justify-between mb-2">
                                <div class="font-bold text-sm text-gray-800">${statusIcon} ${escapeHtml(vc.pet_nome)}</div>
                                <span class="text-xs ${isVencida ? 'text-red-600 font-bold' : 'text-gray-400'}">${formatDate(vc.data_vencimento)}</span>
                            </div>
                            <div class="text-xs text-gray-500 mb-2">Vacina: <strong class="text-gray-700">${escapeHtml(vc.vacina_nome)}</strong></div>
                        </div>`);
                });
            }

            // ── Últimas Faturas (TI) ──
            function renderFaturasBreveList(faturas) {
                const container = $('#dashListaFaturasBreve');
                container.empty();
                if (faturas.length === 0) {
                    container.html('<p class="text-center text-gray-400 py-4 text-sm italic">Nenhuma fatura cadastrada.</p>');
                    return;
                }
                faturas.slice(0, 5).forEach(f => {
                    const isLiq = f.status === 'Liquidada';
                    const statusClass = isLiq ? 'status-pago' : 'status-aberto';
                    const statusLabel = isLiq ? 'Paga' : 'Aberta';
                    container.append(`
                        <div class="fatura-item">
                            <span class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0 ${isLiq ? 'bg-emerald-50' : 'bg-amber-50'}">
                                <span class="material-icons-round text-lg ${isLiq ? 'text-emerald-500' : 'text-amber-500'}">${isLiq ? 'check_circle' : 'pending'}</span>
                            </span>
                            <div class="flex-1 min-w-0">
                                <div class="font-bold text-gray-800 text-sm">${formatCurrency(f.valor_total_fatura)}</div>
                                <div class="text-xs text-gray-400">Venc: ${formatDate(f.data_vencimento)} • #${f.id_fatura}</div>
                            </div>
                            <span class="text-[10px] font-bold px-2.5 py-1 rounded-full shrink-0 ${statusClass}">${statusLabel}</span>
                            <a href="fatura.php?id=${f.id_fatura}" class="shrink-0 text-xs font-bold px-3 py-1.5 rounded-lg transition" style="background: var(--brand-light); color: var(--brand-dark)">Ver</a>
                        </div>`);
                });
            }

            // ── Tab Faturas (Em Aberto / Pagas) ──
            function renderFaturasTabs(faturas) {
                const abertasContainer = $('#listAbertas');
                const pagasContainer = $('#listPagas');
                abertasContainer.empty();
                pagasContainer.empty();
                let hasAbertas = false, hasPagas = false;

                faturas.forEach(fatura => {
                    const isLiquidada = fatura.status === 'Liquidada';
                    const hoje = new Date().toISOString().split('T')[0];
                    const isAtrasada = !isLiquidada && fatura.data_vencimento < hoje;

                    const statusLabel = isLiquidada ? 'Paga' : (isAtrasada ? 'Atrasada' : 'Em Aberto');
                    const statusClass = isLiquidada ? 'status-pago' : (isAtrasada ? 'status-atrasado' : 'status-aberto');
                    const icon = isLiquidada ? 'check_circle' : (isAtrasada ? 'warning' : 'pending');
                    const iconColor = isLiquidada ? 'text-emerald-500' : (isAtrasada ? 'text-red-500' : 'text-amber-500');
                    const iconBg  = isLiquidada ? 'bg-emerald-50' : (isAtrasada ? 'bg-red-50' : 'bg-amber-50');

                    const html = `
                        <div class="fatura-item animate-fadeInUp">
                            <div class="w-12 h-12 rounded-2xl ${iconBg} flex items-center justify-center shrink-0">
                                <span class="material-icons-round text-2xl ${iconColor}">${icon}</span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="font-extrabold text-gray-800">${formatCurrency(fatura.valor_total_fatura)}</div>
                                <div class="text-xs text-gray-400 mt-0.5">Vencimento: ${formatDate(fatura.data_vencimento)}</div>
                                <div class="text-[10px] text-gray-300 mt-0.5">Fatura #${fatura.id_fatura}</div>
                            </div>
                            <div class="flex flex-col items-end gap-2 shrink-0">
                                <span class="text-[10px] font-bold px-2.5 py-1 rounded-full ${statusClass}">${statusLabel}</span>
                                <a href="fatura.php?id=${fatura.id_fatura}"
                                   class="text-xs font-bold px-4 py-2 rounded-xl transition"
                                   style="background: ${isLiquidada ? '#f3f4f6' : 'var(--btn-primary)'}; color: ${isLiquidada ? '#374151' : '#fff'}">
                                    ${isLiquidada ? 'Ver Detalhes' : 'Pagar Agora'}
                                </a>
                            </div>
                        </div>`;

                    if (isLiquidada) { pagasContainer.append(html); hasPagas = true; }
                    else { abertasContainer.append(html); hasAbertas = true; }
                });

                if (!hasAbertas) abertasContainer.html('<p class="text-center py-10 text-gray-400">Nenhuma fatura em aberto. 🎉</p>');
                if (!hasPagas) pagasContainer.html('<p class="text-center py-10 text-gray-400">Nenhuma fatura paga registrada.</p>');
            }

            // ── Carteira de Vacinas Full ──
            function renderCarteiraVacinasFull(vacinas, pets) {
                const container = $('#listaCarteiraVacinasFull');
                container.empty();
                if (!pets || pets.length === 0) {
                    container.html('<p class="text-center text-gray-400 py-8">Nenhum pet cadastrado.</p>');
                    return;
                }

                pets.forEach(pet => {
                    const petVacinas = vacinas.filter(v => v.id_pet == pet.id_pet);
                    const hoje = new Date().toISOString().split('T')[0];
                    let fotoUrl = pet.foto_url || '';
                    if (fotoUrl && !fotoUrl.startsWith('http') && !fotoUrl.startsWith('/')) {
                        fotoUrl = '../dinovatech/' + fotoUrl;
                    }
                    const avatarSmall = fotoUrl
                        ? `<img src="${fotoUrl}" class="w-10 h-10 rounded-full object-cover border-2 border-white shadow-sm" alt="${escapeHtml(pet.nome)}">`
                        : `<div class="w-10 h-10 rounded-full flex items-center justify-center border-2 border-white shadow-sm" style="background: var(--brand)"><span class="material-icons-round text-white text-xl">pets</span></div>`;

                    let vacinasHtml = '';
                    if (petVacinas.length > 0) {
                        petVacinas.forEach(vc => {
                            const isVencida = vc.data_vencimento < hoje;
                            // progress bar temporal
                            const dtAplic = new Date(vc.data_aplicacao).getTime();
                            const dtVenc = new Date(vc.data_vencimento).getTime();
                            const dtHoje = new Date().getTime();
                            const pct = Math.min(100, Math.max(0, Math.round(((dtHoje - dtAplic) / (dtVenc - dtAplic)) * 100)));
                            const barColor = isVencida ? '#ef4444' : (pct > 75 ? '#f59e0b' : 'var(--brand)');
                            const statusBadge = isVencida
                                ? '<span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-red-100 text-red-700">🔴 Vencida</span>'
                                : (pct > 75
                                    ? '<span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-700">🟡 Vence em breve</span>'
                                    : '<span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700">🟢 Em dia</span>');

                            vacinasHtml += `
                                <div class="vacina-card bg-white border border-gray-100 shadow-sm">
                                    <div class="flex justify-between items-center mb-2">
                                        <span class="font-bold text-gray-800 text-sm">${escapeHtml(vc.vacina_nome)}</span>
                                        ${statusBadge}
                                    </div>
                                    <div class="text-xs text-gray-400 mb-2 flex gap-3">
                                        <span>Aplicação: <strong class="text-gray-600">${formatDate(vc.data_aplicacao)}</strong></span>
                                        <span>Vencimento: <strong class="${isVencida ? 'text-red-600' : 'text-gray-600'}">${formatDate(vc.data_vencimento)}</strong></span>
                                    </div>
                                    <div class="vacina-progress">
                                        <div class="vacina-progress-bar" style="width: ${pct}%; background: ${barColor}"></div>
                                    </div>
                                </div>`;
                        });
                    } else {
                        vacinasHtml = '<p class="text-center text-gray-400 py-4 text-sm italic">Nenhuma vacina registrada para este pet.</p>';
                    }

                    container.append(`
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden animate-fadeInUp">
                            <div class="p-4 border-b flex items-center justify-between" style="background: var(--brand-light); border-color: rgba(0,0,0,.06)">
                                <div class="flex items-center gap-3">
                                    ${avatarSmall}
                                    <div>
                                        <h4 class="font-bold text-gray-800">${escapeHtml(pet.nome)}</h4>
                                        <span class="text-xs text-gray-500">${escapeHtml(pet.especie || 'Pet')} ${pet.raca ? '• '+escapeHtml(pet.raca) : ''}</span>
                                    </div>
                                </div>
                                <span class="text-xs font-semibold px-3 py-1 rounded-full" style="background: var(--brand); color:#fff">${petVacinas.length} Vacina(s)</span>
                            </div>
                            <div class="p-4 grid grid-cols-1 sm:grid-cols-2 gap-3">${vacinasHtml}</div>
                        </div>`);
                });
            }

            // ── Banho & Tosa ──
            function renderBanhoTosaSection(data) {
                // Ao vivo
                const aoVivo = data.banho_ao_vivo || [];
                const containerAoVivo = $('#containerBanhoAoVivo');
                const listaAoVivo = $('#listaPetsAoVivo');
                listaAoVivo.empty();

                const etapas = ['aguardando','em_banho','secagem','tosa_finalizacao','pronto'];
                const etapaLabels = ['Recepção','Em Banho 🛁','Secagem 💨','Tosa ✂️','Pronto! 🐾'];

                if (aoVivo.length > 0) {
                    containerAoVivo.removeClass('hidden');
                    aoVivo.forEach(f => {
                        const etapaAtualIdx = etapas.indexOf(f.etapa);
                        let timelineHtml = '<div class="flex items-center w-full mt-3">';
                        etapas.forEach((et, i) => {
                            const isDone   = i < etapaAtualIdx;
                            const isActive = i === etapaAtualIdx;
                            timelineHtml += `<div class="timeline-step">
                                <div class="timeline-dot ${isDone ? 'done' : (isActive ? 'active' : '')}">
                                    <span class="material-icons-round text-sm">${isDone ? 'check' : (isActive ? 'play_arrow' : 'radio_button_unchecked')}</span>
                                </div>
                                <span class="text-[9px] text-white/60 mt-1 text-center leading-tight">${etapaLabels[i]}</span>
                            </div>`;
                            if (i < etapas.length - 1) {
                                timelineHtml += `<div class="timeline-line ${isDone ? 'done' : ''}"></div>`;
                            }
                        });
                        timelineHtml += '</div>';

                        listaAoVivo.append(`
                            <div class="bg-white/10 backdrop-blur rounded-2xl p-4 border border-white/10">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="font-extrabold text-white text-lg">${escapeHtml(f.pet_nome)}</span>
                                    <span class="text-[10px] px-2 py-0.5 rounded-full bg-white/20 text-white font-semibold">Porte ${f.porte || 'P'}</span>
                                </div>
                                <span class="text-xs text-emerald-200">Entrada às ${f.horario_entrada_fmt || '--:--'}</span>
                                ${timelineHtml}
                            </div>`);
                    });
                } else {
                    containerAoVivo.addClass('hidden');
                }

                // Pacotes
                const pacotes = data.pacotes || [];
                const containerPac = $('#listaPacotesCliente');
                containerPac.empty();

                if (pacotes.length > 0) {
                    pacotes.forEach(p => {
                        let saldosHtml = '';
                        (p.saldos || []).forEach(sal => {
                            const total = parseInt(sal.qtd_total) || 1;
                            const util  = parseInt(sal.qtd_utilizada) || 0;
                            const rest  = parseInt(sal.saldo_restante) || 0;
                            const perc  = Math.round((util / total) * 100);
                            saldosHtml += `
                                <div class="space-y-1">
                                    <div class="flex justify-between text-xs font-medium text-gray-700">
                                        <span>${escapeHtml(sal.nome_servico)}</span>
                                        <span class="font-bold ${rest > 0 ? '' : 'text-gray-400'}" style="${rest > 0 ? 'color: var(--brand)' : ''}">${rest} de ${total} restante(s)</span>
                                    </div>
                                    <div class="vacina-progress">
                                        <div class="vacina-progress-bar" style="width: ${100-perc}%; background: var(--brand)"></div>
                                    </div>
                                </div>`;
                        });

                        const petInfo = p.pet_vinculado
                            ? `<span class="px-2 py-0.5 rounded-full text-[11px] font-bold border" style="background: var(--brand-light); color: var(--brand-dark); border-color: var(--brand)">🐾 Exclusivo para ${escapeHtml(p.nome_pet_vinculado)}</span>`
                            : '<span class="px-2 py-0.5 rounded-full text-[11px] font-medium bg-gray-100 text-gray-500">Compartilhado entre todos os pets</span>';

                        containerPac.append(`
                            <div class="bg-gray-50 rounded-2xl p-5 border border-gray-200/80 shadow-sm flex flex-col justify-between">
                                <div>
                                    <div class="flex items-start justify-between gap-2 mb-3">
                                        <div class="flex items-center gap-3">
                                            <div class="w-11 h-11 rounded-xl text-white flex items-center justify-center shadow-sm" style="background: var(--brand)">
                                                <span class="material-icons-round text-2xl">${escapeHtml(p.icone || 'card_giftcard')}</span>
                                            </div>
                                            <div>
                                                <h5 class="font-bold text-gray-900 text-sm leading-snug">${escapeHtml(p.nome_pacote)}</h5>
                                                <span class="text-xs text-gray-400">Adquirido em ${formatDate(p.data_aquisicao)}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-3">${petInfo}</div>
                                    <div class="space-y-3 pt-3 border-t border-gray-200">${saldosHtml}</div>
                                </div>
                                <div class="pt-4 border-t border-gray-200 mt-4 flex justify-end">
                                    <button type="button" onclick="abrirModalExtratoCliente(${p.id_cliente_pacote})"
                                        class="px-3.5 py-1.5 bg-slate-800 hover:bg-slate-900 text-white rounded-xl text-xs font-bold transition flex items-center gap-1 shadow-sm">
                                        <span class="material-icons-round text-xs">receipt_long</span> Ver Extrato</button>
                                </div>
                            </div>`);
                    });
                } else {
                    containerPac.html(`
                        <div class="col-span-full text-center py-8 bg-gray-50 rounded-2xl border border-dashed border-gray-200">
                            <span class="material-icons-round text-4xl text-gray-300 mb-2">card_giftcard</span>
                            <p class="text-gray-400 text-sm font-medium">Nenhum pacote ativo.</p>
                            <p class="text-xs text-gray-400 mt-1">Converse com a recepção para conhecer nossos planos!</p>
                        </div>`);
                }
            }

            // ── Extrato Pacote ──
            window.abrirModalExtratoCliente = function (idContrato) {
                $('#extratoClienteConteudo').html('<div class="text-center py-8 text-gray-400">Carregando extrato...</div>');
                $('#modalExtratoPacoteCliente').removeClass('hidden');
                $.getJSON('../dinovatech/app.php', { action: 'get_extrato_pacote', id_cliente_pacote: idContrato }, function (res) {
                    if (!res.success) { $('#extratoClienteConteudo').html(`<div class="p-4 bg-red-50 text-red-600 rounded-lg text-xs">${res.message}</div>`); return; }
                    const p = res.pacote;
                    const petBadge = p.nome_pet_exclusivo
                        ? `<span class="px-2 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">🐾 Exclusivo: ${p.nome_pet_exclusivo}</span>`
                        : '<span class="px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700">Compartilhado</span>';
                    let saldosHtml = '';
                    res.saldos.forEach(s => {
                        const pct = Math.round((s.qtd_utilizada / s.qtd_total) * 100);
                        saldosHtml += `<div class="bg-gray-50 rounded-xl p-3 border border-gray-100">
                            <div class="flex justify-between items-center text-xs mb-1"><span class="font-bold text-gray-800">${s.nome_servico}</span><span class="font-bold" style="color: var(--brand)">${s.saldo_restante} restante(s) (${s.qtd_utilizada}/${s.qtd_total})</span></div>
                            <div class="vacina-progress"><div class="vacina-progress-bar" style="width: ${pct}%; background: var(--brand)"></div></div></div>`;
                    });
                    let consumosHtml = res.consumos && res.consumos.length > 0
                        ? `<div class="space-y-2">${res.consumos.map(c => `
                            <div class="flex items-center justify-between p-3 bg-white rounded-xl border border-gray-100 shadow-sm text-xs">
                                <div class="flex items-center gap-2.5">
                                    <span class="w-7 h-7 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold">✓</span>
                                    <div><span class="font-bold text-gray-800 block">${c.nome_servico} • ${c.nome_pet}</span><span class="text-[11px] text-gray-400">${c.observacao || 'Utilizado'}</span></div>
                                </div>
                                <span class="font-mono text-gray-400">${c.data_consumo_fmt}</span></div>`).join('')}</div>`
                        : '<p class="text-xs text-gray-400 italic bg-gray-50 p-4 rounded-xl text-center">Nenhuma utilização registrada.</p>';

                    $('#extratoClienteConteudo').html(`
                        <div class="rounded-2xl p-5 shadow-lg text-white" style="background: linear-gradient(135deg, var(--header-from), var(--brand))">
                            <div class="flex justify-between items-start mb-2">
                                <div><span class="text-white/70 font-bold text-xs uppercase tracking-wider block">Meu Pacote</span><h4 class="text-xl font-extrabold">${p.nome_pacote}</h4></div>
                                <span class="text-sm font-extrabold text-white/80">R$ ${parseFloat(p.valor_total).toFixed(2).replace('.', ',')}</span>
                            </div>
                            <div class="flex items-center justify-between pt-3 border-t border-white/20 text-xs">
                                <span class="text-white/70">Adquirido em: ${p.data_aquisicao_fmt}</span>${petBadge}</div>
                        </div>
                        <div><h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Créditos Restantes</h4><div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">${saldosHtml}</div></div>
                        <div><h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Histórico de Utilizações</h4>${consumosHtml}</div>`);
                });
            };

            // ── Modal Agendar Banho ──
            let isOpeningModalBanho = false;

            window.abrirModalAgendarBanhoCliente = function () {
                if (!globalDashboardData) return;
                const pets = globalDashboardData.pets || [];
                const servicos = globalDashboardData.servicos_banho || [];
                const selectPet = $('#modalAgendarPet');
                selectPet.empty().append('<option value="">Selecione seu pet...</option>');
                pets.forEach(p => selectPet.append(`<option value="${p.id_pet}">${escapeHtml(p.nome)} (${p.porte ? 'Porte '+p.porte : 'Pet'})</option>`));
                const selectSrv = $('#modalAgendarServico');
                selectSrv.empty().append('<option value="">Selecione o serviço...</option>');
                servicos.forEach(s => selectSrv.append(`<option value="${s.id_servico}">${escapeHtml(s.nome_servico)} (${s.duracao_minutos}m)</option>`));
                const pad = n => n < 10 ? '0'+n : n;
                const now = new Date();
                const dtHoje = `${now.getFullYear()}-${pad(now.getMonth()+1)}-${pad(now.getDate())}`;
                $('#modalAgendarDataDia').attr('min', dtHoje).val(dtHoje);
                $('#modalAgendarHoraSelecionada').val('');
                $('#btnConfirmarAgendamento').prop('disabled', true);
                $('#boxAgendarSaldoBadge, #modalAgendarMsg').addClass('hidden');
                $('#modalAgendarObservacoes').val('');
                $('#modalAgendarBanho').removeClass('hidden');
                isOpeningModalBanho = true;
                if (pets.length === 1) selectPet.val(pets[0].id_pet).trigger('change');
                else carregarSlotsDisponiveis(true);
            };

            window.fecharModalAgendarBanhoCliente = function () { $('#modalAgendarBanho').addClass('hidden'); };

            function carregarSlotsDisponiveis(isAutoInitialCheck = false, wasRolledToTomorrow = false) {
                const idPet = $('#modalAgendarPet').val();
                const idSrv = $('#modalAgendarServico').val();
                const dataDia = $('#modalAgendarDataDia').val();
                const container = $('#containerSlotsHorarios');
                $('#modalAgendarHoraSelecionada').val('');
                $('#btnConfirmarAgendamento').prop('disabled', true);
                if (!dataDia) { container.html('<p class="text-xs text-gray-400 italic">Selecione uma data.</p>'); return; }
                container.html('<div class="flex items-center gap-2 text-xs py-4" style="color: var(--brand)"><span class="material-icons-round text-sm animate-spin">sync</span> Consultando horários...</div>');
                const pad = n => n < 10 ? '0'+n : n;
                const now = new Date();
                const dtHoje = `${now.getFullYear()}-${pad(now.getMonth()+1)}-${pad(now.getDate())}`;
                $.getJSON('../dinovatech/app.php', { action: 'get_horarios_disponiveis_banho', data: dataDia, id_servico: idSrv||0, id_pet: idPet||0 }, function(res) {
                    if (!res.success) { container.html(`<p class="text-xs text-red-500">${res.message||'Erro.'}</p>`); return; }
                    if (res.duracao_estimada) $('#labelDuracaoEstimada').text(`⏱️ ~${res.duracao_estimada} min`);
                    else $('#labelDuracaoEstimada').text('');
                    const slots = res.slots || [];
                    let totalLivres = 0;
                    slots.forEach(s => { if (s.disponivel) totalLivres++; });
                    if (isAutoInitialCheck && dataDia === dtHoje && totalLivres === 0) {
                        const am = new Date(); am.setDate(am.getDate()+1);
                        const dtAmanha = `${am.getFullYear()}-${pad(am.getMonth()+1)}-${pad(am.getDate())}`;
                        $('#modalAgendarDataDia').val(dtAmanha);
                        carregarSlotsDisponiveis(false, true); return;
                    }
                    if (slots.length === 0) { container.html('<p class="text-xs text-gray-400 italic">Nenhum horário disponível.</p>'); return; }
                    let gridHtml = '<div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 gap-2 w-full">';
                    slots.forEach(s => {
                        if (s.disponivel) {
                            gridHtml += `<button type="button" onclick="selecionarSlotHorario('${s.hora}', this)"
                                class="slot-hora-btn py-2 px-2 rounded-xl text-xs font-bold border bg-white hover:bg-emerald-50 transition flex flex-col items-center shadow-sm"
                                style="border-color: var(--brand); color: var(--brand-dark)">
                                <span>${s.hora}</span><span class="text-[9px] mt-0.5" style="color: var(--brand)">${s.vagas} vaga(s)</span></button>`;
                        } else {
                            gridHtml += `<div class="py-2 px-2 rounded-xl text-xs font-medium border border-gray-200 bg-gray-100 text-gray-400 flex flex-col items-center cursor-not-allowed opacity-60" title="${s.motivo}"><span class="line-through">${s.hora}</span><span class="text-[9px] mt-0.5">${s.motivo}</span></div>`;
                        }
                    });
                    gridHtml += '</div>';
                    if (totalLivres === 0) gridHtml += '<p class="text-xs text-amber-700 mt-2 font-medium text-center">Todos os horários ocupados. Selecione outra data.</p>';
                    if (wasRolledToTomorrow) {
                        const aviso = '<div class="w-full bg-amber-50 border border-amber-200 text-amber-900 px-3 py-2 rounded-xl text-xs mb-3 flex items-center gap-1.5"><span class="material-icons-round text-amber-600 text-sm">schedule</span><span>Horários de hoje esgotados. Exibindo <strong>amanhã</strong>.</span></div>';
                        container.html(aviso + gridHtml);
                    } else { container.html(gridHtml); }
                });
            }

            window.selecionarSlotHorario = function (hora, btnEl) {
                $('.slot-hora-btn').css({'background':'#fff','color':'var(--brand-dark)'}).removeClass('ring-2');
                $(btnEl).css({'background':'var(--brand)','color':'#fff'}).addClass('ring-2').css('ring-color','var(--brand)');
                $('#modalAgendarHoraSelecionada').val(hora);
                $('#btnConfirmarAgendamento').prop('disabled', false);
            };

            $('#modalAgendarPet, #modalAgendarServico, #modalAgendarDataDia').on('change', function () {
                const idPet = $('#modalAgendarPet').val();
                const idSrv = $('#modalAgendarServico').val();
                if (idPet && idSrv && globalDashboardData) {
                    $.getJSON('../dinovatech/app.php', { action: 'get_cliente_pacotes_saldo', id_cliente: globalDashboardData.cliente.id_cliente, id_servico: idSrv, id_pet: idPet }, function (res) {
                        if (res.success && res.saldos && res.saldos.length > 0) {
                            const sal = res.saldos[0];
                            $('#textoAgendarSaldoBadge').text(`Você possui saldo no pacote "${sal.nome_pacote}" (${sal.saldo_restante} banho(s)).`);
                            $('#boxAgendarSaldoBadge').removeClass('hidden');
                            $('#modalAgendarUsarSaldo').prop('checked', true);
                        } else { $('#boxAgendarSaldoBadge').addClass('hidden'); }
                    });
                } else { $('#boxAgendarSaldoBadge').addClass('hidden'); }
                const checkAuto = isOpeningModalBanho;
                isOpeningModalBanho = false;
                carregarSlotsDisponiveis(checkAuto);
            });

            $('#formAgendarBanhoCliente').on('submit', function (e) {
                e.preventDefault();
                const hora = $('#modalAgendarHoraSelecionada').val();
                const dia  = $('#modalAgendarDataDia').val();
                if (!hora || !dia) { alert('Selecione um horário disponível.'); return; }
                const btn = $(this).find('button[type="submit"]');
                btn.prop('disabled', true).text('Agendando...');
                $.ajax({
                    url: '../dinovatech/app.php', type: 'POST', dataType: 'json',
                    data: { action: 'cliente_agendar_banho', id_pet: $('#modalAgendarPet').val(), id_servico: $('#modalAgendarServico').val(), data_inicio: `${dia} ${hora}:00`, observacoes: $('#modalAgendarObservacoes').val(), usar_saldo_pacote: $('#modalAgendarUsarSaldo').is(':checked') ? 1 : 0 },
                    success: function (res) {
                        btn.prop('disabled', false).text('Confirmar Agendamento');
                        const msg = $('#modalAgendarMsg').removeClass('hidden text-emerald-600 text-red-600');
                        if (res.success) {
                            msg.addClass('text-emerald-600').text('Agendamento realizado com sucesso! ✅');
                            setTimeout(() => { fecharModalAgendarBanhoCliente(); window.location.reload(); }, 1400);
                        } else { msg.addClass('text-red-600').text(res.message || 'Erro ao agendar.'); }
                    },
                    error: function () { btn.prop('disabled', false).text('Confirmar Agendamento'); alert('Erro de conexão.'); }
                });
            });

            // ── Populate Meus Dados ──
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

            // ── Utilitários ──
            function formatCurrency(val) {
                return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(val);
            }
            function formatDate(dtStr) {
                if (!dtStr) return '-';
                const parts = dtStr.split('-');
                return parts.length === 3 ? `${parts[2]}/${parts[1]}/${parts[0]}` : dtStr;
            }
            function formatDateTime(dtStr) {
                if (!dtStr) return '-';
                const parts = dtStr.split(' ');
                const datePart = parts[0] ? formatDate(parts[0]) : '-';
                const timePart = parts[1] ? parts[1].substring(0,5) : '';
                return timePart ? `${datePart} às ${timePart}` : datePart;
            }
            function escapeHtml(text) {
                if (!text) return '';
                return String(text).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');
            }
        });
    </script>

</body>
</html>