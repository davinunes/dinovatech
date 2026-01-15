<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}
include "../database.php";

$clientes = [];
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
    $status_filter = $_GET['status'] ?? 'ativos'; // ativos, inativos, todos

    $where_conditions = [];
    if ($search) {
        $where_conditions[] = "(nome LIKE '%$search%' OR cpf_cnpj LIKE '%$search%')";
    }

    // Status Logic (Assuming 'ativo' column exists - defaulting to 1 if missing logic fails, but migration is needed)
    if ($status_filter === 'ativos') {
        $where_conditions[] = "ativo = 1";
    } elseif ($status_filter === 'inativos') {
        $where_conditions[] = "ativo = 0";
    }

    $where_clause = !empty($where_conditions) ? "WHERE " . implode(' AND ', $where_conditions) : "";

    $query_total = "SELECT COUNT(id_cliente) AS total FROM Clientes $where_clause";
    // ...
    $query_clientes = "SELECT id_cliente, nome, cpf_cnpj, email, telefone, ativo FROM Clientes $where_clause ORDER BY nome ASC LIMIT $limit OFFSET $offset";
    // ...

    // UI Updates below ...
    // Filter Buttons (Inside Search Bar Div or beside it)
    /*
    <div class="flex gap-2 mb-2">
        <a href="?status=ativos" class="...">Ativos</a> ...
    </div>
    */
    // Since I'm replacing chunks, I'll do multiple edits or one big one if contiguous?
    // The search logic is at top. The UI is further down.
    // I will use replace_file_content for logic first, then UI.
    // Actually, I can replace the whole PHP block at the top first.
    $result_clientes = DBExecute($link, $query_clientes);
    if ($result_clientes) {
        while ($row = mysqli_fetch_assoc($result_clientes)) {
            $clientes[] = $row;
        }
    }
    DBClose($link);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <title>Clientes - Dinovatech</title>
    <?php include 'components/layout_head.php'; ?>
</head>

