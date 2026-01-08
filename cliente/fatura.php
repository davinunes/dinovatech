<?php
session_set_cookie_params(0, '/');
session_start();
// Security Check
if (!isset($_SESSION['cliente_id'])) {
    header("Location: index.php");
    exit();
}

include "../database.php"; // Using backend database connection
$cliente_id = $_SESSION['cliente_id'];
$id_fatura = $_GET['id'] ?? null;
$fatura = null;
$error_msg = "";

if ($id_fatura) {
    $link = DBConnect();
    $id_safe = mysqli_real_escape_string($link, $id_fatura);
    // Ensure the invoice belongs to the logged client
    $query = "SELECT F.*, C.nome AS nome_cliente, C.cpf_cnpj 
              FROM Faturas F JOIN Clientes C ON F.id_cliente = C.id_cliente 
              WHERE F.id_fatura = '$id_safe' AND F.id_cliente = '$cliente_id'";
    $result = DBExecute($link, $query);
    if ($result && mysqli_num_rows($result) > 0) {
        $fatura = mysqli_fetch_assoc($result);

        $items = [];
        $query_items = "SELECT I.*, S.nome_servico FROM ItensFatura I JOIN Servicos S ON I.id_servico = S.id_servico WHERE I.id_fatura = '$id_safe'";
        $res_items = DBExecute($link, $query_items);
        while ($row = mysqli_fetch_assoc($res_items))
            $items[] = $row;

        $pagamentos = [];
        $query_pag = "SELECT * FROM Pagamentos WHERE id_fatura = '$id_safe' ORDER BY data_pagamento DESC";
        $res_pag = DBExecute($link, $query_pag);
        while ($row = mysqli_fetch_assoc($res_pag))
            $pagamentos[] = $row;

        $total_pago = 0;
        foreach ($pagamentos as $p) {
            if ($p['status_pagamento'] == 'Confirmado')
                $total_pago += $p['valor_pago'];
        }
        $saldo_devedor = $fatura['valor_total_fatura'] - $total_pago;

    } else {
        $error_msg = "Fatura não encontrada ou acesso negado.";
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
        <?= $id_fatura ?> - Área do Cliente
    </title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/kjua@0.9.0/dist/kjua.min.js"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

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
                margin: 0;
                padding: 20px;
            }

            .no-print {
                display: none !important;
            }
        }
    </style>
</head>

