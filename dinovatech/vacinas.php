<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}
include "../database.php";

$vacinas = [];
$link = DBConnect();
$search = "";

if ($link) {
    $search = isset($_GET['search']) ? mysqli_real_escape_string($link, $_GET['search']) : '';
    $where_clause = "";
    if ($search) {
        $where_clause = "WHERE nome LIKE '%$search%' OR descricao LIKE '%$search%'";
    }

    $query = "SELECT * FROM Vacinas $where_clause ORDER BY nome ASC";
    $result = DBExecute($link, $query);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $vacinas[] = $row;
        }
    }
    DBClose($link);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <title>Vacinas - DinoVet</title>
    <?php include 'components/layout_head.php'; ?>
</head>

<body class="bg-gray-50 flex">

    <?php include 'components/sidebar.php'; ?>

    <div class="flex-1 flex flex-col lg:ml-64 min-h-screen transition-all duration-300">
        <main class="flex-1 p-6 mt-16 lg:mt-0">

            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                <div>
                    <h2 class="text-3xl font-bold text-gray-800">Catálogo de Vacinas</h2>
                    <p class="text-gray-500">Gerencie os tipos de vacinas disponíveis.</p>
                </div>
                <a href="vacina_form.php"
                    class="bg-cyan-600 hover:bg-cyan-700 text-white font-medium py-2 px-4 rounded-lg flex items-center transition-colors">
                    <span class="material-icons mr-2">add</span> Nova Vacina
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
                            placeholder="Buscar Vacina..."
                            class="w-full py-2 pl-10 pr-4 text-gray-700 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:border-cyan-500 focus:bg-white focus:ring-0">
                    </div>
                    <button type="submit"
                        class="bg-slate-800 hover:bg-slate-900 text-white px-4 py-2 rounded-lg font-medium transition-colors">Buscar</button>
                </form>
            </div>

            <!-- Vaccines List -->
            <?php if (empty($vacinas)): ?>
                <div class="bg-white p-8 rounded-xl shadow-sm border border-gray-100 text-center text-gray-500">
                    Nenhuma vacina cadastrada.
                </div>
            <?php else: ?>

                <!-- Desktop Table -->
                <div class="hidden md:block bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-gray-50 text-gray-600 text-sm uppercase tracking-wider border-b border-gray-100">
                                <th class="p-4 font-medium">Nome da Vacina</th>
                                <th class="p-4 font-medium">Descrição</th>
                                <th class="p-4 font-medium">Recorrência (Dias)</th>
                                <th class="p-4 font-medium text-right">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-700 text-sm divide-y divide-gray-50">
                            <?php foreach ($vacinas as $vacina): ?>
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="p-4 font-bold text-gray-900">
                                        <?= htmlspecialchars($vacina['nome']) ?>
                                    </td>
                                    <td class="p-4 text-gray-600">
                                        <?= htmlspecialchars($vacina['descricao'] ?: '-') ?>
                                    </td>
                                    <td class="p-4">
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            <?= $vacina['recorrencia_dias'] ?> dias
                                        </span>
                                    </td>
                                    <td class="p-4 text-right">
                                        <a href="vacina_form.php?id=<?= $vacina['id_vacina'] ?>"
                                            class="text-cyan-600 hover:text-cyan-800 font-medium text-sm inline-flex items-center">
                                            <span class="material-icons text-base mr-1">edit</span> Editar
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Cards -->
                <div class="md:hidden space-y-4">
                    <?php foreach ($vacinas as $vacina): ?>
                        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
                            <div class="flex justify-between items-start mb-2">
                                <h3 class="font-bold text-gray-900">
                                    <?= htmlspecialchars($vacina['nome']) ?>
                                </h3>
                                <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full font-medium">
                                    <?= $vacina['recorrencia_dias'] ?> dias
                                </span>
                            </div>
                            <p class="text-gray-600 text-sm mb-4">
                                <?= htmlspecialchars($vacina['descricao'] ?: 'Sem descrição') ?>
                            </p>
                            <div class="pt-3 border-t border-gray-50 flex justify-end">
                                <a href="vacina_form.php?id=<?= $vacina['id_vacina'] ?>"
                                    class="text-cyan-600 hover:text-cyan-800 font-medium text-sm flex items-center">
                                    <span class="material-icons text-base mr-1">edit</span> Editar
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

            <?php endif; ?>

        </main>
    </div>

    <!-- Mobile Overlay -->
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebar-overlay');
            sidebar.classList.toggle('-translate-x-full');
            overlay.classList.toggle('hidden');
        }
    </script>
</body>

</html>