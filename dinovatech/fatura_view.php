<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}
include "../database.php";
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers/AppHelper.php';

$id_fatura = $_GET['id'] ?? null;
$fatura = null;
$error_msg = "";

if ($id_fatura) {
    // We will use AJAX to fetch details to keep consistency with app.php logic or fetch here. 
    // Fetching here is better for SSR initial view.
    $link = DBConnect();
    $id_safe = mysqli_real_escape_string($link, $id_fatura);

    // Fetch Header
    $query = "SELECT F.*, C.nome AS nome_cliente, C.cpf_cnpj, C.email AS email_cliente 
              FROM Faturas F JOIN Clientes C ON F.id_cliente = C.id_cliente 
              WHERE F.id_fatura = '$id_safe'";
    $result = DBExecute($link, $query);
    if ($result && mysqli_num_rows($result) > 0) {
        $fatura = mysqli_fetch_assoc($result);

        // Fetch Items
        $items = [];
        $query_items = "SELECT I.*, S.nome_servico FROM ItensFatura I JOIN Servicos S ON I.id_servico = S.id_servico WHERE I.id_fatura = '$id_safe'";
        $res_items = DBExecute($link, $query_items);
        while ($row = mysqli_fetch_assoc($res_items))
            $items[] = $row;

        // Fetch Payments
        $pagamentos = [];
        $query_pag = "SELECT * FROM Pagamentos WHERE id_fatura = '$id_safe' ORDER BY data_pagamento DESC";
        $res_pag = DBExecute($link, $query_pag);
        while ($row = mysqli_fetch_assoc($res_pag))
            $pagamentos[] = $row;

        // Calculate Paid
        $total_pago = 0;
        foreach ($pagamentos as $p) {
            if ($p['status_pagamento'] == 'Confirmado')
                $total_pago += $p['valor_pago'];
        }

        // Calculate Totals with Retention
        $calcTotals = AppHelper::calculateFaturaTotals($link, $id_fatura);
        $valorLiquidoFatura = $calcTotals['valor_liquido'];
        $saldo_devedor = $valorLiquidoFatura - $total_pago;

        // Fetch NFS-e
        $nfse_list = [];
        $query_nfse = "SELECT * FROM NfseEmissoes WHERE id_fatura = '$id_safe' ORDER BY id_emissao DESC";
        $res_nfse = DBExecute($link, $query_nfse);
        while ($row = mysqli_fetch_assoc($res_nfse))
            $nfse_list[] = $row;

        // Fetch Company Config
        $config_emissor = [];
        $query_config = "SELECT * FROM ConfiguracoesEmissor LIMIT 1";
        $res_config = DBExecute($link, $query_config);
        if ($res_config && mysqli_num_rows($res_config) > 0) {
            $config_emissor = mysqli_fetch_assoc($res_config);
        }
        $isFiscalAtivo = !empty($config_emissor['modulo_fiscal_ativo']) && (int)$config_emissor['modulo_fiscal_ativo'] === 1;
    } else {
        $error_msg = "Fatura não encontrada.";
    }
    DBClose($link);
} else {
    $error_msg = "ID da fatura não fornecido.";
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <title>Fatura #
        <?= $id_fatura ?> - Dinovatech
    </title>
    <?php include 'components/layout_head.php'; ?>
    <style>
        @media print {
            body * {
                visibility: hidden;
            }

            #printableArea,
            #printableArea * {
                visibility: visible;
            }

            #printableArea {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
            }

            .no-print {
                display: none !important;
            }
        }

        /* Fix for jQuery UI Autocomplete z-index in Modals */
        .ui-autocomplete {
            z-index: 9999 !important;
        }
    </style>
</head>

