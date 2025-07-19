<?php
include "../database.php";

// Lógica para buscar e paginar serviços (executa em GET)
$servicos = [];
$total_pages = 0;
$current_page = 1;

$link = DBConnect();
if ($link) {
    // Configurações da paginação
    $limit = 5;
    $current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
    if ($current_page < 1) $current_page = 1;

    // Conta o total de registros
    $query_total = "SELECT COUNT(id_servico) AS total FROM Servicos";
    $result_total = DBExecute($link, $query_total);
    $total_records = mysqli_fetch_assoc($result_total)['total'];
    $total_pages = ceil($total_records / $limit);

    // Calcula o offset e busca os serviços da página atual
    $offset = ($current_page - 1) * $limit;
    $query_servicos = "SELECT id_servico, nome_servico, valor_sugerido FROM Servicos ORDER BY nome_servico ASC LIMIT $limit OFFSET $offset";
    $result_servicos = DBExecute($link, $query_servicos);
    if ($result_servicos) {
        while ($row = mysqli_fetch_assoc($result_servicos)) {
            $servicos[] = $row;
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
    <title>Gerenciamento de Serviços</title>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.13.2/jquery-ui.min.js"></script>
    <link rel="stylesheet" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.13.2/themes/smoothness/jquery-ui.css">
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f4f4f4; }
        .container { max-width: 800px; margin: 0 auto 20px auto; padding: 20px; border: 1px solid #ccc; border-radius: 8px; box-shadow: 2px 2px 10px rgba(0,0,0,0.1); background-color: #fff; }
        h2 { text-align: center; color: #333; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"], input[type="number"] { width: calc(100% - 22px); padding: 10px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 4px; }
        .mensagem { text-align: center; margin: 15px 0; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .actions button { padding: 5px 10px; margin-right: 5px; border-radius: 4px; border: 1px solid transparent; cursor: pointer; }
        .btn-edit { background-color: #ffc107; color: #212529; }
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
            <button id="btnNovoServico">Novo Serviço</button>
        </div>
        <h2>Serviços Cadastrados</h2>
        <table>
            <thead>
                <tr>
                    <th>Nome do Serviço</th>
                    <th>Valor Sugerido</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($servicos)): ?>
                    <?php foreach ($servicos as $servico): ?>
                        <tr>
                            <td><?= htmlspecialchars($servico['nome_servico']) ?></td>
                            <td>R$ <?= number_format($servico['valor_sugerido'], 2, ',', '.') ?></td>
                            <td class="actions">
                                <button class="btn-edit" data-id-servico="<?= $servico['id_servico'] ?>">Editar</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3" style="text-align: center;">Nenhum serviço cadastrado.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Controles de Paginação -->
        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($current_page > 1): ?>
                    <a href="?page=<?= $current_page - 1 ?>">Anterior</a>
                <?php else: ?>
                    <span class="disabled">Anterior</span>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <?php if ($i == $current_page): ?>
                        <span class="current-page"><?= $i ?></span>
                    <?php else: ?>
                        <a href="?page=<?= $i ?>"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($current_page < $total_pages): ?>
                    <a href="?page=<?= $current_page + 1 ?>">Próximo</a>
                <?php else: ?>
                    <span class="disabled">Próximo</span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal de Cadastro de Serviço -->
    <div id="modalCadastrarServico" title="Cadastrar Novo Serviço" style="display: none;">
        <form id="formCadastrarServico">
            <label for="nome_servico">Nome do Serviço:</label>
            <input type="text" id="nome_servico" name="nome_servico" required>
            <label for="valor_sugerido">Valor Sugerido:</label>
            <input type="number" id="valor_sugerido" name="valor_sugerido" step="0.01" min="0" required>
        </form>
        <div class="mensagem" id="cadastroMensagem"></div>
    </div>

    <!-- Modal de Edição de Serviço -->
    <div id="modalEditarServico" title="Editar Serviço" style="display: none;">
        <form id="formEditarServico">
            <input type="hidden" id="editServicoId" name="id_servico">
            <label for="editNomeServico">Nome do Serviço:</label>
            <input type="text" id="editNomeServico" name="nome_servico" required>
            <label for="editValorSugerido">Valor Sugerido:</label>
            <input type="number" id="editValorSugerido" name="valor_sugerido" step="0.01" min="0" required>
        </form>
        <div class="mensagem" id="editMensagem"></div>
    </div>

<script>
$(document).ready(function() {
    // Inicializa o modal de cadastro
    $("#modalCadastrarServico").dialog({
        autoOpen: false, modal: true, width: 500,
        buttons: {
            "Salvar Serviço": function() { $("#formCadastrarServico").submit(); },
            "Cancelar": function() { $(this).dialog("close"); }
        }
    });

    // Inicializa o modal de edição
    $("#modalEditarServico").dialog({
        autoOpen: false, modal: true, width: 500,
        buttons: {
            "Salvar Alterações": function() { $("#formEditarServico").submit(); },
            "Cancelar": function() { $(this).dialog("close"); }
        }
    });

    // Abre o modal de cadastro
    $("#btnNovoServico").on("click", function() {
        $("#formCadastrarServico")[0].reset();
        $("#cadastroMensagem").html("");
        $("#modalCadastrarServico").dialog("open");
    });

    // Submete o formulário de cadastro via AJAX
    $("#formCadastrarServico").on("submit", function(e) {
        e.preventDefault();
        const formData = $(this).serialize() + "&action=criar_servico";
        $("#cadastroMensagem").text("Salvando...");
        $.ajax({
            url: 'app.php', type: 'POST', data: formData, dataType: 'json',
            success: function(response) {
                $("#cadastroMensagem").html(response.message);
                if (response.success) {
                    setTimeout(function() { location.reload(); }, 1500);
                }
            },
            error: function() {
                $("#cadastroMensagem").html("<p style='color: red;'>Erro de comunicação.</p>");
            }
        });
    });

    // Abre e preenche o modal de edição
    $(document).on("click", ".btn-edit", function() {
        const servicoId = $(this).data("id-servico");
        $.ajax({
            url: 'app.php', type: 'POST', data: { action: 'get_servico_details', id_servico: servicoId }, dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $("#editServicoId").val(response.data.id_servico);
                    $("#editNomeServico").val(response.data.nome_servico);
                    $("#editValorSugerido").val(response.data.valor_sugerido);
                    $("#editMensagem").html("");
                    $("#modalEditarServico").dialog("open");
                } else {
                    alert(response.message);
                }
            },
            error: function() {
                alert("Erro ao buscar dados do serviço.");
            }
        });
    });

    // Submete o formulário de edição via AJAX
    $("#formEditarServico").on("submit", function(e) {
        e.preventDefault();
        const formData = $(this).serialize() + "&action=editar_servico";
        $("#editMensagem").text("Salvando...");
        $.ajax({
            url: 'app.php', type: 'POST', data: formData, dataType: 'json',
            success: function(response) {
                $("#editMensagem").html(response.message);
                if (response.success) {
                    setTimeout(function() { location.reload(); }, 1500);
                }
            },
            error: function() {
                $("#editMensagem").html("<p style='color: red;'>Erro de comunicação.</p>");
            }
        });
    });
});
</script>
</body>
</html>
