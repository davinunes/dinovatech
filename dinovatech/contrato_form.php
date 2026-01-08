<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}
include "../database.php";

$id_recorrencia = $_GET['id'] ?? null;
// Pre-fill client ID if coming from client details
$pre_cliente_id = $_GET['cliente_id'] ?? null;

$contrato = null;
$is_edit = false;

$link = DBConnect();

// Fetch Services for Dropdown
$servicos = [];
$query_servicos = "SELECT id_servico, nome_servico, valor_sugerido FROM Servicos ORDER BY nome_servico ASC";
$result_servicos = DBExecute($link, $query_servicos);
while ($row = mysqli_fetch_assoc($result_servicos))
    $servicos[] = $row;

// Fetch Clients for Dropdown
$clientes = [];
$query_clientes = "SELECT id_cliente, nome FROM Clientes ORDER BY nome ASC";
$result_clientes = DBExecute($link, $query_clientes);
while ($row = mysqli_fetch_assoc($result_clientes))
    $clientes[] = $row;


if ($id_recorrencia) {
    $id_safe = mysqli_real_escape_string($link, $id_recorrencia);
    $query = "SELECT * FROM Recorrencias WHERE id_recorrencia = '$id_safe'";
    $result = DBExecute($link, $query);
    if ($result && mysqli_num_rows($result) > 0) {
        $contrato = mysqli_fetch_assoc($result);
        $is_edit = true;
    }
}

DBClose($link);
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <title>
        <?= $is_edit ? 'Editar Contrato' : 'Novo Contrato' ?> - Dinovatech
    </title>
    <?php include 'components/layout_head.php'; ?>
</head>

