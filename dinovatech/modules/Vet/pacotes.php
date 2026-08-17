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

    // Contratos Ativos
    $contratos_ativos = [];
    $qContr = "SELECT cp.*, p.nome_pacote, p.valor_total, c.nome as nome_tutor, pt.nome as nome_pet_exclusivo,
                      DATE_FORMAT(cp.data_aquisicao, '%d/%m/%Y') as data_aquisicao_fmt,
                      (SELECT SUM(qtd_total - qtd_utilizada) FROM ClientePacoteSaldos cps WHERE cps.id_cliente_pacote = cp.id_cliente_pacote) as saldo_total_restante
               FROM ClientePacotes cp
               JOIN Pacotes p ON cp.id_pacote = p.id_pacote
               JOIN Clientes c ON cp.id_cliente = c.id_cliente
               LEFT JOIN Pets pt ON cp.id_pet = pt.id_pet
               WHERE cp.status = 'ativo'
               ORDER BY cp.data_aquisicao DESC LIMIT 50";
    $resContr = DBExecute($link, $qContr);
    if ($resContr) {
        while ($ct = mysqli_fetch_assoc($resContr)) {
            $contratos_ativos[] = $ct;
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
                    <p class="text-gray-500">Combos promocionais de banho e tosa com controle de créditos, extratos e recorrências.</p>
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
                <div class="bg-white p-12 rounded-xl shadow-sm border border-gray-100 text-center mb-8">
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
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 mb-10">
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
                                        <strong><?= (int)$p['total_clientes_ativos'] ?></strong> contrato(s) ativo(s)
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

            <!-- Seção de Contratos Ativos e Extratos -->
            <?php if (!empty($contratos_ativos)): ?>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <div class="flex justify-between items-center mb-4">
                        <div>
                            <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                                <span class="material-icons text-teal-600">receipt_long</span> Contratos Ativos & Extrato de Consumo
                            </h3>
                            <p class="text-xs text-gray-500">Acompanhe os créditos restantes e consulte o extrato de utilizações de cada tutor.</p>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-gray-600">
                            <thead class="bg-gray-50 text-gray-700 text-xs uppercase font-semibold">
                                <tr>
                                    <th class="p-3">Tutor / Cliente</th>
                                    <th class="p-3">Pet Vinculado</th>
                                    <th class="p-3">Pacote</th>
                                    <th class="p-3">Data Aquisição</th>
                                    <th class="p-3">Saldo Restante</th>
                                    <th class="p-3 text-right">Ação</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <?php foreach ($contratos_ativos as $ca): ?>
                                    <tr class="hover:bg-gray-50/80 transition">
                                        <td class="p-3 font-semibold text-gray-900"><?= htmlspecialchars($ca['nome_tutor']) ?></td>
                                        <td class="p-3">
                                            <?php if (!empty($ca['nome_pet_exclusivo'])): ?>
                                                <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-teal-50 text-teal-700 border border-teal-200">
                                                    🐾 <?= htmlspecialchars($ca['nome_pet_exclusivo']) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                                                    Todos os pets (Compartilhado)
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="p-3 font-medium text-gray-800"><?= htmlspecialchars($ca['nome_pacote']) ?></td>
                                        <td class="p-3 text-xs text-gray-500 font-mono"><?= $ca['data_aquisicao_fmt'] ?></td>
                                        <td class="p-3">
                                            <span class="px-2.5 py-1 rounded-full text-xs font-extrabold bg-amber-50 text-amber-800 border border-amber-200">
                                                <?= (int)$ca['saldo_total_restante'] ?> créditos
                                            </span>
                                        </td>
                                        <td class="p-3 text-right">
                                            <button type="button" onclick="abrirExtratoPacoteAdmin(<?= (int)$ca['id_cliente_pacote'] ?>)"
                                                class="px-3 py-1.5 bg-slate-800 hover:bg-slate-900 text-white rounded-lg text-xs font-semibold transition flex items-center gap-1 ml-auto shadow-sm">
                                                <span class="material-icons text-xs">receipt_long</span> Ver Extrato
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
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
                        <label class="block text-sm font-medium text-gray-700 mb-1">Vincular a um Pet Específico (Opcional)</label>
                        <select name="id_pet" id="vincular_id_pet" class="w-full border-gray-300 rounded-lg p-2.5 border text-sm">
                            <option value="">Compartilhado entre todos os pets do tutor</option>
                        </select>
                        <span class="text-[11px] text-gray-400 mt-1 block">Se não selecionar, os créditos poderão ser usados por qualquer pet do tutor.</span>
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

    <!-- Modal Extrato do Pacote (Admin / Clínico) -->
    <div id="modalExtratoAdmin" class="fixed inset-0 bg-black bg-opacity-60 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-2xl w-full p-6 shadow-2xl overflow-y-auto max-h-[90vh]">
            <div class="flex justify-between items-center mb-4 border-b pb-3">
                <div class="flex items-center gap-2">
                    <span class="material-icons text-teal-600">receipt_long</span>
                    <h3 class="text-lg font-bold text-gray-800" id="extratoTitulo">Extrato de Utilização do Pacote</h3>
                </div>
                <button type="button" onclick="$('#modalExtratoAdmin').addClass('hidden')" class="text-gray-400 hover:text-gray-600">
                    <span class="material-icons">close</span>
                </button>
            </div>

            <div id="extratoConteudo" class="space-y-6">
                <!-- Dynamic Content -->
            </div>
        </div>
    </div>

    <?php include '../../components/layout_scripts.php'; ?>
    <script>
        function openVincularModal(idPacote = null) {
            $('#formVincularPacote')[0].reset();
            $('#vincular_id_pet').html('<option value="">Compartilhado entre todos os pets do tutor</option>');
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

        $('#vincular_id_cliente').on('change', function() {
            const idCli = $(this).val();
            const selectPet = $('#vincular_id_pet');
            selectPet.html('<option value="">Compartilhado entre todos os pets do tutor</option>');
            if (idCli) {
                $.getJSON('../../app.php', { action: 'get_pets_by_cliente', id_cliente: idCli }, function(res) {
                    if (res.success && res.pets && res.pets.length > 0) {
                        res.pets.forEach(p => {
                            selectPet.append(`<option value="${p.id_pet}">Exclusivo para: ${p.nome} (Porte ${p.porte})</option>`);
                        });
                    }
                });
            }
        });

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

        function abrirExtratoPacoteAdmin(idContrato) {
            $('#extratoConteudo').html('<div class="text-center py-8 text-gray-400">Carregando extrato...</div>');
            $('#modalExtratoAdmin').removeClass('hidden');

            $.getJSON('../../app.php', { action: 'get_extrato_pacote', id_cliente_pacote: idContrato }, function(res) {
                if (!res.success) {
                    $('#extratoConteudo').html(`<div class="p-4 bg-red-50 text-red-600 rounded-lg">${res.message}</div>`);
                    return;
                }

                const p = res.pacote;
                const petBadge = p.nome_pet_exclusivo 
                    ? `<span class="px-2 py-0.5 rounded-full text-xs font-bold bg-teal-100 text-teal-800">🐾 Exclusivo para: ${p.nome_pet_exclusivo}</span>`
                    : `<span class="px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700">Compartilhado entre todos os pets</span>`;

                let saldosHtml = '';
                res.saldos.forEach(s => {
                    const pct = Math.round((s.qtd_utilizada / s.qtd_total) * 100);
                    saldosHtml += `
                        <div class="bg-gray-50 rounded-xl p-3 border border-gray-100">
                            <div class="flex justify-between items-center text-xs mb-1">
                                <span class="font-bold text-gray-800">${s.nome_servico}</span>
                                <span class="font-bold text-cyan-700">${s.saldo_restante} restante(s) (${s.qtd_utilizada}/${s.qtd_total})</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-cyan-600 h-2 rounded-full" style="width: ${pct}%"></div>
                            </div>
                        </div>
                    `;
                });

                let consumosHtml = '';
                if (res.consumos && res.consumos.length > 0) {
                    consumosHtml = `
                        <div class="space-y-2">
                            ${res.consumos.map(c => `
                                <div class="flex items-center justify-between p-3 bg-white rounded-xl border border-gray-100 shadow-sm text-xs">
                                    <div class="flex items-center gap-2.5">
                                        <span class="w-7 h-7 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold">✓</span>
                                        <div>
                                            <span class="font-bold text-gray-800 block">${c.nome_servico} • Pet: ${c.nome_pet}</span>
                                            <span class="text-[11px] text-gray-500">${c.observacao || 'Utilizado'}</span>
                                        </div>
                                    </div>
                                    <span class="font-mono text-gray-400 font-medium">${c.data_consumo_fmt}</span>
                                </div>
                            `).join('')}
                        </div>
                    `;
                } else {
                    consumosHtml = `<p class="text-xs text-gray-400 italic bg-gray-50 p-4 rounded-xl text-center">Nenhuma utilização registrada ainda neste pacote.</p>`;
                }

                $('#extratoConteudo').html(`
                    <div class="bg-gradient-to-br from-slate-900 to-slate-800 text-white rounded-2xl p-5 shadow-lg">
                        <div class="flex justify-between items-start mb-2">
                            <div>
                                <span class="text-teal-400 font-bold text-xs uppercase tracking-wider block">Contrato de Pacote</span>
                                <h4 class="text-xl font-extrabold">${p.nome_pacote}</h4>
                                <span class="text-xs text-gray-300">Tutor: <strong>${p.nome_tutor}</strong></span>
                            </div>
                            <span class="text-sm font-extrabold text-teal-300">R$ ${parseFloat(p.valor_total).toFixed(2).replace('.', ',')}</span>
                        </div>
                        <div class="flex items-center justify-between pt-3 border-t border-slate-700 text-xs">
                            <span class="text-gray-400">Adquirido em: ${p.data_aquisicao_fmt}</span>
                            ${petBadge}
                        </div>
                    </div>

                    <div>
                        <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Status dos Créditos de Serviços</h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            ${saldosHtml}
                        </div>
                    </div>

                    <div>
                        <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Histórico de Utilizações</h4>
                        ${consumosHtml}
                    </div>
                `);
            });
        }
    </script>
</body>

</html>

