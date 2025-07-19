<?php
include "../database.php";

// Lógica para buscar e paginar clientes (executa em GET)
$clientes = [];
$total_pages = 0;
$current_page = 1;

$link = DBConnect();
if ($link) {
    $limit = 5;
    $current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
    if ($current_page < 1) $current_page = 1;

    $query_total = "SELECT COUNT(id_cliente) AS total FROM Clientes";
    $result_total = DBExecute($link, $query_total);
    $total_records = mysqli_fetch_assoc($result_total)['total'];
    $total_pages = ceil($total_records / $limit);

    $offset = ($current_page - 1) * $limit;
    $query_clientes = "SELECT id_cliente, nome, cpf_cnpj, email FROM Clientes ORDER BY nome ASC LIMIT $limit OFFSET $offset";
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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciamento de Clientes</title>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.13.2/jquery-ui.min.js"></script>
    <link rel="stylesheet" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.13.2/themes/smoothness/jquery-ui.css">
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f4f4f4; }
        .container { max-width: 800px; margin: 0 auto 20px auto; padding: 20px; border: 1px solid #ccc; border-radius: 8px; box-shadow: 2px 2px 10px rgba(0,0,0,0.1); background-color: #fff; }
        h2 { text-align: center; color: #333; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"], input[type="email"] { width: calc(100% - 22px); padding: 10px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 4px; }
        .mensagem { text-align: center; margin: 15px 0; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .actions button { padding: 5px 10px; margin-right: 5px; border-radius: 4px; border: 1px solid transparent; cursor: pointer; }
        .btn-edit { background-color: #ffc107; color: #212529; }
        .btn-details { background-color: #17a2b8; color: white; }
        .pagination { text-align: center; margin-top: 20px; }
        .pagination a, .pagination span { margin: 0 5px; padding: 8px 12px; border: 1px solid #ddd; text-decoration: none; color: #007bff; }
        .pagination a:hover { background-color: #f2f2f2; }
        .pagination .current-page { background-color: #007bff; color: white; border-color: #007bff; }
        .pagination .disabled { color: #ccc; pointer-events: none; }
        .controls { text-align: right; margin-bottom: 20px; }
        .controls button { background-color: #28a745; color: white; padding: 10px 15px; border: none; border-radius: 5px; cursor: pointer; font-size: 1em; }
        .controls button:hover { background-color: #218838; }
        .ui-dialog-titlebar-close { background: none; border: none; }
    </style>
</head>
<body>
    <div class="container">
        <div class="controls">
            <button id="btnNovoCliente">Novo Cliente</button>
        </div>
        <h2>Clientes Cadastrados</h2>
        <table>
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>CPF/CNPJ</th>
                    <th>Email</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($clientes)): ?>
                    <?php foreach ($clientes as $cliente): ?>
                        <tr>
                            <td><?= htmlspecialchars($cliente['nome']) ?></td>
                            <td><?= htmlspecialchars($cliente['cpf_cnpj']) ?></td>
                            <td><?= htmlspecialchars($cliente['email']) ?></td>
                            <td class="actions">
                                <button class="btn-edit" data-id-cliente="<?= $cliente['id_cliente'] ?>">Editar</button>
                                <button class="btn-details" onclick="window.location.href='index.php?cliente_id=<?= $cliente['id_cliente'] ?>'">Detalhar</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" style="text-align: center;">Nenhum cliente cadastrado.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Controles de Paginação -->
        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($current_page > 1): ?> <a href="?page=<?= $current_page - 1 ?>">Anterior</a> <?php else: ?> <span class="disabled">Anterior</span> <?php endif; ?>
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <?php if ($i == $current_page): ?> <span class="current-page"><?= $i ?></span> <?php else: ?> <a href="?page=<?= $i ?>"><?= $i ?></a> <?php endif; ?>
                <?php endfor; ?>
                <?php if ($current_page < $total_pages): ?> <a href="?page=<?= $current_page + 1 ?>">Próximo</a> <?php else: ?> <span class="disabled">Próximo</span> <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal de Cadastro de Cliente -->
    <div id="modalCadastrarCliente" title="Cadastrar Novo Cliente" style="display: none;">
        <form id="formCadastrarCliente">
            <label for="nome">Nome:</label>
            <input type="text" id="nome" name="nome" required>
            <label for="cpf_cnpj">CPF/CNPJ:</label>
            <input type="text" id="cpf_cnpj" name="cpf_cnpj" required>
            <label for="telefone">Telefone:</label>
            <input type="text" id="telefone" name="telefone">
            <label for="email">E-mail:</label>
            <input type="email" id="email" name="email">
        </form>
        <div class="mensagem" id="cadastroMensagem"></div>
    </div>

    <!-- Modal de Edição de Cliente -->
    <div id="modalEditarCliente" title="Editar Cliente" style="display: none;">
        <form id="formEditarCliente">
            <input type="hidden" id="editClienteId" name="id_cliente">
            <label for="editNome">Nome:</label>
            <input type="text" id="editNome" name="nome" required>
            <label for="editCpfCnpj">CPF/CNPJ:</label>
            <input type="text" id="editCpfCnpj" name="cpf_cnpj" required>
            <label for="editTelefone">Telefone:</label>
            <input type="text" id="editTelefone" name="telefone">
            <label for="editEmail">E-mail:</label>
            <input type="email" id="editEmail" name="email">
        </form>
        <div class="mensagem" id="editMensagem"></div>
    </div>

<script>
$(document).ready(function() {
    // Inicializa os modais
    $("#modalCadastrarCliente, #modalEditarCliente").dialog({
        autoOpen: false, modal: true, width: 500,
        buttons: { "Cancelar": function() { $(this).dialog("close"); } }
    });
    $("#modalCadastrarCliente").dialog("option", "buttons", {
        "Salvar Cliente": function() { $("#formCadastrarCliente").submit(); },
        "Cancelar": function() { $(this).dialog("close"); }
    });
    $("#modalEditarCliente").dialog("option", "buttons", {
        "Salvar Alterações": function() { $("#formEditarCliente").submit(); },
        "Cancelar": function() { $(this).dialog("close"); }
    });

    // Abre modal de cadastro
    $("#btnNovoCliente").on("click", function() {
        $("#formCadastrarCliente")[0].reset();
        $("#cadastroMensagem").html("");
        $("#modalCadastrarCliente").dialog("open");
    });

    // Submete cadastro via AJAX
    $("#formCadastrarCliente").on("submit", function(e) {
        e.preventDefault();
        $.ajax({
            url: 'app.php', type: 'POST', data: $(this).serialize() + "&action=criar_cliente", dataType: 'json',
            success: function(response) {
                $("#cadastroMensagem").html(response.message);
                if (response.success) { setTimeout(() => location.reload(), 1500); }
            }
        });
    });

    // Abre e preenche modal de edição
    $(document).on("click", ".btn-edit", function() {
        const clienteId = $(this).data("id-cliente");
        $.ajax({
            url: 'app.php', type: 'POST', data: { action: 'get_cliente_details', id_cliente: clienteId }, dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $("#editClienteId").val(response.data.id_cliente);
                    $("#editNome").val(response.data.nome);
                    $("#editCpfCnpj").val(response.data.cpf_cnpj);
                    $("#editTelefone").val(response.data.telefone);
                    $("#editEmail").val(response.data.email);
                    $("#editMensagem").html("");
                    $("#modalEditarCliente").dialog("open");
                } else { alert(response.message); }
            }
        });
    });

    // Submete edição via AJAX
    $("#formEditarCliente").on("submit", function(e) {
        e.preventDefault();
        $.ajax({
            url: 'app.php', type: 'POST', data: $(this).serialize() + "&action=editar_cliente", dataType: 'json',
            success: function(response) {
                $("#editMensagem").html(response.message);
                if (response.success) { setTimeout(() => location.reload(), 1500); }
            }
        });
    });
});
</script>
</body>
</html>
