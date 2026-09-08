<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}
include "../database.php";
require_once __DIR__ . "/config.php";

$link = DBConnect();

$filtroStatus = $_GET['status'] ?? 'TODOS';
$whereStatus = '';
if ($filtroStatus !== 'TODOS') {
    $filtroSafe = mysqli_real_escape_string($link, $filtroStatus);
    $whereStatus = "WHERE P.status = '$filtroSafe'";
}

$qLista = "SELECT 
                P.id_pix_recorrencia, P.id_rec, P.id_recorrencia, P.id_cliente,
                P.id_fatura_inicial, P.status, P.valor_recorrente, P.data_inicial,
                P.data_final, P.data_criacao, P.data_aceite, P.data_ultima_consulta,
                P.txid_inicial,
                C.nome AS nome_cliente, C.cpf_cnpj,
                S.nome_servico
            FROM PixRecorrencias P
            LEFT JOIN Clientes C ON P.id_cliente = C.id_cliente
            LEFT JOIN Recorrencias R ON P.id_recorrencia = R.id_recorrencia
            LEFT JOIN Servicos S ON R.id_servico = S.id_servico
            $whereStatus
            ORDER BY P.data_criacao DESC";

$resLista = DBExecute($link, $qLista);
$registros = [];
if ($resLista) {
    while ($row = mysqli_fetch_assoc($resLista)) {
        $registros[] = $row;
    }
}

$statusBadge = [
    'CRIADA'    => ['bg' => 'bg-slate-100 text-slate-700 border border-slate-300',       'label' => 'Criada'],
    'PENDENTE'  => ['bg' => 'bg-amber-100 text-amber-800 border border-amber-300',       'label' => 'Pendente Aceite'],
    'APROVADA'  => ['bg' => 'bg-emerald-100 text-emerald-800 border border-emerald-300', 'label' => 'Aprovada / Ativa'],
    'CANCELADA' => ['bg' => 'bg-red-100 text-red-800 border border-red-300',             'label' => 'Cancelada'],
];

DBClose($link);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <?php include 'components/layout_head.php'; ?>
    <title>Pix Automático — Dinovatech Admin</title>
</head>
<body class="bg-gray-50">

<?php include 'components/sidebar.php'; ?>

