<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers/AppHelper.php';
require_once __DIR__ . '/helpers/TenantDbProvisionerHelper.php';
require_once __DIR__ . '/../database.php';

// Trava mandatória: disponível estritamente no modo novo (SAAS_MASTER_ENABLED=true)
if (!defined('SAAS_MASTER_ENABLED') || !SAAS_MASTER_ENABLED) {
    http_response_code(403);
    die('<div style="font-family:sans-serif;padding:30px;text-align:center;"><h2>Módulo SaaS Master Desativado</h2><p>Esta ferramenta de infraestrutura está disponível exclusivamente quando <code>SAAS_MASTER_ENABLED=true</code>.</p><a href="dashboard.php">Voltar ao Dashboard</a></div>');
}

// Endpoint AJAX para execução do provisionamento
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_action']) && $_POST['ajax_action'] === 'provision_database') {
    header('Content-Type: application/json; charset=utf-8');

    $dbName = trim($_POST['db_name'] ?? '');
    $host = trim($_POST['db_host'] ?? (defined('SAAS_DEFAULT_DB_HOST') ? SAAS_DEFAULT_DB_HOST : DB_HOSTNAME));
    $user = trim($_POST['db_user'] ?? DB_USERNAME);
    $pass = $_POST['db_pass'] ?? DB_PASSWORD;
    $port = (int)($_POST['db_port'] ?? 3306);
    $adminEmail = trim($_POST['admin_email'] ?? 'admin@dinovatech.com.br');
    $adminPass = trim($_POST['admin_pass'] ?? 'admin1234');

    if (empty($dbName)) {
        echo json_encode([
            'success' => false,
            'message' => 'O nome da base de dados é obrigatório.',
            'logs' => ['Erro: Nome do banco não informado.']
        ]);
        exit();
    }

    $res = TenantDbProvisionerHelper::provisionTenantDatabase(
        $dbName,
        $host,
        $user,
        $pass,
        $port,
        $adminEmail,
        $adminPass
    );

    echo json_encode($res);
    exit();
}

// Carrega os tenants cadastrados para seleção rápida
$tenantsList = [];
$link = DBConnect();
if ($link) {
    $hasT = DBExecute($link, "SHOW TABLES LIKE 'saas_tenants'");
    if ($hasT && mysqli_num_rows($hasT) > 0) {
        $q = "SELECT T.id_tenant, T.slug_instancia, T.subdominio, T.db_name, T.db_user, T.db_host, T.db_port, C.nome AS nome_cliente 
              FROM saas_tenants T 
              LEFT JOIN Clientes C ON T.id_cliente = C.id_cliente 
              ORDER BY T.id_tenant DESC";
        $r = DBExecute($link, $q);
        if ($r) {
            while ($row = mysqli_fetch_assoc($r)) {
                $tenantsList[] = $row;
            }
        }
    }
    DBClose($link);
}

