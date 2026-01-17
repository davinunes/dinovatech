<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}
include "../database.php";

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
        $id_veterinario = $atendimento['id_veterinario'];
        $motivo = $atendimento['motivo_visita'];
        $anamnese = $atendimento['anamnese'];
        $exame_fisico = $atendimento['exame_fisico'];
        $diagnostico = $atendimento['diagnostico'];
        $tratamento = $atendimento['prescricao'];
        $obs_internas = $atendimento['obs_internas'];
        $peso = $atendimento['peso_atual'];
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
    // Validate required fields
    if (empty($id_pet) || empty($id_veterinario) || empty($data)) {
        die("Erro: Campos obrigatórios não preenchidos (Pet, Veterinário, Data).");
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
        $q = "UPDATE Atendimentos SET id_veterinario=$id_veterinario, data_atendimento='$data_safe', motivo_visita='$motivo', 
              anamnese='$anamnese', exame_fisico='$exame', diagnostico='$diag', prescricao='$presc', obs_internas='$obs', peso_atual=$peso_safe
              WHERE id_atendimento=$id_atendimento";
    } else {
        $q = "INSERT INTO Atendimentos (id_pet, id_veterinario, data_atendimento, motivo_visita, anamnese, exame_fisico, diagnostico, prescricao, obs_internas, peso_atual)
              VALUES ($id_pet, $id_veterinario, '$data_safe', '$motivo', '$anamnese', '$exame', '$diag', '$presc', '$obs', $peso_safe)";
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
    <?php include 'components/layout_head.php'; ?>
    <style>
        .form-section {
            @apply bg-white p-6 rounded-xl shadow-sm border border-gray-100 mb-6;
        }

        .section-title {
            @apply text-lg font-bold text-gray-800 mb-4 flex items-center;
        }
    </style>
</head>

<body class="bg-gray-50 flex">
    <?php include 'components/sidebar.php'; ?>

    <div class="flex-1 flex flex-col lg:ml-64 min-h-screen transition-all duration-300">
        <main class="flex-1 p-6 mt-16 lg:mt-0">

            <form method="POST" class="max-w-4xl mx-auto">
                <input type="hidden" name="id_pet" value="<?= $id_pet_pre ?>">

                <!-- Header -->
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center">
                        <a href="pet_detalhes.php?id=<?= $id_pet_pre ?>" class="mr-4 text-gray-500 hover:text-gray-700">
                            <span class="material-icons">arrow_back</span>
                        </a>
                        <div>
                            <h2 class="text-3xl font-bold text-gray-800">Atendimento Clínico</h2>
                            <p class="text-gray-500">Paciente: <b>
                                    <?= htmlspecialchars($pet['nome']) ?>
                                </b> (
                                <?= htmlspecialchars($pet['especie']) ?>)
                            </p>
                        </div>
                    </div>
                    <button type="submit"
                        class="bg-cyan-600 hover:bg-cyan-700 text-white font-bold py-3 px-8 rounded-lg shadow-lg flex items-center sticky top-4 z-10 transition transform hover:scale-105">
                        <span class="material-icons mr-2">save</span> Salvar Atendimento
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
                                    <option value="<?= $v['id_veterinario'] ?>" <?= $id_veterinario == $v['id_veterinario'] ? 'selected' : '' ?>>
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
                    <h3 class="section-title"><span class="material-icons text-purple-500 mr-2">accessibility_new</span>
                        Exame Físico</h3>
                    <textarea name="exame_fisico" rows="4" class="w-full border-gray-300 rounded-lg p-3 border"
                        placeholder="FC, FR, TPC, Mucosas, Palpação, Ausculta..."><?= htmlspecialchars($exame_fisico) ?></textarea>
                </div>

                <!-- Diagnosis & Treatment -->
                <div class="form-section">
                    <h3 class="section-title"><span class="material-icons text-green-500 mr-2">medical_services</span>
                        Diagnóstico e Conduta</h3>
                    <div class="mb-4">
                        <label class="block text-gray-700 font-medium mb-1">Suspeita / Diagnóstico</label>
                        <input type="text" name="diagnostico" value="<?= htmlspecialchars($diagnostico) ?>"
                            class="w-full border-gray-300 rounded-lg p-3 border">
                    </div>
                    <div>
                        <label class="block text-gray-700 font-medium mb-1">Prescrição / Tratamento Clínico</label>
                        <textarea name="prescricao" rows="6"
                            class="w-full border-gray-300 rounded-lg p-3 border font-mono text-sm" placeholder="Ex: 
1. Dipirona....... 1 gota/kg
2. Omeprazol...... 1 cp via oral"><?= htmlspecialchars($tratamento) ?></textarea>
                    </div>
                </div>

                <!-- Internal Notes -->
                <div class="form-section bg-yellow-50 border-yellow-100">
                    <h3 class="section-title"><span class="material-icons text-yellow-600 mr-2">lock</span> Anotações
                        Internas (Não sai na receita)</h3>
                    <textarea name="obs_internas" rows="2"
                        class="w-full border-yellow-200 bg-white rounded-lg p-3 border"><?= htmlspecialchars($obs_internas) ?></textarea>
                </div>

            </form>
        </main>
    </div>
</body>

</html>