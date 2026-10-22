<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers/AppHelper.php';
require_once __DIR__ . '/helpers/K3sClientHelper.php';

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

// Carregamento inicial de status do cluster
$initialHealth = $k3s->checkHealth();
$initialNodes = $k3s->getNodes();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel SaaS & Orquestração K3s - Dinovatech</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
</head>
<body class="bg-slate-100 font-sans antialiased">
    <?php include 'components/sidebar.php'; ?>

    <div class="lg:pl-64 flex flex-col min-h-screen">
        <!-- Header -->
        <header class="bg-white border-b border-slate-200 px-6 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center justify-center p-2 bg-purple-100 text-purple-700 rounded-lg">
                        <span class="material-icons text-xl">cloud_sync</span>
                    </span>
                    <h1 class="text-2xl font-bold text-slate-800">Orquestração SaaS & Cluster K3s</h1>
                </div>
                <p class="text-sm text-slate-500 mt-1">Gerenciamento de pods, isolamento de containers por tenant e monitoramento de infraestrutura.</p>
            </div>
            <div class="flex items-center gap-3">
                <button type="button" id="btnTestarK3s" onclick="testarConexaoCluster()" class="inline-flex items-center gap-2 px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white font-medium rounded-lg shadow-sm transition-all text-sm">
                    <span class="material-icons text-sm" id="iconBtnTest">sync</span>
                    <span>Testar Conexão K3s</span>
                </button>
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex-1 p-6 space-y-6 max-w-7xl mx-auto w-full">
            <!-- Cards de Diagnóstico do Cluster -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Card 1: Status da API -->
                <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-sm flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Status da API K3s</span>
                        <span id="badgeStatus" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold <?= $initialHealth['healthy'] ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' ?>">
                            <span class="w-2 h-2 rounded-full <?= $initialHealth['healthy'] ? 'bg-emerald-500 animate-pulse' : 'bg-rose-500' ?>"></span>
                            <span id="badgeStatusText"><?= $initialHealth['healthy'] ? 'ONLINE (Ready)' : 'OFFLINE' ?></span>
                        </span>
                    </div>
                    <div class="mt-4">
                        <div class="text-xs text-slate-500">Endpoint:</div>
                        <div class="text-sm font-mono text-slate-700 truncate font-semibold" title="<?= htmlspecialchars(K3S_API_URL) ?>">
                            <?= htmlspecialchars(K3S_API_URL) ?>
                        </div>
                    </div>
                    <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                        <span>Latência REST:</span>
                        <span id="labelLatencia" class="font-bold text-slate-700"><?= $initialHealth['latency_ms'] ?> ms</span>
                    </div>
                </div>

                <!-- Card 2: Nó Master -->
                <?php 
                $firstNode = $initialNodes['nodes'][0] ?? null;
                ?>
                <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-sm flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Nó Master Control-Plane</span>
                        <span class="material-icons text-purple-500">dns</span>
                    </div>
                    <div class="mt-2">
                        <div class="text-lg font-bold text-slate-800" id="labelNodeName">
                            <?= htmlspecialchars($firstNode['name'] ?? 'Nenhum nó detectado') ?>
                        </div>
                        <div class="text-xs text-slate-500 mt-1" id="labelNodeOs">
                            <?= htmlspecialchars($firstNode['os'] ?? '') ?> (<?= htmlspecialchars($firstNode['architecture'] ?? '') ?>)
                        </div>
                    </div>
                    <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                        <span>Versão Kubelet:</span>
                        <span id="labelKubeVersion" class="font-bold text-slate-700"><?= htmlspecialchars($firstNode['kubelet_version'] ?? 'N/A') ?></span>
                    </div>
                </div>

                <!-- Card 3: Recursos Alocáveis -->
                <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-sm flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Capacidade do Nó</span>
                        <span class="material-icons text-cyan-500">memory</span>
                    </div>
                    <div class="mt-2 space-y-2">
                        <div>
                            <div class="flex justify-between text-xs font-medium text-slate-600 mb-1">
                                <span>CPUs:</span>
                                <span id="labelCpuCap" class="font-bold"><?= htmlspecialchars($firstNode['cpu_capacity'] ?? '2') ?> vCPUs</span>
                            </div>
                            <div class="w-full bg-slate-100 rounded-full h-2">
                                <div class="bg-cyan-500 h-2 rounded-full" style="width: 25%"></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex justify-between text-xs font-medium text-slate-600 mb-1">
                                <span>RAM Alocável:</span>
                                <span id="labelMemCap" class="font-bold"><?= htmlspecialchars($firstNode['memory_allocatable'] ?? '11 GiB') ?></span>
                            </div>
                            <div class="w-full bg-slate-100 rounded-full h-2">
                                <div class="bg-purple-500 h-2 rounded-full" style="width: 20%"></div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3 pt-2 border-t border-slate-100 text-xs text-slate-400">
                        Pronto para orquestrar múltiplos containers PHP isolados (~35 MB cada).
                    </div>
                </div>
            </div>

            <!-- Banner Informativo -->
            <div class="bg-gradient-to-r from-purple-900 to-indigo-900 rounded-xl p-6 text-white shadow-md flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                <div class="space-y-1">
                    <h2 class="text-lg font-bold flex items-center gap-2">
                        <span class="material-icons text-amber-400">auto_awesome</span>
                        Playground de Isolamento por Containers (Multi-Tenant SaaS)
                    </h2>
                    <p class="text-sm text-purple-200">
                        O seu sistema principal continua seguro na VPS atual. Este cluster K3s funciona como playground para testes de provisionamento, roteamento de subdomínios via Caddy e sidecar compartilhado WeasyPrint.
                    </p>
                </div>
                <div class="flex-shrink-0">
                    <span class="px-3 py-1 bg-white/10 text-white rounded-lg text-xs font-mono border border-white/20">
                        K3s v1.36.5+k3s1
                    </span>
                </div>
            </div>

            <!-- Seção de Ações Rápidas de Diagnóstico -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                    <h3 class="font-bold text-slate-800 flex items-center gap-2">
                        <span class="material-icons text-slate-500">terminal</span>
                        Pods Ativos no Cluster K3s
                    </h3>
                    <button type="button" onclick="carregarPodsCluster()" class="text-xs text-purple-600 hover:text-purple-800 font-semibold flex items-center gap-1">
                        <span class="material-icons text-xs">refresh</span> Atualizar Lista
                    </button>
                </div>
                <div class="p-6">
                    <div id="loadingPods" class="text-center py-8 text-slate-400">
                        <span class="material-icons text-3xl animate-spin mb-2">autorenew</span>
                        <p class="text-sm">Consultando pods via API REST do K3s...</p>
                    </div>
                    <div id="tabelaPodsWrapper" class="hidden overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-slate-50 text-slate-500 uppercase text-xs">
                                <tr>
                                    <th class="py-3 px-4">Namespace</th>
                                    <th class="py-3 px-4">Nome do Pod</th>
                                    <th class="py-3 px-4">Fase</th>
                                    <th class="py-3 px-4">IP Interno</th>
                                    <th class="py-3 px-4">Data de Criação</th>
                                </tr>
                            </thead>
                            <tbody id="listaPodsCorpo" class="divide-y divide-slate-100 text-slate-700">
                                <!-- Preenchido dinamicamente via JS -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        function testarConexaoCluster() {
            const btn = $('#btnTestarK3s');
            const icon = $('#iconBtnTest');
            icon.addClass('animate-spin');
            btn.prop('disabled', true);

            $.post('saas_tenants.php', { ajax_action: 'test_connection' }, function(data) {
                icon.removeClass('animate-spin');
                btn.prop('disabled', false);

                if (data.success) {
                    $('#badgeStatus')
                        .removeClass('bg-rose-100 text-rose-800')
                        .addClass('bg-emerald-100 text-emerald-800');
                    $('#badgeStatus span:first')
                        .removeClass('bg-rose-500')
                        .addClass('bg-emerald-500 animate-pulse');
                    $('#badgeStatusText').text('ONLINE (Ready)');
                    $('#labelLatencia').text(data.health.latency_ms + ' ms');

                    if (data.nodes && data.nodes.length > 0) {
                        const n = data.nodes[0];
                        $('#labelNodeName').text(n.name);
                        $('#labelNodeOs').text(n.os + ' (' + n.architecture + ')');
                        $('#labelKubeVersion').text(n.kubelet_version);
                    }
                } else {
                    $('#badgeStatus')
                        .removeClass('bg-emerald-100 text-emerald-800')
                        .addClass('bg-rose-100 text-rose-800');
                    $('#badgeStatus span:first')
                        .removeClass('bg-emerald-500 animate-pulse')
                        .addClass('bg-rose-500');
                    $('#badgeStatusText').text('OFFLINE');
                    alert('Erro ao conectar na API do K3s: ' + (data.health.error || 'Falha de comunicação.'));
                }
            }).fail(function() {
                icon.removeClass('animate-spin');
                btn.prop('disabled', false);
                alert('Falha na requisição AJAX ao testar K3s.');
            });
        }

        function carregarPodsCluster() {
            $('#loadingPods').removeClass('hidden');
            $('#tabelaPodsWrapper').addClass('hidden');

            $.post('saas_tenants.php', { ajax_action: 'list_pods' }, function(res) {
                $('#loadingPods').addClass('hidden');
                $('#tabelaPodsWrapper').removeClass('hidden');

                const tbody = $('#listaPodsCorpo');
                tbody.empty();

                if (res.success && res.pods && res.pods.length > 0) {
                    res.pods.forEach(function(pod) {
                        let statusBadge = '<span class="px-2 py-0.5 rounded text-xs font-semibold bg-slate-100 text-slate-700">' + pod.phase + '</span>';
                        if (pod.phase === 'Running') {
                            statusBadge = '<span class="px-2 py-0.5 rounded text-xs font-semibold bg-emerald-100 text-emerald-800">Running</span>';
                        } else if (pod.phase === 'Pending') {
                            statusBadge = '<span class="px-2 py-0.5 rounded text-xs font-semibold bg-amber-100 text-amber-800">Pending</span>';
                        } else if (pod.phase === 'Failed') {
                            statusBadge = '<span class="px-2 py-0.5 rounded text-xs font-semibold bg-rose-100 text-rose-800">Failed</span>';
                        }

                        const tr = $('<tr>')
                            .append($('<td class="py-3 px-4 font-mono text-xs text-purple-700 font-semibold">').text(pod.namespace))
                            .append($('<td class="py-3 px-4 font-medium text-slate-800">').text(pod.name))
                            .append($('<td class="py-3 px-4">').html(statusBadge))
                            .append($('<td class="py-3 px-4 font-mono text-xs text-slate-500">').text(pod.pod_ip || '-'))
                            .append($('<td class="py-3 px-4 text-xs text-slate-400">').text(pod.start_time || '-'));
                        tbody.append(tr);
                    });
                } else {
                    tbody.append('<tr><td colspan="5" class="py-4 text-center text-slate-400">Nenhum pod encontrado ou erro na listagem.</td></tr>');
                }
            }).fail(function() {
                $('#loadingPods').addClass('hidden');
                alert('Erro ao carregar lista de pods.');
            });
        }

        $(document).ready(function() {
            carregarPodsCluster();
        });
    </script>
</body>
</html>