// Preseleção se id_tenant for passado via GET
$selectedTenantId = isset($_GET['id_tenant']) ? (int)$_GET['id_tenant'] : 0;
$defaultTenant = null;
if ($selectedTenantId > 0) {
    foreach ($tenantsList as $t) {
        if ((int)$t['id_tenant'] === $selectedTenantId) {
            $defaultTenant = $t;
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tool: Provisionador de Banco de Dados SaaS - Dinovatech</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
</head>
<body class="bg-slate-100 font-sans antialiased text-slate-800">
    <?php include 'components/sidebar.php'; ?>

    <div class="lg:pl-64 flex flex-col min-h-screen">
        <!-- Header -->
        <header class="bg-white border-b border-slate-200 px-6 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 sticky top-0 z-10 shadow-xs">
            <div>
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center justify-center p-2 bg-emerald-100 text-emerald-700 rounded-lg">
                        <span class="material-icons text-xl">storage</span>
                    </span>
                    <h1 class="text-2xl font-bold text-slate-800">Provisionador de Banco SaaS</h1>
                </div>
                <p class="text-sm text-slate-500 mt-1">Criação de base isolada, execução de migrations e usuário administrador padrão (Multi-Tenant).</p>
            </div>
            <div class="flex items-center gap-2.5">
                <a href="saas_tenants.php" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium rounded-lg text-sm transition">
                    <span class="material-icons text-sm">arrow_back</span>
                    <span>Central SaaS</span>
                </a>
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex-1 p-6 space-y-6 max-w-5xl mx-auto w-full">

            <!-- Card Informativo / Guia -->
            <div class="bg-gradient-to-r from-purple-900 to-indigo-900 text-white rounded-2xl p-6 shadow-md relative overflow-hidden">
                <div class="relative z-10">
                    <div class="flex items-center gap-2 text-purple-200 text-xs font-bold uppercase tracking-wider mb-2">
                        <span class="material-icons text-sm">security</span>
                        <span>Ferramenta de Infraestrutura (Modo SaaS Master)</span>
                    </div>
                    <h2 class="text-xl font-bold mb-2">Inicialização de Banco de Dados de Novo Cliente</h2>
                    <p class="text-xs text-purple-100 max-w-2xl leading-relaxed">
                        Esta ferramenta automatiza a criação do schema isolado para um novo cliente. Ela conecta ao servidor MariaDB remoto, cria a base <code>CREATE DATABASE IF NOT EXISTS</code>, roda todas as migrações SQL versionadas e injeta o usuário master <strong>admin@dinovatech.com.br</strong> com senha <strong>admin1234</strong>.
                    </p>
                </div>
            </div>

            <!-- Formulário de Configuração do Banco -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-6">
                <!-- Seletor Rápido de Tenant -->
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-500 mb-2">Seleção Rápida por Contrato / Tenant Cadastrado</label>
                    <select id="selectTenantRapido" onchange="selecionarTenant(this.value)" class="w-full p-2.5 text-sm border border-slate-300 rounded-xl bg-slate-50 focus:bg-white focus:ring-2 focus:ring-emerald-500 focus:outline-hidden transition">
                        <option value="">-- Preencher manualmente ou selecione um cliente abaixo --</option>
                        <?php foreach ($tenantsList as $t): 
                            $isSel = ($defaultTenant && $defaultTenant['id_tenant'] === $t['id_tenant']) ? 'selected' : '';
                        ?>
                            <option value="<?= $t['id_tenant'] ?>" <?= $isSel ?>
                                data-dbname="<?= htmlspecialchars($t['db_name']) ?>"
                                data-dbuser="<?= htmlspecialchars($t['db_user']) ?>"
                                data-dbhost="<?= htmlspecialchars($t['db_host'] ?: (defined('SAAS_DEFAULT_DB_HOST') ? SAAS_DEFAULT_DB_HOST : DB_HOSTNAME)) ?>"
                                data-dbport="<?= htmlspecialchars($t['db_port'] ?: 3306) ?>"
                                data-cliente="<?= htmlspecialchars($t['nome_cliente']) ?>">
                                <?= htmlspecialchars($t['nome_cliente']) ?> &bull; Base: <?= htmlspecialchars($t['db_name']) ?> (<?= htmlspecialchars($t['subdominio']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <form id="formProvisionDb" onsubmit="iniciarProvisionamento(event)" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Host MariaDB -->
                        <div>
                            <label for="db_host" class="block text-xs font-bold text-slate-700 mb-1">Host do MariaDB / MySQL</label>
                            <input type="text" id="db_host" name="db_host" required
                                value="<?= htmlspecialchars($defaultTenant['db_host'] ?? (defined('SAAS_DEFAULT_DB_HOST') ? SAAS_DEFAULT_DB_HOST : DB_HOSTNAME)) ?>"
                                class="w-full p-2.5 text-xs font-mono border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:outline-hidden">
                            <span class="text-[11px] text-slate-400">Ex: 137.131.208.19 ou IP da VM MariaDB</span>
                        </div>

                        <!-- Porta -->
                        <div>
                            <label for="db_port" class="block text-xs font-bold text-slate-700 mb-1">Porta de Conexão</label>
                            <input type="number" id="db_port" name="db_port" required value="3306"
                                class="w-full p-2.5 text-xs font-mono border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:outline-hidden">
                        </div>

                        <!-- Usuário Admin MySQL -->
                        <div>
                            <label for="db_user" class="block text-xs font-bold text-slate-700 mb-1">Usuário MySQL Administrador</label>
                            <input type="text" id="db_user" name="db_user" required value="<?= htmlspecialchars(DB_USERNAME) ?>"
                                class="w-full p-2.5 text-xs font-mono border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:outline-hidden">
                            <span class="text-[11px] text-slate-400">Deve possuir privilégio de <code>CREATE DATABASE</code></span>
                        </div>

                        <!-- Senha Admin MySQL -->
                        <div>
                            <label for="db_pass" class="block text-xs font-bold text-slate-700 mb-1">Senha do Usuário MySQL</label>
                            <input type="password" id="db_pass" name="db_pass" required value="<?= htmlspecialchars(DB_PASSWORD) ?>"
                                class="w-full p-2.5 text-xs font-mono border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:outline-hidden">
                        </div>
                    </div>

                    <div class="pt-4 border-t border-slate-100">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-3 flex items-center gap-1.5">
                            <span class="material-icons text-sm text-emerald-600">tune</span>
                            <span>Dados do Banco e Acesso do Novo Cliente</span>
                        </h3>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <!-- Nome do Banco a Criar -->
                            <div>
                                <label for="db_name" class="block text-xs font-bold text-slate-700 mb-1">Nome da Base a Criar</label>
                                <input type="text" id="db_name" name="db_name" required placeholder="ex: dino_mariana_costa"
                                    value="<?= htmlspecialchars($defaultTenant['db_name'] ?? '') ?>"
                                    class="w-full p-2.5 text-xs font-mono font-bold text-purple-700 border border-purple-300 rounded-lg bg-purple-50/40 focus:ring-2 focus:ring-purple-500 focus:outline-hidden">
                                <span class="text-[11px] text-slate-400">Padrão sugerido: <code>dino_{slug}</code></span>
                            </div>

                            <!-- E-mail Admin Inicial -->
                            <div>
                                <label for="admin_email" class="block text-xs font-bold text-slate-700 mb-1">E-mail do Administrador Inicial</label>
                                <input type="email" id="admin_email" name="admin_email" required value="admin@dinovatech.com.br"
                                    class="w-full p-2.5 text-xs font-mono border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:outline-hidden">
                            </div>

                            <!-- Senha Admin Inicial -->
                            <div>
                                <label for="admin_pass" class="block text-xs font-bold text-slate-700 mb-1">Senha Base Inicial</label>
                                <input type="text" id="admin_pass" name="admin_pass" required value="admin1234"
                                    class="w-full p-2.5 text-xs font-mono border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:outline-hidden font-bold text-emerald-700">
                            </div>
                        </div>
                    </div>

                    <!-- Botão de Ação -->
                    <div class="pt-4 border-t border-slate-100 flex items-center justify-between">
                        <div class="text-xs text-slate-500 flex items-center gap-1">
                            <span class="material-icons text-xs text-amber-500">info</span>
                            <span>Operação idempotente: Se a base já existir, aplica apenas migrações pendentes.</span>
                        </div>
                        <button type="submit" id="btnProvisionar" class="inline-flex items-center gap-2 px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl shadow-sm transition text-sm">
                            <span class="material-icons text-base" id="iconBtnProv">play_circle_filled</span>
                            <span>Criar & Inicializar Banco de Dados</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Terminal de Execução e Logs em Tempo Real -->
            <div id="boxTerminal" class="bg-slate-950 rounded-2xl border border-slate-800 shadow-xl overflow-hidden hidden">
                <div class="px-5 py-3 bg-slate-900 border-b border-slate-800 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-rose-500"></span>
                        <span class="w-3 h-3 rounded-full bg-amber-500"></span>
                        <span class="w-3 h-3 rounded-full bg-emerald-500"></span>
                        <span class="text-xs font-mono font-bold text-slate-300 ml-2">Console de Provisionamento</span>
                    </div>
                    <span id="badgeStatusTerminal" class="text-[11px] font-mono px-2 py-0.5 rounded bg-slate-800 text-slate-400">Pronto</span>
                </div>
                <div id="conteudoLogsTerminal" class="p-4 font-mono text-xs leading-relaxed text-emerald-400 max-h-80 overflow-y-auto whitespace-pre-wrap select-all bg-slate-950">
                    Aguardando início...
                </div>
            </div>

        </main>
    </div>

    <script>
        function selecionarTenant(idTenant) {
            if (!idTenant) return;
            const opt = $('#selectTenantRapido option:selected');
            const dbName = opt.data('dbname');
            const dbHost = opt.data('dbhost');
            const dbPort = opt.data('dbport');
            const dbUser = opt.data('dbuser');

            if (dbName) $('#db_name').val(dbName);
            if (dbHost) $('#db_host').val(dbHost);
            if (dbPort) $('#db_port').val(dbPort);
            if (dbUser) $('#db_user').val(dbUser);
        }

        function iniciarProvisionamento(e) {
            e.preventDefault();
            const dbName = $('#db_name').val().trim();
            if (!confirm(`Confirma a criação e inicialização completa da base de dados '${dbName}' com admin@dinovatech.com.br?`)) {
                return;
            }

            const btn = $('#btnProvisionar');
            const icon = $('#iconBtnProv');
            const boxTerminal = $('#boxTerminal');
            const logsDiv = $('#conteudoLogsTerminal');
            const badge = $('#badgeStatusTerminal');

            btn.prop('disabled', true).addClass('opacity-50 cursor-not-allowed');
            icon.text('autorenew').addClass('animate-spin');
            boxTerminal.removeClass('hidden');
            badge.removeClass('bg-emerald-900/60 text-emerald-300 bg-rose-900/60 text-rose-300')
                 .addClass('bg-indigo-900/60 text-indigo-300 animate-pulse').text('Executando...');

            logsDiv.html('> Conectando ao servidor e iniciando processo...\n');

            const payload = {
                ajax_action: 'provision_database',
                db_name: $('#db_name').val().trim(),
                db_host: $('#db_host').val().trim(),
                db_port: $('#db_port').val().trim(),
                db_user: $('#db_user').val().trim(),
                db_pass: $('#db_pass').val(),
                admin_email: $('#admin_email').val().trim(),
                admin_pass: $('#admin_pass').val().trim()
            };

            $.post('saas_db_tool.php', payload, function(res) {
                btn.prop('disabled', false).removeClass('opacity-50 cursor-not-allowed');
                icon.text('play_circle_filled').removeClass('animate-spin');

                if (res.logs && res.logs.length > 0) {
                    let formatted = '';
                    res.logs.forEach(function(l) {
                        formatted += '> ' + l + '\n';
                    });
                    logsDiv.text(formatted);
                }

                if (res.success) {
                    badge.removeClass('bg-indigo-900/60 text-indigo-300 animate-pulse')
                         .addClass('bg-emerald-900/60 text-emerald-300').text('100% Concluído com Sucesso!');
                    alert('Sucesso: ' + res.message);
                } else {
                    badge.removeClass('bg-indigo-900/60 text-indigo-300 animate-pulse')
                         .addClass('bg-rose-900/60 text-rose-300').text('Erro na execução');
                    alert('Falha: ' + res.message);
                }
            }, 'json').fail(function(xhr) {
                btn.prop('disabled', false).removeClass('opacity-50 cursor-not-allowed');
                icon.text('play_circle_filled').removeClass('animate-spin');
                badge.removeClass('animate-pulse').addClass('bg-rose-900 text-rose-300').text('Erro de Comunicação');
                logsDiv.append('\n> ERRO CRÍTICO: Falha na requisição HTTP com o servidor (Status ' + xhr.status + ').');
                alert('Erro de conexão com o backend.');
            });
        }
    </script>
</body>
</html>
