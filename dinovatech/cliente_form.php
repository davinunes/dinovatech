<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}
include "../database.php";

$id_cliente = $_GET['id'] ?? null;
$cliente = null;
$is_edit = false;

if ($id_cliente) {
    $link = DBConnect();
    $id_safe = mysqli_real_escape_string($link, $id_cliente);
    $query = "SELECT * FROM Clientes WHERE id_cliente = '$id_safe'";
    $result = DBExecute($link, $query);
    if ($result && mysqli_num_rows($result) > 0) {
        $cliente = mysqli_fetch_assoc($result);
        $is_edit = true;
    }
    DBClose($link);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <title>
        <?= $is_edit ? 'Editar Cliente' : 'Novo Cliente' ?> - Dinovatech
    </title>
    <?php include 'components/layout_head.php'; ?>
</head>

<body class="bg-gray-50 flex">

    <?php include 'components/sidebar.php'; ?>

    <div class="flex-1 flex flex-col lg:ml-64 min-h-screen transition-all duration-300">
        <main class="flex-1 p-6 mt-16 lg:mt-0">

            <div class="max-w-3xl mx-auto">
                <div class="flex items-center mb-6">
                    <a href="clientes.php" class="mr-4 text-gray-500 hover:text-gray-700">
                        <span class="material-icons">arrow_back</span>
                    </a>
                    <h2 class="text-3xl font-bold text-gray-800">
                        <?= $is_edit ? 'Editar Cliente' : 'Cadastrar Novo Cliente' ?>
                    </h2>
                </div>

                <div class="bg-white p-8 rounded-xl shadow-sm border border-gray-100">
                    <form id="clienteForm">
                        <input type="hidden" name="action" value="<?= $is_edit ? 'editar_cliente' : 'criar_cliente' ?>">
                        <?php if ($is_edit): ?>
                            <input type="hidden" name="id_cliente" value="<?= $cliente['id_cliente'] ?>">
                        <?php endif; ?>

                        <div class="space-y-6">
                            <div>
                                <label for="nome" class="block text-sm font-medium text-gray-700 mb-1">Nome Completo /
                                    Razão Social</label>
                                <input type="text" id="nome" name="nome" value="<?= $cliente['nome'] ?? '' ?>" required
                                    class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition">
                            </div>

                            <div>
                                <label for="cpf_cnpj" class="block text-sm font-medium text-gray-700 mb-1">CPF /
                                    CNPJ</label>
                                <input type="text" id="cpf_cnpj" name="cpf_cnpj"
                                    value="<?= $cliente['cpf_cnpj'] ?? '' ?>" required
                                    class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition">
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="inscricao_municipal"
                                        class="block text-sm font-medium text-gray-700 mb-1">Inscrição Municipal
                                        (Opcional)</label>
                                    <input type="text" id="inscricao_municipal" name="inscricao_municipal"
                                        value="<?= $cliente['inscricao_municipal'] ?? '' ?>"
                                        class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition">
                                </div>
                                <div>
                                    <label for="inscricao_estadual"
                                        class="block text-sm font-medium text-gray-700 mb-1">Inscrição Estadual
                                        (Opcional)</label>
                                    <input type="text" id="inscricao_estadual" name="inscricao_estadual"
                                        value="<?= $cliente['inscricao_estadual'] ?? '' ?>"
                                        class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="email"
                                        class="block text-sm font-medium text-gray-700 mb-1">E-mail</label>
                                    <input type="email" id="email" name="email" value="<?= $cliente['email'] ?? '' ?>"
                                        class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition">
                                </div>
                                <div>
                                    <label for="telefone" class="block text-sm font-medium text-gray-700 mb-1">Telefone
                                        / Celular</label>
                                    <input type="text" id="telefone" name="telefone"
                                        value="<?= $cliente['telefone'] ?? '' ?>"
                                        class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition">
                                </div>
                            </div>

                            <!-- Endereço Cliente -->
                            <div class="border-t pt-4 mt-2">
                                <h4 class="text-sm font-semibold text-gray-600 mb-3">Endereço (Obrigatório para NFSe)
                                </h4>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div class="md:col-span-2">
                                        <label for="endereco"
                                            class="block text-sm font-medium text-gray-700 mb-1">Logradouro</label>
                                        <input type="text" id="endereco" name="endereco"
                                            value="<?= $cliente['endereco'] ?? '' ?>"
                                            class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500 transition">
                                    </div>
                                    <div>
                                        <label for="numero"
                                            class="block text-sm font-medium text-gray-700 mb-1">Número</label>
                                        <input type="text" id="numero" name="numero"
                                            value="<?= $cliente['numero'] ?? '' ?>"
                                            class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500 transition">
                                    </div>
                                    <div>
                                        <label for="complemento"
                                            class="block text-sm font-medium text-gray-700 mb-1">Complemento</label>
                                        <input type="text" id="complemento" name="complemento"
                                            value="<?= $cliente['complemento'] ?? '' ?>"
                                            class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500 transition">
                                    </div>
                                    <div>
                                        <label for="bairro"
                                            class="block text-sm font-medium text-gray-700 mb-1">Bairro</label>
                                        <input type="text" id="bairro" name="bairro"
                                            value="<?= $cliente['bairro'] ?? '' ?>"
                                            class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500 transition">
                                    </div>
                                    <div>
                                        <label for="cep"
                                            class="block text-sm font-medium text-gray-700 mb-1">CEP</label>
                                        <input type="text" id="cep" name="cep" value="<?= $cliente['cep'] ?? '' ?>"
                                            class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500 transition">
                                    </div>
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label for="uf"
                                                class="block text-sm font-medium text-gray-700 mb-1">UF</label>
                                            <input type="text" id="uf" name="uf" value="<?= $cliente['uf'] ?? '' ?>"
                                                maxlength="2"
                                                class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500 transition uppercase">
                                        </div>
                                        <div>
                                            <label for="codigo_municipio"
                                                class="block text-sm font-medium text-gray-700 mb-1">Cód. Mun.
                                                (IBGE)</label>
                                            <input type="text" id="codigo_municipio" name="codigo_municipio"
                                                value="<?= $cliente['codigo_municipio'] ?? '5300108' ?>"
                                                class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500 transition">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="pt-4 flex justify-end">
                                <a href="clientes.php"
                                    class="px-6 py-3 mr-3 bg-gray-100 text-gray-700 font-medium rounded-lg hover:bg-gray-200 transition">Cancelar</a>
                                <button type="submit"
                                    class="px-6 py-3 bg-cyan-600 text-white font-medium rounded-lg hover:bg-cyan-700 shadow-md transition transform hover:scale-105">
                                    <?= $is_edit ? 'Salvar Alterações' : 'Cadastrar Cliente' ?>
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
            // Mask for CPF/CNPJ (Simple version)
            $('#cpf_cnpj').on('input', function () {
                this.value = this.value.replace(/[^0-9]/g, '');
            });

            $('#clienteForm').on('submit', function (e) {
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
                                window.location.href = 'clientes.php';
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