<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}
include "../database.php";

$link = DBConnect();
if (!$link) die("Erro de conexão");

$id_vet = $_GET['id'] ?? null;
$vet = null;
$is_edit = false;
$erro = "";

if ($id_vet) {
    $id_safe = mysqli_real_escape_string($link, $id_vet);
    $q = "SELECT * FROM Veterinarios WHERE id_vet = '$id_safe'";
    $r = DBExecute($link, $q);
    if ($r && mysqli_num_rows($r) > 0) {
        $vet = mysqli_fetch_assoc($r);
        $is_edit = true;
    }
}

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'] ?? '';
    $crmv = $_POST['crmv'] ?? '';
    $uf_crmv = $_POST['uf_crmv'] ?? '';
    $telefone = $_POST['telefone'] ?? '';
    $email = $_POST['email'] ?? '';

    if (empty($nome) || empty($crmv) || empty($uf_crmv)) {
        $erro = "Nome, CRMV e UF são obrigatórios.";
    } else {
        $nome = mysqli_real_escape_string($link, $nome);
        $crmv = mysqli_real_escape_string($link, $crmv);
        $uf_crmv = mysqli_real_escape_string($link, $uf_crmv);
        $telefone = mysqli_real_escape_string($link, $telefone);
        $email = mysqli_real_escape_string($link, $email);

        if ($is_edit) {
            $query = "UPDATE Veterinarios SET nome='$nome', crmv='$crmv', uf_crmv='$uf_crmv', telefone='$telefone', email='$email' WHERE id_vet = " . (int)$id_vet;
        } else {
            $query = "INSERT INTO Veterinarios (nome, crmv, uf_crmv, telefone, email) VALUES ('$nome', '$crmv', '$uf_crmv', '$telefone', '$email')";
        }

        if (DBExecute($link, $query)) {
            header("Location: veterinarios.php");
            exit();
        } else {
            $erro = "Erro ao salvar: " . mysqli_error($link);
        }
    }
}
DBClose($link);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <title><?= $is_edit ? "Editar" : "Novo" ?> Veterinário - DinoVet</title>
    <?php include 'components/layout_head.php'; ?>
</head>
<body class="bg-gray-50 flex">
    <?php include 'components/sidebar.php'; ?>

    <div class="flex-1 flex flex-col lg:ml-64 min-h-screen transition-all duration-300">
        <main class="flex-1 p-6 mt-16 lg:mt-0">
            <div class="max-w-xl mx-auto">
                <a href="veterinarios.php" class="text-gray-500 hover:text-gray-700 mb-4 inline-flex items-center">
                    <span class="material-icons mr-1">arrow_back</span> Voltar
                </a>

                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-6 border-b border-gray-100">
                        <h2 class="text-2xl font-bold text-gray-800"><?= $is_edit ? "Editar" : "Novo" ?> Veterinário</h2>
                    </div>

                    <?php if ($erro): ?>
                        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 m-6"><?= $erro ?></div>
                    <?php endif; ?>

                    <form method="POST" class="p-6 space-y-6">
                        <div>
                            <label class="block text-gray-700 font-medium mb-1">Nome Completo *</label>
                            <input type="text" name="nome" value="<?= htmlspecialchars($vet['nome'] ?? '') ?>" required class="w-full border-gray-300 rounded-lg p-3 border">
                        </div>

                        <div class="grid grid-cols-3 gap-4">
                            <div class="col-span-2">
                                <label class="block text-gray-700 font-medium mb-1">CRMV *</label>
                                <input type="text" name="crmv" value="<?= htmlspecialchars($vet['crmv'] ?? '') ?>" required class="w-full border-gray-300 rounded-lg p-3 border">
                            </div>
                            <div>
                                <label class="block text-gray-700 font-medium mb-1">UF *</label>
                                <input type="text" name="uf_crmv" value="<?= htmlspecialchars($vet['uf_crmv'] ?? 'SP') ?>" maxlength="2" required class="w-full border-gray-300 rounded-lg p-3 border uppercase">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-gray-700 font-medium mb-1">Telefone</label>
                                <input type="text" name="telefone" value="<?= htmlspecialchars($vet['telefone'] ?? '') ?>" class="w-full border-gray-300 rounded-lg p-3 border">
                            </div>
                            <div>
                                <label class="block text-gray-700 font-medium mb-1">Email</label>
                                <input type="email" name="email" value="<?= htmlspecialchars($vet['email'] ?? '') ?>" class="w-full border-gray-300 rounded-lg p-3 border">
                            </div>
                        </div>

                        <div class="flex justify-end gap-3 pt-4">
                            <a href="veterinarios.php" class="bg-gray-100 text-gray-700 py-2 px-6 rounded-lg font-medium">Cancelar</a>
                            <button type="submit" class="bg-cyan-600 text-white py-2 px-6 rounded-lg font-medium shadow hover:bg-cyan-700 transition">Salvar</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>
</html>