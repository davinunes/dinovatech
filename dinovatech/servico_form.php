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
                                <label for="descricao_fiscal" class="block text-sm font-medium text-gray-700 mb-1">
                                    Descrição Fiscal (Opcional)
                                    <span class="text-xs text-gray-500 font-normal ml-1">- Substitui o nome do serviço
                                        na NFS-e</span>
                                </label>
                                <textarea id="descricao_fiscal" name="descricao_fiscal" rows="2"
                                    class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500 transition"
                                    placeholder="Ex: Consultoria Técnica (Deixe em branco para usar o nome do serviço)"><?= $servico['descricao_fiscal'] ?? '' ?></textarea>
                            </div>

                            <div>
                                <label for="valor_sugerido" class="block text-sm font-medium text-gray-700 mb-1">Valor
                                    Sugerido (R$)</label>
                                <input type="number" id="valor_sugerido" name="valor_sugerido" step="0.01" min="0.00"
                                    value="<?= $servico['valor_sugerido'] ?? '' ?>" required
                                    class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition">
                            </div>

                            <!-- DADOS FISCAIS -->
                            <div class="border-t pt-4 mt-4">
                                <h3 class="text-lg font-semibold text-gray-700 mb-4 flex items-center">
                                    <span class="material-icons mr-2 text-cyan-600">receipt_long</span> Dados Fiscais
                                    (NFS-e)
                                </h3>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="item_lista_servico"
                                            class="block text-sm font-medium text-gray-700 mb-1">Item Lista Serviço (LC
                                            116)</label>
                                        <input type="text" id="item_lista_servico" name="item_lista_servico"
                                            value="<?= $servico['item_lista_servico'] ?? '' ?>" placeholder="Ex: 01.07"
                                            class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500 transition">
                                    </div>
                                    <div>
                                        <label for="codigo_nbs"
                                            class="block text-sm font-medium text-gray-700 mb-1">Código NBS</label>
                                        <input type="text" id="codigo_nbs" name="codigo_nbs"
                                            value="<?= $servico['codigo_nbs'] ?? '' ?>" placeholder="Ex: 1.01"
                                            class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500 transition">
                                    </div>
                                    <div>
                                        <label for="codigo_cnae"
                                            class="block text-sm font-medium text-gray-700 mb-1">CNAE</label>
                                        <input type="text" id="codigo_cnae" name="codigo_cnae"
                                            value="<?= $servico['codigo_cnae'] ?? '' ?>" placeholder="Ex: 6204000"
                                            class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500 transition">
                                    </div>
                                    <div>
                                        <label for="codigo_tributacao_municipio"
                                            class="block text-sm font-medium text-gray-700 mb-1">Cód. Tributação
                                            Mun.</label>
                                        <input type="text" id="codigo_tributacao_municipio"
                                            name="codigo_tributacao_municipio"
                                            value="<?= $servico['codigo_tributacao_municipio'] ?? '' ?>"
                                            class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500 transition">
                                    </div>
                                    <div>
                                        <label for="aliquota_iss"
                                            class="block text-sm font-medium text-gray-700 mb-1">Alíquota ISS
                                            (%)</label>
                                        <input type="number" id="aliquota_iss" name="aliquota_iss" step="0.01" min="0"
                                            max="100" value="<?= $servico['aliquota_iss'] ?? '0.00' ?>"
                                            class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500 transition">
                                    </div>
                                    <div class="flex items-center pt-8">
                                        <input type="checkbox" name="iss_retido" id="iss_retido" value="1"
                                            <?= ($servico['iss_retido'] ?? 0) ? 'checked' : '' ?>
                                            class="h-4 w-4 text-cyan-600 focus:ring-cyan-500 border-gray-300 rounded">
                                        <label for="iss_retido" class="ml-2 block text-sm text-gray-900">
                                            ISS Retido na Fonte?
                                        </label>
                                    </div>
                                </div>
                                <div class="mt-4">
                                    <label for="descricao_nfse_padrao"
                                        class="block text-sm font-medium text-gray-700 mb-1">Descrição Padrão
                                        (Template)</label>
                                    <textarea id="descricao_nfse_padrao" name="descricao_nfse_padrao" rows="3"
                                        placeholder="Texto que sairá na nota. Use {MES} para substituir pelo mês corrente."
                                        class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500 transition"><?= $servico['descricao_nfse_padrao'] ?? '' ?></textarea>
                                </div>
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