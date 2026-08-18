<?php
session_start();
require_once '../database.php';
require_once __DIR__ . '/config.php';

// Auto-login via cookie de permanência se não houver sessão ativa
if (!isset($_SESSION['usuario_id']) && !empty($_COOKIE['dinovatech_remember'])) {
    $parts = explode(':', $_COOKIE['dinovatech_remember'], 2);
    if (count($parts) === 2) {
        $userId = (int) $parts[0];
        $tokenHash = $parts[1];
        $link = DBConnect();
        if ($link) {
            $userIdSafe = mysqli_real_escape_string($link, $userId);
            $res = DBExecute($link, "SELECT id_usuario, nome, email, nivel_acesso FROM Usuarios WHERE id_usuario = '$userIdSafe' LIMIT 1");
            if ($res && mysqli_num_rows($res) === 1) {
                $user = mysqli_fetch_assoc($res);
                $masterKey = defined('APP_MASTER_KEY') && !empty(APP_MASTER_KEY) ? APP_MASTER_KEY : 'dinovatech_secret_key';
                $expectedHash = hash_hmac('sha256', $user['id_usuario'] . $user['email'], $masterKey);
                if (hash_equals($expectedHash, $tokenHash)) {
                    $_SESSION['usuario_id'] = $user['id_usuario'];
                    $_SESSION['usuario_nome'] = $user['nome'];
                    $_SESSION['usuario_email'] = $user['email'];
                    $_SESSION['nivel_acesso'] = $user['nivel_acesso'];
                }
            }
            DBClose($link);
        }
    }
}

// Se o usuário já estiver logado, redireciona para o painel
if (isset($_SESSION['usuario_id'])) {
    header("Location: dashboard.php");
    exit();
}

// Fetch Company Name from DB
$link = DBConnect();
$empresa_nome = "DinoVet"; // Fallback
if ($link) {
    $q = "SELECT nome_fantasia, razao_social FROM ConfiguracoesEmissor LIMIT 1";
    $r = DBExecute($link, $q);
    if ($r && mysqli_num_rows($r) > 0) {
        $row = mysqli_fetch_assoc($r);
        $empresa_nome = !empty($row['nome_fantasia']) ? $row['nome_fantasia'] : (!empty($row['razao_social']) ? $row['razao_social'] : "DinoVet");
    }
    DBClose($link);
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DinoVet - Acesso Restrito</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>

<body class="bg-gray-100 flex items-center justify-center h-screen">
    <div class="w-full max-w-md">
        <form action="login_process.php" method="POST" class="bg-white shadow-md rounded-xl px-8 pt-6 pb-8 mb-4">
            <div class="mb-6 text-center">
                <h1 class="text-2xl font-bold text-gray-800"><span
                        class="text-cyan-600"><?= htmlspecialchars($empresa_nome) ?></span> - Acesso
                    Administrativo</h1>
            </div>

            <?php if (isset($_GET['error'])): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative mb-4" role="alert">
                    <span class="block sm:inline"><?= htmlspecialchars($_GET['error']) ?></span>
                </div>
            <?php endif; ?>

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="email">
                    E-mail
                </label>
                <input
                    class="shadow-sm appearance-none border rounded-lg w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-cyan-500"
                    id="email" name="email" type="email" placeholder="seuemail@exemplo.com" required>
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="password">
                    Senha
                </label>
                <input
                    class="shadow-sm appearance-none border rounded-lg w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-cyan-500"
                    id="password" name="senha" type="password" placeholder="******************" required>
            </div>
            <div class="mb-6 flex items-center justify-between">
                <label class="flex items-center text-sm text-gray-600 cursor-pointer select-none">
                    <input type="checkbox" name="permanecer_logado" value="1"
                        class="rounded border-gray-300 text-cyan-600 focus:ring-cyan-500 mr-2 h-4 w-4">
                    Permanecer logado
                </label>
            </div>
            <div class="flex items-center justify-between">
                <button
                    class="w-full bg-cyan-600 hover:bg-cyan-700 text-white font-bold py-3 px-4 rounded-lg focus:outline-none focus:shadow-outline"
                    type="submit">
                    Entrar
                </button>
            </div>
            <div class="mt-6 text-center">
                <a href="../" class="text-sm text-gray-500 hover:text-gray-700 hover:underline">
                    &larr; Voltar à página inicial
                </a>
            </div>
        </form>
    </div>
</body>

</html>