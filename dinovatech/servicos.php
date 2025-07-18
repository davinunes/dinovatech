<?php

include "../database.php"; // Certifique-se de que o caminho para 'database.php' está correto

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cadastrar_servico'])) {
    $nome_servico = $_POST['nome_servico'] ?? '';
    $valor_sugerido = $_POST['valor_sugerido'] ?? '';

    // Validação básica
    if (empty($nome_servico) || !is_numeric($valor_sugerido)) {
        $mensagem = "<p style='color: red;'>Nome do serviço e valor sugerido válidos são obrigatórios!</p>";
    } else {
        // Proteção contra SQL Injection
        $link = DBConnect();
        $nome_servico = mysqli_real_escape_string($link, $nome_servico);
        $valor_sugerido = mysqli_real_escape_string($link, $valor_sugerido);
        DBClose($link);

        $query = "INSERT INTO Servicos (nome_servico, valor_sugerido) 
                  VALUES ('$nome_servico', '$valor_sugerido')";

        $result = DBExecute($query);

        if ($result) {
            $mensagem = "<p style='color: green;'>Serviço cadastrado com sucesso!</p>";
        } else {
            $mensagem = "<p style='color: red;'>Erro ao cadastrar serviço.</p>";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Serviços</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ccc; border-radius: 8px; box-shadow: 2px 2px 10px rgba(0,0,0,0.1); }
        h2 { text-align: center; color: #333; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"], input[type="number"] {
            width: calc(100% - 22px);
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        input[type="submit"] {
            background-color: #007bff;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
        }
        input[type="submit"]:hover {
            background-color: #0056b3;
        }
        .mensagem { text-align: center; margin-top: 15px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Cadastro de Serviços</h2>
        <?php if (isset($mensagem)) { echo "<div class='mensagem'>" . $mensagem . "</div>"; } ?>
        <form action="" method="POST">
            <label for="nome_servico">Nome do Serviço:</label>
            <input type="text" id="nome_servico" name="nome_servico" required>

            <label for="valor_sugerido">Valor Sugerido:</label>
            <input type="number" id="valor_sugerido" name="valor_sugerido" step="0.01" min="0" required>

            <input type="submit" name="cadastrar_servico" value="Cadastrar Serviço">
        </form>
    </div>
</body>
</html>