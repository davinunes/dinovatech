<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../../login.php");
    exit();
}
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../helpers/AppHelper.php';
if (!AppHelper::isVetMode()) {
    header("Location: ../../dashboard.php");
    exit();
}
include "../../../database.php";

$link = DBConnect();

$id_atendimento = $_GET['id'] ?? null;
$id_pet_pre = $_GET['pet_id'] ?? null;

// Fetch Lists
$veterinarios = [];
$q_vet = "SELECT * FROM Veterinarios ORDER BY nome ASC";
$r_vet = DBExecute($link, $q_vet);
while ($v = mysqli_fetch_assoc($r_vet))
    $veterinarios[] = $v;

// Init Vars
$atendimento = null;
$is_edit = false;
$data_atendimento = date('Y-m-d H:i');
$id_veterinario = '';
$motivo = '';
$anamnese = '';
$exame_fisico = '';
$diagnostico = '';
$tratamento = '';
$obs_internas = '';
$peso = '';

// Load Data if Edit
if ($id_atendimento) {
    $id_safe = mysqli_real_escape_string($link, $id_atendimento);
    $q = "SELECT * FROM Atendimentos WHERE id_atendimento = '$id_safe'";
    $r = DBExecute($link, $q);
    if ($r && mysqli_num_rows($r) > 0) {
        $atendimento = mysqli_fetch_assoc($r);
        $is_edit = true;
        // Populate inputs
        $id_pet_pre = $atendimento['id_pet'];
        $data_atendimento = date('Y-m-d\TH:i', strtotime($atendimento['data_atendimento'])); // Format for datetime-local
        $id_veterinario = $atendimento['id_vet'];
        $motivo = $atendimento['queixa_principal'];
        $anamnese = $atendimento['anamnese'];
        $exame_fisico = $atendimento['exame_fisico'];
        $diagnostico = $atendimento['diagnostico'];
        $tratamento = $atendimento['conduta_tratamento'];
        $obs_internas = ""; // Column missing in DB
        $peso = "";         // Column missing in DB
    }
}

// Fetch Pet Info (for display)
$pet = null;
if ($id_pet_pre) {
    $pet_safe = mysqli_real_escape_string($link, $id_pet_pre);
    $q_pet = "SELECT p.*, c.nome as nome_tutor FROM Pets p JOIN Clientes c ON p.id_cliente = c.id_cliente WHERE p.id_pet = '$pet_safe'";
    $r_pet = DBExecute($link, $q_pet);
    if ($r_pet)
        $pet = mysqli_fetch_assoc($r_pet);
}

