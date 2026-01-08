<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}
include "../database.php";

$id_servico = $_GET['id'] ?? null;
$servico = null;
$is_edit = false;

if ($id_servico) {
    $link = DBConnect();
    $id_safe = mysqli_real_escape_string($link, $id_servico);
    $query = "SELECT * FROM Servicos WHERE id_servico = '$id_safe'";
    $result = DBExecute($link, $query);
    if ($result && mysqli_num_rows($result) > 0) {
        $servico = mysqli_fetch_assoc($result);
        $is_edit = true;
    }
    DBClose($link);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <title>
        <?= $is_edit ? 'Editar Serviço' : 'Novo Serviço' ?> - Dinovatech
    </title>
    <?php include 'components/layout_head.php'; ?>
</head>

<body class="bg-gray-50 flex">

    <?php include 'components/sidebar.php'; ?>

    <div class="flex-1 flex flex-col lg:ml-64 min-h-screen transition-all duration-300">
        <main class="flex-1 p-6 mt-16 lg:mt-0">

            <div class="max-w-xl mx-auto">
                <div class="flex items-center mb-6">
                    <a href="servicos.php" class="mr-4 text-gray-500 hover:text-gray-700">
                        <span class="material-icons">arrow_back</span>
                    </a>
                    <h2 class="text-3xl font-bold text-gray-800">
                        <?= $is_edit ? 'Editar Serviço' : 'Cadastrar Novo Serviço' ?>
                    </h2>
                </div>

                <div class="bg-white p-8 rounded-xl shadow-sm border border-gray-100">
                    <form id="servicoForm">
                        <input type="hidden" name="action" value="<?= $is_edit ? 'editar_servico' : 'criar_servico' ?>">
                        <?php if ($is_edit): ?>
                            <input type="hidden" name="id_servico" value="<?= $servico['id_servico'] ?>">
                        <?php endif; ?>

                        <div class="space-y-6">
                            <div>
                                <label for="nome_servico" class="block text-sm font-medium text-gray-700 mb-1">Nome do
                                    Serviço</label>
                                <input type="text" id="nome_servico" name="nome_servico"
                                    value="<?= $servico['nome_servico'] ?? '' ?>" required
                                    class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition">
                            </div>

                            <div>
                                <label for="valor_sugerido" class="block text-sm font-medium text-gray-700 mb-1">Valor
                                    Sugerido (R$)</label>
                                <input type="number" id="valor_sugerido" name="valor_sugerido" step="0.01" min="0.00"
                                    value="<?= $servico['valor_sugerido'] ?? '' ?>" required
                                    class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition">
                            </div>

                            <div class="pt-4 flex justify-end">
                                <a href="servicos.php"
                                    class="px-6 py-3 mr-3 bg-gray-100 text-gray-700 font-medium rounded-lg hover:bg-gray-200 transition">Cancelar</a>
                                <button type="submit"
                                    class="px-6 py-3 bg-cyan-600 text-white font-medium rounded-lg hover:bg-cyan-700 shadow-md transition transform hover:scale-105">
                                    <?= $is_edit ? 'Salvar Alterações' : 'Cadastrar Serviço' ?>
                                </button>
                            </div>
                        </div>
                    </form>
                    <div id="formMessage" class="mt-4 text-center font-medium hidden"></div>
                </div>
            </div>

        </main>
    </div>

    <?php include 'components/layout_scripts.php'; ?>
    <script>
        $(document).ready(function () {
            $('#servicoForm').on('submit', function (e) {
                e.preventDefault();
                const btn = $(this).find('button[type="submit"]');
                const originalText = btn.text();
                btn.prop('disabled', true).text('Processando...');

                $.ajax({
                    url: 'app.php',
                    type: 'POST',
                    data: $(this).serialize(),
                    dataType: 'json',
                    success: function (response) {
                        const msgDiv = $('#formMessage');
                        msgDiv.removeClass('hidden text-green-600 text-red-600');
                        msgDiv.text(response.message);

                        if (response.success) {
                            msgDiv.addClass('text-green-600');
                            setTimeout(() => {
                                window.location.href = 'servicos.php';
                            }, 1500);
                        } else {
                            msgDiv.addClass('text-red-600');
                            btn.prop('disabled', false).text(originalText);
                        }
                        msgDiv.show();
                    },
                    error: function () {
                        alert('Erro de conexão.');
                        btn.prop('disabled', false).text(originalText);
                    }
                });
            });
        });
    </script>
</body>

</html>