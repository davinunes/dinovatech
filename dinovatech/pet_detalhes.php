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

// Connection kept open for lists below
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
                            <button onclick="openVacinaModal()"
                                class="text-cyan-600 hover:bg-cyan-50 p-1 rounded transition" title="Registrar Vacina">
                                <span class="material-icons">add</span>
                            </button>
                        </div>

                        <div class="divide-y divide-gray-50">
                            <?php
                            // Fetch Vaccines
                            $query_vacinas = "SELECT cv.*, v.nome as nome_vacina, v.recorrencia_dias FROM CarteiraVacinas cv 
                                              JOIN Vacinas v ON cv.id_vacina = v.id_vacina 
                                              WHERE cv.id_pet = '$id_safe' 
                                              ORDER BY cv.data_aplicacao DESC";
                            $res_vacinas = DBExecute($link, $query_vacinas);
                            if ($res_vacinas && mysqli_num_rows($res_vacinas) > 0):
                                while ($vac = mysqli_fetch_assoc($res_vacinas)):
                                    $vencida = $vac['data_vencimento'] && $vac['data_vencimento'] < date('Y-m-d');
                                    ?>
                                    <div class="p-4 hover:bg-gray-50 transition">
                                        <div class="flex justify-between items-start">
                                            <span
                                                class="font-bold text-gray-700"><?= htmlspecialchars($vac['nome_vacina']) ?></span>
                                            <span
                                                class="text-xs text-gray-400"><?= date('d/m/y', strtotime($vac['data_aplicacao'])) ?></span>
                                        </div>
                                        <?php if ($vac['data_vencimento']): ?>
                                            <div class="mt-1 flex items-center text-xs">
                                                <span
                                                    class="material-icons text-[14px] mr-1 <?= $vencida ? 'text-red-500' : 'text-green-500' ?>">event_repeat</span>
                                                <span class="<?= $vencida ? 'text-red-600 font-bold' : 'text-green-600' ?>">
                                                    Reforço: <?= date('d/m/Y', strtotime($vac['data_vencimento'])) ?>
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($vac['lote']): ?>
                                            <div class="text-xs text-gray-400 mt-1">Lote: <?= htmlspecialchars($vac['lote']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <?php
                                endwhile;
                            else:
                                ?>
                                <div class="p-8 text-center text-gray-400">
                                    <span class="material-icons text-4xl mb-2 opacity-30">medical_services</span>
                                    <p class="text-sm">Nenhuma vacina registrada.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Clinical History (Full Width on Mobile, 2 cols on Desktop) -->
                    <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="p-4 border-b border-gray-100 flex justify-between items-center">
                            <h3 class="font-bold text-gray-800 flex items-center">
                                <span class="material-icons text-blue-500 mr-2">history_edu</span> Histórico Clínico
                            </h3>
                            <a href="atendimento_form.php?pet_id=<?= $pet['id_pet'] ?>"
                                class="bg-cyan-600 text-white px-3 py-1.5 rounded-lg text-sm font-medium hover:bg-cyan-700 transition shadow-sm flex items-center">
                                <span class="material-icons text-sm mr-1">add</span> Novo Atendimento
                            </a>
                        </div>

                        <div class="divide-y divide-gray-50">
                            <?php
                            // Fetch Consultations
                            $query_atend = "SELECT a.*, v.nome as nome_vet FROM Atendimentos a 
                                            LEFT JOIN Veterinarios v ON a.id_vet = v.id_vet 
                                            WHERE a.id_pet = '$id_safe' 
                                            ORDER BY a.data_atendimento DESC";
                            $res_atend = DBExecute($link, $query_atend);

                            if ($res_atend && mysqli_num_rows($res_atend) > 0):
                                while ($atend = mysqli_fetch_assoc($res_atend)):
                                    ?>
                                    <div class="p-6 hover:bg-gray-50 transition block">
                                        <div class="flex flex-col sm:flex-row justify-between mb-2">
                                            <div class="flex items-center mb-2 sm:mb-0">
                                                <div
                                                    class="bg-blue-100 text-blue-600 w-10 h-10 rounded-full flex items-center justify-center mr-3 font-bold text-sm">
                                                    <?= date('d', strtotime($atend['data_atendimento'])) ?>
                                                </div>
                                                <div>
                                                    <h4 class="font-bold text-gray-800 text-lg">
                                                        <?= htmlspecialchars($atend['motivo_visita'] ?: 'Consulta de Rotina') ?>
                                                    </h4>
                                                    <span
                                                        class="text-xs text-gray-500"><?= date('M/Y', strtotime($atend['data_atendimento'])) ?>
                                                        • Dr(a). <?= htmlspecialchars($atend['nome_vet']) ?></span>
                                                </div>
                                            </div>
                                            <div>
                                                <a href="atendimento_form.php?id=<?= $atend['id_atendimento'] ?>&pet_id=<?= $pet['id_pet'] ?>"
                                                    class="text-gray-400 hover:text-cyan-600 transition">
                                                    <span class="material-icons">edit_note</span>
                                                </a>
                                            </div>
                                        </div>

                                        <div class="pl-12 text-sm text-gray-600 space-y-2">
                                            <?php if ($atend['diagnostico']): ?>
                                                <div
                                                    class="bg-red-50 text-red-800 px-3 py-1 inline-block rounded font-medium text-xs mb-1">
                                                    Dx: <?= htmlspecialchars($atend['diagnostico']) ?>
                                                </div>
                                            <?php endif; ?>

                                            <?php if ($atend['anamnese']): ?>
                                                <p><span class="font-semibold text-gray-700">Anamnese:</span>
                                                    <?= substr(htmlspecialchars($atend['anamnese']), 0, 100) . '...' ?></p>
                                            <?php endif; ?>

                                            <?php if ($atend['prescricao']): ?>
                                                <div
                                                    class="mt-2 p-3 bg-gray-100 rounded text-gray-700 font-mono text-xs border border-gray-200">
                                                    <?= nl2br(htmlspecialchars($atend['prescricao'])) ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php
                                endwhile;
                            else:
                                ?>
                                <div class="p-12 text-center text-gray-400">
                                    <span class="material-icons text-5xl mb-3 opacity-30">folder_open</span>
                                    <p class="font-medium">Nenhum atendimento registrado.</p>
                                    <p class="text-sm mt-2 max-w-xs mx-auto text-gray-400">Clique em "Novo Atendimento" para
                                        iniciar um prontuário.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>

            <?php endif; ?>
        </main>
    </div>
    <!-- Modal Registrar Vacina -->
    <div id="modalVacina" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 hidden">
        <div class="bg-white rounded-xl shadow-lg w-full max-w-md p-6">
            <h3 class="text-xl font-bold text-gray-800 mb-4">Registrar Vacina</h3>
            <form id="formVacina">
                <input type="hidden" name="action" value="register_vaccine">
                <input type="hidden" name="id_pet" value="<?= $id_pet ?>">

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Vacina *</label>
                    <select name="id_vacina" id="id_vacina" required class="w-full p-2 border rounded-lg"
                        onchange="calcularProxima()">
                        <option value="">Selecione...</option>
                        <?php
                        // Fetch available vaccines for dropdown
                        $link = DBConnect();
                        $q = "SELECT * FROM Vacinas ORDER BY nome ASC";
                        $r = DBExecute($link, $q);
                        while ($v = mysqli_fetch_assoc($r)):
                            ?>
                            <option value="<?= $v['id_vacina'] ?>" data-dias="<?= $v['recorrencia_dias'] ?>">
                                <?= htmlspecialchars($v['nome']) ?></option>
                        <?php endwhile;
                        DBClose($link); ?>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Data Aplicação *</label>
                        <input type="date" name="data_aplicacao" id="data_aplicacao" value="<?= date('Y-m-d') ?>"
                            required class="w-full p-2 border rounded-lg" onchange="calcularProxima()">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Próxima Dose</label>
                        <input type="date" name="data_proxima" id="data_proxima" class="w-full p-2 border rounded-lg">
                    </div>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Lote (Opcional)</label>
                    <input type="text" name="lote" class="w-full p-2 border rounded-lg">
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Observações</label>
                    <textarea name="observacoes" rows="2" class="w-full p-2 border rounded-lg"></textarea>
                </div>

                <div class="flex justify-end gap-2">
                    <button type="button" onclick="closeVacinaModal()"
                        class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">Cancelar</button>
                    <button type="submit"
                        class="px-4 py-2 bg-cyan-600 text-white rounded-lg hover:bg-cyan-700">Registrar</button>
                </div>
            </form>
        </div>
    </div>

    <?php include 'components/layout_scripts.php'; ?>
    <script>
        function openVacinaModal() {
            $('#modalVacina').removeClass('hidden');
        }
        function closeVacinaModal() {
            $('#modalVacina').addClass('hidden');
        }

        function calcularProxima() {
            const dias = $('#id_vacina option:selected').data('dias');
            const dataAplicacao = $('#data_aplicacao').val();

            if (dias && dataAplicacao) {
                const data = new Date(dataAplicacao);
                data.setDate(data.getDate() + parseInt(dias)); // Add days
                // Format YYYY-MM-DD
                const yyyy = data.getFullYear();
                const mm = String(data.getMonth() + 1).padStart(2, '0');
                const dd = String(data.getDate()).padStart(2, '0');
                $('#data_proxima').val(`${yyyy}-${mm}-${dd}`);
            }
        }

        $('#formVacina').on('submit', function (e) {
            e.preventDefault();
            $.ajax({
                url: 'app.php',
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Erro: ' + response.message);
                    }
                },
                error: function () {
                    alert('Erro de conexão ao salvar vacina.');
                }
            });
        });
    </script>
</body>

</html>