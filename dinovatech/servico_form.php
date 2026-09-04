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

            <div class="max-w-4xl mx-auto">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center gap-3">
                        <a href="servicos.php" class="p-2 text-gray-500 hover:text-gray-800 hover:bg-white rounded-xl transition shadow-sm border border-gray-200">
                            <span class="material-icons">arrow_back</span>
                        </a>
                        <div>
                            <h2 class="text-2xl font-extrabold text-gray-800 flex items-center gap-3">
                                <?= $is_edit ? 'Editar Serviço' : 'Cadastrar Novo Serviço' ?>
                                <?php if ($is_edit): ?>
                                    <?php if (($servico['ativo'] ?? 1) == 1): ?>
                                        <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-emerald-100 text-emerald-800 border border-emerald-200 flex items-center gap-1">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Ativo
                                        </span>
                                    <?php else: ?>
                                        <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-700 border border-gray-300 flex items-center gap-1">
                                            <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span> Desativado
                                        </span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </h2>
                            <p class="text-xs text-gray-500 mt-0.5">Parâmetros comerciais do catálogo e configuração tributária para emissão de NFS-e.</p>
                        </div>
                    </div>
                </div>

                <form id="servicoForm" class="space-y-6">
                    <input type="hidden" name="action" value="<?= $is_edit ? 'editar_servico' : 'criar_servico' ?>">
                    <?php if ($is_edit): ?>
                        <input type="hidden" name="id_servico" value="<?= $servico['id_servico'] ?>">
                    <?php endif; ?>

                    <!-- CARD 1: DADOS COMERCIAIS DO SERVIÇO -->
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200/80">
                        <div class="flex items-center gap-2 pb-4 mb-5 border-b border-gray-100">
                            <div class="w-8 h-8 rounded-lg bg-cyan-50 text-cyan-700 flex items-center justify-center font-bold">
                                <span class="material-icons text-base">inventory_2</span>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-gray-800">1. Dados Comerciais & Catálogo</h3>
                                <p class="text-xs text-gray-400">Identificação do serviço, valor de tabela e tempo estimado.</p>
                            </div>
                        </div>

                        <div class="space-y-5">
                            <div>
                                <label for="nome_servico" class="block text-sm font-semibold text-gray-700 mb-1">
                                    Nome do Serviço <span class="text-rose-500">*</span>
                                </label>
                                <input type="text" id="nome_servico" name="nome_servico"
                                    value="<?= htmlspecialchars($servico['nome_servico'] ?? '') ?>" required
                                    placeholder="Ex: Consultoria em TI, Licenciamento de Software, Banho e Tosa..."
                                    class="w-full p-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-cyan-500 transition font-medium">
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div>
                                    <label for="valor_sugerido" class="block text-sm font-semibold text-gray-700 mb-1">
                                        Valor Sugerido (R$) <span class="text-rose-500">*</span>
                                    </label>
                                    <input type="number" id="valor_sugerido" name="valor_sugerido" step="0.01" min="0.00"
                                        value="<?= $servico['valor_sugerido'] ?? '' ?>" required
                                        class="w-full p-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-cyan-500 transition font-bold text-gray-800">
                                    <span class="text-[11px] text-gray-400">Preço padrão de venda</span>
                                </div>
                                <div>
                                    <label for="duracao_minutos" class="block text-sm font-semibold text-gray-700 mb-1">
                                        Duração Estimada
                                    </label>
                                    <div class="relative">
                                        <input type="number" id="duracao_minutos" name="duracao_minutos" min="5" step="5"
                                            value="<?= $servico['duracao_minutos'] ?? 30 ?>" required
                                            class="w-full p-3 pr-12 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-cyan-500 transition">
                                        <span class="absolute inset-y-0 right-0 flex items-center pr-3 text-xs text-gray-400 font-medium pointer-events-none">min</span>
                                    </div>
                                    <span class="text-[11px] text-gray-400">Tempo de agenda</span>
                                </div>
                                <div>
                                    <label for="ativo" class="block text-sm font-semibold text-gray-700 mb-1">
                                        Status no Catálogo
                                    </label>
                                    <select id="ativo" name="ativo"
                                        class="w-full p-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-cyan-500 transition bg-white font-medium text-sm">
                                        <option value="1" <?= ($servico['ativo'] ?? 1) == 1 ? 'selected' : '' ?>>Ativo (Disponível)</option>
                                        <option value="0" <?= isset($servico['ativo']) && $servico['ativo'] == 0 ? 'selected' : '' ?>>Desativado (Oculto)</option>
                                    </select>
                                    <span class="text-[11px] text-gray-400">Visibilidade para novos atendimentos</span>
                                </div>
                            </div>

                            <!-- MÓDULOS DE DISPONIBILIDADE (Apenas Modo Veterinário / Pet Shop) -->
                            <?php if (AppHelper::isVetMode()): ?>
                            <div class="bg-cyan-50/70 border border-cyan-100 rounded-xl p-4">
                                <label class="block text-xs font-bold text-cyan-900 mb-2">Disponibilidade do Serviço por Módulo</label>
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

                            <!-- ÍCONE E IMAGEM -->
                            <?php 
                            $defaultIcon = AppHelper::isVetMode() ? 'pets' : 'build';
                            $currentIcon = !empty($servico['icone_servico']) ? $servico['icone_servico'] : $defaultIcon;
                            ?>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
                                <div>
                                    <label for="icone_servico" class="block text-sm font-semibold text-gray-700 mb-1">
                                        Ícone do Serviço
                                    </label>
                                    <div class="flex items-center space-x-2">
                                        <input type="text" id="icone_servico" name="icone_servico"
                                            value="<?= htmlspecialchars($currentIcon) ?>"
                                            placeholder="Ex: <?= AppHelper::isVetMode() ? 'pets, shower' : 'build, work, receipt_long' ?>"
                                            class="w-full p-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-cyan-500 transition text-sm font-mono">
                                        <div class="p-3 bg-gray-100 rounded-xl border border-gray-200 text-cyan-600 flex items-center justify-center cursor-pointer" onclick="abrirModalIconesServico()">
                                            <span class="material-icons" id="iconePreview"><?= htmlspecialchars($currentIcon) ?></span>
                                        </div>
                                        <button type="button" onclick="abrirModalIconesServico()" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-3.5 py-3 rounded-xl text-xs font-semibold whitespace-nowrap transition">
                                            Escolher
                                        </button>
                                    </div>
                                </div>
                                <div>
                                    <label for="imagem_url" class="block text-sm font-semibold text-gray-700 mb-1">
                                        Foto do Serviço (Oracle Storage)
                                    </label>
                                    <div class="flex items-center space-x-2">
                                        <input type="url" id="imagem_url" name="imagem_url"
                                            value="<?= htmlspecialchars($servico['imagem_url'] ?? '') ?>"
                                            placeholder="https://... ou faça upload"
                                            class="w-full p-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-cyan-500 transition text-sm">
                                        <input type="file" id="upload_foto_servico_input" accept="image/*" class="hidden" onchange="enviarFotoOracleServico(this)">
                                        <button type="button" onclick="$('#upload_foto_servico_input').click()" id="btnUploadFotoServico"
                                            class="bg-teal-600 hover:bg-teal-700 text-white px-3.5 py-3 rounded-xl text-xs font-semibold whitespace-nowrap flex items-center gap-1 transition shadow-sm">
                                            <span class="material-icons text-sm">cloud_upload</span> Upar
                                        </button>
                                    </div>
                                    <div id="previewFotoServicoContainer" class="<?= empty($servico['imagem_url']) ? 'hidden' : '' ?> mt-2 flex items-center gap-2">
                                        <img id="previewFotoServicoImg" src="<?= htmlspecialchars($servico['imagem_url'] ?? '') ?>" class="w-10 h-10 object-cover rounded-lg border border-gray-200">
                                        <span class="text-xs text-gray-500">Preview salvo</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- CARD 2: DISCRIMINAÇÃO E TEXTOS NA NFS-E (BASE DO CATÁLOGO) -->
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200/80">
                        <div class="flex items-center justify-between pb-4 mb-4 border-b border-gray-100">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-700 flex items-center justify-center font-bold">
                                    <span class="material-icons text-base">description</span>
                                </div>
                                <div>
                                    <h3 class="text-base font-bold text-gray-800">2. Textos & Discriminação na NFS-e</h3>
                                    <p class="text-xs text-gray-400">Descrições padrão impressas no corpo da Nota Fiscal.</p>
                                </div>
                            </div>
                            <span class="text-[11px] bg-blue-50 text-blue-700 px-3 py-1 rounded-full font-semibold border border-blue-200">
                                Base do Catálogo
                            </span>
                        </div>

                        <!-- ALERTA DE PRECEDÊNCIA -->
                        <div class="bg-blue-50/70 border border-blue-200 rounded-xl p-3.5 mb-5 flex items-start gap-3">
                            <span class="material-icons text-blue-600 text-lg flex-shrink-0 mt-0.5">info</span>
                            <div class="text-xs text-blue-900 leading-relaxed">
                                <strong>Regra de Precedência:</strong> Estes textos servem como padrão inicial para faturas deste serviço. Caso o cliente possua um <strong>Contrato Recorrente</strong>, a descrição personalizada definida no contrato terá precedência sobre este cadastro e será impressa na NFS-e.
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <label for="descricao_fiscal" class="block text-sm font-semibold text-gray-700 mb-1">
                                    Nome / Descrição Fiscal Resumida (Opcional)
                                </label>
                                <textarea id="descricao_fiscal" name="descricao_fiscal" rows="2"
                                    class="w-full p-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-cyan-500 transition text-sm"
                                    placeholder="Ex: Consultoria Técnica em Informática (Deixe em branco para usar o nome do serviço)"><?= htmlspecialchars($servico['descricao_fiscal'] ?? '') ?></textarea>
                                <p class="text-[11px] text-gray-400 mt-1">Substitui o nome fantasia do serviço nos campos de identificação da NFS-e.</p>
                            </div>

                            <div>
                                <label for="descricao_nfse_padrao" class="block text-sm font-semibold text-gray-700 mb-1">
                                    Template de Discriminação Completa (Corpo da Nota)
                                </label>
                                <textarea id="descricao_nfse_padrao" name="descricao_nfse_padrao" rows="3"
                                    placeholder="Ex: Prestação de serviços de suporte e manutenção de sistemas referente ao mês de {MES}."
                                    class="w-full p-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-cyan-500 transition text-sm"><?= htmlspecialchars($servico['descricao_nfse_padrao'] ?? '') ?></textarea>
                                <p class="text-[11px] text-gray-400 mt-1">A tag <strong>{MES}</strong> será substituída dinamicamente pelo mês/ano de competência da fatura (ex: "Setembro/2026").</p>
                            </div>
                        </div>
                    </div>

                    <!-- CARD 3: PARÂMETROS FISCAIS GERAIS (COMUNS A AMBOS OS PROVEDORES) -->
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200/80">
                        <div class="flex items-center justify-between pb-4 mb-4 border-b border-gray-100">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-700 flex items-center justify-center font-bold">
                                    <span class="material-icons text-base">receipt_long</span>
                                </div>
                                <div>
                                    <h3 class="text-base font-bold text-gray-800">3. Parâmetros Fiscais Gerais (ISS DF & ABRASF)</h3>
                                    <p class="text-xs text-gray-400">Enquadramento da LC 116/03, código municipal DF e alíquota de ISS.</p>
                                </div>
                            </div>
                            <span class="text-[11px] bg-emerald-50 text-emerald-700 px-3 py-1 rounded-full font-semibold border border-emerald-200">
                                Comum a Ambos os Provedores
                            </span>
                        </div>

                        <!-- ALERTA DE PRECEDÊNCIA -->
                        <div class="bg-amber-50/70 border border-amber-200 rounded-xl p-3.5 mb-5 flex items-start gap-3">
                            <span class="material-icons text-amber-600 text-lg flex-shrink-0 mt-0.5">swap_horiz</span>
                            <div class="text-xs text-amber-900 leading-relaxed">
                                <strong>Sobreposição em Contratos:</strong> Estes parâmetros fiscais são a regra geral do serviço. Em contratos recorrentes (<a href="contratos.php" class="underline font-semibold hover:text-amber-950">Recorrências</a>), você pode personalizar alíquota, retenção de ISS na fonte e CNAE para clientes que possuam regimes tributários específicos.
                            </div>
                        </div>

                        <!-- BANNER SELETOR INTELIGENTE SEFIN DF -->
                        <div class="bg-gradient-to-r from-cyan-50 via-slate-50 to-cyan-50/40 border border-cyan-200 rounded-2xl p-4 mb-6 shadow-sm">
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                                <div>
                                    <label for="select_atividade_fiscal" class="block text-xs font-bold text-gray-800 mb-1.5 flex items-center gap-1.5">
                                        <span class="material-icons text-sm text-cyan-600">category</span> Atividade Municipal / Item LC 116 (Catálogo SEFIN DF)
                                    </label>
                                    <select id="select_atividade_fiscal" class="w-full p-2.5 border border-cyan-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-cyan-500 bg-white text-xs font-medium text-gray-800 shadow-sm">
                                        <option value="">-- Selecione para auto-preencher os campos abaixo --</option>
                                        <?php foreach ($atividadesCatalog as $ativ): 
                                            $isMatch = (($servico['codigo_tributacao_municipio'] ?? '') == $ativ['codigo_tributacao'] || ($servico['item_lista_servico'] ?? '') == $ativ['item_lc116']);
                                        ?>
                                            <option value="<?= $ativ['codigo_tributacao'] ?>" 
                                                data-item="<?= $ativ['item_lc116'] ?>" 
                                                data-trib-nac="<?= $ativ['codigo_tributacao_nacional'] ?? '' ?>"
                                                data-trib-issqn="<?= $ativ['tributacao_issqn'] ?? 1 ?>"
                                                data-aliquota="<?= number_format($ativ['aliquota'], 2, '.', '') ?>" 
                                                data-cnae="<?= $ativ['cnae_sugerido'] ?>"
                                                <?= $isMatch ? 'selected' : '' ?>>
                                                [Cód. <?= $ativ['codigo_tributacao'] ?> | LC <?= $ativ['item_lc116'] ?>] <?= htmlspecialchars($ativ['nome_curto']) ?> (Alíq. <?= number_format($ativ['aliquota'], 2, ',', '.') ?>%<?= $ativ['principal'] ? ' - Principal' : '' ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <p class="text-[11px] text-cyan-800 mt-1.5 flex items-center gap-1">
                                        <span class="material-icons text-xs text-cyan-600">auto_fix_high</span> Auto-preenche Item LC 116, Código Nacional, Código Municipal, Alíquota e CNAE.
                                    </p>
                                </div>

                                <div>
                                    <label for="select_cnae_fiscal" class="block text-xs font-bold text-gray-800 mb-1.5 flex items-center gap-1.5">
                                        <span class="material-icons text-sm text-cyan-600">business</span> CNAE Autorizado (Ficha Cadastral)
                                    </label>
                                    <select id="select_cnae_fiscal" class="w-full p-2.5 border border-cyan-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-cyan-500 bg-white text-xs font-medium text-gray-800 shadow-sm">
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
                                        <span class="material-icons text-xs text-cyan-600">fact_check</span> CNAEs autorizados na Ficha Cadastral do CNPJ.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- CAMPOS TÉCNICOS GERAIS -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div>
                                <label for="item_lista_servico" class="block text-xs font-semibold text-gray-700 mb-1">
                                    Item LC 116/03 <span class="text-rose-500">*</span>
                                </label>
                                <input type="text" id="item_lista_servico" name="item_lista_servico"
                                    value="<?= htmlspecialchars($servico['item_lista_servico'] ?? '') ?>"
                                    placeholder="Ex: 01.07"
                                    class="w-full p-2.5 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-cyan-500 transition text-sm">
                                <p class="text-[10px] text-gray-400 mt-1">Classificação oficial LC 116 (ex: <strong>01.07</strong>).</p>
                            </div>

                            <div>
                                <label for="codigo_tributacao_municipio" class="block text-xs font-semibold text-gray-700 mb-1">
                                    Cód. Tributação Municipal (DF) <span class="text-rose-500">*</span>
                                </label>
                                <input type="text" id="codigo_tributacao_municipio" name="codigo_tributacao_municipio"
                                    value="<?= htmlspecialchars($servico['codigo_tributacao_municipio'] ?? '') ?>"
                                    placeholder="Ex: 106"
                                    class="w-full p-2.5 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-cyan-500 transition text-sm">
                                <p class="text-[10px] text-gray-400 mt-1">Código da atividade na SEFIN DF (ex: <strong>106</strong>).</p>
                            </div>

                            <div>
                                <label for="codigo_cnae" class="block text-xs font-semibold text-gray-700 mb-1">
                                    CNAE (7 dígitos) <span class="text-rose-500">*</span>
                                </label>
                                <input type="text" id="codigo_cnae" name="codigo_cnae"
                                    value="<?= htmlspecialchars($servico['codigo_cnae'] ?? '') ?>" placeholder="Ex: 6204000"
                                    class="w-full p-2.5 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-cyan-500 transition text-sm">
                                <p class="text-[10px] text-gray-400 mt-1">Atividade econômica do prestador (apenas números).</p>
                            </div>

                            <div>
                                <label for="codigo_nbs" class="block text-xs font-semibold text-gray-700 mb-1">
                                    Código NBS v2.0 (9 dígitos)
                                </label>
                                <input type="text" id="codigo_nbs" name="codigo_nbs" maxlength="9"
                                    value="<?= htmlspecialchars($servico['codigo_nbs'] ?? '') ?>" placeholder="Ex: 115080000"
                                    class="w-full p-2.5 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-cyan-500 transition text-sm">
                                <p class="text-[10px] text-gray-400 mt-1">Nomenclatura Brasileira de Serviços.</p>
                            </div>

                            <div>
                                <label for="aliquota_iss" class="block text-xs font-semibold text-gray-700 mb-1">
                                    Alíquota ISS (%) <span class="text-rose-500">*</span>
                                </label>
                                <input type="number" step="0.01" id="aliquota_iss" name="aliquota_iss"
                                    value="<?= htmlspecialchars($servico['aliquota_iss'] ?? '2.00') ?>"
                                    class="w-full p-2.5 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-cyan-500 transition font-bold text-gray-800 bg-gray-50 text-sm">
                                <p class="text-[10px] text-gray-400 mt-1">Percentual devido ao DF (padrão: <strong>2,00%</strong>).</p>
                            </div>

                            <div>
                                <label for="iss_retido" class="block text-xs font-semibold text-gray-700 mb-1">
                                    ISS Retido na Fonte?
                                </label>
                                <select id="iss_retido" name="iss_retido"
                                    class="w-full p-2.5 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-cyan-500 transition bg-white text-sm">
                                    <option value="0" <?= ($servico['iss_retido'] ?? 0) == 0 ? 'selected' : '' ?>>Não (Prestador recolhe)</option>
                                    <option value="1" <?= ($servico['iss_retido'] ?? 0) == 1 ? 'selected' : '' ?>>Sim (Retido pelo Cliente)</option>
                                </select>
                                <p class="text-[10px] text-gray-400 mt-1">Se o cliente deve descontar o imposto.</p>
                            </div>

                            <div class="md:col-span-2 lg:col-span-3">
                                <label for="tributacao_issqn" class="block text-xs font-semibold text-gray-700 mb-1">
                                    Enquadramento / Situação da Tributação do ISSQN
                                </label>
                                <select id="tributacao_issqn" name="tributacao_issqn"
                                    class="w-full p-2.5 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-cyan-500 transition bg-white text-sm">
                                    <option value="1" <?= ($servico['tributacao_issqn'] ?? 1) == 1 ? 'selected' : '' ?>>1 - Operação Tributável (Normal)</option>
                                    <option value="2" <?= ($servico['tributacao_issqn'] ?? 1) == 2 ? 'selected' : '' ?>>2 - Imunidade (CF/88 Art. 150)</option>
                                    <option value="3" <?= ($servico['tributacao_issqn'] ?? 1) == 3 ? 'selected' : '' ?>>3 - Exportação de Serviço para o Exterior</option>
                                    <option value="4" <?= ($servico['tributacao_issqn'] ?? 1) == 4 ? 'selected' : '' ?>>4 - Não Incidência de ISS</option>
                                </select>
                                <p class="text-[10px] text-gray-400 mt-1">Regra geral: <strong>1 - Operação Tributável</strong> para a maioria das empresas privadas.</p>
                            </div>
                        </div>
                    </div>

                    <!-- CARD 4: NOVO PADRÃO NACIONAL & REFORMA TRIBUTÁRIA (IBS/CBS - v1.01) -->
                    <div class="bg-gradient-to-b from-white to-cyan-50/30 p-6 rounded-2xl shadow-sm border-2 border-cyan-200">
                        <div class="flex items-center justify-between pb-4 mb-4 border-b border-cyan-100">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-lg bg-cyan-600 text-white flex items-center justify-center font-bold shadow-sm">
                                    <span class="material-icons text-base">account_balance</span>
                                </div>
                                <div>
                                    <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
                                        4. Novo Padrão Nacional & Reforma Tributária (IBS / CBS - v1.01)
                                    </h3>
                                    <p class="text-xs text-cyan-800">Campos exclusivos da DPS Nacional e transição da EC 132/2023.</p>
                                </div>
                            </div>
                            <span class="text-[11px] bg-cyan-600 text-white px-3 py-1 rounded-full font-bold shadow-sm">
                                Novo Padrão Nacional
                            </span>
                        </div>

                        <!-- ALERTA DO PADRÃO NACIONAL -->
                        <div class="bg-white border border-cyan-200 rounded-xl p-3.5 mb-5 flex items-start gap-3 shadow-xs">
                            <span class="material-icons text-cyan-600 text-lg flex-shrink-0 mt-0.5">verified_user</span>
                            <div class="text-xs text-gray-700 leading-relaxed">
                                <strong>Exigência Oficial:</strong> O <strong>Código de Tributação Nacional (`cTribNac`)</strong> é obrigatório no envio da DPS. Já os campos de <strong>IBS/CBS</strong> possuem valores padrão pré-configurados pela Reforma Tributária e podem ser validados com a sua contabilidade.
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <div>
                                <label for="codigo_tributacao_nacional" class="block text-xs font-bold text-gray-800 mb-1 flex items-center justify-between">
                                    <span>Cód. Trib. Nacional (cTribNac)</span>
                                    <span class="text-[10px] text-cyan-700 font-normal">Obrigatório</span>
                                </label>
                                <input type="text" id="codigo_tributacao_nacional" name="codigo_tributacao_nacional" maxlength="6"
                                    value="<?= htmlspecialchars($servico['codigo_tributacao_nacional'] ?? '') ?>"
                                    placeholder="Ex: 010701"
                                    class="w-full p-2.5 border-2 border-cyan-400 bg-white rounded-xl focus:outline-none focus:ring-2 focus:ring-cyan-500 transition font-mono font-bold text-cyan-950 text-sm">
                                <p class="text-[10px] text-gray-500 mt-1">Exatamente <strong>6 dígitos</strong> (ex: <strong>010701</strong>).</p>
                            </div>

                            <div>
                                <label for="indicador_operacao" class="block text-xs font-bold text-gray-800 mb-1">
                                    Indicador Operação (cIndOp)
                                </label>
                                <input type="text" id="indicador_operacao" name="indicador_operacao" maxlength="6"
                                    value="<?= htmlspecialchars($servico['indicador_operacao'] ?? '050101') ?>"
                                    placeholder="050101"
                                    class="w-full p-2.5 border border-gray-300 rounded-xl text-sm font-mono focus:ring-2 focus:ring-cyan-500 focus:outline-none bg-white">
                                <p class="text-[10px] text-gray-500 mt-1">Padrão: <strong>050101</strong> (Serviço regular).</p>
                            </div>

                            <div>
                                <label for="cst_ibs_cbs" class="block text-xs font-bold text-gray-800 mb-1">
                                    CST IBS/CBS (3 dígitos)
                                </label>
                                <input type="text" id="cst_ibs_cbs" name="cst_ibs_cbs" maxlength="3"
                                    value="<?= htmlspecialchars($servico['cst_ibs_cbs'] ?? '000') ?>"
                                    placeholder="000"
                                    class="w-full p-2.5 border border-gray-300 rounded-xl text-sm font-mono focus:ring-2 focus:ring-cyan-500 focus:outline-none bg-white">
                                <p class="text-[10px] text-gray-500 mt-1">Padrão: <strong>000</strong> (Tributação Integral).</p>
                            </div>

                            <div>
                                <label for="classificacao_trib_ibs_cbs" class="block text-xs font-bold text-gray-800 mb-1">
                                    Classificação Trib. (6 dígitos)
                                </label>
                                <input type="text" id="classificacao_trib_ibs_cbs" name="classificacao_trib_ibs_cbs" maxlength="6"
                                    value="<?= htmlspecialchars($servico['classificacao_trib_ibs_cbs'] ?? '000000') ?>"
                                    placeholder="000000"
                                    class="w-full p-2.5 border border-gray-300 rounded-xl text-sm font-mono focus:ring-2 focus:ring-cyan-500 focus:outline-none bg-white">
                                <p class="text-[10px] text-gray-500 mt-1">Padrão: <strong>000000</strong> (Geral).</p>
                            </div>
                        </div>
                    </div>

                    <!-- AÇÕES -->
                    <div class="pt-4 flex items-center justify-between">
                        <a href="servicos.php"
                            class="px-6 py-3 bg-white text-gray-700 font-semibold rounded-xl border border-gray-200 hover:bg-gray-50 transition shadow-xs">
                            Cancelar
                        </a>
                        <button type="submit"
                            class="px-8 py-3 bg-cyan-600 text-white font-bold rounded-xl hover:bg-cyan-700 shadow-md transition transform hover:scale-[1.02] flex items-center gap-2">
                            <span class="material-icons text-lg">save</span>
                            <?= $is_edit ? 'Salvar Alterações do Serviço' : 'Cadastrar Serviço' ?>
                        </button>
                    </div>
                </form>
                <div id="formMessage" class="mt-4 text-center font-medium hidden p-3 rounded-xl"></div>
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
                const tribNac = opt.data('trib-nac');
                const tribIssqn = opt.data('trib-issqn');
                const aliquota = opt.data('aliquota');
                const cnae = opt.data('cnae');
                const codTrib = opt.val();

                if (item) $('#item_lista_servico').val(item);
                if (tribNac) $('#codigo_tributacao_nacional').val(tribNac);
                if (tribIssqn) $('#tributacao_issqn').val(tribIssqn);
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