<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

require_once '../database.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers/AppHelper.php';

$linkDB = DBConnect();

// Buscar lista de centros de custo para o filtro inicial
$resCC = DBExecute($linkDB, "SELECT * FROM CentrosCusto WHERE ativo = 1 ORDER BY nome ASC");
$centrosCusto = [];
if ($resCC) {
    while ($rCC = mysqli_fetch_assoc($resCC)) {
        $centrosCusto[] = $rCC;
    }
}

// Buscar lista de fornecedores para selects
$resForn = DBExecute($linkDB, "SELECT id_fornecedor, razao_social, nome_fantasia FROM Fornecedores WHERE ativo = 1 ORDER BY razao_social ASC");
$fornecedores = [];
if ($resForn) {
    while ($rF = mysqli_fetch_assoc($resForn)) {
        $fornecedores[] = $rF;
    }
}

DBClose($linkDB);
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <title>Despesas & Contas a Pagar - Dinovatech</title>
    <?php include 'components/layout_head.php'; ?>
    <style>
        .badge-pulse {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: .5; }
        }
    </style>
</head>

<body class="bg-gray-50 flex">

    <?php include 'components/sidebar.php'; ?>

    <div class="flex-1 flex flex-col lg:ml-64 min-h-screen transition-all duration-300">
        <main class="flex-1 p-6 mt-16 lg:mt-0">

            <!-- Cabeçalho & Ações de Topo -->
            <div class="mb-6 flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="p-2 bg-rose-100 text-rose-600 rounded-lg">
                            <span class="material-icons text-2xl">trending_down</span>
                        </span>
                        <div>
                            <h2 class="text-2xl lg:text-3xl font-bold text-gray-800">Contas a Pagar & Despesas</h2>
                            <p class="text-gray-500 text-sm">Controle de saídas operacionais, fornecedores e previsões financeiras.</p>
                        </div>
                    </div>
                </div>

                <!-- Botões de Ação Rápida e Manutenção -->
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" onclick="abrirModalDespesa()" 
                        class="bg-rose-600 hover:bg-rose-700 text-white font-medium px-4 py-2 rounded-lg flex items-center shadow-sm transition text-sm">
                        <span class="material-icons text-base mr-1.5">add_circle</span>
                        Nova Despesa
                    </button>

                    <button type="button" onclick="abrirModalFornecedores()" 
                        class="bg-white hover:bg-gray-100 text-gray-700 border border-gray-300 font-medium px-3.5 py-2 rounded-lg flex items-center shadow-sm transition text-sm"
                        title="Gerenciar Fornecedores e Contatos">
                        <span class="material-icons text-base mr-1.5 text-slate-600">apartment</span>
                        Fornecedores
                    </button>

                    <button type="button" onclick="abrirModalCentrosCusto()" 
                        class="bg-white hover:bg-gray-100 text-gray-700 border border-gray-300 font-medium px-3.5 py-2 rounded-lg flex items-center shadow-sm transition text-sm"
                        title="Gerenciar Centros de Custo e Categorias">
                        <span class="material-icons text-base mr-1.5 text-slate-600">label</span>
                        Centros de Custo
                    </button>

                    <button type="button" onclick="abrirModalRegrasRecorrentes()" 
                        class="bg-white hover:bg-gray-100 text-gray-700 border border-gray-300 font-medium px-3.5 py-2 rounded-lg flex items-center shadow-sm transition text-sm"
                        title="Ver e gerenciar contas com repetição mensal automática">
                        <span class="material-icons text-base mr-1.5 text-purple-600">event_repeat</span>
                        Recorrências
                    </button>

                    <button type="button" id="btnSyncRecorrencias" onclick="sincronizarRecorrencias()" 
                        class="bg-purple-600 hover:bg-purple-700 text-white font-medium px-3.5 py-2 rounded-lg flex items-center shadow-sm transition text-sm"
                        title="Sincronizar e gerar as contas recorrentes para o mês em tela">
                        <span class="material-icons text-base mr-1.5">sync</span>
                        Sincronizar Mês
                    </button>
                </div>
            </div>

            <!-- Filtros de Navegação -->
            <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-200 mb-6 flex flex-wrap items-end gap-3">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Mês / Competência</label>
                    <input type="month" id="filtroMes" value="<?= date('Y-m') ?>" onchange="carregarDespesas(true)"
                        class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm font-medium focus:ring-2 focus:ring-rose-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Centro de Custo</label>
                    <select id="filtroCentroCusto" onchange="carregarDespesas()"
                        class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-rose-500 focus:outline-none max-w-xs">
                        <option value="">Todos</option>
                        <?php foreach ($centrosCusto as $cc): ?>
                            <option value="<?= $cc['id_centro_custo'] ?>"><?= htmlspecialchars($cc['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Status</label>
                    <select id="filtroStatus" onchange="carregarDespesas()"
                        class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-rose-500 focus:outline-none">
                        <option value="">Todos</option>
                        <option value="Em Aberto">Em Aberto</option>
                        <option value="Atrasada">Em Atraso</option>
                        <option value="Liquidada">Liquidadas (Pagas)</option>
                        <option value="Cancelada">Canceladas</option>
                    </select>
                </div>

                <div class="flex-1 min-w-[200px]">
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Buscar</label>
                    <div class="relative">
                        <span class="material-icons absolute left-2.5 top-2 text-gray-400 text-sm">search</span>
                        <input type="text" id="filtroBusca" placeholder="Descrição, fornecedor ou documento..."
                            onkeyup="if(event.key === 'Enter') carregarDespesas()"
                            class="w-full border border-gray-300 rounded-lg pl-8 pr-3 py-1.5 text-sm focus:ring-2 focus:ring-rose-500 focus:outline-none">
                    </div>
                </div>

                <div>
                    <button type="button" onclick="carregarDespesas()" 
                        class="bg-slate-800 hover:bg-slate-900 text-white px-4 py-1.5 rounded-lg text-sm font-medium transition">
                        Filtrar
                    </button>
                </div>
            </div>

            <!-- Cards de Indicadores do Mês -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <!-- A Pagar -->
                <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-200 flex items-center">
                    <div class="p-3 rounded-xl bg-amber-50 text-amber-600 mr-4 border border-amber-100">
                        <span class="material-icons text-2xl">pending_actions</span>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">A Pagar (Mês)</p>
                        <h3 class="text-xl font-bold text-gray-800" id="cardTotalAberto">R$ 0,00</h3>
                        <span class="text-xs text-gray-400" id="cardQtdAberto">0 despesas</span>
                    </div>
                </div>

                <!-- Pago no Mês -->
                <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-200 flex items-center">
                    <div class="p-3 rounded-xl bg-emerald-50 text-emerald-600 mr-4 border border-emerald-100">
                        <span class="material-icons text-2xl">check_circle</span>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Pago (Mês)</p>
                        <h3 class="text-xl font-bold text-emerald-600" id="cardTotalPago">R$ 0,00</h3>
                        <span class="text-xs text-gray-400" id="cardQtdPago">0 pagas</span>
                    </div>
                </div>

                <!-- Em Atraso -->
                <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-200 flex items-center">
                    <div class="p-3 rounded-xl bg-rose-50 text-rose-600 mr-4 border border-rose-100">
                        <span class="material-icons text-2xl">warning</span>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Em Atraso (Geral)</p>
                        <h3 class="text-xl font-bold text-rose-600" id="cardTotalAtrasado">R$ 0,00</h3>
                        <span class="text-xs text-rose-500 font-medium" id="cardQtdAtrasado">0 vencidas</span>
                    </div>
                </div>

                <!-- Previsão Total -->
                <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-200 flex items-center">
                    <div class="p-3 rounded-xl bg-slate-100 text-slate-700 mr-4 border border-slate-200">
                        <span class="material-icons text-2xl">receipt_long</span>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Previsão Total (Mês)</p>
                        <h3 class="text-xl font-bold text-slate-800" id="cardTotalGeral">R$ 0,00</h3>
                        <span class="text-xs text-gray-400">Pago + A Pagar</span>
                    </div>
                </div>
            </div>

            <!-- Tabela de Listagem de Despesas -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="p-4 border-b border-gray-200 flex items-center justify-between bg-slate-50">
                    <div class="flex items-center gap-2">
                        <span class="material-icons text-gray-500">format_list_bulleted</span>
                        <h3 class="font-bold text-gray-800 text-base">Lançamentos do Mês</h3>
                        <span id="badgeTotalRegistros" class="bg-gray-200 text-gray-700 text-xs px-2 py-0.5 rounded-full font-semibold">0</span>
                    </div>
                    <div class="text-xs text-gray-500 italic">
                        * Contas recorrentes são geradas automaticamente na abertura do mês corrente/futuro.
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-gray-600">
                        <thead class="bg-gray-100 text-xs text-gray-500 uppercase font-semibold border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-3">Vencimento</th>
                                <th class="px-4 py-3">Fornecedor</th>
                                <th class="px-4 py-3">Descrição / Tipo</th>
                                <th class="px-4 py-3">Centro de Custo</th>
                                <th class="px-4 py-3 text-right">Valor</th>
                                <th class="px-4 py-3 text-center">Status</th>
                                <th class="px-4 py-3 text-center">Anexos</th>
                                <th class="px-4 py-3 text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody id="tabelaDespesasCorpo" class="divide-y divide-gray-100">
                            <tr>
                                <td colspan="8" class="text-center py-8 text-gray-400">
                                    <span class="material-icons animate-spin text-2xl mb-2">refresh</span>
                                    <p>Carregando despesas...</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL: NOVA / EDITAR DESPESA                                              -->
    <!-- ========================================================================= -->
    <div id="modalDespesa" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4 overflow-y-auto">
        <div class="bg-white rounded-2xl max-w-2xl w-full shadow-2xl overflow-hidden my-8">
            <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="material-icons text-rose-400">post_add</span>
                    <h3 class="text-lg font-bold" id="modalDespesaTitulo">Lançar Nova Despesa</h3>
                </div>
                <button type="button" onclick="fecharModalDespesa()" class="text-slate-400 hover:text-white">
                    <span class="material-icons">close</span>
                </button>
            </div>

            <form id="formDespesa" onsubmit="salvarDespesa(event)" class="p-6 space-y-4">
                <input type="hidden" id="despesaId" name="id_despesa" value="">

                <!-- Seletor de Tipo de Despesa -->
                <div id="containerTipoDespesa">
                    <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Tipo de Lançamento</label>
                    <div class="grid grid-cols-3 gap-3">
                        <label class="flex flex-col items-center justify-center p-3 border-2 rounded-xl cursor-pointer transition text-center hover:bg-gray-50 peer-checked:border-rose-500" id="labelTipoAvulsa">
                            <input type="radio" name="tipo" value="avulsa" checked onchange="alternarTipoDespesa('avulsa')" class="hidden">
                            <span class="material-icons text-rose-600 mb-1">receipt</span>
                            <span class="text-xs font-bold text-gray-800">Avulsa</span>
                            <span class="text-[10px] text-gray-500">Pagamento único</span>
                        </label>
                        <label class="flex flex-col items-center justify-center p-3 border-2 rounded-xl cursor-pointer transition text-center hover:bg-gray-50" id="labelTipoParcelada">
                            <input type="radio" name="tipo" value="parcelada" onchange="alternarTipoDespesa('parcelada')" class="hidden">
                            <span class="material-icons text-blue-600 mb-1">payments</span>
                            <span class="text-xs font-bold text-gray-800">Parcelada</span>
                            <span class="text-[10px] text-gray-500">Carnê / N parcelas</span>
                        </label>
                        <label class="flex flex-col items-center justify-center p-3 border-2 rounded-xl cursor-pointer transition text-center hover:bg-gray-50" id="labelTipoRecorrente">
                            <input type="radio" name="tipo" value="recorrente" onchange="alternarTipoDespesa('recorrente')" class="hidden">
                            <span class="material-icons text-purple-600 mb-1">event_repeat</span>
                            <span class="text-xs font-bold text-gray-800">Recorrente</span>
                            <span class="text-[10px] text-gray-500">Contínuo mensal</span>
                        </label>
                    </div>
                </div>

                <!-- Fornecedor & Centro de Custo -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider">Fornecedor *</label>
                            <button type="button" onclick="abrirModalFornecedores(true)" class="text-xs text-rose-600 hover:underline font-semibold flex items-center">
                                <span class="material-icons text-xs mr-0.5">add</span> Novo
                            </button>
                        </div>
                        <select id="despesaFornecedor" name="id_fornecedor" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-rose-500 focus:outline-none">
                            <option value="">Selecione um fornecedor...</option>
                            <?php foreach ($fornecedores as $f): ?>
                                <option value="<?= $f['id_fornecedor'] ?>">
                                    <?= htmlspecialchars($f['razao_social']) ?><?= !empty($f['nome_fantasia']) ? ' (' . htmlspecialchars($f['nome_fantasia']) . ')' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-1">Centro de Custo</label>
                        <select id="despesaCentroCusto" name="id_centro_custo"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-rose-500 focus:outline-none">
                            <option value="">Nenhum (Geral)</option>
                            <?php foreach ($centrosCusto as $cc): ?>
                                <option value="<?= $cc['id_centro_custo'] ?>"><?= htmlspecialchars($cc['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Descrição -->
                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-1">Descrição da Despesa *</label>
                    <input type="text" id="despesaDescricao" name="descricao" required placeholder="Ex: Energia Elétrica - Sede, Insumos Veterinários, Aluguel"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-rose-500 focus:outline-none">
                </div>

                <!-- Vencimento & Valor -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-1" id="lblDataVencimento">Data de Vencimento *</label>
                        <input type="date" id="despesaVencimento" name="data_vencimento" required value="<?= date('Y-m-d') ?>"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-rose-500 focus:outline-none">
                        <span class="text-[11px] text-gray-400" id="hintVencimento">Para recorrentes, o dia do mês será adotado.</span>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-1" id="lblValor">Valor (R$) *</label>
                        <input type="text" id="despesaValor" name="valor" required placeholder="0,00"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-bold text-gray-800 focus:ring-2 focus:ring-rose-500 focus:outline-none">
                    </div>
                </div>

                <!-- Bloco Específico: Parcelamento -->
                <div id="blocoParcelamento" class="p-3 bg-blue-50 border border-blue-200 rounded-xl space-y-3 hidden">
                    <div class="flex items-center gap-2 text-blue-800 text-xs font-bold">
                        <span class="material-icons text-sm">tune</span> Configuração de Parcelamento
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Total de Parcelas</label>
                            <input type="number" id="despesaTotalParcelas" name="total_parcelas" min="2" max="120" value="2"
                                class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm bg-white">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">O valor informado é:</label>
                            <select id="despesaTipoValorParcela" name="tipo_valor_parcela" class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm bg-white">
                                <option value="parcela">Valor de CADA parcela</option>
                                <option value="total">Valor TOTAL (dividir pelas parcelas)</option>
                            </select>
                        </div>
                    </div>
                    <p class="text-[11px] text-blue-600">
                        * As parcelas mensais serão geradas com vencimentos subsequentes automaticamente.
                    </p>
                </div>

                <!-- Documento / NF e Observações -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-1">Nº Documento / Linha Digitável</label>
                        <input type="text" id="despesaNumeroDoc" name="numero_documento" placeholder="Ex: NF 1024, Boleto 2379..."
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-rose-500 focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-1">Observações</label>
                        <textarea id="despesaObs" name="observacoes" rows="2" placeholder="Informações adicionais..."
                            class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-rose-500 focus:outline-none"></textarea>
                    </div>
                </div>

                <div class="pt-3 border-t border-gray-100 flex items-center justify-end gap-2">
                    <button type="button" onclick="fecharModalDespesa()" 
                        class="px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded-lg transition font-medium">Cancelar</button>
                    <button type="submit" id="btnSalvarDespesa"
                        class="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-lg text-sm font-bold shadow-md transition flex items-center">
                        <span class="material-icons text-base mr-1">save</span>
                        Salvar Despesa
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL: LIQUIDAR / BAIXAR DESPESA                                          -->
    <!-- ========================================================================= -->
    <div id="modalLiquidar" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full shadow-2xl overflow-hidden">
            <div class="px-6 py-4 bg-emerald-700 text-white flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="material-icons text-emerald-200">task_alt</span>
                    <h3 class="text-lg font-bold">Liquidar Despesa</h3>
                </div>
                <button type="button" onclick="fecharModalLiquidar()" class="text-emerald-200 hover:text-white">
                    <span class="material-icons">close</span>
                </button>
            </div>

            <form id="formLiquidar" onsubmit="confirmarLiquidacao(event)" class="p-6 space-y-4">
                <input type="hidden" id="liquidarIdDespesa" name="id_despesa" value="">

                <div class="p-3 bg-emerald-50 border border-emerald-100 rounded-xl">
                    <p class="text-xs text-emerald-800 font-semibold uppercase tracking-wider" id="liquidarDescricao">Despesa</p>
                    <p class="text-sm text-emerald-950 font-bold" id="liquidarFornecedor">Fornecedor</p>
                    <p class="text-xs text-emerald-700 mt-1" id="liquidarVencimento">Vencimento: --/--/----</p>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-1">Data do Pagamento *</label>
                    <input type="datetime-local" id="liquidarDataPagamento" name="data_pagamento" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-1">Valor Pago (R$) *</label>
                    <input type="text" id="liquidarValorPago" name="valor_pago" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-bold text-gray-800 focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-1">Forma de Pagamento *</label>
                    <select id="liquidarFormaPagamento" name="forma_pagamento" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                        <option value="Pix">Pix</option>
                        <option value="Boleto">Boleto Bancário</option>
                        <option value="Transferência">Transferência / TED</option>
                        <option value="Cartão de Crédito">Cartão de Crédito</option>
                        <option value="Cartão de Débito">Cartão de Débito</option>
                        <option value="Dinheiro">Dinheiro</option>
                        <option value="Outro">Outro</option>
                    </select>
                </div>

                <!-- Anexar comprovante de pagamento opcional -->
                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-1">Comprovante de Pagamento (Opcional)</label>
                    <input type="file" id="liquidarComprovante" name="comprovante" accept=".pdf,image/*,.xml"
                        class="w-full text-xs text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100">
                </div>

                <div class="pt-3 border-t border-gray-100 flex items-center justify-end gap-2">
                    <button type="button" onclick="fecharModalLiquidar()" 
                        class="px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded-lg transition font-medium">Cancelar</button>
                    <button type="submit" id="btnConfirmarLiquidar"
                        class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm font-bold shadow-md transition flex items-center">
                        <span class="material-icons text-base mr-1">check_circle</span>
                        Confirmar Baixa
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL: GESTÃO DE ANEXOS / DOCUMENTOS                                      -->
    <!-- ========================================================================= -->
    <div id="modalAnexos" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-lg w-full shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
            <div class="px-6 py-4 bg-slate-800 text-white flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="material-icons text-cyan-400">attach_file</span>
                    <h3 class="text-base font-bold" id="modalAnexosTitulo">Anexos da Despesa</h3>
                </div>
                <button type="button" onclick="fecharModalAnexos()" class="text-slate-400 hover:text-white">
                    <span class="material-icons">close</span>
                </button>
            </div>

            <div class="p-6 overflow-y-auto space-y-4 flex-1">
                <!-- Formulário de Upload de Novo Anexo -->
                <form id="formUploadAnexo" onsubmit="enviarNovoAnexo(event)" class="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-3">
                    <input type="hidden" id="anexoIdDespesa" name="id_despesa" value="">
                    
                    <div class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-1">
                        <span class="material-icons text-xs">upload</span> Anexar Novo Arquivo
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[11px] font-semibold text-gray-500 mb-1">Tipo de Documento</label>
                            <select id="anexoTipoDocumento" name="tipo_documento" class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-xs bg-white">
                                <option value="Boleto">Boleto Bancário</option>
                                <option value="Nota Fiscal">Nota Fiscal / DANFE</option>
                                <option value="Fatura">Fatura / Recibo</option>
                                <option value="Comprovante">Comprovante de Pagamento</option>
                                <option value="Outro">Outro Documento</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-gray-500 mb-1">Selecionar Arquivo</label>
                            <input type="file" id="anexoArquivo" name="arquivo" required accept=".pdf,image/*,.xml,.zip"
                                class="w-full text-xs text-gray-500 file:mr-2 file:py-1 file:px-2 file:rounded-md file:border-0 file:text-[11px] file:font-semibold file:bg-cyan-50 file:text-cyan-700 hover:file:bg-cyan-100">
                        </div>
                    </div>

                    <div class="text-right">
                        <button type="submit" id="btnEnviarAnexo"
                            class="bg-cyan-600 hover:bg-cyan-700 text-white px-3.5 py-1.5 rounded-lg text-xs font-bold shadow-sm transition flex items-center ml-auto">
                            <span class="material-icons text-sm mr-1">cloud_upload</span>
                            Fazer Upload
                        </button>
                    </div>
                </form>

                <!-- Lista de Anexos Existentes -->
                <div>
                    <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Arquivos Anexados</h4>
                    <div id="listaAnexosContainer" class="space-y-2">
                        <p class="text-center py-4 text-gray-400 text-xs italic">Nenhum anexo encontrado.</p>
                    </div>
                </div>
            </div>

            <div class="p-4 border-t border-gray-100 bg-gray-50 text-right">
                <button type="button" onclick="fecharModalAnexos()" class="px-4 py-1.5 text-xs font-semibold bg-gray-200 hover:bg-gray-300 rounded-lg text-gray-700 transition">Fechar</button>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL: GESTÃO DE FORNECEDORES                                             -->
    <!-- ========================================================================= -->
    <div id="modalFornecedores" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-3xl w-full shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
            <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="material-icons text-cyan-400">apartment</span>
                    <h3 class="text-lg font-bold">Gestão de Fornecedores</h3>
                </div>
                <button type="button" onclick="fecharModalFornecedores()" class="text-slate-400 hover:text-white">
                    <span class="material-icons">close</span>
                </button>
            </div>

            <div class="p-6 overflow-y-auto space-y-6 flex-1">
                <!-- Formulário de Cadastro / Edição -->
                <form id="formFornecedor" onsubmit="salvarFornecedor(event)" class="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-3">
                    <input type="hidden" id="fornecedorId" name="id_fornecedor" value="">
                    
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700 uppercase tracking-wider" id="formFornecedorTitulo">Novo Fornecedor</span>
                        <button type="button" onclick="limparFormFornecedor()" class="text-xs text-gray-500 hover:text-gray-800">Limpar</button>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-bold text-gray-600 mb-1">Razão Social / Nome *</label>
                            <input type="text" id="fornecedorRazao" name="razao_social" required placeholder="Ex: Distribuidora Pet S/A"
                                class="w-full border border-gray-300 rounded-lg px-2.5 py-1.5 text-xs focus:ring-2 focus:ring-cyan-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-gray-600 mb-1">Nome Fantasia</label>
                            <input type="text" id="fornecedorFantasia" name="nome_fantasia" placeholder="Ex: Pet Distribuição"
                                class="w-full border border-gray-300 rounded-lg px-2.5 py-1.5 text-xs focus:ring-2 focus:ring-cyan-500 focus:outline-none">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-[11px] font-bold text-gray-600 mb-1">CPF / CNPJ</label>
                            <input type="text" id="fornecedorCpfCnpj" name="cpf_cnpj" placeholder="00.000.000/0000-00"
                                class="w-full border border-gray-300 rounded-lg px-2.5 py-1.5 text-xs focus:ring-2 focus:ring-cyan-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-gray-600 mb-1">Telefone / WhatsApp</label>
                            <input type="text" id="fornecedorTelefone" name="telefone" placeholder="(00) 00000-0000"
                                class="w-full border border-gray-300 rounded-lg px-2.5 py-1.5 text-xs focus:ring-2 focus:ring-cyan-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-gray-600 mb-1">E-mail</label>
                            <input type="email" id="fornecedorEmail" name="email" placeholder="financeiro@empresa.com"
                                class="w-full border border-gray-300 rounded-lg px-2.5 py-1.5 text-xs focus:ring-2 focus:ring-cyan-500 focus:outline-none">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-bold text-gray-600 mb-1">Contato / Representante</label>
                            <input type="text" id="fornecedorContato" name="contato_responsavel" placeholder="Ex: Marcos (Vendas)"
                                class="w-full border border-gray-300 rounded-lg px-2.5 py-1.5 text-xs focus:ring-2 focus:ring-cyan-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-gray-600 mb-1">Observações Adicionais</label>
                            <input type="text" id="fornecedorObs" name="observacoes" placeholder="Ex: Chave PIX, conta bancária ou prazos"
                                class="w-full border border-gray-300 rounded-lg px-2.5 py-1.5 text-xs focus:ring-2 focus:ring-cyan-500 focus:outline-none">
                        </div>
                    </div>

                    <div class="text-right">
                        <button type="submit" id="btnSalvarFornecedor"
                            class="bg-cyan-600 hover:bg-cyan-700 text-white px-4 py-1.5 rounded-lg text-xs font-bold shadow-sm transition">
                            Salvar Fornecedor
                        </button>
                    </div>
                </form>

                <!-- Tabela de Fornecedores Cadastrados -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider">Fornecedores Cadastrados</h4>
                        <input type="text" id="buscaFornecedorTabela" placeholder="Filtrar por nome ou documento..."
                            onkeyup="carregarTabelaFornecedores()"
                            class="border border-gray-300 rounded-lg px-2.5 py-1 text-xs w-64">
                    </div>
                    <div class="border border-gray-200 rounded-xl overflow-hidden">
                        <table class="w-full text-left text-xs text-gray-600">
                            <thead class="bg-gray-100 text-gray-500 uppercase font-semibold">
                                <tr>
                                    <th class="px-3 py-2">Razão / Fantasia</th>
                                    <th class="px-3 py-2">CPF / CNPJ</th>
                                    <th class="px-3 py-2">Contato</th>
                                    <th class="px-3 py-2 text-center">Ações</th>
                                </tr>
                            </thead>
                            <tbody id="tabelaFornecedoresCorpo" class="divide-y divide-gray-100">
                                <tr><td colspan="4" class="text-center py-4 text-gray-400">Carregando...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="p-4 border-t border-gray-100 bg-gray-50 text-right">
                <button type="button" onclick="fecharModalFornecedores()" class="px-4 py-1.5 text-xs font-semibold bg-gray-200 hover:bg-gray-300 rounded-lg text-gray-700 transition">Concluído</button>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL: GESTÃO DE CENTROS DE CUSTO                                         -->
    <!-- ========================================================================= -->
    <div id="modalCentrosCusto" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-lg w-full shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
            <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="material-icons text-cyan-400">label</span>
                    <h3 class="text-base font-bold">Centros de Custo</h3>
                </div>
                <button type="button" onclick="fecharModalCentrosCusto()" class="text-slate-400 hover:text-white">
                    <span class="material-icons">close</span>
                </button>
            </div>

            <div class="p-6 overflow-y-auto space-y-4 flex-1">
                <!-- Form Cadastro Centro de Custo -->
                <form id="formCentroCusto" onsubmit="salvarCentroCusto(event)" class="p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-2">
                    <input type="hidden" id="centroCustoId" name="id_centro_custo" value="">
                    
                    <div class="text-xs font-bold text-slate-700 uppercase tracking-wider" id="formCentroCustoTitulo">Novo Centro de Custo</div>

                    <div class="grid grid-cols-3 gap-2">
                        <div class="col-span-2">
                            <label class="block text-[11px] font-bold text-gray-600 mb-1">Nome *</label>
                            <input type="text" id="centroCustoNome" name="nome" required placeholder="Ex: Operacional, Marketing..."
                                class="w-full border border-gray-300 rounded-lg px-2.5 py-1.5 text-xs">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-gray-600 mb-1">Cor</label>
                            <input type="color" id="centroCustoCor" name="cor" value="#0284c7"
                                class="w-full h-8 p-0 border border-gray-300 rounded-lg cursor-pointer">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-gray-600 mb-1">Descrição</label>
                        <input type="text" id="centroCustoDesc" name="descricao" placeholder="Finalidade deste centro..."
                            class="w-full border border-gray-300 rounded-lg px-2.5 py-1.5 text-xs">
                    </div>

                    <div class="text-right">
                        <button type="submit" class="bg-cyan-600 hover:bg-cyan-700 text-white px-3 py-1 rounded-lg text-xs font-bold transition">
                            Salvar
                        </button>
                    </div>
                </form>

                <!-- Listagem -->
                <div class="border border-gray-200 rounded-xl overflow-hidden">
                    <table class="w-full text-left text-xs text-gray-600">
                        <thead class="bg-gray-100 text-gray-500 uppercase font-semibold">
                            <tr>
                                <th class="px-3 py-2">Centro de Custo</th>
                                <th class="px-3 py-2">Descrição</th>
                                <th class="px-3 py-2 text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody id="tabelaCentrosCustoCorpo" class="divide-y divide-gray-100">
                            <tr><td colspan="3" class="text-center py-4 text-gray-400">Carregando...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="p-4 border-t border-gray-100 bg-gray-50 text-right">
                <button type="button" onclick="fecharModalCentrosCusto()" class="px-4 py-1.5 text-xs font-semibold bg-gray-200 hover:bg-gray-300 rounded-lg text-gray-700 transition">Fechar</button>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL: REGRAS RECORRENTES                                                 -->
    <!-- ========================================================================= -->
    <div id="modalRegrasRecorrentes" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-3xl w-full shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
            <div class="px-6 py-4 bg-purple-900 text-white flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="material-icons text-purple-300">event_repeat</span>
                    <h3 class="text-base font-bold">Despesas Recorrentes (Mensais)</h3>
                </div>
                <button type="button" onclick="fecharModalRegrasRecorrentes()" class="text-purple-300 hover:text-white">
                    <span class="material-icons">close</span>
                </button>
            </div>

            <div class="p-6 overflow-y-auto space-y-4 flex-1">
                <p class="text-xs text-gray-500">
                    Contas que se repetem indefinidamente a cada mês. Na abertura de um mês corrente ou futuro, o sistema gera a competência automaticamente.
                </p>

                <div class="border border-gray-200 rounded-xl overflow-hidden">
                    <table class="w-full text-left text-xs text-gray-600">
                        <thead class="bg-gray-100 text-gray-500 uppercase font-semibold">
                            <tr>
                                <th class="px-3 py-2">Descrição</th>
                                <th class="px-3 py-2">Fornecedor</th>
                                <th class="px-3 py-2">Dia Venc.</th>
                                <th class="px-3 py-2 text-right">Valor Base</th>
                                <th class="px-3 py-2 text-center">Status</th>
                                <th class="px-3 py-2 text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody id="tabelaRegrasRecorrentesCorpo" class="divide-y divide-gray-100">
                            <tr><td colspan="6" class="text-center py-4 text-gray-400">Carregando...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="p-4 border-t border-gray-100 bg-gray-50 text-right">
                <button type="button" onclick="fecharModalRegrasRecorrentes()" class="px-4 py-1.5 text-xs font-semibold bg-gray-200 hover:bg-gray-300 rounded-lg text-gray-700 transition">Fechar</button>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL: EDITAR TEMPLATE DE RECORRÊNCIA                                     -->
    <!-- ========================================================================= -->
    <div id="modalEditarTemplateRecorrente" class="fixed inset-0 bg-black bg-opacity-50 z-55 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-lg w-full shadow-2xl overflow-hidden animate__animated animate__fadeIn">
            <div class="px-6 py-4 bg-purple-900 text-white flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="material-icons text-purple-300">edit_note</span>
                    <h3 class="text-base font-bold">Editar Template da Recorrência</h3>
                </div>
                <button type="button" onclick="fecharModalEditarTemplateRecorrente()" class="text-purple-300 hover:text-white">
                    <span class="material-icons">close</span>
                </button>
            </div>

            <form id="formEditarTemplateRecorrente" onsubmit="salvarTemplateRecorrente(event)" class="p-6 space-y-4">
                <input type="hidden" id="editRecId" name="id_despesa" value="">

                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-1">Descrição da Recorrência *</label>
                    <input type="text" id="editRecDescricao" name="descricao" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-500 focus:outline-none">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-1">Fornecedor *</label>
                        <select id="editRecFornecedor" name="id_fornecedor" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-500 focus:outline-none">
                            <option value="">Selecione...</option>
                            <?php foreach ($fornecedores as $f): ?>
                                <option value="<?= $f['id_fornecedor'] ?>"><?= htmlspecialchars($f['razao_social']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-1">Centro de Custo</label>
                        <select id="editRecCentroCusto" name="id_centro_custo"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-500 focus:outline-none">
                            <option value="">Nenhum (Geral)</option>
                            <?php foreach ($centrosCusto as $cc): ?>
                                <option value="<?= $cc['id_centro_custo'] ?>"><?= htmlspecialchars($cc['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-1">Dia do Vencimento (1 a 31) *</label>
                        <input type="number" id="editRecDiaVenc" name="dia_vencimento" min="1" max="31" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-bold focus:ring-2 focus:ring-purple-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-1">Valor Base Padrão (R$) *</label>
                        <input type="text" id="editRecValor" name="valor" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-bold focus:ring-2 focus:ring-purple-500 focus:outline-none">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-1">Observações Padrão</label>
                    <textarea id="editRecObs" name="observacoes" rows="2"
                        class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-purple-500 focus:outline-none"></textarea>
                </div>

                <div class="flex items-center">
                    <label class="flex items-center text-xs font-bold text-gray-700 cursor-pointer select-none">
                        <input type="checkbox" id="editRecAtiva" name="recorrencia_ativa" class="rounded text-purple-600 focus:ring-purple-500 mr-2">
                        <span>Recorrência Ativa (Gerar automaticamente todo mês)</span>
                    </label>
                </div>

                <div class="pt-3 border-t border-gray-100 flex items-center justify-end gap-2">
                    <button type="button" onclick="fecharModalEditarTemplateRecorrente()" 
                        class="px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded-lg transition">Cancelar</button>
                    <button type="submit" id="btnSalvarTemplateRec"
                        class="px-5 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-sm font-bold shadow-md transition flex items-center">
                        <span class="material-icons text-base mr-1">save</span>
                        Salvar Template
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Scripts JavaScript do Módulo -->
    <?php include 'components/layout_scripts.php'; ?>
    <script>
        let despesasCache = [];
        let centrosCustoCache = <?= json_encode($centrosCusto) ?>;
        let fornecedoresCache = <?= json_encode($fornecedores) ?>;

        $(document).ready(function() {
            // Carrega despesas com auto_sync se for mês presente/futuro
            carregarDespesas(true);
        });

        // Formatação Moeda BRL
        function formatarBRL(val) {
            return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(val || 0);
        }

        // Formatação de Data BR
        function formatarDataBR(str) {
            if (!str) return '-';
            const p = str.split(' ')[0].split('-');
            if (p.length === 3) return `${p[2]}/${p[1]}/${p[0]}`;
            return str;
        }

        // =========================================================================
        // CARREGAMENTO DE DESPESAS E CARDS
        // =========================================================================
        function carregarDespesas(isOpeningMonth = false) {
            const mes = $('#filtroMes').val();
            const idCC = $('#filtroCentroCusto').val();
            const status = $('#filtroStatus').val();
            const busca = $('#filtroBusca').val();

            $('#tabelaDespesasCorpo').html(`
                <tr>
                    <td colspan="8" class="text-center py-8 text-gray-400">
                        <span class="material-icons animate-spin text-2xl mb-1">refresh</span>
                        <p>Atualizando despesas...</p>
                    </td>
                </tr>
            `);

            // Parâmetro auto_sync só vai se a mudança de mês for disparada
            const params = {
                action: 'listar_despesas',
                mes: mes,
                id_centro_custo: idCC,
                status: status,
                busca: busca,
                auto_sync: isOpeningMonth ? 1 : 0
            };

            $.post('app.php', params, function(res) {
                if (res.success) {
                    despesasCache = res.data;
                    renderizarTabelaDespesas(res.data);
                    carregarTotais(mes);
                } else {
                    $('#tabelaDespesasCorpo').html(`<tr><td colspan="8" class="text-center py-6 text-rose-500">${res.message || 'Erro ao carregar despesas.'}</td></tr>`);
                }
            }, 'json').fail(function() {
                $('#tabelaDespesasCorpo').html(`<tr><td colspan="8" class="text-center py-6 text-rose-500">Erro de conexão com o servidor.</td></tr>`);
            });
        }

        function carregarTotais(mes) {
            $.post('app.php', { action: 'obter_totais_despesas', mes: mes }, function(res) {
                if (res.success && res.data) {
                    const t = res.data;
                    $('#cardTotalAberto').text(formatarBRL(t.total_aberto));
                    $('#cardQtdAberto').text(`${t.qtd_aberto} pendentes`);

                    $('#cardTotalPago').text(formatarBRL(t.total_pago));
                    $('#cardQtdPago').text(`${t.qtd_liquidada} liquidadas`);

                    $('#cardTotalAtrasado').text(formatarBRL(t.total_atrasado));
                    $('#cardQtdAtrasado').text(`${t.qtd_atrasada} vencidas`);

                    $('#cardTotalGeral').text(formatarBRL(t.total_geral));
                }
            }, 'json');
        }

        function renderizarTabelaDespesas(lista) {
            $('#badgeTotalRegistros').text(lista.length);

            if (!lista || lista.length === 0) {
                $('#tabelaDespesasCorpo').html(`
                    <tr>
                        <td colspan="8" class="text-center py-10 text-gray-400">
                            <span class="material-icons text-4xl mb-2 text-gray-300">receipt_long</span>
                            <p class="font-medium text-gray-600">Nenhuma despesa encontrada para os filtros selecionados.</p>
                            <p class="text-xs text-gray-400 mt-1">Clique em "Nova Despesa" ou "Sincronizar Mês" para gerar lançamentos.</p>
                        </td>
                    </tr>
                `);
                return;
            }

            let html = '';
            lista.forEach(d => {
                // Status badge
                let statusBadge = '';
                if (d.status === 'Liquidada') {
                    statusBadge = '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">Liquidada</span>';
                } else if (d.status === 'Cancelada') {
                    statusBadge = '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-gray-100 text-gray-600">Cancelada</span>';
                } else if (d.is_atrasada) {
                    statusBadge = '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-100 text-rose-800 badge-pulse">Atrasada</span>';
                } else {
                    statusBadge = '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800">Em Aberto</span>';
                }

                // Centro de custo tag
                let ccBadge = '<span class="text-gray-400 text-xs italic">Geral</span>';
                if (d.centro_custo_nome) {
                    const cor = d.centro_custo_cor || '#0284c7';
                    ccBadge = `<span class="inline-block px-2 py-0.5 rounded text-[11px] font-bold text-white shadow-xs" style="background-color: ${cor}">${d.centro_custo_nome}</span>`;
                }

                // Tipo icon
                let tipoIcon = '';
                let btnEditTemplateRec = '';
                if (d.tipo === 'parcelada') {
                    tipoIcon = `<span class="inline-flex items-center text-[10px] font-semibold text-blue-600 bg-blue-50 px-1.5 py-0.5 rounded border border-blue-200 mr-1" title="Parcela ${d.parcela_atual}/${d.total_parcelas}"><span class="material-icons text-[12px] mr-0.5">payments</span>${d.parcela_atual}/${d.total_parcelas}</span>`;
                } else if (d.tipo === 'recorrente') {
                    const idMatriz = d.id_despesa_origem || d.id_despesa;
                    tipoIcon = `<button type="button" onclick="abrirEditarTemplateRecorrente(${idMatriz})" class="inline-flex items-center text-[10px] font-semibold text-purple-700 bg-purple-50 hover:bg-purple-100 px-1.5 py-0.5 rounded border border-purple-200 mr-1 transition cursor-pointer" title="Editar Template da Recorrência (Regra Matriz)"><span class="material-icons text-[12px] mr-0.5">event_repeat</span>Recorrente</button>`;
                    btnEditTemplateRec = `
                        <button type="button" onclick="abrirEditarTemplateRecorrente(${idMatriz})" 
                            class="p-1.5 text-purple-600 hover:bg-purple-50 rounded-lg transition" title="Editar Template Matriz da Recorrência">
                            <span class="material-icons text-base">event_repeat</span>
                        </button>
                    `;
                }

                // Anexos badge/button
                const totalAnx = parseInt(d.total_anexos || 0);
                const btnAnexos = `
                    <button type="button" onclick="abrirModalAnexos(${d.id_despesa}, '${escapeHtml(d.descricao)}')" 
                        class="inline-flex items-center text-xs px-2 py-1 rounded-lg border transition ${totalAnx > 0 ? 'bg-cyan-50 border-cyan-200 text-cyan-700 font-bold hover:bg-cyan-100' : 'bg-gray-50 border-gray-200 text-gray-400 hover:bg-gray-100'}">
                        <span class="material-icons text-sm mr-1">attach_file</span>
                        ${totalAnx}
                    </button>
                `;

                // Botão de liquidação e reversão
                let btnLiquidar = '';
                let btnReverter = '';
                if (d.status === 'Em Aberto') {
                    btnLiquidar = `
                        <button type="button" onclick="abrirModalLiquidar(${d.id_despesa})" 
                            class="p-1.5 bg-emerald-50 text-emerald-700 hover:bg-emerald-600 hover:text-white rounded-lg transition" title="Liquidar / Pagar">
                            <span class="material-icons text-base">check_circle</span>
                        </button>
                    `;
                } else if (d.status === 'Liquidada') {
                    btnReverter = `
                        <button type="button" onclick="reverterLiquidacao(${d.id_despesa})" 
                            class="p-1.5 bg-amber-50 text-amber-700 hover:bg-amber-600 hover:text-white rounded-lg transition" title="Reverter Liquidação (Voltar para Em Aberto)">
                            <span class="material-icons text-base">undo</span>
                        </button>
                    `;
                }

                html += `
                    <tr class="hover:bg-gray-50 transition border-b border-gray-100">
                        <td class="px-4 py-3 whitespace-nowrap">
                            <span class="font-bold ${d.is_atrasada ? 'text-rose-600' : 'text-gray-800'}">${formatarDataBR(d.data_vencimento)}</span>
                            ${d.data_pagamento ? `<div class="text-[11px] text-emerald-600">Pago em: ${formatarDataBR(d.data_pagamento)}</div>` : ''}
                        </td>
                        <td class="px-4 py-3">
                            <div class="font-bold text-gray-800">${escapeHtml(d.razao_social)}</div>
                            ${d.nome_fantasia ? `<div class="text-xs text-gray-400">${escapeHtml(d.nome_fantasia)}</div>` : ''}
                            ${d.cpf_cnpj ? `<div class="text-[11px] text-gray-400">${d.cpf_cnpj}</div>` : ''}
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center flex-wrap gap-1">
                                ${tipoIcon}
                                <span class="font-medium text-gray-800">${escapeHtml(d.descricao)}</span>
                            </div>
                            ${d.numero_documento ? `<div class="text-xs text-gray-400">Doc: ${escapeHtml(d.numero_documento)}</div>` : ''}
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            ${ccBadge}
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <div class="font-bold text-gray-900">${formatarBRL(d.valor)}</div>
                            ${d.status === 'Liquidada' && d.valor_pago ? `<div class="text-[11px] text-emerald-600">Pago: ${formatarBRL(d.valor_pago)}</div>` : ''}
                        </td>
                        <td class="px-4 py-3 text-center whitespace-nowrap">
                            ${statusBadge}
                        </td>
                        <td class="px-4 py-3 text-center whitespace-nowrap">
                            ${btnAnexos}
                        </td>
                        <td class="px-4 py-3 text-center whitespace-nowrap">
                            <div class="flex items-center justify-center gap-1">
                                ${btnLiquidar}
                                ${btnReverter}
                                ${btnEditTemplateRec}
                                <button type="button" onclick="editarDespesa(${d.id_despesa})" 
                                    class="p-1.5 text-slate-500 hover:bg-slate-100 rounded-lg transition" title="Editar Despesa">
                                    <span class="material-icons text-base">edit</span>
                                </button>
                                <button type="button" onclick="excluirDespesa(${d.id_despesa})" 
                                    class="p-1.5 text-rose-500 hover:bg-rose-50 rounded-lg transition" title="Excluir Despesa">
                                    <span class="material-icons text-base">delete</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            });

            $('#tabelaDespesasCorpo').html(html);
        }

        // =========================================================================
        // SINCRONIZAÇÃO DE RECORRÊNCIAS
        // =========================================================================
        function sincronizarRecorrencias() {
            const mes = $('#filtroMes').val();
            const btn = $('#btnSyncRecorrencias');
            const originalHtml = btn.html();

            btn.html('<span class="material-icons text-base animate-spin mr-1">sync</span> Sincronizando...').prop('disabled', true);

            $.post('app.php', { action: 'sincronizar_recorrencias_mes', mes: mes }, function(res) {
                if (res.success) {
                    alert(res.message);
                    carregarDespesas();
                } else {
                    alert('Erro: ' + res.message);
                }
            }, 'json').always(function() {
                btn.html(originalHtml).prop('disabled', false);
            });
        }

        // =========================================================================
        // MODAL DESPESA (AVULSA / PARCELADA / RECORRENTE)
        // =========================================================================
        function alternarTipoDespesa(tipo) {
            $('input[name="tipo"][value="' + tipo + '"]').prop('checked', true);

            // Estilos visuais
            $('#labelTipoAvulsa').removeClass('border-rose-500 bg-rose-50/30').addClass('border-gray-200');
            $('#labelTipoParcelada').removeClass('border-blue-500 bg-blue-50/30').addClass('border-gray-200');
            $('#labelTipoRecorrente').removeClass('border-purple-500 bg-purple-50/30').addClass('border-gray-200');

            if (tipo === 'avulsa') {
                $('#labelTipoAvulsa').addClass('border-rose-500 bg-rose-50/30');
                $('#blocoParcelamento').addClass('hidden');
                $('#hintVencimento').text('Data em que o pagamento deve ser realizado.');
            } else if (tipo === 'parcelada') {
                $('#labelTipoParcelada').addClass('border-blue-500 bg-blue-50/30');
                $('#blocoParcelamento').removeClass('hidden');
                $('#hintVencimento').text('Data de vencimento da 1ª parcela.');
            } else if (tipo === 'recorrente') {
                $('#labelTipoRecorrente').addClass('border-purple-500 bg-purple-50/30');
                $('#blocoParcelamento').addClass('hidden');
                $('#hintVencimento').text('O dia informado será usado todo mês para as recorrências.');
            }
        }

        function abrirModalDespesa(id = null) {
            $('#formDespesa')[0].reset();
            $('#despesaId').val('');
            $('#containerTipoDespesa').show();
            alternarTipoDespesa('avulsa');
            $('#modalDespesaTitulo').text('Lançar Nova Despesa');
            $('#despesaVencimento').val('<?= date('Y-m-d') ?>');

            $('#modalDespesa').removeClass('hidden');
        }

        function fecharModalDespesa() {
            $('#modalDespesa').addClass('hidden');
        }

        function editarDespesa(id) {
            const desp = despesasCache.find(d => d.id_despesa == id);
            if (!desp) return;

            $('#formDespesa')[0].reset();
            $('#despesaId').val(desp.id_despesa);
            $('#modalDespesaTitulo').text('Editar Despesa #' + desp.id_despesa);

            // Esconder seletor de tipo na edição para não recriar parcelas
            $('#containerTipoDespesa').hide();
            $('#blocoParcelamento').addClass('hidden');

            $('#despesaFornecedor').val(desp.id_fornecedor);
            $('#despesaCentroCusto').val(desp.id_centro_custo || '');
            $('#despesaDescricao').val(desp.descricao);
            $('#despesaVencimento').val(desp.data_vencimento);
            $('#despesaValor').val(parseFloat(desp.valor).toFixed(2).replace('.', ','));
            $('#despesaNumeroDoc').val(desp.numero_documento || '');
            $('#despesaObs').val(desp.observacoes || '');

            $('#modalDespesa').removeClass('hidden');
        }

        function salvarDespesa(e) {
            e.preventDefault();
            const btn = $('#btnSalvarDespesa');
            const originalText = btn.html();
            btn.html('<span class="material-icons text-base animate-spin mr-1">refresh</span> Salvando...').prop('disabled', true);

            const formData = $('#formDespesa').serialize();

            $.post('app.php', { action: 'salvar_despesa', ...$('#formDespesa').serializeArray().reduce((acc, cur) => ({...acc, [cur.name]: cur.value}), {}) }, function(res) {
                if (res.success) {
                    fecharModalDespesa();
                    carregarDespesas();
                } else {
                    alert('Erro: ' + res.message);
                }
            }, 'json').fail(function() {
                alert('Erro de comunicação com o servidor.');
            }).always(function() {
                btn.html(originalText).prop('disabled', false);
            });
        }

        function excluirDespesa(id) {
            if (!confirm('Deseja realmente excluir esta despesa?\nSe houver anexos, eles também serão desvinculados.')) return;

            $.post('app.php', { action: 'excluir_despesa', id_despesa: id }, function(res) {
                if (res.success) {
                    carregarDespesas();
                } else {
                    alert('Erro: ' + res.message);
                }
            }, 'json');
        }

        // =========================================================================
        // LIQUIDAÇÃO / BAIXA
        // =========================================================================
        function abrirModalLiquidar(id) {
            const desp = despesasCache.find(d => d.id_despesa == id);
            if (!desp) return;

            // Reset do estado do botão antes de exibir
            const btn = $('#btnConfirmarLiquidar');
            btn.prop('disabled', false).html('<span class="material-icons text-base mr-1">check_circle</span> Confirmar Baixa');

            $('#liquidarIdDespesa').val(desp.id_despesa);
            $('#liquidarDescricao').text(desp.descricao);
            $('#liquidarFornecedor').text(desp.razao_social);
            $('#liquidarVencimento').text('Vencimento: ' + formatarDataBR(desp.data_vencimento));

            // Agora no formato ISO local YYYY-MM-DDTHH:MM
            const now = new Date();
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            $('#liquidarDataPagamento').val(`${year}-${month}-${day}T${hours}:${minutes}`);

            $('#liquidarValorPago').val(parseFloat(desp.valor).toFixed(2).replace('.', ','));
            $('#liquidarFormaPagamento').val('Pix');
            $('#liquidarComprovante').val('');

            $('#modalLiquidar').removeClass('hidden');
        }

        function fecharModalLiquidar() {
            $('#modalLiquidar').addClass('hidden');
            $('#liquidarComprovante').val('');
            const btn = $('#btnConfirmarLiquidar');
            btn.prop('disabled', false).html('<span class="material-icons text-base mr-1">check_circle</span> Confirmar Baixa');
        }

        function confirmarLiquidacao(e) {
            e.preventDefault();
            const idDespesa = $('#liquidarIdDespesa').val();
            const dataPag = $('#liquidarDataPagamento').val();
            const valPago = $('#liquidarValorPago').val();
            const formaPag = $('#liquidarFormaPagamento').val();
            const comprovanteInput = $('#liquidarComprovante')[0];
            const comprovanteFile = (comprovanteInput && comprovanteInput.files && comprovanteInput.files.length > 0) ? comprovanteInput.files[0] : null;

            const btn = $('#btnConfirmarLiquidar');
            btn.html('<span class="material-icons text-base animate-spin mr-1">refresh</span> Baixando...').prop('disabled', true);

            $.post('app.php', {
                action: 'liquidar_despesa',
                id_despesa: idDespesa,
                data_pagamento: dataPag,
                valor_pago: valPago,
                forma_pagamento: formaPag
            }, function(res) {
                if (res.success) {
                    // Se enviou comprovante, faz o upload logo em seguida
                    if (comprovanteFile) {
                        const formData = new FormData();
                        formData.append('action', 'upload_anexo_despesa');
                        formData.append('id_despesa', idDespesa);
                        formData.append('tipo_documento', 'Comprovante');
                        formData.append('arquivo', comprovanteFile);

                        $.ajax({
                            url: 'app.php',
                            type: 'POST',
                            data: formData,
                            processData: false,
                            contentType: false,
                            dataType: 'json',
                            complete: function() {
                                fecharModalLiquidar();
                                carregarDespesas();
                            }
                        });
                    } else {
                        fecharModalLiquidar();
                        carregarDespesas();
                    }
                } else {
                    alert('Erro ao liquidar: ' + res.message);
                    btn.prop('disabled', false).html('<span class="material-icons text-base mr-1">check_circle</span> Confirmar Baixa');
                }
            }, 'json').fail(function(xhr) {
                alert('Erro de comunicação ao liquidar despesa.');
                btn.prop('disabled', false).html('<span class="material-icons text-base mr-1">check_circle</span> Confirmar Baixa');
            });
        }

        function reverterLiquidacao(id) {
            if (!confirm('Deseja realmente reverter a liquidação desta despesa?\n\nO status retornará para "Em Aberto" e os dados de pagamento serão limpos.')) return;

            $.post('app.php', { action: 'reverter_liquidacao_despesa', id_despesa: id }, function(res) {
                if (res.success) {
                    carregarDespesas();
                } else {
                    alert('Erro: ' + res.message);
                }
            }, 'json');
        }

        // =========================================================================
        // GESTÃO DE ANEXOS
        // =========================================================================
        let anexoDespesaAtualId = null;

        function abrirModalAnexos(idDespesa, descricao) {
            anexoDespesaAtualId = idDespesa;
            $('#anexoIdDespesa').val(idDespesa);
            $('#modalAnexosTitulo').text('Anexos: ' + descricao);
            $('#formUploadAnexo')[0].reset();
            carregarListaAnexos(idDespesa);
            $('#modalAnexos').removeClass('hidden');
        }

        function fecharModalAnexos() {
            $('#modalAnexos').addClass('hidden');
            anexoDespesaAtualId = null;
        }

        function carregarListaAnexos(idDespesa) {
            $('#listaAnexosContainer').html('<p class="text-center py-4 text-gray-400 text-xs italic"><span class="material-icons animate-spin text-sm">refresh</span> Carregando arquivos...</p>');

            $.post('app.php', { action: 'listar_anexos_despesa', id_despesa: idDespesa }, function(res) {
                if (res.success && res.data) {
                    if (res.data.length === 0) {
                        $('#listaAnexosContainer').html('<p class="text-center py-4 text-gray-400 text-xs italic">Nenhum anexo encontrado para esta despesa.</p>');
                        return;
                    }

                    let html = '';
                    res.data.forEach(a => {
                        const tamanhoKb = a.tamanho_bytes ? (a.tamanho_bytes / 1024).toFixed(1) + ' KB' : '';
                        html += `
                            <div class="flex items-center justify-between p-3 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition">
                                <div class="flex items-center gap-2 overflow-hidden mr-2">
                                    <span class="p-2 bg-slate-100 text-slate-700 rounded-lg">
                                        <span class="material-icons text-base">description</span>
                                    </span>
                                    <div class="overflow-hidden">
                                        <a href="${a.url_publica}" target="_blank" class="text-xs font-bold text-blue-600 hover:underline truncate block" title="${escapeHtml(a.nome_original)}">
                                            ${escapeHtml(a.nome_original)}
                                        </a>
                                        <span class="text-[10px] text-gray-400">
                                            <span class="bg-gray-100 px-1.5 py-0.5 rounded font-semibold text-gray-600">${escapeHtml(a.tipo_documento)}</span>
                                            ${tamanhoKb ? ' • ' + tamanhoKb : ''}
                                        </span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-1">
                                    <a href="${a.url_publica}" target="_blank" download class="p-1.5 text-gray-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition" title="Baixar">
                                        <span class="material-icons text-sm">download</span>
                                    </a>
                                    <button type="button" onclick="excluirAnexo(${a.id_vinculo})" class="p-1.5 text-gray-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition" title="Remover">
                                        <span class="material-icons text-sm">delete</span>
                                    </button>
                                </div>
                            </div>
                        `;
                    });
                    $('#listaAnexosContainer').html(html);
                }
            }, 'json');
        }

        function enviarNovoAnexo(e) {
            e.preventDefault();
            const btn = $('#btnEnviarAnexo');
            const originalText = btn.html();
            btn.html('<span class="material-icons text-sm animate-spin mr-1">refresh</span> Enviando...').prop('disabled', true);

            const formData = new FormData($('#formUploadAnexo')[0]);
            formData.append('action', 'upload_anexo_despesa');

            $.ajax({
                url: 'app.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        $('#formUploadAnexo')[0].reset();
                        carregarListaAnexos(anexoDespesaAtualId);
                        carregarDespesas(); // atualiza contador na tabela
                    } else {
                        alert('Erro ao enviar: ' + res.message);
                    }
                },
                error: function() {
                    alert('Erro na requisição de upload.');
                },
                complete: function() {
                    btn.html(originalText).prop('disabled', false);
                }
            });
        }

        function excluirAnexo(idVinculo) {
            if (!confirm('Deseja desvincular este anexo?')) return;

            $.post('app.php', { action: 'excluir_anexo_despesa', id_vinculo: idVinculo }, function(res) {
                if (res.success) {
                    carregarListaAnexos(anexoDespesaAtualId);
                    carregarDespesas();
                } else {
                    alert('Erro: ' + res.message);
                }
            }, 'json');
        }

        // =========================================================================
        // GESTÃO DE FORNECEDORES
        // =========================================================================
        function abrirModalFornecedores(focusNew = false) {
            limparFormFornecedor();
            carregarTabelaFornecedores();
            $('#modalFornecedores').removeClass('hidden');
            if (focusNew) {
                $('#fornecedorRazao').focus();
            }
        }

        function fecharModalFornecedores() {
            $('#modalFornecedores').addClass('hidden');
            // Atualizar selects de fornecedores
            atualizarSelectsFornecedores();
        }

        function limparFormFornecedor() {
            $('#formFornecedor')[0].reset();
            $('#fornecedorId').val('');
            $('#formFornecedorTitulo').text('Novo Fornecedor');
        }

        function carregarTabelaFornecedores() {
            const termo = $('#buscaFornecedorTabela').val();
            $('#tabelaFornecedoresCorpo').html('<tr><td colspan="4" class="text-center py-4 text-gray-400">Carregando fornecedores...</td></tr>');

            $.post('app.php', { action: 'listar_fornecedores', termo: termo }, function(res) {
                if (res.success && res.data) {
                    if (res.data.length === 0) {
                        $('#tabelaFornecedoresCorpo').html('<tr><td colspan="4" class="text-center py-4 text-gray-400">Nenhum fornecedor encontrado.</td></tr>');
                        return;
                    }
                    let html = '';
                    res.data.forEach(f => {
                        html += `
                            <tr class="hover:bg-gray-50 border-b border-gray-100">
                                <td class="px-3 py-2">
                                    <div class="font-bold text-gray-800">${escapeHtml(f.razao_social)}</div>
                                    ${f.nome_fantasia ? `<div class="text-gray-400">${escapeHtml(f.nome_fantasia)}</div>` : ''}
                                </td>
                                <td class="px-3 py-2 text-gray-600">${f.cpf_cnpj || '-'}</td>
                                <td class="px-3 py-2">
                                    <div>${f.telefone || '-'}</div>
                                    ${f.email ? `<div class="text-[10px] text-gray-400">${escapeHtml(f.email)}</div>` : ''}
                                </td>
                                <td class="px-3 py-2 text-center">
                                    <button type="button" onclick="editarFornecedor(${f.id_fornecedor})" class="p-1 text-slate-500 hover:text-cyan-600">
                                        <span class="material-icons text-sm">edit</span>
                                    </button>
                                    <button type="button" onclick="excluirFornecedor(${f.id_fornecedor})" class="p-1 text-slate-400 hover:text-rose-600">
                                        <span class="material-icons text-sm">delete</span>
                                    </button>
                                </td>
                            </tr>
                        `;
                    });
                    $('#tabelaFornecedoresCorpo').html(html);
                }
            }, 'json');
        }

        function salvarFornecedor(e) {
            e.preventDefault();
            const btn = $('#btnSalvarFornecedor');
            const originalText = btn.html();
            btn.html('Salvando...').prop('disabled', true);

            $.post('app.php', {
                action: 'salvar_fornecedor',
                id_fornecedor: $('#fornecedorId').val(),
                razao_social: $('#fornecedorRazao').val(),
                nome_fantasia: $('#fornecedorFantasia').val(),
                cpf_cnpj: $('#fornecedorCpfCnpj').val(),
                telefone: $('#fornecedorTelefone').val(),
                email: $('#fornecedorEmail').val(),
                contato_responsavel: $('#fornecedorContato').val(),
                observacoes: $('#fornecedorObs').val()
            }, function(res) {
                if (res.success) {
                    limparFormFornecedor();
                    carregarTabelaFornecedores();
                    atualizarSelectsFornecedores();
                } else {
                    alert('Erro: ' + res.message);
                }
            }, 'json').always(function() {
                btn.html(originalText).prop('disabled', false);
            });
        }

        function editarFornecedor(id) {
            $.post('app.php', { action: 'obter_fornecedor', id_fornecedor: id }, function(res) {
                if (res.success && res.data) {
                    const f = res.data;
                    $('#fornecedorId').val(f.id_fornecedor);
                    $('#fornecedorRazao').val(f.razao_social);
                    $('#fornecedorFantasia').val(f.nome_fantasia || '');
                    $('#fornecedorCpfCnpj').val(f.cpf_cnpj || '');
                    $('#fornecedorTelefone').val(f.telefone || '');
                    $('#fornecedorEmail').val(f.email || '');
                    $('#fornecedorContato').val(f.contato_responsavel || '');
                    $('#fornecedorObs').val(f.observacoes || '');
                    $('#formFornecedorTitulo').text('Editar Fornecedor #' + f.id_fornecedor);
                }
            }, 'json');
        }

        function excluirFornecedor(id) {
            if (!confirm('Deseja excluir este fornecedor? Se houver despesas associadas, ele será apenas inativado.')) return;

            $.post('app.php', { action: 'excluir_fornecedor', id_fornecedor: id }, function(res) {
                if (res.success) {
                    carregarTabelaFornecedores();
                    atualizarSelectsFornecedores();
                } else {
                    alert('Erro: ' + res.message);
                }
            }, 'json');
        }

        function atualizarSelectsFornecedores() {
            $.post('app.php', { action: 'listar_fornecedores' }, function(res) {
                if (res.success && res.data) {
                    fornecedoresCache = res.data;
                    let opts = '<option value="">Selecione um fornecedor...</option>';
                    res.data.forEach(f => {
                        opts += `<option value="${f.id_fornecedor}">${escapeHtml(f.razao_social)}${f.nome_fantasia ? ' (' + escapeHtml(f.nome_fantasia) + ')' : ''}</option>`;
                    });
                    $('#despesaFornecedor').html(opts);
                }
            }, 'json');
        }

        // =========================================================================
        // GESTÃO DE CENTROS DE CUSTO
        // =========================================================================
        function abrirModalCentrosCusto() {
            $('#formCentroCusto')[0].reset();
            $('#centroCustoId').val('');
            $('#centroCustoCor').val('#0284c7');
            carregarTabelaCentrosCusto();
            $('#modalCentrosCusto').removeClass('hidden');
        }

        function fecharModalCentrosCusto() {
            $('#modalCentrosCusto').addClass('hidden');
            atualizarSelectsCentrosCusto();
        }

        function carregarTabelaCentrosCusto() {
            $('#tabelaCentrosCustoCorpo').html('<tr><td colspan="3" class="text-center py-4 text-gray-400">Carregando...</td></tr>');

            $.post('app.php', { action: 'listar_centros_custo' }, function(res) {
                if (res.success && res.data) {
                    centrosCustoCache = res.data;
                    let html = '';
                    res.data.forEach(cc => {
                        html += `
                            <tr class="hover:bg-gray-50 border-b border-gray-100">
                                <td class="px-3 py-2 flex items-center gap-2">
                                    <span class="w-3 h-3 rounded-full" style="background-color: ${cc.cor || '#0284c7'}"></span>
                                    <span class="font-bold text-gray-800">${escapeHtml(cc.nome)}</span>
                                </td>
                                <td class="px-3 py-2 text-gray-500">${escapeHtml(cc.descricao || '-')}</td>
                                <td class="px-3 py-2 text-center">
                                    <button type="button" onclick="excluirCentroCusto(${cc.id_centro_custo})" class="p-1 text-slate-400 hover:text-rose-600">
                                        <span class="material-icons text-sm">delete</span>
                                    </button>
                                </td>
                            </tr>
                        `;
                    });
                    $('#tabelaCentrosCustoCorpo').html(html);
                }
            }, 'json');
        }

        function salvarCentroCusto(e) {
            e.preventDefault();
            $.post('app.php', {
                action: 'salvar_centro_custo',
                id_centro_custo: $('#centroCustoId').val(),
                nome: $('#centroCustoNome').val(),
                descricao: $('#centroCustoDesc').val(),
                cor: $('#centroCustoCor').val()
            }, function(res) {
                if (res.success) {
                    $('#formCentroCusto')[0].reset();
                    $('#centroCustoId').val('');
                    $('#centroCustoCor').val('#0284c7');
                    carregarTabelaCentrosCusto();
                    atualizarSelectsCentrosCusto();
                } else {
                    alert('Erro: ' + res.message);
                }
            }, 'json');
        }

        function excluirCentroCusto(id) {
            if (!confirm('Deseja remover este centro de custo?')) return;
            $.post('app.php', { action: 'excluir_centro_custo', id_centro_custo: id }, function(res) {
                if (res.success) {
                    carregarTabelaCentrosCusto();
                    atualizarSelectsCentrosCusto();
                } else {
                    alert('Erro: ' + res.message);
                }
            }, 'json');
        }

        function atualizarSelectsCentrosCusto() {
            $.post('app.php', { action: 'listar_centros_custo' }, function(res) {
                if (res.success && res.data) {
                    centrosCustoCache = res.data;
                    let optsFiltro = '<option value="">Todos</option>';
                    let optsForm = '<option value="">Nenhum (Geral)</option>';
                    res.data.forEach(cc => {
                        optsFiltro += `<option value="${cc.id_centro_custo}">${escapeHtml(cc.nome)}</option>`;
                        optsForm += `<option value="${cc.id_centro_custo}">${escapeHtml(cc.nome)}</option>`;
                    });
                    $('#filtroCentroCusto').html(optsFiltro);
                    $('#despesaCentroCusto').html(optsForm);
                }
            }, 'json');
        }

        // =========================================================================
        // REGRAS RECORRENTES
        // =========================================================================
        function abrirModalRegrasRecorrentes() {
            carregarTabelaRegrasRecorrentes();
            $('#modalRegrasRecorrentes').removeClass('hidden');
        }

        function fecharModalRegrasRecorrentes() {
            $('#modalRegrasRecorrentes').addClass('hidden');
        }

        function carregarTabelaRegrasRecorrentes() {
            $('#tabelaRegrasRecorrentesCorpo').html('<tr><td colspan="6" class="text-center py-4 text-gray-400">Carregando...</td></tr>');

            $.post('app.php', { action: 'listar_recorrencias_regras' }, function(res) {
                if (res.success && res.data) {
                    if (res.data.length === 0) {
                        $('#tabelaRegrasRecorrentesCorpo').html('<tr><td colspan="6" class="text-center py-4 text-gray-400">Nenhuma regra recorrente cadastrada.</td></tr>');
                        return;
                    }
                    let html = '';
                    res.data.forEach(r => {
                        const isAtiva = parseInt(r.recorrencia_ativa || 0) === 1;
                        html += `
                            <tr class="hover:bg-gray-50 border-b border-gray-100">
                                <td class="px-3 py-2 font-bold text-gray-800">${escapeHtml(r.descricao)}</td>
                                <td class="px-3 py-2 text-gray-600">${escapeHtml(r.razao_social)}</td>
                                <td class="px-3 py-2 font-semibold">Dia ${r.dia_vencimento_recorrencia || 10}</td>
                                <td class="px-3 py-2 text-right font-bold text-gray-900">${formatarBRL(r.valor)}</td>
                                <td class="px-3 py-2 text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold ${isAtiva ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-500'}">
                                        ${isAtiva ? 'Ativa' : 'Pausada'}
                                    </span>
                                </td>
                                <td class="px-3 py-2 text-center">
                                    <div class="flex items-center justify-center gap-1">
                                        <button type="button" onclick="abrirEditarTemplateRecorrente(${r.id_despesa})" 
                                            class="p-1.5 rounded-lg text-purple-700 bg-purple-50 hover:bg-purple-100 transition" title="Editar Template da Recorrência">
                                            <span class="material-icons text-sm">edit</span>
                                        </button>
                                        <button type="button" onclick="alternarRecorrencia(${r.id_despesa}, ${isAtiva ? 0 : 1})" 
                                            class="px-2 py-1 rounded-lg text-xs font-bold transition ${isAtiva ? 'bg-amber-50 text-amber-700 hover:bg-amber-100' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100'}">
                                            ${isAtiva ? 'Pausar' : 'Reativar'}
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        `;
                    });
                    $('#tabelaRegrasRecorrentesCorpo').html(html);
                }
            }, 'json');
        }

        function alternarRecorrencia(id, novoStatus) {
            $.post('app.php', { action: 'alternar_status_recorrencia', id_despesa: id, ativo: novoStatus }, function(res) {
                if (res.success) {
                    carregarTabelaRegrasRecorrentes();
                } else {
                    alert('Erro: ' + res.message);
                }
            }, 'json');
        }

        function abrirEditarTemplateRecorrente(id) {
            $.post('app.php', { action: 'obter_regra_recorrente', id_despesa: id }, function(res) {
                if (res.success && res.data) {
                    const r = res.data;
                    $('#editRecId').val(r.id_despesa);
                    $('#editRecDescricao').val(r.descricao);
                    $('#editRecFornecedor').val(r.id_fornecedor);
                    $('#editRecCentroCusto').val(r.id_centro_custo || '');
                    $('#editRecDiaVenc').val(r.dia_vencimento_recorrencia || 10);
                    $('#editRecValor').val(parseFloat(r.valor).toFixed(2).replace('.', ','));
                    $('#editRecObs').val(r.observacoes || '');
                    $('#editRecAtiva').prop('checked', parseInt(r.recorrencia_ativa || 0) === 1);

                    $('#modalEditarTemplateRecorrente').removeClass('hidden');
                } else {
                    alert('Erro: ' + res.message);
                }
            }, 'json');
        }

        function fecharModalEditarTemplateRecorrente() {
            $('#modalEditarTemplateRecorrente').addClass('hidden');
        }

        function salvarTemplateRecorrente(e) {
            e.preventDefault();
            const btn = $('#btnSalvarTemplateRec');
            const origText = btn.html();
            btn.html('<span class="material-icons text-sm animate-spin mr-1">refresh</span> Salvando...').prop('disabled', true);

            $.post('app.php', {
                action: 'salvar_template_recorrencia',
                id_despesa: $('#editRecId').val(),
                descricao: $('#editRecDescricao').val(),
                id_fornecedor: $('#editRecFornecedor').val(),
                id_centro_custo: $('#editRecCentroCusto').val(),
                dia_vencimento: $('#editRecDiaVenc').val(),
                valor: $('#editRecValor').val(),
                observacoes: $('#editRecObs').val(),
                recorrencia_ativa: $('#editRecAtiva').is(':checked') ? 1 : 0
            }, function(res) {
                if (res.success) {
                    fecharModalEditarTemplateRecorrente();
                    carregarTabelaRegrasRecorrentes();
                    carregarDespesas();
                } else {
                    alert('Erro: ' + res.message);
                }
            }, 'json').always(function() {
                btn.html(origText).prop('disabled', false);
            });
        }

        function escapeHtml(text) {
            if (!text) return '';
            return text
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }
    </script>
</body>
</html>