<body class="bg-gray-50 flex">

    <?php include 'components/sidebar.php'; ?>

    <div class="flex-1 flex flex-col lg:ml-64 min-h-screen transition-all duration-300">
        <main class="flex-1 p-6 mt-16 lg:mt-0">

            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                <div>
                    <h2 class="text-3xl font-bold text-gray-800">Clientes</h2>
                    <p class="text-gray-500">Gerencie sua base de clientes.</p>
                </div>
                <a href="cliente_form.php"
                    class="bg-cyan-600 hover:bg-cyan-700 text-white font-medium py-2 px-4 rounded-lg flex items-center transition-colors">
                    <span class="material-icons mr-2">add</span>
                    Novo Cliente
                </a>
            </div>

            <!-- Status Filter -->
            <div class="flex gap-2 mb-4">
                <a href="?status=ativos&search=<?= urlencode($search) ?>"
                    class="px-4 py-2 rounded-lg text-sm font-medium transition-colors <?= $status_filter === 'ativos' ? 'bg-cyan-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50 border border-gray-200' ?>">
                    Ativos
                </a>
                <a href="?status=inativos&search=<?= urlencode($search) ?>"
                    class="px-4 py-2 rounded-lg text-sm font-medium transition-colors <?= $status_filter === 'inativos' ? 'bg-cyan-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50 border border-gray-200' ?>">
                    Inativos
                </a>
                <a href="?status=todos&search=<?= urlencode($search) ?>"
                    class="px-4 py-2 rounded-lg text-sm font-medium transition-colors <?= $status_filter === 'todos' ? 'bg-cyan-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50 border border-gray-200' ?>">
                    Todos
                </a>
            </div>

            <!-- Search Bar -->
            <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 mb-6">
                <form method="GET" class="flex gap-2">
                    <input type="hidden" name="status" value="<?= htmlspecialchars($status_filter) ?>">
                    <div class="relative flex-1">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                            <span class="material-icons">search</span>
                        </span>
                        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                            placeholder="Buscar por Nome ou CPF/CNPJ..."
                            class="w-full py-2 pl-10 pr-4 text-gray-700 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:border-cyan-500 focus:bg-white focus:ring-0">
                    </div>
                    <button type="submit"
                        class="bg-slate-800 hover:bg-slate-900 text-white px-4 py-2 rounded-lg font-medium transition-colors">Buscar</button>
                    <?php if ($search): ?>
                        <a href="clientes.php"
                            class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg font-medium transition-colors flex items-center">Limpar</a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Clientes List -->
            <?php if (empty($clientes)): ?>
                <div class="bg-white p-8 rounded-xl shadow-sm border border-gray-100 text-center text-gray-500">
                    Nenhum cliente encontrado.
                </div>
            <?php else: ?>

                <!-- Desktop Table -->
                <div class="hidden md:block bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-gray-50 text-gray-600 text-sm uppercase tracking-wider border-b border-gray-100">
                                <th class="p-4 font-medium">Nome</th>
                                <th class="p-4 font-medium">CPF/CNPJ</th>
                                <th class="p-4 font-medium">Email</th>
                                <th class="p-4 font-medium">Telefone</th>
                                <th class="p-4 font-medium text-right">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-700 text-sm divide-y divide-gray-50">
                            <?php foreach ($clientes as $cliente): ?>
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="p-4 font-medium text-gray-900">
                                        <?= htmlspecialchars($cliente['nome']) ?>
                                        <?php if (isset($cliente['ativo']) && $cliente['ativo'] == 0): ?>
                                            <span
                                                class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">Inativo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="p-4"><?= htmlspecialchars($cliente['cpf_cnpj']) ?></td>
                                    <td class="p-4"><?= htmlspecialchars($cliente['email']) ?></td>
                                    <td class="p-4"><?= htmlspecialchars($cliente['telefone']) ?></td>
                                    <td class="p-4 text-right">
                                        <a href="cliente_detalhes.php?id=<?= $cliente['id_cliente'] ?>"
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
                    <?php foreach ($clientes as $cliente): ?>
                        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
                            <div class="flex justify-between items-start mb-3">
                                <div>
                                    <h3 class="font-bold text-gray-900 flex items-center gap-2">
                                        <?= htmlspecialchars($cliente['nome']) ?>
                                        <?php if (isset($cliente['ativo']) && $cliente['ativo'] == 0): ?>
                                            <span
                                                class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">Inativo</span>
                                        <?php endif; ?>
                                    </h3>
                                    <p class="text-sm text-gray-500"><?= htmlspecialchars($cliente['cpf_cnpj']) ?></p>
                                </div>
                                <a href="cliente_detalhes.php?id=<?= $cliente['id_cliente'] ?>"
                                    class="text-cyan-600 hover:text-cyan-800 p-2">
                                    <span class="material-icons">chevron_right</span>
                                </a>
                            </div>
                            <div class="text-sm text-gray-600 space-y-1">
                                <?php if ($cliente['telefone']): ?>
                                    <div class="flex items-center">
                                        <span class="material-icons text-gray-400 text-sm mr-2">phone</span>
                                        <?= htmlspecialchars($cliente['telefone']) ?>
                                    </div>
                                <?php endif; ?>
                                <?php if ($cliente['email']): ?>
                                    <div class="flex items-center">
                                        <span class="material-icons text-gray-400 text-sm mr-2">email</span>
                                        <span class="truncate"><?= htmlspecialchars($cliente['email']) ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="mt-4 pt-3 border-t border-gray-50 flex justify-end">
                                <a href="cliente_detalhes.php?id=<?= $cliente['id_cliente'] ?>"
                                    class="w-full text-center bg-gray-50 hover:bg-gray-100 text-gray-700 py-2 rounded-lg text-sm font-medium transition-colors">
                                    Ver Detalhes
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination (Common) -->
                <?php if ($total_pages > 1): ?>
                    <div class="py-6 flex justify-center">
                        <nav class="flex gap-1">
                            <?php if ($current_page > 1): ?>
                                <a href="?page=<?= $current_page - 1 ?>&search=<?= urlencode($search) ?>"
                                    class="px-3 py-1 rounded bg-white border border-gray-200 text-gray-600 hover:bg-gray-50 shadow-sm">Anterior</a>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"
                                    class="px-3 py-1 rounded border shadow-sm <?= $i == $current_page ? 'bg-cyan-600 text-white border-cyan-600' : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50' ?>"><?= $i ?></a>
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

    <?php include 'components/layout_scripts.php'; ?>
</body>

</html>