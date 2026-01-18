<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}
include "../database.php";

$link = DBConnect();
if (!$link) {
    die("Erro de conexão com o banco de dados.");
}

$id_vacina = $_GET['id'] ?? null;
$vacina = null;
$is_edit = false;
$erro = "";

if ($id_vacina) {
    $id_safe = mysqli_real_escape_string($link, $id_vacina);
    $query = "SELECT * FROM Vacinas WHERE id_vacina = '$id_safe'";
    $result = DBExecute($link, $query);
    if ($result && mysqli_num_rows($result) > 0) {
        $vacina = mysqli_fetch_assoc($result);
        $is_edit = true;
    }
}

// Fetch Cycles if Edit
$ciclos = [];
if ($is_edit) {
    $q_ciclos = "SELECT * FROM VacinaCiclos WHERE id_vacina = " . (int) $id_vacina . " ORDER BY id_ciclo ASC";
    $r_ciclos = DBExecute($link, $q_ciclos);
    while ($c = mysqli_fetch_assoc($r_ciclos)) {
        $ciclos[] = $c;
    }
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'] ?? '';
    $descricao = $_POST['descricao'] ?? '';
    $recorrencia_dias = (int) ($_POST['recorrencia_dias'] ?? 365);

    if (empty($nome)) {
        $erro = "O nome da vacina é obrigatório.";
    } else {
        $nome_safe = mysqli_real_escape_string($link, $nome);
        $descricao_safe = mysqli_real_escape_string($link, $descricao);

        if ($is_edit) {
            $query = "UPDATE Vacinas SET nome='$nome_safe', descricao='$descricao_safe', recorrencia_dias=$recorrencia_dias WHERE id_vacina=" . (int) $id_vacina;
        } else {
            $query = "INSERT INTO Vacinas (nome, descricao, recorrencia_dias) VALUES ('$nome_safe', '$descricao_safe', $recorrencia_dias)";
        }

        if (DBExecute($link, $query)) {
            // Get ID if INSERT
            if (!$is_edit) {
                $id_vacina = mysqli_insert_id($link);
            }

            // Sync Cycles
            // 1. Delete all existing (simplest approach for now)
            DBExecute($link, "DELETE FROM VacinaCiclos WHERE id_vacina = " . (int) $id_vacina);

            // 2. Insert new ones
            if (isset($_POST['ciclo_nome']) && is_array($_POST['ciclo_nome'])) {
                foreach ($_POST['ciclo_nome'] as $k => $c_nome) {
                    $c_nome_s = mysqli_real_escape_string($link, $c_nome);
                    $c_int = (int) $_POST['ciclo_intervalo'][$k];
                    if ($c_nome_s && $c_int > 0) {
                        DBExecute($link, "INSERT INTO VacinaCiclos (id_vacina, nome, intervalo) VALUES ($id_vacina, '$c_nome_s', $c_int)");
                    }
                }
            }

            header("Location: vacinas.php");
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
        <?= $is_edit ? 'Editar Vacina' : 'Nova Vacina' ?> - DinoVet
    </title>
    <?php include 'components/layout_head.php'; ?>
</head>

<body class="bg-gray-50 flex">
    <?php include 'components/sidebar.php'; ?>

    <div class="flex-1 flex flex-col lg:ml-64 min-h-screen transition-all duration-300">
        <main class="flex-1 p-6 mt-16 lg:mt-0">
            <div class="max-w-xl mx-auto">
                <a href="vacinas.php" class="text-gray-500 hover:text-gray-700 mb-4 inline-flex items-center">
                    <span class="material-icons mr-1">arrow_back</span> Voltar para Vacinas
                </a>

                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-6 border-b border-gray-100">
                        <h2 class="text-2xl font-bold text-gray-800">
                            <?= $is_edit ? 'Editar Vacina' : 'Nova Vacina' ?>
                        </h2>
                    </div>

                    <?php if ($erro): ?>
                        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 m-6" role="alert">
                            <p>
                                <?= $erro ?>
                            </p>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="p-6 space-y-6">

                        <div>
                            <label class="block text-gray-700 font-medium mb-2">Nome da Vacina *</label>
                            <input type="text" name="nome" value="<?= htmlspecialchars($vacina['nome'] ?? '') ?>"
                                required placeholder="Ex: V10, Antirrábica..."
                                class="w-full border-gray-300 rounded-lg shadow-sm focus:border-cyan-500 focus:ring focus:ring-cyan-200 focus:ring-opacity-50 p-3 border">
                        </div>

                        <div>
                            <label class="block text-gray-700 font-medium mb-2">Descrição</label>
                            <textarea name="descricao" rows="3"
                                class="w-full border-gray-300 rounded-lg shadow-sm focus:border-cyan-500 focus:ring focus:ring-cyan-200 focus:ring-opacity-50 p-3 border"
                                placeholder="Detalhes sobre a vacina..."><?= htmlspecialchars($vacina['descricao'] ?? '') ?></textarea>
                        </div>

                        <div>
                            <label class="block text-gray-700 font-medium mb-2">Recorrência Padrão (Dias)</label>
                            <div class="flex items-center gap-4">
                                <input type="number" name="recorrencia_dias"
                                    value="<?= $vacina['recorrencia_dias'] ?? 365 ?>" required
                                    class="w-32 border-gray-300 rounded-lg shadow-sm focus:border-cyan-500 focus:ring focus:ring-cyan-200 focus:ring-opacity-50 p-3 border">
                                <span class="text-gray-500 text-sm">Ex: 365 para anual, 21 para reforço.</span>
                            </div>
                        </div>

                        <!-- Ciclos / Protocolos -->
                        <div class="border-t border-gray-100 pt-6">
                            <h3 class="text-lg font-bold text-gray-800 mb-2">Ciclos / Protocolos (Opcional)</h3>
                            <p class="text-sm text-gray-500 mb-4">Adicione configurações específicas (ex: Filhote,
                                Reforço Curto) para facilitar o cálculo.</p>

                            <div id="ciclos-container" class="space-y-3">
                                <?php foreach ($ciclos as $c): ?>
                                    <div class="flex gap-2 items-center ciclo-row">
                                        <input type="text" name="ciclo_nome[]" value="<?= htmlspecialchars($c['nome']) ?>"
                                            placeholder="Nome (Ex: Filhote)"
                                            class="flex-1 border-gray-300 rounded-lg p-2 border text-sm" required>
                                        <input type="number" name="ciclo_intervalo[]" value="<?= $c['intervalo'] ?>"
                                            placeholder="Dias" class="w-24 border-gray-300 rounded-lg p-2 border text-sm"
                                            required>
                                        <button type="button" onclick="removeCiclo(this)"
                                            class="text-red-500 hover:text-red-700 p-2"><span
                                                class="material-icons">delete</span></button>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <button type="button" onclick="addCiclo()"
                                class="mt-3 text-cyan-600 font-medium text-sm flex items-center hover:text-cyan-800">
                                <span class="material-icons mr-1 text-sm">add_circle</span> Adicionar Ciclo
                            </button>
                        </div>

                        <script>
                            function addCiclo() {
                                const html = `
                                    <div class="flex gap-2 items-center ciclo-row">
                                        <input type="text" name="ciclo_nome[]" placeholder="Nome (Ex: Filhote)" class="flex-1 border-gray-300 rounded-lg p-2 border text-sm" required>
                                        <input type="number" name="ciclo_intervalo[]" placeholder="Dias" class="w-24 border-gray-300 rounded-lg p-2 border text-sm" required>
                                        <button type="button" onclick="removeCiclo(this)" class="text-red-500 hover:text-red-700 p-2"><span class="material-icons">delete</span></button>
                                    </div>
                                `;
                                document.getElementById('ciclos-container').insertAdjacentHTML('beforeend', html);
                            }

                            function removeCiclo(btn) {
                                btn.closest('.ciclo-row').remove();
                            }
                        </script>

                        <div class="pt-4 flex justify-end gap-3">
                            <a href="vacinas.php"
                                class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium py-2 px-6 rounded-lg transition-colors">Cancelar</a>
                            <button type="submit"
                                class="bg-cyan-600 hover:bg-cyan-700 text-white font-medium py-2 px-6 rounded-lg shadow-md transition-all">Salvar</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>

</html>