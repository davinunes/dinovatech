<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers/AppHelper.php';
require_once __DIR__ . '/helpers/TenantDbProvisionerHelper.php';
require_once __DIR__ . '/helpers/StorageMigrationHelper.php';
require_once __DIR__ . '/../database.php';

// Trava mandatória: disponível estritamente no modo novo (SAAS_MASTER_ENABLED=true)
if (!defined('SAAS_MASTER_ENABLED') || !SAAS_MASTER_ENABLED) {
    http_response_code(403);
    die('<div style="font-family:sans-serif;padding:30px;text-align:center;"><h2>Módulo SaaS Master Desativado</h2><p>Esta ferramenta de infraestrutura está disponível exclusivamente quando <code>SAAS_MASTER_ENABLED=true</code>.</p><a href="dashboard.php">Voltar ao Dashboard</a></div>');
}

// -------------------------------------------------------------
// Endpoint AJAX: Provisionamento de Novo Banco
// -------------------------------------------------------------
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

// -------------------------------------------------------------
// Endpoint AJAX: Auditoria e Migração de Storage (Disco -> S3)
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_action']) && in_array($_POST['ajax_action'], ['scan_storage', 'execute_storage'])) {
    header('Content-Type: application/json; charset=utf-8');

    $isDryRun = ($_POST['ajax_action'] === 'scan_storage');
    $targetDb = trim($_POST['target_db'] ?? '');
    $customBucket = trim($_POST['bucket_url'] ?? '');

    $link = DBConnect();
    if (!$link) {
        echo json_encode([
            'success' => false,
            'message' => 'Falha ao conectar no MariaDB.',
            'logs' => ['Erro: Não foi possível conectar ao banco de dados.']
        ]);
        exit();
    }

    if (!empty($targetDb) && $targetDb !== DB_DATABASE) {
        if (!@mysqli_select_db($link, $targetDb)) {
            echo json_encode([
                'success' => false,
                'message' => "Não foi possível selecionar o banco '$targetDb': " . mysqli_error($link),
                'logs' => ["Erro ao trocar de banco: " . mysqli_error($link)]
            ]);
            DBClose($link);
            exit();
        }
    }

    $bucketUrl = !empty($customBucket) ? $customBucket : StorageMigrationHelper::getBucketUrl($link);
    if (empty($bucketUrl)) {
        echo json_encode([
            'success' => false,
            'message' => 'Nenhuma URL de bucket Oracle (PAR) configurada. Preencha o campo de URL do Bucket.',
            'logs' => ['Erro: URL do bucket Oracle ausente.']
        ]);
        DBClose($link);
        exit();
    }

    $projectRoot = dirname(__DIR__);
    $res = StorageMigrationHelper::runMigration($link, [
        'dry_run' => $isDryRun,
        'bucket_url' => $bucketUrl,
        'base_dir' => $projectRoot
    ]);

    DBClose($link);

    echo json_encode([
        'success' => true,
        'is_dry_run' => $isDryRun,
        'bucket_url' => $bucketUrl,
        'data' => $res,
        'message' => $isDryRun 
            ? "Varredura de banco concluída: {$res['found_on_disk']} arquivos prontos para envio, {$res['orphans_in_db']} referências órfãs." 
            : "Migração executada: {$res['migrated_count']} arquivos migrados com sucesso para o Oracle S3."
    ]);
    exit();
}

