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

$id_pacote = $_GET['id'] ?? null;
$pacote = null;
$itens_pacote = [];
$is_edit = false;

$link = DBConnect();
$servicos = [];

if ($link) {
    // Fetch all services available for banho/estética or geral (apenas ativos)
    $qServ = "SELECT id_servico, nome_servico, valor_sugerido, duracao_minutos, icone_servico 
              FROM Servicos 
              WHERE (ativo = 1 OR ativo IS NULL)
              ORDER BY nome_servico ASC";
    $rServ = DBExecute($link, $qServ);
    if ($rServ) {
        while ($s = mysqli_fetch_assoc($rServ)) {
            $servicos[] = $s;
        }
    }

    if ($id_pacote) {
        $id_safe = (int)$id_pacote;
        $qP = "SELECT * FROM Pacotes WHERE id_pacote = $id_safe";
        $rP = DBExecute($link, $qP);
        if ($rP && mysqli_num_rows($rP) > 0) {
            $pacote = mysqli_fetch_assoc($rP);
            $is_edit = true;

            $qI = "SELECT * FROM PacoteItens WHERE id_pacote = $id_safe";
            $rI = DBExecute($link, $qI);
            if ($rI) {
                while ($it = mysqli_fetch_assoc($rI)) {
                    $itens_pacote[] = $it;
                }
            }
        }
    }

    DBClose($link);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <title><?= $is_edit ? 'Editar Pacote' : 'Novo Pacote' ?> - DinoVet</title>
    <?php include '../../components/layout_head.php'; ?>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body class="bg-gray-50 flex">

    <?php include '../../components/sidebar.php'; ?>

    <div class="flex-1 flex flex-col lg:ml-64 min-h-screen transition-all duration-300">
        <main class="flex-1 p-6 mt-16 lg:mt-0">

            <div class="max-w-3xl mx-auto">
                <div class="flex items-center mb-6">
                    <a href="pacotes.php" class="mr-4 text-gray-500 hover:text-gray-700">
                        <span class="material-icons">arrow_back</span>
                    </a>
                    <div>
                        <h2 class="text-3xl font-bold text-gray-800">
                            <?= $is_edit ? 'Editar Pacote / Combo' : 'Cadastrar Novo Pacote' ?>
                        </h2>
                        <p class="text-gray-500 text-sm">Monte combos de serviços com quantidades e regras financeiras.</p>
                    </div>
                </div>

                <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100">
                    <form id="pacoteForm">
                        <input type="hidden" name="action" value="save_pacote">
                        <?php if ($is_edit): ?>
                            <input type="hidden" name="id_pacote" value="<?= $pacote['id_pacote'] ?>">
                        <?php endif; ?>

                        <div class="space-y-6">

                            <!-- Nome do Pacote -->
                            <div>
                                <label for="nome_pacote" class="block text-sm font-medium text-gray-700 mb-1">Nome do Pacote / Combo *</label>
                                <input type="text" id="nome_pacote" name="nome_pacote"
                                    value="<?= htmlspecialchars($pacote['nome_pacote'] ?? '') ?>" required
                                    placeholder="Ex: PetBasic (3 Banhos + 1 Tosa Higiênica)"
                                    class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500 transition font-medium">
                            </div>

                            <!-- Descrição -->
                            <div>
                                <label for="descricao" class="block text-sm font-medium text-gray-700 mb-1">Descrição Comercial</label>
                                <textarea id="descricao" name="descricao" rows="2"
                                    placeholder="Detalhes para a recepção ou vitrine do cliente..."
                                    class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500 transition text-sm"><?= htmlspecialchars($pacote['descricao'] ?? '') ?></textarea>
                            </div>

                            <!-- Valores e Recorrência -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-gray-50 p-4 rounded-xl border border-gray-100">
                                <div>
                                    <label for="valor_total" class="block text-sm font-medium text-gray-700 mb-1">Valor Total do Pacote (R$) *</label>
                                    <input type="number" id="valor_total" name="valor_total" step="0.01" min="0.00"
                                        value="<?= $pacote['valor_total'] ?? '' ?>" required
                                        placeholder="0.00"
                                        class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500 font-bold text-cyan-700 bg-white">
                                    <div class="flex items-center justify-between mt-1">
                                        <span class="text-xs text-gray-400">Soma dos itens avulsos:</span>
                                        <span id="somaAvulsos" class="text-xs font-semibold text-gray-600">R$ 0,00</span>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Opção de Recorrência</label>
                                    <label class="flex items-center space-x-2 cursor-pointer bg-white p-3 rounded-lg border border-gray-200">
                                        <input type="checkbox" name="is_recorrente" id="is_recorrente" value="1"
                                            <?= ($pacote['is_recorrente'] ?? 0) ? 'checked' : '' ?>
                                            class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300 rounded">
                                        <span class="text-sm font-semibold text-gray-800">Pacote com Cobrança Recorrente</span>
                                    </label>
                                    <div id="intervaloContainer" class="<?= ($pacote['is_recorrente'] ?? 0) ? '' : 'hidden' ?> mt-2">
                                        <label class="block text-xs text-gray-500 mb-1">Intervalo de Renovação (dias)</label>
                                        <input type="number" name="intervalo_dias_recorrencia" id="intervalo_dias_recorrencia"
                                            value="<?= $pacote['intervalo_dias_recorrencia'] ?? 30 ?>" min="1"
                                            class="w-full p-2 border border-gray-300 rounded-lg text-sm bg-white">
                                    </div>
                                </div>
                            </div>

                            <!-- COMPOSIÇÃO DOS SERVIÇOS DO PACOTE -->
                            <div class="border-t pt-4">
                                <div class="flex justify-between items-center mb-3">
                                    <div>
                                        <h3 class="text-lg font-bold text-gray-800 flex items-center gap-1.5">
                                            <span class="material-icons text-cyan-600">checklist</span> Composição dos Serviços
                                        </h3>
                                        <p class="text-xs text-gray-500">Defina os serviços e a quantidade de utilizações inclusas neste combo.</p>
                                    </div>
                                    <button type="button" onclick="adicionarItemServico()"
                                        class="bg-cyan-50 hover:bg-cyan-100 text-cyan-700 font-semibold px-3 py-1.5 rounded-lg text-xs flex items-center gap-1 transition">
                                        <span class="material-icons text-sm">add_circle</span> Adicionar Serviço
                                    </button>
                                </div>

                                <div id="itensContainer" class="space-y-3">
                                    <!-- Dynamic Items -->
                                </div>
                            </div>

                            <!-- IDENTIFICAÇÃO VISUAL -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 border-t pt-4">
                                <div>
                                    <label for="icone" class="block text-sm font-medium text-gray-700 mb-1">Ícone do Pacote</label>
                                    <div class="flex items-center space-x-2">
                                        <input type="text" id="icone" name="icone"
                                            value="<?= htmlspecialchars($pacote['icone'] ?? 'card_giftcard') ?>"
                                            class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500 transition text-sm">
                                        <div class="p-3 bg-gray-100 rounded-lg border border-gray-200 text-cyan-600 flex items-center justify-center cursor-pointer" onclick="abrirModalIcones()">
                                            <span class="material-icons" id="iconePreview"><?= htmlspecialchars($pacote['icone'] ?? 'card_giftcard') ?></span>
                                        </div>
                                        <button type="button" onclick="abrirModalIcones()" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-3 rounded-lg text-xs font-semibold whitespace-nowrap transition">
                                            Escolher
                                        </button>
                                    </div>
                                </div>
                                <div>
                                    <label for="imagem_url" class="block text-sm font-medium text-gray-700 mb-1">Foto / Banner do Pacote</label>
                                    <div class="flex items-center space-x-2">
                                        <input type="url" id="imagem_url" name="imagem_url"
                                            value="<?= htmlspecialchars($pacote['imagem_url'] ?? '') ?>"
                                            placeholder="https://... ou faça upload"
                                            class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500 transition text-sm">
                                        <input type="file" id="upload_foto_input" accept="image/*" class="hidden" onchange="enviarFotoOracle(this)">
                                        <button type="button" onclick="$('#upload_foto_input').click()" id="btnUploadFoto"
                                            class="bg-teal-600 hover:bg-teal-700 text-white px-3 py-3 rounded-lg text-xs font-semibold whitespace-nowrap flex items-center gap-1 transition shadow-sm">
                                            <span class="material-icons text-sm">cloud_upload</span> Upar Foto
                                        </button>
                                    </div>
                                    <div id="previewFotoContainer" class="<?= empty($pacote['imagem_url']) ? 'hidden' : '' ?> mt-2 flex items-center gap-2">
                                        <img id="previewFotoImg" src="<?= htmlspecialchars($pacote['imagem_url'] ?? '') ?>" class="w-12 h-12 object-cover rounded-lg border border-gray-200">
                                        <span class="text-xs text-gray-500">Preview da imagem</span>
                                    </div>
                                </div>
                            </div>

                            <div class="pt-4 flex justify-between items-center border-t">
                                <?php if ($is_edit): ?>
                                    <button type="button" onclick="excluirPacote(<?= (int)$pacote['id_pacote'] ?>)"
                                        class="text-red-600 hover:text-red-800 text-sm font-medium flex items-center gap-1">
                                        <span class="material-icons text-sm">delete</span> Excluir Pacote
                                    </button>
                                <?php else: ?>
                                    <div></div>
                                <?php endif; ?>

                                <div class="flex gap-2">
                                    <a href="pacotes.php"
                                        class="px-5 py-2.5 bg-gray-100 text-gray-700 font-medium rounded-lg hover:bg-gray-200 transition text-sm">Cancelar</a>
                                    <button type="submit"
                                        class="px-6 py-2.5 bg-cyan-600 hover:bg-cyan-700 text-white font-semibold rounded-lg shadow-md transition transform hover:scale-[1.02] text-sm">
                                        <?= $is_edit ? 'Salvar Alterações' : 'Criar Pacote' ?>
                                    </button>
                                </div>
                            </div>

                        </div>
                    </form>
                    <div id="formMessage" class="mt-4 text-center font-medium hidden"></div>
                </div>
            </div>

        </main>
    </div>

    <!-- Modal Seletor de Ícones Material Icons -->
    <div id="modalIcones" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4 bg-black bg-opacity-50">
        <div class="bg-white rounded-2xl max-w-xl w-full p-6 shadow-2xl overflow-y-auto max-h-[85vh]">
            <div class="flex justify-between items-center mb-4 border-b pb-3">
                <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                    <span class="material-icons text-cyan-600">palette</span> Escolher Ícone
                </h3>
                <button type="button" onclick="fecharModalIcones()" class="text-gray-400 hover:text-gray-600">
                    <span class="material-icons">close</span>
                </button>
            </div>

            <div class="mb-4">
                <input type="text" id="filtroIcones" placeholder="Filtrar ícones..."
                    class="w-full p-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500">
            </div>

            <div class="grid grid-cols-5 sm:grid-cols-8 gap-3 max-h-[50vh] overflow-y-auto p-1" id="gridIcones">
                <!-- Populated via JS -->
            </div>
        </div>
    </div>

    <!-- Template for JS Item Row -->
    <template id="itemTemplate">
        <div class="item-row flex items-center gap-3 bg-white p-3 rounded-xl border border-gray-200 shadow-sm">
            <div class="flex-1">
                <label class="block text-xs text-gray-500 mb-1">Serviço</label>
                <select name="itens_servico[]" class="servico-select w-full border-gray-300 rounded-lg p-2 text-sm border focus:ring-cyan-500 focus:border-cyan-500" required>
                    <option value="">Selecione o serviço...</option>
                    <?php foreach ($servicos as $s): ?>
                        <option value="<?= $s['id_servico'] ?>" data-preco="<?= $s['valor_sugerido'] ?>" data-duracao="<?= $s['duracao_minutos'] ?>">
                            <?= htmlspecialchars($s['nome_servico']) ?> (R$ <?= number_format($s['valor_sugerido'], 2, ',', '.') ?> • <?= $s['duracao_minutos'] ?>m)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="w-28">
                <label class="block text-xs text-gray-500 mb-1">Qtd. Inclusa</label>
                <input type="number" name="itens_quantidade[]" value="1" min="1" max="100" required
                    class="qtd-input w-full border-gray-300 rounded-lg p-2 text-sm border text-center font-semibold focus:ring-cyan-500 focus:border-cyan-500">
            </div>
            <div class="pt-5">
                <button type="button" onclick="removerItem(this)" class="p-2 text-red-500 hover:text-red-700 hover:bg-red-50 rounded-lg transition" title="Remover item">
                    <span class="material-icons text-xl">delete_outline</span>
                </button>
            </div>
        </div>
    </template>

    <?php include '../../components/layout_scripts.php'; ?>
    <script>
        const initialItems = <?= json_encode($itens_pacote) ?>;

        $(document).ready(function () {
            $('#icone').on('input', function() {
                const val = $(this).val().trim();
                $('#iconePreview').text(val || 'card_giftcard');
            });

            $('#is_recorrente').on('change', function() {
                if ($(this).is(':checked')) {
                    $('#intervaloContainer').removeClass('hidden');
                } else {
                    $('#intervaloContainer').addClass('hidden');
                }
            });

            // Populate existing items or add 1 default row
            if (initialItems.length > 0) {
                initialItems.forEach(it => {
                    adicionarItemServico(it.id_servico, it.quantidade);
                });
            } else {
                adicionarItemServico();
            }

            $(document).on('change', '.servico-select, .qtd-input', function() {
                recalcularSomaAvulsos();
            });

            $('#pacoteForm').on('submit', function (e) {
                e.preventDefault();
                const btn = $(this).find('button[type="submit"]');
                const originalText = btn.text();
                btn.prop('disabled', true).text('Salvando...');

                $.ajax({
                    url: '../../app.php',
                    type: 'POST',
                    data: $(this).serialize(),
                    dataType: 'json',
                    success: function (response) {
                        const msgDiv = $('#formMessage');
                        msgDiv.removeClass('hidden text-green-600 text-red-600');
                        msgDiv.text(response.message);

                        if (response.success) {
                            msgDiv.addClass('text-green-600');
                            setTimeout(() => {
                                window.location.href = 'pacotes.php';
                            }, 1200);
                        } else {
                            msgDiv.addClass('text-red-600');
                            btn.prop('disabled', false).text(originalText);
                        }
                        msgDiv.show();
                    },
                    error: function () {
                        alert('Erro de conexão com o servidor.');
                        btn.prop('disabled', false).text(originalText);
                    }
                });
            });
        });

        function adicionarItemServico(idServico = null, qtd = 1) {
            const template = document.getElementById('itemTemplate');
            const clone = template.content.cloneNode(true);
            const container = document.getElementById('itensContainer');
            
            if (idServico) {
                $(clone).find('.servico-select').val(idServico);
            }
            if (qtd) {
                $(clone).find('.qtd-input').val(qtd);
            }

            container.appendChild(clone);
            recalcularSomaAvulsos();
        }

        function removerItem(btn) {
            const rows = $('.item-row');
            if (rows.length <= 1) {
                alert('O pacote deve ter pelo menos 1 serviço.');
                return;
            }
            $(btn).closest('.item-row').remove();
            recalcularSomaAvulsos();
        }

        function recalcularSomaAvulsos() {
            let total = 0;
            $('.item-row').each(function() {
                const select = $(this).find('.servico-select option:selected');
                const preco = parseFloat(select.data('preco')) || 0;
                const qtd = parseInt($(this).find('.qtd-input').val()) || 1;
                total += (preco * qtd);
            });
            $('#somaAvulsos').text('R$ ' + total.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
        }

        function excluirPacote(idPacote) {
            if (confirm('Tem certeza que deseja desativar este pacote?')) {
                $.post('../../app.php', { action: 'delete_pacote', id_pacote: idPacote }, function(res) {
                    if (res.success) {
                        alert(res.message);
                        window.location.href = 'pacotes.php';
                    } else {
                        alert(res.message);
                    }
                }, 'json');
            }
        }

        // --- ICON SELECTOR MODAL ---
        const listaIconesMaterial = [
            'shower', 'bathtub', 'pets', 'cut', 'spa', 'card_giftcard', 'wash', 'brush',
            'clean_hands', 'health_and_safety', 'favorite', 'star', 'diamond', 'inventory_2',
            'local_offer', 'verified', 'bolt', 'category', 'loyalty', 'medical_services',
            'healing', 'vaccines', 'psychology', 'emergency', 'local_hospital', 'content_cut',
            'dry_cleaning', 'soap', 'sanitizer', 'auto_awesome', 'water_drop', 'flare',
            'redeem', 'military_tech', 'workspace_premium', 'military_tech', 'loyalty',
            'shield', 'lock', 'sentiment_very_satisfied', 'face', 'cruelty_free'
        ];

        function abrirModalIcones() {
            renderGridIcones(listaIconesMaterial);
            $('#filtroIcones').val('');
            $('#modalIcones').removeClass('hidden');
        }

        function fecharModalIcones() {
            $('#modalIcones').addClass('hidden');
        }

        function renderGridIcones(icones) {
            const grid = $('#gridIcones');
            grid.empty();
            icones.forEach(ic => {
                grid.append(`
                    <button type="button" onclick="selecionarIcone('${ic}')"
                        class="p-3 bg-gray-50 hover:bg-cyan-50 hover:text-cyan-600 border border-gray-100 hover:border-cyan-300 rounded-xl flex flex-col items-center justify-center transition group">
                        <span class="material-icons text-2xl text-gray-600 group-hover:text-cyan-600">${ic}</span>
                        <span class="text-[9px] text-gray-400 group-hover:text-cyan-700 truncate w-full text-center mt-1">${ic}</span>
                    </button>
                `);
            });
        }

        function selecionarIcone(nomeIcone) {
            $('#icone').val(nomeIcone);
            $('#iconePreview').text(nomeIcone);
            fecharModalIcones();
        }

        $('#filtroIcones').on('input', function() {
            const termo = $(this).val().toLowerCase().trim();
            const filtrados = listaIconesMaterial.filter(i => i.toLowerCase().includes(termo));
            renderGridIcones(filtrados);
        });

        // --- UPLOAD DE FOTO (ORACLE CLOUD) ---
        function enviarFotoOracle(input) {
            if (!input.files || !input.files[0]) return;
            const file = input.files[0];
            const formData = new FormData();
            formData.append('action', 'upload_imagem_oracle');
            formData.append('foto', file);

            const btn = $('#btnUploadFoto');
            const origHtml = btn.html();
            btn.prop('disabled', true).html('<span class="material-icons animate-spin text-sm">sync</span> Enviando...');

            $.ajax({
                url: '../../app.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(res) {
                    btn.prop('disabled', false).html(origHtml);
                    if (res.success && res.url) {
                        $('#imagem_url').val(res.url);
                        $('#previewFotoImg').attr('src', res.url.startsWith('http') ? res.url : '../../' + res.url);
                        $('#previewFotoContainer').removeClass('hidden');
                        alert('Foto carregada com sucesso!');
                    } else {
                        alert(res.message || 'Erro ao enviar foto.');
                    }
                },
                error: function() {
                    btn.prop('disabled', false).html(origHtml);
                    alert('Erro de conexão ao enviar imagem.');
                }
            });
        }
    </script>
</body>

</html>
