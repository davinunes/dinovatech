<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../../login.php");
    exit();
}
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../helpers/AppHelper.php';
if (!AppHelper::isVetMode()) {
    header("Location: ../../dashboard.php");
    exit();
}
include "../../../database.php";

$pets = [];
$total_pages = 0;
$current_page = 1;

$link = DBConnect();
if ($link) {
    $limit = 10;
    $current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;
    if ($current_page < 1)
        $current_page = 1;
    $offset = ($current_page - 1) * $limit;

    // Search logic
    $search = isset($_GET['search']) ? mysqli_real_escape_string($link, $_GET['search']) : '';
    $where_conditions = [];
    if ($search) {
        $where_conditions[] = "(p.nome LIKE '%$search%' OR p.especie LIKE '%$search%' OR p.raca LIKE '%$search%' OR c.nome LIKE '%$search%')";
    }

    $where_clause = !empty($where_conditions) ? "WHERE " . implode(' AND ', $where_conditions) : "";

    // Count Total
    $query_total = "SELECT COUNT(p.id_pet) AS total FROM Pets p LEFT JOIN Clientes c ON p.id_cliente = c.id_cliente $where_clause";
    $result_total = DBExecute($link, $query_total);
    $total_records = $result_total ? mysqli_fetch_assoc($result_total)['total'] : 0;
    $total_pages = ceil($total_records / $limit);

    // Get Data
    $query_pets = "SELECT p.*, c.nome as nome_tutor 
                   FROM Pets p 
                   LEFT JOIN Clientes c ON p.id_cliente = c.id_cliente 
                   $where_clause 
                   ORDER BY p.nome ASC 
                   LIMIT $limit OFFSET $offset";

    $result_pets = DBExecute($link, $query_pets);
    if ($result_pets) {
        while ($row = mysqli_fetch_assoc($result_pets)) {
            $pets[] = $row;
        }
    }
    DBClose($link);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <title>Pets - DinoVet</title>
    <?php include '../../components/layout_head.php'; ?>
</head>

<body class="bg-gray-50 flex">

    <?php include '../../components/sidebar.php'; ?>

    <div class="flex-1 flex flex-col lg:ml-64 min-h-screen transition-all duration-300">
        <main class="flex-1 p-6 mt-16 lg:mt-0">

            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                <div>
                    <h2 class="text-3xl font-bold text-gray-800">Pets (Pacientes)</h2>
                    <p class="text-gray-500">Gerencie os animais cadastrados.</p>
                </div>
                <!-- Future Phase 1 Action -->
                <a href="pet_form.php"
                    class="bg-cyan-600 hover:bg-cyan-700 text-white font-medium py-2 px-4 rounded-lg flex items-center transition-colors">
                    <span class="material-icons mr-2">add</span>
                    Novo Pet
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
                            placeholder="Buscar por Nome do Pet, Tutor ou Espécie..."
                            class="w-full py-2 pl-10 pr-4 text-gray-700 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:border-cyan-500 focus:bg-white focus:ring-0">
                    </div>
                    <button type="submit"
                        class="bg-slate-800 hover:bg-slate-900 text-white px-4 py-2 rounded-lg font-medium transition-colors">Buscar</button>
                    <?php if ($search): ?>
                        <a href="pets.php"
                            class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg font-medium transition-colors flex items-center">Limpar</a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Pets List -->
            <?php if (empty($pets)): ?>
                <div class="bg-white p-8 rounded-xl shadow-sm border border-gray-100 text-center text-gray-500">
                    Nenhum pet encontrado.
                </div>
            <?php else: ?>

                <!-- Desktop Table -->
                <div class="hidden md:block bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-gray-50 text-gray-600 text-sm uppercase tracking-wider border-b border-gray-100">
                                <th class="p-4 font-medium">Nome / Espécie</th>
                                <th class="p-4 font-medium">Tutor (Proprietário)</th>
                                <th class="p-4 font-medium">Raça / Cor</th>
                                <th class="p-4 font-medium">Peso</th>
                                <th class="p-4 font-medium text-right">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-700 text-sm divide-y divide-gray-50">
                            <?php foreach ($pets as $pet): ?>
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="p-4">
                                        <div class="font-bold text-gray-900">
                                            <?= htmlspecialchars($pet['nome']) ?>
                                        </div>
                                        <div class="text-xs text-gray-500 uppercase">
                                            <?= htmlspecialchars($pet['especie']) ?>
                                        </div>
                                    </td>
                                    <td class="p-4 font-medium text-cyan-700">
                                        <a href="../../cliente_detalhes.php?id=<?= $pet['id_cliente'] ?>"
                                            class="hover:underline">
                                            <?= htmlspecialchars($pet['nome_tutor']) ?>
                                        </a>
                                    </td>
                                    <td class="p-4">
                                        <?= htmlspecialchars($pet['raca'] ?: '-') ?>
                                        <span class="text-gray-400 block text-xs">
                                            <?= htmlspecialchars($pet['sexo'] == 'M' ? 'Macho' : ($pet['sexo'] == 'F' ? 'Fêmea' : '-')) ?>
                                        </span>
                                    </td>
                                    <td class="p-4">
                                        <?= htmlspecialchars($pet['peso']) ?> kg
                                    </td>
                                    <td class="p-4 text-right">
                                        <a href="pet_detalhes.php?id=<?= $pet['id_pet'] ?>"
                                            class="text-cyan-600 hover:text-cyan-800 font-medium text-sm inline-flex items-center">
                                            Detalhes <span class="material-icons text-base ml-1">arrow_forward</span>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Cards -->
                <div class="md:hidden space-y-4">
                    <?php foreach ($pets as $pet): ?>
                        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
                            <div class="flex justify-between items-start mb-3">
                                <div>
                                    <h3 class="font-bold text-gray-900 text-lg">
                                        <?= htmlspecialchars($pet['nome']) ?>
                                    </h3>
                                    <span
                                        class="inline-block bg-gray-100 text-gray-600 text-xs px-2 py-1 rounded uppercase font-semibold">
                                        <?= htmlspecialchars($pet['especie']) ?>
                                    </span>
                                </div>
                                <a href="pet_detalhes.php?id=<?= $pet['id_pet'] ?>"
                                    class="text-cyan-600 hover:text-cyan-800 p-2">
                                    <span class="material-icons">chevron_right</span>
                                </a>
                            </div>
                            <div class="text-sm text-gray-600 space-y-1">
                                <p><span class="font-medium text-gray-500">Tutor:</span>
                                    <?= htmlspecialchars($pet['nome_tutor']) ?>
                                </p>
                                <p><span class="font-medium text-gray-500">Raça:</span>
                                    <?= htmlspecialchars($pet['raca']) ?>
                                </p>
                                <p><span class="font-medium text-gray-500">Peso:</span>
                                    <?= htmlspecialchars($pet['peso']) ?> kg
                                </p>
                            </div>
                            <div class="mt-3 pt-3 border-t border-gray-50 flex justify-end">
                                <a href="pet_detalhes.php?id=<?= $pet['id_pet'] ?>"
                                    class="w-full text-center bg-gray-50 hover:bg-gray-100 text-gray-700 py-2 rounded-lg text-sm font-medium transition-colors">
                                    Ver Prontuário
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="py-6 flex justify-center">
                        <nav class="flex gap-1">
                            <?php if ($current_page > 1): ?>
                                <a href="?page=<?= $current_page - 1 ?>&search=<?= urlencode($search) ?>"
                                    class="px-3 py-1 rounded bg-white border border-gray-200 text-gray-600 hover:bg-gray-50 shadow-sm">Anterior</a>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"
                                    class="px-3 py-1 rounded border shadow-sm <?= $i == $current_page ? 'bg-cyan-600 text-white border-cyan-600' : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50' ?>">
                                    <?= $i ?>
                                </a>
                            <?php endfor; ?>

                            <?php if ($current_page < $total_pages): ?>
                                <a href="?page=<?= $current_page + 1 ?>&search=<?= urlencode($search) ?>"
                                    class="px-3 py-1 rounded bg-white border border-gray-200 text-gray-600 hover:bg-gray-50 shadow-sm">Próximo</a>
                            <?php endif; ?>
                        </nav>
                    </div>
                <?php endif; ?>

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