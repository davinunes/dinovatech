<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers/AppHelper.php';
require_once __DIR__ . '/helpers/K3sClientHelper.php';
require_once __DIR__ . '/helpers/SaasProvisionerHelper.php';
require_once __DIR__ . '/../database.php';

// Trava de segurança: somente renderiza se a variável de ambiente permitir
if (!defined('SAAS_MASTER_ENABLED') || !SAAS_MASTER_ENABLED) {
    http_response_code(403);
    die('<div style="font-family:sans-serif;padding:30px;text-align:center;"><h2>Módulo SaaS Master Desativado</h2><p>Para ativar este módulo, configure <code>SAAS_MASTER_ENABLED=true</code> no arquivo .env.</p><a href="dashboard.php">Voltar ao Dashboard</a></div>');
}

$k3s = new K3sClientHelper();

// Endpoint AJAX interno para ações e testes de diagnóstico
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_action'])) {
    header('Content-Type: application/json; charset=utf-8');
    $action = $_POST['ajax_action'];

    if ($action === 'test_connection') {
        $health = $k3s->checkHealth();
        $nodes = $k3s->getNodes();
        $namespaces = $k3s->listNamespaces();

        echo json_encode([
            'success' => $health['healthy'],
            'health' => $health,
            'nodes' => $nodes['nodes'] ?? [],
            'namespaces_count' => count($namespaces['namespaces'] ?? []),
            'api_url' => defined('K3S_API_URL') ? K3S_API_URL : ''
        ]);
        exit();
    }

    if ($action === 'list_pods') {
        $ns = !empty($_POST['namespace']) ? trim($_POST['namespace']) : null;
        $pods = $k3s->listPods($ns);
        echo json_encode($pods);
        exit();
    }

    echo json_encode(['success' => false, 'error' => 'Ação não reconhecida.']);
    exit();
}

// 1. Carregamento dos Tenants e Contratos SaaS do banco
$tenants = [];
$totalMrr = 0.0;
$countRunning = 0;
$countSuspended = 0;
$countPending = 0;

$link = DBConnect();
if ($link) {
    $hasTable = false;
    $checkT = DBExecute($link, "SHOW TABLES LIKE 'saas_tenants'");
    if ($checkT && mysqli_num_rows($checkT) > 0) {
        $hasTable = true;
    }

    if ($hasTable) {
        $q = "SELECT T.*, C.nome AS nome_cliente, C.telefone, R.valor_sugerido_recorrencia, R.tipo_periodo, R.tipo_cobranca, R.motivo_cortesia, S.nome_servico 
              FROM saas_tenants T
              LEFT JOIN Clientes C ON T.id_cliente = C.id_cliente
              LEFT JOIN Recorrencias R ON T.id_recorrencia = R.id_recorrencia
              LEFT JOIN Servicos S ON R.id_servico = S.id_servico
              ORDER BY T.id_tenant DESC";
        $res = DBExecute($link, $q);
        $countCortesiasSaas = 0;
        $countTestesSaas = 0;
        if ($res) {
            while ($row = mysqli_fetch_assoc($res)) {
                $tenants[] = $row;
                $st = $row['status_pod'] ?? 'PENDING';
                if ($st === 'RUNNING') {
                    $countRunning++;
                } elseif ($st === 'SUSPENDED') {
                    $countSuspended++;
                } else {
                    $countPending++;
                }

                $tipoCob = $row['tipo_cobranca'] ?? 'COMERCIAL';
                if ($tipoCob === 'CORTESIA') {
                    $countCortesiasSaas++;
                } elseif ($tipoCob === 'TESTE') {
                    $countTestesSaas++;
                } else {
                    if (!empty($row['valor_sugerido_recorrencia'])) {
                        $totalMrr += (float)$row['valor_sugerido_recorrencia'];
                    }
                }
            }
        }
    }
    DBClose($link);
}