// Handle Save
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_pet = $_POST['id_pet'] ?? '';
    $id_veterinario = $_POST['id_veterinario'] ?? '';
    $data = $_POST['data_atendimento'] ?? '';
    $peso = $_POST['peso'] ?? '';
    $motivo = mysqli_real_escape_string($link, $_POST['motivo'] ?? '');
    $anamnese = mysqli_real_escape_string($link, $_POST['anamnese'] ?? '');
    $exame = mysqli_real_escape_string($link, $_POST['exame_fisico'] ?? '');
    $diag = mysqli_real_escape_string($link, $_POST['diagnostico'] ?? '');
    $presc = mysqli_real_escape_string($link, $_POST['prescricao'] ?? '');
    $obs = mysqli_real_escape_string($link, $_POST['obs_internas'] ?? '');

    // Validate required fields
    $missing = [];
    if (empty($id_pet))
        $missing[] = "Pet (ID: $id_pet)";
    if (empty($id_veterinario))
        $missing[] = "Veterinário";
    if (empty($data))
        $missing[] = "Data";

    if (!empty($missing)) {
        echo "<pre>";
        echo "Erro: Campos obrigatórios faltando: " . implode(", ", $missing) . "\n";
        echo "DADOS RECEBIDOS (POST):\n";
        print_r($_POST);
        echo "</pre>";
        exit;
    }

    $id_pet = (int) $id_pet;
    $id_veterinario = (int) $id_veterinario;
    $data_safe = mysqli_real_escape_string($link, $data);
    $peso_safe = $peso ? "'" . mysqli_real_escape_string($link, $peso) . "'" : "NULL";

    // Update Pet Weight
    if ($peso) {
        $peso_val = mysqli_real_escape_string($link, $peso);
        DBExecute($link, "UPDATE Pets SET peso = '$peso_val' WHERE id_pet = $id_pet");
    }

    if ($is_edit) {
        $q = "UPDATE Atendimentos SET id_vet=$id_veterinario, data_atendimento='$data_safe', queixa_principal='$motivo', 
              anamnese='$anamnese', exame_fisico='$exame', diagnostico='$diag', conduta_tratamento='$presc'
              WHERE id_atendimento=$id_atendimento";
    } else {
        $q = "INSERT INTO Atendimentos (id_pet, id_vet, data_atendimento, queixa_principal, anamnese, exame_fisico, diagnostico, conduta_tratamento)
              VALUES ($id_pet, $id_veterinario, '$data_safe', '$motivo', '$anamnese', '$exame', '$diag', '$presc')";
    }

    if (DBExecute($link, $q)) {
        header("Location: pet_detalhes.php?id=$id_pet"); // Back to Pet Profile
        exit();
    } else {
        echo "Erro: " . mysqli_error($link);
    }
}
DBClose($link);
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <title>Atendimento Clínico - DinoVet</title>
    <?php include '../../components/layout_head.php'; ?>
    <style>
        .form-section {
            @apply bg-white p-6 rounded-xl shadow-sm border border-gray-100 mb-6;
        }

        .section-title {
            @apply text-lg font-bold text-gray-800 mb-4 flex items-center;
        }

        /* Improved Tabs */
        .tabs-nav {
            @apply flex space-x-2 bg-gray-100 p-1 rounded-lg mb-6;
        }

        .tab-btn {
            @apply flex-1 py-2.5 px-4 text-sm font-medium text-gray-500 rounded-md transition-all duration-200 text-center focus:outline-none;
        }

        .tab-btn:hover {
            @apply text-gray-700 bg-gray-200;
        }

        .tab-btn.active {
            @apply text-cyan-700 bg-white shadow-sm font-bold;
        }
    </style>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body class="bg-gray-50 flex">
    <?php include '../../components/sidebar.php'; ?>

    <div class="flex-1 flex flex-col lg:ml-64 min-h-screen transition-all duration-300">
        <main class="flex-1 p-6 mt-16 lg:mt-0">

            <!-- Header -->
            <div class="flex flex-col md:flex-row items-center justify-between mb-6">
                <div class="flex items-center mb-4 md:mb-0">
                    <a href="pet_detalhes.php?id=<?= $id_pet_pre ?>" class="mr-4 text-gray-500 hover:text-gray-700">
                        <span class="material-icons">arrow_back</span>
                    </a>
                    <div>
                        <h2 class="text-3xl font-bold text-gray-800">Atendimento Clínico</h2>
                        <p class="text-gray-500">Paciente: <b><?= htmlspecialchars($pet['nome']) ?></b>
                            (<?= htmlspecialchars($pet['especie']) ?>)</p>
                    </div>
                </div>
            </div>

            <!-- Tabs Navigation -->
            <div class="max-w-5xl mx-auto tabs-nav">
                <button type="button" class="tab-btn active" onclick="openTab('prontuario')">
                    <span class="material-icons text-sm align-middle mr-1">assignment</span> Prontuário
                </button>
                <button type="button" class="tab-btn" onclick="openTab('receitas')">
                    <span class="material-icons text-sm align-middle mr-1">receipt</span> Receitas
                </button>
                <button type="button" class="tab-btn" onclick="openTab('anexos')">
                    <span class="material-icons text-sm align-middle mr-1">attach_file</span> Anexos / Docs
                </button>
            </div>

            <!-- TABS CONTENT -->

            <!-- TAB 1: PRONTUÁRIO -->
            <div id="tab-prontuario" class="tab-content">
                <form method="POST" class="max-w-5xl mx-auto">
                    <input type="hidden" name="id_pet" value="<?= $id_pet_pre ?>">

                    <div class="flex justify-end mb-4 sticky top-4 z-10">
                        <button type="submit"
                            class="bg-cyan-600 hover:bg-cyan-700 text-white font-bold py-3 px-8 rounded-lg shadow-lg flex items-center transition transform hover:scale-105">
                            <span class="material-icons mr-2">save</span> Salvar Prontuário
                        </button>
                    </div>

                    <!-- Info Basics -->
                    <div class="form-section">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label class="block text-gray-700 font-medium mb-1">Data/Hora</label>
                                <input type="datetime-local" name="data_atendimento" value="<?= $data_atendimento ?>"
                                    required class="w-full border-gray-300 rounded-lg p-3 border">
                            </div>
                            <div>
                                <label class="block text-gray-700 font-medium mb-1">Veterinário Responsável</label>
                                <select name="id_veterinario" required
                                    class="w-full border-gray-300 rounded-lg p-3 border bg-white">
                                    <option value="">Selecione...</option>
                                    <?php foreach ($veterinarios as $v): ?>
                                        <option value="<?= $v['id_vet'] ?>" <?= $id_veterinario == $v['id_vet'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($v['nome']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-gray-700 font-medium mb-1">Peso Atual (kg)</label>
                                <input type="number" step="0.01" name="peso" value="<?= $peso ?>"
                                    class="w-full border-gray-300 rounded-lg p-3 border" placeholder="0.00">
                            </div>
                        </div>
                    </div>

                    <!-- Anamnesis -->
                    <div class="form-section">
                        <h3 class="section-title"><span class="material-icons text-blue-500 mr-2">question_answer</span>
                            Anamnese / Queixa Principal</h3>
                        <div class="mb-4">
                            <label class="block text-gray-700 font-medium mb-1">Motivo da Visita</label>
                            <input type="text" name="motivo" value="<?= htmlspecialchars($motivo) ?>"
                                class="w-full border-gray-300 rounded-lg p-3 border"
                                placeholder="Ex: Vômito, check-up, vacina...">
                        </div>
                        <div>
                            <label class="block text-gray-700 font-medium mb-1">Histórico / Anamnese Detalhada</label>
                            <textarea name="anamnese" rows="4" class="w-full border-gray-300 rounded-lg p-3 border"
                                placeholder="Descreva o histórico relatado pelo tutor..."><?= htmlspecialchars($anamnese) ?></textarea>
                        </div>
                    </div>

                    <!-- Physical Exam -->
                    <div class="form-section">
                        <h3 class="section-title"><span
                                class="material-icons text-purple-500 mr-2">accessibility_new</span> Exame Físico</h3>
                        <textarea name="exame_fisico" rows="4" class="w-full border-gray-300 rounded-lg p-3 border"
                            placeholder="FC, FR, TPC, Mucosas, Palpação, Ausculta..."><?= htmlspecialchars($exame_fisico) ?></textarea>
                    </div>

                    <!-- Diagnosis & Treatment -->
                    <div class="form-section">
                        <h3 class="section-title"><span
                                class="material-icons text-green-500 mr-2">medical_services</span> Diagnóstico e Conduta
                        </h3>
                        <div class="mb-4">
                            <label class="block text-gray-700 font-medium mb-1">Suspeita / Diagnóstico</label>
                            <input type="text" name="diagnostico" value="<?= htmlspecialchars($diagnostico) ?>"
                                class="w-full border-gray-300 rounded-lg p-3 border">
                        </div>
                        <div>
                            <label class="block text-gray-700 font-medium mb-1">Tratamento Clínico (Texto Livre)</label>
                            <textarea name="prescricao" rows="4"
                                class="w-full border-gray-300 rounded-lg p-3 border font-mono text-sm"
                                placeholder="Resumo do tratamento... (Use a aba 'Receitas' para gerar prescrição formal)"><?= htmlspecialchars($tratamento) ?></textarea>
                            <p class="text-xs text-gray-500 mt-1">Dica: Use a aba <b>Receitas</b> acima para criar
                                receitas estruturadas para impressão.</p>
                        </div>
                    </div>

                    <!-- Internal Notes -->
                    <div class="form-section bg-yellow-50 border-yellow-100">
                        <h3 class="section-title"><span class="material-icons text-yellow-600 mr-2">lock</span>
                            Anotações Internas (Não sai na receita)</h3>
                        <textarea name="obs_internas" rows="2"
                            class="w-full border-yellow-200 bg-white rounded-lg p-3 border"><?= htmlspecialchars($obs_internas) ?></textarea>
                    </div>
                </form>
            </div>

            <!-- TAB 2: RECEITAS -->
            <div id="tab-receitas" class="tab-content hidden max-w-5xl mx-auto">
                <?php if (!$id_atendimento): ?>
                    <div class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4" role="alert">
                        <p class="font-bold">Atenção</p>
                        <p>Salve o atendimento pela primeira vez para criar receitas.</p>
                    </div>
                <?php else: ?>
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-xl font-bold text-gray-800">Receitas Emitidas</h3>
                        <button onclick="novaReceita()"
                            class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded flex items-center">
                            <span class="material-icons mr-2">add_circle</span> Nova Receita
                        </button>
                    </div>

                    <div id="lista-receitas" class="grid grid-cols-1 gap-4">
                        <!-- Carregado via AJAX -->
                    </div>
                <?php endif; ?>
            </div>

            <!-- TAB 3: ANEXOS -->
            <div id="tab-anexos" class="tab-content hidden max-w-5xl mx-auto">
                <?php if (!$id_atendimento): ?>
                    <div class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4" role="alert">
                        <p class="font-bold">Atenção</p>
                        <p>Salve o atendimento pela primeira vez para anexar arquivos.</p>
                    </div>
                <?php else: ?>
                    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                        <h3 class="text-xl font-bold text-gray-800 mb-4">Arquivos e Documentos</h3>

                        <div class="border border-gray-200 rounded-lg p-6 bg-gray-50 mb-6">
                            <h4 class="font-bold text-gray-700 mb-3">Novo Arquivo</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Descrição do Arquivo
                                        (Opcional)</label>
                                    <input type="text" id="arquivo-desc" class="w-full border-gray-300 rounded p-2 border"
                                        placeholder="Ex: Exame de Sangue, Raio-X...">
                                </div>
                                <div class="flex items-end">
                                    <input type="file" id="input-arquivo-upload" class="hidden" onchange="updateFileName()">
                                    <button onclick="document.getElementById('input-arquivo-upload').click()"
                                        class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded shadow-sm hover:bg-gray-50 mr-2 w-full text-center">
                                        <span class="material-icons text-sm align-middle">folder_open</span> Selecionar
                                        Arquivo
                                    </button>
                                </div>
                            </div>
                            <div id="file-name-display" class="text-sm text-gray-500 mt-2 italic hidden"></div>

                            <div class="mt-4 flex justify-end">
                                <button onclick="uploadArquivo()"
                                    class="bg-cyan-600 text-white px-6 py-2 rounded shadow hover:bg-cyan-700 font-bold flex items-center">
                                    <span class="material-icons text-sm mr-2">cloud_upload</span> Enviar Arquivo
                                </button>
                            </div>
                            <p id="upload-status" class="text-sm text-gray-400 mt-2 text-right"></p>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Arquivo</th>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Descrição</th>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Data</th>
                                        <th
                                            class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Ações</th>
                                    </tr>
                                </thead>
                                <tbody id="lista-arquivos" class="bg-white divide-y divide-gray-200">
                                    <!-- Carregado via AJAX -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

        </main>
    </div>

    <!-- MODAL NOVA RECEITA -->
    <div id="modal-receita" class="fixed inset-0 z-50 overflow-y-auto hidden" aria-labelledby="modal-title"
        role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"
                onclick="toggleModalReceita(false)"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div
                class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="flex justify-between items-center mb-4 border-b pb-2">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">Nova Receita</h3>
                        <button onclick="toggleModalReceita(false)" class="text-gray-400 hover:text-gray-500"><span
                                class="material-icons">close</span></button>
                    </div>

                    <!-- Form Receita -->
                    <div class="grid grid-cols-1 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Observações Gerais</label>
                            <input type="text" id="receita-obs"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm p-2 border"
                                placeholder="Ex: Uso contínuo por 10 dias...">
                        </div>

                        <!-- ADD ITEM FORM -->
                        <div class="bg-gray-50 p-4 rounded-md border border-gray-200">
                            <h4 class="text-sm font-bold text-gray-700 mb-2">Adicionar Item</h4>
                            <div class="grid grid-cols-1 md:grid-cols-12 gap-2">
                                <div class="md:col-span-4">
                                    <input type="text" id="item-nome" placeholder="Medicamento (Ex: Dipirona)"
                                        class="w-full border-gray-300 rounded p-2 text-sm border">
                                </div>
                                <div class="md:col-span-2">
                                    <input type="text" id="item-qtd" placeholder="Qtd (Ex: 1 frasco)"
                                        class="w-full border-gray-300 rounded p-2 text-sm border">
                                </div>
                                <div class="md:col-span-2">
                                    <select id="item-uso" class="w-full border-gray-300 rounded p-2 text-sm border">
                                        <option value="Oral">Oral</option>
                                        <option value="Topico">Tópico</option>
                                        <option value="Injetavel">Injetável</option>
                                        <option value="Oftalmico">Oftálmico</option>
                                        <option value="Otologico">Otológico</option>
                                    </select>
                                </div>
                                <div class="md:col-span-3">
                                    <input type="text" id="item-pos" placeholder="Posologia (Ex: 1 gota a cada 12h)"
                                        class="w-full border-gray-300 rounded p-2 text-sm border">
                                </div>
                                <div class="md:col-span-1 flex items-end">
                                    <button onclick="adicionarItemReceita()"
                                        class="w-full bg-green-600 hover:bg-green-700 text-white rounded p-2 flex justify-center"><span
                                            class="material-icons text-sm">add</span></button>
                                </div>
                            </div>
                        </div>

                        <!-- LISTA ITENS -->
                        <div class="border rounded-md overflow-hidden">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Medicamento
                                        </th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Qtd</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Uso</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Posologia</th>
                                        <th class="px-3 py-2"></th>
                                    </tr>
                                </thead>
                                <tbody id="tabela-itens-receita" class="bg-white divide-y divide-gray-200">
                                    <!-- JS Items -->
                                </tbody>
                            </table>
                            <div id="empty-itens" class="p-4 text-center text-gray-400 text-sm">Nenhum item adicionado
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button" onclick="salvarReceita()"
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-cyan-600 text-base font-medium text-white hover:bg-cyan-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">
                        Salvar e Gerar PDF
                    </button>
                    <button type="button" onclick="toggleModalReceita(false)"
                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        const ID_ATENDIMENTO = "<?= $id_atendimento ?>"; // Pode ser vazio
        const BASE_URL = "../../app.php";

        // --- TABS LOGIC ---
        function openTab(tabName) {
            $('.tab-content').addClass('hidden');
            $('#tab-' + tabName).removeClass('hidden');
            $('.tab-btn').removeClass('active');
            $('.tab-btn[onclick="openTab(\'' + tabName + '\')"]').addClass('active');

            if (tabName === 'receitas' && ID_ATENDIMENTO) carregarReceitas();
            if (tabName === 'anexos' && ID_ATENDIMENTO) carregarArquivos();
        }

        // --- ANEXOS ---
        function updateFileName() {
            let input = document.getElementById('input-arquivo-upload');
            if (input.files.length > 0) {
                $('#file-name-display').text('Selecionado: ' + input.files[0].name).removeClass('hidden');
            } else {
                $('#file-name-display').addClass('hidden').text('');
            }
        }

        function carregarArquivos() {
            $.post(BASE_URL, { action: 'get_atendimento_arquivos', id_atendimento: ID_ATENDIMENTO }, function (res) {
                try {
                    res = typeof res === 'string' ? JSON.parse(res) : res;
                    if (res.success) {
                        let html = '';
                        if (res.data.length === 0) {
                            html = '<tr><td colspan="4" class="px-6 py-4 text-center text-gray-500">Nenhum arquivo anexado.</td></tr>';
                        } else {
                            res.data.forEach(arq => {
                                html += `
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <a href="${arq.url_publica}" target="_blank" class="text-indigo-600 hover:text-indigo-900 font-medium flex items-center">
                                                <span class="material-icons text-sm mr-2">description</span> ${arq.nome_original}
                                            </a>
                                            <span class="text-xs text-gray-400">${(arq.tamanho_bytes / 1024).toFixed(1)} KB</span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">${arq.descricao || '-'}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${new Date(arq.data_upload).toLocaleString('pt-BR')}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <button onclick="excluirArquivo(${arq.id_arquivo})" class="text-red-600 hover:text-red-900 px-2">Excluir</button>
                                        </td>
                                    </tr>
                                `;
                            });
                        }
                        $('#lista-arquivos').html(html);
                    }
                } catch (e) { console.error(e); }
            });
        }

        function uploadArquivo() {
            let fileInput = document.getElementById('input-arquivo-upload');
            if (fileInput.files.length === 0) {
                alert('Selecione um arquivo primeiro.');
                return;
            }

            let file = fileInput.files[0];
            let desc = $('#arquivo-desc').val();
            let formData = new FormData();
            formData.append('action', 'upload_arquivo_atendimento');
            formData.append('id_atendimento', ID_ATENDIMENTO);
            formData.append('arquivo', file);
            formData.append('descricao', desc);

            $('#upload-status').text('Enviando...').removeClass('text-red-500').addClass('text-blue-500');

            $.ajax({
                url: BASE_URL,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (res) {
                    try {
                        res = typeof res === 'string' ? JSON.parse(res) : res;
                        if (res.success) {
                            $('#upload-status').text('Arquivo enviado com sucesso!').addClass('text-green-600').removeClass('text-blue-500');
                            carregarArquivos();
                            // Reset form
                            fileInput.value = '';
                            $('#arquivo-desc').val('');
                            $('#file-name-display').addClass('hidden');
                            setTimeout(() => $('#upload-status').text(''), 3000);
                        } else {
                            $('#upload-status').text('Erro: ' + res.message).addClass('text-red-500');
                        }
                    } catch (e) {
                        $('#upload-status').text('Erro no servidor.').addClass('text-red-500');
                    }
                },
                error: function () {
                    $('#upload-status').text('Falha na conexão.').addClass('text-red-500');
                }
            });
        }

        function excluirArquivo(id) {
            if (!confirm('Tem certeza que deseja excluir este arquivo?')) return;
            $.post(BASE_URL, { action: 'excluir_arquivo_atendimento', id_arquivo: id }, function (res) {
                res = typeof res === 'string' ? JSON.parse(res) : res;
                if (res.success) {
                    carregarArquivos();
                } else {
                    alert('Erro: ' + res.message);
                }
            });
        }

        // --- RECEITAS ---
        let itensReceita = [];
        let idReceitaEdicao = ''; // Guarda ID se estiver editando

        function toggleModalReceita(show) {
            if (show) {
                $('#modal-receita').removeClass('hidden');
                // Se não for edição (chamado pelo botão Nova Receita), limpa tudo
                // Se for edição, o editarReceita já setou as vars, então não faz nada ou apenas show
            } else {
                $('#modal-receita').addClass('hidden');
            }
        }

        function novaReceita() {
            idReceitaEdicao = '';
            itensReceita = [];
            $('#receita-obs').val('');
            $('#item-nome').val('');
            $('#item-qtd').val('');
            $('#item-pos').val('');
            $('#modal-title').text('Nova Receita');
            renderItensReceita();
            toggleModalReceita(true);
        }

        function adicionarItemReceita() {
            let nome = $('#item-nome').val();
            let qtd = $('#item-qtd').val();
            let uso = $('#item-uso').val();
            let pos = $('#item-pos').val();

            if (!nome || !qtd || !pos) {
                alert('Preencha Medicamento, Qtd e Posologia');
                return;
            }

            itensReceita.push({
                nome_medicamento: nome,
                quantidade: qtd,
                uso: uso,
                posologia: pos,
                categoria: 'Veterinaria'
            });

            $('#item-nome').val('').focus();
            $('#item-qtd').val('');
            $('#item-pos').val('');
            renderItensReceita();
        }

        function renderItensReceita() {
            let html = '';
            if (itensReceita.length === 0) {
                $('#empty-itens').show();
            } else {
                $('#empty-itens').hide();
                itensReceita.forEach((item, idx) => {
                    html += `
                        <tr>
                            <td class="px-3 py-2 text-sm text-gray-900">${item.nome_medicamento}</td>
                            <td class="px-3 py-2 text-sm text-gray-500">${item.quantidade}</td>
                            <td class="px-3 py-2 text-sm text-gray-500">${item.uso}</td>
                            <td class="px-3 py-2 text-sm text-gray-500">${item.posologia}</td>
                            <td class="px-3 py-2 text-right">
                                <button onclick="removerItemReceita(${idx})" class="text-red-500 hover:text-red-700 font-bold">×</button>
                            </td>
                        </tr>
                    `;
                });
            }
            $('#tabela-itens-receita').html(html);
        }

        function removerItemReceita(idx) {
            itensReceita.splice(idx, 1);
            renderItensReceita();
        }

        function salvarReceita() {
            if (itensReceita.length === 0) {
                alert('Adicione ao menos um item.');
                return;
            }

            let obs = $('#receita-obs').val();

            $.post(BASE_URL, {
                action: 'salvar_receita',
                id_atendimento: ID_ATENDIMENTO,
                id_receita: idReceitaEdicao, // Passa ID se for edição
                observacoes: obs,
                itens: JSON.stringify(itensReceita)
            }, function (res) {
                res = typeof res === 'string' ? JSON.parse(res) : res;
                if (res.success) {
                    toggleModalReceita(false);
                    carregarReceitas();
                    alert(res.message);
                } else {
                    alert('Erro ao salvar receita: ' + res.message);
                }
            });
        }

        function carregarReceitas() {
            $.post(BASE_URL, { action: 'get_receitas_atendimento', id_atendimento: ID_ATENDIMENTO }, function (res) {
                res = typeof res === 'string' ? JSON.parse(res) : res;
                if (res.success) {
                    let html = '';
                    if (res.data.length === 0) html = '<p class="text-gray-500 text-center py-4">Nenhuma receita emitida.</p>';
                    else {
                        res.data.forEach(r => {
                            // Serialize to pass to edit function
                            let jsonR = JSON.stringify(r).replace(/"/g, '&quot;');
                            let itensHtml = r.itens.map(i => `<li><b>${i.nome_medicamento}</b> (${i.quantidade}) - ${i.uso}: ${i.posologia}</li>`).join('');
                            html += `
                                <div class="bg-white border rounded-lg p-4 shadow-sm mb-4">
                                    <div class="flex justify-between border-b pb-2 mb-2">
                                        <span class="font-bold text-lg text-gray-800">Receita #${r.id_receita}</span>
                                        <span class="text-sm text-gray-500">${new Date(r.data_receita).toLocaleString('pt-BR')}</span>
                                    </div>
                                    <ul class="list-disc list-inside text-gray-700 text-sm mb-2">
                                        ${itensHtml}
                                    </ul>
                                    ${r.observacoes ? `<p class="text-sm text-gray-500 italic mb-2">Obs: ${r.observacoes}</p>` : ''}
                                    <div class="flex justify-end space-x-2">
                                        <button onclick="editarReceita(${jsonR})" class="text-blue-600 hover:underline text-sm font-bold flex items-center"><span class="material-icons text-sm mr-1">edit</span> Editar</button>
                                        <button onclick="window.open('../../modules/Vet/receita_print.php?id=${r.id_receita}', '_blank')" class="text-cyan-600 hover:underline text-sm font-bold flex items-center"><span class="material-icons text-sm mr-1">print</span> Imprimir</button>
                                        <button onclick="excluirReceita(${r.id_receita})" class="text-red-600 hover:underline text-sm font-bold flex items-center"><span class="material-icons text-sm mr-1">delete</span> Excluir</button>
                                    </div>
                                </div>
                            `;
                        });
                    }
                    $('#lista-receitas').html(html);
                }
            });
        }

        function editarReceita(r) {
            idReceitaEdicao = r.id_receita;
            $('#receita-obs').val(r.observacoes);
            itensReceita = r.itens.map(i => ({
                nome_medicamento: i.nome_medicamento,
                quantidade: i.quantidade,
                uso: i.uso,
                posologia: i.posologia,
                categoria: i.categoria
            }));
            $('#modal-title').text('Editar Receita #' + r.id_receita);
            renderItensReceita();
            toggleModalReceita(true);
        }

        function excluirReceita(id) {
            if (!confirm('Deseja excluir esta receita?')) return;
            $.post(BASE_URL, { action: 'excluir_receita', id_receita: id }, function (res) {
                res = typeof res === 'string' ? JSON.parse(res) : res;
                if (res.success) carregarReceitas();
                else alert('Erro: ' + res.message);
            });
        }
    </script>
</body>

</html>