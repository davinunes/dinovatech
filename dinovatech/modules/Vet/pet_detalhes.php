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

// Fetch Veterinarians for modals
$vets_list = [];
if ($link) {
    $res_vets = DBExecute($link, "SELECT id_vet, nome, crmv FROM Veterinarios ORDER BY nome ASC");
    if ($res_vets) {
        while ($v = mysqli_fetch_assoc($res_vets)) {
            $vets_list[] = $v;
        }
    }
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
    <?php include '../../components/layout_head.php'; ?>
</head>

<body class="bg-gray-50 flex">

    <?php include '../../components/sidebar.php'; ?>

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
                            <a href="../../cliente_detalhes.php?id=<?= $pet['id_cliente'] ?>"
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
                                    <div class="p-4 hover:bg-gray-50 transition group relative">
                                        <div class="flex justify-between items-start pr-16">
                                            <span
                                                class="font-bold text-gray-700"><?= htmlspecialchars($vac['nome_vacina']) ?></span>
                                            <span
                                                class="text-xs text-gray-400"><?= date('d/m/y', strtotime($vac['data_aplicacao'])) ?></span>
                                        </div>
                                        <?php if ($vac['data_vencimento']): ?>
                                            <div class="mt-1 flex items-center text-xs pr-16">
                                                <span
                                                    class="material-icons text-[14px] mr-1 <?= $vencida ? 'text-red-500' : 'text-green-500' ?>">event_repeat</span>
                                                <span class="<?= $vencida ? 'text-red-600 font-bold' : 'text-green-600' ?>">
                                                    Reforço: <?= date('d/m/Y', strtotime($vac['data_vencimento'])) ?>
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($vac['lote']): ?>
                                            <div class="text-xs text-gray-400 mt-1 pr-16">Lote: <?= htmlspecialchars($vac['lote']) ?></div>
                                        <?php endif; ?>
                                        <?php if ($vac['observacao']): ?>
                                            <div class="text-xs text-gray-500 mt-1 italic pr-16">Obs: <?= htmlspecialchars($vac['observacao']) ?></div>
                                        <?php endif; ?>

                                        <!-- Actions (Edit/Delete) on hover -->
                                        <div class="absolute right-2 top-1/2 -translate-y-1/2 flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <?php
                                            $vac_json = json_encode([
                                                'id_carteira' => $vac['id_carteira'],
                                                'id_vacina' => $vac['id_vacina'],
                                                'data_aplicacao' => $vac['data_aplicacao'],
                                                'data_vencimento' => $vac['data_vencimento'] ?: '',
                                                'lote' => $vac['lote'] ?: '',
                                                'observacao' => $vac['observacao'] ?: ''
                                            ], JSON_HEX_APOS | JSON_HEX_QUOT);
                                            ?>
                                            <button onclick='editVacina(<?= $vac_json ?>)' class="p-1 text-cyan-600 hover:bg-cyan-50 rounded transition" title="Editar">
                                                <span class="material-icons text-[18px]">edit</span>
                                            </button>
                                            <button onclick="deleteVacina(<?= $vac['id_carteira'] ?>)" class="p-1 text-red-600 hover:bg-red-50 rounded transition" title="Excluir">
                                                <span class="material-icons text-[18px]">delete</span>
                                            </button>
                                        </div>
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

                    <!-- Receitas Column (New) -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden h-fit mt-6">
                        <div class="p-4 border-b border-gray-100 flex justify-between items-center">
                            <h3 class="font-bold text-gray-800 flex items-center">
                                <span class="material-icons text-indigo-500 mr-2">receipt</span> Receitas
                            </h3>
                        </div>
                        <div class="divide-y divide-gray-50 max-h-64 overflow-y-auto">
                            <?php
                            $q_rec = "SELECT r.*, a.data_atendimento, (SELECT COUNT(*) FROM ItensReceita WHERE id_receita = r.id_receita) as qtd_itens 
                                      FROM Receitas r
                                      JOIN Atendimentos a ON r.id_atendimento = a.id_atendimento
                                      WHERE a.id_pet = '$id_safe'
                                      ORDER BY r.data_receita DESC";
                            $res_rec = DBExecute($link, $q_rec);
                            if ($res_rec && mysqli_num_rows($res_rec) > 0):
                                while ($rec = mysqli_fetch_assoc($res_rec)):
                                    ?>
                                    <div class="p-4 hover:bg-gray-50 transition cursor-pointer"
                                        onclick="window.location.href='atendimento_form.php?id=<?= $rec['id_atendimento'] ?>&pet_id=<?= $id_safe ?>'">
                                        <div class="flex justify-between items-start">
                                            <span class="font-bold text-gray-700">Receita #<?= $rec['id_receita'] ?></span>
                                            <span
                                                class="text-xs text-gray-400"><?= date('d/m/y', strtotime($rec['data_receita'])) ?></span>
                                        </div>
                                        <p class="text-xs text-gray-500 mt-1"><?= $rec['qtd_itens'] ?> medicamento(s)</p>
                                    </div>
                                    <?php
                                endwhile;
                            else:
                                ?>
                                <div class="p-6 text-center text-gray-400">
                                    <p class="text-sm">Nenhuma receita.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Documentos Column (New) -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden h-fit mt-6">
                        <div class="p-4 border-b border-gray-100 flex justify-between items-center">
                            <h3 class="font-bold text-gray-800 flex items-center">
                                <span class="material-icons text-orange-500 mr-2">folder</span> Documentos
                            </h3>
                        </div>
                        <div class="divide-y divide-gray-50 max-h-64 overflow-y-auto">
                            <?php
                            $q_docs = "SELECT arq.*, a.data_atendimento 
                                       FROM Arquivos arq
                                       JOIN AtendimentoArquivos aa ON arq.id_arquivo = aa.id_arquivo
                                       JOIN Atendimentos a ON aa.id_atendimento = a.id_atendimento
                                       WHERE a.id_pet = '$id_safe'
                                       ORDER BY arq.data_upload DESC";
                            $res_docs = DBExecute($link, $q_docs);
                            if ($res_docs && mysqli_num_rows($res_docs) > 0):
                                while ($doc = mysqli_fetch_assoc($res_docs)):
                                    ?>
                                    <div class="p-4 hover:bg-gray-50 transition">
                                        <a href="<?= $doc['url_publica'] ?>" target="_blank"
                                            class="flex justify-between items-center group">
                                            <div class="flex items-center overflow-hidden">
                                                <span
                                                    class="material-icons text-gray-400 group-hover:text-cyan-600 text-sm mr-2">description</span>
                                                <span
                                                    class="text-sm text-gray-700 truncate group-hover:text-cyan-700 font-medium"><?= htmlspecialchars($doc['nome_original']) ?></span>
                                            </div>
                                        </a>
                                        <div class="mt-1 flex justify-between text-xs text-gray-400 ml-6">
                                            <span><?= date('d/m/y', strtotime($doc['data_upload'])) ?></span>
                                            <span><?= number_format($doc['tamanho_bytes'] / 1024, 1) ?> KB</span>
                                        </div>
                                    </div>
                                    <?php
                                endwhile;
                            else:
                                ?>
                                <div class="p-6 text-center text-gray-400">
                                    <p class="text-sm">Nenhum documento.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Internações Section -->
                    <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-6">
                        <div class="p-4 border-b border-gray-100 flex justify-between items-center bg-rose-50/50">
                            <h3 class="font-bold text-gray-800 flex items-center">
                                <span class="material-icons text-rose-600 mr-2">local_hospital</span> Internações
                            </h3>
                            <button onclick="openInternacaoModal()"
                                class="bg-rose-600 text-white px-3 py-1.5 rounded-lg text-sm font-medium hover:bg-rose-700 transition shadow-sm flex items-center">
                                <span class="material-icons text-sm mr-1">add</span> Nova Internação
                            </button>
                        </div>

                        <div class="divide-y divide-gray-100">
                            <?php
                            $query_int = "SELECT i.*, v.nome as nome_vet, v.crmv as crmv_vet,
                                         (SELECT COUNT(*) FROM InternacaoDias WHERE id_internacao = i.id_internacao) as qtd_dias
                                         FROM Internacoes i 
                                         LEFT JOIN Veterinarios v ON i.id_vet = v.id_vet 
                                         WHERE i.id_pet = '$id_safe' 
                                         ORDER BY i.data_internacao DESC";
                            $res_int = DBExecute($link, $query_int);

                            if ($res_int && mysqli_num_rows($res_int) > 0):
                                while ($int = mysqli_fetch_assoc($res_int)):
                                    $status_class = match($int['status']) {
                                        'internado' => 'bg-amber-100 text-amber-800 border-amber-200',
                                        'alta' => 'bg-green-100 text-green-800 border-green-200',
                                        'obito' => 'bg-gray-100 text-gray-800 border-gray-200',
                                        'cancelado' => 'bg-red-100 text-red-800 border-red-200',
                                        default => 'bg-gray-100 text-gray-800'
                                    };
                                    $status_label = match($int['status']) {
                                        'internado' => 'Em Internação',
                                        'alta' => 'Alta Médica',
                                        'obito' => 'Óbito',
                                        'cancelado' => 'Cancelado',
                                        default => ucfirst($int['status'])
                                    };
                                    $int_json = json_encode($int, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
                                    ?>
                                    <div class="p-5 hover:bg-gray-50/80 transition">
                                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                            <div>
                                                <div class="flex items-center gap-2 mb-1">
                                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold border <?= $status_class ?>">
                                                        <?= $status_label ?>
                                                    </span>
                                                    <span class="text-xs text-gray-500 font-medium">
                                                        Entrada: <?= date('d/m/Y H:i', strtotime($int['data_internacao'])) ?>
                                                    </span>
                                                    <?php if ($int['data_alta']): ?>
                                                        <span class="text-xs text-gray-500 font-medium">
                                                            • Alta: <?= date('d/m/Y H:i', strtotime($int['data_alta'])) ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                                <h4 class="font-bold text-gray-800 text-base mt-1">
                                                    Suspeita: <?= htmlspecialchars($int['suspeita_clinica'] ?: 'Não informada') ?>
                                                </h4>
                                                <p class="text-xs text-gray-500 mt-1">
                                                    Vet Responsável: Dr(a). <?= htmlspecialchars($int['nome_vet'] ?: 'Não atribuído') ?>
                                                    <?= $int['crmv_vet'] ? ' (CRMV: '.$int['crmv_vet'].')' : '' ?>
                                                    • <?= $int['qtd_dias'] ?> dia(s) registrado(s)
                                                </p>
                                            </div>
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <button onclick='openFichaDigital(<?= $int['id_internacao'] ?>)' 
                                                    class="bg-indigo-50 text-indigo-700 hover:bg-indigo-100 px-3 py-1.5 rounded-lg text-xs font-semibold transition flex items-center gap-1 border border-indigo-200 shadow-sm">
                                                    <span class="material-icons text-sm">edit_note</span> Ficha Digital
                                                </button>
                                                <a href="internacao_print.php?id=<?= $int['id_internacao'] ?>" target="_blank"
                                                    class="bg-gray-100 text-gray-700 hover:bg-gray-200 px-3 py-1.5 rounded-lg text-xs font-semibold transition flex items-center gap-1 border border-gray-300 shadow-sm">
                                                    <span class="material-icons text-sm">print</span> Imprimir Ficha
                                                </a>
                                                <button onclick='editInternacao(<?= $int_json ?>)'
                                                    class="p-1.5 text-gray-500 hover:text-cyan-600 hover:bg-gray-100 rounded-lg transition" title="Editar">
                                                    <span class="material-icons text-lg">edit</span>
                                                </button>
                                                <button onclick="deleteInternacao(<?= $int['id_internacao'] ?>)"
                                                    class="p-1.5 text-gray-500 hover:text-red-600 hover:bg-gray-100 rounded-lg transition" title="Excluir">
                                                    <span class="material-icons text-lg">delete</span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                <?php
                                endwhile;
                            else:
                                ?>
                                <div class="p-8 text-center text-gray-400">
                                    <span class="material-icons text-4xl mb-2 opacity-30">local_hospital</span>
                                    <p class="text-sm">Nenhuma internação registrada para este pet.</p>
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
                        onchange="onVacinaChange()">
                        <option value="">Selecione...</option>
                        <?php
                        $link = DBConnect();
                        // Fetch vaccines
                        $q = "SELECT * FROM Vacinas ORDER BY nome ASC";
                        $r = DBExecute($link, $q);

                        // Fetch all cycles
                        $ciclos_map = [];
                        $qc = "SELECT * FROM VacinaCiclos ORDER BY id_vacina, intervalo ASC";
                        $rc = DBExecute($link, $qc);
                        while ($ciclo = mysqli_fetch_assoc($rc)) {
                            $ciclos_map[$ciclo['id_vacina']][] = [
                                'nome' => $ciclo['nome'],
                                'dias' => $ciclo['intervalo']
                            ];
                        }

                        while ($v = mysqli_fetch_assoc($r)):
                            $ciclos_json = isset($ciclos_map[$v['id_vacina']]) ? json_encode($ciclos_map[$v['id_vacina']]) : '[]';
                            ?>
                            <option value="<?= $v['id_vacina'] ?>" data-dias="<?= $v['recorrencia_dias'] ?>"
                                data-ciclos='<?= $ciclos_json ?>'>
                                <?= htmlspecialchars($v['nome']) ?>
                            </option>
                        <?php endwhile;
                        DBClose($link); ?>
                    </select>
                </div>

                <!-- Ciclo Selection (Hidden by default) -->
                <div id="div-ciclo" class="mb-4 hidden">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Ciclo / Protocolo (Opcional)</label>
                    <select id="select_ciclo" class="w-full p-2 border rounded-lg bg-gray-50" onchange="applyCiclo()">
                        <option value="">Padrão da Vacina</option>
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

    <?php include dirname(__DIR__, 2) . '/components/layout_scripts.php'; ?>
    <script>
        function openVacinaModal() {
            $('#modalVacina h3').text('Registrar Vacina');
            $('#formVacina input[name="action"]').val('register_vaccine');
            $('#id_carteira').remove();
            
            // Reset fields
            $('#id_vacina').val('').trigger('change');
            $('#data_aplicacao').val('<?= date('Y-m-d') ?>');
            $('#data_proxima').val('');
            $('#formVacina input[name="lote"]').val('');
            $('#formVacina textarea[name="observacoes"]').val('');
            
            $('#modalVacina button[type="submit"]').text('Registrar');
            $('#modalVacina').removeClass('hidden');
        }
        function closeVacinaModal() {
            $('#modalVacina').addClass('hidden');
        }

        function editVacina(data) {
            $('#modalVacina h3').text('Editar Vacina Aplicada');
            $('#formVacina input[name="action"]').val('edit_vaccine');
            
            if ($('#id_carteira').length === 0) {
                $('#formVacina').prepend('<input type="hidden" name="id_carteira" id="id_carteira">');
            }
            $('#id_carteira').val(data.id_carteira);
            $('#id_vacina').val(data.id_vacina);
            
            // Setup cycles but skip auto calculation of proxima dose
            onVacinaChange(true);
            
            $('#data_aplicacao').val(data.data_aplicacao);
            $('#data_proxima').val(data.data_vencimento);
            $('#formVacina input[name="lote"]').val(data.lote);
            $('#formVacina textarea[name="observacoes"]').val(data.observacao);
            
            $('#modalVacina button[type="submit"]').text('Salvar Alterações');
            $('#modalVacina').removeClass('hidden');
        }

        function deleteVacina(id_carteira) {
            if (confirm('Tem certeza que deseja remover esta aplicação de vacina?')) {
                $.ajax({
                    url: '../../app.php',
                    type: 'POST',
                    data: {
                        action: 'delete_vaccine',
                        id_carteira: id_carteira
                    },
                    dataType: 'json',
                    success: function (response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Erro: ' + response.message);
                        }
                    },
                    error: function () {
                        alert('Erro de conexão ao remover vacina.');
                    }
                });
            }
        }

        function onVacinaChange(skipCalculation = false) {
            const opt = $('#id_vacina option:selected');
            const ciclos = opt.data('ciclos');
            const defaultDias = opt.data('dias');

            const selectCiclo = $('#select_ciclo');
            const divCiclo = $('#div-ciclo');

            // Reset Cycle Dropdown
            selectCiclo.empty().append('<option value="">Padrão (' + defaultDias + ' dias)</option>');

            if (ciclos && ciclos.length > 0) {
                divCiclo.removeClass('hidden');
                ciclos.forEach(c => {
                    selectCiclo.append(`<option value="${c.dias}">${c.nome} (${c.dias} dias)</option>`);
                });
            } else {
                divCiclo.addClass('hidden');
            }

            if (!skipCalculation) {
                // Apply default calculation
                calcularProxima(defaultDias);
            }
        }

        function applyCiclo() {
            const diasCiclo = $('#select_ciclo').val();
            if (diasCiclo) {
                calcularProxima(diasCiclo);
            } else {
                const defaultDias = $('#id_vacina option:selected').data('dias');
                calcularProxima(defaultDias);
            }
        }

        function calcularProxima(dias) {
            // override manual logic
            if (!dias) dias = $('#id_vacina option:selected').data('dias');

            const dataAplicacao = $('#data_aplicacao').val();

            if (dias && dataAplicacao) {
                const data = new Date(dataAplicacao);
                data.setDate(data.getDate() + parseInt(dias));
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
                url: '../../app.php',
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

    <!-- Modal Cadastrar / Editar Internação -->
    <div id="modalInternacao" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 hidden p-4">
        <div class="bg-white rounded-xl shadow-lg w-full max-w-lg p-6">
            <h3 class="text-xl font-bold text-gray-800 mb-4" id="modalInternacaoTitle">Nova Internação</h3>
            <form id="formInternacao">
                <input type="hidden" name="action" value="save_internacao">
                <input type="hidden" name="id_internacao" id="int_id_internacao" value="">
                <input type="hidden" name="id_pet" value="<?= $id_pet ?>">

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Médico Veterinário Responsável</label>
                    <select name="id_vet" id="int_id_vet" class="w-full p-2 border rounded-lg">
                        <option value="">Selecione um veterinário...</option>
                        <?php foreach ($vets_list as $v): ?>
                            <option value="<?= $v['id_vet'] ?>">
                                <?= htmlspecialchars($v['nome']) ?> <?= $v['crmv'] ? '(CRMV: ' . htmlspecialchars($v['crmv']) . ')' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Data/Hora Internação *</label>
                        <input type="datetime-local" name="data_internacao" id="int_data_internacao" required class="w-full p-2 border rounded-lg" value="<?= date('Y-m-d\TH:i') ?>">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select name="status" id="int_status" class="w-full p-2 border rounded-lg">
                            <option value="internado">Em Internação</option>
                            <option value="alta">Alta Médica</option>
                            <option value="obito">Óbito</option>
                            <option value="cancelado">Cancelado</option>
                        </select>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Data/Hora da Alta (Opcional)</label>
                    <input type="datetime-local" name="data_alta" id="int_data_alta" class="w-full p-2 border rounded-lg">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Suspeita Clínica / Diagnóstico</label>
                    <textarea name="suspeita_clinica" id="int_suspeita_clinica" rows="2" class="w-full p-2 border rounded-lg" placeholder="Ex: Gastroenterite hemorrágica, Desidratação..."></textarea>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Observações Gerais</label>
                    <textarea name="observacoes" id="int_observacoes" rows="2" class="w-full p-2 border rounded-lg"></textarea>
                </div>

                <div class="flex justify-end gap-2">
                    <button type="button" onclick="closeInternacaoModal()" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">Cancelar</button>
                    <button type="submit" class="px-4 py-2 bg-rose-600 text-white rounded-lg hover:bg-rose-700 font-medium">Salvar Internação</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Ficha Digital de Internação -->
    <div id="modalFichaDigital" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-60 hidden p-4 overflow-y-auto">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-5xl my-8 overflow-hidden flex flex-col max-h-[90vh]">
            <!-- Header -->
            <div class="p-4 bg-slate-800 text-white flex justify-between items-center">
                <div class="flex items-center space-x-2">
                    <span class="material-icons text-rose-400">edit_note</span>
                    <h3 class="text-lg font-bold">Ficha Digital de Internação</h3>
                    <span id="fd_vet_info" class="text-xs text-slate-300 ml-2"></span>
                </div>
                <button onclick="closeFichaDigital()" class="text-slate-400 hover:text-white transition">
                    <span class="material-icons">close</span>
                </button>
            </div>

            <!-- Body -->
            <div class="p-6 overflow-y-auto flex-1 bg-gray-50 space-y-6">
                
                <!-- Days Navigation Tabs -->
                <div class="flex items-center justify-between border-b border-gray-200 pb-3">
                    <div class="flex items-center gap-2 overflow-x-auto" id="fd_dias_tabs">
                        <!-- Loaded via JS -->
                    </div>
                    <button onclick="adicionarNovoDiaFicha()" class="bg-rose-600 hover:bg-rose-700 text-white px-3 py-1.5 rounded-lg text-xs font-medium flex items-center gap-1 shrink-0 shadow-sm">
                        <span class="material-icons text-xs">add</span> Adicionar Dia
                    </button>
                </div>

                <!-- Active Day Container -->
                <div id="fd_dia_container" class="space-y-6 hidden">

                    <!-- Soro & Fluidoterapia Card -->
                    <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
                        <div class="flex justify-between items-center mb-3">
                            <h4 class="font-bold text-gray-700 flex items-center text-sm">
                                <span class="material-icons text-cyan-600 text-base mr-1.5">water_drop</span> Soro & Fluidoterapia do Dia
                            </h4>
                            <span class="text-xs text-gray-400" id="fd_dia_data_label"></span>
                        </div>
                        <form id="formDiaFicha" class="grid grid-cols-1 md:grid-cols-4 gap-3">
                            <input type="hidden" name="action" value="save_internacao_dia">
                            <input type="hidden" name="id_dia" id="fd_id_dia">
                            <input type="hidden" name="id_internacao" id="fd_id_internacao">
                            <input type="hidden" name="data_dia" id="fd_data_dia">

                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Soro</label>
                                <input type="text" name="soro" id="fd_soro" class="w-full p-2 border text-sm rounded-lg" placeholder="Ex: Ringer Lactato">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Volume</label>
                                <input type="text" name="volume" id="fd_volume" class="w-full p-2 border text-sm rounded-lg" placeholder="Ex: 500 ml">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Frequência</label>
                                <input type="text" name="frequencia" id="fd_frequencia" class="w-full p-2 border text-sm rounded-lg" placeholder="Ex: 20 ml/h">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Observações</label>
                                <input type="text" name="observacoes" id="fd_observacoes_dia" class="w-full p-2 border text-sm rounded-lg" placeholder="Obs fluidoterapia">
                            </div>
                            <div class="md:col-span-4 flex justify-end">
                                <button type="submit" class="bg-cyan-600 hover:bg-cyan-700 text-white px-3 py-1.5 rounded-lg text-xs font-semibold transition">
                                    Salvar Fluidoterapia
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Medicações Card -->
                    <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
                        <div class="flex justify-between items-center mb-4">
                            <h4 class="font-bold text-gray-700 flex items-center text-sm">
                                <span class="material-icons text-purple-600 text-base mr-1.5">medication</span> Lista de Medicações Lançadas
                            </h4>
                            <button onclick="openMedicacaoFichaModal()" class="bg-purple-600 hover:bg-purple-700 text-white px-3 py-1.5 rounded-lg text-xs font-semibold flex items-center gap-1 shadow-sm">
                                <span class="material-icons text-xs">add</span> Lançar Medicação
                            </button>
                        </div>

                        <!-- Table of Medications -->
                        <div class="overflow-x-auto">
                            <table class="w-full text-xs text-left border-collapse" id="fd_table_meds">
                                <thead>
                                    <tr class="bg-gray-100 text-gray-600 font-bold border-b border-gray-200">
                                        <th class="p-2.5 w-1/3">MEDICAÇÃO</th>
                                        <th class="p-2.5">DOSE</th>
                                        <th class="p-2.5">VIA</th>
                                        <th class="p-2.5 text-center">HORÁRIOS & CHEQUES (6 SLOTS)</th>
                                        <th class="p-2.5 text-right">AÇÕES</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 text-gray-700">
                                    <!-- Rendered via JS -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>

                <!-- Empty State if no days exist -->
                <div id="fd_no_days" class="p-12 text-center text-gray-400 hidden">
                    <span class="material-icons text-5xl mb-2 opacity-30">calendar_today</span>
                    <p class="font-medium text-gray-600">Nenhum dia cadastrado nesta internação.</p>
                    <button onclick="adicionarNovoDiaFicha()" class="mt-3 bg-rose-600 text-white px-4 py-2 rounded-lg text-xs font-semibold">
                        Adicionar Dia 1
                    </button>
                </div>

            </div>

            <!-- Footer -->
            <div class="p-4 bg-gray-100 border-t border-gray-200 flex justify-between items-center">
                <span class="text-xs text-gray-500">As medicações e checagens salvas aqui serão pré-preenchidas na Ficha de Impressão.</span>
                <div class="flex gap-2">
                    <a id="fd_btn_imprimir" href="#" target="_blank" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg text-xs font-semibold transition flex items-center gap-1">
                        <span class="material-icons text-xs">print</span> Visualizar Impressão
                    </a>
                    <button type="button" onclick="closeFichaDigital()" class="bg-slate-700 hover:bg-slate-800 text-white px-4 py-2 rounded-lg text-xs font-semibold">
                        Concluir
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Sub-Modal Formulário de Medicação da Ficha -->
    <div id="modalMedicacaoFicha" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 hidden p-4">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-lg p-6">
            <h4 class="text-lg font-bold text-gray-800 mb-4" id="modalMedicacaoFichaTitle">Adicionar Medicação à Ficha</h4>
            <form id="formMedicacaoFicha">
                <input type="hidden" name="action" value="save_internacao_medicacao">
                <input type="hidden" name="id_medicacao" id="mf_id_medicacao" value="">
                <input type="hidden" name="id_dia" id="mf_id_dia" value="">

                <div class="mb-3">
                    <label class="block text-xs font-semibold text-gray-700 mb-1">Nome da Medicação * (Digitação Livre)</label>
                    <input type="text" name="medicacao" id="mf_medicacao" required class="w-full p-2 border rounded-lg text-sm" placeholder="Ex: Dipirona, Zofran, Enrofloxacino...">
                </div>

                <div class="grid grid-cols-2 gap-3 mb-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Dose</label>
                        <input type="text" name="dose" id="mf_dose" class="w-full p-2 border rounded-lg text-sm" placeholder="Ex: 0.5ml, 1 comp">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Via</label>
                        <input type="text" name="via" id="mf_via" class="w-full p-2 border rounded-lg text-sm" placeholder="Ex: IV, SC, IM, VO">
                    </div>
                </div>

                <!-- 6 Horários Slots -->
                <div class="mb-5 bg-gray-50 p-3 rounded-lg border border-gray-200">
                    <label class="block text-xs font-semibold text-gray-700 mb-2">Horários programados e Checagem de Aplicação (até 6 slots)</label>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3" id="mf_horarios_container">
                        <?php for ($i = 0; $i < 6; $i++): ?>
                            <div class="bg-white p-2 border rounded-lg flex items-center justify-between">
                                <input type="text" id="mf_hora_<?= $i ?>" class="w-20 p-1 text-xs border rounded text-center" placeholder="Ex: 08:00">
                                <label class="flex items-center gap-1 cursor-pointer text-xs">
                                    <input type="checkbox" id="mf_check_<?= $i ?>" class="rounded text-rose-600 focus:ring-rose-500 h-4 w-4">
                                    <span class="text-[10px] font-semibold text-gray-500">Aplicado</span>
                                </label>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>

                <div class="flex justify-end gap-2">
                    <button type="button" onclick="closeMedicacaoFichaModal()" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-xs">Cancelar</button>
                    <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 text-xs font-semibold">Salvar Medicação</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // --- Internação Modals & Handlers ---
        function openInternacaoModal() {
            $('#modalInternacaoTitle').text('Nova Internação');
            $('#formInternacao input[name="action"]').val('save_internacao');
            $('#int_id_internacao').val('');
            $('#int_id_vet').val('');
            const now = new Date();
            const nowISO = new Date(now.getTime() - (now.getTimezoneOffset() * 60000)).toISOString().slice(0,16);
            $('#int_data_internacao').val(nowISO);
            $('#int_status').val('internado');
            $('#int_data_alta').val('');
            $('#int_suspeita_clinica').val('');
            $('#int_observacoes').val('');
            $('#modalInternacao').removeClass('hidden');
        }

        function closeInternacaoModal() {
            $('#modalInternacao').addClass('hidden');
        }

        function editInternacao(data) {
            $('#modalInternacaoTitle').text('Editar Internação');
            $('#formInternacao input[name="action"]').val('save_internacao');
            $('#int_id_internacao').val(data.id_internacao);
            $('#int_id_vet').val(data.id_vet || '');
            if (data.data_internacao) {
                const dt = new Date(data.data_internacao);
                const dtISO = new Date(dt.getTime() - (dt.getTimezoneOffset() * 60000)).toISOString().slice(0,16);
                $('#int_data_internacao').val(dtISO);
            }
            if (data.data_alta) {
                const da = new Date(data.data_alta);
                const daISO = new Date(da.getTime() - (da.getTimezoneOffset() * 60000)).toISOString().slice(0,16);
                $('#int_data_alta').val(daISO);
            } else {
                $('#int_data_alta').val('');
            }
            $('#int_status').val(data.status);
            $('#int_suspeita_clinica').val(data.suspeita_clinica || '');
            $('#int_observacoes').val(data.observacoes || '');
            $('#modalInternacao').removeClass('hidden');
        }

        function deleteInternacao(id_internacao) {
            if (confirm('Tem certeza que deseja excluir esta internação e todo o seu histórico diário?')) {
                $.ajax({
                    url: '../../app.php',
                    type: 'POST',
                    data: { action: 'delete_internacao', id_internacao: id_internacao },
                    dataType: 'json',
                    success: function(res) {
                        if (res.success) location.reload();
                        else alert('Erro: ' + res.message);
                    },
                    error: function() { alert('Erro de conexão.'); }
                });
            }
        }

        $('#formInternacao').on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                url: '../../app.php',
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(res) {
                    if (res.success) location.reload();
                    else alert('Erro: ' + res.message);
                },
                error: function() { alert('Erro de conexão ao salvar internação.'); }
            });
        });

        // --- Ficha Digital State & Actions ---
        let currentFichaData = null;
        let currentSelectedDiaIndex = 0;

        function openFichaDigital(id_internacao) {
            $('#fd_btn_imprimir').attr('href', 'internacao_print.php?id=' + id_internacao);
            $('#modalFichaDigital').removeClass('hidden');
            carregarFichaDigital(id_internacao);
        }

        function closeFichaDigital() {
            $('#modalFichaDigital').addClass('hidden');
        }

        function carregarFichaDigital(id_internacao, selectDiaId = null) {
            $.ajax({
                url: '../../app.php',
                type: 'POST',
                data: { action: 'get_internacao_details', id_internacao: id_internacao },
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        currentFichaData = res;
                        renderFichaDigitalTabs(selectDiaId);
                    } else {
                        alert('Erro: ' + res.message);
                        closeFichaDigital();
                    }
                },
                error: function() {
                    alert('Erro de conexão ao carregar Ficha Digital.');
                    closeFichaDigital();
                }
            });
        }

        function renderFichaDigitalTabs(selectDiaId = null) {
            if (!currentFichaData) return;
            const dias = currentFichaData.dias || [];
            const vetNome = currentFichaData.internacao.vet_nome ? 'Dr(a). ' + currentFichaData.internacao.vet_nome : '';
            $('#fd_vet_info').text(vetNome);
            
            const tabsContainer = $('#fd_dias_tabs');
            tabsContainer.empty();

            if (dias.length === 0) {
                $('#fd_dia_container').addClass('hidden');
                $('#fd_no_days').removeClass('hidden');
                return;
            }

            $('#fd_no_days').addClass('hidden');
            $('#fd_dia_container').removeClass('hidden');

            let targetIdx = 0;
            if (selectDiaId) {
                const foundIdx = dias.findIndex(d => d.id_dia == selectDiaId);
                if (foundIdx !== -1) targetIdx = foundIdx;
            } else if (currentSelectedDiaIndex < dias.length) {
                targetIdx = currentSelectedDiaIndex;
            } else {
                targetIdx = dias.length - 1;
            }

            dias.forEach((d, idx) => {
                const dtParts = d.data_dia.split('-');
                const dtFmt = dtParts.length === 3 ? `${dtParts[2]}/${dtParts[1]}` : d.data_dia;
                const active = idx === targetIdx;
                const activeClass = active 
                    ? 'bg-rose-600 text-white font-bold shadow-sm' 
                    : 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-200 font-medium';
                
                tabsContainer.append(`
                    <div class="flex items-center gap-1 rounded-lg p-1 border border-transparent">
                        <button type="button" onclick="selectDiaFichaIndex(${idx})" class="px-3 py-1.5 rounded-lg text-xs transition ${activeClass}">
                            Dia ${idx + 1} (${dtFmt})
                        </button>
                        ${dias.length > 1 ? `<button type="button" onclick="deleteDiaFicha(${d.id_dia})" class="text-gray-400 hover:text-red-600 p-1" title="Excluir este dia"><span class="material-icons text-xs">close</span></button>` : ''}
                    </div>
                `);
            });

            selectDiaFichaIndex(targetIdx);
        }

        function selectDiaFichaIndex(idx) {
            currentSelectedDiaIndex = idx;
            const dias = currentFichaData.dias || [];
            if (!dias[idx]) return;
            const dia = dias[idx];

            const dtParts = dia.data_dia.split('-');
            const dtFmt = dtParts.length === 3 ? `${dtParts[2]}/${dtParts[1]}/${dtParts[0]}` : dia.data_dia;
            $('#fd_dia_data_label').text('Data: ' + dtFmt);

            $('#fd_id_dia').val(dia.id_dia);
            $('#fd_id_internacao').val(currentFichaData.internacao.id_internacao);
            $('#fd_data_dia').val(dia.data_dia);
            $('#fd_soro').val(dia.soro || '');
            $('#fd_volume').val(dia.volume || '');
            $('#fd_frequencia').val(dia.frequencia || '');
            $('#fd_observacoes_dia').val(dia.observacoes || '');

            const tbody = $('#fd_table_meds tbody');
            tbody.empty();
            const meds = dia.medicacoes || [];

            if (meds.length === 0) {
                tbody.append(`
                    <tr>
                        <td colspan="5" class="p-6 text-center text-gray-400 italic">
                            Nenhuma medicação registrada para este dia. Clique em "+ Lançar Medicação".
                        </td>
                    </tr>
                `);
            } else {
                meds.forEach(m => {
                    let horariosArr = [];
                    if (m.horarios) {
                        try { horariosArr = JSON.parse(m.horarios); } catch(e){}
                    }

                    let slotsHtml = '<div class="flex items-center justify-center gap-1.5 flex-wrap">';
                    for (let h = 0; h < 6; h++) {
                        const slot = horariosArr[h] || {};
                        const hora = slot.hora || '';
                        const checked = !!slot.checked;
                        slotsHtml += `
                            <button type="button" onclick='toggleCheckSlot(${m.id_medicacao}, ${h})' 
                                class="px-2 py-1 rounded text-[11px] flex items-center gap-1 border transition ${checked ? 'bg-green-100 text-green-800 border-green-300 font-bold' : 'bg-gray-50 text-gray-600 border-gray-200'}">
                                <span class="material-icons text-xs">${checked ? 'check_box' : 'check_box_outline_blank'}</span>
                                ${hora || '--:--'}
                            </button>
                        `;
                    }
                    slotsHtml += '</div>';

                    const mJson = JSON.stringify(m).replace(/'/g, "&apos;");

                    tbody.append(`
                        <tr class="hover:bg-gray-50/80 transition">
                            <td class="p-2.5 font-bold text-gray-800">${escapeHtml(m.medicacao)}</td>
                            <td class="p-2.5 font-medium">${escapeHtml(m.dose || '-')}</td>
                            <td class="p-2.5 font-medium">${escapeHtml(m.via || '-')}</td>
                            <td class="p-2.5 text-center">${slotsHtml}</td>
                            <td class="p-2.5 text-right space-x-1">
                                <button type="button" onclick='editMedicacaoFicha(${mJson})' class="p-1 text-gray-500 hover:text-purple-600 rounded transition" title="Editar"><span class="material-icons text-base">edit</span></button>
                                <button type="button" onclick="deleteMedicacaoFicha(${m.id_medicacao})" class="p-1 text-gray-500 hover:text-red-600 rounded transition" title="Excluir"><span class="material-icons text-base">delete</span></button>
                            </td>
                        </tr>
                    `);
                });
            }
        }

        function escapeHtml(str) {
            if (!str) return '';
            return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }

        function adicionarNovoDiaFicha() {
            if (!currentFichaData) return;
            const intId = currentFichaData.internacao.id_internacao;
            const dias = currentFichaData.dias || [];
            
            let nextDate = new Date().toISOString().slice(0,10);
            if (dias.length > 0) {
                const lastDate = new Date(dias[dias.length - 1].data_dia + 'T00:00:00');
                lastDate.setDate(lastDate.getDate() + 1);
                nextDate = lastDate.toISOString().slice(0,10);
            }

            $.ajax({
                url: '../../app.php',
                type: 'POST',
                data: { action: 'save_internacao_dia', id_internacao: intId, data_dia: nextDate },
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        carregarFichaDigital(intId, res.id_dia);
                    } else {
                        alert('Erro: ' + res.message);
                    }
                },
                error: function() { alert('Erro de conexão ao adicionar dia.'); }
            });
        }

        function deleteDiaFicha(id_dia) {
            if (confirm('Tem certeza que deseja excluir este dia de internação?')) {
                $.ajax({
                    url: '../../app.php',
                    type: 'POST',
                    data: { action: 'delete_internacao_dia', id_dia: id_dia },
                    dataType: 'json',
                    success: function(res) {
                        if (res.success) {
                            carregarFichaDigital(currentFichaData.internacao.id_internacao);
                        } else {
                            alert('Erro: ' + res.message);
                        }
                    },
                    error: function() { alert('Erro ao excluir dia.'); }
                });
            }
        }

        $('#formDiaFicha').on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                url: '../../app.php',
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        carregarFichaDigital(currentFichaData.internacao.id_internacao, res.id_dia);
                    } else {
                        alert('Erro: ' + res.message);
                    }
                },
                error: function() { alert('Erro ao salvar fluidoterapia.'); }
            });
        });

        // --- Medicação Sub-Modal & Actions ---
        function openMedicacaoFichaModal() {
            if (!currentFichaData) return;
            const dias = currentFichaData.dias || [];
            const dia = dias[currentSelectedDiaIndex];
            if (!dia) return;

            $('#modalMedicacaoFichaTitle').text('Adicionar Medicação à Ficha');
            $('#formMedicacaoFicha input[name="action"]').val('save_internacao_medicacao');
            $('#mf_id_medicacao').val('');
            $('#mf_id_dia').val(dia.id_dia);
            $('#mf_medicacao').val('');
            $('#mf_dose').val('');
            $('#mf_via').val('');

            const defaultTimes = ['08:00', '12:00', '16:00', '20:00', '00:00', '04:00'];
            for (let i = 0; i < 6; i++) {
                $(`#mf_hora_${i}`).val(defaultTimes[i] || '');
                $(`#mf_check_${i}`).prop('checked', false);
            }

            $('#modalMedicacaoFicha').removeClass('hidden');
        }

        function closeMedicacaoFichaModal() {
            $('#modalMedicacaoFicha').addClass('hidden');
        }

        function editMedicacaoFicha(med) {
            $('#modalMedicacaoFichaTitle').text('Editar Medicação da Ficha');
            $('#formMedicacaoFicha input[name="action"]').val('save_internacao_medicacao');
            $('#mf_id_medicacao').val(med.id_medicacao);
            $('#mf_id_dia').val(med.id_dia);
            $('#mf_medicacao').val(med.medicacao);
            $('#mf_dose').val(med.dose || '');
            $('#mf_via').val(med.via || '');

            let horariosArr = [];
            if (med.horarios) {
                try { horariosArr = JSON.parse(med.horarios); } catch(e){}
            }

            for (let i = 0; i < 6; i++) {
                const slot = horariosArr[i] || {};
                $(`#mf_hora_${i}`).val(slot.hora || '');
                $(`#mf_check_${i}`).prop('checked', !!slot.checked);
            }

            $('#modalMedicacaoFicha').removeClass('hidden');
        }

        function toggleCheckSlot(id_medicacao, slotIndex) {
            const dias = currentFichaData.dias || [];
            const dia = dias[currentSelectedDiaIndex];
            if (!dia) return;
            const med = (dia.medicacoes || []).find(m => m.id_medicacao == id_medicacao);
            if (!med) return;

            let horariosArr = [];
            if (med.horarios) {
                try { horariosArr = JSON.parse(med.horarios); } catch(e){}
            }

            for (let i = 0; i < 6; i++) {
                if (!horariosArr[i]) horariosArr[i] = { hora: '', checked: 0 };
            }

            horariosArr[slotIndex].checked = horariosArr[slotIndex].checked ? 0 : 1;

            $.ajax({
                url: '../../app.php',
                type: 'POST',
                data: {
                    action: 'save_internacao_medicacao',
                    id_medicacao: med.id_medicacao,
                    id_dia: med.id_dia,
                    medicacao: med.medicacao,
                    dose: med.dose,
                    via: med.via,
                    horarios: JSON.stringify(horariosArr)
                },
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        carregarFichaDigital(currentFichaData.internacao.id_internacao, dia.id_dia);
                    } else {
                        alert('Erro: ' + res.message);
                    }
                },
                error: function() { alert('Erro ao alternar horário.'); }
            });
        }

        function deleteMedicacaoFicha(id_medicacao) {
            if (confirm('Remover esta medicação da ficha?')) {
                const dias = currentFichaData.dias || [];
                const dia = dias[currentSelectedDiaIndex];
                $.ajax({
                    url: '../../app.php',
                    type: 'POST',
                    data: { action: 'delete_internacao_medicacao', id_medicacao: id_medicacao },
                    dataType: 'json',
                    success: function(res) {
                        if (res.success) {
                            carregarFichaDigital(currentFichaData.internacao.id_internacao, dia ? dia.id_dia : null);
                        } else {
                            alert('Erro: ' + res.message);
                        }
                    },
                    error: function() { alert('Erro ao remover medicação.'); }
                });
            }
        }

        $('#formMedicacaoFicha').on('submit', function(e) {
            e.preventDefault();
            const slots = [];
            for (let i = 0; i < 6; i++) {
                slots.push({
                    hora: $(`#mf_hora_${i}`).val().trim(),
                    checked: $(`#mf_check_${i}`).is(':checked') ? 1 : 0
                });
            }

            const formData = {
                action: 'save_internacao_medicacao',
                id_medicacao: $('#mf_id_medicacao').val(),
                id_dia: $('#mf_id_dia').val(),
                medicacao: $('#mf_medicacao').val(),
                dose: $('#mf_dose').val(),
                via: $('#mf_via').val(),
                horarios: JSON.stringify(slots)
            };

            $.ajax({
                url: '../../app.php',
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        closeMedicacaoFichaModal();
                        carregarFichaDigital(currentFichaData.internacao.id_internacao, $('#mf_id_dia').val());
                    } else {
                        alert('Erro: ' + res.message);
                    }
                },
                error: function() { alert('Erro ao salvar medicação.'); }
            });
        });
    </script>
</body>

</html>