// 2. Carregamento de status do cluster
$initialHealth = $k3s->checkHealth();
$initialNodes = $k3s->getNodes();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Instâncias SaaS & Cluster K3s - Dinovatech</title>
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
                    <span class="inline-flex items-center justify-center p-2 bg-purple-100 text-purple-700 rounded-lg">
                        <span class="material-icons text-xl">cloud_sync</span>
                    </span>
                    <h1 class="text-2xl font-bold text-slate-800">Central de Instâncias SaaS (K3s)</h1>
                </div>
                <p class="text-sm text-slate-500 mt-1">Gerenciamento de contratos SaaS, isolamento de pods por cliente e controle de versões.</p>
            </div>
            <div class="flex items-center gap-2.5">
                <a href="saas_db_tool.php" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg text-sm transition shadow-sm">
                    <span class="material-icons text-sm">storage</span>
                    <span>Bancos SaaS (Tool)</span>
                </a>
                <a href="contrato_form.php" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-slate-800 hover:bg-slate-900 text-white font-medium rounded-lg text-sm transition shadow-sm">
                    <span class="material-icons text-sm">add</span>
                    <span>Novo Contrato SaaS</span>
                </a>
                <button type="button" id="btnSyncAllTenants" onclick="sincronizarStatusAoVivo()" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg shadow-sm transition text-sm">
                    <span class="material-icons text-sm" id="iconSyncAll">autorenew</span>
                    <span>Sincronizar Pods</span>
                </button>
                <button type="button" id="btnTestarK3s" onclick="testarConexaoCluster()" class="inline-flex items-center gap-2 px-3.5 py-2 bg-purple-600 hover:bg-purple-700 text-white font-medium rounded-lg shadow-sm transition text-sm">
                    <span class="material-icons text-sm" id="iconBtnTest">sync</span>
                    <span>Testar Cluster</span>
                </button>
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex-1 p-6 space-y-6 max-w-7xl mx-auto w-full">

            <!-- CARDS DE MÉTRICAS SAAS & NEGÓCIO -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Card 1: Total Tenants -->
                <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-sm flex items-center justify-between">
                    <div>
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Total de Instâncias</span>
                        <div class="text-2xl font-black text-slate-800 mt-1"><?= count($tenants) ?></div>
                        <span class="text-[11px] text-slate-500">Contratos SaaS ativos</span>
                    </div>
                    <div class="w-11 h-11 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center border border-purple-100">
                        <span class="material-icons text-2xl">dns</span>
                    </div>
                </div>

                <!-- Card 2: Pods Ativos -->
                <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-sm flex items-center justify-between">
                    <div>
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Pods em Execução</span>
                        <div class="text-2xl font-black text-emerald-600 mt-1"><?= $countRunning ?></div>
                        <span class="text-[11px] text-emerald-700 font-medium">Running no K3s</span>
                    </div>
                    <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center border border-emerald-100">
                        <span class="material-icons text-2xl">check_circle</span>
                    </div>
                </div>

                <!-- Card 3: Suspensos / Pendentes -->
                <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-sm flex items-center justify-between">
                    <div>
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Suspensos / Pendentes</span>
                        <div class="text-2xl font-black text-amber-600 mt-1"><?= $countSuspended + $countPending ?></div>
                        <span class="text-[11px] text-slate-500"><?= $countSuspended ?> suspensos / <?= $countPending ?> pendentes</span>
                    </div>
                    <div class="w-11 h-11 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center border border-amber-100">
                        <span class="material-icons text-2xl">pause_circle</span>
                    </div>
                </div>

                <!-- Card 4: MRR SaaS -->
                <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-sm flex items-center justify-between">
                    <div>
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Receita Recorrente (MRR)</span>
                        <div class="text-2xl font-black text-purple-700 mt-1">R$ <?= number_format($totalMrr, 2, ',', '.') ?></div>
                        <span class="text-[11px] text-purple-600 font-medium">Faturamento mensal<?= ($countCortesiasSaas + $countTestesSaas > 0) ? " • +{$countCortesiasSaas} cortesia / +{$countTestesSaas} teste" : "" ?></span>
                    </div>
                    <div class="w-11 h-11 rounded-xl bg-purple-50 text-purple-700 flex items-center justify-center border border-purple-100">
                        <span class="material-icons text-2xl">payments</span>
                    </div>
                </div>
            </div>

            <!-- TABELA CENTRAL DE CLIENTES / INSTÂNCIAS SAAS -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <span class="material-icons text-purple-600">view_list</span>
                        <h2 class="text-base font-bold text-slate-800">Instâncias Dedicadas de Clientes</h2>
                        <span class="text-xs font-semibold bg-purple-100 text-purple-800 px-2 py-0.5 rounded-full"><?= count($tenants) ?></span>
                    </div>
                    <div class="text-xs text-slate-500 flex items-center gap-2">
                        <span class="inline-flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-emerald-500"></span> Running</span>
                        <span class="inline-flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-amber-500"></span> Suspended</span>
                        <span class="inline-flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-slate-400"></span> Pending</span>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50 text-slate-600 uppercase text-xs tracking-wider border-b border-slate-200">
                            <tr>
                                <th class="py-3 px-4 font-semibold">Cliente / Contrato</th>
                                <th class="py-3 px-4 font-semibold">Subdomínio & Acesso</th>
                                <th class="py-3 px-4 font-semibold">Recursos Elásticos</th>
                                <th class="py-3 px-4 font-semibold">Versão Imagem & Rollout</th>
                                <th class="py-3 px-4 font-semibold">Banco de Dados</th>
                                <th class="py-3 px-4 font-semibold">Status Pod (Ao Vivo)</th>
                                <th class="py-3 px-4 font-semibold text-right">Ações Rápidas</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            <?php if (!empty($tenants)): ?>
                                <?php foreach ($tenants as $t): 
                                    $status = $t['status_pod'] ?? 'PENDING';
                                    $badge = 'bg-slate-100 text-slate-700 border-slate-200';
                                    $dot = 'bg-slate-400';
                                    if ($status === 'RUNNING') {
                                        $badge = 'bg-emerald-50 text-emerald-800 border-emerald-200';
                                        $dot = 'bg-emerald-500 animate-pulse';
                                    } elseif ($status === 'SUSPENDED') {
                                        $badge = 'bg-amber-50 text-amber-800 border-amber-200';
                                        $dot = 'bg-amber-500';
                                    } elseif ($status === 'ERROR') {
                                        $badge = 'bg-rose-50 text-rose-800 border-rose-200';
                                        $dot = 'bg-rose-500';
                                    }
                                ?>
                                    <tr class="hover:bg-slate-50/70 transition" id="row-tenant-<?= $t['id_tenant'] ?>" data-tenant-id="<?= $t['id_tenant'] ?>">
                                        <!-- Cliente / Contrato -->
                                        <td class="py-3.5 px-4 font-medium text-slate-900">
                                             <div class="flex items-center gap-2.5">
                                                <div class="w-9 h-9 rounded-lg bg-purple-50 text-purple-700 flex items-center justify-center font-bold text-xs border border-purple-100">
                                                    <?= strtoupper(substr($t['nome_cliente'] ?? 'C', 0, 2)) ?>
                                                </div>
                                                <div>
                                                    <div class="font-bold text-slate-800 leading-tight">
                                                        <?= htmlspecialchars($t['nome_cliente'] ?? 'Cliente Desconhecido') ?>
                                                    </div>
                                                    <?php $tenantTipoCob = $t['tipo_cobranca'] ?? 'COMERCIAL'; ?>
                                                    <div class="text-xs text-slate-500 flex items-center gap-1.5 mt-0.5 flex-wrap">
                                                        <span><?= htmlspecialchars($t['nome_servico'] ?? 'SaaS') ?></span>
                                                        <span>•</span>
                                                        <?php if ($tenantTipoCob === 'CORTESIA'): ?>
                                                            <span class="inline-flex items-center gap-0.5 px-1.5 py-0.2 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200" title="<?= htmlspecialchars($t['motivo_cortesia'] ?: 'Cortesia / Isento de cobrança') ?>">
                                                                <span class="material-icons text-[11px]">card_giftcard</span> Cortesia
                                                            </span>
                                                        <?php elseif ($tenantTipoCob === 'TESTE'): ?>
                                                            <span class="inline-flex items-center gap-0.5 px-1.5 py-0.2 rounded text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200" title="<?= htmlspecialchars($t['motivo_cortesia'] ?: 'Degustação / Homologação') ?>">
                                                                <span class="material-icons text-[11px]">science</span> Teste
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="font-semibold text-purple-700">R$ <?= number_format($t['valor_sugerido_recorrencia'] ?? 0, 2, ',', '.') ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- Subdomínio & URL -->
                                        <td class="py-3.5 px-4">
                                            <div class="flex items-center gap-1 font-mono text-xs">
                                                <span class="material-icons text-emerald-600 text-[14px]" title="SSL On-Demand Ativo">lock</span>
                                                <a href="https://<?= htmlspecialchars($t['subdominio']) ?>" target="_blank" class="text-cyan-700 hover:text-cyan-900 font-semibold hover:underline flex items-center gap-0.5">
                                                    <?= htmlspecialchars($t['subdominio']) ?>
                                                    <span class="material-icons text-[12px]">open_in_new</span>
                                                </a>
                                            </div>
                                            <div class="text-[11px] text-slate-400 font-mono mt-0.5">
                                                NS: <?= htmlspecialchars($t['namespace_k8s']) ?>
                                            </div>
                                        </td>

                                        <!-- Recursos Elásticos -->
                                        <td class="py-3.5 px-4">
                                            <div class="text-xs font-mono space-y-0.5">
                                                <div>
                                                    <span class="text-slate-400">RAM:</span> 
                                                    <span class="text-slate-700 font-semibold">64Mi ~ <?= htmlspecialchars($t['limite_memoria']) ?></span>
                                                </div>
                                                <div>
                                                    <span class="text-slate-400">CPU:</span> 
                                                    <span class="text-slate-700 font-semibold">50m ~ <?= htmlspecialchars($t['limite_cpu']) ?></span>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- Versão da Imagem Docker & Rollout -->
                                        <td class="py-3.5 px-4">
                                            <div class="space-y-1">
                                                <div class="inline-flex items-center gap-1 px-2 py-0.5 bg-slate-100 text-slate-700 border border-slate-200 rounded font-mono text-[11px] max-w-[200px] truncate" title="<?= htmlspecialchars($t['versao_imagem']) ?>">
                                                    <span class="material-icons text-[11px] text-purple-600">tag</span>
                                                    <span id="img-tag-<?= $t['id_tenant'] ?>" class="truncate"><?= htmlspecialchars($t['versao_imagem']) ?></span>
                                                </div>
                                                <div id="rollout-badge-<?= $t['id_tenant'] ?>" class="text-[10px] text-slate-500 font-medium flex items-center gap-1">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-slate-300"></span>
                                                    <span>Aguardando sync...</span>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- Banco de Dados -->
                                        <td class="py-3.5 px-4 font-mono text-xs">
                                            <div class="flex items-center gap-1.5">
                                                <span class="font-semibold text-slate-800"><?= htmlspecialchars($t['db_name']) ?></span>
                                                <a href="saas_db_tool.php?id_tenant=<?= $t['id_tenant'] ?>" class="text-emerald-600 hover:text-emerald-800 transition" title="Provisionar / Inicializar Base deste Tenant">
                                                    <span class="material-icons text-[14px]">build_circle</span>
                                                </a>
                                            </div>
                                            <div class="text-[11px] text-slate-400">usr: <?= htmlspecialchars($t['db_user']) ?></div>
                                        </td>

                                        <!-- Status Pod (Ao Vivo) -->
                                        <td class="py-3.5 px-4">
                                            <div id="status-container-<?= $t['id_tenant'] ?>">
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold border <?= $badge ?>">
                                                    <span class="w-2 h-2 rounded-full <?= $dot ?>"></span>
                                                    <span id="status-label-<?= $t['id_tenant'] ?>"><?= htmlspecialchars($status) ?></span>
                                                </span>
                                            </div>
                                        </td>

                                        <!-- Ações Rápidas -->
                                        <td class="py-3.5 px-4 text-right">
                                            <div class="flex items-center justify-end gap-1.5">
                                                <!-- Ver Detalhes do Rollout e Eventos K3s -->
                                                <button type="button" onclick="abrirModalRollout(<?= $t['id_tenant'] ?>, '<?= htmlspecialchars(addslashes($t['nome_cliente'] ?? '')) ?>')" class="p-1.5 text-cyan-600 hover:text-cyan-700 hover:bg-cyan-50 rounded-lg transition" title="Detalhes do Rollout, Pods e Eventos">
                                                    <span class="material-icons text-base">visibility</span>
                                                </button>

                                                <span id="action-btn-container-<?= $t['id_tenant'] ?>">
                                                    <?php if ($status === 'PENDING' || $status === 'ERROR'): ?>
                                                        <!-- Botão Deploy / Provisionar -->
                                                        <button type="button" onclick="provisionarTenant(<?= $t['id_tenant'] ?>)" class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-xs font-bold shadow-xs transition" title="Provisionar Pod no K3s">
                                                            <span class="material-icons text-xs">rocket_launch</span> Deploy
                                                        </button>
                                                    <?php elseif ($status === 'RUNNING'): ?>
                                                        <!-- Botão Suspender -->
                                                        <button type="button" onclick="alterarEscala(<?= $t['id_tenant'] ?>, 0)" class="p-1.5 text-amber-600 hover:bg-amber-50 rounded-lg transition" title="Suspender Pod (Escalar para 0)">
                                                            <span class="material-icons text-base">pause_circle_filled</span>
                                                        </button>
                                                    <?php elseif ($status === 'SUSPENDED'): ?>
                                                        <!-- Botão Reativar -->
                                                        <button type="button" onclick="alterarEscala(<?= $t['id_tenant'] ?>, 1)" class="p-1.5 text-emerald-600 hover:bg-emerald-50 rounded-lg transition" title="Reativar Pod (Escalar para 1)">
                                                            <span class="material-icons text-base">play_circle_filled</span>
                                                        </button>
                                                    <?php endif; ?>
                                                </span>

                                                <!-- Atualizar Versão (RollingUpdate) -->
                                                <button type="button" onclick="abrirModalUpdate(<?= $t['id_tenant'] ?>, '<?= htmlspecialchars(addslashes($t['nome_cliente'] ?? '')) ?>', '<?= htmlspecialchars(addslashes($t['versao_imagem'])) ?>')" class="p-1.5 text-indigo-600 hover:bg-indigo-50 rounded-lg transition" title="Atualizar Versão (RollingUpdate)">
                                                    <span class="material-icons text-base">upgrade</span>
                                                </button>

                                                <!-- Ver Logs -->
                                                <button type="button" onclick="abrirModalLogs(<?= $t['id_tenant'] ?>, '<?= htmlspecialchars(addslashes($t['nome_cliente'] ?? '')) ?>')" class="p-1.5 text-slate-600 hover:bg-slate-100 rounded-lg transition" title="Ver Logs do Container">
                                                    <span class="material-icons text-base">terminal</span>
                                                </button>

                                                <!-- Rotinas Agendadas & CronJobs K3s -->
                                                <button type="button" onclick="abrirModalCrons(<?= $t['id_tenant'] ?>, '<?= htmlspecialchars(addslashes($t['nome_cliente'] ?? '')) ?>', '<?= htmlspecialchars(addslashes($t['namespace_k8s'] ?? '')) ?>')" class="p-1.5 text-amber-600 hover:text-amber-700 hover:bg-amber-50 rounded-lg transition" title="Rotinas Agendadas & CronJobs K3s">
                                                    <span class="material-icons text-base">schedule</span>
                                                </button>

                                                <!-- Editar Contrato -->
                                                <a href="contrato_form.php?id=<?= $t['id_recorrencia'] ?>" class="p-1.5 text-slate-400 hover:text-slate-700 hover:bg-slate-100 rounded-lg transition" title="Editar Contrato">
                                                    <span class="material-icons text-base">settings</span>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="py-12 text-center text-slate-400">
                                        <span class="material-icons text-4xl mb-2 text-slate-300">cloud_off</span>
                                        <p class="font-medium text-slate-600">Nenhum contrato SaaS cadastrado ainda.</p>
                                        <p class="text-xs text-slate-400 mt-1">Ao cadastrar um contrato com um serviço do tipo "Instância SaaS", ele aparecerá aqui automaticamente.</p>
                                        <a href="contrato_form.php" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-xs font-semibold mt-4 transition">
                                            <span class="material-icons text-xs">add</span> Criar Primeiro Contrato SaaS
                                        </a>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- SEÇÃO EXPANSÍVEL: SAÚDE DO CLUSTER K3S & NÓS -->
            <details class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden group">
                <summary class="px-6 py-4 border-b border-slate-200 cursor-pointer flex items-center justify-between bg-slate-50/50 hover:bg-slate-100/50 transition">
                    <div class="flex items-center gap-2">
                        <span class="material-icons text-slate-600">storage</span>
                        <h3 class="font-bold text-slate-800">Diagnóstico e Infraestrutura do Cluster K3s</h3>
                        <span class="text-xs px-2 py-0.5 rounded-full <?= $initialHealth['healthy'] ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' ?> font-semibold">
                            <?= $initialHealth['healthy'] ? 'Cluster Online' : 'Cluster Offline' ?>
                        </span>
                    </div>
                    <span class="material-icons text-slate-400 group-open:rotate-180 transition-transform">expand_more</span>
                </summary>

                <div class="p-6 space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Card Status da API -->
                        <div class="bg-slate-50 rounded-xl p-4 border border-slate-200">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-semibold uppercase text-slate-400">API REST do K3s</span>
                                <span class="text-xs font-bold text-emerald-700"><?= $initialHealth['latency_ms'] ?> ms</span>
                            </div>
                            <div class="mt-2 text-xs font-mono text-slate-700 truncate" title="<?= htmlspecialchars(K3S_API_URL) ?>">
                                <?= htmlspecialchars(K3S_API_URL) ?>
                            </div>
                        </div>

                        <!-- Card Nó Master -->
                        <?php $firstNode = $initialNodes['nodes'][0] ?? null; ?>
                        <div class="bg-slate-50 rounded-xl p-4 border border-slate-200">
                            <span class="text-xs font-semibold uppercase text-slate-400">Nó Master Control-Plane</span>
                            <div class="text-sm font-bold text-slate-800 mt-1"><?= htmlspecialchars($firstNode['name'] ?? 'Nenhum nó') ?></div>
                            <div class="text-xs text-slate-500 mt-0.5"><?= htmlspecialchars($firstNode['os'] ?? '') ?> (<?= htmlspecialchars($firstNode['architecture'] ?? '') ?>)</div>
                        </div>

                        <!-- Card Recursos do Nó -->
                        <div class="bg-slate-50 rounded-xl p-4 border border-slate-200">
                            <span class="text-xs font-semibold uppercase text-slate-400">Capacidade da Máquina</span>
                            <div class="text-sm font-bold text-slate-800 mt-1"><?= htmlspecialchars($firstNode['cpu_capacity'] ?? '2') ?> vCPUs / <?= htmlspecialchars($firstNode['memory_allocatable'] ?? '11 GiB') ?> RAM</div>
                            <div class="text-xs text-purple-600 font-semibold mt-0.5">Ingress Caddy + WeasyPrint Compartilhado</div>
                        </div>
                    </div>

                    <!-- Tabela de Pods Brutos do Cluster -->
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <h4 class="text-xs font-bold text-slate-600 uppercase tracking-wider">Pods Brutos no Cluster (Todos os Namespaces)</h4>
                            <button type="button" onclick="carregarPodsCluster()" class="text-xs text-purple-600 hover:text-purple-800 font-semibold flex items-center gap-1">
                                <span class="material-icons text-xs">refresh</span> Atualizar Pods K3s
                            </button>
                        </div>
                        <div id="loadingPods" class="text-center py-6 text-slate-400">
                            <span class="material-icons text-2xl animate-spin mb-1">autorenew</span>
                            <p class="text-xs">Consultando pods via API REST do K3s...</p>
                        </div>
                        <div id="tabelaPodsWrapper" class="hidden overflow-x-auto border border-slate-200 rounded-lg">
                            <table class="w-full text-left text-xs">
                                <thead class="bg-slate-100 text-slate-500 uppercase">
                                    <tr>
                                        <th class="py-2.5 px-3">Namespace</th>
                                        <th class="py-2.5 px-3">Nome do Pod</th>
                                        <th class="py-2.5 px-3">Imagem Docker</th>
                                        <th class="py-2.5 px-3">Fase</th>
                                        <th class="py-2.5 px-3">Pronto</th>
                                        <th class="py-2.5 px-3">Restarts</th>
                                        <th class="py-2.5 px-3">IP Interno</th>
                                        <th class="py-2.5 px-3">Idade</th>
                                    </tr>
                                </thead>
                                <tbody id="listaPodsCorpo" class="divide-y divide-slate-100 font-mono text-slate-700"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </details>
        </main>
    </div>

    <!-- MODAL DE ATUALIZAÇÃO DE IMAGEM (ROLLINGUPDATE ZERO-DOWNTIME) -->
    <div id="modalUpdate" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs hidden z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full shadow-2xl border border-slate-200 overflow-hidden animate-in fade-in zoom-in-95 duration-150">
            <div class="px-6 py-4 bg-purple-50/70 border-b border-purple-100 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="material-icons text-purple-600">upgrade</span>
                    <h3 class="font-bold text-purple-900 text-sm">Atualizar Versão da Imagem</h3>
                </div>
                <button type="button" onclick="fecharModalUpdate()" class="text-slate-400 hover:text-slate-600">
                    <span class="material-icons">close</span>
                </button>
            </div>
            <form id="formUpdateImage" onsubmit="salvarNovaImagem(event)" class="p-6 space-y-4">
                <input type="hidden" id="modal_id_tenant" name="id_tenant">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Cliente / Instância</label>
                    <div id="modal_tenant_cliente" class="text-sm font-bold text-slate-900"></div>
                </div>
                <div>
                    <label for="modal_new_image" class="block text-xs font-semibold text-slate-700 mb-1">Nova Imagem Docker / Tag</label>
                    <input type="text" id="modal_new_image" name="new_image" required placeholder="ex: dinovatech:v1.2.0" class="w-full p-2.5 text-xs font-mono border border-slate-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:outline-hidden">
                    <p class="text-[11px] text-slate-500 mt-1.5 flex items-center gap-1">
                        <span class="material-icons text-xs text-purple-600">info</span>
                        <span>O K3s subirá o novo pod primeiro. O pod antigo só morrerá quando o novo estiver 100% pronto (Zero-Downtime).</span>
                    </p>
                </div>
                <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                    <button type="button" onclick="fecharModalUpdate()" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-lg transition">Cancelar</button>
                    <button type="submit" id="btnConfirmarUpdate" class="px-4 py-2 text-xs font-bold bg-purple-600 hover:bg-purple-700 text-white rounded-lg shadow-xs transition flex items-center gap-1">
                        <span class="material-icons text-xs">rocket</span> Aplicar RollingUpdate
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL DE LOGS DO CONTAINER -->
    <div id="modalLogs" class="fixed inset-0 bg-slate-900/70 backdrop-blur-xs hidden z-50 flex items-center justify-center p-4">
        <div class="bg-slate-950 rounded-2xl max-w-4xl w-full shadow-2xl border border-slate-800 overflow-hidden flex flex-col h-[80vh]">
            <div class="px-6 py-3.5 bg-slate-900 border-b border-slate-800 flex items-center justify-between text-slate-300">
                <div class="flex items-center gap-2">
                    <span class="material-icons text-emerald-400 text-sm">terminal</span>
                    <span class="font-bold text-xs text-white" id="modalLogsTitulo">Logs do Pod</span>
                    <span class="text-[11px] text-slate-400 font-mono" id="modalLogsSub"></span>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" onclick="recarregarLogsModal()" class="text-xs text-slate-400 hover:text-white flex items-center gap-1 px-2 py-1 rounded bg-slate-800 hover:bg-slate-700 transition font-mono">
                        <span class="material-icons text-xs">refresh</span> Recarregar
                    </button>
                    <button type="button" onclick="fecharModalLogs()" class="text-slate-400 hover:text-white">
                        <span class="material-icons">close</span>
                    </button>
                </div>
            </div>
            <div class="flex-1 p-4 overflow-y-auto font-mono text-xs leading-relaxed text-emerald-400 bg-slate-950 whitespace-pre-wrap select-all" id="modalLogsConteudo">
                Carregando logs do container...
            </div>
        </div>
    </div>

    <!-- MODAL DE DETALHES DO ROLLOUT, VERSÃO E EVENTOS K3S -->
    <div id="modalRolloutDetails" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs hidden z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-4xl w-full shadow-2xl border border-slate-200 overflow-hidden animate-in fade-in zoom-in-95 duration-150 flex flex-col max-h-[85vh]">
            <!-- Header Modal -->
            <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between border-b border-slate-800">
                <div class="flex items-center gap-2.5">
                    <span class="inline-flex items-center justify-center p-2 bg-indigo-500/20 text-indigo-400 rounded-lg">
                        <span class="material-icons text-xl">deployed_code</span>
                    </span>
                    <div>
                        <h3 class="font-bold text-sm text-white flex items-center gap-2">
                            <span id="modalRolloutCliente">Cliente</span>
                            <span id="modalRolloutStateBadge" class="text-[11px] font-semibold px-2 py-0.5 rounded-full bg-slate-700 text-slate-300">Consultando...</span>
                        </h3>
                        <p class="text-xs text-slate-400 font-mono mt-0.5" id="modalRolloutSub">Namespace: -</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" onclick="recarregarModalRollout()" class="text-xs text-slate-300 hover:text-white flex items-center gap-1 px-2.5 py-1 rounded bg-slate-800 hover:bg-slate-700 transition font-mono">
                        <span class="material-icons text-xs" id="iconRefreshRollout">refresh</span> Atualizar
                    </button>
                    <button type="button" onclick="fecharModalRollout()" class="text-slate-400 hover:text-white">
                        <span class="material-icons">close</span>
                    </button>
                </div>
            </div>

            <!-- Corpo Modal com Scroll -->
            <div class="p-6 overflow-y-auto space-y-6 flex-1 text-slate-700">
                <!-- Cards de Resumo -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Card 1: Imagem Ativa -->
                    <div class="bg-slate-50 p-4 rounded-xl border border-slate-200">
                        <span class="text-xs font-semibold uppercase text-slate-400">Imagem no K3s</span>
                        <div class="font-mono text-xs font-bold text-slate-900 mt-1 truncate" id="modalRolloutImgLive">-</div>
                        <div class="text-[11px] mt-1" id="modalRolloutImgSyncStatus">
                            <span class="text-slate-400">Verificando...</span>
                        </div>
                    </div>

                    <!-- Card 2: Prontidão / Réplicas -->
                    <div class="bg-slate-50 p-4 rounded-xl border border-slate-200">
                        <span class="text-xs font-semibold uppercase text-slate-400">Prontidão das Réplicas</span>
                        <div class="text-sm font-black text-slate-900 mt-1" id="modalRolloutReplicas">-</div>
                        <div class="text-[11px] text-slate-500 mt-0.5" id="modalRolloutUpdatedReplicas">-</div>
                    </div>

                    <!-- Card 3: Estratégia & Health -->
                    <div class="bg-slate-50 p-4 rounded-xl border border-slate-200">
                        <span class="text-xs font-semibold uppercase text-slate-400">Estratégia Zero-Downtime</span>
                        <div class="text-xs font-bold text-indigo-700 mt-1">RollingUpdate (Surge: 1, Unavail: 0)</div>
                        <div class="text-[11px] text-slate-500 mt-0.5">Readiness: GET /login.php:80</div>
                    </div>
                </div>

                <!-- Seção Pods Ativos -->
                <div>
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2 flex items-center gap-1.5">
                        <span class="material-icons text-sm text-purple-600">view_in_ar</span>
                        <span>Pods Ativos no Namespace</span>
                    </h4>
                    <div class="border border-slate-200 rounded-xl overflow-hidden shadow-xs">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-100 text-slate-600 uppercase">
                                <tr>
                                    <th class="py-2.5 px-3">Nome do Pod</th>
                                    <th class="py-2.5 px-3">Imagem do Container</th>
                                    <th class="py-2.5 px-3">Fase</th>
                                    <th class="py-2.5 px-3">Pronto</th>
                                    <th class="py-2.5 px-3">Restarts</th>
                                    <th class="py-2.5 px-3">Idade / IP</th>
                                </tr>
                            </thead>
                            <tbody id="modalRolloutPodsList" class="divide-y divide-slate-100 font-mono text-slate-700">
                                <tr><td colspan="6" class="py-4 text-center text-slate-400 font-sans">Consultando pods...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Seção Linha do Tempo de Eventos -->
                <div>
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2 flex items-center gap-1.5">
                        <span class="material-icons text-sm text-indigo-600">history</span>
                        <span>Linha do Tempo de Eventos do K3s (Rollout & Ciclo de Vida)</span>
                    </h4>
                    <div id="modalRolloutEventsList" class="space-y-2 max-h-56 overflow-y-auto pr-1">
                        <div class="text-xs text-slate-400 text-center py-4 font-sans">Consultando eventos...</div>
                    </div>
                </div>
            </div>

            <!-- Footer Modal -->
            <div class="px-6 py-3 bg-slate-50 border-t border-slate-200 flex items-center justify-between text-xs text-slate-500">
                <span class="flex items-center gap-1">
                    <span class="material-icons text-xs text-emerald-500">verified</span>
                    <span>Dados obtidos via API REST nativa do Kubernetes (porta 6443)</span>
                </span>
                <button type="button" onclick="fecharModalRollout()" class="px-4 py-1.5 bg-slate-800 hover:bg-slate-900 text-white font-medium rounded-lg transition">Fechar</button>
            </div>
        </div>
    </div>

    <!-- MODAL DE GESTÃO DE ROTINAS AGENDADAS (CRONJOBS K3S) -->
    <div id="modalCrons" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs hidden z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-4xl w-full shadow-2xl border border-slate-200 overflow-hidden animate-in fade-in zoom-in-95 duration-150 flex flex-col max-h-[90vh]">
            <!-- Header Modal -->
            <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between border-b border-slate-800">
                <div class="flex items-center gap-2.5">
                    <span class="inline-flex items-center justify-center p-2 bg-amber-500/20 text-amber-400 rounded-lg">
                        <span class="material-icons text-xl">schedule</span>
                    </span>
                    <div>
                        <h3 class="font-bold text-sm text-white flex items-center gap-2">
                            <span>Rotinas Agendadas & CronJobs K3s</span>
                            <span id="modalCronsClienteBadge" class="text-xs font-semibold px-2 py-0.5 rounded-full bg-slate-700 text-slate-200">-</span>
                        </h3>
                        <p class="text-xs text-slate-400 font-mono mt-0.5" id="modalCronsSub">Namespace: -</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" onclick="sincronizarCronsTenant()" class="text-xs text-amber-300 hover:text-white flex items-center gap-1 px-2.5 py-1 rounded bg-amber-950/60 border border-amber-800/60 hover:bg-amber-900 transition font-mono" title="Reconciliar todos os CronJobs no cluster K3s">
                        <span class="material-icons text-xs" id="iconSyncCrons">sync</span> Reconciliar K3s
                    </button>
                    <button type="button" onclick="fecharModalCrons()" class="text-slate-400 hover:text-white">
                        <span class="material-icons">close</span>
                    </button>
                </div>
            </div>

            <!-- Corpo Modal -->
            <div class="p-6 overflow-y-auto space-y-6 flex-1 text-slate-700">
                <input type="hidden" id="crons_id_tenant" value="">

                <!-- Presets Rápidos -->
                <div class="bg-amber-50/70 border border-amber-200 rounded-xl p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <div class="font-bold text-amber-950 text-xs flex items-center gap-1.5">
                            <span class="material-icons text-amber-600 text-sm">auto_fix_high</span>
                            <span>Preset Padrão de Faturamento</span>
                        </div>
                        <p class="text-[11px] text-amber-800 mt-0.5">
                            Preenche os campos abaixo com a rotina de faturamento de contratos recorrentes e agendamento de débito Pix Automático.
                        </p>
                    </div>
                    <button type="button" onclick="aplicarPresetFaturas()" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-xs font-bold shadow-xs transition whitespace-nowrap">
                        <span class="material-icons text-xs">add_task</span> Carregar Preset de Faturas
                    </button>
                </div>

                <!-- Formulário de Cadastro / Edição -->
                <div class="bg-slate-50 border border-slate-200 rounded-xl p-4">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-600 mb-3 flex items-center gap-1.5" id="formCronTitulo">
                        <span class="material-icons text-sm text-purple-600">post_add</span>
                        <span>Cadastrar / Editar Rotina Agendada</span>
                    </h4>
                    <form id="formCronTenant" onsubmit="salvarCronTenant(event)" class="space-y-3">
                        <input type="hidden" id="cron_id_cron" value="0">
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
                            <div class="md:col-span-4">
                                <label for="cron_nome_job" class="block text-xs font-bold text-slate-700 mb-1">
                                    Identificador / Nome do Job <span class="text-rose-500">*</span>
                                </label>
                                <input type="text" id="cron_nome_job" required placeholder="ex: faturas-recorrentes" class="w-full p-2 text-xs font-mono border border-slate-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                                <span class="text-[10px] text-slate-400">Nome do CronJob no K3s: <code>cron-{nome}</code></span>
                            </div>
                            <div class="md:col-span-8">
                                <label for="cron_descricao" class="block text-xs font-bold text-slate-700 mb-1">
                                    Descrição Amigável
                                </label>
                                <input type="text" id="cron_descricao" placeholder="ex: Geração Mensal de Faturas e Pix Automático" class="w-full p-2 text-xs border border-slate-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
                            <div class="md:col-span-8">
                                <label for="cron_script_path" class="block text-xs font-bold text-slate-700 mb-1">
                                    Caminho do Script PHP (no container) <span class="text-rose-500">*</span>
                                </label>
                                <div class="flex items-center">
                                    <span class="inline-flex items-center px-2 py-2 bg-slate-200 border border-r-0 border-slate-300 rounded-l-lg text-[11px] font-mono text-slate-600">/var/www/html/</span>
                                    <input type="text" id="cron_script_path" required placeholder="dinovatech/cron/gerar_faturas_recorrencias.php" class="w-full p-2 text-xs font-mono border border-slate-300 rounded-r-lg focus:ring-2 focus:ring-purple-500">
                                </div>
                            </div>
                            <div class="md:col-span-4">
                                <label for="cron_expression" class="block text-xs font-bold text-slate-700 mb-1">
                                    Periodicidade (Expressão Cron) <span class="text-rose-500">*</span>
                                </label>
                                <input type="text" id="cron_expression" required value="0 6 1 * *" placeholder="0 6 1 * *" class="w-full p-2 text-xs font-mono font-bold border border-slate-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                            </div>
                        </div>

                        <!-- Atalhos de Periodicidade -->
                        <div class="flex flex-wrap items-center gap-2 pt-1 text-[11px]">
                            <span class="text-slate-400 font-semibold">Atalhos:</span>
                            <button type="button" onclick="setCronExpr('0 6 1 * *')" class="px-2 py-0.5 bg-slate-200 hover:bg-slate-300 rounded text-slate-700 font-mono transition">Mensal (Dia 1 às 06h)</button>
                            <button type="button" onclick="setCronExpr('0 6 * * *')" class="px-2 py-0.5 bg-slate-200 hover:bg-slate-300 rounded text-slate-700 font-mono transition">Diário (06:00)</button>
                            <button type="button" onclick="setCronExpr('0 20 * * *')" class="px-2 py-0.5 bg-slate-200 hover:bg-slate-300 rounded text-slate-700 font-mono transition">Diário (20:00)</button>
                            <button type="button" onclick="setCronExpr('0 * * * *')" class="px-2 py-0.5 bg-slate-200 hover:bg-slate-300 rounded text-slate-700 font-mono transition">A cada 1 hora</button>
                            <button type="button" onclick="setCronExpr('*/15 * * * *')" class="px-2 py-0.5 bg-slate-200 hover:bg-slate-300 rounded text-slate-700 font-mono transition">A cada 15 min</button>
                        </div>

                        <div class="flex items-center justify-between pt-2 border-t border-slate-200">
                            <button type="button" onclick="limparFormCron()" class="text-xs text-slate-500 hover:text-slate-800 font-medium">Limpar Campos</button>
                            <button type="submit" id="btnSalvarCron" class="inline-flex items-center gap-1 px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-xs font-bold shadow-xs transition">
                                <span class="material-icons text-xs">save</span> Salvar Rotina & Sincronizar K3s
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Tabela de Rotinas Cadastradas -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 flex items-center gap-1.5">
                            <span class="material-icons text-sm text-purple-600">format_list_bulleted</span>
                            <span>Rotinas Cadastradas para este Tenant</span>
                        </h4>
                        <span id="cronsCountBadge" class="text-[11px] font-semibold bg-slate-200 text-slate-700 px-2 py-0.5 rounded-full">0 rotinas</span>
                    </div>

                    <div class="border border-slate-200 rounded-xl overflow-hidden shadow-xs">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-100 text-slate-600 uppercase">
                                <tr>
                                    <th class="py-2.5 px-3">Job / Descrição</th>
                                    <th class="py-2.5 px-3">Script Executado</th>
                                    <th class="py-2.5 px-3">Periodicidade</th>
                                    <th class="py-2.5 px-3">Status K3s</th>
                                    <th class="py-2.5 px-3 text-center">Ativo</th>
                                    <th class="py-2.5 px-3 text-right">Ações</th>
                                </tr>
                            </thead>
                            <tbody id="listaCronsCorpo" class="divide-y divide-slate-100">
                                <tr>
                                    <td colspan="6" class="py-6 text-center text-slate-400">Carregando rotinas agendadas...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Footer Modal -->
            <div class="px-6 py-3 bg-slate-50 border-t border-slate-200 flex items-center justify-between text-xs text-slate-500">
                <span class="flex items-center gap-1">
                    <span class="material-icons text-xs text-amber-500">schedule</span>
                    <span>CronJobs rodam em containers isolados no namespace do cliente com injeção do <code>tenant-env</code></span>
                </span>
                <button type="button" onclick="fecharModalCrons()" class="px-4 py-1.5 bg-slate-800 hover:bg-slate-900 text-white font-medium rounded-lg transition">Fechar</button>
            </div>
        </div>
    </div>

    <script>

        let currentLogTenantId = null;
        let currentRolloutTenantId = null;

        function provisionarTenant(idTenant) {
            if (!confirm('Deseja iniciar o provisionamento desta instância no Cluster K3s agora?')) return;
            $.post('app.php', { action: 'saas_provision_pod', id_tenant: idTenant }, function(res) {
                if (res.success) {
                    alert('Sucesso: ' + res.message);
                    window.location.reload();
                } else {
                    alert('Erro no provisionamento: ' + res.message);
                }
            }, 'json').fail(function() {
                alert('Erro de comunicação com o servidor.');
            });
        }

        function alterarEscala(idTenant, replicas) {
            const acao = (replicas === 0) ? 'suspender' : 'reativar';
            if (!confirm(`Deseja realmente ${acao} o Pod desta instância no Cluster K3s?`)) return;

            $.post('app.php', { action: 'saas_scale_pod', id_tenant: idTenant, replicas: replicas }, function(res) {
                if (res.success) {
                    alert(res.message);
                    window.location.reload();
                } else {
                    alert('Erro: ' + res.message);
                }
            }, 'json').fail(function() {
                alert('Falha de conexão ao alterar estado do pod.');
            });
        }

        function abrirModalUpdate(idTenant, cliente, imagemAtual) {
            $('#modal_id_tenant').val(idTenant);
            $('#modal_tenant_cliente').text(cliente);
            $('#modal_new_image').val(imagemAtual);
            $('#modalUpdate').removeClass('hidden');
        }

        function fecharModalUpdate() {
            $('#modalUpdate').addClass('hidden');
        }

        function salvarNovaImagem(e) {
            e.preventDefault();
            const idTenant = $('#modal_id_tenant').val();
            const newImage = $('#modal_new_image').val().trim();
            const btn = $('#btnConfirmarUpdate');
            const orig = btn.html();

            btn.prop('disabled', true).html('<span class="material-icons text-xs animate-spin">sync</span> Disparando...');

            $.post('app.php', { action: 'saas_update_image', id_tenant: idTenant, new_image: newImage }, function(res) {
                btn.prop('disabled', false).html(orig);
                if (res.success) {
                    alert(res.message);
                    fecharModalUpdate();
                    sincronizarStatusAoVivo();
                } else {
                    alert('Erro: ' + res.message);
                }
            }, 'json').fail(function() {
                btn.prop('disabled', false).html(orig);
                alert('Erro de comunicação ao disparar RollingUpdate.');
            });
        }

        function abrirModalLogs(idTenant, cliente) {
            currentLogTenantId = idTenant;
            $('#modalLogsTitulo').text('Logs: ' + cliente);
            $('#modalLogsSub').text('Consultando...');
            $('#modalLogsConteudo').text('Consultando logs do container no K3s...');
            $('#modalLogs').removeClass('hidden');

            recarregarLogsModal();
        }

        function fecharModalLogs() {
            $('#modalLogs').addClass('hidden');
            currentLogTenantId = null;
        }

        function recarregarLogsModal() {
            if (!currentLogTenantId) return;
            $('#modalLogsConteudo').html('<span class="text-slate-500">Buscando últimas linhas de log...</span>');

            $.post('app.php', { action: 'saas_get_pod_logs', id_tenant: currentLogTenantId, lines: 100 }, function(res) {
                if (res.success && res.data) {
                    $('#modalLogsSub').text(res.data.namespace + ' / ' + res.data.pod_name);
                    const logs = res.data.logs || 'Nenhum log gerado pelo container até o momento.';
                    $('#modalLogsConteudo').text(logs);
                } else {
                    $('#modalLogsConteudo').html('<span class="text-rose-400">Erro ao obter logs: ' + (res.message || 'Sem resposta') + '</span>');
                }
            }, 'json').fail(function() {
                $('#modalLogsConteudo').html('<span class="text-rose-400">Falha de conexão ao buscar logs.</span>');
            });
        }

        // ==========================================
        // DETALHES DE ROLLOUT E EVENTOS K3S
        // ==========================================
        function abrirModalRollout(idTenant, cliente) {
            currentRolloutTenantId = idTenant;
            $('#modalRolloutCliente').text(cliente);
            $('#modalRolloutSub').text('Consultando detalhes no K3s...');
            $('#modalRolloutDetails').removeClass('hidden');
            recarregarModalRollout();
        }

        function fecharModalRollout() {
            $('#modalRolloutDetails').addClass('hidden');
            currentRolloutTenantId = null;
        }

        function recarregarModalRollout() {
            if (!currentRolloutTenantId) return;
            const icon = $('#iconRefreshRollout');
            icon.addClass('animate-spin');

            $.post('app.php', { action: 'saas_get_live_details', id_tenant: currentRolloutTenantId }, function(res) {
                icon.removeClass('animate-spin');
                if (res.success && res.data) {
                    const d = res.data;
                    $('#modalRolloutSub').text('Namespace: ' + d.namespace + ' | Host: ' + d.subdominio);

                    // Badge de Estado do Rollout
                    let stateBadgeHtml = '';
                    if (d.rollout_state === 'RUNNING') {
                        stateBadgeHtml = '<span class="bg-emerald-500/20 text-emerald-300 px-2.5 py-0.5 rounded-full text-xs font-bold border border-emerald-500/30">100% Ativo & Saudável</span>';
                    } else if (d.rollout_state === 'UPDATING') {
                        stateBadgeHtml = '<span class="bg-indigo-500/20 text-indigo-300 px-2.5 py-0.5 rounded-full text-xs font-bold border border-indigo-500/30 animate-pulse">RollingUpdate em Progresso...</span>';
                    } else if (d.rollout_state === 'SUSPENDED') {
                        stateBadgeHtml = '<span class="bg-amber-500/20 text-amber-300 px-2.5 py-0.5 rounded-full text-xs font-bold border border-amber-500/30">Suspenso (0 Réplicas)</span>';
                    } else {
                        stateBadgeHtml = '<span class="bg-slate-700 text-slate-300 px-2.5 py-0.5 rounded-full text-xs font-bold">Pendente de Deploy</span>';
                    }
                    $('#modalRolloutStateBadge').html(stateBadgeHtml);

                    // Imagem Live vs DB
                    $('#modalRolloutImgLive').text(d.k8s_image || '-').attr('title', d.k8s_image);
                    if (d.is_image_sync) {
                        $('#modalRolloutImgSyncStatus').html('<span class="text-emerald-600 font-semibold flex items-center gap-1"><span class="material-icons text-xs">check</span> Imagem sincronizada com o banco</span>');
                    } else {
                        $('#modalRolloutImgSyncStatus').html('<span class="text-indigo-600 font-semibold flex items-center gap-1"><span class="material-icons text-xs">sync</span> Banco: ' + d.db_image + '</span>');
                    }

                    // Réplicas
                    $('#modalRolloutReplicas').text(d.ready_replicas + ' de ' + d.replicas + ' Pods Prontos');
                    $('#modalRolloutUpdatedReplicas').text(d.updated_replicas + ' réplica(s) atualizada(s) para a nova versão');

                    // Lista de Pods
                    const tbody = $('#modalRolloutPodsList');
                    tbody.empty();
                    if (d.pods && d.pods.length > 0) {
                        d.pods.forEach(function(p) {
                            const readyBadge = p.ready 
                                ? '<span class="inline-flex items-center gap-1 text-emerald-600 font-bold"><span class="w-2 h-2 rounded-full bg-emerald-500"></span> Sim (1/1)</span>'
                                : '<span class="inline-flex items-center gap-1 text-amber-600 font-bold"><span class="w-2 h-2 rounded-full bg-amber-500"></span> Não (0/1)</span>';
                            const phaseBadge = (p.phase === 'Running') ? 'text-emerald-700 font-bold' : 'text-slate-600';

                            tbody.append(`
                                <tr class="hover:bg-slate-50">
                                    <td class="py-2 px-3 font-semibold text-slate-900">${p.name}</td>
                                    <td class="py-2 px-3 text-[11px] text-purple-700 max-w-[200px] truncate" title="${p.image}">${p.image}</td>
                                    <td class="py-2 px-3 ${phaseBadge}">${p.phase}</td>
                                    <td class="py-2 px-3">${readyBadge}</td>
                                    <td class="py-2 px-3">${p.restart_count}</td>
                                    <td class="py-2 px-3 text-slate-500">${p.age || '-'} / ${p.pod_ip || '-'}</td>
                                </tr>
                            `);
                        });
                    } else {
                        tbody.html('<tr><td colspan="6" class="py-4 text-center text-slate-400 font-sans">Nenhum pod encontrado em execução.</td></tr>');
                    }

                    // Lista de Eventos
                    const eventsList = $('#modalRolloutEventsList');
                    eventsList.empty();
                    if (d.events && d.events.length > 0) {
                        d.events.forEach(function(ev) {
                            const isWarn = (ev.type === 'Warning');
                            const typeBadge = isWarn 
                                ? '<span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-800">Alerta</span>'
                                : '<span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-700">Evento</span>';

                            eventsList.append(`
                                <div class="p-2.5 rounded-lg border border-slate-200 bg-slate-50 flex items-start gap-2.5 text-xs">
                                    <div class="mt-0.5">${typeBadge}</div>
                                    <div class="flex-1">
                                        <div class="flex items-center justify-between">
                                            <span class="font-bold text-slate-800">${ev.reason} (${ev.object})</span>
                                            <span class="text-[11px] text-slate-400 font-mono">${ev.last_timestamp || ''}</span>
                                        </div>
                                        <p class="text-slate-600 mt-0.5 text-xs font-mono">${ev.message}</p>
                                    </div>
                                </div>
                            `);
                        });
                    } else {
                        eventsList.html('<div class="text-xs text-slate-400 text-center py-4 font-sans">Nenhum evento registrado recentemente neste namespace.</div>');
                    }
                } else {
                    alert('Erro ao carregar detalhes: ' + (res.message || 'Sem resposta.'));
                }
            }, 'json').fail(function() {
                icon.removeClass('animate-spin');
                alert('Falha de conexão com a API K3s.');
            });
        }

        // ==========================================
        // SINCRONIZAÇÃO AO VIVO DE TODOS OS TENANTS
        // ==========================================
        function sincronizarStatusAoVivo() {
            const btn = $('#btnSyncAllTenants');
            const icon = $('#iconSyncAll');
            icon.addClass('animate-spin');
            btn.prop('disabled', true);

            $.post('app.php', { action: 'saas_sync_all_tenants' }, function(res) {
                icon.removeClass('animate-spin');
                btn.prop('disabled', false);

                if (res.success && res.data && res.data.tenants) {
                    res.data.tenants.forEach(function(t) {
                        // Atualiza tag de imagem
                        $('#img-tag-' + t.id_tenant).text(t.k8s_image);

                        // Atualiza badge de rollout
                        let rolloutHtml = '';
                        if (t.rollout_state === 'RUNNING') {
                            rolloutHtml = '<span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span><span class="text-emerald-700 font-semibold">' + t.ready_replicas + '/' + t.replicas + ' Ready</span>';
                        } else if (t.rollout_state === 'UPDATING') {
                            rolloutHtml = '<span class="w-1.5 h-1.5 rounded-full bg-indigo-500 animate-ping"></span><span class="text-indigo-700 font-semibold">Atualizando...</span>';
                        } else if (t.rollout_state === 'SUSPENDED') {
                            rolloutHtml = '<span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span><span class="text-amber-700 font-semibold">0/0 Suspenso</span>';
                        } else {
                            rolloutHtml = '<span class="w-1.5 h-1.5 rounded-full bg-slate-300"></span><span class="text-slate-400">Pendente</span>';
                        }
                        $('#rollout-badge-' + t.id_tenant).html(rolloutHtml);

                        // Atualiza status do pod
                        let statusBadgeHtml = '';
                        if (t.rollout_state === 'RUNNING') {
                            statusBadgeHtml = '<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold border bg-emerald-50 text-emerald-800 border-emerald-200"><span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span><span>RUNNING</span></span>';
                        } else if (t.rollout_state === 'SUSPENDED') {
                            statusBadgeHtml = '<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold border bg-amber-50 text-amber-800 border-amber-200"><span class="w-2 h-2 rounded-full bg-amber-500"></span><span>SUSPENDED</span></span>';
                        } else {
                            statusBadgeHtml = '<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold border bg-slate-100 text-slate-700 border-slate-200"><span class="w-2 h-2 rounded-full bg-slate-400"></span><span>' + t.status_pod + '</span></span>';
                        }
                        $('#status-container-' + t.id_tenant).html(statusBadgeHtml);

                        // Atualiza botões de ação
                        let actionHtml = '';
                        if (t.rollout_state === 'RUNNING') {
                            actionHtml = '<button type="button" onclick="alterarEscala(' + t.id_tenant + ', 0)" class="p-1.5 text-amber-600 hover:bg-amber-50 rounded-lg transition" title="Suspender Pod"><span class="material-icons text-base">pause_circle_filled</span></button>';
                        } else if (t.rollout_state === 'SUSPENDED') {
                            actionHtml = '<button type="button" onclick="alterarEscala(' + t.id_tenant + ', 1)" class="p-1.5 text-emerald-600 hover:bg-emerald-50 rounded-lg transition" title="Reativar Pod"><span class="material-icons text-base">play_circle_filled</span></button>';
                        } else if (t.rollout_state === 'NOT_DEPLOYED' || t.rollout_state === 'PENDING') {
                            actionHtml = '<button type="button" onclick="provisionarTenant(' + t.id_tenant + ')" class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-xs font-bold shadow-xs transition" title="Provisionar Pod no K3s"><span class="material-icons text-xs">rocket_launch</span> Deploy</button>';
                        }
                        $('#action-btn-container-' + t.id_tenant).html(actionHtml);
                    });
                }
            }, 'json').fail(function() {
                icon.removeClass('animate-spin');
                btn.prop('disabled', false);
            });
        }

        function testarConexaoCluster() {
            const btn = $('#btnTestarK3s');
            const icon = $('#iconBtnTest');
            icon.addClass('animate-spin');
            btn.prop('disabled', true);

            $.post('saas_tenants.php', { ajax_action: 'test_connection' }, function(data) {
                icon.removeClass('animate-spin');
                btn.prop('disabled', false);
                alert('Conexão K3s testada! Latência: ' + (data.health.latency_ms || 0) + ' ms');
            }, 'json').fail(function() {
                icon.removeClass('animate-spin');
                btn.prop('disabled', false);
                alert('Falha ao comunicar com o cluster K3s.');
            });
        }

        function carregarPodsCluster() {
            $('#loadingPods').removeClass('hidden');
            $('#tabelaPodsWrapper').addClass('hidden');

            $.post('saas_tenants.php', { ajax_action: 'list_pods' }, function(data) {
                $('#loadingPods').addClass('hidden');
                $('#tabelaPodsWrapper').removeClass('hidden');

                const tbody = $('#listaPodsCorpo');
                tbody.empty();

                if (data.pods && data.pods.length > 0) {
                    data.pods.forEach(function(pod) {
                        const phaseClass = (pod.phase === 'Running') ? 'text-emerald-600 font-bold' : 'text-slate-500';
                        const readyIcon = pod.ready 
                            ? '<span class="text-emerald-600 font-bold">1/1</span>'
                            : '<span class="text-amber-600 font-bold">0/1</span>';
                        const row = `
                            <tr class="hover:bg-slate-50">
                                <td class="py-2 px-3 font-semibold text-slate-800">${pod.namespace}</td>
                                <td class="py-2 px-3">${pod.name}</td>
                                <td class="py-2 px-3 text-purple-700 max-w-[220px] truncate" title="${pod.image || ''}">${pod.image || '-'}</td>
                                <td class="py-2 px-3 ${phaseClass}">${pod.phase}</td>
                                <td class="py-2 px-3">${readyIcon}</td>
                                <td class="py-2 px-3">${pod.restart_count || 0}</td>
                                <td class="py-2 px-3">${pod.pod_ip || '-'}</td>
                                <td class="py-2 px-3 text-slate-400">${pod.age || pod.start_time || '-'}</td>
                            </tr>
                        `;
                        tbody.append(row);
                    });
                } else {
                    tbody.html('<tr><td colspan="8" class="py-4 text-center text-slate-400">Nenhum pod encontrado.</td></tr>');
                }
            }, 'json');
        }

        // =========================================================================
        // GESTÃO DE ROTINAS AGENDADAS & CRONJOBS K3S (MULTI-TENANT)
        // =========================================================================
        let currentCronsTenantId = null;

        function abrirModalCrons(idTenant, cliente, namespace) {
            currentCronsTenantId = idTenant;
            $('#crons_id_tenant').val(idTenant);
            $('#modalCronsClienteBadge').text(cliente);
            $('#modalCronsSub').text('Namespace: ' + namespace);
            limparFormCron();
            $('#modalCrons').removeClass('hidden');
            carregarCronsTenant(idTenant);
        }

        function fecharModalCrons() {
            $('#modalCrons').addClass('hidden');
            currentCronsTenantId = null;
        }

        function setCronExpr(expr) {
            $('#cron_expression').val(expr);
        }

        function limparFormCron() {
            $('#cron_id_cron').val(0);
            $('#cron_nome_job').val('').prop('readonly', false);
            $('#cron_descricao').val('');
            $('#cron_script_path').val('');
            $('#cron_expression').val('0 6 1 * *');
            $('#formCronTitulo').html('<span class="material-icons text-sm text-purple-600">post_add</span><span>Cadastrar Nova Rotina Agendada</span>');
            $('#btnSalvarCron').html('<span class="material-icons text-xs">save</span> Salvar Rotina & Sincronizar K3s');
        }

        function aplicarPresetFaturas() {
            $('#cron_id_cron').val(0);
            $('#cron_nome_job').val('faturas-recorrentes');
            $('#cron_descricao').val('Geração Mensal de Faturas e Débito Pix Automático');
            $('#cron_script_path').val('dinovatech/cron/gerar_faturas_recorrencias.php');
            $('#cron_expression').val('0 6 1 * *');
            $('#cron_nome_job').focus();
        }

        function carregarCronsTenant(idTenant) {
            const tbody = $('#listaCronsCorpo');
            tbody.html('<tr><td colspan="6" class="py-6 text-center text-slate-400"><span class="material-icons text-base animate-spin mr-1">sync</span> Consultando banco de dados e cluster K3s...</td></tr>');

            $.post('app.php', { action: 'saas_get_tenant_crons', id_tenant: idTenant }, function(res) {
                if (res.success) {
                    const crons = res.crons || [];
                    $('#cronsCountBadge').text(crons.length + ' rotina(s)');

                    if (crons.length === 0) {
                        tbody.html('<tr><td colspan="6" class="py-6 text-center text-slate-400">Nenhuma rotina agendada cadastrada para este tenant. Clique em "Carregar Preset de Faturas" acima para iniciar.</td></tr>');
                        return;
                    }

                    tbody.empty();
                    crons.forEach(function(c) {
                        const ativo = (parseInt(c.ativo) === 1);
                        const k8sExists = c.k8s_exists;
                        const k8sSuspend = c.k8s_suspend;

                        // Badge K3s
                        let k8sBadge = '';
                        if (k8sExists) {
                            if (k8sSuspend) {
                                k8sBadge = '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200"><span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span> Pausado no K3s</span>';
                            } else {
                                k8sBadge = '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span> Ativo no K3s</span>';
                            }
                        } else {
                            k8sBadge = '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-medium bg-slate-100 text-slate-500 border border-slate-200">Não criado no K3s</span>';
                        }

                        // Formatação cron legível
                        let exprLegivel = c.cron_expression;
                        if (c.cron_expression === '0 6 1 * *') exprLegivel = 'Todo dia 1º às 06h';
                        else if (c.cron_expression === '0 6 * * *') exprLegivel = 'Diário às 06h';
                        else if (c.cron_expression === '0 20 * * *') exprLegivel = 'Diário às 20h';

                        const tr = `
                            <tr class="hover:bg-slate-50 transition">
                                <td class="py-2.5 px-3">
                                    <div class="font-bold text-slate-800 font-mono text-xs">cron-${c.nome_job}</div>
                                    <div class="text-[11px] text-slate-500">${c.descricao || 'Sem descrição'}</div>
                                </td>
                                <td class="py-2.5 px-3 font-mono text-[11px] text-purple-700 max-w-[220px] truncate" title="${c.script_path}">
                                    ${c.script_path}
                                </td>
                                <td class="py-2.5 px-3">
                                    <span class="font-mono font-bold text-slate-800">${c.cron_expression}</span>
                                    <div class="text-[10px] text-slate-400">${exprLegivel}</div>
                                </td>
                                <td class="py-2.5 px-3">
                                    ${k8sBadge}
                                    ${c.k8s_last_schedule ? `<div class="text-[10px] text-slate-400 mt-0.5 font-mono">Último: ${c.k8s_last_schedule.substring(0, 16)}</div>` : ''}
                                </td>
                                <td class="py-2.5 px-3 text-center">
                                    <button type="button" onclick="alternarStatusCron(${c.id_cron}, ${ativo ? 0 : 1})" class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold ${ativo ? 'bg-emerald-100 text-emerald-800 hover:bg-emerald-200' : 'bg-slate-200 text-slate-600 hover:bg-slate-300'} transition">
                                        ${ativo ? 'SIM' : 'NÃO'}
                                    </button>
                                </td>
                                <td class="py-2.5 px-3 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <button type="button" onclick='editarCron(${JSON.stringify(c)})' class="p-1 text-indigo-600 hover:bg-indigo-50 rounded" title="Editar">
                                            <span class="material-icons text-sm">edit</span>
                                        </button>
                                        <button type="button" onclick="excluirCronTenant(${c.id_cron})" class="p-1 text-rose-600 hover:bg-rose-50 rounded" title="Excluir">
                                            <span class="material-icons text-sm">delete</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        `;
                        tbody.append(tr);
                    });
                } else {
                    tbody.html('<tr><td colspan="6" class="py-4 text-center text-rose-500">Erro ao carregar rotinas: ' + res.message + '</td></tr>');
                }
            }, 'json').fail(function() {
                tbody.html('<tr><td colspan="6" class="py-4 text-center text-rose-500">Falha de conexão com o backend.</td></tr>');
            });
        }

        function editarCron(c) {
            $('#cron_id_cron').val(c.id_cron);
            $('#cron_nome_job').val(c.nome_job).prop('readonly', true);
            $('#cron_descricao').val(c.descricao || '');
            $('#cron_script_path').val(c.script_path);
            $('#cron_expression').val(c.cron_expression);
            $('#formCronTitulo').html('<span class="material-icons text-sm text-indigo-600">edit</span><span>Editando Rotina: ' + c.nome_job + '</span>');
            $('#btnSalvarCron').html('<span class="material-icons text-xs">save</span> Atualizar Rotina');
            $('#cron_descricao').focus();
        }

        function salvarCronTenant(e) {
            e.preventDefault();
            if (!currentCronsTenantId) return;

            const btn = $('#btnSalvarCron');
            const orig = btn.html();
            btn.prop('disabled', true).html('<span class="material-icons text-xs animate-spin mr-1">sync</span> Salvando no K3s...');

            const payload = {
                action: 'saas_save_tenant_cron',
                id_tenant: currentCronsTenantId,
                id_cron: $('#cron_id_cron').val(),
                nome_job: $('#cron_nome_job').val().trim(),
                descricao: $('#cron_descricao').val().trim(),
                script_path: $('#cron_script_path').val().trim(),
                cron_expression: $('#cron_expression').val().trim(),
                ativo: 1
            };

            $.post('app.php', payload, function(res) {
                btn.prop('disabled', false).html(orig);
                if (res.success) {
                    alert(res.message);
                    limparFormCron();
                    carregarCronsTenant(currentCronsTenantId);
                } else {
                    alert('Erro: ' + res.message);
                }
            }, 'json').fail(function() {
                btn.prop('disabled', false).html(orig);
                alert('Falha de conexão ao salvar rotina.');
            });
        }

        function alternarStatusCron(idCron, novoStatus) {
            $.post('app.php', { action: 'saas_toggle_tenant_cron', id_cron: idCron, ativo: novoStatus }, function(res) {
                if (res.success) {
                    carregarCronsTenant(currentCronsTenantId);
                } else {
                    alert('Erro: ' + res.message);
                }
            }, 'json').fail(function() {
                alert('Falha ao alternar status da rotina.');
            });
        }

        function excluirCronTenant(idCron) {
            if (!confirm('Deseja realmente excluir esta rotina agendada? O CronJob correspondente será removido do K3s.')) return;

            $.post('app.php', { action: 'saas_delete_tenant_cron', id_cron: idCron }, function(res) {
                if (res.success) {
                    alert(res.message);
                    carregarCronsTenant(currentCronsTenantId);
                } else {
                    alert('Erro: ' + res.message);
                }
            }, 'json').fail(function() {
                alert('Falha ao excluir rotina.');
            });
        }

        function sincronizarCronsTenant() {
            if (!currentCronsTenantId) return;
            const icon = $('#iconSyncCrons');
            icon.addClass('animate-spin');

            $.post('app.php', { action: 'saas_sync_tenant_crons', id_tenant: currentCronsTenantId }, function(res) {
                icon.removeClass('animate-spin');
                alert(res.message);
                carregarCronsTenant(currentCronsTenantId);
            }, 'json').fail(function() {
                icon.removeClass('animate-spin');
                alert('Falha de comunicação ao sincronizar crons no cluster.');
            });
        }

        $(document).ready(function() {
            sincronizarStatusAoVivo();
            carregarPodsCluster();
        });
    </script>

</body>
</html>
