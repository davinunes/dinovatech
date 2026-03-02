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
if (!$link) {
    die("Erro de conexão com o banco de dados.");
}

$id_pet = $_GET['id'] ?? null;
$is_edit = !empty($id_pet);
$erro = "";
$sucesso = "";

// Fetch Clients for Dropdown
$query_clientes = "SELECT id_cliente, nome, cpf_cnpj FROM Clientes WHERE ativo = 1 ORDER BY nome ASC";
$res_clientes = DBExecute($link, $query_clientes);
$clientes = [];
while ($r = mysqli_fetch_assoc($res_clientes)) {
    $clientes[] = $r;
}

// Form Data Defaults
$nome = '';
$id_cliente = '';
$especie = 'Canino';
$raca = '';
$sexo = 'M';
$data_nascimento = '';
$peso = '';
$chip_id = '';
$obs = '';

// Load Existing Data
if ($is_edit) {
    $id_pet_safe = mysqli_real_escape_string($link, $id_pet);
    $query = "SELECT * FROM Pets WHERE id_pet = '$id_pet_safe'";
    $result = DBExecute($link, $query);
    if ($result && mysqli_num_rows($result) > 0) {
        $pet = mysqli_fetch_assoc($result);
        $nome = $pet['nome'];
        $id_cliente = $pet['id_cliente'];
        $especie = $pet['especie'];
        $raca = $pet['raca'];
        $sexo = $pet['sexo'];
        $data_nascimento = $pet['data_nascimento'];
        $peso = $pet['peso'];
        $chip_id = $pet['chip_id'];
        $obs = $pet['obs'];
    } else {
        $erro = "Pet não encontrado.";
        $is_edit = false;
    }
} else {
    // Check if client_id is passed in URL (e.g. from Client Details)
    if (isset($_GET['client_id'])) {
        $id_cliente = $_GET['client_id'];
    }
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'] ?? '';
    $id_cliente = $_POST['id_cliente'] ?? '';
    $especie = $_POST['especie'] ?? '';
    $raca = $_POST['raca'] ?? '';
    $sexo = $_POST['sexo'] ?? '';
    $data_nascimento = $_POST['data_nascimento'] ?: NULL;
    $peso = $_POST['peso'] ?: NULL;
    $chip_id = $_POST['chip_id'] ?? '';
    $obs = $_POST['obs'] ?? '';

    // Validation
    if (empty($nome) || empty($id_cliente) || empty($especie)) {
        $erro = "Preencha os campos obrigatórios (Nome, Tutor, Espécie).";
    } else {
        $nome_safe = mysqli_real_escape_string($link, $nome);
        $id_cliente_int = (int) $id_cliente;
        $especie_safe = mysqli_real_escape_string($link, $especie);
        $raca_safe = mysqli_real_escape_string($link, $raca);
        $sexo_safe = mysqli_real_escape_string($link, $sexo);
        $data_nascimento_val = $data_nascimento ? "'$data_nascimento'" : "NULL";
        $peso_val = $peso ? "'$peso'" : "NULL";
        $chip_id_safe = mysqli_real_escape_string($link, $chip_id);
        $obs_safe = mysqli_real_escape_string($link, $obs);

        if ($is_edit) {
            $query = "UPDATE Pets SET 
                id_cliente = $id_cliente_int,
                nome = '$nome_safe',
                especie = '$especie_safe',
                raca = '$raca_safe',
                sexo = '$sexo_safe',
                data_nascimento = $data_nascimento_val,
                peso = $peso_val,
                chip_id = '$chip_id_safe',
                obs = '$obs_safe'
                WHERE id_pet = " . (int) $id_pet;
        } else {
            $query = "INSERT INTO Pets (id_cliente, nome, especie, raca, sexo, data_nascimento, peso, chip_id, obs) VALUES 
                ($id_cliente_int, '$nome_safe', '$especie_safe', '$raca_safe', '$sexo_safe', $data_nascimento_val, $peso_val, '$chip_id_safe', '$obs_safe')";
        }

        if (DBExecute($link, $query)) {
            // Redirect
            $new_id = $is_edit ? $id_pet : mysqli_insert_id($link);
            header("Location: pet_detalhes.php?id=" . $new_id . "&msg=saved");
            exit();
        } else {
            $erro = "Erro ao salvar: " . mysqli_error($link);
        }
    }
}

DBClose($link);
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <title>
        <?= $is_edit ? 'Editar Pet' : 'Novo Pet' ?> - DinoVet
    </title>
    <?php include '../../components/layout_head.php'; ?>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <style>
        .select2-container .select2-selection--single {
            height: 42px;
            border-color: #e5e7eb;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            top: 8px;
        }
    </style>
</head>

