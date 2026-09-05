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

$id_recorrencia = $_GET['id'] ?? null;
// Pre-fill client ID if coming from client details
$pre_cliente_id = $_GET['cliente_id'] ?? null;

$contrato = null;
$is_edit = false;

$cnaesCatalog = FiscalCatalogHelper::getCnaes();
$atividadesCatalog = FiscalCatalogHelper::getAtividades();

$link = DBConnect();

// Fetch Clients for Dropdown
$clientes = [];
$query_clientes = "SELECT id_cliente, nome FROM Clientes ORDER BY nome ASC";
$result_clientes = DBExecute($link, $query_clientes);
while ($row = mysqli_fetch_assoc($result_clientes))
    $clientes[] = $row;

if ($id_recorrencia) {
    $id_safe = mysqli_real_escape_string($link, $id_recorrencia);
    $query = "SELECT * FROM Recorrencias WHERE id_recorrencia = '$id_safe'";
    $result = DBExecute($link, $query);
    if ($result && mysqli_num_rows($result) > 0) {
        $contrato = mysqli_fetch_assoc($result);
        $is_edit = true;
    }
}

// Fetch Services for Dropdown (apenas serviços ativos ou o serviço vinculado ao contrato em edição)
$servicos = [];
$current_servico_id = $contrato['id_servico'] ?? 0;
$filtro_servicos = "(ativo = 1 OR ativo IS NULL" . ($current_servico_id ? " OR id_servico = '$current_servico_id'" : "") . ")";
$query_servicos = "SELECT id_servico, nome_servico, valor_sugerido, COALESCE(ativo, 1) as ativo FROM Servicos WHERE $filtro_servicos ORDER BY nome_servico ASC";
$result_servicos = DBExecute($link, $query_servicos);
if ($result_servicos) {
    while ($row = mysqli_fetch_assoc($result_servicos))
        $servicos[] = $row;
}