<div class="main-content p-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                <span class="material-icons text-purple-600">bolt</span>
                Pix Automático (Jornada 4)
            </h1>
            <p class="text-sm text-gray-500 mt-0.5">Contratos de débito automático via Pix cadastrados no sistema.</p>
        </div>
        <form method="GET" class="flex gap-2 items-center">
            <label class="text-sm font-medium text-gray-600">Status:</label>
            <select name="status" onchange="this.form.submit()"
                class="text-sm border border-gray-300 rounded-lg px-3 py-1.5 bg-white shadow-sm focus:ring-2 focus:ring-purple-400 focus:border-purple-400">
                <option value="TODOS"    <?= $filtroStatus === 'TODOS'    ? 'selected' : '' ?>>Todos</option>
                <option value="CRIADA"   <?= $filtroStatus === 'CRIADA'   ? 'selected' : '' ?>>Criada</option>
                <option value="PENDENTE" <?= $filtroStatus === 'PENDENTE' ? 'selected' : '' ?>>Pendente Aceite</option>
                <option value="APROVADA" <?= $filtroStatus === 'APROVADA' ? 'selected' : '' ?>>Aprovada / Ativa</option>
                <option value="CANCELADA"<?= $filtroStatus === 'CANCELADA'? 'selected' : '' ?>>Cancelada</option>
            </select>
        </form>
    </div>

    <!-- Cards de Resumo -->
    <?php
    $totals = ['CRIADA' => 0, 'PENDENTE' => 0, 'APROVADA' => 0, 'CANCELADA' => 0];
    // Conta todos (ignora filtro atual para o resumo geral)
    // Reutiliza $registros quando TODOS, caso contrário faz query separada
    if ($filtroStatus === 'TODOS') {
        foreach ($registros as $r) {
            $st = $r['status'] ?? 'CRIADA';
            if (isset($totals[$st])) $totals[$st]++;
        }
    }
    $cards = [
        'APROVADA'  => ['icon' => 'check_circle', 'color' => 'text-emerald-600', 'bg' => 'bg-emerald-50 border-emerald-200', 'label' => 'Ativas'],
        'PENDENTE'  => ['icon' => 'pending',       'color' => 'text-amber-600',   'bg' => 'bg-amber-50 border-amber-200',     'label' => 'Pend. Aceite'],
        'CRIADA'    => ['icon' => 'add_circle',    'color' => 'text-slate-600',   'bg' => 'bg-slate-50 border-slate-200',     'label' => 'Criadas'],
        'CANCELADA' => ['icon' => 'cancel',        'color' => 'text-red-600',     'bg' => 'bg-red-50 border-red-200',         'label' => 'Canceladas'],
    ];
    ?>
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
        <?php foreach ($cards as $st => $c): ?>
        <a href="?status=<?= $st ?>" class="<?= $c['bg'] ?> rounded-xl p-4 flex items-center gap-3 shadow-sm border hover:shadow-md transition <?= $filtroStatus === $st ? 'ring-2 ring-offset-1 ring-purple-400' : '' ?>">
            <span class="material-icons <?= $c['color'] ?> text-2xl"><?= $c['icon'] ?></span>
            <div>
                <p class="text-xl font-bold text-gray-800"><?= $totals[$st] ?></p>
                <p class="text-xs text-gray-500"><?= $c['label'] ?></p>
            </div>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- Tabela -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <?php if (empty($registros)): ?>
        <div class="text-center py-16 text-gray-400">
            <span class="material-icons text-5xl mb-3 block">bolt</span>
            <p class="font-medium">Nenhum contrato de Pix Automático encontrado.</p>
            <p class="text-sm mt-1">Eles são criados ao gerar uma proposta Jornada 4 em uma fatura.</p>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600 text-xs uppercase tracking-wide">Status</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600 text-xs uppercase tracking-wide">Cliente</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600 text-xs uppercase tracking-wide">Contrato / Serviço</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600 text-xs uppercase tracking-wide">ID Rec (Inter)</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-600 text-xs uppercase tracking-wide">Valor/Mês</th>
                        <th class="px-4 py-3 text-center font-semibold text-gray-600 text-xs uppercase tracking-wide">Início</th>
                        <th class="px-4 py-3 text-center font-semibold text-gray-600 text-xs uppercase tracking-wide">Aceite</th>
                        <th class="px-4 py-3 text-center font-semibold text-gray-600 text-xs uppercase tracking-wide">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach ($registros as $reg):
                        $st = $reg['status'] ?? 'CRIADA';
                        $badge = $statusBadge[$st] ?? ['bg' => 'bg-gray-100 text-gray-600 border border-gray-300', 'label' => $st];
                        $idRecEsc = htmlspecialchars($reg['id_rec']);
                    ?>
                    <tr class="hover:bg-purple-50/30 transition" id="row-<?= $reg['id_pix_recorrencia'] ?>">
                        <td class="px-4 py-3">
                            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold <?= $badge['bg'] ?>">
                                <?= $badge['label'] ?>
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <a href="cliente_form.php?id=<?= (int)$reg['id_cliente'] ?>" class="font-medium text-gray-800 hover:text-blue-600 transition">
                                <?= htmlspecialchars($reg['nome_cliente'] ?? '—') ?>
                            </a>
                            <p class="text-xs text-gray-400 mt-0.5"><?= htmlspecialchars($reg['cpf_cnpj'] ?? '') ?></p>
                        </td>
                        <td class="px-4 py-3">
                            <a href="contrato_form.php?id=<?= (int)$reg['id_recorrencia'] ?>" class="text-blue-600 hover:underline text-xs font-mono font-semibold">
                                #<?= (int)$reg['id_recorrencia'] ?>
                            </a>
                            <p class="text-xs text-gray-400 mt-0.5"><?= htmlspecialchars($reg['nome_servico'] ?? '—') ?></p>
                        </td>
                        <td class="px-4 py-3">
                            <span class="text-[11px] font-mono text-purple-700 select-all break-all"><?= $idRecEsc ?></span>
                        </td>
                        <td class="px-4 py-3 text-right font-bold text-gray-900">
                            R$ <?= number_format((float)$reg['valor_recorrente'], 2, ',', '.') ?>
                        </td>
                        <td class="px-4 py-3 text-center text-xs text-gray-600">
                            <?= !empty($reg['data_inicial']) ? date('d/m/Y', strtotime($reg['data_inicial'])) : '—' ?>
                        </td>
                        <td class="px-4 py-3 text-center text-xs">
                            <?php if (!empty($reg['data_aceite'])): ?>
                                <span class="text-emerald-700 font-semibold"><?= date('d/m/Y H:i', strtotime($reg['data_aceite'])) ?></span>
                            <?php else: ?>
                                <span class="text-gray-400">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-center gap-1.5">
                                <button type="button"
                                    onclick="verificarAceite('<?= $idRecEsc ?>')"
                                    title="Consultar status no Banco Inter"
                                    id="btn-sync-<?= $reg['id_pix_recorrencia'] ?>"
                                    class="p-1.5 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-600 transition border border-indigo-200">
                                    <span class="material-icons text-sm">sync</span>
                                </button>
                                <?php if (!empty($reg['id_fatura_inicial'])): ?>
                                <a href="fatura_view.php?id=<?= (int)$reg['id_fatura_inicial'] ?>"
                                    title="Ver fatura inicial"
                                    class="p-1.5 rounded-lg bg-gray-50 hover:bg-gray-100 text-gray-600 transition border border-gray-200">
                                    <span class="material-icons text-sm">receipt</span>
                                </a>
                                <?php endif; ?>
                                <?php if ($st !== 'CANCELADA'): ?>
                                <button type="button"
                                    onclick="cancelarRec('<?= $idRecEsc ?>')"
                                    title="Cancelar autorização de débito"
                                    class="p-1.5 rounded-lg bg-red-50 hover:bg-red-100 text-red-600 transition border border-red-200">
                                    <span class="material-icons text-sm">block</span>
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-gray-100 text-xs text-gray-400 text-right">
            <?= count($registros) ?> registro(s)
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Toast -->
<div id="toast-pix" class="fixed bottom-5 right-5 z-50 hidden">
    <div class="bg-gray-900 text-white text-sm px-4 py-3 rounded-xl shadow-xl flex items-center gap-2 max-w-sm">
        <span class="material-icons text-base" id="toast-icon">info</span>
        <span id="toast-msg"></span>
    </div>
