<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}
include "../database.php";
require_once __DIR__ . "/config.php";
require_once __DIR__ . "/helpers/AppHelper.php";
require_once __DIR__ . "/helpers/FiscalCatalogHelper.php";

$id_servico = $_GET['id'] ?? null;
$servico = null;
$is_edit = false;

$cnaesCatalog = FiscalCatalogHelper::getCnaes();
$atividadesCatalog = FiscalCatalogHelper::getAtividades();

if ($id_servico) {
    $link = DBConnect();
    $id_safe = mysqli_real_escape_string($link, $id_servico);
    $query = "SELECT * FROM Servicos WHERE id_servico = '$id_safe'";
    $result = DBExecute($link, $query);
    if ($result && mysqli_num_rows($result) > 0) {
        $servico = mysqli_fetch_assoc($result);
        $is_edit = true;
    }
    DBClose($link);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <title>
        <?= $is_edit ? 'Editar Serviço' : 'Novo Serviço' ?> - <?= htmlspecialchars(AppHelper::getCompanyName()) ?>
    </title>
    <?php include 'components/layout_head.php'; ?>
</head>

<body class="bg-gray-50 flex">

    <?php include 'components/sidebar.php'; ?>

    <div class="flex-1 flex flex-col lg:ml-64 min-h-screen transition-all duration-300">
        <main class="flex-1 p-6 mt-16 lg:mt-0">

            <div class="max-w-xl mx-auto">
                <div class="flex items-center mb-6">
                    <a href="servicos.php" class="mr-4 text-gray-500 hover:text-gray-700">
                        <span class="material-icons">arrow_back</span>
                    </a>
                    <h2 class="text-3xl font-bold text-gray-800">
                        <?= $is_edit ? 'Editar Serviço' : 'Cadastrar Novo Serviço' ?>
                    </h2>
                </div>

                <div class="bg-white p-8 rounded-xl shadow-sm border border-gray-100">
                    <form id="servicoForm">
                        <input type="hidden" name="action" value="<?= $is_edit ? 'editar_servico' : 'criar_servico' ?>">
                        <?php if ($is_edit): ?>
                            <input type="hidden" name="id_servico" value="<?= $servico['id_servico'] ?>">
                        <?php endif; ?>

                        <div class="space-y-6">
                            <div>
                                <label for="nome_servico" class="block text-sm font-medium text-gray-700 mb-1">Nome do
                                    Serviço</label>
                                <input type="text" id="nome_servico" name="nome_servico"
                                    value="<?= htmlspecialchars($servico['nome_servico'] ?? '') ?>" required
                                    class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition">
                            </div>

                            <div>
                                <label for="descricao_fiscal" class="block text-sm font-medium text-gray-700 mb-1">
                                    Descrição Fiscal (Opcional)
                                    <span class="text-xs text-gray-500 font-normal ml-1">- Substitui o nome do serviço na NFS-e</span>
                                </label>
                                <textarea id="descricao_fiscal" name="descricao_fiscal" rows="2"
                                    class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500 transition"
                                    placeholder="Ex: Consultoria Técnica (Deixe em branco para usar o nome do serviço)"><?= htmlspecialchars($servico['descricao_fiscal'] ?? '') ?></textarea>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="valor_sugerido" class="block text-sm font-medium text-gray-700 mb-1">Valor
                                        Sugerido (R$)</label>
                                    <input type="number" id="valor_sugerido" name="valor_sugerido" step="0.01" min="0.00"
                                        value="<?= $servico['valor_sugerido'] ?? '' ?>" required
                                        class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition">
                                </div>
                                <div>
                                    <label for="duracao_minutos" class="block text-sm font-medium text-gray-700 mb-1">
                                        Tempo Padrão de Duração (Minutos)
                                    </label>
                                    <input type="number" id="duracao_minutos" name="duracao_minutos" min="5" step="5"
                                        value="<?= $servico['duracao_minutos'] ?? 30 ?>" required
                                        class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition">
                                    <span class="text-xs text-gray-400">Referência para cálculo da agenda</span>
                                </div>
                            </div>

                            <!-- MÓDULOS DE DISPONIBILIDADE (Apenas Modo Veterinário / Pet Shop) -->
                            <?php if (AppHelper::isVetMode()): ?>
                            <div class="bg-cyan-50 border border-cyan-100 rounded-xl p-4">
                                <label class="block text-sm font-semibold text-cyan-900 mb-2">Disponibilidade do Serviço</label>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <label class="flex items-center space-x-2 cursor-pointer bg-white p-3 rounded-lg border border-cyan-200 hover:bg-cyan-50/50 transition">
                                        <input type="checkbox" name="disponivel_clinica" id="disponivel_clinica" value="1"
                                            <?= ($servico['disponivel_clinica'] ?? 1) ? 'checked' : '' ?>
                                            class="h-4 w-4 text-cyan-600 focus:ring-cyan-500 border-gray-300 rounded">
                                        <span class="text-sm font-medium text-gray-700">Módulo Clínica / Consultas</span>
                                    </label>
                                    <label class="flex items-center space-x-2 cursor-pointer bg-white p-3 rounded-lg border border-cyan-200 hover:bg-cyan-50/50 transition">
                                        <input type="checkbox" name="disponivel_banho" id="disponivel_banho" value="1"
                                            <?= ($servico['disponivel_banho'] ?? 0) ? 'checked' : '' ?>
                                            class="h-4 w-4 text-cyan-600 focus:ring-cyan-500 border-gray-300 rounded">
                                        <span class="text-sm font-medium text-gray-700">Módulo Banho e Tosa</span>
                                    </label>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- VITRINE / IDENTIFICAÇÃO VISUAL -->
                            <?php 
                            $defaultIcon = AppHelper::isVetMode() ? 'pets' : 'build';
                            $currentIcon = !empty($servico['icone_servico']) ? $servico['icone_servico'] : $defaultIcon;
                            ?>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="icone_servico" class="block text-sm font-medium text-gray-700 mb-1">
                                        Ícone do Serviço
                                    </label>
                                    <div class="flex items-center space-x-2">
                                        <input type="text" id="icone_servico" name="icone_servico"
                                            value="<?= htmlspecialchars($currentIcon) ?>"
                                            placeholder="Ex: <?= AppHelper::isVetMode() ? 'pets, shower' : 'build, work, receipt_long' ?>"
                                            class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500 transition">
                                        <div class="p-3 bg-gray-100 rounded-lg border border-gray-200 text-cyan-600 flex items-center justify-center cursor-pointer" onclick="abrirModalIconesServico()">
                                            <span class="material-icons" id="iconePreview"><?= htmlspecialchars($currentIcon) ?></span>
                                        </div>
                                        <button type="button" onclick="abrirModalIconesServico()" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-3 rounded-lg text-xs font-semibold whitespace-nowrap transition">
                                            Escolher
                                        </button>
                                    </div>
                                </div>
                                <div>
                                    <label for="imagem_url" class="block text-sm font-medium text-gray-700 mb-1">
                                        Foto / Imagem do Serviço
                                    </label>
                                    <div class="flex items-center space-x-2">
                                        <input type="url" id="imagem_url" name="imagem_url"
                                            value="<?= htmlspecialchars($servico['imagem_url'] ?? '') ?>"
                                            placeholder="https://... ou faça upload"
                                            class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500 transition">
                                        <input type="file" id="upload_foto_servico_input" accept="image/*" class="hidden" onchange="enviarFotoOracleServico(this)">
                                        <button type="button" onclick="$('#upload_foto_servico_input').click()" id="btnUploadFotoServico"
                                            class="bg-teal-600 hover:bg-teal-700 text-white px-3 py-3 rounded-lg text-xs font-semibold whitespace-nowrap flex items-center gap-1 transition shadow-sm">
                                            <span class="material-icons text-sm">cloud_upload</span> Upar Foto
                                        </button>
                                    </div>
                                    <div id="previewFotoServicoContainer" class="<?= empty($servico['imagem_url']) ? 'hidden' : '' ?> mt-2 flex items-center gap-2">
                                        <img id="previewFotoServicoImg" src="<?= htmlspecialchars($servico['imagem_url'] ?? '') ?>" class="w-12 h-12 object-cover rounded-lg border border-gray-200">
                                        <span class="text-xs text-gray-500">Preview da imagem</span>
                                    </div>
                                </div>
                            </div>

                            <!-- DADOS FISCAIS / NFSE (LARGURA TOTAL) -->
                            <div class="border-t border-gray-200 pt-6 mt-6">
                                <div class="flex items-center justify-between mb-4">
                                    <div>
                                        <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                                            <span class="material-icons text-cyan-600">receipt_long</span> Parâmetros Fiscais (NFS-e)
                                        </h3>
                                        <p class="text-xs text-gray-500 mt-0.5">Configuração tributária oficial para emissão de Notas Fiscais de Serviço no ISS DF.</p>
                                    </div>
                                    <span class="text-xs bg-cyan-50 text-cyan-700 px-3 py-1.5 rounded-full font-semibold border border-cyan-200 flex items-center gap-1 shadow-sm">
                                        <span class="material-icons text-sm text-cyan-600">verified</span> SEFIN DF / Nota Control
                                    </span>
                                </div>

                                <!-- BANNER COM SELETORES INTELIGENTES -->
                                <div class="bg-gradient-to-r from-cyan-50 via-slate-50 to-cyan-50/30 border border-cyan-200 rounded-2xl p-5 mb-6 shadow-sm">
                                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                                        <div>
                                            <label for="select_atividade_fiscal" class="block text-xs font-bold text-gray-800 mb-1.5 flex items-center gap-1.5">
                                                <span class="material-icons text-sm text-cyan-600">category</span> Atividade Municipal / Item LC 116 (Catálogo SEFIN DF)
                                            </label>
                                            <select id="select_atividade_fiscal" class="w-full p-3 border border-cyan-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-cyan-500 bg-white text-xs font-medium text-gray-800 shadow-sm">
                                                <option value="">-- Selecione para auto-preencher os campos abaixo --</option>
                                                <?php foreach ($atividadesCatalog as $ativ): 
                                                    $isMatch = (($servico['codigo_tributacao_municipio'] ?? '') == $ativ['codigo_tributacao'] || ($servico['item_lista_servico'] ?? '') == $ativ['item_lc116']);
                                                ?>
                                                    <option value="<?= $ativ['codigo_tributacao'] ?>" 
                                                        data-item="<?= $ativ['item_lc116'] ?>" 
                                                        data-aliquota="<?= number_format($ativ['aliquota'], 2, '.', '') ?>" 
                                                        data-cnae="<?= $ativ['cnae_sugerido'] ?>"
                                                        <?= $isMatch ? 'selected' : '' ?>>
                                                        [Cód. <?= $ativ['codigo_tributacao'] ?> | LC <?= $ativ['item_lc116'] ?>] <?= htmlspecialchars($ativ['nome_curto']) ?> (Alíq. <?= number_format($ativ['aliquota'], 2, ',', '.') ?>%<?= $ativ['principal'] ? ' - Principal' : '' ?>)
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <p class="text-[11px] text-cyan-800 mt-1.5 flex items-center gap-1">
                                                <span class="material-icons text-xs text-cyan-600">auto_fix_high</span> Auto-preenche Item LC 116, Código de Tributação, Alíquota (2,00%) e sugere o CNAE correspondente.
                                            </p>
                                        </div>

                                        <div>
                                            <label for="select_cnae_fiscal" class="block text-xs font-bold text-gray-800 mb-1.5 flex items-center gap-1.5">
                                                <span class="material-icons text-sm text-cyan-600">business</span> CNAE Autorizado (Ficha Cadastral)
                                            </label>
                                            <select id="select_cnae_fiscal" class="w-full p-3 border border-cyan-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-cyan-500 bg-white text-xs font-medium text-gray-800 shadow-sm">
                                                <option value="">-- Selecione o CNAE --</option>
                                                <?php foreach ($cnaesCatalog as $cnae): 
                                                    $isCnaeMatch = (($servico['codigo_cnae'] ?? '') == $cnae['codigo']);
                                                ?>
                                                    <option value="<?= $cnae['codigo'] ?>" <?= $isCnaeMatch ? 'selected' : '' ?>>
                                                        <?= $cnae['codigo'] ?> - <?= htmlspecialchars($cnae['descricao']) ?> (<?= $cnae['tipo'] ?>)
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <p class="text-[11px] text-cyan-800 mt-1.5 flex items-center gap-1">
                                                <span class="material-icons text-xs text-cyan-600">fact_check</span> CNAEs oficiais autorizados para emissão de nota no CNPJ da empresa.
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- CAMPOS TÉCNICOS COM LEGENDAS COMPLETAS -->
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                                    <div>
                                        <label for="item_lista_servico" class="block text-sm font-semibold text-gray-700 mb-1">
                                            Item LC 116/03
                                        </label>
                                        <input type="text" id="item_lista_servico" name="item_lista_servico"
                                            value="<?= htmlspecialchars($servico['item_lista_servico'] ?? '') ?>"
                                            placeholder="Ex: 01.05"
                                            class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500 transition">
                                        <p class="text-[11px] text-gray-500 mt-1">Classificação do serviço segundo a Lei Complementar 116/2003 (ex: <strong>01.05</strong> - Licenciamento de software).</p>
                                    </div>

                                    <div>
                                        <label for="codigo_cnae" class="block text-sm font-semibold text-gray-700 mb-1">
                                            CNAE (Apenas números)
                                        </label>
                                        <input type="text" id="codigo_cnae" name="codigo_cnae"
                                            value="<?= htmlspecialchars($servico['codigo_cnae'] ?? '') ?>" placeholder="Ex: 6202300"
                                            class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500 transition">
                                        <p class="text-[11px] text-gray-500 mt-1">Código de 7 dígitos da atividade econômica vinculada ao CNPJ (ex: <strong>6202300</strong>).</p>
                                    </div>

                                    <div>
                                        <label for="codigo_tributacao_municipio" class="block text-sm font-semibold text-gray-700 mb-1">
                                            Cód. Tributação Municipal
                                        </label>
                                        <input type="text" id="codigo_tributacao_municipio" name="codigo_tributacao_municipio"
                                            value="<?= htmlspecialchars($servico['codigo_tributacao_municipio'] ?? '') ?>"
                                            placeholder="Ex: 105"
                                            class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500 transition">
                                        <p class="text-[11px] text-gray-500 mt-1">Código interno da atividade na prefeitura / SEFIN DF (ex: <strong>105</strong>, <strong>106</strong>, <strong>101</strong>).</p>
                                    </div>

                                    <div>
                                        <label for="codigo_nbs" class="block text-sm font-semibold text-gray-700 mb-1">
                                            Código NBS
                                        </label>
                                        <input type="text" id="codigo_nbs" name="codigo_nbs"
                                            value="<?= htmlspecialchars($servico['codigo_nbs'] ?? '') ?>" placeholder="Ex: 115080000"
                                            class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500 transition">
                                        <p class="text-[11px] text-gray-500 mt-1">Nomenclatura Brasileira de Serviços e Intangíveis da Receita Federal (ex: <strong>115080000</strong>).</p>
                                    </div>

                                    <div>
                                        <label for="aliquota_iss" class="block text-sm font-semibold text-gray-700 mb-1">
                                            Alíquota ISS (%)
                                        </label>
                                        <input type="number" step="0.01" id="aliquota_iss" name="aliquota_iss"
                                            value="<?= htmlspecialchars($servico['aliquota_iss'] ?? '2.00') ?>"
                                            class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500 transition font-bold text-gray-800 bg-gray-50">
                                        <p class="text-[11px] text-gray-500 mt-1">Percentual de imposto devido ao município (conforme Ficha Cadastral: <strong>2,00%</strong>).</p>
                                    </div>

                                    <div>
                                        <label for="iss_retido" class="block text-sm font-semibold text-gray-700 mb-1">
                                            ISS Retido na Fonte?
                                        </label>
                                        <select id="iss_retido" name="iss_retido"
                                            class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500 transition bg-white">
                                            <option value="0" <?= ($servico['iss_retido'] ?? 0) == 0 ? 'selected' : '' ?>>Não (Normal - Prestador recolhe)</option>
                                            <option value="1" <?= ($servico['iss_retido'] ?? 0) == 1 ? 'selected' : '' ?>>Sim (Retido na Fonte pelo Cliente)</option>
                                        </select>
                                        <p class="text-[11px] text-gray-500 mt-1">Indica se o tomador do serviço reterá o valor do imposto do pagamento final.</p>
                                    </div>
                                </div>

                                <div class="mt-5">
                                    <label for="descricao_nfse_padrao" class="block text-sm font-semibold text-gray-700 mb-1">
                                        Descrição Padrão da NFS-e (Template)
                                    </label>
                                    <textarea id="descricao_nfse_padrao" name="descricao_nfse_padrao" rows="3"
                                        placeholder="Texto que sairá discriminado no corpo da nota fiscal. Use {MES} para substituir dinamicamente pelo mês de competência."
                                        class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500 transition"><?= htmlspecialchars($servico['descricao_nfse_padrao'] ?? '') ?></textarea>
                                    <p class="text-[11px] text-gray-500 mt-1">Texto exibido na discriminação dos serviços da nota. A tag <strong>{MES}</strong> será substituída automaticamente (ex: "Setembro/2026").</p>
                                </div>
                            </div>

                            <div class="pt-6 flex justify-end border-t border-gray-100 mt-6">
                                <a href="servicos.php"
                                    class="px-6 py-3 mr-3 bg-gray-100 text-gray-700 font-medium rounded-lg hover:bg-gray-200 transition">Cancelar</a>
                                <button type="submit"
                                    class="px-6 py-3 bg-cyan-600 text-white font-medium rounded-lg hover:bg-cyan-700 shadow-md transition transform hover:scale-105">
                                    <?= $is_edit ? 'Salvar Alterações' : 'Cadastrar Serviço' ?>
                                </button>
                            </div>
                        </div>
                    </form>
                    <div id="formMessage" class="mt-4 text-center font-medium hidden"></div>
                </div>
            </div>

        </main>
    </div>

    <!-- Modal Seletor de Ícones Material Icons -->
    <div id="modalIconesServico" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4 bg-black bg-opacity-50">
        <div class="bg-white rounded-2xl max-w-xl w-full p-6 shadow-2xl overflow-y-auto max-h-[85vh]">
            <div class="flex justify-between items-center mb-4 border-b pb-3">
                <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                    <span class="material-icons text-cyan-600">palette</span> Escolher Ícone
                </h3>
                <button type="button" onclick="fecharModalIconesServico()" class="text-gray-400 hover:text-gray-600">
                    <span class="material-icons">close</span>
                </button>
            </div>

            <div class="mb-4">
                <input type="text" id="filtroIconesServico" placeholder="Filtrar ícones..."
                    class="w-full p-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500">
            </div>

            <div class="grid grid-cols-5 sm:grid-cols-8 gap-3 max-h-[50vh] overflow-y-auto p-1" id="gridIconesServico">
                <!-- Populated via JS -->
            </div>
        </div>
    </div>

    <?php include 'components/layout_scripts.php'; ?>
    <script>
        const listaIconesServicos = <?= AppHelper::isVetMode() ? json_encode([
            'pets', 'shower', 'bathtub', 'cut', 'spa', 'card_giftcard', 'wash', 'brush',
            'clean_hands', 'health_and_safety', 'favorite', 'star', 'diamond', 'inventory_2',
            'local_offer', 'verified', 'bolt', 'category', 'loyalty', 'medical_services',
            'healing', 'vaccines', 'psychology', 'emergency', 'local_hospital', 'content_cut',
            'dry_cleaning', 'soap', 'sanitizer', 'auto_awesome', 'water_drop', 'flare',
            'shield', 'sentiment_very_satisfied', 'face', 'cruelty_free'
        ]) : json_encode([
            'build', 'work', 'receipt_long', 'construction', 'handyman', 'design_services',
            'computer', 'code', 'support_agent', 'analytics', 'campaign', 'paid', 'shopping_bag',
            'local_shipping', 'schedule', 'settings', 'verified', 'star', 'diamond', 'bolt',
            'category', 'loyalty', 'shield', 'assignment', 'inventory_2', 'account_balance',
            'engineering', 'psychology', 'group', 'badge', 'store', 'devices'
        ]) ?>;

        function abrirModalIconesServico() {
            renderGridIconesServico(listaIconesServicos);
            $('#filtroIconesServico').val('');
            $('#modalIconesServico').removeClass('hidden');
        }

        function fecharModalIconesServico() {
            $('#modalIconesServico').addClass('hidden');
        }

        function renderGridIconesServico(icones) {
            const grid = $('#gridIconesServico');
            grid.empty();
            icones.forEach(ic => {
                grid.append(`
                    <button type="button" onclick="selecionarIconeServico('${ic}')"
                        class="p-3 bg-gray-50 hover:bg-cyan-50 hover:text-cyan-600 border border-gray-100 hover:border-cyan-300 rounded-xl flex flex-col items-center justify-center transition group">
                        <span class="material-icons text-2xl text-gray-600 group-hover:text-cyan-600">${ic}</span>
                        <span class="text-[9px] text-gray-400 group-hover:text-cyan-700 truncate w-full text-center mt-1">${ic}</span>
                    </button>
                `);
            });
        }

        function selecionarIconeServico(nomeIcone) {
            $('#icone_servico').val(nomeIcone);
            $('#iconePreview').text(nomeIcone);
            fecharModalIconesServico();
        }

        $('#filtroIconesServico').on('input', function() {
            const termo = $(this).val().toLowerCase().trim();
            const filtrados = listaIconesServicos.filter(i => i.toLowerCase().includes(termo));
            renderGridIconesServico(filtrados);
        });

        function enviarFotoOracleServico(input) {
            if (!input.files || !input.files[0]) return;
            const file = input.files[0];
            const formData = new FormData();
            formData.append('action', 'upload_imagem_oracle');
            formData.append('foto', file);

            const btn = $('#btnUploadFotoServico');
            const origHtml = btn.html();
            btn.prop('disabled', true).html('<span class="material-icons animate-spin text-sm">sync</span> Enviando...');

            $.ajax({
                url: 'app.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(res) {
                    btn.prop('disabled', false).html(origHtml);
                    if (res.success && res.url) {
                        $('#imagem_url').val(res.url);
                        $('#previewFotoServicoImg').attr('src', res.url.startsWith('http') ? res.url : res.url);
                        $('#previewFotoServicoContainer').removeClass('hidden');
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

        $(document).ready(function () {
            $('#icone_servico').on('input', function() {
                const val = $(this).val().trim();
                $('#iconePreview').text(val || '<?= AppHelper::isVetMode() ? "pets" : "build" ?>');
            });

            // Auto-preenchimento ao selecionar Atividade Municipal
            $('#select_atividade_fiscal').on('change', function() {
                const opt = $(this).find('option:selected');
                if (!opt.val()) return;

                const item = opt.data('item');
                const aliquota = opt.data('aliquota');
                const cnae = opt.data('cnae');
                const codTrib = opt.val();

                if (item) $('#item_lista_servico').val(item);
                if (codTrib) $('#codigo_tributacao_municipio').val(codTrib);
                if (aliquota !== undefined) $('#aliquota_iss').val(aliquota);
                if (cnae) {
                    $('#codigo_cnae').val(cnae);
                    $('#select_cnae_fiscal').val(cnae);
                }
            });

            // Sincronização ao selecionar CNAE
            $('#select_cnae_fiscal').on('change', function() {
                const cnaeVal = $(this).val();
                if (cnaeVal) {
                    $('#codigo_cnae').val(cnaeVal);
                }
            });

            $('#servicoForm').on('submit', function (e) {
                e.preventDefault();
                const btn = $(this).find('button[type="submit"]');
                const originalText = btn.text();
                btn.prop('disabled', true).text('Processando...');

                $.ajax({
                    url: 'app.php',
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
                                window.location.href = 'servicos.php';
                            }, 1500);
                        } else {
                            msgDiv.addClass('text-red-600');
                            btn.prop('disabled', false).text(originalText);
                        }
                        msgDiv.show();
                    },
                    error: function () {
                        alert('Erro de conexão.');
                        btn.prop('disabled', false).text(originalText);
                    }
                });
            });
        });
    </script>
</body>

</html>