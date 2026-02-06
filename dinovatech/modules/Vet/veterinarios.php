<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../../login.php");
    exit();
}
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../helpers/AppHelper.php';
// Vet Mode Restriction Removed for modularity (Collaborator Mode)
include "../../../database.php";

$veterinarios = [];
$link = DBConnect();
$search = "";

if ($link) {
    $search = isset($_GET['search']) ? mysqli_real_escape_string($link, $_GET['search']) : '';
    $where_clause = "";
    if ($search) {
        $where_clause = "WHERE nome LIKE '%$search%' OR crmv LIKE '%$search%'";
    }

    $query = "SELECT * FROM Veterinarios $where_clause ORDER BY nome ASC";
    $result = DBExecute($link, $query);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $veterinarios[] = $row;
        }
    }
    DBClose($link);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <title>Veterinários - DinoVet</title>
    <?php include '../../components/layout_head.php'; ?>
</head>

<body class="bg-gray-50 flex">
    <?php include '../../components/sidebar.php'; ?>

    <div class="flex-1 flex flex-col lg:ml-64 min-h-screen transition-all duration-300">
        <main class="flex-1 p-6 mt-16 lg:mt-0">

            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                <div>
                    <h2 class="text-3xl font-bold text-gray-800">
                        <?= AppHelper::isVetMode() ? 'Corpo Clínico' : 'Colaboradores' ?>
                    </h2>
                    <p class="text-gray-500">Gerencie os
                        <?= AppHelper::isVetMode() ? 'veterinários' : 'colaboradores' ?> cadastrados.
                    </p>
                </div>
                <a href="veterinario_form.php"
                    class="bg-cyan-600 hover:bg-cyan-700 text-white font-medium py-2 px-4 rounded-lg flex items-center transition-colors">
                    <span class="material-icons mr-2">add</span> Novo
                    <?= AppHelper::isVetMode() ? 'Veterinário' : 'Colaborador' ?>
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
                            placeholder="Buscar <?= AppHelper::isVetMode() ? 'Veterinário (Nome ou CRMV)' : 'Colaborador' ?>..."
                            class="w-full py-2 pl-10 pr-4 text-gray-700 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:border-cyan-500 focus:bg-white focus:ring-0">
                    </div>
                </form>
            </div>

            <!-- List -->
            <?php if (empty($veterinarios)): ?>
                <div class="bg-white p-8 rounded-xl shadow-sm border border-gray-100 text-center text-gray-500">
                    Nenhum <?= AppHelper::isVetMode() ? 'veterinário' : 'colaborador' ?> cadastrado.
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($veterinarios as $vet): ?>
                        <div
                            class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition">
                            <div class="p-6">
                                <div class="flex items-center space-x-4">
                                    <div
                                        class="h-12 w-12 rounded-full bg-cyan-100 flex items-center justify-center text-cyan-600 font-bold text-xl">
                                        <?= strtoupper(substr($vet['nome'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <h3 class="font-bold text-gray-900">
                                            <?= htmlspecialchars($vet['nome']) ?>
                                        </h3>
                                        <?php if (AppHelper::isVetMode()): ?>
                                            <p class="text-sm text-gray-500">CRMV:
                                                <?= htmlspecialchars($vet['crmv']) ?> /
                                                <?= htmlspecialchars($vet['uf_crmv']) ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="mt-4 space-y-2 text-sm text-gray-600">
                                    <?php if ($vet['telefone']): ?>
                                        <div class="flex items-center">
                                            <span class="material-icons text-gray-400 text-base mr-2">phone</span>
                                            <?= htmlspecialchars($vet['telefone']) ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($vet['email']): ?>
                                        <div class="flex items-center">
                                            <span class="material-icons text-gray-400 text-base mr-2">email</span>
                                            <?= htmlspecialchars($vet['email']) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="bg-gray-50 px-6 py-3 border-t border-gray-100 flex justify-end">
                                <a href="veterinario_form.php?id=<?= $vet['id_vet'] ?>"
                                    class="text-cyan-600 hover:text-cyan-800 font-medium text-sm">Editar</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        </main>
    </div>
</body>

</html>