</div>

<?php include 'components/layout_scripts.php'; ?>
<script>
const ENDPOINT = '../inter/endpoint.php';

function showToast(msg, tipo = 'info') {
    const icons = { success: 'check_circle', error: 'error', info: 'info' };
    const colors = { success: 'text-emerald-400', error: 'text-red-400', info: 'text-blue-400' };
    $('#toast-icon').text(icons[tipo] || 'info').removeClass().addClass('material-icons text-base ' + (colors[tipo] || ''));
    $('#toast-msg').text(msg);
    $('#toast-pix').removeClass('hidden').fadeIn(200);
    setTimeout(() => $('#toast-pix').fadeOut(400, () => $('#toast-pix').addClass('hidden')), 4000);
}

function verificarAceite(idRec) {
    if (!idRec) return;
    const btn = event.currentTarget;
    const icon = btn.querySelector('.material-icons');
    btn.disabled = true;
    icon.classList.add('animate-spin');

    $.post(ENDPOINT + '?action=consultar_status_recorrencia', { idRec: idRec }, function(res) {
        btn.disabled = false;
        icon.classList.remove('animate-spin');
        if (res.success) {
            showToast('Status atualizado: ' + (res.status || 'OK'), 'success');
            setTimeout(() => window.location.reload(), 1200);
        } else {
            showToast('Erro: ' + (res.message || 'Falha'), 'error');
        }
    }, 'json').fail(function() {
        btn.disabled = false;
        icon.classList.remove('animate-spin');
        showToast('Erro de conexão com o servidor.', 'error');
    });
}

function cancelarRec(idRec) {
    if (!idRec) return;
    if (!confirm('Cancelar o Pix Automático ' + idRec + '?\n\nEsta ação cancela os débitos automáticos futuros no Banco Inter.')) return;

    $.post(ENDPOINT + '?action=cancelar_pix_recorrencia_contrato', { idRec: idRec }, function(res) {
        if (res.success) {
            showToast('Recorrência cancelada com sucesso.', 'success');
            setTimeout(() => window.location.reload(), 1200);
        } else {
            showToast('Erro: ' + (res.message || 'Falha'), 'error');
        }
    }, 'json').fail(function() {
        showToast('Erro de conexão.', 'error');
    });
}
</script>
</body>
</html>
