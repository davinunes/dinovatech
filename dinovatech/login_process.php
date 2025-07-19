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

        DBClose($link);
        header('Location: index.php'); // Redireciona para o painel principal
        exit();
    }
}

// Se chegou até aqui, o login falhou
DBClose($link);
header('Location: login.php?error=Email ou senha inválidos.');
exit();
