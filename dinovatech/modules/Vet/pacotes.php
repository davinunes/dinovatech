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

$pacotes = [];
$clientes = [];
$link = DBConnect();
$search = "";

if ($link) {
    $search = isset($_GET['search']) ? mysqli_real_escape_string($link, $_GET['search']) : '';
    $where_clause = "WHERE p.ativo = 1";
    if ($search) {
        $where_clause .= " AND (p.nome_pacote LIKE '%$search%' OR p.descricao LIKE '%$search%')";
    }

    $query = "SELECT p.*, 
              (SELECT COUNT(*) FROM ClientePacotes cp WHERE cp.id_pacote = p.id_pacote AND cp.status = 'ativo') as total_clientes_ativos
              FROM Pacotes p 
              $where_clause 
              ORDER BY p.nome_pacote ASC";
    $result = DBExecute($link, $query);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            // Fetch items
            $id_pacote_safe = (int)$row['id_pacote'];
            $qItems = "SELECT pi.*, s.nome_servico, s.duracao_minutos, s.icone_servico, s.valor_sugerido 
                       FROM PacoteItens pi 
                       JOIN Servicos s ON pi.id_servico = s.id_servico 
                       WHERE pi.id_pacote = $id_pacote_safe";
            $resItems = DBExecute($link, $qItems);
            $items = [];
            if ($resItems) {
                while ($it = mysqli_fetch_assoc($resItems)) {
                    $items[] = $it;
                }
            }
            $row['itens'] = $items;
            $pacotes[] = $row;
        }
    }

    // Clientes for vincular modal
    $resC = DBExecute($link, "SELECT id_cliente, nome, cpf_cnpj FROM Clientes WHERE ativo = 1 ORDER BY nome ASC");
    if ($resC) {
        while ($c = mysqli_fetch_assoc($resC)) {
            $clientes[] = $c;
        }
    }

    DBClose($link);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <title>Pacotes e Combos - DinoVet</title>
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

            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                <div>
                    <h2 class="text-3xl font-bold text-gray-800">Pacotes & Combos</h2>
                    <p class="text-gray-500">Combos promocionais de banho e tosa com controle de créditos e recorrências.</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <button type="button" onclick="openVincularModal()"
                        class="bg-emerald-600 hover:bg-emerald-700 text-white font-medium py-2 px-4 rounded-lg flex items-center transition-colors shadow-sm">
                        <span class="material-icons mr-1.5 text-lg">card_giftcard</span> Vincular ao Cliente
                    </button>
                    <a href="pacote_form.php"
                        class="bg-cyan-600 hover:bg-cyan-700 text-white font-medium py-2 px-4 rounded-lg flex items-center transition-colors shadow-sm">
                        <span class="material-icons mr-1.5 text-lg">add</span> Novo Pacote
                    </a>
                </div>
            </div>

            <!-- Search Bar -->
            <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 mb-6">
                <form method="GET" class="flex gap-2">
                    <div class="relative flex-1">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                            <span class="material-icons">search</span>
                        </span>
                        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                            placeholder="Buscar Pacote por nome ou descrição..."
                            class="w-full py-2 pl-10 pr-4 text-gray-700 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:border-cyan-500 focus:bg-white focus:ring-0">
                    </div>
                    <button type="submit"
                        class="bg-slate-800 hover:bg-slate-900 text-white px-4 py-2 rounded-lg font-medium transition-colors">Buscar</button>
                </form>
            </div>

            <!-- Packages Grid -->
            <?php if (empty($pacotes)): ?>
                <div class="bg-white p-12 rounded-xl shadow-sm border border-gray-100 text-center">
                    <div class="w-16 h-16 bg-cyan-50 text-cyan-600 rounded-full flex items-center justify-center mx-auto mb-4">
                        <span class="material-icons text-3xl">inventory_2</span>
                    </div>
                    <h3 class="text-lg font-bold text-gray-800 mb-1">Nenhum pacote cadastrado</h3>
                    <p class="text-gray-500 text-sm mb-6">Crie pacotes e combos de banho/tosa para fidelizar seus clientes.</p>
                    <a href="pacote_form.php" class="inline-flex items-center px-4 py-2 bg-cyan-600 text-white rounded-lg font-medium hover:bg-cyan-700 transition">
                        <span class="material-icons mr-1.5 text-sm">add</span> Criar Primeiro Pacote
                    </a>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                    <?php foreach ($pacotes as $p): ?>
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 hover:border-cyan-200 transition-all p-6 flex flex-col justify-between">
                            <div>
                                <div class="flex items-start justify-between gap-3 mb-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-cyan-500 to-teal-600 text-white flex items-center justify-center shadow-md">
                                            <span class="material-icons text-2xl"><?= htmlspecialchars($p['icone'] ?: 'card_giftcard') ?></span>
                                        </div>
                                        <div>
                                            <h3 class="font-bold text-lg text-gray-900 leading-snug"><?= htmlspecialchars($p['nome_pacote']) ?></h3>
                                            <div class="flex items-center gap-2 mt-1">
                                                <?php if ($p['is_recorrente']): ?>
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-purple-50 text-purple-700 border border-purple-200">
                                                        <span class="material-icons text-[12px] mr-1">repeat</span> Recorrente (<?= (int)$p['intervalo_dias_recorrencia'] ?> dias)
                                                    </span>
                                                <?php else: ?>
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-gray-100 text-gray-600">
                                                        Avulso / Sem Vigência
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <?php if (!empty($p['descricao'])): ?>
                                    <p class="text-sm text-gray-500 mb-4"><?= htmlspecialchars($p['descricao']) ?></p>
                                <?php endif; ?>

                                <!-- Items Included -->
                                <div class="bg-gray-50/80 rounded-xl p-3.5 mb-4 border border-gray-100">
                                    <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider block mb-2">Serviços Inclusos no Combo</span>
                                    <ul class="space-y-2 text-sm">
                                        <?php if (!empty($p['itens'])): ?>
                                            <?php foreach ($p['itens'] as $it): ?>
                                                <li class="flex items-center justify-between text-gray-700">
                                                    <span class="flex items-center gap-2">
                                                        <span class="w-5 h-5 rounded-full bg-cyan-100 text-cyan-700 flex items-center justify-center font-bold text-xs">
                                                            <?= (int)$it['quantidade'] ?>x
                                                        </span>
                                                        <span class="font-medium"><?= htmlspecialchars($it['nome_servico']) ?></span>
                                                    </span>
                                                    <span class="text-xs text-gray-400 font-mono"><?= (int)$it['duracao_minutos'] ?>m cada</span>
                                                </li>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <li class="text-xs text-gray-400 italic">Nenhum serviço configurado.</li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            </div>

                            <div>
                                <div class="flex items-baseline justify-between pt-3 border-t border-gray-100 mb-4">
                                    <span class="text-xs text-gray-500">
                                        <strong><?= (int)$p['total_clientes_ativos'] ?></strong> cliente(s) ativo(s)
                                    </span>
                                    <div class="text-right">
                                        <span class="text-xs text-gray-400 block">Valor do Pacote</span>
                                        <span class="text-2xl font-extrabold text-cyan-700">R$ <?= number_format($p['valor_total'], 2, ',', '.') ?></span>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-2">
                                    <button type="button" onclick="openVincularModal(<?= (int)$p['id_pacote'] ?>)"
                                        class="w-full bg-emerald-50 hover:bg-emerald-100 text-emerald-700 py-2 rounded-lg font-medium text-xs flex items-center justify-center gap-1 transition">
                                        <span class="material-icons text-sm">person_add</span> Vincular
                                    </button>
                                    <a href="pacote_form.php?id=<?= $p['id_pacote'] ?>"
                                        class="w-full bg-gray-100 hover:bg-gray-200 text-gray-700 py-2 rounded-lg font-medium text-xs flex items-center justify-center gap-1 transition text-center">
                                        <span class="material-icons text-sm">edit</span> Editar
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        </main>
    </div>

    <!-- Modal Vincular Pacote ao Cliente -->
    <div id="modalVincular" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl">
            <div class="flex justify-between items-center mb-4">
                <div class="flex items-center gap-2">
                    <span class="material-icons text-emerald-600">card_giftcard</span>
                    <h3 class="text-lg font-bold text-gray-800">Vincular Pacote ao Tutor</h3>
                </div>
                <button type="button" onclick="closeVincularModal()" class="text-gray-400 hover:text-gray-600">
                    <span class="material-icons">close</span>
                </button>
            </div>

            <form id="formVincularPacote">
                <input type="hidden" name="action" value="vincular_cliente_pacote">
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Cliente / Tutor *</label>
                        <select name="id_cliente" id="vincular_id_cliente" class="w-full select2-modal" required>
                            <option value="">Selecione um cliente...</option>
                            <?php foreach ($clientes as $c): ?>
                                <option value="<?= $c['id_cliente'] ?>">
                                    <?= htmlspecialchars($c['nome']) ?> (<?= $c['cpf_cnpj'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Pacote *</label>
                        <select name="id_pacote" id="vincular_id_pacote" class="w-full border-gray-300 rounded-lg p-2.5 border text-sm" required>
                            <option value="">Selecione o pacote...</option>
                            <?php foreach ($pacotes as $p): ?>
                                <option value="<?= $p['id_pacote'] ?>" data-recorrente="<?= $p['is_recorrente'] ?>">
                                    <?= htmlspecialchars($p['nome_pacote']) ?> - R$ <?= number_format($p['valor_total'], 2, ',', '.') ?> <?= $p['is_recorrente'] ? '(Recorrente)' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div id="recorrenciaInfo" class="hidden bg-purple-50 border border-purple-200 rounded-lg p-3 text-xs text-purple-800">
                        <span class="font-semibold flex items-center gap-1">
                            <span class="material-icons text-sm">info</span> Pacote Recorrente
                        </span>
                        Este pacote criará uma recorrência financeira no perfil do cliente para cobrança nas próximas faturas.
                    </div>
                </div>

                <div id="vincularMessage" class="mt-4 text-xs font-medium text-center hidden"></div>

                <div class="mt-6 flex justify-end gap-2">
                    <button type="button" onclick="closeVincularModal()" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200">Cancelar</button>
                    <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm font-medium shadow transition">Confirmar Vínculo</button>
                </div>
            </form>
        </div>
    </div>

    <?php include '../../components/layout_scripts.php'; ?>
    <script>
        function openVincularModal(idPacote = null) {
            $('#formVincularPacote')[0].reset();
            $('#vincularMessage').addClass('hidden').text('');
            if (idPacote) {
                $('#vincular_id_pacote').val(idPacote).trigger('change');
            }
            $('#modalVincular').removeClass('hidden');
            $('#vincular_id_cliente').select2({
                dropdownParent: $('#modalVincular'),
                placeholder: "Selecione o cliente...",
                width: '100%'
            });
        }

        function closeVincularModal() {
            $('#modalVincular').addClass('hidden');
        }

        $('#vincular_id_pacote').on('change', function() {
            const isRec = $(this).find('option:selected').data('recorrente');
            if (isRec == 1) {
                $('#recorrenciaInfo').removeClass('hidden');
            } else {
                $('#recorrenciaInfo').addClass('hidden');
            }
        });

        $('#formVincularPacote').on('submit', function(e) {
            e.preventDefault();
            const btn = $(this).find('button[type="submit"]');
            btn.prop('disabled', true).text('Processando...');

            $.ajax({
                url: '../../app.php',
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(response) {
                    const msg = $('#vincularMessage');
                    msg.removeClass('hidden text-green-600 text-red-600');
                    if (response.success) {
                        msg.addClass('text-green-600').text(response.message);
                        setTimeout(() => {
                            location.reload();
                        }, 1200);
                    } else {
                        msg.addClass('text-red-600').text(response.message);
                        btn.prop('disabled', false).text('Confirmar Vínculo');
                    }
                },
                error: function() {
                    alert('Erro de conexão.');
                    btn.prop('disabled', false).text('Confirmar Vínculo');
                }
            });
        });
    </script>
</body>

</html>
