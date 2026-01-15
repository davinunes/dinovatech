<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}
include "../database.php";

$servicos = [];
$link = DBConnect();
$search = "";
if ($link) {
    $search = isset($_GET['search']) ? mysqli_real_escape_string($link, $_GET['search']) : '';
    $where_clause = "";
    if ($search) {
        $where_clause = "WHERE nome_servico LIKE '%$search%'";
    }

    $query = "SELECT * FROM Servicos $where_clause ORDER BY nome_servico ASC";
    $result = DBExecute($link, $query);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $servicos[] = $row;
        }
    }
    DBClose($link);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <title>Serviços - Dinovatech</title>
    <?php include 'components/layout_head.php'; ?>
</head>

<body class="bg-gray-50 flex">

    <?php include 'components/sidebar.php'; ?>

    <div class="flex-1 flex flex-col lg:ml-64 min-h-screen transition-all duration-300">
        <main class="flex-1 p-6 mt-16 lg:mt-0">

            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                <div>
                    <h2 class="text-3xl font-bold text-gray-800">Serviços</h2>
                    <p class="text-gray-500">Gerencie os serviços oferecidos.</p>
                </div>
                <a href="servico_form.php"
                    class="bg-cyan-600 hover:bg-cyan-700 text-white font-medium py-2 px-4 rounded-lg flex items-center transition-colors">
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
                </form>
            </div>

            <!-- Services List -->
            <!-- Desktop Table -->
            <div class="hidden md:block bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 text-gray-600 text-sm uppercase tracking-wider">
                                <th class="p-4 font-medium">Nome do Serviço</th>
                                <th class="p-4 font-medium">Valor Sugerido</th>
                                <th class="p-4 font-medium text-right">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-700 text-sm">
                            <?php if (!empty($servicos)): ?>
                                <?php foreach ($servicos as $servico): ?>
                                    <tr class="border-b border-gray-50 hover:bg-gray-50 transition">
                                        <td class="p-4 font-medium text-gray-900">
                                            <?= htmlspecialchars($servico['nome_servico']) ?></td>
                                        <td class="p-4">R$ <?= number_format($servico['valor_sugerido'], 2, ',', '.') ?></td>
                                        <td class="p-4 text-right">
                                            <a href="servico_form.php?id=<?= $servico['id_servico'] ?>"
                                                class="text-gray-500 hover:text-gray-700 font-medium text-sm flex items-center justify-end">
                                                <span class="material-icons text-base mr-1">edit</span> Editar
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="p-8 text-center text-gray-500">Nenhum serviço encontrado.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Mobile Cards -->
            <div class="md:hidden space-y-4">
                <?php if (!empty($servicos)): ?>
                    <?php foreach ($servicos as $servico): ?>
                        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
                            <div class="flex justify-between items-start mb-2">
                                <h3 class="font-bold text-gray-900"><?= htmlspecialchars($servico['nome_servico']) ?></h3>
                            </div>
                            <div class="text-gray-600 mb-4">
                                <span class="text-xs uppercase tracking-wide text-gray-400 block mb-1">Valor Sugerido</span>
                                <span class="text-lg font-medium text-gray-800">R$
                                    <?= number_format($servico['valor_sugerido'], 2, ',', '.') ?></span>
                            </div>
                            <div class="pt-3 border-t border-gray-50 flex justify-end">
                                <a href="servico_form.php?id=<?= $servico['id_servico'] ?>"
                                    class="w-full text-center bg-gray-50 hover:bg-gray-100 text-gray-700 py-2 rounded-lg text-sm font-medium transition-colors">
                                    Editar Serviço
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="bg-white p-8 rounded-xl shadow-sm border border-gray-100 text-center text-gray-500">
                        Nenhum serviço encontrado.
                    </div>
                <?php endif; ?>
            </div>

        </main>
    </div>

    <?php include 'components/layout_scripts.php'; ?>
</body>

</html>