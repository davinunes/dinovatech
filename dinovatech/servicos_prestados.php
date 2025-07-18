<?php

include "../database.php"; // Verifique o caminho para 'database.php'

$clientes = [];
$servicos = [];
$mensagem = '';

// 1. Carrega Clientes para o Dropdown
$query_clientes = "SELECT id_cliente, nome FROM Clientes ORDER BY nome ASC";
$result_clientes = DBExecute($query_clientes);
if ($result_clientes) {
    while ($row = mysqli_fetch_assoc($result_clientes)) {
        $clientes[] = $row;
    }
} else {
    $mensagem = "<p style='color: red;'>Erro ao carregar clientes.</p>";
}

// 2. Carrega Serviços para o Dropdown
$query_servicos = "SELECT id_servico, nome_servico, valor_sugerido FROM Servicos ORDER BY nome_servico ASC";
$result_servicos = DBExecute($query_servicos);
if ($result_servicos) {
    while ($row = mysqli_fetch_assoc($result_servicos)) {
        $servicos[] = $row;
    }
} else {
    $mensagem .= "<p style='color: red;'>Erro ao carregar serviços.</p>";
}

// 3. Processa o Formulário de Cadastro de Serviço Prestado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cadastrar_servico_prestado'])) {
    $id_cliente = $_POST['id_cliente'] ?? '';
    $id_servico = $_POST['id_servico'] ?? '';
    $quantidade = $_POST['quantidade'] ?? '';
    $valor_final = $_POST['valor_final'] ?? '';
    $data_vencimento = $_POST['data_vencimento'] ?? '';

    // Validação básica dos dados
    if (empty($id_cliente) || empty($id_servico) || !is_numeric($quantidade) || $quantidade <= 0 || !is_numeric($valor_final) || $valor_final <= 0 || empty($data_vencimento)) {
        $mensagem = "<p style='color: red;'>Por favor, preencha todos os campos corretamente (quantidade e valor devem ser maiores que zero).</p>";
    } else {
        // Proteção contra SQL Injection
        $link = DBConnect();
        $id_cliente = mysqli_real_escape_string($link, $id_cliente);
        $id_servico = mysqli_real_escape_string($link, $id_servico);
        $quantidade = mysqli_real_escape_string($link, $quantidade);
        $valor_final = mysqli_real_escape_string($link, $valor_final);
        $data_vencimento = mysqli_real_escape_string($link, $data_vencimento);
        DBClose($link);

        $query_insert = "INSERT INTO ServicosPrestados (id_cliente, id_servico, quantidade, valor_final, data_vencimento) 
                         VALUES ('$id_cliente', '$id_servico', '$quantidade', '$valor_final', '$data_vencimento')";

        $result_insert = DBExecute($query_insert);

        if ($result_insert) {
            $mensagem = "<p style='color: green;'>Serviço prestado (Orçamento) cadastrado com sucesso!</p>";
            // Opcional: Limpar os campos do formulário após o sucesso
            // $id_cliente = $id_servico = $quantidade = $valor_final = $data_vencimento = '';
        } else {
            $mensagem = "<p style='color: red;'>Erro ao cadastrar serviço prestado.</p>";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Novo Orçamento / Serviço Prestado</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ccc; border-radius: 8px; box-shadow: 2px 2px 10px rgba(0,0,0,0.1); }
        h2 { text-align: center; color: #333; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        select, input[type="number"], input[type="date"] {
            width: calc(100% - 22px);
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        input[type="submit"] {
            background-color: #28a745; /* Verde para destacar a ação */
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
        }
        input[type="submit"]:hover {
            background-color: #218838;
        }
        .mensagem { text-align: center; margin-top: 15px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Novo Orçamento / Serviço Prestado</h2>
        <?php if (!empty($mensagem)) { echo "<div class='mensagem'>" . $mensagem . "</div>"; } ?>

        <form action="" method="POST">
            <label for="id_cliente">Cliente:</label>
            <select id="id_cliente" name="id_cliente" required>
                <option value="">Selecione um cliente</option>
                <?php foreach ($clientes as $cliente): ?>
                    <option value="<?= htmlspecialchars($cliente['id_cliente']) ?>">
                        <?= htmlspecialchars($cliente['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="id_servico">Serviço:</label>
            <select id="id_servico" name="id_servico" required>
                <option value="">Selecione um serviço</option>
                <?php foreach ($servicos as $servico): ?>
                    <option value="<?= htmlspecialchars($servico['id_servico']) ?>"
                            data-valor-sugerido="<?= htmlspecialchars($servico['valor_sugerido']) ?>">
                        <?= htmlspecialchars($servico['nome_servico']) ?> (Sug. R$ <?= number_format($servico['valor_sugerido'], 2, ',', '.') ?>)
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="quantidade">Quantidade:</label>
            <input type="number" id="quantidade" name="quantidade" min="1" value="1" required>

            <label for="valor_final">Valor Final (R$):</label>
            <input type="number" id="valor_final" name="valor_final" step="0.01" min="0.01" required>

            <label for="data_vencimento">Data de Vencimento:</label>
            <input type="date" id="data_vencimento" name="data_vencimento" required>

            <input type="submit" name="cadastrar_servico_prestado" value="Registrar Serviço Prestado">
        </form>
    </div>

    <script>
        // JavaScript para preencher o Valor Final com o Valor Sugerido do serviço selecionado
        document.getElementById('id_servico').addEventListener('change', function() {
            var selectedOption = this.options[this.selectedIndex];
            var valorSugerido = selectedOption.getAttribute('data-valor-sugerido');
            if (valorSugerido) {
                document.getElementById('valor_final').value = parseFloat(valorSugerido).toFixed(2);
            } else {
                document.getElementById('valor_final').value = ''; // Limpa se nenhuma opção válida for selecionada
            }
        });
    </script>
</body>
</html>