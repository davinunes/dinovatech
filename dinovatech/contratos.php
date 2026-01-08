<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}
include "../database.php";

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
                <a href="contrato_form.php"
                    class="bg-cyan-600 hover:bg-cyan-700 text-white font-medium py-2 px-4 rounded-lg flex items-center transition-colors">
                    <span class="material-icons mr-2">add</span>
                    Novo Contrato
                </a>
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

            <!-- List -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 text-gray-600 text-sm uppercase tracking-wider">
                                <th class="p-4 font-medium">Cliente</th>
                                <th class="p-4 font-medium">Serviço</th>
                                <th class="p-4 font-medium">Valor</th>
                                <th class="p-4 font-medium">Período</th>
                                <th class="p-4 font-medium">Início</th>
                                <th class="p-4 font-medium text-right">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-700 text-sm">
                            <?php if (!empty($contratos)): ?>
                                <?php foreach ($contratos as $contrato): ?>
                                    <tr class="border-b border-gray-50 hover:bg-gray-50 transition">
                                        <td class="p-4 font-medium text-gray-900">
                                            <?= htmlspecialchars($contrato['nome_cliente']) ?>
                                        </td>
                                        <td class="p-4">
                                            <?= htmlspecialchars($contrato['nome_servico']) ?>
                                        </td>
                                        <td class="p-4">R$
                                            <?= number_format($contrato['valor_sugerido_recorrencia'], 2, ',', '.') ?>
                                        </td>
                                        <td class="p-4 capitalize">
                                            <?= $contrato['tipo_periodo'] ?>
                                        </td>
                                        <td class="p-4">
                                            <?= date('d/m/Y', strtotime($contrato['data_inicio_cobranca'])) ?>
                                        </td>
                                        <td class="p-4 text-right">
                                            <a href="contrato_form.php?id=<?= $contrato['id_recorrencia'] ?>"
                                                class="text-gray-500 hover:text-gray-700 font-medium text-sm flex items-center justify-end">
                                                <span class="material-icons text-base mr-1">edit</span> Editar
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="p-8 text-center text-gray-500">Nenhum contrato encontrado.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

    <?php include 'components/layout_scripts.php'; ?>
</body>

</html>