<body class="bg-gray-50 flex">

    <?php include 'components/sidebar.php'; ?>

    <div class="flex-1 flex flex-col lg:ml-64 min-h-screen transition-all duration-300">
        <main class="flex-1 p-6 mt-16 lg:mt-0">

            <div class="flex items-center mb-6 no-print">
                <a href="cliente_detalhes.php?id=<?= $fatura['id_cliente'] ?>"
                    class="mr-4 text-gray-500 hover:text-gray-700">
                    <span class="material-icons">arrow_back</span>
                </a>
                <h2 class="text-3xl font-bold text-gray-800">Fatura #
                    <?= $id_fatura ?>
                </h2>
            </div>

            <?php if ($error_msg): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <strong class="font-bold">Erro!</strong>
                    <span class="block sm:inline">
                        <?= $error_msg ?>
                    </span>
                </div>
            <?php else: ?>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

                    <!-- Fatura Detail (Left 2/3) -->
                    <div class="lg:col-span-2 space-y-6">

                        <!-- Header Card -->
                        <div id="printableArea" class="bg-white p-8 rounded-xl shadow-sm border border-gray-100 relative">
                            <!-- Status Badge -->
                            <div class="absolute top-8 right-8 no-print">
                                <?php
                                $statusColor = 'bg-gray-100 text-gray-700 border-gray-200';

                                if ($fatura['status'] === 'Liquidada') {
                                    $statusColor = 'bg-green-100 text-green-700 border-green-200';
                                } elseif ($fatura['status'] === 'Em Aberto') {
                                    $statusColor = 'bg-yellow-100 text-yellow-700 border-yellow-200';
                                } elseif ($fatura['status'] === 'Atrasada') {
                                    $statusColor = 'bg-red-100 text-red-700 border-red-200';
                                }
                                // Override for visual consistency if overdue
                                if ($fatura['status'] == 'Em Aberto' && strtotime($fatura['data_vencimento']) < time()) {
                                    $fatura['status'] = 'Atrasada';
                                    $statusColor = 'bg-red-100 text-red-700 border-red-200';
                                }
                                ?>
                                <span
                                    class="px-4 py-2 rounded-full border text-sm font-bold uppercase tracking-wide <?= $statusColor ?>">
                                    <?= $fatura['status'] ?>
                                </span>
                            </div>

                            <!-- Watermark/Stamp if Paid -->
                            <?php if ($fatura['status'] === 'Liquidada'): ?>
                                <div
                                    class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 border-[6px] border-green-600 text-green-600 font-bold text-8xl px-8 py-2 rounded-xl opacity-20 rotate-[-15deg] pointer-events-none select-none z-0 whitespace-nowrap">
                                    PAGO
                                </div>
                            <?php endif; ?>

                            <div class="mb-8 border-b pb-6 relative z-10">
                                <?php
                                $empresaNome = $config_emissor['nome_fantasia'] ?? $config_emissor['razao_social'] ?? 'Minha Empresa';
                                $empresaCnpj = $config_emissor['cnpj'] ?? '00.000.000/0000-00';
                                $empresaEndereco = $config_emissor['endereco'] . ', ' . $config_emissor['numero'];
                                if (!empty($config_emissor['complemento']))
                                    $empresaEndereco .= ' - ' . $config_emissor['complemento'];
                                $empresaEndereco .= ' - ' . $config_emissor['bairro'];
                                ?>
                                <h1 class="text-2xl font-bold text-gray-800 mb-1"><?= htmlspecialchars($empresaNome) ?></h1>
                                <p class="text-sm text-gray-500 mb-1">CNPJ: <?= htmlspecialchars($empresaCnpj) ?></p>
                                <p class="text-sm text-gray-500 mb-1"><?= htmlspecialchars($empresaEndereco) ?></p>
                                <p class="text-sm text-gray-400">Documento Auxiliar de Cobrança</p>
                            </div>

                            <div class="grid grid-cols-2 gap-8 mb-8">
                                <div>
                                    <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Cliente</h4>
                                    <p class="font-semibold text-gray-800 text-lg">
                                        <?= htmlspecialchars($fatura['nome_cliente']) ?>
                                    </p>
                                    <p class="text-gray-500 text-sm">
                                        <?= htmlspecialchars($fatura['cpf_cnpj']) ?>
                                    </p>
                                </div>
                                <div class="text-right">
                                    <div class="mb-2">
                                        <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Vencimento
                                        </h4>
                                        <p class="font-bold text-gray-800 text-lg">
                                            <?= date('d/m/Y', strtotime($fatura['data_vencimento'])) ?>
                                        </p>
                                    </div>
                                    <div>
                                        <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Emissão
                                        </h4>
                                        <p class="text-gray-500 text-sm">
                                            <?= date('d/m/Y', strtotime($fatura['data_emissao'])) ?>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Items Table -->
                            <table class="w-full text-left mb-8">
                                <thead>
                                    <tr class="border-b border-gray-200">
                                        <th class="py-2 text-sm font-semibold text-gray-600">Descrição</th>
                                        <th class="py-2 text-sm font-semibold text-gray-600 text-center">Qtd</th>
                                        <th class="py-2 text-sm font-semibold text-gray-600 text-right">Unitário</th>
                                        <th class="py-2 text-sm font-semibold text-gray-600 text-right">Total</th>
                                        <th class="py-2 no-print"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($items as $item): ?>
                                        <tr class="border-b border-gray-100">
                                            <td class="py-3">
                                                <p class="font-medium text-gray-800">
                                                    <?= htmlspecialchars($item['nome_servico']) ?>
                                                </p>
                                                <p class="text-sm text-gray-500">
                                                    <?= htmlspecialchars($item['tag'] ?? '') ?>
                                                </p>
                                            </td>
                                            <td class="py-3 text-center text-gray-600">
                                                <?= $item['quantidade'] ?>
                                            </td>
                                            <td class="py-3 text-right text-gray-600">R$
                                                <?= number_format($item['valor_unitario'], 2, ',', '.') ?>
                                            </td>
                                            <td class="py-3 text-right font-medium text-gray-800">R$
                                                <?= number_format($item['quantidade'] * $item['valor_unitario'], 2, ',', '.') ?>
                                            </td>
                                            <td class="py-3 text-right no-print">
                                                <button onclick="deleteItem(<?= $item['id_item_fatura'] ?>)"
                                                    class="text-red-400 hover:text-red-600"><span
                                                        class="material-icons text-sm">delete</span></button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>

                            <!-- Totals -->
                            <div class="flex justify-end">
                                <div class="text-right space-y-2 w-48">
                                    <div class="flex justify-between text-gray-600">
                                        <span>Subtotal</span>
                                        <span>R$
                                            <?= number_format($fatura['valor_total_fatura'], 2, ',', '.') ?>
                                        </span>
                                    </div>
                                    <?php if ($calcTotals['iss_retido']): ?>
                                        <div class="flex justify-between text-red-500 text-sm">
                                            <span>(-) <?= $calcTotals['detalhes_retencao'] ?></span>
                                            <span>R$
                                                <?= number_format($calcTotals['valor_retencao'], 2, ',', '.') ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($calcTotals['desconto_aplicado']) && $calcTotals['desconto_aplicado'] > 0): ?>
                                        <div class="flex justify-between text-emerald-600 text-sm">
                                            <span>
                                                (-) Desconto
                                                <?php if ($calcTotals['tipo_desconto'] == 'percentual'): ?>
                                                    (<?= number_format($calcTotals['valor_desconto_original'], 2, ',', '.') ?>%)
                                                <?php endif; ?>
                                            </span>
                                            <span>R$
                                                <?= number_format($calcTotals['desconto_aplicado'], 2, ',', '.') ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                    <div class="flex justify-between font-bold text-xl text-gray-800 pt-2 border-t">
                                        <span>Total a Receber</span>
                                        <span>R$
                                            <?= number_format($valorLiquidoFatura, 2, ',', '.') ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Add Item Button (No Print) -->
                        <div class="no-print text-right">
                            <button onclick="openAddItemModal()"
                                class="bg-gray-800 text-white px-4 py-2 rounded-lg font-medium hover:bg-gray-900 transition flex items-center inline-flex">
                                <span class="material-icons text-sm mr-2">add</span> Adicionar Item
                            </button>
                            </button>
                            <button onclick="openIncorporarModal()"
                                class="bg-cyan-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-cyan-700 transition flex items-center inline-flex ml-2">
                                <span class="material-icons text-sm mr-2">auto_fix_high</span> Incorporar Recorrências
                            </button>
                            <button onclick="openEditarFaturaModal()"
                                class="bg-indigo-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-indigo-700 transition flex items-center inline-flex ml-2">
                                <span class="material-icons text-sm mr-2">edit</span> Editar Detalhes
                            </button>
                        </div>

                    </div>

                    <!-- Sidebar Actions (Right 1/3) -->
                    <div class="space-y-6 no-print">

                        <!-- Payment Status Card -->
                        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                            <h3 class="font-bold text-gray-800 mb-4">Pagamentos</h3>
                            <div class="space-y-3 mb-6">
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-500">Total Pago</span>
                                    <span class="font-medium text-green-600">R$
                                        <?= number_format($total_pago, 2, ',', '.') ?>
                                    </span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-500">Saldo a Receber</span>
                                    <span class="font-bold text-red-600">R$
                                        <?= number_format($saldo_devedor, 2, ',', '.') ?>
                                    </span>
                                </div>
                            </div>

                            <?php if ($saldo_devedor > 0): ?>
                                <button onclick="openPagamentoModal()"
                                    class="w-full bg-green-600 hover:bg-green-700 text-white py-3 rounded-lg font-medium transition shadow-md mb-2">Registrar
                                    Pagamento</button>
                            <?php endif; ?>
                            <?php if ($isFiscalAtivo || !empty($nfse_list)): ?>
                            <!-- NFS-e Section -->
                            <div class="mt-4 border-t pt-4">
                                <h3 class="font-bold text-gray-800 mb-2">Nota Fiscal (NFS-e)</h3>

                                <?php
                                $hasAuthorized = false;
                                $hasCompletedNfse = !empty($fatura['possui_nfse']);
                                $activeNfse = [];
                                $errorNfse = [];

                                if (!empty($nfse_list)) {
                                    foreach ($nfse_list as $nfse) {
                                        $statusLower = strtolower($nfse['status'] ?? '');

                                        if ($statusLower === 'concluido') {
                                            $hasCompletedNfse = true;
                                        }

                                        if ($statusLower == 'concluido' && isset($nfse['ambiente']) && $nfse['ambiente'] == 'producao')
                                            $hasAuthorized = true;

                                        // Group: IF NOT 'concluido' AND NOT 'processando' -> Error/History
                                        if ($statusLower !== 'concluido' && $statusLower !== 'processando') {
                                            $errorNfse[] = $nfse;
                                        } else {
                                            $activeNfse[] = $nfse;
                                        }
                                    }
                                }

                                // Function to render NFSe Item
                                if (!function_exists('renderNfseItem')) {
                                    function renderNfseItem($nfse)
                                    {
                                        $statusClass = 'text-gray-500';
                                        $icon = 'history';
                                        $statusLower = strtolower($nfse['status'] ?? '');

                                        if ($statusLower == 'concluido') {
                                            $statusClass = 'text-green-600';
                                            $icon = 'check_circle';
                                        } elseif ($statusLower == 'processando') {
                                            $statusClass = 'text-blue-500';
                                            $icon = 'hourglass_empty';
                                        } else {
                                            // Assume error or failure if not success/processing
                                            $statusClass = 'text-red-500';
                                            $icon = 'error';
                                        }

                                        // Parsed Info
                                        $numero_nota = 'Pending';
                                        if ($nfse['xml_retorno']) {
                                            preg_match('/<Numero>(.*?)<\/Numero>/', $nfse['xml_retorno'], $m);
                                            if (!empty($m[1]))
                                                $numero_nota = $m[1];
                                        }

                                        $html = '<div class="bg-gray-50 p-2 rounded border border-gray-100 mb-2 text-sm">';
                                        $html .= '<div class="flex items-center justify-between mb-1">';
                                        $html .= '<span class="font-bold ' . $statusClass . ' flex items-center"><span class="material-icons text-sm mr-1">' . $icon . '</span>' . ucfirst($nfse['status']) . '</span>';
                                        $html .= '<span class="text-xs text-gray-400">' . date('d/m H:i', strtotime($nfse['data_emissao'])) . '</span>';
                                        $html .= '</div>';

                                        if ($statusLower == 'concluido') {
                                            $html .= '<div class="text-xs text-gray-600 mt-1">';
                                            $html .= '<strong>Número:</strong> ' . $numero_nota . '<br>';
                                            $html .= '<strong>RPS:</strong> ' . $nfse['numero_rps'] . '/' . $nfse['serie_rps'] . '<br>';
                                            $html .= '<span class="text-[10px] text-gray-400">' . ucfirst($nfse['ambiente']) . '</span>';
                                            $html .= '</div>';

                                            $html .= '<div class="grid grid-cols-2 gap-2 mt-2">';
                                            $html .= '<a href="ver_nfse_xml.php?id=' . $nfse['id_emissao'] . '" target="_blank" class="text-center text-xs bg-blue-50 text-blue-600 py-1 rounded hover:bg-blue-100 border border-blue-200">XML Assinado</a>';
                                            if ($nfse['url_pdf']) {
                                                $html .= '<a href="' . $nfse['url_pdf'] . '" target="_blank" class="text-center text-xs bg-red-50 text-red-600 py-1 rounded hover:bg-red-100 border border-red-200">PDF</a>';
                                            } else {
                                                $html .= '<button onclick="consultarUrlNfse(' . $nfse['id_emissao'] . ')" class="text-center text-xs bg-gray-100 text-gray-600 py-1 rounded hover:bg-gray-200 border border-gray-300" title="Tentar obter link PDF na Prefeitura">Buscar PDF</button>';
                                            }
                                            $html .= '</div>';
                                        } elseif ($statusLower !== 'processando') {
                                            $html .= '<div class="text-xs text-red-400 mt-1 leading-tight max-h-16 overflow-y-auto">';
                                            $html .= substr(strip_tags($nfse['xml_retorno']), 0, 100) . '...';
                                            $html .= '</div>';
                                        }
                                        $html .= '</div>';
                                        return $html;
                                    }
                                }

                                // Render Active
                                if (empty($nfse_list)) {
                                    echo "<p class='text-xs text-gray-400 mb-2 italic'>Nenhuma nota emitida.</p>";
                                } else {
                                    foreach ($activeNfse as $nfse)
                                        echo renderNfseItem($nfse);

                                    if (!empty($errorNfse)) {
                                        echo '<details class="group mt-2">';
                                        echo '<summary class="flex items-center text-xs text-red-500 cursor-pointer hover:text-red-700 font-medium list-none">';
                                        echo '<span class="material-icons text-sm mr-1 transition group-open:rotate-90">chevron_right</span>';
                                        echo 'Ver ' . count($errorNfse) . ' falhas/tentativas';
                                        echo '</summary>';
                                        echo '<div class="mt-2 pl-2 border-l-2 border-red-100">';
                                        foreach ($errorNfse as $nfse)
                                            echo renderNfseItem($nfse);
                                        echo '</div>';
                                        echo '</details>';
                                    }
                                }
                                ?>

                                <?php if ($isFiscalAtivo && !$hasAuthorized): ?>
                                    <!-- NFS-e Preview Card -->
                                    <div id="nfsePreviewCard"
                                        class="hidden bg-blue-50 p-3 rounded-lg border border-blue-100 mb-3">
                                        <h4
                                            class="text-[10px] font-bold text-blue-800 uppercase tracking-wider mb-2 flex items-center">
                                            <span class="material-icons text-xs mr-1">info</span> Resumo Fiscal (Prévia)
                                        </h4>
                                        <div class="text-[11px] text-blue-900 space-y-1 leading-tight">
                                            <div class="grid grid-cols-2 gap-1">
                                                <p><strong>CNAE:</strong> <span id="nfseCnae">...</span></p>
                                                <p><strong>LC116:</strong> <span id="nfseItList">...</span></p>
                                                <p><strong>NBS:</strong> <span id="nfseNbs">...</span></p>
                                                <p><strong>Aliq:</strong> <span id="nfseAliq">...</span>%</p>
                                                <p><strong>Retido:</strong> <span id="nfseRet">...</span></p>
                                            </div>
                                            <p class="truncate" title=""><strong>Desc:</strong> <span id="nfseDesc">...</span>
                                            </p>
                                            <p class="truncate"><strong>Tomador:</strong> <span id="nfseTomador">...</span></p>

                                            <div id="nfseErrors"
                                                class="hidden mt-2 p-2 bg-red-100 text-red-700 rounded border border-red-200">
                                            </div>
                                        </div>
                                    </div>

                                    <button id="btnGerarNfse" onclick="gerarNfse()"
                                        class="w-full bg-cyan-600 hover:bg-cyan-700 text-white py-3 rounded-lg font-medium transition shadow-md mb-2 flex justify-center items-center">
                                        <span class="material-icons text-sm mr-2">receipt</span> Gerar NFS-e
                                    </button>
                                    <button type="button" onclick="openImportarNfseModal()"
                                        class="w-full bg-gray-100 hover:bg-gray-200 text-gray-700 border border-gray-300 py-2.5 rounded-lg text-xs font-semibold flex justify-center items-center gap-1.5 transition mb-2">
                                        <span class="material-icons text-sm text-cyan-600">sync_alt</span> Importar / Vincular NFS-e
                                    </button>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                            <?php if ($hasCompletedNfse): ?>
                            <!-- Premium Card: ContaDev-Contabilidade -->
                            <div class="mt-4 border-t pt-4">
                                <div class="bg-gradient-to-br from-slate-900 to-slate-800 text-white p-4 rounded-xl shadow-md border border-slate-700 mb-3 relative overflow-hidden">
                                    <div class="flex items-center justify-between mb-2">
                                        <div class="flex items-center space-x-2">
                                            <span class="material-icons text-emerald-400 text-lg">cloud_upload</span>
                                            <h3 class="font-bold text-sm text-white">ContaDev Contabilidade</h3>
                                        </div>
                                        <span id="badge_contadev_sync" class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-700 text-slate-300">
                                            Verificando...
                                        </span>
                                    </div>

                                    <div id="contadev_feedback_info" class="text-xs text-slate-300 space-y-1 mb-3">
                                        <p class="text-[11px] leading-tight">Sincronize os arquivos desta fatura (PDF e XML assinado) com seu painel de contabilidade.</p>
                                    </div>

                                    <button id="btnImportarContaDev" onclick="importarContaDev(<?= $id_fatura ?>)"
                                        class="w-full bg-emerald-600 hover:bg-emerald-500 active:bg-emerald-700 text-white py-2.5 px-3 rounded-lg text-xs font-bold transition flex items-center justify-center gap-1.5 shadow">
                                        <span class="material-icons text-sm">cloud_upload</span> Importar no ContaDev
                                    </button>
                                </div>
                            </div>
                            <?php endif; ?>

                            <button onclick="window.print()"
                                class="w-full bg-white border border-gray-300 text-gray-700 py-2 rounded-lg font-medium hover:bg-gray-50 transition">Imprimir
                                / PDF</button>

                            <?php if (!empty($config_emissor['google_oauth_email'])): ?>
                                <button id="btnEnviarEmail" onclick="enviarFaturaEmail(<?= $id_fatura ?>)"
                                    class="w-full bg-cyan-600 hover:bg-cyan-700 text-white py-2.5 rounded-lg font-medium transition mt-2 flex justify-center items-center gap-2">
                                    <span class="material-icons text-base">email</span> Enviar por E-mail
                                </button>
                            <?php else: ?>
                                <div class="text-[10px] text-gray-400 italic text-center mt-2">
                                    Integração com Gmail inativa nas configurações.
                                </div>
                            <?php endif; ?>

                            <!-- Payment History -->
                            <div class="mt-6 pt-4 border-t">
                                <h4 class="text-xs font-bold text-gray-800 uppercase tracking-wider mb-2">Histórico de
                                    Pagamentos</h4>
                                <ul class="space-y-2">
                                    <?php
                                    $activePayments = [];
                                    $archivedPayments = [];

                                    foreach ($pagamentos as $pag) {
                                        if ($pag['status_pagamento'] == 'Confirmado' || $pag['status_pagamento'] == 'Pendente') {
                                            $activePayments[] = $pag;
                                        } else {
                                            $archivedPayments[] = $pag;
                                        }
                                    }

                                    function renderPaymentItem($pag)
                                    {
                                        // Calculate expiration info
                                        $expInfo = "";
                                        if ($pag['status_pagamento'] != 'Confirmado' && !empty($pag['calendario'])) {
                                            $cal = json_decode($pag['calendario'], true);
                                            if (isset($cal['criacao']) && isset($cal['expiracao'])) {
                                                $dtCriacao = new DateTime($cal['criacao']);
                                                $dtCriacao->modify("+{$cal['expiracao']} seconds");
                                                $dtCriacao->setTimezone(new DateTimeZone('America/Sao_Paulo'));
                                                $expString = $dtCriacao->format('d/m/Y H:i');
                                                $now = new DateTime('now', new DateTimeZone('America/Sao_Paulo'));

                                                if ($now > $dtCriacao) {
                                                    $expInfo = "<span class='block text-[10px] text-red-400'>Expirou em: $expString</span>";
                                                } else {
                                                    $expInfo = "<span class='block text-[10px] text-blue-400'>Expira em: $expString</span>";
                                                }
                                            }
                                        }

                                        $statusColor = $pag['status_pagamento'] == 'Confirmado' ? 'text-green-600' :
                                            ($pag['status_pagamento'] == 'Expirado' ? 'text-gray-400' :
                                                ($pag['status_pagamento'] == 'Cancelado' ? 'text-red-400' : 'text-yellow-600'));

                                        $html = '<li class="text-sm bg-gray-50 p-2 rounded border border-gray-100">';
                                        $html .= '<div class="flex justify-between mb-1">';
                                        $html .= '<span class="font-medium text-gray-800">R$ ' . number_format($pag['valor_pago'], 2, ',', '.') . '</span>';
                                        $html .= '<div class="text-right">';
                                        $html .= '<span class="text-gray-500 text-xs block">' . date('d/m', strtotime($pag['data_pagamento'])) . '</span>';
                                        $html .= $expInfo;
                                        $html .= '</div></div>';

                                        $html .= '<div class="flex justify-between items-center text-xs">';
                                        $html .= '<span class="' . $statusColor . '">' . $pag['status_pagamento'] . '</span>';

                                        if (($pag['status_pagamento'] == 'Pendente' || $pag['status_pagamento'] == 'Expirado') && !empty($pag['txid'])) {
                                            $html .= '<button onclick="verificarPix(\'' . $pag['txid'] . '\')" class="ml-2 text-blue-500 hover:text-blue-700" title="Verificar Pagamento na API"><span class="material-icons text-sm">search</span></button>';
                                        }
                                        if ($pag['status_pagamento'] == 'Confirmado') {
                                            $html .= '<button onclick="estornarPagamento(' . $pag['id_pagamento'] . ')" class="text-red-400 hover:underline">Estornar</button>';
                                        }
                                        $html .= '</div></li>';
                                        return $html;
                                    }

                                    foreach ($activePayments as $pag)
                                        echo renderPaymentItem($pag);

                                    if (!empty($archivedPayments)) {
                                        echo '<details class="group mt-2">';
                                        echo '<summary class="flex items-center text-xs text-gray-500 cursor-pointer hover:text-gray-700 font-medium list-none">';
                                        echo '<span class="material-icons text-sm mr-1 transition group-open:rotate-90">chevron_right</span>';
                                        echo 'Ver ' . count($archivedPayments) . ' cancelados/expirados';
                                        echo '</summary>';
                                        echo '<div class="mt-2 pl-2 border-l-2 border-gray-200 space-y-2 opacity-75">';
                                        foreach ($archivedPayments as $pag)
                                            echo renderPaymentItem($pag);
                                        echo '</div>';
                                        echo '</details>';
                                    }

                                    if (empty($activePayments) && empty($archivedPayments)) {
                                        echo "<p class='text-xs text-gray-400 italic'>Nenhum pagamento registrado.</p>";
                                    }
                                    ?>
                                </ul>
                            </div>
                        </div>

                        <!-- Attachments Card -->
                        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="font-bold text-gray-800">Anexos</h3>
                                <button onclick="openUploadModal()"
                                    class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                    + Adicionar
                                </button>
                            </div>

                            <ul id="listaAnexos" class="space-y-3">
                                <li class="text-center text-gray-400 text-sm py-2">Carregando anexos...</li>
                            </ul>
                        </div>

                    </div>
                </div>

            <?php endif; ?>

        </main>
    </div>

    <!-- Modals -->
    <!-- Add Item Modal -->
    <div id="modalAddItem"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 hidden no-print">
        <div class="bg-white rounded-xl shadow-lg w-full max-w-md p-6">
            <h3 class="text-xl font-bold text-gray-800 mb-4">Adicionar Item</h3>
            <form id="formAddItem">
                <input type="hidden" name="action" value="adicionar_item_fatura">
                <input type="hidden" name="id_fatura" value="<?= $id_fatura ?>">

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Serviço</label>
                    <input type="text" id="servicoSearch" placeholder="Buscar serviço..."
                        class="w-full p-2 border rounded-lg">
                    <input type="hidden" name="id_servico" id="selectedServicoId">
                </div>
                <!-- Logic for simple Service Select if search fails or simple usage -->
                <!-- We can rely on autocomplete from jQuery UI included in layout_scripts -->

                <div class="flex gap-4 mb-4">
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Quantidade</label>
                        <input type="number" name="quantidade" value="1" min="1" class="w-full p-2 border rounded-lg">
                    </div>
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Valor Unit.</label>
                        <input type="number" name="valor_unitario" id="valorUnitario" step="0.01"
                            class="w-full p-2 border rounded-lg">
                    </div>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Descrição Adicional (Tag)</label>
                    <input type="text" name="tag" class="w-full p-2 border rounded-lg">
                </div>

                <div class="flex justify-end gap-2">
                    <button type="button" onclick="$('#modalAddItem').addClass('hidden')"
                        class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg">Cancelar</button>
                    <button type="submit" class="px-4 py-2 bg-cyan-600 text-white rounded-lg">Adicionar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Register Payment Modal -->
    <div id="modalPagamento"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 hidden no-print">
        <div class="bg-white rounded-xl shadow-lg w-full max-w-md p-6">
            <h3 class="text-xl font-bold text-gray-800 mb-4">Registrar Pagamento</h3>
            <form id="formPagamento">
                <input type="hidden" name="action" value="registrar_pagamento">
                <input type="hidden" name="id_fatura" value="<?= $id_fatura ?>">

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Valor Pago</label>
                    <input type="number" name="valor_pago" value="<?= $saldo_devedor ?>" step="0.01"
                        class="w-full p-2 border rounded-lg font-bold text-gray-800">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Data Pagamento</label>
                    <input type="date" name="data_pagamento" value="<?= date('Y-m-d') ?>"
                        class="w-full p-2 border rounded-lg">
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Observação</label>
                    <textarea name="observacao" class="w-full p-2 border rounded-lg" rows="2"></textarea>
                </div>

                <div class="flex justify-end gap-2">
                    <button type="button" onclick="$('#modalPagamento').addClass('hidden')"
                        class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg">Cancelar</button>
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg">Confirmar
                        Pagamento</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Incorporar Modal -->
    <div id="modalIncorporar"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 hidden no-print">
        <div class="bg-white rounded-xl shadow-lg w-full max-w-sm p-6">
            <h3 class="text-xl font-bold text-gray-800 mb-4">Incorporar Recorrências</h3>
            <p class="text-sm text-gray-600 mb-4">Buscar contratos ativos neste mês e adicionar à fatura.</p>
            <form id="formIncorporar">
                <input type="hidden" name="action" value="incorporar_recorrencias_na_fatura">
                <input type="hidden" name="id_fatura" value="<?= $id_fatura ?>">
                <input type="hidden" name="id_cliente" value="<?= $fatura['id_cliente'] ?>">

                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Mês de Referência (YYYY-MM)</label>
                    <!-- Default to Invoice month -->
                    <input type="month" name="mes_ano_fatura"
                        value="<?= date('Y-m', strtotime($fatura['data_emissao'])) ?>"
                        class="w-full p-2 border rounded-lg">
                </div>

                <div class="flex justify-end gap-2">
                    <button type="button" onclick="$('#modalIncorporar').addClass('hidden')"
                        class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg">Cancelar</button>
                    <button type="submit" class="px-4 py-2 bg-cyan-600 text-white rounded-lg">Processar</button>
                </div>
            </form>
        </div>
    </div>


    <!--  Upload Modal -->
    <div id="modalUpload"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 hidden no-print">
        <div class="bg-white rounded-xl shadow-lg w-full max-w-sm p-6">
            <h3 class="text-xl font-bold text-gray-800 mb-4">Anexar Arquivo</h3>
            <form id="formUpload" enctype="multipart/form-data">
                <input type="hidden" name="action" value="upload_arquivo_fatura">
                <input type="hidden" name="id_fatura" value="<?= $id_fatura ?>">

                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Selecione o arquivo (Max 10MB)</label>
                    <input type="file" name="arquivo" required class="w-full text-sm text-gray-500
                        file:mr-4 file:py-2 file:px-4
                        file:rounded-full file:border-0
                        file:text-sm file:font-semibold
                        file:bg-blue-50 file:text-blue-700
                        hover:file:bg-blue-100">
                    <p class="text-xs text-gray-500 mt-1">PDF, Imagens, XML, ZIP</p>
                </div>

                <!-- Progress Bar (Initially Hidden) -->
                <div id="uploadProgress" class="hidden mb-4">
                    <div class="w-full bg-gray-200 rounded-full h-2.5">
                        <div class="bg-blue-600 h-2.5 rounded-full" style="width: 0%"></div>
                    </div>
                    <p class="text-xs text-center text-gray-500 mt-1">Enviando...</p>
                </div>

                <div class="flex justify-end gap-2">
                    <button type="button" onclick="$('#modalUpload').addClass('hidden')"
                        class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg">Cancelar</button>
                    <button type="submit" id="btnUploadSubmit"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg">Enviar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Invoice Data Modal -->
    <div id="modalEditarFatura"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 hidden no-print">
        <div class="bg-white rounded-xl shadow-lg w-full max-w-md p-6">
            <h3 class="text-xl font-bold text-gray-800 mb-4">Editar Detalhes da Fatura</h3>
            <form id="formEditarFatura">
                <input type="hidden" name="action" value="editar_fatura_dados">
                <input type="hidden" name="id_fatura" value="<?= $id_fatura ?>">

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Data de Vencimento</label>
                    <input type="date" name="data_vencimento" value="<?= $fatura['data_vencimento'] ?>"
                        class="w-full p-2 border rounded-lg">
                </div>

                <div class="flex gap-4 mb-4">
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Desconto</label>
                        <input type="number" name="desconto_valor" value="<?= $fatura['desconto_valor'] ?? '0.00' ?>"
                            step="0.01" class="w-full p-2 border rounded-lg">
                    </div>
                    <div class="w-1/3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tipo</label>
                        <select name="desconto_tipo" class="w-full p-2 border rounded-lg">
                            <option value="percentual" <?= ($fatura['desconto_tipo'] ?? '') == 'percentual' ? 'selected' : '' ?>>%</option>
                            <option value="fixo" <?= ($fatura['desconto_tipo'] ?? '') == 'fixo' ? 'selected' : '' ?>>R$
                            </option>
                        </select>
                    </div>
                </div>

                <div class="mb-6">
                    <label
                        class="flex items-center space-x-2 cursor-pointer bg-gray-50 p-3 rounded-lg border border-gray-200">
                        <input type="checkbox" name="permitir_pagamento_parcial" value="1"
                            <?= ($fatura['permitir_pagamento_parcial'] ?? 0) == 1 ? 'checked' : '' ?>
                            class="form-checkbox h-5 w-5 text-blue-600 rounded">
                        <span class="text-gray-700 font-medium">Permitir Pagamento Parcial</span>
                    </label>
                    <p class="text-xs text-gray-500 mt-1 ml-1">Se habilitado, o cliente poderá pagar qualquer valor
                        menor que o total.</p>
                </div>

                <div class="flex items-center justify-between mt-6 pt-4 border-t border-gray-100">
                    <?php if (($fatura['status'] ?? '') !== 'Liquidada'): ?>
                        <button type="button" onclick="excluirFaturaAtual(<?= (int)$id_fatura ?>)"
                            class="px-4 py-2 bg-red-50 text-red-600 hover:bg-red-100 font-medium rounded-lg transition flex items-center gap-1">
                            <span class="material-icons text-sm">delete</span> Excluir Fatura
                        </button>
                    <?php else: ?>
                        <button type="button" disabled title="Faturas liquidadas não podem ser excluídas"
                            class="px-4 py-2 bg-gray-100 text-gray-400 font-medium rounded-lg cursor-not-allowed flex items-center gap-1">
                            <span class="material-icons text-sm">block</span> Excluir (Liquidada)
                        </button>
                    <?php endif; ?>

                    <div class="flex gap-2">
                        <button type="button" onclick="$('#modalEditarFatura').addClass('hidden')"
                            class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg">Cancelar</button>
                        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg">Salvar
                            Alterações</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Importar / Vincular NFS-e -->
    <div id="modalImportarNfse"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 hidden no-print">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-lg p-6 relative max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between border-b pb-3 mb-4">
                <div class="flex items-center space-x-2">
                    <span class="material-icons text-cyan-600">receipt_long</span>
                    <h3 class="text-lg font-bold text-gray-800">Importar / Vincular NFS-e Existente</h3>
                </div>
                <button type="button" onclick="$('#modalImportarNfse').addClass('hidden')"
                    class="text-gray-400 hover:text-gray-600">
                    <span class="material-icons">close</span>
                </button>
            </div>

            <!-- Tabs de Navegação -->
            <div class="flex border-b border-gray-200 mb-4">
                <button type="button" id="tabBtnConsulta" onclick="switchNfseImportTab('consulta')"
                    class="py-2 px-4 text-xs font-bold border-b-2 border-cyan-600 text-cyan-600 flex items-center gap-1">
                    <span class="material-icons text-sm">travel_explore</span> Consultar no ISS DF
                </button>
                <button type="button" id="tabBtnManual" onclick="switchNfseImportTab('manual')"
                    class="py-2 px-4 text-xs font-medium text-gray-500 hover:text-gray-700 border-b-2 border-transparent flex items-center gap-1">
                    <span class="material-icons text-sm">edit_note</span> Vínculo Manual
                </button>
            </div>

            <!-- Aba 1: Consulta Automática no ISS -->
            <div id="tabContentConsulta">
                <form id="formConsultaNfse">
                    <input type="hidden" name="action" value="consultar_e_vincular_nfse">
                    <input type="hidden" name="id_fatura" value="<?= $id_fatura ?>">

                    <div class="bg-cyan-50 p-3 rounded-lg border border-cyan-100 mb-4 text-xs text-cyan-900 leading-relaxed">
                        <span class="font-bold">Como funciona:</span> O sistema usará o certificado digital para consultar a nota no ISS DF (ISSNet), baixará os dados fiscais e o XML assinado, vinculando-a diretamente a esta fatura.
                    </div>

                    <div class="mb-4">
                        <label class="block text-xs font-bold text-gray-700 mb-1">Consultar por:</label>
                        <div class="grid grid-cols-2 gap-2">
                            <label class="flex items-center p-2 border rounded-lg cursor-pointer hover:bg-gray-50 text-xs">
                                <input type="radio" name="tipo_busca" value="numero_nota" checked class="mr-2 text-cyan-600" onchange="toggleTipoBusca(this.value)">
                                <span>Número da NFS-e</span>
                            </label>
                            <label class="flex items-center p-2 border rounded-lg cursor-pointer hover:bg-gray-50 text-xs">
                                <input type="radio" name="tipo_busca" value="numero_rps" class="mr-2 text-cyan-600" onchange="toggleTipoBusca(this.value)">
                                <span>Número do RPS</span>
                            </label>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-3 mb-4">
                        <div class="col-span-2" id="divNumeroBuscaWrapper">
                            <label id="lblNumeroBusca" class="block text-xs font-medium text-gray-700 mb-1">Número da NFS-e</label>
                            <input type="number" name="numero_busca" id="inputNumeroBusca" required placeholder="Ex: 53"
                                class="w-full p-2 border rounded-lg text-sm font-semibold">
                        </div>
                        <div id="divSerieRps" class="hidden">
                            <label class="block text-xs font-medium text-gray-700 mb-1">Série RPS</label>
                            <input type="text" name="serie_rps" value="<?= htmlspecialchars($config_emissor['serie_rps'] ?? '3') ?>"
                                class="w-full p-2 border rounded-lg text-sm">
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-3 border-t">
                        <button type="button" onclick="$('#modalImportarNfse').addClass('hidden')"
                            class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-medium rounded-lg">Cancelar</button>
                        <button type="submit" id="btnSubmitConsultaNfse"
                            class="px-4 py-2 bg-cyan-600 hover:bg-cyan-700 text-white text-xs font-bold rounded-lg flex items-center gap-1 shadow">
                            <span class="material-icons text-sm">search</span> Consultar e Vincular
                        </button>
                    </div>
                </form>
            </div>

            <!-- Aba 2: Vínculo Manual Direto -->
            <div id="tabContentManual" class="hidden">
                <form id="formManualNfse">
                    <input type="hidden" name="action" value="vincular_nfse_manual">
                    <input type="hidden" name="id_fatura" value="<?= $id_fatura ?>">

                    <div class="bg-amber-50 p-3 rounded-lg border border-amber-100 mb-4 text-xs text-amber-900 leading-relaxed">
                        <span class="font-bold">Vínculo Direto:</span> Utilize esta opção caso o WebService da prefeitura esteja offline ou com instabilidade.
                    </div>

                    <div class="grid grid-cols-2 gap-3 mb-3">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Número da NFS-e *</label>
                            <input type="text" name="numero_nota" required placeholder="Ex: 53"
                                class="w-full p-2 border rounded-lg text-sm font-semibold">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Cód. Verificação</label>
                            <input type="text" name="codigo_verificacao" placeholder="Ex: A1B2C3D4"
                                class="w-full p-2 border rounded-lg text-sm">
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-3 mb-3">
                        <div class="col-span-2">
                            <label class="block text-xs font-medium text-gray-700 mb-1">Número do RPS</label>
                            <input type="number" name="numero_rps" placeholder="Ex: 60"
                                class="w-full p-2 border rounded-lg text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Série RPS</label>
                            <input type="text" name="serie_rps" value="<?= htmlspecialchars($config_emissor['serie_rps'] ?? '3') ?>"
                                class="w-full p-2 border rounded-lg text-sm">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3 mb-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Data de Emissão</label>
                            <input type="date" name="data_emissao" value="<?= date('Y-m-d') ?>"
                                class="w-full p-2 border rounded-lg text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Link do PDF (Opcional)</label>
                            <input type="url" name="url_pdf" placeholder="https://..."
                                class="w-full p-2 border rounded-lg text-sm">
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-3 border-t">
                        <button type="button" onclick="$('#modalImportarNfse').addClass('hidden')"
                            class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-medium rounded-lg">Cancelar</button>
                        <button type="submit" id="btnSubmitManualNfse"
                            class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-lg flex items-center gap-1 shadow">
                            <span class="material-icons text-sm">save</span> Salvar e Vincular
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Toast Notification Container -->
    <div id="toast-container" class="fixed bottom-4 right-4 z-50 space-y-2"></div>

    <?php include 'components/layout_scripts.php'; ?>
    <script>
        function excluirFaturaAtual(idFatura) {
            if (!confirm("ATENÇÃO: Tem certeza que deseja excluir esta fatura?\n\nEsta ação é irreversível e excluirá permanentemente os itens, pagamentos e arquivos vinculados a ela.")) {
                return;
            }
            
            $.post('app.php', { action: 'excluir_fatura', id_fatura: idFatura }, function (res) {
                if (res.success) {
                    alert(res.message);
                    if (res.id_cliente) {
                        window.location.href = 'cliente_detalhes.php?id=' + res.id_cliente;
                    } else {
                        window.location.href = 'clientes.php';
                    }
                } else {
                    alert('Erro: ' + (res.message || 'Falha ao excluir fatura.'));
                }
            }, 'json').fail(function() {
                alert('Erro ao se comunicar com o servidor.');
            });
        }

        $(document).ready(function () {
            $('#formEditarFatura').on('submit', function (e) {
                e.preventDefault();
                $.post('app.php', $(this).serialize(), function (res) {
                    if (res.success) {
                        location.reload();
                    } else {
                        alert(res.message);
                    }
                }, 'json');
            });
        });

        function openAddItemModal() { $('#modalAddItem').removeClass('hidden'); }
        function openPagamentoModal() {
            $('#modalPagamento').removeClass('hidden');
        }

        function verificarPix(txid) {
            if (!confirm('Deseja verificar o status deste PIX na API do Inter agora?')) return;

            // Show loading or disable buttons could be good here
            const btn = event.currentTarget;
            const originalContent = btn.innerHTML;
            btn.innerHTML = '<span class="material-icons text-sm animate-spin">refresh</span>';
            btn.disabled = true;

            $.getJSON(`../inter/endpoint.php?action=verificar_pagamento_pix&txid=${txid}`, function (res) {
                if (res.success) {
                    if (res.data.status === 'CONCLUIDA') {
                        alert('Pagamento confirmado com sucesso! A página será recarregada.');
                        location.reload();
                    } else {
                        alert('Status atual na API: ' + res.data.status);
                        btn.innerHTML = originalContent;
                        btn.disabled = false;
                    }
                } else {
                    alert('Erro ao verificar: ' + (res.message || 'Desconhecido'));
                    btn.innerHTML = originalContent;
                    btn.disabled = false;
                }
            }).fail(function () {
                alert('Falha na comunicação com o servidor.');
                btn.innerHTML = originalContent;
                btn.disabled = false;
            });
        }
        function openIncorporarModal() { $('#modalIncorporar').removeClass('hidden'); }
        function openEditarFaturaModal() { $('#modalEditarFatura').removeClass('hidden'); }
        function openUploadModal() { $('#modalUpload').removeClass('hidden'); }
        function openImportarNfseModal() { $('#modalImportarNfse').removeClass('hidden'); }

        function switchNfseImportTab(tab) {
            if (tab === 'consulta') {
                $('#tabBtnConsulta').addClass('border-cyan-600 text-cyan-600 font-bold').removeClass('border-transparent text-gray-500 font-medium');
                $('#tabBtnManual').removeClass('border-cyan-600 text-cyan-600 font-bold').addClass('border-transparent text-gray-500 font-medium');
                $('#tabContentConsulta').removeClass('hidden');
                $('#tabContentManual').addClass('hidden');
            } else {
                $('#tabBtnManual').addClass('border-cyan-600 text-cyan-600 font-bold').removeClass('border-transparent text-gray-500 font-medium');
                $('#tabBtnConsulta').removeClass('border-cyan-600 text-cyan-600 font-bold').addClass('border-transparent text-gray-500 font-medium');
                $('#tabContentManual').removeClass('hidden');
                $('#tabContentConsulta').addClass('hidden');
            }
        }

        function toggleTipoBusca(tipo) {
            if (tipo === 'numero_rps') {
                $('#lblNumeroBusca').text('Número do RPS');
                $('#inputNumeroBusca').attr('placeholder', 'Ex: 60');
                $('#divSerieRps').removeClass('hidden');
                $('#divNumeroBuscaWrapper').removeClass('col-span-3').addClass('col-span-2');
            } else {
                $('#lblNumeroBusca').text('Número da NFS-e');
                $('#inputNumeroBusca').attr('placeholder', 'Ex: 53');
                $('#divSerieRps').addClass('hidden');
                $('#divNumeroBuscaWrapper').removeClass('col-span-2').addClass('col-span-3');
            }
        }

        // Generic Toast Function
        function showToast(message, type = 'success') {
            const colorClass = type === 'success' ? 'bg-green-500' : 'bg-red-500';
            const icon = type === 'success' ? 'check_circle' : 'error';

            const toast = $(`
                <div class="flex items-center w-full max-w-xs p-4 mb-4 text-white rounded-lg shadow ${colorClass} transition-opacity duration-300 opacity-0" role="alert">
                    <div class="inline-flex items-center justify-center flex-shrink-0 w-8 h-8 text-white rounded-lg">
                        <span class="material-icons">${icon}</span>
                    </div>
                    <div class="ml-3 text-sm font-normal">${message}</div>
                    <button type="button" class="ml-auto -mx-1.5 -my-1.5 bg-transparent text-white hover:text-gray-100 rounded-lg focus:ring-2 focus:ring-gray-300 p-1.5 inline-flex h-8 w-8" aria-label="Close" onclick="$(this).parent().remove()">
                        <span class="material-icons text-sm">close</span>
                    </button>
                </div>
            `);

            $('#toast-container').append(toast);

            // Trigger reflow/animation
            requestAnimationFrame(() => {
                toast.removeClass('opacity-0');
            });

            setTimeout(() => {
                toast.addClass('opacity-0');
                setTimeout(() => {
                    toast.remove();
                }, 300);
            }, 5000);
        }

        function deleteItem(id) {
            if (confirm('Tem certeza que deseja remover este item?')) {
                $.post('app.php', { action: 'remover_item_fatura', id_item_fatura: id, id_fatura: <?= $id_fatura ?> }, function (res) {
                    location.reload();
                }, 'json');
            }
        }

        function estornarPagamento(id) {
            if (confirm('Tem certeza que deseja estornar este pagamento?')) {
                $.post('app.php', { action: 'estornar_pagamento', id_pagamento: id, id_fatura: <?= $id_fatura ?> }, function (res) {
                    location.reload();
                }, 'json');
            }
        }

        function carregarAnexos() {
            $.post('app.php', { action: 'get_fatura_arquivos', id_fatura: <?= $id_fatura ?> }, function (res) {
                if (res.success) {
                    let html = '';
                    if (res.data.length > 0) {
                        res.data.forEach(arq => {
                            // Format bytes to KB/MB
                            let sizeStr = '';
                            if (arq.tamanho_bytes < 1024) sizeStr = arq.tamanho_bytes + ' B';
                            else if (arq.tamanho_bytes < 1024 * 1024) sizeStr = (arq.tamanho_bytes / 1024).toFixed(1) + ' KB';
                            else sizeStr = (arq.tamanho_bytes / (1024 * 1024)).toFixed(1) + ' MB';

                            html += `
                                <li class="flex items-center justify-between bg-gray-50 p-2 rounded border border-gray-100">
                                    <div class="flex items-center overflow-hidden">
                                        <span class="material-icons text-gray-500 mr-2 text-xl">description</span>
                                        <div class="truncate">
                                            <a href="${arq.url_publica}" target="_blank" class="text-sm font-medium text-blue-600 hover:underline block truncate" title="${arq.nome_original}">${arq.nome_original}</a>
                                            <span class="text-xs text-gray-400">${sizeStr}</span>
                                        </div>
                                    </div>
                                    <button onclick="excluirAnexo(${arq.id_arquivo})" class="ml-2 text-gray-400 hover:text-red-500" title="Excluir anexo">
                                        <span class="material-icons text-lg">delete</span>
                                    </button>
                                </li>
                            `;
                        });
                    } else {
                        html = '<li class="text-center text-gray-400 text-sm py-2">Nenhum anexo.</li>';
                    }
                    $('#listaAnexos').html(html);
                } else {
                    $('#listaAnexos').html('<li class="text-center text-red-400 text-sm py-2">Erro ao carregar.</li>');
                }
            }, 'json');
        }

        function excluirAnexo(id) {
            if (confirm('Deseja desvincular este arquivo da fatura?')) {
                $.post('app.php', { action: 'excluir_arquivo_fatura', id_arquivo: id, id_fatura: <?= $id_fatura ?> }, function (res) {
                    if (res.success) {
                        showToast(res.message, 'success');
                        carregarAnexos();
                    } else {
                        showToast(res.message, 'error');
                    }
                }, 'json');
            }
        }

        function consultarUrlNfse(idEmissao) {
            const btn = event.target;
            const originalText = btn.innerText;
            btn.disabled = true;
            btn.innerText = '...';

            $.post('app.php', { action: 'consultar_url_nfse', id_emissao: idEmissao }, function (res) {
                if (res.success) {
                    showToast('PDF encontrado! Atualizando...', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(res.message, 'error');
                    btn.disabled = false;
                    btn.innerText = originalText;
                }
            }, 'json');
        }

        function loadNfsePreview() {
            $.post('app.php', { action: 'preview_nfse_data', id_fatura: <?= $id_fatura ?> }, function (res) {
                if (res.success) {
                    $('#nfsePreviewCard').removeClass('hidden');
                    $('#nfseDesc').text(res.data.discriminacao).attr('title', res.data.discriminacao);
                    $('#nfseCnae').text(res.data.tax_settings.codigo_cnae);
                    $('#nfseItList').text(res.data.tax_settings.item_lista_servico);
                    $('#nfseNbs').text(res.data.tax_settings.codigo_nbs);
                    $('#nfseAliq').text(res.data.tax_settings.aliquota_iss);
                    $('#nfseRet').text(res.data.tax_settings.iss_retido == '1' ? 'SIM' : 'NÃO');

                    let tomadorText = res.data.tomador.razao_social;
                    if (res.data.tomador.codigo_municipio) tomadorText += ' (Mun: ' + res.data.tomador.codigo_municipio + ')';

                    $('#nfseTomador').text(tomadorText);

                    if (res.data.validation_errors.length > 0) {
                        $('#nfseErrors').removeClass('hidden').html('<strong>Erros:</strong> ' + res.data.validation_errors.join(', '));
                        $('#btnGerarNfse').prop('disabled', true).addClass('opacity-50 cursor-not-allowed').attr('title', 'Corrija os erros do cliente antes de gerar.');
                    } else {
                        $('#nfseErrors').addClass('hidden');
                        $('#btnGerarNfse').prop('disabled', false).removeClass('opacity-50 cursor-not-allowed');
                    }
                }
            }, 'json');
        }


        function gerarNfse() {
            if (!confirm('Deseja iniciar a geração da NFS-e para esta fatura?')) return;

            const btn = $('#btnGerarNfse');
            const originalText = btn.html();
            btn.prop('disabled', true).html('<span class="material-icons animate-spin text-sm mr-2">refresh</span> Gerando...');

            $.post('app.php', { action: 'gerar_nfse', id_fatura: <?= $id_fatura ?> }, function (res) {
                if (res.success) {
                    showToast(res.message, 'success');
                    // Reload to show new status or attachments
                    setTimeout(() => location.reload(), 2000);
                } else {
                    showToast(res.message, 'error');
                    // Show detailed error in alert for easier debugging
                    let debugMsg = res.details || '';
                    if (res.debug_xml) {
                        console.log("DEBUG XML ENVIO:", res.debug_xml);
                        console.log("DEBUG INPUT:", res.debug_input);
                        debugMsg += "\n\n(Verifique Console [F12] para XML de Envio)";
                    }
                    if (debugMsg) alert(debugMsg);
                    btn.prop('disabled', false).html(originalText);
                }
            }, 'json').fail(function () {
                showToast('Erro de comunicação com o servidor.', 'error');
                btn.prop('disabled', false).html(originalText);
            });
        }

        $(document).ready(function () {
            // Load Attachments
            carregarAnexos();

            // Load NFSe Preview
            <?php if ($isFiscalAtivo && !$hasAuthorized): // Only load if we can generate ?>
                loadNfsePreview();
            <?php endif; ?>

            // Autocomplete for services
            $("#servicoSearch").autocomplete({
                source: function (request, response) {
                    $.post('app.php', { action: 'buscar_servicos', termo: request.term }, function (data) {
                        if (data.success) {
                            response($.map(data.data, function (item) {
                                return { label: item.nome_servico, value: item.nome_servico, id: item.id_servico, price: item.valor_sugerido };
                            }));
                        }
                    }, 'json');
                },
                select: function (event, ui) {
                    $('#selectedServicoId').val(ui.item.id);
                    $('#valorUnitario').val(ui.item.price);
                }
            });

            // Forms
            $('#formAddItem').on('submit', function (e) {
                e.preventDefault();
                $.post('app.php', $(this).serialize(), function (res) {
                    if (res.success) location.reload();
                    else showToast(res.message, 'error');
                }, 'json');
            });

            $('#formConsultaNfse').on('submit', function (e) {
                e.preventDefault();
                const btn = $('#btnSubmitConsultaNfse');
                const origHtml = btn.html();
                btn.prop('disabled', true).html('<span class="material-icons animate-spin text-sm">refresh</span> Consultando...');

                $.post('app.php', $(this).serialize(), function (res) {
                    if (res.success) {
                        showToast(res.message, 'success');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showToast(res.message || 'Erro ao consultar NFS-e', 'error');
                        alert(res.message || 'Erro ao consultar NFS-e.');
                        btn.prop('disabled', false).html(origHtml);
                    }
                }, 'json').fail(function () {
                    showToast('Erro de comunicação com o servidor.', 'error');
                    btn.prop('disabled', false).html(origHtml);
                });
            });

            $('#formManualNfse').on('submit', function (e) {
                e.preventDefault();
                const btn = $('#btnSubmitManualNfse');
                const origHtml = btn.html();
                btn.prop('disabled', true).html('<span class="material-icons animate-spin text-sm">refresh</span> Salvando...');

                $.post('app.php', $(this).serialize(), function (res) {
                    if (res.success) {
                        showToast(res.message, 'success');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showToast(res.message || 'Erro ao vincular NFS-e', 'error');
                        alert(res.message || 'Erro ao vincular NFS-e.');
                        btn.prop('disabled', false).html(origHtml);
                    }
                }, 'json').fail(function () {
                    showToast('Erro de comunicação com o servidor.', 'error');
                    btn.prop('disabled', false).html(origHtml);
                });
            });

            $('#formPagamento').on('submit', function (e) {
                e.preventDefault();
                $.post('app.php', $(this).serialize(), function (res) {
                    if (res.success) location.reload();
                    else showToast(res.message, 'error');
                }, 'json');
            });

            $('#formIncorporar').on('submit', function (e) {
                e.preventDefault();
                $.post('app.php', $(this).serialize(), function (res) {
                    if (res.success) {
                        showToast(res.message, 'success');
                        setTimeout(() => location.reload(), 1500);
                    }
                    else showToast(res.message, 'error');
                }, 'json');
            });

            // Upload Form
            $('#formUpload').on('submit', function (e) {
                e.preventDefault();
                var formData = new FormData(this);

                $('#uploadProgress').removeClass('hidden');
                $('#btnUploadSubmit').prop('disabled', true).addClass('opacity-50');

                $.ajax({
                    url: 'app.php',
                    type: 'POST',
                    data: formData,
                    success: function (res) {
                        if (res.success) {
                            showToast(res.message, 'success');
                            $('#modalUpload').addClass('hidden');
                            $('#formUpload')[0].reset();
                            carregarAnexos();
                        } else {
                            showToast(res.message, 'error');
                        }
                    },
                    error: function () {
                        showToast('Erro de comunicação no upload.', 'error');
                    },
                    complete: function () {
                        $('#uploadProgress').addClass('hidden');
                        $('#btnUploadSubmit').prop('disabled', false).removeClass('opacity-50');
                    },
                    cache: false,
                    contentType: false,
                    processData: false,
                    dataType: 'json'
                });
            });

            // Enviar Fatura por E-mail
            window.enviarFaturaEmail = function (idFatura) {
                const btn = $('#btnEnviarEmail');
                const origHtml = btn.html();
                
                if (confirm('Deseja realmente enviar esta fatura por e-mail para o cliente?')) {
                    btn.prop('disabled', true).addClass('opacity-75 cursor-wait').html('<span class="material-icons text-base animate-spin mr-1">sync</span> Enviando...');
                    
                    $.post('app.php', { action: 'enviar_fatura_email', id_fatura: idFatura }, function (res) {
                        if (res.success) {
                            showToast(res.message, 'success');
                        } else {
                            showToast(res.message, 'error');
                        }
                    }, 'json')
                    .fail(function () {
                        showToast('Erro de comunicação ao enviar e-mail.', 'error');
                    })
                    .always(function () {
                        btn.prop('disabled', false).removeClass('opacity-75 cursor-wait').html(origHtml);
                    });
                }
            };

            // --- CONTADEV INTEGRATION JS ---
            window.verificarStatusContaDevFatura = function() {
                $.post('app.php', { action: 'contadev_check_fatura', id_fatura: <?= (int)$id_fatura ?> }, function(res) {
                    if (res.success && res.data) {
                        const data = res.data;
                        const badge = $('#badge_contadev_sync');
                        const info = $('#contadev_feedback_info');
                        const btn = $('#btnImportarContaDev');

                        if (data.already_imported) {
                            badge.removeClass('bg-slate-700 text-slate-300 bg-red-950 text-red-300').addClass('bg-emerald-950 text-emerald-300 border border-emerald-700/50').text('Sincronizada');
                            
                            let detailsHtml = '<p class="text-emerald-400 font-semibold flex items-center"><span class="material-icons text-xs mr-1">check_circle</span> Fatura já importada na ContaDev</p>';
                            if (data.sync && data.sync.contadev_nf_id) {
                                detailsHtml += `<p class="text-[10px] text-slate-400 font-mono">ID ContaDev: ${data.sync.contadev_nf_id}</p>`;
                            }
                            if (data.sync && data.sync.issued_at) {
                                detailsHtml += `<p class="text-[10px] text-slate-400">Data Emissão: ${data.sync.issued_at}</p>`;
                            }
                            info.html(detailsHtml);
                            btn.attr('onclick', `importarContaDev(<?= (int)$id_fatura ?>, true)`).html('<span class="material-icons text-sm">refresh</span> Re-importar no ContaDev');
                        } else {
                            badge.removeClass('bg-emerald-950 text-emerald-300 bg-red-950 text-red-300').addClass('bg-amber-950 text-amber-300 border border-amber-700/50').text('Não Importada');
                            info.html('<p class="text-[11px] text-slate-300 leading-tight">Fatura pronta para ser importada para a contabilidade no ContaDev.</p>');
                            btn.attr('onclick', `importarContaDev(<?= (int)$id_fatura ?>, false)`).html('<span class="material-icons text-sm">cloud_upload</span> Importar no ContaDev');
                        }
                    }
                }, 'json');
            };

            window.importarContaDev = function(idFatura, force = false) {
                const btn = $('#btnImportarContaDev');
                const origHtml = btn.html();

                btn.prop('disabled', true).addClass('opacity-75 cursor-wait').html('<span class="material-icons text-sm animate-spin mr-1">sync</span> Sincronizando...');

                $.post('app.php', { action: 'contadev_import_fatura', id_fatura: idFatura, force: force ? 1 : 0 }, function(res) {
                    btn.prop('disabled', false).removeClass('opacity-75 cursor-wait').html(origHtml);

                    if (res.success) {
                        showToast(res.message, 'success');
                        verificarStatusContaDevFatura();
                    } else {
                        showToast(res.message || 'Erro ao importar fatura no ContaDev.', 'error');
                    }
                }, 'json').fail(function() {
                    btn.prop('disabled', false).removeClass('opacity-75 cursor-wait').html(origHtml);
                    showToast('Erro de comunicação com o servidor ao importar.', 'error');
                });
            };

            verificarStatusContaDevFatura();
        });
    </script>
</body>

</html>