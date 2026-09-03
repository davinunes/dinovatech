<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}
include "../database.php";
require_once __DIR__ . "/config.php";
require_once __DIR__ . "/helpers/AppHelper.php";

$servicos_ativos = [];
$servicos_desativados = [];
$link = DBConnect();
$search = "";
if ($link) {
    $search = isset($_GET['search']) ? mysqli_real_escape_string($link, $_GET['search']) : '';
    $where_clause = "";
    if ($search) {
        $where_clause = "WHERE (nome_servico LIKE '%$search%' OR descricao_fiscal LIKE '%$search%')";
    }

    $query = "SELECT *, COALESCE(ativo, 1) AS status_ativo FROM Servicos $where_clause ORDER BY nome_servico ASC";
    $result = DBExecute($link, $query);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            if ($row['status_ativo'] == 1) {
                $servicos_ativos[] = $row;
            } else {
                $servicos_desativados[] = $row;
            }
        }
    }
    DBClose($link);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <title>Serviços - <?= htmlspecialchars(AppHelper::getCompanyName()) ?></title>
    <?php include 'components/layout_head.php'; ?>
</head>

<body class="bg-gray-50 flex">

    <?php include 'components/sidebar.php'; ?>

    <div class="flex-1 flex flex-col lg:ml-64 min-h-screen transition-all duration-300">
        <main class="flex-1 p-6 mt-16 lg:mt-0">

            <!-- Cabeçalho -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                <div>
                    <h2 class="text-3xl font-bold text-gray-800">Serviços</h2>
                    <p class="text-gray-500">Gerencie os serviços oferecidos e controle a ativação no catálogo.</p>
                </div>
                <a href="servico_form.php"
                    class="bg-cyan-600 hover:bg-cyan-700 text-white font-medium py-2 px-4 rounded-lg flex items-center transition-colors shadow-sm">
                    <span class="material-icons mr-2">add</span>
                    Novo Serviço
                </a>
            </div>

            <!-- Search Bar -->
            <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 mb-6">
                <form method="GET" class="flex gap-2">
                    <div class="relative flex-1">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                            <span class="material-icons">search</span>
                        </span>
                        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                            placeholder="Buscar Serviço..."
                            class="w-full py-2 pl-10 pr-4 text-gray-700 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:border-cyan-500 focus:bg-white focus:ring-0">
                    </div>
                    <button type="submit"
                        class="bg-slate-800 hover:bg-slate-900 text-white px-4 py-2 rounded-lg font-medium transition-colors">Buscar</button>
                    <?php if (!empty($search)): ?>
                        <a href="servicos.php" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-2 rounded-lg font-medium flex items-center transition-colors">
                            Limpar
                        </a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Mensagens de Alerta Dinâmicas -->
            <div id="alertServicoMsg" class="hidden mb-6 p-4 rounded-xl text-sm font-medium border flex items-start gap-3 transition-all"></div>

            <!-- Navegação em Abas (Tabs) -->
            <div class="flex border-b border-gray-200 mb-6 space-x-2">
                <button onclick="switchTab('ativos')" id="btn-tab-ativos"
                    class="px-6 py-3 text-cyan-600 border-b-2 border-cyan-600 font-bold transition-all flex items-center gap-2">
                    <span class="material-icons text-lg">check_circle</span>
                    Serviços Ativos
                    <span class="px-2 py-0.5 text-xs rounded-full bg-cyan-100 text-cyan-800 font-semibold" id="badge-count-ativos">
                        <?= count($servicos_ativos) ?>
                    </span>
                </button>
                <button onclick="switchTab('desativados')" id="btn-tab-desativados"
                    class="px-6 py-3 text-gray-500 hover:text-gray-700 font-medium transition-all flex items-center gap-2">
                    <span class="material-icons text-lg">pause_circle</span>
                    Serviços Desativados
                    <span class="px-2 py-0.5 text-xs rounded-full bg-gray-200 text-gray-700 font-semibold" id="badge-count-desativados">
                        <?= count($servicos_desativados) ?>
                    </span>
                </button>
            </div>

            <!-- ABA 1: SERVIÇOS ATIVOS -->
            <div id="tab-content-ativos" class="tab-pane">
                <!-- Desktop Table Ativos -->
                <div class="hidden md:block bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-6">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 text-gray-600 text-sm uppercase tracking-wider">
                                    <th class="p-4 font-medium">Serviço</th>
                                    <?php if (AppHelper::isVetMode()): ?>
                                        <th class="p-4 font-medium">Módulos</th>
                                    <?php endif; ?>
                                    <th class="p-4 font-medium">Duração</th>
                                    <th class="p-4 font-medium">Valor Sugerido</th>
                                    <th class="p-4 font-medium text-right">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="text-gray-700 text-sm divide-y divide-gray-50">
                                <?php if (!empty($servicos_ativos)): ?>
                                    <?php foreach ($servicos_ativos as $servico): ?>
                                        <tr class="hover:bg-gray-50/70 transition" id="row-servico-<?= $servico['id_servico'] ?>">
                                            <td class="p-4 font-medium text-gray-900 flex items-center gap-3">
                                                <div class="w-10 h-10 rounded-xl bg-cyan-50 text-cyan-700 flex items-center justify-center flex-shrink-0 shadow-sm border border-cyan-100">
                                                    <span class="material-icons text-lg"><?= htmlspecialchars($servico['icone_servico'] ?? (AppHelper::isVetMode() ? 'pets' : 'build')) ?></span>
                                                </div>
                                                <div>
                                                    <div class="font-semibold text-gray-900 flex items-center gap-2">
                                                        <?= htmlspecialchars($servico['nome_servico']) ?>
                                                        <?php if (!empty($servico['codigo_tributacao_nacional'])): ?>
                                                            <span class="text-[10px] bg-cyan-50 text-cyan-700 px-1.5 py-0.5 rounded border border-cyan-200 font-mono font-semibold" title="Código de Tributação Nacional">cTribNac: <?= htmlspecialchars($servico['codigo_tributacao_nacional']) ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <?php if (!empty($servico['descricao_fiscal'])): ?>
                                                        <div class="text-xs text-gray-400">NFS-e: <?= htmlspecialchars($servico['descricao_fiscal']) ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <?php if (AppHelper::isVetMode()): ?>
                                                <td class="p-4">
                                                    <div class="flex flex-wrap gap-1">
                                                        <?php if (!empty($servico['disponivel_clinica'])): ?>
                                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200">
                                                                <span class="material-icons text-[12px] mr-1">local_hospital</span> Clínica
                                                            </span>
                                                        <?php endif; ?>
                                                        <?php if (!empty($servico['disponivel_banho'])): ?>
                                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-teal-50 text-teal-700 border border-teal-200">
                                                                <span class="material-icons text-[12px] mr-1">shower</span> Banho & Tosa
                                                            </span>
                                                        <?php endif; ?>
                                                        <?php if (empty($servico['disponivel_clinica']) && empty($servico['disponivel_banho'])): ?>
                                                            <span class="text-xs text-gray-400">Geral</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            <?php endif; ?>
                                            <td class="p-4 text-gray-600">
                                                <span class="inline-flex items-center gap-1">
                                                    <span class="material-icons text-sm text-gray-400">schedule</span>
                                                    <?= (int)($servico['duracao_minutos'] ?? 30) ?> min
                                                </span>
                                            </td>
                                            <td class="p-4 font-semibold text-gray-800">
                                                R$ <?= number_format($servico['valor_sugerido'], 2, ',', '.') ?>
                                            </td>
                                            <td class="p-4 text-right">
                                                <div class="flex items-center justify-end gap-2">
                                                    <a href="servico_form.php?id=<?= $servico['id_servico'] ?>"
                                                        class="p-2 text-gray-500 hover:text-cyan-700 hover:bg-cyan-50 rounded-lg transition" title="Editar Serviço">
                                                        <span class="material-icons text-lg">edit</span>
                                                    </a>
                                                    <button type="button" onclick="alterarStatusServico(<?= $servico['id_servico'] ?>, 0, '<?= htmlspecialchars(addslashes($servico['nome_servico'])) ?>')"
                                                        class="p-2 text-rose-500 hover:text-rose-700 hover:bg-rose-50 rounded-lg transition" title="Desativar Serviço">
                                                        <span class="material-icons text-lg">pause_circle_filled</span>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="<?= AppHelper::isVetMode() ? '5' : '4' ?>" class="p-8 text-center text-gray-500">
                                            Nenhum serviço ativo encontrado.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Mobile Cards Ativos -->
                <div class="md:hidden space-y-4">
                    <?php if (!empty($servicos_ativos)): ?>
                        <?php foreach ($servicos_ativos as $servico): ?>
                            <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100" id="card-servico-<?= $servico['id_servico'] ?>">
                                <div class="flex items-center gap-3 mb-3">
                                    <div class="w-10 h-10 rounded-xl bg-cyan-50 text-cyan-700 flex items-center justify-center flex-shrink-0 border border-cyan-100">
                                        <span class="material-icons text-xl"><?= htmlspecialchars($servico['icone_servico'] ?? (AppHelper::isVetMode() ? 'pets' : 'build')) ?></span>
                                    </div>
                                    <div class="flex-1">
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            <h3 class="font-bold text-gray-900 leading-snug"><?= htmlspecialchars($servico['nome_servico']) ?></h3>
                                            <?php if (!empty($servico['codigo_tributacao_nacional'])): ?>
                                                <span class="text-[9px] bg-cyan-50 text-cyan-700 px-1 py-0.5 rounded border border-cyan-200 font-mono font-semibold">cTrib: <?= htmlspecialchars($servico['codigo_tributacao_nacional']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <span class="text-xs text-gray-500 flex items-center gap-1 mt-0.5">
                                            <span class="material-icons text-[13px]">schedule</span> <?= (int)($servico['duracao_minutos'] ?? 30) ?> min
                                        </span>
                                    </div>
                                </div>
                                
                                <?php if (AppHelper::isVetMode()): ?>
                                    <div class="flex flex-wrap gap-1 mb-3">
                                        <?php if (!empty($servico['disponivel_clinica'])): ?>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200">
                                                Clínica
                                            </span>
                                        <?php endif; ?>
                                        <?php if (!empty($servico['disponivel_banho'])): ?>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-teal-50 text-teal-700 border border-teal-200">
                                                Banho & Tosa
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>

                                <div class="text-gray-600 mb-4 flex justify-between items-baseline pt-2 border-t border-gray-50">
                                    <span class="text-xs uppercase tracking-wide text-gray-400 block">Valor Sugerido</span>
                                    <span class="text-lg font-bold text-cyan-700">R$
                                        <?= number_format($servico['valor_sugerido'], 2, ',', '.') ?></span>
                                </div>
                                <div class="pt-2 border-t border-gray-50 grid grid-cols-2 gap-2">
                                    <a href="servico_form.php?id=<?= $servico['id_servico'] ?>"
                                        class="text-center bg-gray-50 hover:bg-cyan-50 text-gray-700 hover:text-cyan-700 py-2 rounded-lg text-sm font-medium transition-colors flex items-center justify-center gap-1">
                                        <span class="material-icons text-base">edit</span> Editar
                                    </a>
                                    <button type="button" onclick="alterarStatusServico(<?= $servico['id_servico'] ?>, 0, '<?= htmlspecialchars(addslashes($servico['nome_servico'])) ?>')"
                                        class="text-center bg-rose-50 hover:bg-rose-100 text-rose-700 py-2 rounded-lg text-sm font-medium transition-colors flex items-center justify-center gap-1">
                                        <span class="material-icons text-base">pause_circle_filled</span> Desativar
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="bg-white p-8 rounded-xl shadow-sm border border-gray-100 text-center text-gray-500">
                            Nenhum serviço ativo encontrado.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ABA 2: SERVIÇOS DESATIVADOS -->
            <div id="tab-content-desativados" class="tab-pane hidden">
                <!-- Desktop Table Desativados -->
                <div class="hidden md:block bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-6 opacity-95">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-100 text-gray-600 text-sm uppercase tracking-wider">
                                    <th class="p-4 font-medium">Serviço (Desativado)</th>
                                    <?php if (AppHelper::isVetMode()): ?>
                                        <th class="p-4 font-medium">Módulos</th>
                                    <?php endif; ?>
                                    <th class="p-4 font-medium">Duração</th>
                                    <th class="p-4 font-medium">Valor Sugerido</th>
                                    <th class="p-4 font-medium text-right">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="text-gray-600 text-sm divide-y divide-gray-100">
                                <?php if (!empty($servicos_desativados)): ?>
                                    <?php foreach ($servicos_desativados as $servico): ?>
                                        <tr class="hover:bg-gray-50/70 transition bg-gray-50/40" id="row-servico-<?= $servico['id_servico'] ?>">
                                            <td class="p-4 font-medium text-gray-600 flex items-center gap-3">
                                                <div class="w-10 h-10 rounded-xl bg-gray-200 text-gray-500 flex items-center justify-center flex-shrink-0">
                                                    <span class="material-icons text-lg"><?= htmlspecialchars($servico['icone_servico'] ?? (AppHelper::isVetMode() ? 'pets' : 'build')) ?></span>
                                                </div>
                                                <div>
                                                    <div class="font-semibold text-gray-700 flex items-center gap-2">
                                                        <?= htmlspecialchars($servico['nome_servico']) ?>
                                                        <span class="px-2 py-0.5 text-[10px] uppercase font-bold bg-gray-200 text-gray-600 rounded-full">Inativo</span>
                                                    </div>
                                                    <?php if (!empty($servico['descricao_fiscal'])): ?>
                                                        <div class="text-xs text-gray-400">NFS-e: <?= htmlspecialchars($servico['descricao_fiscal']) ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <?php if (AppHelper::isVetMode()): ?>
                                                <td class="p-4">
                                                    <div class="flex flex-wrap gap-1 opacity-70">
                                                        <?php if (!empty($servico['disponivel_clinica'])): ?>
                                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600 border border-gray-200">
                                                                Clínica
                                                            </span>
                                                        <?php endif; ?>
                                                        <?php if (!empty($servico['disponivel_banho'])): ?>
                                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600 border border-gray-200">
                                                                Banho & Tosa
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            <?php endif; ?>
                                            <td class="p-4 text-gray-500">
                                                <span class="inline-flex items-center gap-1">
                                                    <span class="material-icons text-sm text-gray-400">schedule</span>
                                                    <?= (int)($servico['duracao_minutos'] ?? 30) ?> min
                                                </span>
                                            </td>
                                            <td class="p-4 font-semibold text-gray-600">
                                                R$ <?= number_format($servico['valor_sugerido'], 2, ',', '.') ?>
                                            </td>
                                            <td class="p-4 text-right">
                                                <div class="flex items-center justify-end gap-2">
                                                    <a href="servico_form.php?id=<?= $servico['id_servico'] ?>"
                                                        class="p-2 text-gray-500 hover:text-cyan-700 hover:bg-cyan-50 rounded-lg transition" title="Editar Serviço">
                                                        <span class="material-icons text-lg">edit</span>
                                                    </a>
                                                    <button type="button" onclick="alterarStatusServico(<?= $servico['id_servico'] ?>, 1, '<?= htmlspecialchars(addslashes($servico['nome_servico'])) ?>')"
                                                        class="px-3 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 rounded-lg text-xs font-semibold inline-flex items-center gap-1 transition border border-emerald-200" title="Reativar Serviço">
                                                        <span class="material-icons text-sm">play_circle_filled</span> Reativar
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="<?= AppHelper::isVetMode() ? '5' : '4' ?>" class="p-8 text-center text-gray-500">
                                            Nenhum serviço desativado no momento.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Mobile Cards Desativados -->
                <div class="md:hidden space-y-4">
                    <?php if (!empty($servicos_desativados)): ?>
                        <?php foreach ($servicos_desativados as $servico): ?>
                            <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-200 opacity-90" id="card-servico-<?= $servico['id_servico'] ?>">
                                <div class="flex items-center gap-3 mb-3">
                                    <div class="w-10 h-10 rounded-xl bg-gray-100 text-gray-500 flex items-center justify-center flex-shrink-0 border border-gray-200">
                                        <span class="material-icons text-xl"><?= htmlspecialchars($servico['icone_servico'] ?? (AppHelper::isVetMode() ? 'pets' : 'build')) ?></span>
                                    </div>
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2">
                                            <h3 class="font-bold text-gray-700 leading-snug"><?= htmlspecialchars($servico['nome_servico']) ?></h3>
                                            <span class="px-1.5 py-0.5 text-[9px] uppercase font-bold bg-gray-200 text-gray-600 rounded">Inativo</span>
                                        </div>
                                        <span class="text-xs text-gray-400 flex items-center gap-1 mt-0.5">
                                            <span class="material-icons text-[13px]">schedule</span> <?= (int)($servico['duracao_minutos'] ?? 30) ?> min
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="text-gray-500 mb-4 flex justify-between items-baseline pt-2 border-t border-gray-50">
                                    <span class="text-xs uppercase tracking-wide text-gray-400 block">Valor Sugerido</span>
                                    <span class="text-lg font-bold text-gray-600">R$
                                        <?= number_format($servico['valor_sugerido'], 2, ',', '.') ?></span>
                                </div>
                                <div class="pt-2 border-t border-gray-50 grid grid-cols-2 gap-2">
                                    <a href="servico_form.php?id=<?= $servico['id_servico'] ?>"
                                        class="text-center bg-gray-50 hover:bg-cyan-50 text-gray-700 hover:text-cyan-700 py-2 rounded-lg text-sm font-medium transition-colors flex items-center justify-center gap-1">
                                        <span class="material-icons text-base">edit</span> Editar
                                    </a>
                                    <button type="button" onclick="alterarStatusServico(<?= $servico['id_servico'] ?>, 1, '<?= htmlspecialchars(addslashes($servico['nome_servico'])) ?>')"
                                        class="text-center bg-emerald-50 hover:bg-emerald-100 text-emerald-700 py-2 rounded-lg text-sm font-medium transition-colors flex items-center justify-center gap-1 border border-emerald-200">
                                        <span class="material-icons text-base">play_circle_filled</span> Reativar
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="bg-white p-8 rounded-xl shadow-sm border border-gray-100 text-center text-gray-500">
                            Nenhum serviço desativado no momento.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </main>
    </div>

    <?php include 'components/layout_scripts.php'; ?>
    <script>
        function switchTab(tab) {
            $('.tab-pane').addClass('hidden');
            $('#btn-tab-ativos, #btn-tab-desativados')
                .removeClass('text-cyan-600 border-b-2 border-cyan-600 font-bold')
                .addClass('text-gray-500 font-medium');

            if (tab === 'ativos') {
                $('#tab-content-ativos').removeClass('hidden');
                $('#btn-tab-ativos')
                    .addClass('text-cyan-600 border-b-2 border-cyan-600 font-bold')
                    .removeClass('text-gray-500 font-medium');
            } else {
                $('#tab-content-desativados').removeClass('hidden');
                $('#btn-tab-desativados')
                    .addClass('text-cyan-600 border-b-2 border-cyan-600 font-bold')
                    .removeClass('text-gray-500 font-medium');
            }
        }

        function showAlert(msg, isSuccess = true) {
            const el = $('#alertServicoMsg');
            el.removeClass('hidden bg-emerald-50 border-emerald-200 text-emerald-800 bg-rose-50 border-rose-200 text-rose-800');
            if (isSuccess) {
                el.addClass('bg-emerald-50 border-emerald-200 text-emerald-800')
                  .html(`<span class="material-icons text-emerald-600">check_circle</span><div>${msg}</div>`);
            } else {
                el.addClass('bg-rose-50 border-rose-200 text-rose-800')
                  .html(`<span class="material-icons text-rose-600">error</span><div>${msg}</div>`);
            }
            el.fadeIn();
            $('html, body').animate({ scrollTop: el.offset().top - 80 }, 300);
        }

        function alterarStatusServico(idServico, novoStatus, nomeServico) {
            const acaoTexto = novoStatus === 1 ? 'reativar' : 'desativar';
            const confirmMsg = novoStatus === 1 
                ? `Deseja realmente reativar o serviço "${nomeServico}"?` 
                : `Deseja realmente desativar o serviço "${nomeServico}"?\n\nEle deixará de ser sugerido para novos contratos e atendimentos.`;

            if (!confirm(confirmMsg)) {
                return;
            }

            $.ajax({
                url: 'app.php',
                type: 'POST',
                data: {
                    action: 'alterar_status_servico',
                    id_servico: idServico,
                    ativo: novoStatus
                },
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        showAlert(res.message, true);
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        showAlert(res.message || `Não foi possível ${acaoTexto} o serviço.`, false);
                    }
                },
                error: function() {
                    showAlert('Erro de comunicação com o servidor. Tente novamente.', false);
                }
            });
        }
    </script>
</body>

</html>