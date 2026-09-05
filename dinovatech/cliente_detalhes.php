<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}
include "../database.php";
require_once __DIR__ . "/config.php";
require_once __DIR__ . "/helpers/AppHelper.php";
include "components/layout_head.php";

$id_cliente = $_GET['id'] ?? null;
$cliente = null;
$error_msg = "";

if ($id_cliente) {
    $link = DBConnect();
    $id_safe = mysqli_real_escape_string($link, $id_cliente);

    // Get Client Info
    $query = "SELECT * FROM Clientes WHERE id_cliente = '$id_safe'";
    $result = DBExecute($link, $query);
    if ($result && mysqli_num_rows($result) > 0) {
        $cliente = mysqli_fetch_assoc($result);
    } else {
        $error_msg = "Cliente não encontrado.";
    }

    // Get Invoices
    $faturas = [];
    if ($cliente) {
        $query_faturas = "SELECT id_fatura, data_emissao, data_vencimento, valor_total_fatura, status FROM Faturas WHERE id_cliente = '$id_safe' ORDER BY data_emissao DESC";
        $result_faturas = DBExecute($link, $query_faturas);
        while ($row = mysqli_fetch_assoc($result_faturas)) {
            $faturas[] = $row;
        }

        // Get Contracts (Recorrencias)
        $contratos = [];
        $query_contratos = "SELECT R.*, S.nome_servico FROM Recorrencias R JOIN Servicos S ON R.id_servico = S.id_servico WHERE R.id_cliente = '$id_safe' ORDER BY R.data_inicio_cobranca DESC";
        $result_contratos = DBExecute($link, $query_contratos);
        while ($row = mysqli_fetch_assoc($result_contratos)) {
            $contratos[] = $row;
        }

        // Get Pets (Patients) - Only if VET Mode
        $pets = [];
        $cliente_pacotes = [];
        if (AppHelper::isVetMode()) {
            $query_pets = "SELECT p.*, (SELECT MAX(data_atendimento) FROM Atendimentos WHERE id_pet = p.id_pet) as ultimo_atend FROM Pets p WHERE p.id_cliente = '$id_safe' ORDER BY p.nome ASC";
            $result_pets = DBExecute($link, $query_pets);
            if ($result_pets) {
                while ($row = mysqli_fetch_assoc($result_pets)) {
                    $pets[] = $row;
                }
            }

            // Get Pacotes & Saldos
            $query_pacotes = "SELECT cp.*, p.nome_pacote, p.valor_total, p.is_recorrente, p.icone 
                              FROM ClientePacotes cp 
                              JOIN Pacotes p ON cp.id_pacote = p.id_pacote 
                              WHERE cp.id_cliente = '$id_safe' 
                              ORDER BY cp.data_aquisicao DESC";
            $res_pacotes = DBExecute($link, $query_pacotes);
            if ($res_pacotes) {
                while ($cp = mysqli_fetch_assoc($res_pacotes)) {
                    $id_cp = (int)$cp['id_cliente_pacote'];
                    $qS = "SELECT cps.*, s.nome_servico, s.duracao_minutos, (cps.qtd_total - cps.qtd_utilizada) as saldo_restante 
                           FROM ClientePacoteSaldos cps 
                           JOIN Servicos s ON cps.id_servico = s.id_servico 
                           WHERE cps.id_cliente_pacote = $id_cp";
                    $rS = DBExecute($link, $qS);
                    $saldos = [];
                    if ($rS) {
                        while ($sRow = mysqli_fetch_assoc($rS)) {
                            $saldos[] = $sRow;
                        }
                    }
                    $cp['saldos'] = $saldos;
                    $cliente_pacotes[] = $cp;
                }
            }
        }
    }
    // Moved DBClose to end of file to ensure connection stays open for any intermediate needs (though rare)
    // DBClose($link);
} else {
    $error_msg = "ID do cliente não fornecido.";
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <title>Detalhes do Cliente - Dinovatech</title>
</head>

<body class="bg-gray-50 flex">

    <?php include 'components/sidebar.php'; ?>

    <div class="flex-1 flex flex-col lg:ml-64 min-h-screen transition-all duration-300">
        <main class="flex-1 p-6 mt-16 lg:mt-0">

            <div class="flex items-center mb-6">
                <a href="clientes.php" class="mr-4 text-gray-500 hover:text-gray-700">
                    <span class="material-icons">arrow_back</span>
                </a>
                <h2 class="text-3xl font-bold text-gray-800">Detalhes do Cliente</h2>
            </div>

            <?php if ($error_msg): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <strong class="font-bold">Erro!</strong>
                    <span class="block sm:inline">
                        <?= $error_msg ?>
                    </span>
                </div>
            <?php else: ?>

                <!-- Client Info Card -->
                <div
                    class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 mb-8 flex flex-col md:flex-row justify-between items-start md:items-center">
                    <div>
                        <h3 class="text-2xl font-bold text-gray-800 mb-1">
                            <?= htmlspecialchars($cliente['nome']) ?>
                        </h3>
                        <div class="text-gray-500 space-y-1">
                            <p><span class="font-medium text-gray-700">CPF/CNPJ:</span>
                                <?= htmlspecialchars($cliente['cpf_cnpj']) ?>
                            </p>
                            <p><span class="font-medium text-gray-700">Email:</span>
                                <?= htmlspecialchars($cliente['email']) ?>
                            </p>
                            <p><span class="font-medium text-gray-700">Telefone:</span>
                                <?= htmlspecialchars($cliente['telefone']) ?>
                            </p>
                        </div>
                    </div>
                    <div class="mt-4 md:mt-0 flex flex-col gap-2">
                        <a href="cliente_form.php?id=<?= $cliente['id_cliente'] ?>"
                            class="bg-gray-100 text-gray-700 hover:bg-gray-200 px-4 py-2 rounded-lg font-medium transition text-center">Editar
                            Dados</a>

                        <?php if (isset($cliente['ativo']) && $cliente['ativo'] == 0): ?>
                            <a href="app.php?action=toggle_status_cliente&id=<?= $cliente['id_cliente'] ?>&status=1"
                                class="border border-green-500 text-green-600 hover:bg-green-50 px-4 py-2 rounded-lg font-medium transition text-center">
                                Ativar Cliente
                            </a>
                        <?php else: ?>
                            <a href="app.php?action=toggle_status_cliente&id=<?= $cliente['id_cliente'] ?>&status=0"
                                class="border border-red-300 text-red-600 hover:bg-red-50 px-4 py-2 rounded-lg font-medium transition text-center"
                                onclick="return confirm('Tem certeza que deseja inativar este cliente?');">
                                Inativar Cliente
                            </a>
                        <?php endif; ?>

                        <button onclick="openNovaFaturaModal()"
                            class="bg-cyan-600 text-white hover:bg-cyan-700 px-4 py-2 rounded-lg font-medium transition shadow-sm">Nova
                            Fatura</button>
                    </div>
                </div>

                <!-- Pets Section (Full Width) -->
                <?php if (AppHelper::isVetMode()): ?>
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-8">
                        <div class="p-6 border-b border-gray-100 flex justify-between items-center">
                            <h3 class="text-xl font-bold text-gray-800 flex items-center">
                                <span class="material-icons text-cyan-600 mr-2">pets</span> Meus Pets (Pacientes)
                            </h3>
                            <a href="modules/Vet/pet_form.php?client_id=<?= $cliente['id_cliente'] ?>"
                                class="bg-cyan-600 hover:bg-cyan-700 text-white font-medium py-2 px-4 rounded-lg flex items-center transition-colors shadow-sm">
                                <span class="material-icons mr-2">add_circle</span> Novo Pet
                            </a>
                        </div>

                        <?php if (empty($pets)): ?>
                            <div class="p-8 text-center bg-gray-50">
                                <span class="material-icons text-4xl text-gray-300 mb-2">pets</span>
                                <p class="text-gray-500 font-medium">Este cliente ainda não possui pets cadastrados.</p>
                                <p class="text-sm text-gray-400 mt-1">Clique em "Novo Pet" para adicionar o primeiro paciente.</p>
                            </div>
                        <?php else: ?>
                            <!-- Desktop Table -->
                            <div class="hidden md:block overflow-x-auto">
                                <table class="w-full text-left">
                                    <thead class="bg-gray-50 text-gray-600 text-xs uppercase tracking-wider">
                                        <tr>
                                            <th class="p-4 pl-6">Nome / Espécie</th>
                                            <th class="p-4">Raça / Sexo</th>
                                            <th class="p-4">Idade</th>
                                            <th class="p-4">Último Atendimento</th>
                                            <th class="p-4 text-right pr-6">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody class="text-gray-700 text-sm divide-y divide-gray-50">
                                        <?php foreach ($pets as $pet):
                                            $idade_str = '-';
                                            if ($pet['data_nascimento']) {
                                                $dob = new DateTime($pet['data_nascimento']);
                                                $diff = $dob->diff(new DateTime());
                                                $idade_str = $diff->y . ' anos';
                                            }
                                            ?>
                                            <tr class="hover:bg-gray-50 transition">
                                                <td class="p-4 pl-6">
                                                    <div class="font-bold text-gray-900 text-base"><?= htmlspecialchars($pet['nome']) ?>
                                                    </div>
                                                    <div class="flex items-center mt-1">
                                                        <span
                                                            class="text-xs uppercase font-semibold text-gray-500 bg-gray-100 px-2 py-0.5 rounded mr-2"><?= htmlspecialchars($pet['especie']) ?></span>
                                                    </div>
                                                </td>
                                                <td class="p-4">
                                                    <div class="text-gray-900"><?= htmlspecialchars($pet['raca'] ?: 'Indefinida') ?>
                                                    </div>
                                                    <div class="text-xs text-gray-500 mt-1 flex items-center">
                                                        <?= $pet['sexo'] == 'M' ? '<span class="text-blue-600 font-bold">Macho</span>' : '<span class="text-pink-600 font-bold">Fêmea</span>' ?>
                                                    </div>
                                                </td>
                                                <td class="p-4 font-medium"><?= $idade_str ?></td>
                                                <td class="p-4 text-gray-500">
                                                    <?= $pet['ultimo_atend'] ? date('d/m/Y', strtotime($pet['ultimo_atend'])) : 'Nunca atendido' ?>
                                                </td>
                                                <td class="p-4 text-right pr-6">
                                                    <a href="modules/Vet/pet_detalhes.php?id=<?= $pet['id_pet'] ?>"
                                                        class="text-cyan-600 hover:text-cyan-800 font-bold bg-cyan-50 hover:bg-cyan-100 px-3 py-1.5 rounded-lg transition-colors inline-block">
                                                        Prontuário
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Mobile Cards -->
                            <div class="md:hidden grid grid-cols-1 gap-4 p-4 bg-gray-50">
                                <?php foreach ($pets as $pet): ?>
                                    <div
                                        class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 flex justify-between items-center">
                                        <div>
                                            <h4 class="font-bold text-gray-800 text-lg"><?= htmlspecialchars($pet['nome']) ?></h4>
                                            <div class="text-sm text-gray-500 mt-1">
                                                <?= htmlspecialchars($pet['especie']) ?> • <?= htmlspecialchars($pet['raca']) ?>
                                            </div>
                                        </div>
                                        <a href="modules/Vet/pet_detalhes.php?id=<?= $pet['id_pet'] ?>"
                                            class="bg-gray-100 text-cyan-600 p-2 rounded-full">
                                            <span class="material-icons">arrow_forward</span>
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">

                    <!-- Faturas Section -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="p-6 border-b border-gray-100 flex justify-between items-center">
                            <h3 class="text-lg font-bold text-gray-800">Faturas</h3>
                        </div>
                        <!-- Desktop Table -->
                        <div class="hidden md:block overflow-x-auto max-h-[500px] overflow-y-auto">
                            <table class="w-full text-left border-collapse">
                                <thead class="bg-gray-50 text-gray-600 text-xs uppercase tracking-wider sticky top-0">
                                    <tr>
                                        <th class="p-4">ID</th>
                                        <th class="p-4">Vencimento</th>
                                        <th class="p-4">Valor</th>
                                        <th class="p-4">Status</th>
                                        <th class="p-4"></th>
                                    </tr>
                                </thead>
                                <tbody class="text-sm text-gray-700">
                                    <?php if (empty($faturas)): ?>
                                        <tr>
                                            <td colspan="5" class="p-6 text-center text-gray-500">Nenhuma fatura registrada.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($faturas as $fatura):
                                            $statusClass = 'text-gray-600 bg-gray-100';
                                            $hoje = date('Y-m-d');

                                            if ($fatura['status'] === 'Liquidada') {
                                                $statusClass = 'text-green-600 bg-green-100';
                                            } elseif ($fatura['status'] === 'Em Aberto') {
                                                if ($fatura['data_vencimento'] < $hoje) {
                                                    $statusClass = 'text-red-600 bg-red-100';
                                                } else {
                                                    $statusClass = 'text-yellow-600 bg-yellow-100';
                                                }
                                            }

                                            $statusLabel = ($fatura['status'] == 'Em Aberto' && $fatura['data_vencimento'] < $hoje) ? 'Atrasada' : $fatura['status'];
                                            ?>
                                            <tr class="border-b border-gray-50 hover:bg-gray-50">
                                                <td class="p-4">#
                                                    <?= $fatura['id_fatura'] ?>
                                                </td>
                                                <td class="p-4">
                                                    <?= date('d/m/Y', strtotime($fatura['data_vencimento'])) ?>
                                                </td>
                                                <td class="p-4 font-medium">R$
                                                    <?= number_format($fatura['valor_total_fatura'], 2, ',', '.') ?>
                                                </td>
                                                <td class="p-4"><span
                                                        class="px-2 py-1 rounded text-xs font-bold <?= $statusClass ?>">
                                                        <?= $statusLabel ?>
                                                    </span></td>
                                                <td class="p-4 text-right">
                                                    <a href="fatura_view.php?id=<?= $fatura['id_fatura'] ?>"
                                                        class="text-cyan-600 hover:text-cyan-800 font-medium text-xs">Ver</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Mobile Cards -->
                        <div class="md:hidden space-y-3 p-4 bg-gray-50">
                            <?php if (empty($faturas)): ?>
                                <div class="text-center text-gray-500 py-4">Nenhuma fatura registrada.</div>
                            <?php else: ?>
                                <?php foreach ($faturas as $fatura):
                                    $statusClass = 'text-gray-600 bg-gray-100';
                                    $hoje = date('Y-m-d');
                                    if ($fatura['status'] === 'Liquidada') {
                                        $statusClass = 'text-green-600 bg-green-100';
                                    } elseif ($fatura['status'] === 'Em Aberto') {
                                        if ($fatura['data_vencimento'] < $hoje) {
                                            $statusClass = 'text-red-600 bg-red-100';
                                        } else {
                                            $statusClass = 'text-yellow-600 bg-yellow-100';
                                        }
                                    }
                                    $statusLabel = ($fatura['status'] == 'Em Aberto' && $fatura['data_vencimento'] < $hoje) ? 'Atrasada' : $fatura['status'];
                                    ?>
                                    <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
                                        <div class="flex justify-between items-start mb-2">
                                            <div>
                                                <span class="text-xs text-gray-400">#<?= $fatura['id_fatura'] ?></span>
                                                <div class="text-sm text-gray-500">
                                                    Venc: <?= date('d/m/Y', strtotime($fatura['data_vencimento'])) ?>
                                                </div>
                                            </div>
                                            <span class="px-2 py-1 rounded text-xs font-bold <?= $statusClass ?>">
                                                <?= $statusLabel ?>
                                            </span>
                                        </div>
                                        <div class="flex justify-between items-end mt-2">
                                            <div class="text-lg font-bold text-gray-800">
                                                R$ <?= number_format($fatura['valor_total_fatura'], 2, ',', '.') ?>
                                            </div>
                                            <a href="fatura_view.php?id=<?= $fatura['id_fatura'] ?>"
                                                class="bg-gray-50 hover:bg-gray-100 text-gray-700 px-3 py-1.5 rounded-lg text-xs font-medium transition-colors">
                                                Ver Detalhes
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Contratos / Recorrência Section -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="p-6 border-b border-gray-100 flex justify-between items-center">
                            <h3 class="text-lg font-bold text-gray-800">Contratos (Recorrência)</h3>
                            <button
                                onclick="window.location.href='contrato_form.php?cliente_id=<?= $cliente['id_cliente'] ?>'"
                                class="text-cyan-600 hover:text-cyan-800 text-sm font-medium flex items-center">
                                <span class="material-icons text-base mr-1">add</span> Adicionar
                            </button>
                        </div>
                        <!-- Desktop Table -->
                        <div class="hidden md:block overflow-x-auto max-h-[500px] overflow-y-auto">
                            <table class="w-full text-left border-collapse">
                                <thead class="bg-gray-50 text-gray-600 text-xs uppercase tracking-wider sticky top-0">
                                    <tr>
                                        <th class="p-4">Serviço</th>
                                        <th class="p-4">Valor</th>
                                        <th class="p-4">Início</th>
                                        <th class="p-4"></th>
                                    </tr>
                                </thead>
                                <tbody class="text-sm text-gray-700">
                                    <?php if (empty($contratos)): ?>
                                        <tr>
                                            <td colspan="4" class="p-6 text-center text-gray-500">Nenhum contrato ativo.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($contratos as $contrato):
                                            $is_expired = !empty($contrato['data_fim']) && $contrato['data_fim'] < date('Y-m-d');
                                            ?>
                                            <tr
                                                class="border-b border-gray-50 hover:bg-gray-50 <?= $is_expired ? 'bg-red-50' : '' ?>">
                                                <td class="p-4 font-medium">
                                                    <div class="flex items-center gap-2">
                                                        <span><?= htmlspecialchars($contrato['nome_servico']) ?></span>
                                                        <?php if ($is_expired): ?>
                                                            <span
                                                                class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-red-100 text-red-800">EXP</span>
                                                        <?php endif; ?>
                                                        <?php if (!empty(trim(strip_tags($contrato['descricao_personalizada'] ?? '')))): ?>
                                                            <button type="button" 
                                                                onclick="verNotasContrato(<?= $contrato['id_recorrencia'] ?>)"
                                                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-semibold bg-cyan-50 text-cyan-700 border border-cyan-200 hover:bg-cyan-100 transition shadow-sm"
                                                                title="Visualizar Anotações Técnicas deste Contrato">
                                                                <span class="material-icons text-[13px]">engineering</span> Notas Técnicas
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td class="p-4">R$
                                                    <?= number_format($contrato['valor_sugerido_recorrencia'], 2, ',', '.') ?> /
                                                    <?= ucfirst($contrato['tipo_periodo']) ?>
                                                </td>
                                                <td class="p-4">
                                                    <?= date('d/m/Y', strtotime($contrato['data_inicio_cobranca'])) ?>
                                                </td>
                                                <td class="p-4 text-right">
                                                    <a href="contrato_form.php?id=<?= $contrato['id_recorrencia'] ?>"
                                                        class="text-gray-400 hover:text-gray-600 transition"><span
                                                            class="material-icons text-base">edit</span></a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Mobile Cards -->
                        <div class="md:hidden space-y-3 p-4 bg-gray-50">
                            <?php if (empty($contratos)): ?>
                                <div class="text-center text-gray-500 py-4">Nenhum contrato ativo.</div>
                            <?php else: ?>
                                <?php foreach ($contratos as $contrato):
                                    $is_expired = !empty($contrato['data_fim']) && $contrato['data_fim'] < date('Y-m-d');
                                    $card_class = $is_expired ? 'bg-red-50 border-red-200' : 'bg-white border-gray-100';
                                    ?>
                                    <div class="<?= $card_class ?> p-4 rounded-xl shadow-sm border mb-3">
                                        <div class="flex justify-between items-start mb-2">
                                            <h4 class="font-bold text-gray-800"><?= htmlspecialchars($contrato['nome_servico']) ?>
                                            </h4>
                                            <?php if ($is_expired): ?>
                                                <span
                                                    class="px-2 py-0.5 rounded text-xs font-bold bg-red-100 text-red-800">Vencido</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-sm text-gray-600 mb-3">
                                            <div class="flex justify-between">
                                                <span>R$ <?= number_format($contrato['valor_sugerido_recorrencia'], 2, ',', '.') ?>
                                                    / <?= ucfirst($contrato['tipo_periodo']) ?></span>
                                            </div>
                                            <div class="mt-1">Início:
                                                <?= date('d/m/Y', strtotime($contrato['data_inicio_cobranca'])) ?>
                                            </div>
                                        </div>
                                        <div class="flex justify-end pt-2 border-t border-gray-200/50">
                                            <a href="contrato_form.php?id=<?= $contrato['id_recorrencia'] ?>"
                                                class="bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 px-3 py-1.5 rounded-lg text-xs font-medium transition-colors w-full text-center">
                                                Editar Contrato
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if (AppHelper::isVetMode()): ?>
                        <!-- Pacotes & Saldos de Banho/Tosa Section -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mt-6">
                            <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gradient-to-r from-teal-50/50 to-white">
                                <div class="flex items-center gap-2">
                                    <span class="material-icons text-teal-600">card_giftcard</span>
                                    <div>
                                        <h3 class="text-lg font-bold text-gray-800">Pacotes & Créditos de Banho/Tosa</h3>
                                        <p class="text-xs text-gray-500">Saldos de utilização e combos ativos deste cliente.</p>
                                    </div>
                                </div>
                                <a href="modules/Vet/pacotes.php"
                                    class="bg-teal-600 hover:bg-teal-700 text-white text-xs font-semibold py-1.5 px-3 rounded-lg flex items-center transition shadow-sm">
                                    <span class="material-icons text-sm mr-1">add</span> Vincular Pacote
                                </a>
                            </div>

                            <div class="p-6">
                                <?php if (empty($cliente_pacotes)): ?>
                                    <div class="text-center py-6 text-gray-400 text-sm">
                                        Nenhum pacote de banho/tosa ativo para este cliente.
                                    </div>
                                <?php else: ?>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <?php foreach ($cliente_pacotes as $cp): ?>
                                            <div class="bg-gray-50 rounded-xl p-4 border border-gray-200/80 shadow-sm flex flex-col justify-between">
                                                <div>
                                                    <div class="flex items-start justify-between gap-2 mb-2">
                                                        <div class="flex items-center gap-2">
                                                            <div class="w-9 h-9 rounded-lg bg-teal-100 text-teal-700 flex items-center justify-center">
                                                                <span class="material-icons text-lg"><?= htmlspecialchars($cp['icone'] ?: 'card_giftcard') ?></span>
                                                            </div>
                                                            <div>
                                                                <h4 class="font-bold text-sm text-gray-900"><?= htmlspecialchars($cp['nome_pacote']) ?></h4>
                                                                <span class="text-[11px] text-gray-500">Adquirido em <?= date('d/m/Y', strtotime($cp['data_aquisicao'])) ?></span>
                                                            </div>
                                                        </div>
                                                        <span class="px-2 py-0.5 rounded text-[11px] font-bold <?= $cp['status'] === 'ativo' ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-200 text-gray-600' ?>">
                                                            <?= ucfirst($cp['status']) ?>
                                                        </span>
                                                    </div>

                                                    <!-- Saldos list -->
                                                    <div class="space-y-2 mt-3 pt-3 border-t border-gray-200">
                                                        <?php foreach ($cp['saldos'] as $sal):
                                                            $total = (int)$sal['qtd_total'];
                                                            $util = (int)$sal['qtd_utilizada'];
                                                            $rest = (int)$sal['saldo_restante'];
                                                            $perc = $total > 0 ? round(($util / $total) * 100) : 0;
                                                        ?>
                                                            <div>
                                                                <div class="flex justify-between text-xs font-medium text-gray-700 mb-1">
                                                                    <span><?= htmlspecialchars($sal['nome_servico']) ?></span>
                                                                    <span class="font-bold <?= $rest > 0 ? 'text-teal-700' : 'text-gray-400' ?>">
                                                                        <?= $rest ?> de <?= $total ?> restantes
                                                                    </span>
                                                                </div>
                                                                <div class="w-full bg-gray-200 rounded-full h-1.5 overflow-hidden">
                                                                    <div class="bg-teal-600 h-1.5 rounded-full" style="width: <?= 100 - $perc ?>%"></div>
                                                                </div>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                </div>

            <?php endif; ?>

        </main>
    </div>

    <!-- Modal Nova Fatura (Simplificado para criação rápida) -->
    <div id="modalNovaFatura" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 hidden">
        <div class="bg-white rounded-xl shadow-lg w-full max-w-md p-6">
            <h3 class="text-xl font-bold text-gray-800 mb-4">Nova Fatura</h3>
            <form id="formNovaFatura">
                <input type="hidden" name="action" value="criar_fatura">
                <input type="hidden" name="id_cliente" value="<?= $id_cliente ?>">

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Data de Emissão</label>
                    <input type="date" name="data_emissao" value="<?= date('Y-m-d') ?>"
                        class="w-full p-2 border rounded-lg">
                </div>
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Data de Vencimento</label>
                    <input type="date" name="data_vencimento" value="<?= date('Y-m-d') ?>" required
                        class="w-full p-2 border rounded-lg">
                </div>

                <div class="flex justify-end gap-2">
                    <button type="button" onclick="closeNovaFaturaModal()"
                        class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">Cancelar</button>
                    <button type="submit"
                        class="px-4 py-2 bg-cyan-600 text-white rounded-lg hover:bg-cyan-700">Criar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal de Anotações Técnicas do Contrato -->
    <div id="modalNotasContrato" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl overflow-hidden animate-fade-in">
            <div class="p-5 border-b border-gray-100 flex justify-between items-center bg-gradient-to-r from-cyan-50/50 to-white">
                <div class="flex items-center gap-2">
                    <span class="material-icons text-cyan-600">engineering</span>
                    <div>
                        <h3 class="text-base font-bold text-gray-800" id="modalNotasTitulo">Anotações Técnicas do Contrato</h3>
                        <p class="text-xs text-gray-500" id="modalNotasServico">Especificações e instruções de atendimento</p>
                    </div>
                </div>
                <button type="button" onclick="fecharModalNotasContrato()" class="text-gray-400 hover:text-gray-600 rounded-lg p-1 transition">
                    <span class="material-icons text-xl">close</span>
                </button>
            </div>
            <div class="p-6 max-h-[65vh] overflow-y-auto" id="modalNotasCorpo">
                <!-- Conteúdo HTML do TinyMCE injetado aqui -->
            </div>
            <div class="p-4 border-t border-gray-100 bg-gray-50 flex justify-between items-center">
                <a id="modalNotasBtnEditar" href="#" class="inline-flex items-center gap-1 text-xs font-semibold text-cyan-700 hover:text-cyan-900">
                    <span class="material-icons text-sm">edit</span> Editar Contrato
                </a>
                <button type="button" onclick="fecharModalNotasContrato()" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 text-xs font-semibold rounded-lg transition">
                    Fechar
                </button>
            </div>
        </div>
    </div>

    <?php include 'components/layout_scripts.php'; ?>
    <script>
        // Mapeamento dos contratos com notas técnicas
        const contratosComNotas = <?= json_encode(array_column($contratos, null, 'id_recorrencia')) ?>;

        function verNotasContrato(idRec) {
            const c = contratosComNotas[idRec];
            if (!c) return;

            $('#modalNotasTitulo').text('Anotações Técnicas: ' + (c.nome_servico || 'Contrato'));
            $('#modalNotasServico').text('Contrato #' + idRec + ' • ' + (c.tipo_periodo || ''));
            $('#modalNotasBtnEditar').attr('href', 'contrato_form.php?id=' + idRec);

            let rawHtml = c.descricao_personalizada || '';
            // Se for texto plano antigo sem tags HTML, converte quebras de linha para <br>
            if (!/<[a-z][\s\S]*>/i.test(rawHtml)) {
                rawHtml = $('<div/>').text(rawHtml).html().replace(/\n/g, '<br>');
            }

            $('#modalNotasCorpo').html(`
                <div class="prose prose-sm max-w-none text-gray-700 text-sm leading-relaxed">
                    ${rawHtml}
                </div>
            `);

            $('#modalNotasContrato').removeClass('hidden');
        }

        function fecharModalNotasContrato() {
            $('#modalNotasContrato').addClass('hidden');
        }

        function openNovaFaturaModal() {
            $('#modalNovaFatura').removeClass('hidden');
        }
        function closeNovaFaturaModal() {
            $('#modalNovaFatura').addClass('hidden');
        }

        $('#formNovaFatura').on('submit', function (e) {
            e.preventDefault();
            $.ajax({
                url: 'app.php', post: 'POST', data: $(this).serialize(), dataType: 'json', type: 'POST',
                success: function (response) {
                    if (response.success) {
                        window.location.href = 'fatura_view.php?id=' + response.id_fatura;
                    } else {
                        alert('Erro ao criar fatura: ' + response.message);
                    }
                }
            });
        });
    </script>
</body>
<?php if (isset($link))
    DBClose($link); ?>

</html>