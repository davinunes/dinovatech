<?php

include "../database.php"; // Certifique-se de que o caminho para 'database.php' está correto

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cadastrar_cliente'])) {
    $nome = $_POST['nome'] ?? '';
    $cpf_cnpj = $_POST['cpf_cnpj'] ?? '';
    $telefone = $_POST['telefone'] ?? '';
    $email = $_POST['email'] ?? '';

    // Validação básica dos dados (você pode adicionar validações mais robustas)
    if (empty($nome) || empty($cpf_cnpj)) {
        $mensagem = "<p style='color: red;'>Nome e CPF/CNPJ são campos obrigatórios!</p>";
    } else {
        // Proteção contra SQL Injection básica (ideal é usar prepared statements)
        $link = DBConnect(); // Abre a conexão para escapar as strings
        $nome = mysqli_real_escape_string($link, $nome);
        $cpf_cnpj = mysqli_real_escape_string($link, $cpf_cnpj);
        $telefone = mysqli_real_escape_string($link, $telefone);
        $email = mysqli_real_escape_string($link, $email);
        DBClose($link); // Fecha a conexão

        $query = "INSERT INTO Clientes (nome, cpf_cnpj, telefone, email) 
                  VALUES ('$nome', '$cpf_cnpj', '$telefone', '$email')";

        $result = DBExecute($query);

        if ($result) {
            $mensagem = "<p style='color: green;'>Cliente cadastrado com sucesso!</p>";
        } else {
            $mensagem = "<p style='color: red;'>Erro ao cadastrar cliente. Verifique se o CPF/CNPJ ou Email já existem.</p>";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Clientes</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ccc; border-radius: 8px; box-shadow: 2px 2px 10px rgba(0,0,0,0.1); }
        h2 { text-align: center; color: #333; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"], input[type="email"] {
            width: calc(100% - 22px);
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        input[type="submit"] {
            background-color: #4CAF50;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
        }
        input[type="submit"]:hover {
            background-color: #45a049;
        }
        .mensagem { text-align: center; margin-top: 15px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Cadastro de Clientes</h2>
        <?php if (isset($mensagem)) { echo "<div class='mensagem'>" . $mensagem . "</div>"; } ?>
        <form action="" method="POST">
            <label for="nome">Nome:</label>
            <input type="text" id="nome" name="nome" required>

            <label for="cpf_cnpj">CPF/CNPJ:</label>
            <input type="text" id="cpf_cnpj" name="cpf_cnpj" required>

            <label for="telefone">Telefone:</label>
            <input type="text" id="telefone" name="telefone">

            <label for="email">E-mail:</label>
            <input type="email" id="email" name="email">

            <input type="submit" name="cadastrar_cliente" value="Cadastrar Cliente">
        </form>
    </div>
</body>
</html>