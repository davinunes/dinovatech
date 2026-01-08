<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}
include "../database.php";

$id_fatura = $_GET['id'] ?? null;
$fatura = null;
$error_msg = "";

if ($id_fatura) {
    // We will use AJAX to fetch details to keep consistency with app.php logic or fetch here. 
    // Fetching here is better for SSR initial view.
    $link = DBConnect();
    $id_safe = mysqli_real_escape_string($link, $id_fatura);

    // Fetch Header
    $query = "SELECT F.*, C.nome AS nome_cliente, C.cpf_cnpj 
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
        $saldo_devedor = $fatura['valor_total_fatura'] - $total_pago;

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

                            <div class="mb-8 border-b pb-6">
                                <h1 class="text-2xl font-bold text-gray-800 mb-2">Dinovatech Tecnologia</h1>
                                <p class="text-sm text-gray-500">Documento Auxiliar de Cobrança</p>
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
                                    <div class="flex justify-between font-bold text-xl text-gray-800 pt-2 border-t">
                                        <span>Total</span>
                                        <span>R$
                                            <?= number_format($fatura['valor_total_fatura'], 2, ',', '.') ?>
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
                            <button onclick="openIncorporarModal()"
                                class="bg-cyan-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-cyan-700 transition flex items-center inline-flex ml-2">
                                <span class="material-icons text-sm mr-2">auto_fix_high</span> Incorporar Recorrências
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
                                    <span class="text-gray-500">Saldo Devedor</span>
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

                            <button onclick="window.print()"
                                class="w-full bg-white border border-gray-300 text-gray-700 py-2 rounded-lg font-medium hover:bg-gray-50 transition">Imprimir
                                / PDF</button>

                            <!-- Payment History -->
                            <div class="mt-6 pt-4 border-t">
                                <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Histórico</h4>
                                <ul class="space-y-2">
                                    <?php foreach ($pagamentos as $pag): ?>
                                        <li class="text-sm bg-gray-50 p-2 rounded border border-gray-100">
                                            <div class="flex justify-between mb-1">
                                                <span class="font-medium text-gray-800">R$
                                                    <?= number_format($pag['valor_pago'], 2, ',', '.') ?>
                                                </span>
                                                <span class="text-gray-500 text-xs">
                                                    <?= date('d/m', strtotime($pag['data_pagamento'])) ?>
                                                </span>
                                            </div>
                                            <div class="flex justify-between items-center text-xs">
                                                <span
                                                    class="<?= $pag['status_pagamento'] == 'Confirmado' ? 'text-green-600' : 'text-red-500' ?>">
                                                    <?= $pag['status_pagamento'] ?>
                                                </span>
                                                <?php if ($pag['status_pagamento'] == 'Confirmado'): ?>
                                                    <button onclick="estornarPagamento(<?= $pag['id_pagamento'] ?>)"
                                                        class="text-red-400 hover:underline">Estornar</button>
                                                <?php endif; ?>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
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


    <?php include 'components/layout_scripts.php'; ?>
    <script>
        function openAddItemModal() { $('#modalAddItem').removeClass('hidden'); }
        function openPagamentoModal() { $('#modalPagamento').removeClass('hidden'); }
        function openIncorporarModal() { $('#modalIncorporar').removeClass('hidden'); }

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

        $(document).ready(function () {
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
                    else alert(res.message);
                }, 'json');
            });

            $('#formPagamento').on('submit', function (e) {
                e.preventDefault();
                $.post('app.php', $(this).serialize(), function (res) {
                    if (res.success) location.reload();
                    else alert(res.message);
                }, 'json');
            });

            $('#formIncorporar').on('submit', function (e) {
                e.preventDefault();
                $.post('app.php', $(this).serialize(), function (res) {
                    if (res.success) {
                        alert(res.message);
                        location.reload();
                    }
                    else alert(res.message);
                }, 'json');
            });
        });
    </script>
</body>

</html>