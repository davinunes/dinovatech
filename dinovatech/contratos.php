<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}
include "../database.php";
require_once __DIR__ . "/config.php";
require_once __DIR__ . "/helpers/AppHelper.php";

$contratos = [];
$link = DBConnect();
$search = "";
if ($link) {
    $search = isset($_GET['search']) ? mysqli_real_escape_string($link, $_GET['search']) : '';
    $where_clause = "";
    if ($search) {
        $where_clause = "WHERE C.nome LIKE '%$search%' OR S.nome_servico LIKE '%$search%'";
    }

    $query = "SELECT R.*, C.nome AS nome_cliente, S.nome_servico 
              FROM Recorrencias R 
              JOIN Clientes C ON R.id_cliente = C.id_cliente 
              JOIN Servicos S ON R.id_servico = S.id_servico 
              $where_clause
              ORDER BY R.unica_fatura_gerada_mes_ano DESC, C.nome ASC";
    // Correction: Field name is ultima_fatura_gerada_mes_ano, not unica...
    $query = "SELECT R.*, C.nome AS nome_cliente, S.nome_servico 
              FROM Recorrencias R 
              JOIN Clientes C ON R.id_cliente = C.id_cliente 
              JOIN Servicos S ON R.id_servico = S.id_servico 
              $where_clause
              ORDER BY C.nome ASC";

    $result = DBExecute($link, $query);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $contratos[] = $row;
        }
    }
    DBClose($link);
}
// Separate Active/Expired

$ativos = [];
$expirados = [];
$hoje = date('Y-m-d');

