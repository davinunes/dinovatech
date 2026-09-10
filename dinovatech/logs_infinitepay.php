<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}
require_once __DIR__ . "/config.php";
require_once __DIR__ . "/helpers/AppHelper.php";

$isInfiniteActive = AppHelper::isInfinitePayActive();
$logFile = __DIR__ . '/webhook_infinitepay.log';

// Ação de Limpeza de Log
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'limpar_log') {
    if (file_exists($logFile)) {
        file_put_contents($logFile, "");
    }
    header("Location: logs_infinitepay.php?msg=cleared");
    exit();
}

$logContent = file_exists($logFile) ? file_get_contents($logFile) : '';
$logEntries = [];

if (!empty(trim($logContent))) {
    $rawBlocks = preg_split('/\n\s*\n/', trim($logContent));
    foreach ($rawBlocks as $block) {
        $block = trim($block);
        if (empty($block)) continue;

        $header = '';
        $payload = '';

        if (preg_match('/^(\[[^\]]+\]\s*\[[^\]]+\]\s*[^\n]+)\n?(.*)$/s', $block, $matches)) {
            $header = trim($matches[1]);
            $payload = trim($matches[2]);
        } else {
            $payload = $block;
        }

        $prettyJson = '';
        $decoded = json_decode($payload, true);
        if ($decoded !== null) {
            $prettyJson = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } else {
            $prettyJson = $payload;
        }

        $logEntries[] = [
            'raw_header' => $header,
            'pretty_payload' => $prettyJson,
            'decoded' => $decoded
        ];
    }
}

// Logs mais recentes primeiro
$logEntries = array_reverse($logEntries);
$msgCleared = ($_GET['msg'] ?? '') === 'cleared';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <?php include 'components/layout_head.php'; ?>
    <title>Logs InfinitePay — Dinovatech Admin</title>
</head>
<body class="bg-gray-50 flex">

<?php include 'components/sidebar.php'; ?>