// Carrega os tenants cadastrados para seleção rápida
$tenantsList = [];
$link = DBConnect();
$oracleBucketUrlPadrao = '';
if ($link) {
    $oracleBucketUrlPadrao = StorageMigrationHelper::getBucketUrl($link);

    $hasT = DBExecute($link, "SHOW TABLES LIKE 'saas_tenants'");
    if ($hasT && mysqli_num_rows($hasT) > 0) {
        $resT = DBExecute($link, "SELECT id_tenant, nome_cliente, subdominio, db_name, db_user, db_host, db_port FROM saas_tenants ORDER BY nome_cliente ASC");
        while ($row = mysqli_fetch_assoc($resT)) {
            $tenantsList[] = $row;
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
    <title>SaaS Infrastructure Tool - Dinovatech</title>
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
                    <span class="inline-flex items-center justify-center p-2 bg-indigo-100 text-indigo-700 rounded-lg">
                        <span class="material-icons text-xl">construction</span>
                    </span>
                    <h1 class="text-2xl font-bold text-slate-800">Ferramentas de Infraestrutura SaaS</h1>
                </div>
                <p class="text-sm text-slate-500 mt-1">Provisionamento de bancos isolados e migração de arquivos locais para Oracle S3 (Stateless).</p>
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

            <!-- Tab Switcher -->
            <div class="flex border-b border-slate-200 space-x-2">
                <button type="button" id="tabBtnProv" onclick="switchTab('prov')" 
                    class="py-3 px-5 text-sm font-bold border-b-2 border-indigo-600 text-indigo-600 flex items-center gap-2 transition">
                    <span class="material-icons text-base">storage</span>
                    <span>Provisionar Banco de Tenant</span>
                </button>
                <button type="button" id="tabBtnStorage" onclick="switchTab('storage')" 
                    class="py-3 px-5 text-sm font-semibold border-b-2 border-transparent text-slate-500 hover:text-slate-700 flex items-center gap-2 transition">
                    <span class="material-icons text-base">cloud_upload</span>
                    <span>Migração de Arquivos para S3 / OCI</span>
                </button>
            </div>

            <!-- TAB 1: PROVISIONAMENTO DE BANCO -->
            <div id="tabContentProv" class="space-y-6">
                <!-- Card Informativo -->
                <div class="bg-gradient-to-r from-purple-900 to-indigo-900 text-white rounded-2xl p-6 shadow-md relative overflow-hidden">
                    <div class="relative z-10">
                        <div class="flex items-center gap-2 text-purple-200 text-xs font-bold uppercase tracking-wider mb-2">
                            <span class="material-icons text-sm">security</span>
                            <span>Inicialização Automatizada de Tenant</span>
                        </div>
                        <h2 class="text-xl font-bold mb-2">Inicialização de Banco de Dados de Novo Cliente</h2>
                        <p class="text-xs text-purple-100 max-w-2xl leading-relaxed">
                            Cria o schema isolado no MariaDB, executa todas as migrações SQL versionadas e cria o usuário master <strong>admin@dinovatech.com.br</strong> com senha <strong>admin1234</strong>.
                        </p>
                    </div>
                </div>

                <!-- Formulário de Provisionamento -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-6">
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-500 mb-2">Seleção Rápida por Contrato / Tenant Cadastrado</label>
                        <select id="selectTenantRapido" onchange="selecionarTenant(this.value)" class="w-full p-2.5 text-sm border border-slate-300 rounded-xl bg-slate-50 focus:bg-white focus:ring-2 focus:ring-indigo-500 focus:outline-hidden transition">
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
                            <div>
                                <label for="db_host" class="block text-xs font-bold text-slate-700 mb-1">Host do MariaDB / MySQL</label>
                                <input type="text" id="db_host" name="db_host" required
                                    value="<?= htmlspecialchars($defaultTenant['db_host'] ?? (defined('SAAS_DEFAULT_DB_HOST') ? SAAS_DEFAULT_DB_HOST : DB_HOSTNAME)) ?>"
                                    class="w-full p-2.5 text-xs font-mono border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-hidden">
                            </div>
                            <div>
                                <label for="db_port" class="block text-xs font-bold text-slate-700 mb-1">Porta de Conexão</label>
                                <input type="number" id="db_port" name="db_port" required value="3306"
                                    class="w-full p-2.5 text-xs font-mono border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-hidden">
                            </div>
                            <div>
                                <label for="db_user" class="block text-xs font-bold text-slate-700 mb-1">Usuário MySQL Administrador</label>
                                <input type="text" id="db_user" name="db_user" required value="<?= htmlspecialchars(DB_USERNAME) ?>"
                                    class="w-full p-2.5 text-xs font-mono border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-hidden">
                            </div>
                            <div>
                                <label for="db_pass" class="block text-xs font-bold text-slate-700 mb-1">Senha do Usuário MySQL</label>
                                <input type="password" id="db_pass" name="db_pass" required value="<?= htmlspecialchars(DB_PASSWORD) ?>"
                                    class="w-full p-2.5 text-xs font-mono border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-hidden">
                            </div>
                        </div>

                        <div class="pt-4 border-t border-slate-100">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-3 flex items-center gap-1.5">
                                <span class="material-icons text-sm text-indigo-600">tune</span>
                                <span>Dados do Banco e Acesso do Novo Cliente</span>
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label for="db_name" class="block text-xs font-bold text-slate-700 mb-1">Nome do Banco (Schema)</label>
                                    <input type="text" id="db_name" name="db_name" required placeholder="ex: dino_mariana"
                                        value="<?= htmlspecialchars($defaultTenant['db_name'] ?? '') ?>"
                                        class="w-full p-2.5 text-xs font-mono border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-hidden">
                                </div>
                                <div>
                                    <label for="admin_email" class="block text-xs font-bold text-slate-700 mb-1">E-mail Master Inicial</label>
                                    <input type="email" id="admin_email" name="admin_email" required value="admin@dinovatech.com.br"
                                        class="w-full p-2.5 text-xs border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-hidden">
                                </div>
                                <div>
                                    <label for="admin_pass" class="block text-xs font-bold text-slate-700 mb-1">Senha Inicial</label>
                                    <input type="text" id="admin_pass" name="admin_pass" required value="admin1234"
                                        class="w-full p-2.5 text-xs font-mono border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-hidden">
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                            <button type="submit" id="btnProvisionar" class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl text-sm shadow-md shadow-indigo-600/20 transition">
                                <span class="material-icons text-base" id="iconBtnProv">play_circle_filled</span>
                                <span>Criar e Inicializar Banco</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- TAB 2: MIGRAÇÃO STORAGE PARA S3 / ORACLE (STATELESS) -->
            <div id="tabContentStorage" class="space-y-6 hidden">
                <!-- Card Informativo -->
                <div class="bg-gradient-to-r from-sky-900 to-cyan-900 text-white rounded-2xl p-6 shadow-md relative overflow-hidden">
                    <div class="relative z-10">
                        <div class="flex items-center gap-2 text-cyan-200 text-xs font-bold uppercase tracking-wider mb-2">
                            <span class="material-icons text-sm">cloud_sync</span>
                            <span>Estratégia Stateless para Pods K3s</span>
                        </div>
                        <h2 class="text-xl font-bold mb-2">Auditoria e Migração Baseada em Banco de Dados</h2>
                        <p class="text-xs text-cyan-100 max-w-2xl leading-relaxed">
                            Para evitar subir arquivos órfãos já esquecidos em disco, esta ferramenta <strong>olha primeiro para o banco de dados</strong> em busca de caminhos relativos (<code>NOT LIKE 'http%'</code>), testa a existência do arquivo físico no servidor e realiza o upload via PUT para o Oracle Object Storage, atualizando a URL pública no banco.
                        </p>
                    </div>
                </div>

                <!-- Formulário de Migração -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="storage_target_db" class="block text-xs font-bold text-slate-700 mb-1">Base de Dados a Auditar/Migrar</label>
                            <select id="storage_target_db" class="w-full p-2.5 text-xs font-mono border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:outline-hidden">
                                <option value="<?= htmlspecialchars(DB_DATABASE) ?>">Base Atual (<?= htmlspecialchars(DB_DATABASE) ?>)</option>
                                <?php foreach ($tenantsList as $t): ?>
                                    <option value="<?= htmlspecialchars($t['db_name']) ?>">
                                        <?= htmlspecialchars($t['nome_cliente']) ?> &bull; <?= htmlspecialchars($t['db_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="storage_bucket_url" class="block text-xs font-bold text-slate-700 mb-1">URL Pre-Auth (PAR) Oracle Object Storage</label>
                            <input type="text" id="storage_bucket_url" value="<?= htmlspecialchars($oracleBucketUrlPadrao) ?>"
                                placeholder="https://objectstorage.sa-saopaulo-1.oraclecloud.com/p/.../b/bucket/o/"
                                class="w-full p-2.5 text-xs font-mono border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:outline-hidden">
                            <span class="text-[11px] text-slate-400">Puxada de <code>ConfiguracoesEmissor.api_oracle_url</code> ou <code>oci-s3.php</code></span>
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-4 border-t border-slate-100">
                        <div class="text-xs text-slate-500">
                            Tabelas auditadas: <code>ConfiguracoesEmissor</code>, <code>Pets</code>, <code>Clientes</code>, <code>BanhoCheckinFotos</code>, <code>Arquivos</code>, <code>Veterinarios</code>
                        </div>
                        <div class="flex items-center gap-3">
                            <button type="button" id="btnAuditStorage" onclick="iniciarMigracaoStorage(true)" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-xl text-sm transition">
                                <span class="material-icons text-base">search</span>
                                <span>Auditar Banco (--dry-run)</span>
                            </button>
                            <button type="button" id="btnExecStorage" onclick="iniciarMigracaoStorage(false)" class="inline-flex items-center gap-2 px-5 py-2 bg-cyan-600 hover:bg-cyan-700 text-white font-semibold rounded-xl text-sm shadow-md shadow-cyan-600/20 transition">
                                <span class="material-icons text-base" id="iconBtnExecStorage">cloud_upload</span>
                                <span>Migrar para S3 (--execute)</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Painel de Estatísticas da Migração -->
                <div id="boxStatsStorage" class="hidden grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <div class="bg-white p-4 rounded-xl border border-slate-200">
                        <span class="text-xs text-slate-500 font-semibold block">Refs no Banco</span>
                        <span id="statDbRefs" class="text-2xl font-bold text-slate-800">0</span>
                    </div>
                    <div class="bg-white p-4 rounded-xl border border-slate-200">
                        <span class="text-xs text-slate-500 font-semibold block">Existentes no Disco</span>
                        <span id="statFoundDisk" class="text-2xl font-bold text-emerald-600">0</span>
                    </div>
                    <div class="bg-white p-4 rounded-xl border border-slate-200">
                        <span class="text-xs text-slate-500 font-semibold block">Órfãos no Banco</span>
                        <span id="statOrphansDb" class="text-2xl font-bold text-rose-500">0</span>
                    </div>
                    <div class="bg-white p-4 rounded-xl border border-slate-200">
                        <span class="text-xs text-slate-500 font-semibold block">Volume Total</span>
                        <span id="statVolume" class="text-2xl font-bold text-cyan-600">0 MB</span>
                    </div>
                </div>

                <!-- Tabela de Resumo por Tabela -->
                <div id="boxTabelaStorage" class="hidden bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-xs">
                    <table class="w-full text-xs text-left">
                        <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-bold uppercase">
                            <tr>
                                <th class="p-3">Tabela</th>
                                <th class="p-3 text-center">Registros Relativos</th>
                                <th class="p-3 text-center">No Disco</th>
                                <th class="p-3 text-center">Órfãos no Banco</th>
                                <th class="p-3 text-center">Migrados</th>
                                <th class="p-3 text-right">Volume</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyStorageSummary" class="divide-y divide-slate-100 font-mono">
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Box Terminal de Logs -->
            <div id="boxTerminal" class="bg-slate-950 rounded-2xl border border-slate-800 shadow-xl overflow-hidden hidden">
                <div class="px-5 py-3 bg-slate-900 border-b border-slate-800 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-rose-500"></span>
                        <span class="w-3 h-3 rounded-full bg-amber-500"></span>
                        <span class="w-3 h-3 rounded-full bg-emerald-500"></span>
                        <span id="tituloTerminal" class="text-xs font-mono font-bold text-slate-300 ml-2">Console de Operações</span>
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
        function switchTab(tab) {
            if (tab === 'prov') {
                $('#tabBtnProv').addClass('border-indigo-600 text-indigo-600').removeClass('border-transparent text-slate-500');
                $('#tabBtnStorage').removeClass('border-cyan-600 text-cyan-600').addClass('border-transparent text-slate-500');
                $('#tabContentProv').removeClass('hidden');
                $('#tabContentStorage').addClass('hidden');
            } else {
                $('#tabBtnStorage').addClass('border-cyan-600 text-cyan-600').removeClass('border-transparent text-slate-500');
                $('#tabBtnProv').removeClass('border-indigo-600 text-indigo-600').addClass('border-transparent text-slate-500');
                $('#tabContentStorage').removeClass('hidden');
                $('#tabContentProv').addClass('hidden');
            }
        }

        function selecionarTenant(idTenant) {
            if (!idTenant) return;
            const opt = $('#selectTenantRapido option:selected');
            const dbName = opt.data('dbname');
            const dbHost = opt.data('dbhost');
            const dbPort = opt.data('dbport');
            const dbUser = opt.data('dbuser');

            if (dbName) {
                $('#db_name').val(dbName);
                $('#storage_target_db').val(dbName);
            }
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
            $('#tituloTerminal').text('Console de Provisionamento de Banco');
            boxTerminal.removeClass('hidden');
            badge.removeClass('bg-emerald-900/60 text-emerald-300 bg-rose-900/60 text-rose-300')
                 .addClass('bg-indigo-900/60 text-indigo-300 animate-pulse').text('Executando...');

            logsDiv.html('> Conectando ao servidor e iniciando provisionamento...\n');

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
                         .addClass('bg-emerald-900/60 text-emerald-300').text('100% Concluído!');
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

        function iniciarMigracaoStorage(isDryRun) {
            const targetDb = $('#storage_target_db').val();
            const bucketUrl = $('#storage_bucket_url').val().trim();

            if (!bucketUrl) {
                alert('Informe a URL do Bucket Oracle Pre-Auth (PAR).');
                return;
            }

            if (!isDryRun) {
                if (!confirm(`ATENÇÃO: Você está prestes a realizar o upload de todos os arquivos encontrados para o Oracle Object Storage e atualizar os registros do banco '${targetDb}'. Deseja prosseguir?`)) {
                    return;
                }
            }

            const boxTerminal = $('#boxTerminal');
            const logsDiv = $('#conteudoLogsTerminal');
            const badge = $('#badgeStatusTerminal');
            const btnExec = $('#btnExecStorage');
            const btnAudit = $('#btnAuditStorage');

            btnExec.prop('disabled', true).addClass('opacity-50 cursor-not-allowed');
            btnAudit.prop('disabled', true).addClass('opacity-50 cursor-not-allowed');
            $('#tituloTerminal').text(isDryRun ? 'Auditoria de Storage (--dry-run)' : 'Migração de Storage (--execute)');
            boxTerminal.removeClass('hidden');
            badge.removeClass('bg-emerald-900/60 text-emerald-300 bg-rose-900/60 text-rose-300')
                 .addClass('bg-cyan-900/60 text-cyan-300 animate-pulse').text(isDryRun ? 'Varrendo Banco...' : 'Migrando Arquivos...');

            logsDiv.html(`> Iniciando varredura orientada ao banco de dados [${targetDb}]...\n`);

            const payload = {
                ajax_action: isDryRun ? 'scan_storage' : 'execute_storage',
                target_db: targetDb,
                bucket_url: bucketUrl
            };

            $.post('saas_db_tool.php', payload, function(res) {
                btnExec.prop('disabled', false).removeClass('opacity-50 cursor-not-allowed');
                btnAudit.prop('disabled', false).removeClass('opacity-50 cursor-not-allowed');

                if (res.success && res.data) {
                    const d = res.data;
                    $('#statDbRefs').text(d.scanned_in_db);
                    $('#statFoundDisk').text(d.found_on_disk);
                    $('#statOrphansDb').text(d.orphans_in_db);
                    $('#statVolume').text((d.total_bytes / (1024 * 1024)).toFixed(2) + ' MB');
                    $('#boxStatsStorage').removeClass('hidden');

                    let rows = '';
                    for (const [tbl, s] of Object.entries(d.tables_summary)) {
                        const mb = (s.bytes / (1024 * 1024)).toFixed(2) + ' MB';
                        rows += `
                            <tr>
                                <td class="p-3 font-semibold text-slate-700 font-sans">${tbl}</td>
                                <td class="p-3 text-center">${s.scanned}</td>
                                <td class="p-3 text-center text-emerald-600 font-bold">${s.found_on_disk}</td>
                                <td class="p-3 text-center text-rose-500 font-bold">${s.orphans_in_db}</td>
                                <td class="p-3 text-center text-cyan-600 font-bold">${s.migrated}</td>
                                <td class="p-3 text-right text-slate-500">${mb}</td>
                            </tr>
                        `;
                    }
                    $('#tbodyStorageSummary').html(rows);
                    $('#boxTabelaStorage').removeClass('hidden');

                    let logTxt = `> Varredura concluída com sucesso!\n`;
                    logTxt += `> Total de registros no banco: ${d.scanned_in_db}\n`;
                    logTxt += `> Arquivos físicos encontrados no disco: ${d.found_on_disk}\n`;
                    logTxt += `> Registros órfãos (sem arquivo físico no disco): ${d.orphans_in_db}\n`;
                    logTxt += `> Volume total analisado: ${(d.total_bytes / (1024 * 1024)).toFixed(2)} MB\n`;

                    if (isDryRun) {
                        logTxt += `\n[SIMULAÇÃO DRY-RUN CONCLUÍDA]: Nenhum arquivo foi enviado e o banco não foi modificado.\n`;
                        logTxt += `Para migrar os ${d.found_on_disk} arquivos agora, clique em 'Migrar para S3 (--execute)'.\n`;
                        badge.removeClass('bg-cyan-900/60 text-cyan-300 animate-pulse')
                             .addClass('bg-emerald-900/60 text-emerald-300').text('Auditoria OK');
                    } else {
                        logTxt += `\n[MIGRAÇÃO CONCLUÍDA]: ${d.migrated_count} arquivos enviados com sucesso para o Oracle S3 e atualizados no banco!\n`;
                        if (d.error_count > 0) {
                            logTxt += `> Ocorreram ${d.error_count} erros durante o processo.\n`;
                        }
                        badge.removeClass('bg-cyan-900/60 text-cyan-300 animate-pulse')
                             .addClass('bg-emerald-900/60 text-emerald-300').text('Migração 100%');
                    }

                    logsDiv.text(logTxt);
                } else {
                    badge.removeClass('animate-pulse').addClass('bg-rose-900 text-rose-300').text('Erro na Operação');
                    logsDiv.append('\n> ERRO: ' + (res.message || 'Falha ao processar migração.'));
                    alert('Erro: ' + (res.message || 'Falha ao processar.'));
                }
            }, 'json').fail(function(xhr) {
                btnExec.prop('disabled', false).removeClass('opacity-50 cursor-not-allowed');
                btnAudit.prop('disabled', false).removeClass('opacity-50 cursor-not-allowed');
                badge.removeClass('animate-pulse').addClass('bg-rose-900 text-rose-300').text('Erro de Rede');
                logsDiv.append('\n> ERRO CRÍTICO: Falha na requisição HTTP (Status ' + xhr.status + ').');
                alert('Erro de conexão com o backend.');
            });
        }
    </script>
</body>
</html>