<body class="bg-gray-50 flex">
    <?php include '../../components/sidebar.php'; ?>

    <div class="flex-1 flex flex-col lg:ml-64 min-h-screen transition-all duration-300">
        <main class="flex-1 p-6 mt-16 lg:mt-0">
            <div class="max-w-4xl mx-auto">
                <a href="pets.php" class="text-gray-500 hover:text-gray-700 mb-4 inline-flex items-center">
                    <span class="material-icons mr-1">arrow_back</span> Voltar para Pets
                </a>

                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-6 border-b border-gray-100">
                        <h2 class="text-2xl font-bold text-gray-800">
                            <?= $is_edit ? 'Editar Pet' : 'Novo Pet' ?>
                        </h2>
                    </div>

                    <?php if ($erro): ?>
                        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 m-6" role="alert">
                            <p>
                                <?= $erro ?>
                            </p>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                            <!-- Nome -->
                            <div class="col-span-1 md:col-span-2">
                                <label class="block text-gray-700 font-medium mb-2">Nome do Paciente *</label>
                                <input type="text" name="nome" value="<?= htmlspecialchars($nome) ?>" required
                                    class="w-full border-gray-300 rounded-lg shadow-sm focus:border-cyan-500 focus:ring focus:ring-cyan-200 focus:ring-opacity-50 p-2 border">
                            </div>

                            <!-- Tutor -->
                            <div class="col-span-1 md:col-span-2">
                                <label class="block text-gray-700 font-medium mb-2">Tutor (Cliente) *</label>
                                <select name="id_cliente" class="w-full select2-search" required>
                                    <option value="">Selecione um tutor...</option>
                                    <?php foreach ($clientes as $c): ?>
                                        <option value="<?= $c['id_cliente'] ?>" <?= $id_cliente == $c['id_cliente'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($c['nome']) ?> (
                                            <?= $c['cpf_cnpj'] ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Espécie -->
                            <div>
                                <label class="block text-gray-700 font-medium mb-2">Espécie *</label>
                                <select name="especie"
                                    class="w-full border-gray-300 rounded-lg shadow-sm focus:border-cyan-500 focus:ring focus:ring-cyan-200 focus:ring-opacity-50 p-2 border"
                                    required>
                                    <option value="Canino" <?= $especie == 'Canino' ? 'selected' : '' ?>>Canino (Cachorro)
                                    </option>
                                    <option value="Felino" <?= $especie == 'Felino' ? 'selected' : '' ?>>Felino (Gato)
                                    </option>
                                    <option value="Ave" <?= $especie == 'Ave' ? 'selected' : '' ?>>Ave</option>
                                    <option value="Roedor" <?= $especie == 'Roedor' ? 'selected' : '' ?>>Roedor</option>
                                    <option value="Outros" <?= $especie == 'Outros' ? 'selected' : '' ?>>Outros</option>
                                </select>
                            </div>

                            <!-- Raça -->
                            <div>
                                <label class="block text-gray-700 font-medium mb-2">Raça</label>
                                <input type="text" name="raca" value="<?= htmlspecialchars($raca) ?>"
                                    placeholder="Ex: Vira-lata, Poodle..."
                                    class="w-full border-gray-300 rounded-lg shadow-sm focus:border-cyan-500 focus:ring focus:ring-cyan-200 focus:ring-opacity-50 p-2 border">
                            </div>

                            <!-- Sexo -->
                            <div>
                                <label class="block text-gray-700 font-medium mb-2">Sexo</label>
                                <div class="flex gap-4 pt-2">
                                    <label class="inline-flex items-center">
                                        <input type="radio" name="sexo" value="M" <?= $sexo == 'M' ? 'checked' : '' ?>
                                            class="form-radio text-cyan-600">
                                        <span class="ml-2">Macho</span>
                                    </label>
                                    <label class="inline-flex items-center">
                                        <input type="radio" name="sexo" value="F" <?= $sexo == 'F' ? 'checked' : '' ?>
                                            class="form-radio text-pink-600">
                                        <span class="ml-2">Fêmea</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Data Nascimento -->
                            <div>
                                <label class="block text-gray-700 font-medium mb-2">Data Nascimento</label>
                                <input type="date" name="data_nascimento" value="<?= $data_nascimento ?>"
                                    class="w-full border-gray-300 rounded-lg shadow-sm focus:border-cyan-500 focus:ring focus:ring-cyan-200 focus:ring-opacity-50 p-2 border">
                            </div>

                            <!-- Peso -->
                            <div>
                                <label class="block text-gray-700 font-medium mb-2">Peso Atual (kg)</label>
                                <input type="number" step="0.01" name="peso" value="<?= $peso ?>" placeholder="0.00"
                                    class="w-full border-gray-300 rounded-lg shadow-sm focus:border-cyan-500 focus:ring focus:ring-cyan-200 focus:ring-opacity-50 p-2 border">
                            </div>

                            <!-- Chip ID -->
                            <div>
                                <label class="block text-gray-700 font-medium mb-2">Microchip ID</label>
                                <input type="text" name="chip_id" value="<?= htmlspecialchars($chip_id) ?>"
                                    class="w-full border-gray-300 rounded-lg shadow-sm focus:border-cyan-500 focus:ring focus:ring-cyan-200 focus:ring-opacity-50 p-2 border">
                            </div>

                            <!-- Obs -->
                            <div class="col-span-1 md:col-span-2">
                                <label class="block text-gray-700 font-medium mb-2">Observações /
                                    Características</label>
                                <textarea name="obs" rows="3"
                                    class="w-full border-gray-300 rounded-lg shadow-sm focus:border-cyan-500 focus:ring focus:ring-cyan-200 focus:ring-opacity-50 p-2 border"><?= htmlspecialchars($obs) ?></textarea>
                            </div>

                        </div>

                        <div class="mt-8 flex justify-end gap-3">
                            <a href="pets.php"
                                class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium py-2 px-6 rounded-lg transition-colors">Cancelar</a>
                            <button type="submit"
                                class="bg-cyan-600 hover:bg-cyan-700 text-white font-medium py-2 px-6 rounded-lg shadow-md transition-all">Salvar
                                Pet</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script>
        $(document).ready(function () {
            $('.select2-search').select2({
                placeholder: "Selecione um tutor...",
                allowClear: true,
                language: {
                    noResults: function () {
                        return "Nenhum cliente encontrado";
                    }
                }
            });
        });
    </script>
</body>

</html>