DBClose($link);
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <title>
        <?= $is_edit ? 'Editar Contrato' : 'Novo Contrato' ?> - Dinovatech
    </title>
    <?php include 'components/layout_head.php'; ?>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .select2-container .select2-selection--single {
            height: 50px !important;
            border-color: #d1d5db !important;
            border-radius: 0.5rem !important;
            padding-top: 10px;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 48px !important;
        }

        .tabs-nav {
            display: flex;
            gap: 0.5rem;
            background-color: #f3f4f6;
            padding: 0.25rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
        }

        .tab-btn {
            flex: 1;
            padding: 0.625rem 1rem;
            font-size: 0.875rem;
            font-weight: 500;
            color: #6b7280;
            border-radius: 0.375rem;
            transition: all 0.2s ease-in-out;
            text-align: center;
            border: none;
            background: transparent;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .tab-btn:hover {
            color: #374151;
            background-color: #e5e7eb;
        }

        .tab-btn.active {
            color: #0e7490;
            background-color: #ffffff;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            font-weight: 700;
        }
    </style>
    <!-- TinyMCE -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.2/tinymce.min.js" referrerpolicy="origin"></script>
</head>

<body class="bg-gray-50 flex">

    <?php include 'components/sidebar.php'; ?>

    <div class="flex-1 flex flex-col lg:ml-64 min-h-screen transition-all duration-300">
        <main class="flex-1 p-6 mt-16 lg:mt-0">

            <div class="max-w-4xl mx-auto">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center">
                        <a href="contratos.php" class="mr-4 text-gray-500 hover:text-gray-700">
                            <span class="material-icons">arrow_back</span>
                        </a>
                        <h2 class="text-3xl font-bold text-gray-800">
                            <?= $is_edit ? 'Editar Contrato' : 'Novo Contrato' ?>
                        </h2>
                    </div>
                </div>

                <!-- Tabs Navigation -->
                <div class="tabs-nav">
                    <button type="button" class="tab-btn active" onclick="openTab('dados')">
                        <span class="material-icons text-sm align-middle mr-1">edit_document</span> Dados do Contrato
                    </button>
                    <?php if ($is_edit): ?>
                        <button type="button" class="tab-btn" onclick="openTab('documentos')">
                            <span class="material-icons text-sm align-middle mr-1">description</span> Documentos / Contratos
                        </button>
                    <?php endif; ?>
                </div>

                <!-- TAB 1: DADOS DO CONTRATO -->
                <div id="tab-dados" class="tab-content">
                    <div class="bg-white p-8 rounded-xl shadow-sm border border-gray-100">
                        <form id="contratoForm">
                            <input type="hidden" name="action"
                                value="<?= $is_edit ? 'editar_recorrencia' : 'vincular_recorrencia' ?>">
                            <?php if ($is_edit): ?>
                                <input type="hidden" name="id_recorrencia" value="<?= $id_recorrencia ?>">
                            <?php endif; ?>

                            <div class="space-y-6">

                                <div>
                                    <label for="id_cliente"
                                        class="block text-sm font-medium text-gray-700 mb-1">Cliente</label>
                                    <select id="id_cliente" name="id_cliente" required
                                        class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500 transition bg-white select2-enable">
                                        <option value="">Selecione um cliente...</option>
                                        <?php foreach ($clientes as $c): ?>
                                            <option value="<?= $c['id_cliente'] ?>" <?= ($contrato['id_cliente'] ?? $pre_cliente_id) == $c['id_cliente'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($c['nome']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div>
                                    <label for="id_servico"
                                        class="block text-sm font-medium text-gray-700 mb-1">Serviço</label>
                                    <select id="id_servico" name="id_servico" required
                                        class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500 transition bg-white"
                                        onchange="updateValorSugerido()">
                                        <option value="">Selecione um serviço...</option>
                                        <?php foreach ($servicos as $s): ?>
                                            <option value="<?= $s['id_servico'] ?>" data-valor="<?= $s['valor_sugerido'] ?>"
                                                <?= ($contrato['id_servico'] ?? '') == $s['id_servico'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($s['nome_servico']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label for="valor_sugerido_recorrencia"
                                            class="block text-sm font-medium text-gray-700 mb-1">Valor Recorrente
                                            (R$)</label>
                                        <input type="number" id="valor_sugerido_recorrencia"
                                            name="valor_sugerido_recorrencia" step="0.01"
                                            value="<?= $contrato['valor_sugerido_recorrencia'] ?? '' ?>" required
                                            class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500 transition">
                                    </div>
                                    <div>
                                        <label for="quantidade"
                                            class="block text-sm font-medium text-gray-700 mb-1">Quantidade</label>
                                        <input type="number" id="quantidade" name="quantidade"
                                            value="<?= $contrato['quantidade'] ?? '1' ?>" required
                                            class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500 transition">
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label for="tipo_periodo"
                                            class="block text-sm font-medium text-gray-700 mb-1">Periodicidade</label>
                                        <select id="tipo_periodo" name="tipo_periodo" required
                                            class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500 transition bg-white">
                                            <option value="mensal" <?= ($contrato['tipo_periodo'] ?? '') == 'mensal' ? 'selected' : '' ?>>Mensal</option>
                                            <option value="anual" <?= ($contrato['tipo_periodo'] ?? '') == 'anual' ? 'selected' : '' ?>>Anual</option>
                                            <option value="semanal" <?= ($contrato['tipo_periodo'] ?? '') == 'semanal' ? 'selected' : '' ?>>Semanal</option>
                                            <option value="diario" <?= ($contrato['tipo_periodo'] ?? '') == 'diario' ? 'selected' : '' ?>>Diário</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label for="intervalo"
                                            class="block text-sm font-medium text-gray-700 mb-1">Intervalo (a
                                            cada)</label>
                                        <input type="number" id="intervalo" name="intervalo"
                                            value="<?= $contrato['intervalo'] ?? '1' ?>" required
                                            class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500 transition">
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    <div>
                                        <label for="data_inicio_cobranca"
                                            class="block text-sm font-medium text-gray-700 mb-1">Data Início</label>
                                        <input type="date" id="data_inicio_cobranca" name="data_inicio_cobranca"
                                            value="<?= $contrato['data_inicio_cobranca'] ?? date('Y-m-d') ?>" required
                                            class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500 transition">
                                    </div>
                                    <div>
                                        <label for="data_fim_cobranca"
                                            class="block text-sm font-medium text-gray-700 mb-1">Data Fim
                                            (Opcional)</label>
                                        <input type="date" id="data_fim_cobranca" name="data_fim_cobranca"
                                            value="<?= $contrato['data_fim_cobranca'] ?? '' ?>"
                                            class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500 transition">
                                    </div>
                                    <div>
                                        <label for="dia_vencimento"
                                            class="block text-sm font-medium text-gray-700 mb-1">Dia do Vencimento</label>
                                        <input type="number" id="dia_vencimento" name="dia_vencimento" min="1" max="31"
                                            placeholder="Ex: 10 (Padrão: dia de início)"
                                            value="<?= $contrato['dia_vencimento'] ?? '' ?>"
                                            class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500 transition"
                                            title="Dia do mês em que a fatura vencerá (1 a 31)">
                                    </div>
                                </div>

                                <!-- DADOS FISCAIS PERSONALIZADOS -->
                                <div class="border-t pt-6 mt-4">
                                    <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                                        <h3 class="text-lg font-bold text-gray-800 flex items-center">
                                            <span class="material-icons mr-2 text-amber-500">tune</span> 
                                            Personalização Fiscal (Sobreposição / Override)
                                        </h3>
                                        <span class="inline-flex items-center gap-1 text-xs font-semibold px-2.5 py-1 rounded-full bg-amber-50 text-amber-800 border border-amber-200">
                                            <span class="material-icons text-[14px]">layers</span> Precedência: Serviço &lt; Contrato
                                        </span>
                                    </div>

                                    <!-- Banner de Alerta de Sobreposição -->
                                    <div class="bg-amber-50/90 border border-amber-200 rounded-xl p-3.5 mb-5 text-xs text-amber-900 flex items-start gap-2.5 shadow-sm">
                                        <span class="material-icons text-amber-600 text-base mt-0.5">info</span>
                                        <div>
                                            <span class="font-bold">Atenção sobre a Precedência:</span> Os campos preenchidos nesta seção <strong>sobrepõem diretamente o cadastro do Serviço</strong> para todas as faturas geradas por este contrato. 
                                            <span class="block text-amber-800/90 mt-0.5">
                                                Campos deixados <em>em branco</em> continuarão herdando automaticamente as informações cadastradas no Serviço original.
                                            </span>
                                        </div>
                                    </div>

                                    <div class="mb-4">
                                        <div class="flex items-center justify-between mb-1">
                                            <label for="descricao_fiscal" class="block text-sm font-semibold text-gray-700">
                                                Descrição Fiscal da NFS-e
                                            </label>
                                            <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-amber-100 text-amber-800 border border-amber-300">
                                                ⚡ Sobrepõe o Serviço
                                            </span>
                                        </div>
                                        <textarea id="descricao_fiscal" name="descricao_fiscal" rows="2"
                                            class="w-full p-3 border border-amber-200 bg-amber-50/20 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500 transition text-sm"
                                            placeholder="Ex: Mensalidade de Gestão e Suporte TI - Contrato Especial (Deixe em branco para usar o texto padrão do serviço)"><?= $contrato['descricao_fiscal'] ?? '' ?></textarea>
                                        <p class="text-[11px] text-gray-500 mt-1">Substitui a discriminação do serviço para este contrato. Suporta a variável <code class="bg-gray-100 px-1 py-0.5 rounded text-cyan-700 font-mono">{MES}</code>.</p>
                                    </div>

                                    <div class="bg-gradient-to-r from-amber-50/40 via-slate-50 to-cyan-50/40 border border-amber-200/80 rounded-xl p-4 mb-4">
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <label for="select_atividade_contrato" class="block text-xs font-bold text-gray-800 mb-1 flex items-center justify-between">
                                                    <span class="flex items-center gap-1">
                                                        <span class="material-icons text-xs text-cyan-600">category</span> Atividade Municipal / Item LC 116 (ISS DF)
                                                    </span>
                                                    <span class="text-[10px] font-bold px-1.5 py-0.2 rounded bg-amber-100 text-amber-800">Sobreposição</span>
                                                </label>
                                                <select id="select_atividade_contrato" class="w-full p-2.5 border border-cyan-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500 bg-white text-xs font-medium text-gray-800 shadow-sm">
                                                    <option value="">-- Padrão do Cadastro do Serviço --</option>
                                                    <?php foreach ($atividadesCatalog as $ativ): 
                                                        $isMatch = (($contrato['codigo_tributacao_municipio'] ?? '') == $ativ['codigo_tributacao'] || ($contrato['item_lista_servico'] ?? '') == $ativ['item_lc116']);
                                                    ?>
                                                        <option value="<?= $ativ['codigo_tributacao'] ?>" 
                                                            data-item="<?= $ativ['item_lc116'] ?>" 
                                                            data-aliquota="<?= number_format($ativ['aliquota'], 2, '.', '') ?>" 
                                                            data-cnae="<?= $ativ['cnae_sugerido'] ?>"
                                                            <?= $isMatch ? 'selected' : '' ?>>
                                                            [Cód. <?= $ativ['codigo_tributacao'] ?> | LC <?= $ativ['item_lc116'] ?>] <?= htmlspecialchars($ativ['nome_curto']) ?> (Alíq. <?= number_format($ativ['aliquota'], 2, ',', '.') ?>%)
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <p class="text-[10px] text-gray-500 mt-1">Preenche automaticamente Item LC 116, Código de Tributação, Alíquota e CNAE para este contrato.</p>
                                            </div>

                                            <div>
                                                <label for="select_cnae_contrato" class="block text-xs font-bold text-gray-800 mb-1 flex items-center justify-between">
                                                    <span class="flex items-center gap-1">
                                                        <span class="material-icons text-xs text-cyan-600">business</span> CNAE Autorizado (Ficha Cadastral)
                                                    </span>
                                                    <span class="text-[10px] font-bold px-1.5 py-0.2 rounded bg-amber-100 text-amber-800">Sobreposição</span>
                                                </label>
                                                <select id="select_cnae_contrato" class="w-full p-2.5 border border-cyan-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500 bg-white text-xs font-medium text-gray-800 shadow-sm">
                                                    <option value="">-- Padrão do Cadastro do Serviço --</option>
                                                    <?php foreach ($cnaesCatalog as $cnae): 
                                                        $isCnaeMatch = (($contrato['codigo_cnae'] ?? '') == $cnae['codigo']);
                                                    ?>
                                                        <option value="<?= $cnae['codigo'] ?>" <?= $isCnaeMatch ? 'selected' : '' ?>>
                                                            <?= $cnae['codigo'] ?> - <?= htmlspecialchars($cnae['descricao']) ?> (<?= $cnae['tipo'] ?>)
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <p class="text-[10px] text-gray-500 mt-1">CNAE específico para as faturas deste contrato.</p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                        <div>
                                            <div class="flex items-center justify-between mb-1">
                                                <label for="item_lista_servico" class="block text-xs font-bold text-gray-700">
                                                    Item LC 116/03
                                                </label>
                                                <span class="text-[9px] font-semibold text-amber-700 bg-amber-50 px-1 rounded border border-amber-200">Override</span>
                                            </div>
                                            <input type="text" id="item_lista_servico" name="item_lista_servico"
                                                value="<?= $contrato['item_lista_servico'] ?? '' ?>"
                                                placeholder="Padrão Serviço (Ex: 01.05)"
                                                class="w-full p-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500 transition">
                                            <p class="text-[10px] text-gray-500 mt-1">Item da LC 116 para este contrato.</p>
                                        </div>

                                        <div>
                                            <div class="flex items-center justify-between mb-1">
                                                <label for="codigo_cnae" class="block text-xs font-bold text-gray-700">
                                                    Código CNAE
                                                </label>
                                                <span class="text-[9px] font-semibold text-amber-700 bg-amber-50 px-1 rounded border border-amber-200">Override</span>
                                            </div>
                                            <input type="text" id="codigo_cnae" name="codigo_cnae"
                                                value="<?= $contrato['codigo_cnae'] ?? '' ?>"
                                                placeholder="Padrão Serviço (Ex: 6202300)"
                                                class="w-full p-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500 transition">
                                            <p class="text-[10px] text-gray-500 mt-1">CNAE de 7 dígitos da atividade.</p>
                                        </div>

                                        <div>
                                            <div class="flex items-center justify-between mb-1">
                                                <label for="codigo_tributacao_municipio" class="block text-xs font-bold text-gray-700">
                                                    Cód. Tributação Municipal
                                                </label>
                                                <span class="text-[9px] font-semibold text-amber-700 bg-amber-50 px-1 rounded border border-amber-200">Override</span>
                                            </div>
                                            <input type="text" id="codigo_tributacao_municipio"
                                                name="codigo_tributacao_municipio"
                                                value="<?= $contrato['codigo_tributacao_municipio'] ?? '' ?>"
                                                placeholder="Padrão Serviço (Ex: 105)"
                                                class="w-full p-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500 transition">
                                            <p class="text-[10px] text-gray-500 mt-1">Código SEFIN DF (ex: 105).</p>
                                        </div>

                                        <div>
                                            <div class="flex items-center justify-between mb-1">
                                                <label for="codigo_nbs" class="block text-xs font-bold text-gray-700">
                                                    Código NBS
                                                </label>
                                                <span class="text-[9px] font-semibold text-amber-700 bg-amber-50 px-1 rounded border border-amber-200">Override</span>
                                            </div>
                                            <input type="text" id="codigo_nbs" name="codigo_nbs"
                                                value="<?= $contrato['codigo_nbs'] ?? '' ?>"
                                                placeholder="Padrão Serviço (Ex: 115080000)"
                                                class="w-full p-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500 transition">
                                            <p class="text-[10px] text-gray-500 mt-1">Nomenclatura Bras. Serviços.</p>
                                        </div>

                                        <div>
                                            <div class="flex items-center justify-between mb-1">
                                                <label for="aliquota_iss" class="block text-xs font-bold text-gray-700">
                                                    Alíquota ISS (%)
                                                </label>
                                                <span class="text-[9px] font-semibold text-amber-700 bg-amber-50 px-1 rounded border border-amber-200">Override</span>
                                            </div>
                                            <input type="number" id="aliquota_iss" name="aliquota_iss" step="0.01"
                                                min="0" max="100" value="<?= $contrato['aliquota_iss'] ?? '' ?>"
                                                placeholder="Padrão Serviço (Ex: 2.00)"
                                                class="w-full p-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500 transition font-semibold text-gray-800 bg-gray-50">
                                            <p class="text-[10px] text-gray-500 mt-1">Alíquota devida (ex: 2,00%).</p>
                                        </div>

                                        <div>
                                            <div class="flex items-center justify-between mb-1">
                                                <label for="iss_retido" class="block text-xs font-bold text-gray-700">
                                                    ISS Retido na Fonte?
                                                </label>
                                                <span class="text-[9px] font-semibold text-amber-700 bg-amber-50 px-1 rounded border border-amber-200">Override</span>
                                            </div>
                                            <select name="iss_retido" id="iss_retido"
                                                class="w-full p-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500 transition bg-white">
                                                <option value="">Padrão do Serviço</option>
                                                <option value="1" <?= ($contrato['iss_retido'] ?? '') === '1' ? 'selected' : '' ?>>Sim, Retido na Fonte</option>
                                                <option value="0" <?= ($contrato['iss_retido'] ?? '') === '0' ? 'selected' : '' ?>>Não, Normal</option>
                                            </select>
                                            <p class="text-[10px] text-gray-500 mt-1">Se o cliente deve reter o ISS.</p>
                                    </div>
                                </div>
                            </div>

                            <!-- ANOTAÇÕES TÉCNICAS E OPERACIONAIS DO CONTRATO -->
                                <div class="border-t pt-6 mt-4">
                                    <div class="flex items-center justify-between mb-2">
                                        <h3 class="text-lg font-bold text-gray-800 flex items-center">
                                            <span class="material-icons mr-2 text-cyan-600">engineering</span> 
                                            Anotações Técnicas & Operacionais do Contrato
                                        </h3>
                                        <span class="text-xs bg-cyan-50 text-cyan-700 font-semibold px-2.5 py-1 rounded-full border border-cyan-200 flex items-center gap-1">
                                            <span class="material-icons text-[14px]">edit_note</span> Editor WYSIWYG
                                        </span>
                                    </div>
                                    <p class="text-xs text-gray-500 mb-3">
                                        Registre especificações técnicas, mapas de pinagem, credenciais locais, topologia, IPs ou instruções de atendimento. Estas informações ficam disponíveis na ficha do cliente e podem ser injetadas automaticamente ao abrir agendamentos na Agenda.
                                    </p>
                                    <div class="rounded-lg overflow-hidden border border-gray-300 shadow-sm">
                                        <textarea id="descricao_personalizada" name="descricao_personalizada" rows="6"
                                            class="w-full p-3"><?= $contrato['descricao_personalizada'] ?? '' ?></textarea>
                                    </div>
                                </div>

                                <div class="pt-6 flex justify-end">
                                    <a href="contratos.php"
                                        class="px-6 py-3 mr-3 bg-gray-100 text-gray-700 font-medium rounded-lg hover:bg-gray-200 transition">Cancelar</a>
                                    <button type="submit"
                                        class="px-6 py-3 bg-cyan-600 text-white font-medium rounded-lg hover:bg-cyan-700 shadow-md transition transform hover:scale-105">
                                        <?= $is_edit ? 'Salvar Contrato' : 'Criar Contrato' ?>
                                    </button>
                                </div>
                            </div>
                        </form>
                        <div id="formMessage" class="mt-4 text-center font-medium hidden"></div>
                    </div>
                </div>

                <!-- TAB 2: DOCUMENTOS (MODELOS) -->
                <div id="tab-documentos" class="tab-content hidden">
                    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                        <h3 class="text-xl font-bold text-gray-800 mb-4">Emitir Documento (Baseado em Modelo)</h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-end">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Selecione o Modelo</label>
                                <select id="select-modelo-doc"
                                    class="w-full border-gray-300 rounded p-2 border bg-white">
                                    <option value="">Carregando modelos...</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Título do Documento
                                    (Opcional)</label>
                                <input type="text" id="titulo-doc-custom"
                                    class="w-full border-gray-300 rounded p-2 border"
                                    placeholder="Ex: Contrato Personalizado">
                                <p class="text-xs text-gray-400 mt-1">Se vazio, usa o título do modelo.</p>
                            </div>
                        </div>

                        <div class="flex gap-4 mt-4">
                            <button type="button" onclick="salvarDocumento()"
                                class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-6 rounded shadow flex items-center justify-center flex-1">
                                <span class="material-icons mr-2">save</span> Salvar no Histórico
                            </button>
                            <button type="button" onclick="gerarDocumentoModelo('print')"
                                class="bg-cyan-600 hover:bg-cyan-700 text-white font-bold py-2 px-6 rounded shadow flex items-center justify-center flex-1">
                                <span class="material-icons mr-2">print</span> Visualizar / Imprimir
                            </button>
                        </div>

                        <!-- Custom Text Editor (Hidden by default) -->
                        <div id="custom-text-container" class="mt-6 hidden">
                            <label class="block text-sm font-bold text-gray-700 mb-2">Texto Personalizado do
                                Documento</label>
                            <textarea id="editor-texto-custom" name="texto_custom"></textarea>
                        </div>

                        <!-- Variables Preview -->
                        <div id="vars-preview-container" class="mt-6 hidden">
                            <h4 class="text-sm font-bold text-gray-700 mb-2 border-b pb-1">Revisão de Campos
                                (Substituição)
                            </h4>
                            <div id="vars-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                <!-- Inputs dynamic -->
                            </div>
                            <p class="text-xs text-gray-400 mt-2">Tecle ENTER para atualizar a visualização se
                                necessário.
                            </p>
                        </div>
                    </div>

                    <!-- HISTORY SECTION -->
                    <div class="mt-8 bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                        <h3 class="text-xl font-bold text-gray-800 mb-4">Histórico de Documentos Emitidos</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Título</th>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Tipo</th>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Emissor</th>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Data</th>
                                        <th
                                            class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Ações</th>
                                    </tr>
                                </thead>
                                <tbody id="lista-docs-emitidos" class="bg-white divide-y divide-gray-200">
                                    <tr>
                                        <td colspan="5" class="px-6 py-4 text-center text-gray-500">Carregando...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>

        </main>
    </div>

    <?php include 'components/layout_scripts.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        const ID_RECORRENCIA = '<?= $id_recorrencia ?>';
        const BASE_URL = 'app.php'; // Relative to root since we are in root

        $(document).ready(function () {
            $('.select2-enable').select2({
                width: '100%',
                placeholder: 'Selecione...'
            });
            $('#id_servico').on('select2:select', function (e) { updateValorSugerido(); });

            updateValorSugerido(); // Init

            // Init Tabs
            if (ID_RECORRENCIA) {
                carregarModelosDoc();
                carregarHistoricoDocs();
            }

            // Init TinyMCE para Documentos
            tinymce.init({
                selector: '#editor-texto-custom',
                height: 300,
                menubar: true, // Enable menubar
                plugins: [
                    'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
                    'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
                    'insertdatetime', 'media', 'table', 'help', 'wordcount'
                ],
                toolbar: 'undo redo | blocks fontfamily fontsize | ' +
                    'bold italic backcolor forecolor | lineheight | alignleft aligncenter ' +
                    'alignright alignjustify | bullist numlist outdent indent | ' +
                    'table hr | code removeformat | help',
                content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:14px }',
                font_size_formats: '8pt 10pt 12pt 14pt 16pt 18pt 24pt 36pt 48pt',
            });

            // Init TinyMCE para Anotações Técnicas do Contrato
            tinymce.init({
                selector: '#descricao_personalizada',
                height: 260,
                menubar: 'edit insert view format table tools',
                plugins: [
                    'advlist', 'autolink', 'lists', 'link', 'table', 'code', 'searchreplace', 'visualblocks', 'wordcount'
                ],
                toolbar: 'undo redo | fontfamily fontsize | bold italic underline forecolor backcolor | ' +
                    'bullist numlist | table | code removeformat',
                content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; font-size: 13px; line-height: 1.5; color: #1e293b; } table { border-collapse: collapse; width: 100%; } th, td { border: 1px solid #cbd5e1; padding: 6px 8px; } th { background-color: #f1f5f9; font-weight: 600; }',
                font_size_formats: '9pt 10pt 11pt 12pt 14pt 16pt',
                branding: false,
                statusbar: true
            });
        });

        // Tabs Logic
        function openTab(tabName) {
            // Oculta todas as abas
            $('#tab-dados, #tab-documentos').addClass('hidden');
            $('.tab-btn').removeClass('active');

            // Exibe a aba clicada
            $('#tab-' + tabName).removeClass('hidden');
            $('.tab-btn[onclick*="' + tabName + '"]').addClass('active');

            // Ao entrar na aba de documentos, carrega dados e histórico
            if (tabName === 'documentos' && ID_RECORRENCIA) {
                carregarModelosDoc();
                carregarHistoricoDocs();
            }
        }

        // --- DOCUMENTOS MODELOS ---
        function carregarModelosDoc() {
            $.post(BASE_URL, { action: 'get_modelos_documentos' }, function (res) {
                if (res.success) {
                    let html = '<option value="">Selecione um modelo...</option>';
                    res.data.forEach(m => {
                        html += `<option value="${m.id_modelo}">${m.titulo} (${m.tipo})</option>`;
                    });
                    $('#select-modelo-doc').html(html);
                }
            }, 'json');

            // Listen for change
            $('#select-modelo-doc').change(function () {
                let idModelo = $(this).val();
                $('#vars-preview-container').addClass('hidden');
                $('#custom-text-container').addClass('hidden');

                if (!idModelo) return;

                // Fetch vars (Adapt for Recorrencia)
                $.post(BASE_URL, { action: 'get_modelo_vars_preview', id_modelo: idModelo, id_recorrencia: ID_RECORRENCIA }, function (res) {
                    res = typeof res === 'string' ? JSON.parse(res) : res;

                    if (res.has_custom_text) {
                        $('#custom-text-container').removeClass('hidden');
                        tinymce.get('editor-texto-custom').setContent('');
                    }

                    if (res.vars && res.success) {
                        // Removed preview logic for brevity unless needed, 
                        // but keeping structure if we want to show preview of fields like CLIENT, SERVICE etc.
                        // For now, simple implementation
                    }
                }, 'json');
            });
        }

        function salvarDocumento() {
            let idModelo = $('#select-modelo-doc').val();
            if (!idModelo) { alert('Selecione um modelo.'); return; }
            if (!ID_RECORRENCIA) { alert('Salve o contrato antes.'); return; }

            let tituloCustom = $('#titulo-doc-custom').val();
            let customContent = '';
            if (!$('#custom-text-container').hasClass('hidden')) {
                customContent = tinymce.get('editor-texto-custom').getContent();
            }

            // Collect overrides
            let overrides = {};
            if (customContent) overrides['{{TEXTO_PERSONALIZADO}}'] = customContent;

            $.post(BASE_URL, {
                action: 'save_document_emitted',
                id_recorrencia: ID_RECORRENCIA, // Changed from id_atendimento
                id_modelo: idModelo,
                titulo_custom: tituloCustom,
                overrides: overrides
            }, function (res) {
                try { res = typeof res === 'string' ? JSON.parse(res) : res; } catch (e) { }

                if (res.success) {
                    carregarHistoricoDocs();
                    // Show success feedback
                    alert('Documento salvo no histórico!');
                } else {
                    alert('Erro ao salvar: ' + (res.message || 'Desconhecido'));
                }
            });
        }

        function gerarDocumentoModelo(mode) {
            let idModelo = $('#select-modelo-doc').val();
            if (!idModelo) { alert('Selecione um modelo.'); return; }
            if (!ID_RECORRENCIA) { alert('Salve o contrato antes.'); return; }

            // Create form
            let form = document.createElement('form');
            form.method = 'POST';
            form.action = 'modules/Vet/documento_print.php'; // Path to printer
            form.target = '_blank';

            let inputId = document.createElement('input');
            inputId.type = 'hidden';
            inputId.name = 'id_recorrencia';
            inputId.value = ID_RECORRENCIA;
            form.appendChild(inputId);

            let inputMod = document.createElement('input');
            inputMod.type = 'hidden';
            inputMod.name = 'id_modelo';
            inputMod.value = idModelo;
            form.appendChild(inputMod);

            let tituloCustom = $('#titulo-doc-custom').val();
            if (tituloCustom) {
                let inputT = document.createElement('input');
                inputT.type = 'hidden';
                inputT.name = 'titulo_custom';
                inputT.value = tituloCustom;
                form.appendChild(inputT);
            }

            if (!$('#custom-text-container').hasClass('hidden')) {
                let customContent = tinymce.get('editor-texto-custom').getContent();
                let inputCustom = document.createElement('input');
                inputCustom.type = 'hidden';
                inputCustom.name = 'overrides[{{TEXTO_PERSONALIZADO}}]';
                inputCustom.value = customContent;
                form.appendChild(inputCustom);
            }

            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
        }

        function carregarHistoricoDocs() {
            if (!ID_RECORRENCIA) return;
            $.post(BASE_URL, { action: 'get_documentos_emitidos', id_recorrencia: ID_RECORRENCIA }, function (res) {
                if (res.success) {
                    let html = '';
                    if (res.data.length === 0) {
                        html = '<tr><td colspan="5" class="px-6 py-4 text-center text-gray-500">Nenhum documento emitido.</td></tr>';
                    } else {
                        res.data.forEach(d => {
                            let dataF = new Date(d.data_emissao).toLocaleString('pt-BR');
                            html += `
                                 <tr>
                                     <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${d.titulo || d.tipo}</td>
                                     <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${d.tipo}</td>
                                     <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${d.nome_emissor || '-'}</td>
                                     <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${dataF}</td>
                                     <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                         <a href="#" class="text-indigo-600 hover:text-indigo-900 mr-3" 
                                            onclick="verDocumentoSalvo(${d.id_documento_emitido}); return false;"><span class="material-icons text-sm" style="vertical-align: middle;">visibility</span> Ver</a>
                                         <a href="#" class="text-red-600 hover:text-red-900" 
                                            onclick="excluirDocumento(${d.id_documento_emitido}); return false;"><span class="material-icons text-sm" style="vertical-align: middle;">delete</span> Excluir</a>
                                     </td>
                                 </tr>
                             `;
                        });
                    }
                    $('#lista-docs-emitidos').html(html);
                } else {
                    $('#lista-docs-emitidos').html('<tr><td colspan="5" class="px-6 py-4 text-center text-red-500">Erro ao carregar.</td></tr>');
                }
            }, 'json');
        }

        function verDocumentoSalvo(id) {
            window.open('modules/Vet/documento_view.php?id=' + id, '_blank'); // Adjust path
        }

        function excluirDocumento(id) {
            if (!confirm('Tem certeza que deseja excluir?')) return;
            $.post(BASE_URL, { action: 'excluir_documento_emitido', id_documento: id }, function (res) {
                if (res.success) carregarHistoricoDocs();
                else alert('Erro: ' + (res.message || 'Desconhecido'));
            }, 'json');
        }

        function updateValorSugerido() {
            const select = document.getElementById('id_servico');
            const valorInput = document.getElementById('valor_sugerido_recorrencia');
            const selectedOption = select.options[select.selectedIndex];
            const valor = selectedOption.getAttribute('data-valor');
            if (valor && !valorInput.value) { // Update only if empty
                valorInput.value = valor;
            }
        }

        // Auto-preenchimento ao selecionar Atividade Municipal no Contrato
        $('#select_atividade_contrato').on('change', function() {
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
                $('#select_cnae_contrato').val(cnae);
            }
        });

        // Sincronização ao selecionar CNAE no Contrato
        $('#select_cnae_contrato').on('change', function() {
            const cnaeVal = $(this).val();
            if (cnaeVal) {
                $('#codigo_cnae').val(cnaeVal);
            }
        });

        // Keep existing save Logic
        $('#contratoForm').on('submit', function (e) {
            e.preventDefault();

            // Sincroniza instâncias do TinyMCE para os textareas antes de serializar
            if (typeof tinymce !== 'undefined') {
                tinymce.triggerSave();
            }

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
                            window.location.href = 'contratos.php';
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
    </script>
</body>

</html>