<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}
include "../database.php";

$id_pet = $_GET['id'] ?? null;
$pet = null;
$error_msg = "";

$link = DBConnect();

if ($id_pet) {
    if (!$link) {
        $error_msg = "Erro de conexão com o banco.";
    } else {
        $id_safe = mysqli_real_escape_string($link, $id_pet);

        // Fetch Pet + Tutor Info
        $query = "SELECT p.*, c.id_cliente, c.nome as nome_tutor, c.telefone as tel_tutor, c.email as email_tutor 
                  FROM Pets p 
                  JOIN Clientes c ON p.id_cliente = c.id_cliente 
                  WHERE p.id_pet = '$id_safe'";

        $result = DBExecute($link, $query);
        if ($result && mysqli_num_rows($result) > 0) {
            $pet = mysqli_fetch_assoc($result);
        } else {
            $error_msg = "Pet não encontrado.";
        }
    }
} else {
    $error_msg = "ID do pet não fornecido.";
}

// Age Calculator Helper
function calcularIdade($data_nasc)
{
    if (!$data_nasc)
        return "Desconhecida";
    $dob = new DateTime($data_nasc);
    $now = new DateTime();
    $diff = $now->diff($dob);

    $parts = [];
    if ($diff->y > 0)
        $parts[] = $diff->y . " ano" . ($diff->y > 1 ? 's' : '');
    if ($diff->m > 0)
        $parts[] = $diff->m . " mês" . ($diff->m > 1 ? 'es' : '');

    if (empty($parts))
        return "Menos de 1 mês";
    return implode(' e ', $parts);
}

DBClose($link);
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <title>Prontuário do Pet - DinoVet</title>
    <?php include 'components/layout_head.php'; ?>
</head>