<body class="bg-gray-50 flex">

    <?php include 'components/sidebar.php'; ?>

    <div class="flex-1 flex flex-col lg:ml-64 min-h-screen transition-all duration-300">
        <main class="flex-1 p-6 mt-16 lg:mt-0">

            <div class="max-w-2xl mx-auto">
                <div class="flex items-center mb-6">
                    <a href="contratos.php" class="mr-4 text-gray-500 hover:text-gray-700">
                        <span class="material-icons">arrow_back</span>
                    </a>
                    <h2 class="text-3xl font-bold text-gray-800">
                        <?= $is_edit ? 'Editar Contrato' : 'Novo Contrato' ?>
                    </h2>
                </div>

                <div class="bg-white p-8 rounded-xl shadow-sm border border-gray-100">
                    <form id="contratoForm">
                        <input type="hidden" name="action" value="vincular_recorrencia"> <!-- Reuse logic -->
                        <!-- If edit logic existed in app.php for recorrencia, we'd use it. But looking at app.php, there might not be 'editar_recorrencia'.
                              Wait, app.php does NOT have 'editar_recorrencia'. It has 'vincular_recorrencia'.
                              I should probably implement 'editar_recorrencia' later or just delete and re-create.
                              For now let's assume I can duplicate OR I should add 'editar_recorrencia' to app.php.
                              Given usage, I will stick to 'vincular_recorrencia' (Create) mostly, but for 'Edit' I might need to handle it.
                              Actually, standard practice: Add 'editar_recorrencia' to app.php.
                              For this moment, if it's edit, I'll allow editing. I will update app.php later if needed.
                              Let's check app.php again... no 'editar_recorrencia'.
                              So, 'Edit' functionality effectively creates a new one or fails?
                              If I use 'vincular_recorrencia' it inserts.
                              For this MVP step, I will only support CREATION via this form properly.
                              If $is_edit, I should warn or Implement Update. 
                              Let's Implement 'editar_recorrencia' in app.php in next step.
                              For now, lets set action to 'editar_recorrencia' if is_edit, and 'vincular_recorrencia' if not.
                          -->
                        <input type="hidden" name="action"
                            value="<?= $is_edit ? 'editar_recorrencia' : 'vincular_recorrencia' ?>">
                        <?php if ($is_edit): ?>
                            <input type="hidden" name="id_recorrencia" value="<?= $id_recorrencia ?>">
                        <?php endif; ?>

                        <div class="space-y-6">

                            <div>
                                <label for="id_cliente"
                                    class="block text-sm font-medium text-gray-700 mb-1">Cliente</label>
                                <select id="id_cliente" name="id_cliente" required
                                    class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500 transition bg-white">
                                    <option value="">Selecione um cliente...</option>
                                    <?php foreach ($clientes as $c): ?>
                                        <option value="<?= $c['id_cliente'] ?>" <?= ($contrato['id_cliente'] ?? $pre_cliente_id) == $c['id_cliente'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($c['nome']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div>
                                <label for="id_servico"
                                    class="block text-sm font-medium text-gray-700 mb-1">Serviço</label>
                                <select id="id_servico" name="id_servico" required
                                    class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500 transition bg-white"
                                    onchange="updateValorSugerido()">
                                    <option value="">Selecione um serviço...</option>
                                    <?php foreach ($servicos as $s): ?>
                                        <option value="<?= $s['id_servico'] ?>" data-valor="<?= $s['valor_sugerido'] ?>"
                                            <?= ($contrato['id_servico'] ?? '') == $s['id_servico'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($s['nome_servico']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="valor_sugerido_recorrencia"
                                        class="block text-sm font-medium text-gray-700 mb-1">Valor Recorrente
                                        (R$)</label>
                                    <input type="number" id="valor_sugerido_recorrencia"
                                        name="valor_sugerido_recorrencia" step="0.01"
                                        value="<?= $contrato['valor_sugerido_recorrencia'] ?? '' ?>" required
                                        class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500 transition">
                                </div>
                                <div>
                                    <label for="quantidade"
                                        class="block text-sm font-medium text-gray-700 mb-1">Quantidade</label>
                                    <input type="number" id="quantidade" name="quantidade"
                                        value="<?= $contrato['quantidade'] ?? '1' ?>" required
                                        class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500 transition">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="tipo_periodo"
                                        class="block text-sm font-medium text-gray-700 mb-1">Periodicidade</label>
                                    <select id="tipo_periodo" name="tipo_periodo" required
                                        class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500 transition bg-white">
                                        <option value="mensal" <?= ($contrato['tipo_periodo'] ?? '') == 'mensal' ? 'selected' : '' ?>>Mensal</option>
                                        <option value="anual" <?= ($contrato['tipo_periodo'] ?? '') == 'anual' ? 'selected' : '' ?>>Anual</option>
                                        <option value="semanal" <?= ($contrato['tipo_periodo'] ?? '') == 'semanal' ? 'selected' : '' ?>>Semanal</option>
                                        <option value="diario" <?= ($contrato['tipo_periodo'] ?? '') == 'diario' ? 'selected' : '' ?>>Diário</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="intervalo"
                                        class="block text-sm font-medium text-gray-700 mb-1">Intervalo (a cada)</label>
                                    <input type="number" id="intervalo" name="intervalo"
                                        value="<?= $contrato['intervalo'] ?? '1' ?>" required
                                        class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500 transition">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="data_inicio_cobranca"
                                        class="block text-sm font-medium text-gray-700 mb-1">Data Início</label>
                                    <input type="date" id="data_inicio_cobranca" name="data_inicio_cobranca"
                                        value="<?= $contrato['data_inicio_cobranca'] ?? date('Y-m-d') ?>" required
                                        class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500 transition">
                                </div>
                                <div>
                                    <label for="data_fim_cobranca"
                                        class="block text-sm font-medium text-gray-700 mb-1">Data Fim (Opcional)</label>
                                    <input type="date" id="data_fim_cobranca" name="data_fim_cobranca"
                                        value="<?= $contrato['data_fim_cobranca'] ?? '' ?>"
                                        class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500 transition">
                                </div>
                            </div>

                            <div class="pt-4 flex justify-end">
                                <a href="contratos.php"
                                    class="px-6 py-3 mr-3 bg-gray-100 text-gray-700 font-medium rounded-lg hover:bg-gray-200 transition">Cancelar</a>
                                <button type="submit"
                                    class="px-6 py-3 bg-cyan-600 text-white font-medium rounded-lg hover:bg-cyan-700 shadow-md transition transform hover:scale-105">
                                    <?= $is_edit ? 'Salvar Contrato' : 'Criar Contrato' ?>
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
        function updateValorSugerido() {
            const select = document.getElementById('id_servico');
            const valorInput = document.getElementById('valor_sugerido_recorrencia');
            const selectedOption = select.options[select.selectedIndex];
            const valor = selectedOption.getAttribute('data-valor');
            if (valor && !valorInput.value) { // Update only if empty
                valorInput.value = valor;
            }
        }

        $(document).ready(function () {
            updateValorSugerido(); // Init

            $('#contratoForm').on('submit', function (e) {
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

                        // Handle "Action Invalid" for edits since we didn't add it yet
                        if (response.message === "Ação inválida.") {
                            msgDiv.text("Erro: Funcionalidade de edição ainda não implementada no backend. (Ação inválida)");
                            msgDiv.addClass('text-red-600');
                            btn.prop('disabled', false).text(originalText);
                            return;
                        }

                        if (response.success) {
                            msgDiv.addClass('text-green-600');
                            setTimeout(() => {
                                window.location.href = 'contratos.php';
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