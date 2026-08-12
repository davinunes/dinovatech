<?php
session_start();
require_once '../database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit();
}

$email = $_POST['email'] ?? '';
$senha = $_POST['senha'] ?? '';

if (empty($email) || empty($senha)) {
    header('Location: login.php?error=Email e senha são obrigatórios.');
    exit();
}

$link = DBConnect();
if (!$link) {
    header('Location: login.php?error=Erro de conexão com o banco de dados.');
    exit();
}

// Busca o usuário pelo email
$email_safe = mysqli_real_escape_string($link, $email);
$query = "SELECT id_usuario, nome, email, senha, nivel_acesso FROM Usuarios WHERE email = '{$email_safe}' LIMIT 1";
$result = DBExecute($link, $query);

if ($result && mysqli_num_rows($result) === 1) {
    $usuario = mysqli_fetch_assoc($result);

    // Verifica a senha usando password_verify()
    if (password_verify($senha, $usuario['senha'])) {
        // Senha correta, inicia a sessão
        $_SESSION['usuario_id'] = $usuario['id_usuario'];
        $_SESSION['usuario_nome'] = $usuario['nome'];
        $_SESSION['usuario_email'] = $usuario['email'];
        $_SESSION['nivel_acesso'] = $usuario['nivel_acesso'];

        // Se o usuário selecionou permanecer logado
        if (!empty($_POST['permanecer_logado'])) {
            $params = session_get_cookie_params();
            setcookie(session_name(), session_id(), time() + 2592000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);

            require_once __DIR__ . '/config.php';
            $masterKey = defined('APP_MASTER_KEY') && !empty(APP_MASTER_KEY) ? APP_MASTER_KEY : 'dinovatech_secret_key';
            $tokenHash = hash_hmac('sha256', $usuario['id_usuario'] . $usuario['email'], $masterKey);
            $cookieValue = $usuario['id_usuario'] . ':' . $tokenHash;
            setcookie('dinovatech_remember', $cookieValue, time() + 2592000, '/', '', false, true);
        } else {
            setcookie('dinovatech_remember', '', time() - 3600, '/');
        }

        DBClose($link);
        header('Location: clientes.php'); // Redireciona para o painel principal (Clientes)
        exit();
    }
}

// Se chegou até aqui, o login falhou
DBClose($link);
header('Location: login.php?error=Email ou senha inválidos.');
exit();