foreach ($contratos as $c) {
    if (!empty($c['data_fim_cobranca']) && $c['data_fim_cobranca'] < $hoje) {
        $expirados[] = $c;
    } else {
        $ativos[] = $c;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <title>Recorrência (Contratos) - Dinovatech</title>
    <?php include 'components/layout_head.php'; ?>
</head>

<body class="bg-gray-50 flex">

    <?php include 'components/sidebar.php'; ?>

    <div class="flex-1 flex flex-col lg:ml-64 min-h-screen transition-all duration-300">
        <main class="flex-1 p-6 mt-16 lg:mt-0">

            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                <div>
                    <h2 class="text-3xl font-bold text-gray-800">Recorrência (Contratos)</h2>
                    <p class="text-gray-500">Gerencie assinaturas e cobranças frequentes.</p>
                </div>
                <div class="flex items-center gap-3">
                    <button type="button" onclick="executarCronRecorrenciasManual()" id="btnGerarFaturasRec"
                        class="bg-emerald-600 hover:bg-emerald-700 text-white font-medium py-2 px-4 rounded-lg flex items-center transition-colors shadow-sm"
                        title="Processa e gera faturas para contratos ativos da competência atual">
                        <span class="material-icons mr-1.5 text-sm">autorenew</span>
                        Gerar Faturas do Mês
                    </button>
                    <a href="contrato_form.php"
                        class="bg-cyan-600 hover:bg-cyan-700 text-white font-medium py-2 px-4 rounded-lg flex items-center transition-colors shadow-sm">
                        <span class="material-icons mr-2">add</span>
                        Novo Contrato
                    </a>
                </div>
            </div>

            <!-- Search -->
            <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 mb-6">
                <form method="GET" class="flex gap-2">
                    <div class="relative flex-1">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                            <span class="material-icons">search</span>
                        </span>
                        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                            placeholder="Buscar por Cliente ou Serviço..."
                            class="w-full py-2 pl-10 pr-4 text-gray-700 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:border-cyan-500 focus:bg-white focus:ring-0">
                    </div>
                    <button type="submit"
                        class="bg-slate-800 hover:bg-slate-900 text-white px-4 py-2 rounded-lg font-medium transition-colors">Buscar</button>
                </form>
            </div>

            <!-- Tabs -->
            <div class="flex border-b border-gray-200 mb-6">
                <button onclick="switchTab('ativos')" id="btn-ativos"
                    class="px-6 py-2 text-cyan-600 border-b-2 border-cyan-600 font-bold transition-colors">
                    Contratos Ativos
                </button>
                <button onclick="switchTab('expirados')" id="btn-expirados"
                    class="px-6 py-2 text-gray-500 hover:text-gray-700 font-medium transition-colors">
                    Expirados / Finalizados
                </button>
            </div>

            <!-- Content: ATIVOS -->
            <div id="tab-ativos">
                <!-- Desktop List -->
                <div class="hidden md:block bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-6">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 text-gray-600 text-sm uppercase tracking-wider">
                                    <th class="p-4 font-medium">Cliente</th>
                                    <th class="p-4 font-medium">Serviço</th>
                                    <th class="p-4 font-medium">Valor</th>
                                    <th class="p-4 font-medium">Período</th>
                                    <th class="p-4 font-medium">Vencimento</th>
                                    <th class="p-4 font-medium">Início</th>
                                    <th class="p-4 font-medium text-right">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="text-gray-700 text-sm">
                                <?php if (!empty($ativos)): ?>
                                    <?php foreach ($ativos as $contrato): ?>
                                        <?php
                                        $diaVenc = !empty($contrato['dia_vencimento']) ? (int) $contrato['dia_vencimento'] : (int) date('d', strtotime($contrato['data_inicio_cobranca']));
                                        ?>
                                        <tr class="hover:bg-gray-50 transition border-b border-gray-100 last:border-b-0">
                                            <td class="p-4 font-medium text-gray-900">
                                                <?= htmlspecialchars($contrato['nome_cliente']) ?>
                                            </td>
                                            <td class="p-4"><?= htmlspecialchars($contrato['nome_servico']) ?></td>
                                            <td class="p-4">R$
                                                <?= number_format($contrato['valor_sugerido_recorrencia'], 2, ',', '.') ?>
                                            </td>
                                            <td class="p-4 capitalize"><?= htmlspecialchars($contrato['tipo_periodo']) ?></td>
                                            <td class="p-4 font-semibold text-cyan-700">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-cyan-50 border border-cyan-200">
                                                    Dia <?= $diaVenc ?>
                                                </span>
                                            </td>
                                            <td class="p-4"><?= date('d/m/Y', strtotime($contrato['data_inicio_cobranca'])) ?>
                                            </td>
                                            <td class="p-4 text-right">
                                                <a href="contrato_form.php?id=<?= $contrato['id_recorrencia'] ?>"
                                                    class="text-cyan-600 hover:text-cyan-800 font-medium text-sm">Editar</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="p-8 text-center text-gray-500">Nenhum contrato ativo
                                            encontrado.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <!-- Mobile List -->
                <div class="md:hidden space-y-4">
                    <?php if (!empty($ativos)): ?>
                        <?php foreach ($ativos as $contrato): ?>
                            <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
                                <div class="flex justify-between items-start mb-3">
                                    <div>
                                        <h3 class="font-bold text-gray-900"><?= htmlspecialchars($contrato['nome_cliente']) ?>
                                        </h3>
                                        <p class="text-sm text-gray-600"><?= htmlspecialchars($contrato['nome_servico']) ?></p>
                                    </div>
                                    <div class="text-right">
                                        <span class="block font-bold text-gray-900">R$
                                            <?= number_format($contrato['valor_sugerido_recorrencia'], 2, ',', '.') ?></span>
                                        <span
                                            class="text-xs text-gray-500 uppercase"><?= htmlspecialchars($contrato['tipo_periodo']) ?></span>
                                    </div>
                                </div>
                                <div class="text-sm text-gray-600 mb-4 flex justify-between">
                                    <span>Início: <?= date('d/m/Y', strtotime($contrato['data_inicio_cobranca'])) ?></span>
                                </div>
                                <div class="pt-3 border-t border-gray-200/50 flex justify-end">
                                    <a href="contrato_form.php?id=<?= $contrato['id_recorrencia'] ?>"
                                        class="w-full text-center bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 py-2 rounded-lg text-sm font-medium transition-colors">Editar
                                        Contrato</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="bg-white p-8 rounded-xl shadow-sm border border-gray-100 text-center text-gray-500">
                            Nenhum contrato ativo.</div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Content: EXPIRADOS -->
            <div id="tab-expirados" class="hidden">
                <!-- Desktop List -->
                <div
                    class="hidden md:block bg-gray-50 rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-6">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-100 text-gray-600 text-sm uppercase tracking-wider">
                                    <th class="p-4 font-medium">Cliente</th>
                                    <th class="p-4 font-medium">Serviço</th>
                                    <th class="p-4 font-medium">Valor</th>
                                    <th class="p-4 font-medium">Início</th>
                                    <th class="p-4 font-medium">Fim</th>
                                    <th class="p-4 font-medium text-right">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="text-gray-600 text-sm">
                                <?php if (!empty($expirados)): ?>
                                    <?php foreach ($expirados as $contrato): ?>
                                        <tr
                                            class="transition border-b border-gray-200 last:border-b-0 opacity-75 hover:opacity-100 bg-gray-50">
                                            <td class="p-4 font-medium">
                                                <?= htmlspecialchars($contrato['nome_cliente']) ?>
                                                <span
                                                    class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">Vencido</span>
                                            </td>
                                            <td class="p-4"><?= htmlspecialchars($contrato['nome_servico']) ?></td>
                                            <td class="p-4">R$
                                                <?= number_format($contrato['valor_sugerido_recorrencia'], 2, ',', '.') ?>
                                            </td>
                                            <td class="p-4"><?= date('d/m/Y', strtotime($contrato['data_inicio_cobranca'])) ?>
                                            </td>
                                            <td class="p-4 font-bold text-red-600">
                                                <?= date('d/m/Y', strtotime($contrato['data_fim'])) ?>
                                            </td>
                                            <td class="p-4 text-right">
                                                <a href="contrato_form.php?id=<?= $contrato['id_recorrencia'] ?>"
                                                    class="text-gray-500 hover:text-gray-700 font-medium text-sm">Ver</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="p-8 text-center text-gray-500">Nenhum contrato expirado
                                            encontrado.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <!-- Mobile List -->
                <div class="md:hidden space-y-4">
                    <?php if (!empty($expirados)): ?>
                        <?php foreach ($expirados as $contrato): ?>
                            <div class="bg-gray-50 p-4 rounded-xl shadow-sm border border-gray-200 opacity-80">
                                <div class="flex justify-between items-start mb-3">
                                    <div>
                                        <h3 class="font-bold text-gray-700"><?= htmlspecialchars($contrato['nome_cliente']) ?>
                                        </h3>
                                        <p class="text-sm text-gray-500"><?= htmlspecialchars($contrato['nome_servico']) ?></p>
                                        <span
                                            class="mt-1 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">Vencido</span>
                                    </div>
                                    <div class="text-right">
                                        <span class="block font-bold text-gray-700">R$
                                            <?= number_format($contrato['valor_sugerido_recorrencia'], 2, ',', '.') ?></span>
                                    </div>
                                </div>
                                <div
                                    class="text-sm text-gray-600 mb-4 flex justify-between bg-white p-2 rounded border border-gray-100">
                                    <span>Fim: <strong
                                            class="text-red-600"><?= date('d/m/Y', strtotime($contrato['data_fim'])) ?></strong></span>
                                </div>
                                <div class="pt-3 border-t border-gray-200/50 flex justify-end">
                                    <a href="contrato_form.php?id=<?= $contrato['id_recorrencia'] ?>"
                                        class="w-full text-center bg-white border border-gray-200 hover:bg-gray-50 text-gray-600 py-2 rounded-lg text-sm font-medium transition-colors">Ver
                                        Detalhes</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="bg-gray-50 p-8 rounded-xl shadow-sm border border-gray-200 text-center text-gray-500">
                            Nenhum contrato expirado.</div>
                    <?php endif; ?>
                </div>
            </div>

            <script>
                function switchTab(tab) {
                    const btnAtivos = document.getElementById('btn-ativos');
                    const btnExpirados = document.getElementById('btn-expirados');
                    const tabAtivos = document.getElementById('tab-ativos');
                    const tabExpirados = document.getElementById('tab-expirados');

                    if (tab === 'ativos') {
                        tabAtivos.classList.remove('hidden');
                        tabExpirados.classList.add('hidden');

                        btnAtivos.className = 'px-6 py-2 text-cyan-600 border-b-2 border-cyan-600 font-bold transition-colors';
                        btnExpirados.className = 'px-6 py-2 text-gray-500 hover:text-gray-700 font-medium transition-colors';
                    } else {
                        tabAtivos.classList.add('hidden');
                        tabExpirados.classList.remove('hidden');

                        btnExpirados.className = 'px-6 py-2 text-cyan-600 border-b-2 border-cyan-600 font-bold transition-colors';
                        btnAtivos.className = 'px-6 py-2 text-gray-500 hover:text-gray-700 font-medium transition-colors';
                    }
                }

                function executarCronRecorrenciasManual() {
                    const mesAtual = '<?= date('m/Y') ?>';
                    if (!confirm(`Deseja processar e gerar as faturas de contratos para o mês vigente (${mesAtual}) agora?`)) {
                        return;
                    }

                    const btn = $('#btnGerarFaturasRec');
                    const originalHtml = btn.html();
                    btn.prop('disabled', true).html('<span class="material-icons text-sm animate-spin mr-1.5">refresh</span> Gerando...');

                    $.post('app.php', { action: 'executar_cron_recorrencias_manual', competencia: mesAtual }, function (res) {
                        btn.prop('disabled', false).html(originalHtml);
                        if (res.success) {
                            let msg = res.message || 'Processamento concluído com sucesso!';
                            if (res.faturas && res.faturas.length > 0) {
                                msg += '\n\nFaturas Geradas:\n' + res.faturas.map(f => `• ${f.cliente_nome} (${f.servico_nome}) - R$ ${Number(f.valor).toLocaleString('pt-BR', { minimumFractionDigits: 2 })} - Venc: ${f.vencimento}`).join('\n');
                            }
                            alert(msg);
                            window.location.reload();
                        } else {
                            alert('Erro ao processar faturas: ' + (res.message || 'Erro desconhecido.'));
                        }
                    }, 'json').fail(function (xhr) {
                        btn.prop('disabled', false).html(originalHtml);
                        alert('Falha na comunicação com o servidor: ' + (xhr.responseText || 'Erro HTTP'));
                    });
                }
            </script>

        </main>
    </div>

    <?php include 'components/layout_scripts.php'; ?>
</body>

</html>