<body class="bg-gray-100 min-h-screen py-8">

    <div class="container mx-auto px-4 max-w-4xl">

        <div class="mb-6 no-print flex justify-between items-center">
            <a href="index.php" class="flex items-center text-gray-600 hover:text-gray-900 transition-colors">
                <span class="material-icons mr-2">arrow_back</span>
                Voltar para Minhas Faturas
            </a>
            <?php if (!$error_msg): ?>
                <div>
                    <button onclick="window.print()"
                        class="bg-white border border-gray-300 text-gray-700 font-medium py-2 px-4 rounded-lg shadow-sm hover:bg-gray-50 transition-colors mr-2">
                        Imprimir / PDF
                    </button>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($error_msg): ?>
            <div class="bg-white border-l-4 border-red-500 p-8 rounded-lg shadow-sm text-center">
                <h2 class="text-xl font-bold text-gray-800 mb-2">Ops!</h2>
                <p class="text-gray-600">
                    <?= $error_msg ?>
                </p>
                <a href="index.php"
                    class="inline-block mt-4 bg-cyan-600 text-white px-6 py-2 rounded-lg font-medium hover:bg-cyan-700">Voltar</a>
            </div>
        <?php else: ?>

            <div id="printableArea" class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden relative">

                <!-- Status Badge -->
                <div class="absolute top-0 right-0 p-8 no-print">
                    <?php
                    $hoje = date('Y-m-d');
                    $isVencida = ($fatura['status'] == 'Em Aberto' && $fatura['data_vencimento'] < $hoje);
                    $statusLabel = $fatura['status'];
                    $statusClass = 'bg-gray-100 text-gray-600';

                    if ($fatura['status'] == 'Liquidada') {
                        $statusClass = 'bg-green-100 text-green-700 border border-green-200';
                    } elseif ($isVencida) {
                        $statusLabel = 'Atrasada';
                        $statusClass = 'bg-red-100 text-red-700 border border-red-200';
                    } else {
                        $statusClass = 'bg-yellow-100 text-yellow-700 border border-yellow-200';
                    }
                    ?>
                    <span class="px-4 py-2 rounded-full text-sm font-bold uppercase tracking-wide <?= $statusClass ?>">
                        <?= $statusLabel ?>
                    </span>
                </div>

                <div class="p-8 md:p-12">
                    <!-- Invoice Header -->
                    <div class="border-b border-gray-100 pb-8 mb-8">
                        <div class="w-full">
                            <h1 class="text-3xl font-bold text-gray-900 mb-2">Dinovatech Tecnologia</h1>
                            <p class="text-gray-500 text-sm uppercase tracking-wide">Fatura #
                                <?= $id_fatura ?>
                            </p>
                        </div>
                    </div>

                    <!-- Client & Dates -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
                        <div>
                            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Faturado Para</h3>
                            <p class="text-lg font-bold text-gray-800">
                                <?= htmlspecialchars($fatura['nome_cliente']) ?>
                            </p>
                            <p class="text-gray-600">
                                <?= htmlspecialchars($fatura['cpf_cnpj']) ?>
                            </p>
                        </div>
                        <div class="md:text-right">
                            <div class="mb-4">
                                <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Data de Vencimento
                                </h3>
                                <p class="text-xl font-bold <?= $isVencida ? 'text-red-600' : 'text-gray-800' ?>">
                                    <?= date('d/m/Y', strtotime($fatura['data_vencimento'])) ?>
                                </p>
                            </div>
                            <div>
                                <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Data de Emissão
                                </h3>
                                <p class="text-gray-600">
                                    <?= date('d/m/Y', strtotime($fatura['data_emissao'])) ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Items -->
                    <div class="mb-8">
                        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4 border-b pb-2">Detalhes do
                            Serviço</h3>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead class="text-sm text-gray-500">
                                    <tr>
                                        <th class="pb-4 font-semibold">Descrição</th>
                                        <th class="pb-4 font-semibold text-center">Qtd</th>
                                        <th class="pb-4 font-semibold text-right">Valor Unit.</th>
                                        <th class="pb-4 font-semibold text-right">Total</th>
                                    </tr>
                                </thead>
                                <tbody class="text-gray-700">
                                    <?php foreach ($items as $item): ?>
                                        <tr class="border-b border-gray-50">
                                            <td class="py-4">
                                                <p class="font-bold text-gray-800">
                                                    <?= htmlspecialchars($item['nome_servico']) ?>
                                                </p>
                                                <?php if ($item['tag']): ?>
                                                    <p class="text-sm text-gray-500 mt-1">
                                                        <?= htmlspecialchars($item['tag']) ?>
                                                    </p>
                                                <?php endif; ?>
                                            </td>
                                            <td class="py-4 text-center">
                                                <?= $item['quantidade'] ?>
                                            </td>
                                            <td class="py-4 text-right">R$
                                                <?= number_format($item['valor_unitario'], 2, ',', '.') ?>
                                            </td>
                                            <td class="py-4 text-right font-bold">R$
                                                <?= number_format($item['quantidade'] * $item['valor_unitario'], 2, ',', '.') ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Totals -->
                    <div class="flex justify-end border-t border-gray-100 pt-8">
                        <div class="w-full md:w-1/2 lg:w-1/3">
                            <div class="flex justify-between mb-2 text-gray-600">
                                <span>Subtotal</span>
                                <span>R$
                                    <?= number_format($fatura['valor_total_fatura'], 2, ',', '.') ?>
                                </span>
                            </div>
                            <?php if ($total_pago > 0): ?>
                                <div class="flex justify-between mb-2 text-green-600">
                                    <span>Valor Pago</span>
                                    <span>- R$
                                        <?= number_format($total_pago, 2, ',', '.') ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                            <div
                                class="flex justify-between pt-4 border-t border-gray-200 text-2xl font-bold text-gray-900">
                                <span>Total a Pagar</span>
                                <span>R$
                                    <?= number_format($saldo_devedor, 2, ',', '.') ?>
                                </span>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Payment Action Bar (Bottom) -->
                <?php if ($saldo_devedor > 0): ?>
                    <div
                        class="bg-gray-50 px-8 py-6 border-t border-gray-200 flex flex-col md:flex-row justify-between items-center no-print">
                        <p class="text-gray-600 mb-4 md:mb-0 text-sm">
                            <span class="material-icons text-base align-middle mr-1">security</span>
                            Pagamento seguro via PIX
                        </p>
                        <button id="btnPagarPix"
                            class="w-full md:w-auto bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-8 rounded-lg shadow-lg transform transition hover:scale-105 flex items-center justify-center">
                            <span class="material-icons mr-2">qr_code_2</span>
                            Pagar com PIX
                        </button>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Modal PIX -->
            <div id="modalPix" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-75 hidden">
                <div class="bg-white rounded-xl shadow-2xl w-full max-w-md p-8 text-center relative">
                    <button onclick="$('#modalPix').addClass('hidden')"
                        class="absolute top-4 right-4 text-gray-400 hover:text-gray-600">
                        <span class="material-icons">close</span>
                    </button>

                    <h2 class="text-2xl font-bold text-gray-800 mb-2">Pagamento via PIX</h2>
                    <div id="pixLoading" class="py-8">
                        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-cyan-600 mx-auto"></div>
                        <p class="mt-4 text-gray-600">Gerando QR Code...</p>
                    </div>

                    <div id="pixContent" class="hidden">
                        <p class="text-sm text-gray-600 mb-6">Escaneie o QR Code ou use o código Copia e Cola.</p>

                        <div id="qrcodeDisplay"
                            class="mx-auto inline-block p-4 border border-gray-200 rounded-lg mb-6 shadow-sm"></div>

                        <div class="mb-6">
                            <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2 text-left">Pix
                                Copia e Cola</label>
                            <div class="flex">
                                <input type="text" id="pixCopiaColaInput" readonly
                                    class="flex-1 p-2 bg-gray-50 border border-r-0 border-gray-300 rounded-l-lg text-xs text-gray-600 focus:outline-none">
                                <button onclick="copiarPix()"
                                    class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-r-lg font-medium text-xs transition-colors">Copiar</button>
                            </div>
                            <p id="msgCopia" class="text-green-600 text-xs mt-1 hidden font-bold">Copiado!</p>
                        </div>

                        <div class="bg-blue-50 text-blue-700 p-4 rounded-lg text-sm">
                            <p class="font-bold flex items-center justify-center mb-1"><span
                                    class="material-icons text-sm mr-1">sync</span> Aguardando pagamento...</p>
                            <p class="text-xs">Após pagar, a fatura será baixada automaticamente em instantes.</p>
                        </div>
                    </div>

                    <div id="pixSuccess" class="hidden py-8">
                        <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 mb-4">
                            <span class="material-icons text-green-600 text-3xl">check</span>
                        </div>
                        <h3 class="text-xl font-bold text-gray-800 mb-2">Pagamento Confirmado!</h3>
                        <p class="text-gray-600 mb-6">Obrigado. Sua fatura foi liquidada.</p>
                        <button onclick="window.location.reload()"
                            class="bg-gray-800 text-white px-6 py-2 rounded-lg font-medium hover:bg-gray-900">Fechar</button>
                    </div>

                </div>
            </div>

            <script>
                $(document).ready(function () {
                    let pollingInterval;

                    $('#btnPagarPix').click(function () {
                        $('#modalPix').removeClass('hidden');
                        generatePix();
                    });

                    function generatePix() {
                        // Use existing logic from original index.php but adapted
                        // We need to call the INTER endpoint logic
                        $.ajax({
                            url: '../inter/endpoint.php?action=obter_ou_criar_pix_pagamento',
                            type: 'POST',
                            data: JSON.stringify({ id_fatura: <?= $id_fatura ?> }),
                            contentType: 'application/json',
                            dataType: 'json',
                            success: function (response) {
                                if (response.success) {
                                    renderPix(response.data);
                                } else {
                                    alert('Erro ao gerar PIX: ' + response.message);
                                    $('#modalPix').addClass('hidden');
                                }
                            },
                            error: function () {
                                alert('Erro de comunicação.');
                                $('#modalPix').addClass('hidden');
                            }
                        });
                    }

                    function renderPix(data) {
                        $('#pixLoading').addClass('hidden');
                        $('#pixContent').removeClass('hidden');

                        // Generate QR
                        // Generate QR - Higher resolution, scaled down by CSS
                        const el = kjua({ text: data.pixCopiaECola, size: 400, fill: '#000', back: '#fff', quiet: 1 });
                        // Make responsive
                        $(el).css({ 'max-width': '100%', 'height': 'auto' });
                        $('#qrcodeDisplay').html('').append(el);
                        $('#pixCopiaColaInput').val(data.pixCopiaECola);

                        // Start Polling
                        startPolling(data.txid);
                    }

                    function startPolling(txid) {
                        if (pollingInterval) clearInterval(pollingInterval);
                        pollingInterval = setInterval(function () {
                            $.getJSON(`../inter/endpoint.php?action=verificar_pagamento_pix&txid=${txid}`, function (res) {
                                if (res.success && res.data.status === 'CONCLUIDA') {
                                    clearInterval(pollingInterval);
                                    $('#pixContent').addClass('hidden');
                                    $('#pixSuccess').removeClass('hidden');
                                }
                            });
                        }, 5000);
                    }

                    window.copiarPix = function () {
                        const copyText = document.getElementById("pixCopiaColaInput");
                        copyText.select();
                        document.execCommand("copy"); // Fallback
                        // Or Clipboard API
                        if (navigator.clipboard) navigator.clipboard.writeText(copyText.value);

                        $('#msgCopia').removeClass('hidden');
                        setTimeout(() => $('#msgCopia').addClass('hidden'), 2000);
                    }
                });
            </script>

        <?php endif; ?>
    </div>
</body>

</html>