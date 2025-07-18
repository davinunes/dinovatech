<?php
// index.php (Painel Administrativo)

// Apenas para incluir o database.php caso seja necessário para alguma informação inicial,
// mas a lógica principal será via AJAX para app.php
// O caminho "../database.php" está correto, pois o database.php está um nível acima.
include "../database.php"; 
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administração Dinovatech</title>
    <!-- Inclua jQuery via CDN -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <!-- Inclua jQuery UI para autocomplete e dialog (modal) -->
    <script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.13.2/jquery-ui.min.js"></script>
    <link rel="stylesheet" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.13.2/themes/smoothness/jquery-ui.css">

    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f4f4f4; }
        .container { max-width: 960px; margin: 0 auto; padding: 20px; background-color: #fff; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        h1, h2 { text-align: center; color: #333; }
        .controls { text-align: center; margin-bottom: 20px; }
        .controls button { padding: 10px 20px; margin: 5px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; transition: background-color 0.3s; }
        .controls button.primary { background-color: #007bff; color: white; }
        .controls button.primary:hover { background-color: #0056b3; }
        .controls button.secondary { background-color: #6c757d; color: white; }
        .controls button.secondary:hover { background-color: #5a6268; }

        .search-area { margin-top: 30px; padding: 15px; border: 1px solid #ddd; border-radius: 8px; background-color: #e9ecef; }
        .search-area label { display: block; margin-bottom: 8px; font-weight: bold; }
        .search-area input[type="text"] { width: calc(100% - 22px); padding: 10px; border: 1px solid #ccc; border-radius: 4px; }
        .client-info, .fatura-info { margin-top: 20px; padding: 15px; border: 1px solid #bce8f1; background-color: #d9edf7; border-radius: 8px; }
        .fatura-list { margin-top: 15px; }
        .fatura-list table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .fatura-list th, .fatura-list td { border: 1px solid #dee2e6; padding: 8px; text-align: left; }
        .fatura-list th { background-color: #e9ecef; font-weight: bold; }
        .fatura-list tr:nth-child(even) { background-color: #f8f9fa; }
        .fatura-list .action-buttons button { padding: 5px 10px; margin-right: 5px; font-size: 0.9em; }

        /* Estilo para modais (jQuery UI Dialog) */
        .ui-dialog { z-index: 1000 !important; }
        .ui-dialog .ui-dialog-content { padding: 1.5em !important; }
        .ui-dialog label { display: block; margin-bottom: 5px; font-weight: bold; }
        .ui-dialog input[type="text"], 
        .ui-dialog input[type="email"], 
        .ui-dialog input[type="number"], 
        .ui-dialog input[type="date"],
        .ui-dialog select,
        .ui-dialog textarea { /* Adicionado textarea */
            width: calc(100% - 20px); 
            padding: 8px; 
            margin-bottom: 10px; 
            border: 1px solid #ccc; 
            border-radius: 4px; 
        }
        .ui-dialog button.submit { background-color: #28a745; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; }
        .ui-dialog button.submit:hover { background-color: #218838; }

        #faturaItensTable th, #faturaItensTable td { padding: 5px; font-size: 0.9em; }
        #faturaItensTable .item-actions button { margin-right: 5px; padding: 3px 8px; font-size: 0.8em; }
        #faturaItensTable .item-actions .btn-edit { background-color: #ffc107; color: #333; }
        #faturaItensTable .item-actions .btn-edit:hover { background-color: #e0a800; }
        #faturaItensTable .item-actions .btn-remove { background-color: #dc3545; color: white; }
        #faturaItensTable .item-actions .btn-remove:hover { background-color: #c82333; }

        .service-search-area { margin-top: 20px; padding: 15px; border: 1px solid #ddd; border-radius: 8px; background-color: #e9ecef; }
        .service-search-area button { background-color: #ffc107; color: #333; }
        .service-search-area button:hover { background-color: #e0a800; }

        /* Estilos para Recorrências */
        #recorrenciasList table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        #recorrenciasList th, #recorrenciasList td { border: 1px solid #dee2e6; padding: 8px; text-align: left; }
        #recorrenciasList th { background-color: #e9ecef; font-weight: bold; }
        #recorrenciasList tr:nth-child(even) { background-color: #f8f9fa; }
        #recorrenciasList .action-buttons button { padding: 3px 8px; font-size: 0.8em; }
        .recorrencia-item { font-size: 0.9em; margin-bottom: 5px; padding: 5px; background-color: #f0f8ff; border-radius: 3px; }

        /* Estilos para Pagamentos */
        #pagamentosList table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        #pagamentosList th, #pagamentosList td { border: 1px solid #dee2e6; padding: 8px; text-align: left; }
        #pagamentosList th { background-color: #e9ecef; font-weight: bold; }
        #pagamentosList tr:nth-child(even) { background-color: #f8f9fa; }
        .pagamento-status-Confirmado { color: #28a745; font-weight: bold; }
        .pagamento-status-Pendente { color: #ffc107; font-weight: bold; }
        .pagamento-status-Cancelado { color: #dc3545; font-weight: bold; }
        
        /* Estilos para seleção de itens de pagamento */
        #itensPagosSelection table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        #itensPagosSelection th, #itensPagosSelection td { border: 1px solid #eee; padding: 5px; text-align: left; font-size: 0.9em; }
        #itensPagosSelection th { background-color: #f9f9f9; }
        #itensPagosSelection input[type="checkbox"] { margin-right: 5px; }
        #itensPagosSelection .item-pagamento-checkbox-row { /* Estilo para a linha do item selecionável */
            background-color: #fdfdfd;
        }
        #itensPagosSelection .item-pagamento-checkbox-row:hover {
            background-color: #f0f0f0;
        }
        #itensPagosSelection .item-pagamento-checkbox-row.selected {
            background-color: #e6f7ff; /* Cor mais clara para itens selecionados */
        }
        .saldo-devedor-fatura-display {
            font-weight: bold;
            color: #d9534f; /* Cor para saldo devedor */
        }

    </style>
</head>
<body>
    <div class="container">
        <h1>Dinovatech - Painel de Administração</h1>

        <div class="controls">
            <button class="primary" id="btnCadastrarCliente">Cadastrar Cliente</button>
            <button class="secondary" id="btnCadastrarServico">Cadastrar Serviço</button>
        </div>

        <div class="search-area">
            <label for="clienteSearch">Buscar Cliente (Nome ou CPF/CNPJ):</label>
            <input type="text" id="clienteSearch" placeholder="Digite para buscar clientes...">
            <div id="clienteSearchResults" style="margin-top: 10px;"></div>
        </div>

        <div id="clientDetailsSection" style="display: none;">
            <div class="client-info">
                <h3>Cliente Selecionado: <span id="selectedClientName"></span></h3>
                <p>CPF/CNPJ: <span id="selectedClientCpfCnpj"></span></p>
                <input type="hidden" id="selectedClientId">
                <button class="primary" id="btnCriarFatura">Criar Nova Fatura para este Cliente</button>
                <button class="secondary" id="btnEditarCliente">Editar Cliente</button>
                <button class="secondary" id="btnVincularRecorrencia">Vincular Recorrência</button>
            </div>

            <div class="fatura-list">
                <h3>Faturas do Cliente:</h3>
                <div id="clientFaturasList">
                    <p>Nenhuma fatura encontrada ou carregada para este cliente.</p>
                </div>
            </div>
        </div>

        <!-- Área para buscar e editar serviços -->
        <div class="service-search-area">
            <label for="servicoSearchEdit">Buscar Serviço para Editar:</label>
            <input type="text" id="servicoSearchEdit" placeholder="Digite para buscar serviços...">
            <input type="hidden" id="selectedServicoIdEdit">
            <button class="secondary" id="btnEditarServico" disabled>Editar Serviço Selecionado</button>
            <div id="servicoSearchResultsEdit" style="margin-top: 10px;"></div>
        </div>


    </div>

    <!-- Modais -->

    <!-- Modal de Cadastro de Cliente -->
    <div id="modalCadastrarCliente" title="Cadastrar Novo Cliente" style="display: none;">
        <form id="formCadastrarCliente">
            <label for="clienteNome">Nome:</label>
            <input type="text" id="clienteNome" name="nome" required>

            <label for="clienteCpfCnpj">CPF/CNPJ:</label>
            <input type="text" id="clienteCpfCnpj" name="cpf_cnpj" required>

            <label for="clienteTelefone">Telefone:</label>
            <input type="text" id="clienteTelefone" name="telefone">

            <label for="clienteEmail">E-mail:</label>
            <input type="email" id="clienteEmail" name="email">

            <button type="submit" class="submit">Salvar Cliente</button>
        </form>
        <div id="clienteMessage" class="mensagem"></div>
    </div>

    <!-- Modal de Edição de Cliente -->
    <div id="modalEditarCliente" title="Editar Cliente" style="display: none;">
        <form id="formEditarCliente">
            <input type="hidden" id="editClienteId" name="id_cliente">
            <label for="editClienteNome">Nome:</label>
            <input type="text" id="editClienteNome" name="nome" required>

            <label for="editClienteCpfCnpj">CPF/CNPJ:</label>
            <input type="text" id="editClienteCpfCnpj" name="cpf_cnpj" required>

            <label for="editClienteTelefone">Telefone:</label>
            <input type="text" id="editClienteTelefone" name="telefone">

            <label for="editClienteEmail">E-mail:</label>
            <input type="email" id="editClienteEmail" name="email">

            <button type="submit" class="submit">Salvar Alterações</button>
        </form>
        <div id="editClienteMessage" class="mensagem"></div>
    </div>


    <!-- Modal de Cadastro de Serviço -->
    <div id="modalCadastrarServico" title="Cadastrar Novo Serviço" style="display: none;">
        <form id="formCadastrarServico">
            <label for="servicoNome">Nome do Serviço:</label>
            <input type="text" id="servicoNome" name="nome_servico" required>

            <label for="servicoValorSugerido">Valor Sugerido (R$):</label>
            <input type="number" id="servicoValorSugerido" name="valor_sugerido" step="0.01" min="0.01" required>

            <button type="submit" class="submit">Salvar Serviço</button>
        </form>
        <div id="servicoMessage" class="mensagem"></div>
    </div>

    <!-- Modal de Edição de Serviço -->
    <div id="modalEditarServico" title="Editar Serviço" style="display: none;">
        <form id="formEditarServico">
            <input type="hidden" id="editServicoId" name="id_servico">
            <label for="editServicoNome">Nome do Serviço:</label>
            <input type="text" id="editServicoNome" name="nome_servico" required>

            <label for="editServicoValorSugerido">Valor Sugerido (R$):</label>
            <input type="number" id="editServicoValorSugerido" name="valor_sugerido" step="0.01" min="0.01" required>

            <button type="submit" class="submit">Salvar Alterações</button>
        </form>
        <div id="editServicoMessage" class="mensagem"></div>
    </div>


    <!-- Modal de Criar Fatura -->
    <div id="modalCriarFatura" title="Criar Nova Fatura" style="display: none;">
        <form id="formCriarFatura">
            <p>Cliente: <strong id="faturaClienteNomeDisplay"></strong></p>
            <input type="hidden" id="faturaClienteIdInput" name="id_cliente">

            <label for="faturaDataEmissao">Data de Emissão:</label>
            <input type="date" id="faturaDataEmissao" name="data_emissao" value="<?= date('Y-m-d') ?>" required>

            <label for="faturaDataVencimento">Data de Vencimento:</label>
            <input type="date" id="faturaDataVencimento" name="data_vencimento" required>

            <button type="submit" class="submit">Criar Fatura</button>
        </form>
        <div id="faturaMessage" class="mensagem"></div>
    </div>

    <!-- Modal de Detalhes e Adicionar Itens à Fatura -->
    <div id="modalFaturaDetalhes" title="Detalhes da Fatura e Itens" style="display: none;">
        <div id="faturaDetalhesHeader">
            <p><strong>Fatura ID:</strong> <span id="detalheFaturaId"></span></p>
            <p><strong>Cliente:</strong> <span id="detalheFaturaCliente"></span></p>
            <p><strong>Emissão:</strong> <span id="detalheFaturaEmissao"></span> | <strong>Vencimento:</strong> <span id="detalheFaturaVencimento"></span></p>
            <p><strong>Status:</strong> <span id="detalheFaturaStatus"></span> | <strong>Total:</strong> R$ <span id="detalheFaturaTotal"></span></p>
            <p><strong>Total Pago:</strong> R$ <span id="detalheFaturaTotalPago">0,00</span></p>
            <p><strong>Saldo Devedor:</strong> R$ <span id="detalheFaturaSaldoDevedor">0,00</span></p>
            <button id="btnIncorporarRecorrencias" class="secondary" style="margin-top: 10px;">Incorporar Recorrências do Mês</button>
            <button id="btnRegistrarPagamento" class="primary btn-registrar-pagamento" style="margin-top: 10px; margin-left: 10px;">Registrar Pagamento</button> <!-- ADICIONADO CLASSE btn-registrar-pagamento -->
        </div>

        <h3>Adicionar Novo Item</h3>
        <form id="formAdicionarItemFatura">
            <input type="hidden" id="itemFaturaId" name="id_fatura">

            <label for="itemServicoSearch">Serviço:</label>
            <input type="text" id="itemServicoSearch" placeholder="Digite para buscar um serviço..." required>
            <input type="hidden" id="itemServicoSelectedId" name="id_servico">

            <label for="itemQuantidade">Quantidade:</label>
            <input type="number" id="itemQuantidade" name="quantidade" min="1" value="1" required>

            <label for="itemValorUnitario">Valor Unitário (R$):</label>
            <input type="number" id="itemValorUnitario" name="valor_unitario" step="0.01" min="0.01" required>

            <label for="itemTag">Tag/Explicação (Opcional):</label>
            <input type="text" id="itemTag" name="tag" maxlength="255">

            <button type="submit" class="submit">Adicionar Item</button>
        </form>
        <div id="itemFaturaMessage" class="mensagem"></div>

        <h3>Itens da Fatura</h3>
        <div id="itensFaturaList">
            <table id="faturaItensTable">
                <thead>
                    <tr>
                        <th>Serviço</th>
                        <th>Qtd</th>
                        <th>Valor Unit.</th>
                        <th>Subtotal</th>
                        <th>Tag</th>
                        <th>Ações</th> 
                    </tr>
                </thead>
                <tbody>
                    <!-- Itens serão carregados aqui via JS -->
                </tbody>
            </table>
        </div>

        <h3>Pagamentos Recebidos</h3>
        <div id="pagamentosList">
            <table id="faturaPagamentosTable">
                <thead>
                    <tr>
                        <th>ID Pagamento</th>
                        <th>Valor Pago</th>
                        <th>Data</th>
                        <th>Status</th>
                        <th>Observação</th>
                        <th>Ações</th> <!-- Nova coluna para o botão Estornar -->
                    </tr>
                </thead>
                <tbody>
                    <!-- Pagamentos serão carregados aqui via JS -->
                </tbody>
            </table>
            <p id="noPaymentsMessage" style="text-align: center; margin-top: 10px; display: none;">Nenhum pagamento registrado para esta fatura.</p>
        </div>
    </div>

    <!-- Modal de Edição de Item da Fatura -->
    <div id="modalEditarItemFatura" title="Editar Item da Fatura" style="display: none;">
        <form id="formEditarItemFatura">
            <input type="hidden" id="editItemFaturaId" name="id_fatura">
            <input type="hidden" id="editItemId" name="id_item_fatura">
            
            <p>Serviço: <strong id="editServicoNomeDisplay"></strong></p>
            <input type="hidden" id="editServicoId" name="id_servico">

            <label for="editQuantidade">Quantidade:</label>
            <input type="number" id="editQuantidade" name="quantidade" min="1" required>

            <label for="editValorUnitario">Valor Unitário (R$):</label>
            <input type="number" id="editValorUnitario" name="valor_unitario" step="0.01" min="0.01" required>

            <label for="editTag">Tag/Explicação (Opcional):</label>
            <input type="text" id="editTag" name="tag" maxlength="255">

            <button type="submit" class="submit">Salvar Edição</button>
        </form>
        <div id="editItemMessage" class="mensagem"></div>
    </div>

    <!-- Modal de Vincular Recorrência -->
    <div id="modalVincularRecorrencia" title="Vincular Recorrência ao Cliente" style="display: none;">
        <p>Cliente: <strong id="recorrenciaClienteNomeDisplay"></strong></p>
        <input type="hidden" id="recorrenciaClienteId" name="id_cliente">

        <h3>Adicionar Nova Recorrência</h3>
        <form id="formAdicionarRecorrencia">
            <input type="hidden" id="addRecorrenciaClienteId" name="id_cliente">

            <label for="addRecorrenciaServicoSearch">Serviço:</label>
            <input type="text" id="addRecorrenciaServicoSearch" placeholder="Digite para buscar um serviço..." required>
            <input type="hidden" id="addRecorrenciaServicoSelectedId" name="id_servico">

            <label for="addRecorrenciaQuantidade">Quantidade:</label>
            <input type="number" id="addRecorrenciaQuantidade" name="quantidade" min="1" value="1" required>

            <label for="addRecorrenciaValorSugerido">Valor Sugerido (R$):</label>
            <input type="number" id="addRecorrenciaValorSugerido" name="valor_sugerido_recorrencia" step="0.01" min="0.01" required>

            <label for="addRecorrenciaTipoPeriodo">Tipo de Período:</label>
            <select id="addRecorrenciaTipoPeriodo" name="tipo_periodo" required>
                <option value="mensal">Mensal</option>
                <option value="anual">Anual</option>
                <option value="semanal">Semanal</option>
                <option value="diario">Diário</option>
            </select>

            <label for="addRecorrenciaIntervalo">Intervalo (a cada X):</label>
            <input type="number" id="addRecorrenciaIntervalo" name="intervalo" min="1" value="1" required>

            <label for="addRecorrenciaDataInicio">Data de Início da Cobrança:</label>
            <input type="date" id="addRecorrenciaDataInicio" name="data_inicio_cobranca" value="<?= date('Y-m-d') ?>" required>

            <label for="addRecorrenciaDataFim">Data de Fim (Opcional):</label>
            <input type="date" id="addRecorrenciaDataFim" name="data_fim_cobranca">

            <button type="submit" class="submit">Adicionar Recorrência</button>
        </form>
        <div id="addRecorrenciaMessage" class="mensagem"></div>

        <h3>Recorrências Ativas do Cliente</h3>
        <div id="recorrenciasList">
            <p>Carregando recorrências...</p>
        </div>
    </div>

    <!-- Modal de Registrar Pagamento -->
    <div id="modalRegistrarPagamento" title="Registrar Pagamento" style="display: none;">
        <form id="formRegistrarPagamento">
            <input type="hidden" id="pagamentoFaturaId" name="id_fatura">
            <p>Fatura: <strong id="pagamentoFaturaIdDisplay"></strong></p>
            <p>Cliente: <strong id="pagamentoClienteNomeDisplay"></strong></p>
            <p>Total da Fatura: R$ <strong id="pagamentoFaturaTotalDisplay"></strong></p>
            <p>Total Pago: R$ <strong id="pagamentoFaturaTotalPagoDisplay"></strong></p>
            <p>Saldo Devedor: R$ <strong id="pagamentoFaturaSaldoDevedorDisplay" class="saldo-devedor-fatura-display"></strong></p>

            <label for="valorPagamento">Valor do Pagamento (R$):</label>
            <input type="number" id="valorPagamento" name="valor_pago" step="0.01" min="0.00" required>

            <label for="dataPagamento">Data do Pagamento:</label>
            <input type="date" id="dataPagamento" name="data_pagamento" value="<?= date('Y-m-d') ?>" required>

            <label for="statusPagamento">Status do Pagamento:</label>
            <select id="statusPagamento" name="status_pagamento" required>
                <option value="Confirmado">Confirmado</option>
                <option value="Pendente">Pendente</option>
                <option value="Cancelado">Cancelado</option>
            </select>

            <label for="observacaoPagamento">Observação (Opcional):</label>
            <textarea id="observacaoPagamento" name="observacao" rows="3" maxlength="255"></textarea>

            <h3>Itens da Fatura para Pagamento</h3>
            <div id="itensPagosSelection">
                <!-- Tabela de itens da fatura com checkboxes será carregada aqui -->
            </div>
            <input type="hidden" id="itensPagosJson" name="itens_pagos_json">
            <small>Os itens selecionados acima serão convertidos para JSON Base64 automaticamente.</small>

            <button type="submit" class="submit">Registrar Pagamento</button>
        </form>
        <div id="pagamentoMessage" class="mensagem"></div>
    </div>


    <script>
        $(document).ready(function() {
            // Configuração dos modais com jQuery UI
            $("#modalCadastrarCliente").dialog({
                autoOpen: false,
                modal: true,
                width: 400,
                buttons: {
                    "Fechar": function() { $(this).dialog("close"); }
                }
            });

            $("#modalEditarCliente").dialog({ 
                autoOpen: false,
                modal: true,
                width: 400,
                buttons: {
                    "Fechar": function() { $(this).dialog("close"); }
                }
            });

            $("#modalCadastrarServico").dialog({
                autoOpen: false,
                modal: true,
                width: 400,
                buttons: {
                    "Fechar": function() { $(this).dialog("close"); }
                }
            });

            $("#modalEditarServico").dialog({ 
                autoOpen: false,
                modal: true,
                width: 400,
                buttons: {
                    "Fechar": function() { $(this).dialog("close"); }
                }
            });

            $("#modalCriarFatura").dialog({
                autoOpen: false,
                modal: true,
                width: 450,
                buttons: {
                    "Fechar": function() { $(this).dialog("close"); }
                }
            });
            
            $("#modalFaturaDetalhes").dialog({
                autoOpen: false,
                modal: true,
                width: 700, // Largura padrão
                buttons: {
                    "Fechar": function() { $(this).dialog("close"); }
                }
            });

            $("#modalEditarItemFatura").dialog({
                autoOpen: false,
                modal: true,
                width: 450,
                buttons: {
                    "Fechar": function() { $(this).dialog("close"); }
                }
            });

            $("#modalVincularRecorrencia").dialog({
                autoOpen: false,
                modal: true,
                width: 700,
                buttons: {
                    "Fechar": function() { $(this).dialog("close"); }
                }
            });

            // NOVO MODAL: Registrar Pagamento
            $("#modalRegistrarPagamento").dialog({
                autoOpen: false,
                modal: true,
                width: 500,
                buttons: {
                    "Fechar": function() { $(this).dialog("close"); }
                }
            });


            // Botões de Abertura de Modal
            $("#btnCadastrarCliente").on("click", function() {
                $("#formCadastrarCliente")[0].reset(); // Limpa o formulário
                $("#clienteMessage").text(""); // Limpa mensagens
                $("#modalCadastrarCliente").dialog("open");
            });

            $("#btnCadastrarServico").on("click", function() {
                $("#formCadastrarServico")[0].reset(); // Limpa o formulário
                $("#servicoMessage").text(""); // Limpa mensagens
                $("#modalCadastrarServico").dialog("open");
            });

            // SANITIZAÇÃO CPF/CNPJ: Garante que apenas números sejam digitados
            $("#clienteCpfCnpj, #editClienteCpfCnpj").on("keyup", function() { // Aplicado a ambos os campos
                let value = $(this).val();
                $(this).val(value.replace(/[^0-9]/g, '')); // Remove tudo que não for número
            });


            // Formulário de Cadastro de Cliente (AJAX)
            $("#formCadastrarCliente").on("submit", function(e) {
                e.preventDefault();
                const formData = $(this).serialize() + "&action=criar_cliente";
                $("#clienteMessage").text("Cadastrando...");

                $.ajax({
                    url: 'app.php', 
                    type: 'POST',
                    data: formData,
                    dataType: 'json',
                    success: function(response) {
                        $("#clienteMessage").text(response.message);
                        if (response.success) {
                            $("#formCadastrarCliente")[0].reset();
                            // Opcional: Atualizar a lista de clientes no autocomplete
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        $("#clienteMessage").text("Erro de comunicação com o servidor: " + textStatus);
                        console.error("AJAX Error (criar_cliente):", textStatus, errorThrown, jqXHR);
                    }
                });
            });

            // Lógica para o botão EDITAR CLIENTE
            $("#btnEditarCliente").on("click", function() {
                const clientId = $("#selectedClientId").val();
                if (clientId) {
                    $.ajax({
                        url: 'app.php', 
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            action: 'get_cliente_details', 
                            id_cliente: clientId
                        },
                        success: function(response) {
                            if (response.success && response.data) {
                                $("#editClienteId").val(response.data.id_cliente); 
                                $("#editClienteNome").val(response.data.nome);
                                $("#editClienteCpfCnpj").val(response.data.cpf_cnpj);
                                $("#editClienteTelefone").val(response.data.telefone);
                                $("#editClienteEmail").val(response.data.email);
                                $("#editClienteMessage").text("");
                                $("#modalEditarCliente").dialog("open");
                            } else {
                                alert("Erro ao carregar dados do cliente para edição: " + response.message);
                            }
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            alert("Erro de comunicação ao carregar dados do cliente: " + textStatus);
                            console.error("AJAX Error (get_cliente_details):", textStatus, errorThrown, jqXHR);
                        }
                    });

                } else {
                    alert("Por favor, selecione um cliente para editar.");
                }
            });

            // Formulário de Edição de Cliente (AJAX)
            $("#formEditarCliente").on("submit", function(e) {
                e.preventDefault();
                const formData = $(this).serialize() + "&action=editar_cliente";
                $("#editClienteMessage").text("Salvando alterações...");

                $.ajax({
                    url: 'app.php', 
                    type: 'POST',
                    data: formData,
                    dataType: 'json',
                    success: function(response) {
                        $("#editClienteMessage").text(response.message);
                        if (response.success) {
                            $("#modalEditarCliente").dialog("close");
                            $("#selectedClientName").text($("#editClienteNome").val());
                            $("#selectedClientCpfCnpj").text($("#editClienteCpfCnpj").val());
                            loadClientFaturas($("#selectedClientId").val()); 
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        $("#clienteMessage").text("Erro de comunicação com o servidor: " + textStatus);
                        console.error("AJAX Error (editar_cliente):", textStatus, errorThrown, jqXHR);
                    }
                });
            });


            // Formulário de Cadastro de Serviço (AJAX)
            $("#formCadastrarServico").on("submit", function(e) {
                e.preventDefault();
                const formData = $(this).serialize() + "&action=criar_servico";
                $("#servicoMessage").text("Cadastrando...");

                $.ajax({
                    url: 'app.php', 
                    type: 'POST',
                    data: formData,
                    dataType: 'json',
                    success: function(response) {
                        $("#servicoMessage").text(response.message);
                        if (response.success) {
                            $("#formCadastrarServico")[0].reset();
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        $("#servicoMessage").text("Erro de comunicação com o servidor: " + textStatus);
                        console.error("AJAX Error (criar_servico):", textStatus, errorThrown, jqXHR);
                    }
                });
            });

            // AutoComplete para Buscar Serviço para Edição
            $("#servicoSearchEdit").autocomplete({
                source: function(request, response) {
                    $.ajax({
                        url: 'app.php', 
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            action: 'buscar_servicos', 
                            termo: request.term
                        },
                        success: function(data) {
                            if (data.success && data.data.length > 0) {
                                response($.map(data.data, function(item) {
                                    return {
                                        label: item.nome_servico + " (R$ " + parseFloat(item.valor_sugerido).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ")",
                                        value: item.nome_servico,
                                        id: item.id_servico,
                                        nome: item.nome_servico,
                                        valor_sugerido: item.valor_sugerido
                                    };
                                }));
                            } else {
                                response([{ label: "Nenhum serviço encontrado", value: "" }]);
                            }
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            console.error("AJAX Error (buscar_servicos para edicao):", textStatus, errorThrown, jqXHR);
                            response([{ label: "Erro na busca de serviços", value: "" }]);
                        }
                    });
                },
                minLength: 2,
                select: function(event, ui) {
                    const selectedServico = ui.item;
                    $("#selectedServicoIdEdit").val(selectedServico.id);
                    $("#btnEditarServico").prop('disabled', false); 
                    return false;
                }
            });

            // Lógica para o botão EDITAR SERVIÇO
            $("#btnEditarServico").on("click", function() {
                const servicoId = $("#selectedServicoIdEdit").val();
                if (servicoId) {
                    $.ajax({
                        url: 'app.php', 
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            action: 'get_servico_details', 
                            id_servico: servicoId
                        },
                        success: function(response) {
                            if (response.success && response.data) {
                                $("#editServicoId").val(response.data.id_servico);
                                $("#editServicoNome").val(response.data.nome_servico);
                                $("#editServicoValorSugerido").val(parseFloat(response.data.valor_sugerido).toFixed(2));
                                $("#editServicoMessage").text("");
                                $("#modalEditarServico").dialog("open");
                            } else {
                                alert("Erro ao carregar dados do serviço para edição: " + response.message);
                            }
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            alert("Erro de comunicação ao carregar dados do serviço: " + textStatus);
                            console.error("AJAX Error (get_servico_details):", textStatus, errorThrown, jqXHR);
                        }
                    });
                } else {
                    alert("Por favor, selecione um serviço para editar.");
                }
            });

            // Formulário de Edição de Serviço (AJAX)
            $("#formEditarServico").on("submit", function(e) {
                e.preventDefault();
                const formData = $(this).serialize() + "&action=editar_servico";
                $("#editServicoMessage").text("Salvando alterações...");

                $.ajax({
                    url: 'app.php', 
                    type: 'POST',
                    data: formData,
                    dataType: 'json',
                    success: function(response) {
                        $("#editServicoMessage").text(response.message);
                        if (response.success) {
                            $("#modalEditarServico").dialog("close");
                            $("#servicoSearchEdit").val(''); 
                            $("#btnEditarServico").prop('disabled', true); 
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        $("#editServicoMessage").text("Erro de comunicação com o servidor: " + textStatus);
                        console.error("AJAX Error (editar_servico):", textStatus, errorThrown, jqXHR);
                    }
                });
            });


            // AutoComplete para Busca de Clientes
            $("#clienteSearch").autocomplete({
                source: function(request, response) {
                    $.ajax({
                        url: 'app.php', 
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            action: 'buscar_clientes',
                            termo: request.term
                        },
                        success: function(data) {
                            if (data.success && data.data.length > 0) {
                                response($.map(data.data, function(item) {
                                    return {
                                        label: item.nome + " (" + item.cpf_cnpj + ")",
                                        value: item.nome, 
                                        id: item.id_cliente,
                                        nome: item.nome,
                                        cpf_cnpj: item.cpf_cnpj
                                    };
                                }));
                            } else {
                                response([{ label: "Nenhum cliente encontrado", value: "" }]);
                            }
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            console.error("AJAX Error (buscar_clientes):", textStatus, errorThrown, jqXHR);
                            response([{ label: "Erro na busca de clientes", value: "" }]);
                        }
                    });
                },
                minLength: 2, 
                select: function(event, ui) {
                    const selectedClient = ui.item;
                    $("#selectedClientId").val(selectedClient.id);
                    $("#selectedClientName").text(selectedClient.nome);
                    $("#selectedClientCpfCnpj").text(selectedClient.cpf_cnpj);
                    $("#clientDetailsSection").show();
                    loadClientFaturas(selectedClient.id); 
                    return false; 
                }
            });

            // Função para carregar faturas do cliente
            function loadClientFaturas(clientId) {
                $("#clientFaturasList").html("<p>Carregando faturas...</p>");
                $.ajax({
                    url: 'app.php', 
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'buscar_faturas_cliente',
                        id_cliente: clientId
                    },
                    success: function(response) {
                        console.log("Response from buscar_faturas_cliente:", response); 
                        if (response.success && response.data.length > 0) {
                            let faturaHtml = "<table><thead><tr><th>ID</th><th>Emissão</th><th>Vencimento</th><th>Total/Pendente</th><th>Status</th><th>Ações</th></tr></thead><tbody>"; // Título da coluna ajustado
                            response.data.forEach(fatura => {
                                const totalFatura = parseFloat(fatura.valor_total_fatura);
                                const totalPagoFatura = parseFloat(fatura.total_pago_fatura || 0);
                                const saldoDevedorFatura = totalFatura - totalPagoFatura;

                                let valorExibido;
                                if (fatura.status === 'Liquidada') {
                                    valorExibido = totalFatura.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
                                } else {
                                    valorExibido = saldoDevedorFatura.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
                                }

                                faturaHtml += `
                                    <tr>
                                        <td>${fatura.id_fatura}</td>
                                        <td>${new Date(fatura.data_emissao).toLocaleDateString('pt-BR')}</td>
                                        <td>${new Date(fatura.data_vencimento).toLocaleDateString('pt-BR')}</td>
                                        <td>${valorExibido}</td> <!-- Exibe o valor ajustado -->
                                        <td>${fatura.status}</td>
                                        <td class="action-buttons">
                                            <button class="btn-ver-fatura primary" data-id-fatura="${fatura.id_fatura}">Ver Detalhes</button>
                                            <button class="btn-registrar-pagamento secondary" data-id-fatura="${fatura.id_fatura}" 
                                                    data-cliente-nome="${$("#selectedClientName").text()}"
                                                    data-fatura-total="${fatura.valor_total_fatura}">Registrar Pagamento</button>
                                        </td>
                                    </tr>
                                `;
                            });
                            faturaHtml += "</tbody></table>";
                            $("#clientFaturasList").html(faturaHtml);
                        } else {
                            $("#clientFaturasList").html("<p>Nenhuma fatura encontrada para este cliente.</p>");
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        $("#clientFaturasList").html("<p style='color: red;'>Erro ao carregar faturas. Verifique o console para mais informações.</p>");
                        console.error("AJAX Error (buscar_faturas_cliente):", textStatus, errorThrown, jqXHR);
                    }
                });
            }

            // Botão Criar Nova Fatura
            $("#btnCriarFatura").on("click", function() {
                const clientId = $("#selectedClientId").val();
                const clientName = $("#selectedClientName").text();
                if (clientId) {
                    $("#faturaClienteIdInput").val(clientId);
                    $("#faturaClienteNomeDisplay").text(clientName);
                    $("#faturaDataEmissao").val('<?= date('Y-m-d') ?>'); 
                    $("#faturaDataVencimento").val(''); 
                    $("#faturaMessage").text("");
                    $("#modalCriarFatura").dialog("open");
                } else {
                    alert("Por favor, selecione um cliente primeiro.");
                }
            });

            // Formulário de Criar Fatura (AJAX)
            $("#formCriarFatura").on("submit", function(e) {
                e.preventDefault();
                const formData = $(this).serialize() + "&action=criar_fatura";
                $("#faturaMessage").text("Criando fatura...");

                $.ajax({
                    url: 'app.php', 
                    type: 'POST',
                    data: formData,
                    dataType: 'json',
                    success: function(response) {
                        $("#faturaMessage").text(response.message);
                        if (response.success) {
                            $("#formCriarFatura")[0].reset();
                            $("#modalCriarFatura").dialog("close");
                            openFaturaDetalhesModal(response.id_fatura);
                            loadClientFaturas($("#selectedClientId").val()); 
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        $("#faturaMessage").text("Erro de comunicação com o servidor: " + textStatus);
                        console.error("AJAX Error (criar_fatura):", textStatus, errorThrown, jqXHR);
                    }
                });
            });

            // Abrir Modal de Detalhes da Fatura
            $(document).on("click", ".btn-ver-fatura", function() {
                const faturaId = $(this).data("id-fatura");
                openFaturaDetalhesModal(faturaId);
            });

            // Função para preencher e abrir o modal de detalhes da fatura
            function openFaturaDetalhesModal(faturaId) {
                // Ajusta a largura do modal antes de abrir
                const newWidth = Math.min($(window).width() * 0.9, 1200);
                $("#modalFaturaDetalhes").dialog("option", "width", newWidth);

                $("#modalFaturaDetalhes").dialog("open");
                // RECRIAÇÃO DO HEADER COM OS DADOS DA FATURA E BOTÕES COM DATA ATTRIBUTES
                $("#faturaDetalhesHeader").html(`
                    <p><strong>Fatura ID:</strong> <span id="detalheFaturaId"></span></p>
                    <p><strong>Cliente:</strong> <span id="detalheFaturaCliente"></span></p>
                    <p><strong>Emissão:</strong> <span id="detalheFaturaEmissao"></span> | <strong>Vencimento:</strong> <span id="detalheFaturaVencimento"></span></p>
                    <p><strong>Status:</strong> <span id="detalheFaturaStatus"></span> | <strong>Total:</strong> R$ <span id="detalheFaturaTotal"></span></p>
                    <p><strong>Total Pago:</strong> R$ <span id="detalheFaturaTotalPago">0,00</span></p>
                    <p><strong>Saldo Devedor:</strong> R$ <span id="detalheFaturaSaldoDevedor">0,00</span></p>
                    <button id="btnIncorporarRecorrencias" class="secondary" style="margin-top: 10px;">Incorporar Recorrências do Mês</button>
                    <button class="primary btn-registrar-pagamento" style="margin-top: 10px; margin-left: 10px;"
                            data-id-fatura="${faturaId}" 
                            data-cliente-id="${$("#selectedClientId").val()}" <!-- Passa o ID do cliente selecionado -->
                    >Registrar Pagamento</button> <!-- AGORA COM DATA ATTRIBUTES -->
                `); 
                $("#itensFaturaList tbody").html("<tr><td colspan='6'>Carregando itens...</td></tr>"); 
                $("#pagamentosList tbody").html("<tr><td colspan='6'>Carregando pagamentos...</td></tr>"); 
                $("#noPaymentsMessage").hide(); 
                $("#itemFaturaMessage").text("");
                $("#formAdicionarItemFatura")[0].reset();
                $("#itemServicoSearch").val(''); 
                $("#itemServicoSelectedId").val(''); 
                $("#itemValorUnitario").val(''); 
                $("#itemTag").val(''); 

                // Carrega detalhes da fatura e pagamentos
                $.ajax({
                    url: 'app.php', 
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'get_fatura_detalhes',
                        id_fatura: faturaId
                    },
                    success: function(response) {
                        console.log("Response from get_fatura_detalhes:", response); 
                        if (response.success && response.data.fatura) {
                            const fatura = response.data.fatura;
                            $("#detalheFaturaId").text(fatura.id_fatura);
                            $("#detalheFaturaCliente").text(fatura.nome_cliente);
                            $("#detalheFaturaEmissao").text(new Date(fatura.data_emissao).toLocaleDateString('pt-BR'));
                            $("#detalheFaturaVencimento").text(new Date(fatura.data_vencimento).toLocaleDateString('pt-BR'));
                            $("#detalheFaturaStatus").text(fatura.status);
                            $("#detalheFaturaTotal").text(parseFloat(fatura.valor_total_fatura).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }));
                            
                            const totalPago = parseFloat(fatura.total_pago || 0);
                            const saldoDevedor = parseFloat(fatura.valor_total_fatura) - totalPago; 
                            $("#detalheFaturaTotalPago").text(totalPago.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }));
                            $("#detalheFaturaSaldoDevedor").text(saldoDevedor.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }));

                            populateItensFaturaTable(faturaId, response.data.itens); 
                            populatePagamentosTable(faturaId, response.data.pagamentos); 
                        } else {
                            $("#faturaDetalhesHeader").append("<p style='color: red;'>" + response.message + "</p>");
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        console.error("AJAX Error (get_fatura_detalhes):", textStatus, errorThrown, jqXHR); 
                        $("#faturaDetalhesHeader").append("<p style='color: red;'>Erro ao carregar detalhes da fatura. Verifique o console para mais informações.</p>");
                    }
                });

                // AutoComplete para buscar serviços no modal de adição de item
                $("#itemServicoSearch").autocomplete({
                    source: function(request, response) {
                        $.ajax({
                            url: 'app.php', 
                            type: 'POST',
                            dataType: 'json',
                            data: {
                                action: 'buscar_servicos', 
                                termo: request.term
                            },
                            success: function(data) {
                                if (data.success && data.data.length > 0) {
                                    response($.map(data.data, function(item) {
                                        return {
                                            label: item.nome_servico + " (R$ " + parseFloat(item.valor_sugerido).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ")",
                                            value: item.nome_servico, 
                                            id: item.id_servico,
                                            valor_sugerido: item.valor_sugerido
                                        };
                                    }));
                                } else {
                                    response([{ label: "Nenhum serviço encontrado", value: "" }]);
                                }
                            }
                        });
                    },
                    minLength: 2,
                    select: function(event, ui) {
                        const selectedServico = ui.item;
                        $("#itemServicoSelectedId").val(selectedServico.id); 
                        $("#itemValorUnitario").val(parseFloat(selectedServico.valor_sugerido).toFixed(2)); 
                        return false; 
                    }
                });
            }

            // Popula a tabela de itens da fatura
            function populateItensFaturaTable(faturaId, itens) {
                let tbodyHtml = '';
                let totalItensCalculated = 0;
                if (itens && itens.length > 0) {
                    itens.forEach(item => {
                        const subtotal = parseFloat(item.quantidade) * parseFloat(item.valor_unitario);
                        totalItensCalculated += subtotal;
                        const isRecorrente = item.tag && item.tag.includes('Recorrência'); 
                        const recorrenteIcon = isRecorrente ? '<span class="recorrente-icon" title="Serviço Recorrente">&#x1F504;</span>' : ''; 
                        tbodyHtml += `
                            <tr>
                                <td>${item.nome_servico} ${recorrenteIcon}</td>
                                <td>${item.quantidade}</td>
                                <td>R$ ${parseFloat(item.valor_unitario).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                                <td>R$ ${subtotal.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                                <td>${item.tag ? item.tag : ''}</td> 
                                <td class="item-actions">
                                    <button class="btn-edit" data-id-item="${item.id_item_fatura}" 
                                            data-id-fatura="${faturaId}" 
                                            data-servico-nome="${item.nome_servico}"
                                            data-quantidade="${item.quantidade}" 
                                            data-valor-unitario="${item.valor_unitario}"
                                            data-tag="${item.tag ? item.tag : ''}">Editar</button>
                                    <button class="btn-remove" data-id-item="${item.id_item_fatura}" 
                                            data-id-fatura="${faturaId}">Remover</button>
                                </td>
                            </tr>
                        `;
                    });
                    tbodyHtml += `
                        <tr>
                            <td colspan="4" style="text-align: right; font-weight: bold;">Total dos Itens:</td>
                            <td colspan="2" style="font-weight: bold;">R$ ${totalItensCalculated.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                        </tr>
                    `;
                } else {
                    tbodyHtml = '<tr><td colspan="6">Nenhum item adicionado a esta fatura ainda.</td></tr>'; 
                }
                $("#faturaItensTable tbody").html(tbodyHtml);
            }

            // Popula a tabela de pagamentos
            function populatePagamentosTable(faturaId, pagamentos) { // Recebe faturaId
                let tbodyHtml = '';
                if (pagamentos && pagamentos.length > 0) {
                    pagamentos.forEach(pagamento => {
                        const valor = parseFloat(pagamento.valor_pago).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
                        const data = new Date(pagamento.data_pagamento).toLocaleDateString('pt-BR');
                        const statusClass = `pagamento-status-${pagamento.status_pagamento}`;
                        const estornarButton = (pagamento.status_pagamento === 'Confirmado' || pagamento.status_pagamento === 'Pendente') ? 
                            `<button class="btn-estornar-pagamento secondary" data-id-pagamento="${pagamento.id_pagamento}" data-id-fatura="${faturaId}">Estornar</button>` : '';

                        tbodyHtml += `
                            <tr>
                                <td>${pagamento.id_pagamento}</td>
                                <td>${valor}</td>
                                <td>${data}</td>
                                <td class="${statusClass}">${pagamento.status_pagamento}</td>
                                <td>${pagamento.observacao ? pagamento.observacao : ''}</td>
                                <td>${estornarButton}</td> <!-- Botão Estornar -->
                            </tr>
                        `;
                    });
                    $("#faturaPagamentosTable tbody").html(tbodyHtml);
                    $("#noPaymentsMessage").hide();
                } else {
                    $("#faturaPagamentosTable tbody").html(''); 
                    $("#noPaymentsMessage").show(); 
                }
            }

            // Função para popular os itens selecionáveis no modal de pagamento
            function populateSelectableItemsForPayment(items) {
                let tableHtml = '<table><thead><tr><th></th><th>Serviço</th><th>Qtd</th><th>Valor Unit.</th><th>Subtotal</th></tr></thead><tbody>';
                if (items && items.length > 0) {
                    items.forEach(item => {
                        const subtotal = parseFloat(item.quantidade) * parseFloat(item.valor_unitario);
                        tableHtml += `
                            <tr class="item-pagamento-checkbox-row">
                                <td><input type="checkbox" class="item-pagamento-checkbox" checked 
                                           data-item-id="${item.id_item_fatura}" 
                                           data-item-value="${subtotal.toFixed(2)}"></td>
                                <td>${item.nome_servico}</td>
                                <td>${item.quantidade}</td>
                                <td>R$ ${parseFloat(item.valor_unitario).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                                <td>R$ ${subtotal.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                            </tr>
                        `;
                    });
                } else {
                    tableHtml += '<tr><td colspan="5">Nenhum item na fatura.</td></tr>';
                }
                tableHtml += '</tbody></table>';
                $("#itensPagosSelection").html(tableHtml);

                // Dispara a atualização do JSON Base64 inicial e do valor do pagamento
                updateItensPagosJson();
                updateValorPagamento(); 
            }

            // Função para atualizar o campo de JSON Base64 dos itens pagos
            function updateItensPagosJson() {
                const selectedItems = [];
                $('#itensPagosSelection .item-pagamento-checkbox:checked').each(function() {
                    selectedItems.push({
                        id_item: $(this).data('item-id'),
                        valor: parseFloat($(this).data('item-value'))
                    });
                });
                const jsonString = JSON.stringify(selectedItems);
                const base64Encoded = btoa(jsonString); // Converte para Base64
                $("#itensPagosJson").val(base64Encoded);
            }

            // Função para atualizar o valor do campo de pagamento
            function updateValorPagamento() {
                let totalSelecionado = 0;
                $('#itensPagosSelection .item-pagamento-checkbox:checked').each(function() {
                    const itemValue = parseFloat($(this).data('item-value'));
                    if (!isNaN(itemValue)) { // Garante que é um número
                        totalSelecionado += itemValue;
                    } else {
                        console.warn("Item com valor NaN encontrado:", $(this).data('item-value'));
                    }
                });
                console.log("Total selecionado dos itens:", totalSelecionado);
                
                // Pega o saldo devedor atual da fatura do display
                // Certifique-se de que o texto do span é limpo corretamente antes de parsear
                const saldoDevedorText = $("#detalheFaturaSaldoDevedor").text();
                const cleanedSaldoDevedorText = saldoDevedorText.replace('R$ ', '').replace(/\./g, '').replace(',', '.');
                const saldoDevedorFaturaDisplay = parseFloat(cleanedSaldoDevedorText);

                console.log("Saldo Devedor do Display (texto original):", saldoDevedorText);
                console.log("Saldo Devedor do Display (texto limpo):", cleanedSaldoDevedorText);
                console.log("Saldo Devedor do Display (parseado):", saldoDevedorFaturaDisplay);

                if (isNaN(saldoDevedorFaturaDisplay)) {
                    console.error("Erro: Saldo Devedor parseado resultou em NaN. O cálculo do valor do pagamento pode estar incorreto.");
                    // Fallback: Se o saldo devedor não puder ser parseado, apenas use o total dos itens selecionados.
                    // Isso evita que o campo de valor do pagamento também seja NaN.
                    $("#valorPagamento").val(totalSelecionado.toFixed(2));
                    return;
                }

                // O valor do pagamento não deve exceder o saldo devedor da fatura
                const valorFinalPagamento = Math.max(0, Math.min(totalSelecionado, saldoDevedorFaturaDisplay));
                
                $("#valorPagamento").val(valorFinalPagamento.toFixed(2));
            }

            // Event listener para checkboxes de itens de pagamento
            $(document).on('change', '.item-pagamento-checkbox', function() {
                updateItensPagosJson();
                updateValorPagamento(); 
            });

            // Adiciona/remove classe 'selected' na linha do checkbox
            $(document).on('change', '.item-pagamento-checkbox', function() {
                if ($(this).is(':checked')) {
                    $(this).closest('tr').addClass('selected');
                } else {
                    $(this).closest('tr').removeClass('selected');
                }
            });
            // Inicializa a classe 'selected' ao popular a tabela
            $(document).on('populateSelectableItemsForPayment', function() {
                $('#itensPagosSelection .item-pagamento-checkbox:checked').closest('tr').addClass('selected');
            });


            // Formulário de Adicionar Item à Fatura (AJAX)
            $("#formAdicionarItemFatura").on("submit", function(e) {
                e.preventDefault();
                if (!$("#itemServicoSelectedId").val()) {
                    $("#itemFaturaMessage").text("Por favor, selecione um serviço válido na lista.");
                    return;
                }

                const formData = $(this).serialize() + "&action=adicionar_item_fatura";
                $("#itemFaturaMessage").text("Adicionando item...");

                $.ajax({
                    url: 'app.php', 
                    type: 'POST',
                    data: formData,
                    dataType: 'json',
                    success: function(response) {
                        $("#itemFaturaMessage").text(response.message);
                        if (response.success) {
                            $("#formAdicionarItemFatura")[0].reset();
                            $("#itemServicoSearch").val(''); 
                            $("#itemServicoSelectedId").val(''); 
                            $("#itemValorUnitario").val(''); 
                            $("#itemTag").val(''); 
                            openFaturaDetalhesModal($("#itemFaturaId").val());
                            loadClientFaturas($("#selectedClientId").val()); 
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        $("#itemFaturaMessage").text("Erro de comunicação ao adicionar item: " + textStatus);
                        console.error("AJAX Error (adicionar_item_fatura):", textStatus, errorThrown, jqXHR);
                    }
                });
            });

            // Lógica para o botão EDITAR item da fatura
            $(document).on("click", ".btn-edit", function() {
                const itemId = $(this).data("id-item");
                const faturaId = $(this).data("id-fatura");
                const servicoNome = $(this).data("servico-nome");
                const quantidade = $(this).data("quantidade");
                const valorUnitario = $(this).data("valor-unitario");
                const tag = $(this).data("tag"); 

                $("#editItemFaturaId").val(faturaId);
                $("#editItemId").val(itemId);
                $("#editServicoNomeDisplay").text(servicoNome);
                $("#editQuantidade").val(quantidade);
                $("#editValorUnitario").val(parseFloat(valorUnitario).toFixed(2));
                $("#editTag").val(tag); 
                $("#editItemMessage").text("");

                $("#modalEditarItemFatura").dialog("open");
            });

            // Formulário de Edição de Item da Fatura (AJAX)
            $(document).on("submit", "#formEditarItemFatura", function(e) { 
                e.preventDefault();
                const formData = $(this).serialize() + "&action=editar_item_fatura";
                $("#editItemMessage").text("Salvando edição...");

                $.ajax({
                    url: 'app.php', 
                    type: 'POST',
                    data: formData,
                    dataType: 'json',
                    success: function(response) {
                        $("#editItemMessage").text(response.message);
                        if (response.success) {
                            $("#modalEditarItemFatura").dialog("close");
                            openFaturaDetalhesModal($("#editItemFaturaId").val());
                            loadClientFaturas($("#selectedClientId").val()); 
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        $("#editItemMessage").text("Erro de comunicação ao editar item: " + textStatus);
                        console.error("AJAX Error (editar_item_fatura):", textStatus, errorThrown, jqXHR);
                    }
                });
            });

            // Lógica para o botão REMOVER item da fatura
            $(document).on("click", ".btn-remove", function() {
                const itemId = $(this).data("id-item");
                const faturaId = $(this).data("id-fatura");

                if (confirm("Tem certeza que deseja remover este item da fatura?")) {
                    $.ajax({
                        url: 'app.php', 
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            action: 'remover_item_fatura',
                            id_item_fatura: itemId,
                            id_fatura: faturaId 
                        },
                        success: function(response) {
                            alert(response.message); 
                            if (response.success) {
                                openFaturaDetalhesModal(faturaId);
                                loadClientFaturas($("#selectedClientId").val()); 
                            }
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            alert("Erro de comunicação ao remover item: " + textStatus);
                            console.error("AJAX Error (remover_item_fatura):", textStatus, errorThrown, jqXHR);
                        }
                    });
                }
            });

            // Lógica para o botão VINCULAR RECORRÊNCIA
            $("#btnVincularRecorrencia").on("click", function() {
                const clientId = $("#selectedClientId").val();
                const clientName = $("#selectedClientName").text();
                if (clientId) {
                    $("#recorrenciaClienteId").val(clientId);
                    $("#recorrenciaClienteNomeDisplay").text(clientName);
                    $("#addRecorrenciaClienteId").val(clientId); 
                    $("#formAdicionarRecorrencia")[0].reset();
                    $("#addRecorrenciaMessage").text("");
                    $("#addRecorrenciaServicoSearch").val(''); 
                    $("#addRecorrenciaServicoSelectedId").val(''); 
                    $("#addRecorrenciaValorSugerido").val(''); 

                    loadClienteRecorrencias(clientId); 
                    $("#modalVincularRecorrencia").dialog("open");
                } else {
                    alert("Por favor, selecione um cliente para vincular recorrências.");
                }
            });

            // AutoComplete para buscar serviços no modal de Vincular Recorrência
            $("#addRecorrenciaServicoSearch").autocomplete({
                source: function(request, response) {
                    $.ajax({
                        url: 'app.php', 
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            action: 'buscar_servicos', 
                            termo: request.term
                        },
                        success: function(data) {
                            if (data.success && data.data.length > 0) {
                                response($.map(data.data, function(item) {
                                    return {
                                        label: item.nome_servico + " (R$ " + parseFloat(item.valor_sugerido).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ")",
                                        value: item.nome_servico, 
                                        id: item.id_servico,
                                        valor_sugerido: item.valor_sugerido
                                    };
                                }));
                            } else {
                                response([{ label: "Nenhum serviço encontrado", value: "" }]);
                            }
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            console.error("AJAX Error (buscar_servicos para edicao):", textStatus, errorThrown, jqXHR);
                            response([{ label: "Erro na busca de serviços", value: "" }]);
                        }
                    });
                },
                minLength: 2,
                select: function(event, ui) {
                    const selectedServico = ui.item;
                    $("#addRecorrenciaServicoSelectedId").val(selectedServico.id); 
                    $("#addRecorrenciaValorSugerido").val(parseFloat(selectedServico.valor_sugerido).toFixed(2)); 
                    return false; 
                }
            });

            // Formulário para Adicionar Recorrência
            $("#formAdicionarRecorrencia").on("submit", function(e) {
                e.preventDefault();
                if (!$("#addRecorrenciaServicoSelectedId").val()) {
                    $("#addRecorrenciaMessage").text("Por favor, selecione um serviço válido na lista.");
                    return;
                }
                const formData = $(this).serialize() + "&action=vincular_recorrencia";
                $("#addRecorrenciaMessage").text("Adicionando recorrência...");

                $.ajax({
                    url: 'app.php', 
                    type: 'POST',
                    data: formData,
                    dataType: 'json',
                    success: function(response) {
                        $("#addRecorrenciaMessage").text(response.message);
                        if (response.success) {
                            $("#formAdicionarRecorrencia")[0].reset();
                            $("#addRecorrenciaServicoSearch").val('');
                            $("#addRecorrenciaServicoSelectedId").val('');
                            $("#addRecorrenciaValorSugerido").val('');
                            loadClienteRecorrencias($("#recorrenciaClienteId").val()); 
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        $("#addRecorrenciaMessage").text("Erro de comunicação: " + textStatus);
                        console.error("AJAX Error (vincular_recorrencia):", textStatus, errorThrown, jqXHR);
                    }
                });
            });

            // Função para carregar e exibir recorrências do cliente
            function loadClienteRecorrencias(clientId) {
                $("#recorrenciasList").html("<p>Carregando recorrências...</p>");
                $.ajax({
                    url: 'app.php', 
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'get_cliente_recorrencias',
                        id_cliente: clientId
                    },
                    success: function(response) {
                        console.log("Recorrências do cliente:", response);
                        if (response.success && response.data.length > 0) {
                            let recorrenciaHtml = "<table><thead><tr><th>Serviço</th><th>Qtd</th><th>Valor Sug.</th><th>Período</th><th>Início</th><th>Fim</th><th>Ações</th></tr></thead><tbody>";
                            response.data.forEach(rec => {
                                const valor = parseFloat(rec.valor_sugerido_recorrencia).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                                const dataInicio = new Date(rec.data_inicio_cobranca).toLocaleDateString('pt-BR');
                                const dataFim = rec.data_fim_cobranca ? new Date(rec.data_fim_cobranca).toLocaleDateString('pt-BR') : 'Sem fim';
                                recorrenciaHtml += `
                                    <tr>
                                        <td>${rec.nome_servico}</td>
                                        <td>${rec.quantidade}</td>
                                        <td>${valor}</td>
                                        <td>${rec.intervalo} ${rec.tipo_periodo}</td>
                                        <td>${dataInicio}</td>
                                        <td>${dataFim}</td>
                                        <td><button class="btn-remove-recorrencia secondary" data-id-recorrencia="${rec.id_recorrencia}" data-id-cliente="${clientId}">Remover</button></td>
                                    </tr>
                                `;
                            });
                            recorrenciaHtml += "</tbody></table>";
                            $("#recorrenciasList").html(recorrenciaHtml);
                        } else {
                            $("#recorrenciasList").html("<p>Nenhuma recorrência vinculada a este cliente.</p>");
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        $("#recorrenciasList").html("<p style='color: red;'>Erro ao carregar recorrências.</p>");
                        console.error("AJAX Error (get_cliente_recorrencias):", textStatus, errorThrown, jqXHR);
                    }
                });
            }

            // Lógica para remover recorrência
            $(document).on("click", ".btn-remove-recorrencia", function() {
                const recorrenciaId = $(this).data("id-recorrencia");
                const clientId = $(this).data("id-cliente");
                if (confirm("Tem certeza que deseja remover esta recorrência?")) {
                    $.ajax({
                        url: 'app.php',
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            action: 'remover_recorrencia',
                            id_recorrencia: recorrenciaId
                        },
                        success: function(response) {
                            alert(response.message);
                            if (response.success) {
                                loadClienteRecorrencias(clientId); 
                            }
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            alert("Erro ao remover recorrência: " + textStatus);
                            console.error("AJAX Error (remover_recorrencia):", textStatus, errorThrown, jqXHR);
                        }
                    });
                }
            });

            // Lógica para o botão INCORPORAR RECORRÊNCIAS DO MÊS
            $(document).on("click", "#btnIncorporarRecorrencias", function() {
                const faturaId = $("#detalheFaturaId").text();
                const clienteId = $("#selectedClientId").val(); 
                const dataVencimentoFatura = $("#detalheFaturaVencimento").text(); 

                if (!faturaId || !clienteId || !dataVencimentoFatura) {
                    alert("Não foi possível identificar a fatura ou o cliente.");
                    return;
                }

                const partesData = dataVencimentoFatura.split('/');
                if (partesData.length !== 3) {
                    alert("Formato de data de vencimento inválido para incorporar recorrências.");
                    return;
                }
                const mesAnoVencimento = `${partesData[2]}-${partesData[1]}`; 

                if (confirm(`Deseja incorporar os serviços recorrentes do cliente para o mês de ${partesData[1]}/${partesData[2]} nesta fatura?`)) {
                    $.ajax({
                        url: 'app.php',
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            action: 'incorporar_recorrencias_na_fatura',
                            id_fatura: faturaId,
                            id_cliente: clienteId,
                            mes_ano_fatura: mesAnoVencimento
                        },
                        success: function(response) {
                            alert(response.message);
                            if (response.success) {
                                openFaturaDetalhesModal(faturaId); 
                                loadClientFaturas(clienteId); 
                            }
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            alert("Erro ao incorporar recorrências: " + textStatus);
                            console.error("AJAX Error (incorporar_recorrencias_na_fatura):", textStatus, errorThrown, jqXHR);
                        }
                    });
                }
            });

            // Lógica para o botão REGISTRAR PAGAMENTO (agora com seletor delegado)
            $(document).on("click", ".btn-registrar-pagamento", function() { // Seleção delegada
                const faturaId = $(this).data("id-fatura");
                const clienteId = $(this).data("cliente-id"); // Pega o ID do cliente do data attribute do botão

                // Carrega os detalhes da fatura para preencher o modal de pagamento
                $.ajax({
                    url: 'app.php',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'get_fatura_detalhes', 
                        id_fatura: faturaId
                    },
                    success: function(response) {
                        if (response.success && response.data.fatura) {
                            const fatura = response.data.fatura;
                            const totalPago = parseFloat(fatura.total_pago || 0);
                            const saldoDevedor = parseFloat(fatura.valor_total_fatura) - totalPago;

                            $("#pagamentoFaturaId").val(faturaId);
                            $("#pagamentoFaturaIdDisplay").text(faturaId);
                            $("#pagamentoClienteNomeDisplay").text(fatura.nome_cliente);
                            $("#pagamentoFaturaTotalDisplay").text(parseFloat(fatura.valor_total_fatura).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }));
                            $("#pagamentoFaturaTotalPagoDisplay").text(totalPago.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }));
                            $("#pagamentoFaturaSaldoDevedorDisplay").text(saldoDevedor.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }));
                            
                            $("#valorPagamento").val(saldoDevedor > 0 ? saldoDevedor.toFixed(2) : '0.00'); 
                            $("#dataPagamento").val('<?= date('Y-m-d') ?>'); 
                            $("#statusPagamento").val('Confirmado'); 
                            $("#observacaoPagamento").val('');
                            $("#itensPagosJson").val(''); // Limpa o campo hidden
                            $("#pagamentoMessage").text('');

                            // Popula os itens selecionáveis para pagamento, passando o saldo devedor
                            populateSelectableItemsForPayment(response.data.itens); // Removido saldoDevedor como parâmetro, pois a função já o busca do DOM

                            $("#modalRegistrarPagamento").dialog("open");
                        } else {
                            alert("Erro ao carregar detalhes da fatura para registro de pagamento: " + response.message);
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        alert("Erro de comunicação ao carregar detalhes da fatura para pagamento: " + textStatus);
                        console.error("AJAX Error (get_fatura_detalhes for payment):", textStatus, errorThrown, jqXHR);
                    }
                });
            });

            // Formulário de Registrar Pagamento (AJAX)
            $("#formRegistrarPagamento").on("submit", function(e) {
                e.preventDefault();
                // Garante que o JSON dos itens pagos esteja atualizado antes de enviar
                updateItensPagosJson(); 
                
                const formData = $(this).serialize() + "&action=registrar_pagamento";
                $("#pagamentoMessage").text("Registrando pagamento...");

                $.ajax({
                    url: 'app.php',
                    type: 'POST',
                    data: formData,
                    dataType: 'json',
                    success: function(response) {
                        $("#pagamentoMessage").text(response.message);
                        if (response.success) {
                            $("#modalRegistrarPagamento").dialog("close");
                            openFaturaDetalhesModal($("#pagamentoFaturaId").val());
                            loadClientFaturas($("#selectedClientId").val()); 
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        $("#pagamentoMessage").text("Erro de comunicação ao registrar pagamento: " + textStatus);
                        console.error("AJAX Error (registrar_pagamento):", textStatus, errorThrown, jqXHR);
                    }
                });
            });

            // Lógica para o botão ESTORNAR PAGAMENTO
            $(document).on("click", ".btn-estornar-pagamento", function() {
                const pagamentoId = $(this).data("id-pagamento");
                const faturaId = $(this).data("id-fatura");

                if (confirm(`Tem certeza que deseja estornar o pagamento ID ${pagamentoId} da fatura ID ${faturaId}?`)) {
                    $.ajax({
                        url: 'app.php',
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            action: 'estornar_pagamento',
                            id_pagamento: pagamentoId,
                            id_fatura: faturaId // Passa o ID da fatura para recalcular o total e status
                        },
                        success: function(response) {
                            alert(response.message);
                            if (response.success) {
                                openFaturaDetalhesModal(faturaId); // Recarrega os detalhes da fatura
                                loadClientFaturas($("#selectedClientId").val()); // Recarrega a lista de faturas do cliente
                            }
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            alert("Erro ao estornar pagamento: " + textStatus);
                            console.error("AJAX Error (estornar_pagamento):", textStatus, errorThrown, jqXHR);
                        }
                    });
                }
            });

        });
    </script>
</body>
</html>