<body class="bg-gray-50 flex">

    <?php include 'components/sidebar.php'; ?>

    <div class="flex-1 flex flex-col lg:ml-64 min-h-screen transition-all duration-300">
        <main class="flex-1 p-6 mt-16 lg:mt-0">

            <div class="flex items-center mb-6">
                <a href="pets.php" class="mr-4 text-gray-500 hover:text-gray-700">
                    <span class="material-icons">arrow_back</span>
                </a>
                <h2 class="text-3xl font-bold text-gray-800">Prontuário do Paciente</h2>
            </div>

            <?php if ($error_msg): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <strong class="font-bold">Erro!</strong>
                    <span>
                        <?= $error_msg ?>
                    </span>
                </div>
            <?php else: ?>

                <!-- Top Section: Pet Info & Tutor -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">

                    <!-- Pet Profile Card -->
                    <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="p-6 flex flex-col sm:flex-row gap-6">
                            <!-- Avatar Placeholder -->
                            <div
                                class="flex-shrink-0 flex items-center justify-center w-32 h-32 bg-cyan-100 rounded-full text-cyan-500">
                                <span class="material-icons text-6xl">pets</span>
                            </div>

                            <!-- Info -->
                            <div class="flex-1">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h1 class="text-3xl font-bold text-gray-800">
                                            <?= htmlspecialchars($pet['nome']) ?>
                                        </h1>
                                        <div class="flex gap-2 mt-1">
                                            <span
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 uppercase">
                                                <?= htmlspecialchars($pet['especie']) ?>
                                            </span>
                                            <span
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $pet['sexo'] == 'M' ? 'bg-blue-100 text-blue-800' : 'bg-pink-100 text-pink-800' ?>">
                                                <?= $pet['sexo'] == 'M' ? 'Macho' : 'Fêmea' ?>
                                            </span>
                                        </div>
                                    </div>
                                    <a href="pet_form.php?id=<?= $pet['id_pet'] ?>"
                                        class="text-gray-400 hover:text-cyan-600 p-2">
                                        <span class="material-icons">edit</span>
                                    </a>
                                </div>

                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 mt-6">
                                    <div>
                                        <span class="block text-xs font-medium text-gray-400 uppercase">Raça</span>
                                        <span class="block text-gray-700 font-medium">
                                            <?= htmlspecialchars($pet['raca'] ?: 'Não informada') ?>
                                        </span>
                                    </div>
                                    <div>
                                        <span class="block text-xs font-medium text-gray-400 uppercase">Idade</span>
                                        <span class="block text-gray-700 font-medium">
                                            <?= calcularIdade($pet['data_nascimento']) ?>
                                        </span>
                                    </div>
                                    <div>
                                        <span class="block text-xs font-medium text-gray-400 uppercase">Peso</span>
                                        <span class="block text-gray-700 font-medium">
                                            <?= $pet['peso'] ? number_format($pet['peso'], 2) . ' kg' : '-' ?>
                                        </span>
                                    </div>
                                    <div>
                                        <span class="block text-xs font-medium text-gray-400 uppercase">Microchip</span>
                                        <span class="block text-gray-700 font-medium tracking-wide">
                                            <?= htmlspecialchars($pet['chip_id'] ?: '-') ?>
                                        </span>
                                    </div>
                                </div>

                                <?php if ($pet['obs']): ?>
                                    <div class="mt-4 pt-4 border-t border-gray-50">
                                        <span class="block text-xs font-medium text-gray-400 uppercase mb-1">Observações</span>
                                        <p class="text-sm text-gray-600 bg-yellow-50 p-2 rounded border border-yellow-100">
                                            <?= nl2br(htmlspecialchars($pet['obs'])) ?>
                                        </p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Tutor Card -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden flex flex-col">
                        <div class="p-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                            <h3 class="font-bold text-gray-700 flex items-center">
                                <span class="material-icons text-gray-400 mr-2">person</span> Tutor Responsável
                            </h3>
                            <a href="cliente_detalhes.php?id=<?= $pet['id_cliente'] ?>"
                                class="text-xs font-medium text-cyan-600 hover:text-cyan-800">Ver Perfil</a>
                        </div>
                        <div class="p-6 flex-1 flex flex-col justify-center">
                            <div class="text-center mb-4">
                                <div
                                    class="w-16 h-16 bg-gray-200 rounded-full flex items-center justify-center mx-auto text-gray-500 mb-2">
                                    <span class="material-icons text-3xl">person</span>
                                </div>
                                <h4 class="text-lg font-bold text-gray-800">
                                    <?= htmlspecialchars($pet['nome_tutor']) ?>
                                </h4>
                            </div>
                            <div class="space-y-3">
                                <?php if ($pet['tel_tutor']): ?>
                                    <a href="https://wa.me/55<?= preg_replace('/\D/', '', $pet['tel_tutor']) ?>" target="_blank"
                                        class="flex items-center justify-center w-full py-2 bg-green-50 text-green-700 rounded-lg hover:bg-green-100 transition">
                                        <span class="material-icons text-sm mr-2">whatsapp</span>
                                        <?= htmlspecialchars($pet['tel_tutor']) ?>
                                    </a>
                                <?php endif; ?>
                                <?php if ($pet['email_tutor']): ?>
                                    <a href="mailto:<?= htmlspecialchars($pet['email_tutor']) ?>"
                                        class="flex items-center justify-center w-full py-2 bg-gray-50 text-gray-600 rounded-lg hover:bg-gray-100 transition">
                                        <span class="material-icons text-sm mr-2">email</span>
                                        Enviar Email
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabs / Sections - Future Phases -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                    <!-- Vacinas Column -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden h-fit">
                        <div class="p-4 border-b border-gray-100 flex justify-between items-center">
                            <h3 class="font-bold text-gray-800 flex items-center">
                                <span class="material-icons text-purple-500 mr-2">vaccines</span> Vacinas
                            </h3>
                            <button
                                class="text-cyan-600 hover:bg-cyan-50 p-1 rounded transition opacity-50 cursor-not-allowed"
                                title="Em breve">
                                <span class="material-icons">add</span>
                            </button>
                        </div>
                        <div class="p-8 text-center text-gray-400">
                            <span class="material-icons text-4xl mb-2 opacity-30">medical_services</span>
                            <p class="text-sm">Histórico de vacinas vazio.</p>
                            <p class="text-xs mt-1 text-gray-300">(Módulo em desenvolvimento)</p>
                        </div>
                    </div>

                    <!-- Clinical History (Full Width on Mobile, 2 cols on Desktop) -->
                    <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="p-4 border-b border-gray-100 flex justify-between items-center">
                            <h3 class="font-bold text-gray-800 flex items-center">
                                <span class="material-icons text-blue-500 mr-2">history_edu</span> Histórico Clínico
                            </h3>
                            <button
                                class="bg-cyan-600 text-white px-3 py-1.5 rounded-lg text-sm font-medium hover:bg-cyan-700 transition opacity-80 cursor-not-allowed"
                                title="Em breve">
                                Novo Atendimento
                            </button>
                        </div>
                        <div class="p-12 text-center text-gray-400">
                            <span class="material-icons text-5xl mb-3 opacity-30">folder_open</span>
                            <p class="font-medium">Nenhum atendimento registrado.</p>
                            <p class="text-sm mt-2 max-w-xs mx-auto text-gray-400">Os atendimentos clínicos, exames e
                                cirurgias aparecerão aqui em uma linha do tempo.</p>
                        </div>
                    </div>

                </div>

            <?php endif; ?>
        </main>
    </div>
</body>

</html>