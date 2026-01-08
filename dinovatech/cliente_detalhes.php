<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}
include "../database.php";
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
    }
    DBClose($link);
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
                        <button onclick="openNovaFaturaModal()"
                            class="bg-cyan-600 text-white hover:bg-cyan-700 px-4 py-2 rounded-lg font-medium transition shadow-sm">Nova
                            Fatura</button>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">

                    <!-- Faturas Section -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="p-6 border-b border-gray-100 flex justify-between items-center">
                            <h3 class="text-lg font-bold text-gray-800">Faturas</h3>
                        </div>
                        <div class="overflow-x-auto max-h-[500px] overflow-y-auto">
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
                                            $statusClass = match ($fatura['status']) {
                                                'Liquidada' => 'text-green-600 bg-green-100',
                                                'Em Aberto' => (strtotime($fatura['data_vencimento']) < time()) ? 'text-red-600 bg-red-100' : 'text-yellow-600 bg-yellow-100',
                                                default => 'text-gray-600 bg-gray-100'
                                            };
                                            $statusLabel = ($fatura['status'] == 'Em Aberto' && strtotime($fatura['data_vencimento']) < time()) ? 'Atrasada' : $fatura['status'];
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
                        <div class="overflow-x-auto max-h-[500px] overflow-y-auto">
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
                                        <?php foreach ($contratos as $contrato): ?>
                                            <tr class="border-b border-gray-50 hover:bg-gray-50">
                                                <td class="p-4 font-medium">
                                                    <?= htmlspecialchars($contrato['nome_servico']) ?>
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
                    </div>

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
                    <input type="date" name="data_vencimento" required class="w-full p-2 border rounded-lg">
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

    <?php include 'components/layout_scripts.php'; ?>
    <script>
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

</html>