<div class="flex-1 flex flex-col lg:ml-64 min-h-screen transition-all duration-300">
    <main class="flex-1 p-6">
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <span class="material-icons text-emerald-500 text-3xl">receipt_long</span>
                    <h1 class="text-2xl font-bold text-gray-800">Logs de Webhook — InfinitePay</h1>
                </div>
                <p class="text-sm text-gray-500 mt-1">
                    Visualização detalhada e formatada das notificações recebidas da InfinitePay
                </p>
            </div>

            <div class="flex items-center gap-3">
                <button onclick="window.location.reload()" class="px-4 py-2 bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 rounded-lg text-sm font-medium transition-colors shadow-sm flex items-center gap-2">
                    <span class="material-icons text-base">refresh</span>
                    Atualizar
                </button>
                <?php if (!empty($logEntries)): ?>
                    <form method="POST" onsubmit="return confirm('Tem certeza que deseja limpar todo o histórico de logs da InfinitePay?');" class="inline">
                        <input type="hidden" name="action" value="limpar_log">
                        <button type="submit" class="px-4 py-2 bg-red-50 text-red-600 hover:bg-red-100 border border-red-200 rounded-lg text-sm font-medium transition-colors shadow-sm flex items-center gap-2">
                            <span class="material-icons text-base">delete_outline</span>
                            Limpar Log
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($msgCleared): ?>
            <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-800 text-sm flex items-center gap-3">
                <span class="material-icons text-emerald-600">check_circle</span>
                <span>O arquivo de logs da InfinitePay foi limpo com sucesso.</span>
            </div>
        <?php endif; ?>

        <?php if (!$isInfiniteActive): ?>
            <div class="mb-6 p-4 bg-amber-50 border border-amber-200 rounded-xl text-amber-800 text-sm flex items-center gap-3">
                <span class="material-icons text-amber-600">warning</span>
                <div>
                    <strong>Atenção:</strong> A integração com a InfinitePay está inativa ou o Handle não está configurado. 
                    <a href="config_fiscal.php" class="underline font-semibold hover:text-amber-900 ml-1">Acessar Configurações</a>
                </div>
            </div>
        <?php endif; ?>

        <!-- Stats Bar -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-6 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="w-3 h-3 rounded-full <?= $isInfiniteActive ? 'bg-emerald-500 animate-pulse' : 'bg-gray-400' ?>"></span>
                <span class="text-sm font-medium text-gray-700">
                    Status da Integração: <strong class="<?= $isInfiniteActive ? 'text-emerald-600' : 'text-gray-500' ?>"><?= $isInfiniteActive ? 'Ativa' : 'Inativa' ?></strong>
                </span>
            </div>
            <div class="text-sm text-gray-500">
                Total de Notificações Registradas: <span class="font-bold text-gray-800 bg-gray-100 px-2.5 py-0.5 rounded-full text-xs"><?= count($logEntries) ?></span>
            </div>
        </div>

        <!-- Log List -->
        <?php if (empty($logEntries)): ?>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
                <span class="material-icons text-gray-300 text-5xl mb-3">folder_open</span>
                <h3 class="text-lg font-semibold text-gray-700">Nenhum Log Registrado</h3>
                <p class="text-gray-500 text-sm mt-1">Os webhooks recebidos da InfinitePay aparecerão nesta tela formatados e identados.</p>
            </div>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($logEntries as $idx => $entry): ?>
                    <?php 
                        $decoded = $entry['decoded'];
                        $orderNsu = $decoded['order_nsu'] ?? null;
                        $captureMethod = $decoded['capture_method'] ?? null;
                        $paidAmount = isset($decoded['paid_amount']) ? number_format($decoded['paid_amount'] / 100, 2, ',', '.') : null;
                        $amount = isset($decoded['amount']) ? number_format($decoded['amount'] / 100, 2, ',', '.') : null;
                    ?>
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden transition-all hover:border-gray-300">
                        <!-- Card Header -->
                        <div class="bg-slate-900 text-slate-100 px-4 py-3 flex flex-wrap items-center justify-between gap-3 text-xs font-mono border-b border-slate-800">
                            <div class="flex items-center gap-3">
                                <span class="material-icons text-emerald-400 text-base">terminal</span>
                                <span class="text-slate-300"><?= htmlspecialchars($entry['raw_header']) ?></span>
                            </div>

                            <div class="flex items-center gap-2 font-sans">
                                <?php if ($orderNsu): ?>
                                    <span class="bg-cyan-900/80 text-cyan-200 px-2.5 py-0.5 rounded text-xs font-semibold border border-cyan-700">
                                        <?= htmlspecialchars($orderNsu) ?>
                                    </span>
                                <?php endif; ?>
                                <?php if ($captureMethod): ?>
                                    <span class="bg-slate-800 text-slate-300 px-2 py-0.5 rounded text-xs uppercase font-medium">
                                        <?= htmlspecialchars($captureMethod) ?>
                                    </span>
                                <?php endif; ?>
                                <?php if ($amount): ?>
                                    <span class="bg-emerald-900/80 text-emerald-200 px-2 py-0.5 rounded text-xs font-semibold border border-emerald-700">
                                        R$ <?= $amount ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Card Body (JSON Formatted) -->
                        <div class="p-4 bg-slate-950 relative group">
                            <button onclick="copiarJson('json_<?= $idx ?>', this)" class="absolute top-3 right-3 bg-slate-800 hover:bg-slate-700 text-slate-300 px-2.5 py-1 rounded text-xs transition-colors flex items-center gap-1 opacity-80 group-hover:opacity-100">
                                <span class="material-icons text-xs">content_copy</span>
                                <span>Copiar</span>
                            </button>
                            <pre id="json_<?= $idx ?>" class="text-emerald-400 font-mono text-xs overflow-x-auto p-2 leading-relaxed whitespace-pre-wrap select-all"><?= htmlspecialchars($entry['pretty_payload']) ?></pre>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
</div>

<script>
function copiarJson(elementId, btn) {
    const text = document.getElementById(elementId).innerText;
    navigator.clipboard.writeText(text).then(() => {
        const span = btn.querySelector('span:last-child');
        const icon = btn.querySelector('.material-icons');
        const originalText = span.innerText;
        const originalIcon = icon.innerText;
        
        span.innerText = 'Copiado!';
        icon.innerText = 'check';
        btn.classList.remove('bg-slate-800', 'hover:bg-slate-700');
        btn.classList.add('bg-emerald-600', 'text-white');
        
        setTimeout(() => {
            span.innerText = originalText;
            icon.innerText = originalIcon;
            btn.classList.remove('bg-emerald-600', 'text-white');
            btn.classList.add('bg-slate-800', 'hover:bg-slate-700');
        }, 2000);
    });
}
</script>

</body>
</html>
