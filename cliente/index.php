<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Área do Cliente Dinovatech</title>
    <!-- Bibliotecas do Sistema Original -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.13.2/jquery-ui.min.js"></script>
    <link rel="stylesheet" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.13.2/themes/smoothness/jquery-ui.css">
    
    <!-- Bibliotecas para o Modal PIX -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/kjua@0.9.0/dist/kjua.min.js"></script>

    <style>
        /* Estilos Originais */
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f0f2f5; }
        .container { max-width: 800px; width: 100%; padding: 30px; background-color: #fff; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); margin: 20px auto; }
        h1, h2 { text-align: center; color: #333; margin-bottom: 25px; }
        #loginSection { background-color: #e9ecef; padding: 25px; border-radius: 8px; box-shadow: inset 0 1px 3px rgba(0,0,0,0.1); max-width: 400px; margin: 0 auto; }
        #loginSection label { display: block; margin-bottom: 8px; font-weight: bold; color: #555; }
        #loginSection input[type="text"] { width: calc(100% - 22px); padding: 12px; margin-bottom: 20px; border: 1px solid #ccc; border-radius: 5px; font-size: 1.1em; }
        #loginSection button { background-color: #007bff; color: white; padding: 12px 25px; border: none; border-radius: 5px; cursor: pointer; font-size: 1.1em; width: 100%; transition: background-color 0.3s ease; }
        #loginSection button:hover { background-color: #0056b3; }
        .message { text-align: center; margin-top: 15px; font-weight: bold; }
        .message.error { color: #dc3545; }
        .message.success { color: #28a745; }
        #faturasSection { display: none; margin-top: 30px; }
        #faturasHeader { background-color: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px; text-align: center; }
        #faturasHeader p { margin: 5px 0; font-size: 1.1em; }
        #faturasHeader button { background-color: #6c757d; color: white; padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer; font-size: 0.9em; margin-top: 10px; }
        #clientFaturasList table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        #clientFaturasList th, #clientFaturasList td { border: 1px solid #dee2e6; padding: 10px; text-align: left; }
        #clientFaturasList th { background-color: #e9ecef; font-weight: bold; }
        #clientFaturasList .action-buttons button { padding: 5px 10px; margin-right: 5px; font-size: 0.9em; background-color: #17a2b8; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .ui-dialog { z-index: 1000 !important; }
        #modalFaturaDetalhes .fatura-header-modal { background-color: #f9f9f9; padding: 10px; border-radius: 5px; margin-bottom: 20px; border: 1px dashed #ccc; }
        #modalFaturaDetalhes table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        #modalFaturaDetalhes th, #modalFaturaDetalhes td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .recorrente-icon { margin-left: 5px; font-size: 0.9em; color: #28a745; }

        /* Centralização do Modal jQuery UI */
        .ui-dialog { position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); }

        /* Estilos para o Modal PIX */
        #payment-modal { 
            display: none; 
            font-family: 'Inter', sans-serif;
            z-index: 1050; 
        }
        .spinner { border: 4px solid rgba(0, 0, 0, 0.1); width: 36px; height: 36px; border-radius: 50%; border-left-color: #06b6d4; animation: spin 1s ease infinite; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
</head>
<body>
    <div class="container">
        <h1>Área do Cliente</h1>
        <div id="loginSection">
            <h2>Acesse suas Faturas</h2>
            <form id="loginForm">
                <label for="cpfCnpjLogin">CPF/CNPJ:</label>
                <input type="text" id="cpfCnpjLogin" name="cpf_cnpj" placeholder="Digite seu CPF ou CNPJ" required>
                <div style="margin-bottom: 15px;">
                    <input type="checkbox" id="rememberMe" name="remember_me">
                    <label for="rememberMe" style="display: inline-block; margin-left: 5px; font-weight: normal;">Lembrar CPF/CNPJ</label>
                </div>
                <button type="submit">Entrar</button>
            </form>
            <div id="loginMessage" class="message"></div>
        </div>
        <div id="faturasSection">
            <div id="faturasHeader">
                <p>Bem-vindo(a), <strong id="loggedInClientName"></strong>!</p>
                <p>Aqui estão suas faturas:</p>
                <button id="btnLogout">Sair</button>
            </div>
            <div id="clientFaturasList">
                <p>Carregando suas faturas...</p>
            </div>
        </div>
    </div>

    <!-- Modal de Detalhes da Fatura -->
    <div id="modalFaturaDetalhes" title="Detalhes da Fatura" style="display: none;">
        <div class="fatura-header-modal">
            <p><strong>Fatura ID:</strong> <span id="detalheFaturaId"></span></p>
            <p><strong>Cliente:</strong> <span id="detalheFaturaCliente"></span></p>
            <p><strong>Emissão:</strong> <span id="detalheFaturaEmissao"></span> | <strong>Vencimento:</strong> <span id="detalheFaturaVencimento"></span></p>
            <p><strong>Status:</strong> <span id="detalheFaturaStatus"></span> | <strong>Total:</strong> <span id="detalheFaturaTotal"></span></p>
        </div>
        <h3>Itens da Fatura</h3>
        <div id="itensFaturaList">
            <table id="faturaItensTable">
                <thead><tr><th>Serviço</th><th>Qtd</th><th>Valor Unit.</th><th>Subtotal</th><th>Tag</th></tr></thead>
                <tbody></tbody>
            </table>
        </div>
        <h3 class="mt-6">Histórico de Pagamentos</h3>
        <div id="pagamentosList">
            <table id="faturaPagamentosTable">
                <thead><tr><th>Data</th><th>Valor Pago</th><th>Observação</th></tr></thead>
                <tbody></tbody>
            </table>
        </div>
        <div id="action-buttons-container" class="mt-6 text-center flex justify-center gap-4"></div>
    </div>

    <!-- Modal de Pagamento PIX -->
    <div id="payment-modal" class="fixed inset-0 bg-gray-800 bg-opacity-75 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl p-8 max-w-md w-full text-center">
            <div id="modal-loading"><div class="spinner mx-auto"></div><p class="mt-4 text-gray-700 font-medium">Gerando seu PIX, aguarde...</p></div>
            <div id="modal-payment" class="hidden">
                <h2 class="text-2xl font-bold text-gray-900 mb-2">Pague com PIX</h2>
                <p class="text-gray-600 mb-4">Aponte a câmera do seu celular para o QR Code ou use o "Copia e Cola".</p>
                <div id="qrcode" class="inline-block p-2 bg-white border rounded-lg shadow-sm"></div>
                
                <!-- ** NOVO: Div para exibir o TXID ** -->
                <div id="pixTxidContainer" class="mt-4 text-xs text-gray-500 break-all">
                    <strong>ID da Transação (txid):</strong><br>
                    <span id="pixTxid"></span>
                </div>

                <div class="mt-4">
                    <textarea id="pixCopiaECola" class="w-full p-2 border rounded-md text-xs bg-gray-100" rows="3" readonly></textarea>
                    <button id="btnCopiar" class="w-full mt-2 bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2 px-4 rounded-lg text-sm">Copiar Código</button>
                </div>
                <div class="mt-6 bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4 rounded-md" role="alert">
                    <p class="font-bold">Aguardando pagamento...</p>
                    <p id="status-text" class="text-sm">O QR Code expira em <span id="timer" class="font-bold">--:--</span>.</p>
                </div>
            </div>
            <div id="modal-success" class="hidden">
                <svg class="mx-auto h-16 w-16 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <h2 class="text-2xl font-bold text-gray-900 mt-4">Pagamento Confirmado!</h2>
                <p class="text-gray-600 mt-2">Sua fatura foi processada com sucesso.</p>
                <button onclick="fecharModalPix()" class="mt-6 w-full bg-gray-700 hover:bg-gray-800 text-white font-bold py-2 px-4 rounded-lg">Fechar</button>
            </div>
            <div id="modal-error" class="hidden">
                <svg class="mx-auto h-16 w-16 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <h2 class="text-2xl font-bold text-gray-900 mt-4">Ocorreu um Erro</h2>
                <p id="error-message" class="text-gray-600 mt-2">Não foi possível processar seu pagamento.</p>
                <button onclick="fecharModalPix()" class="mt-6 w-full bg-gray-700 hover:bg-gray-800 text-white font-bold py-2 px-4 rounded-lg">Fechar</button>
            </div>
        </div>
    </div>

<script>
$(document).ready(function() {
    // --- LÓGICA ORIGINAL (JQUERY) ---
    let currentClientId = null;
    $("#modalFaturaDetalhes").dialog({ autoOpen: false, modal: true, width: 'auto', minWidth: 700, buttons: { "Fechar": function() { $(this).dialog("close"); } } });
    
    function performLogin(cpfCnpj, rememberMe, isAutoLogin = false) {
        if (!isAutoLogin) { $("#loginMessage").removeClass('error success').text("Verificando..."); } 
        else { $("#loginMessage").removeClass('error success').text("Login automático..."); }
        $.ajax({
            url: '../dinovatech/app.php', type: 'POST', dataType: 'json',
            data: { action: 'validar_cpf_cnpj', cpf_cnpj: cpfCnpj },
            success: function(response) {
                if (response.success) {
                    $("#loginMessage").addClass('success').text(response.message);
                    currentClientId = response.data.id_cliente;
                    $("#loggedInClientName").text(response.data.nome_cliente);
                    if (rememberMe) { localStorage.setItem('dinovatech_cpf_cnpj', cpfCnpj); } 
                    else { localStorage.removeItem('dinovatech_cpf_cnpj'); }
                    $("#loginSection").hide();
                    $("#faturasSection").show();
                    loadClientFaturas(currentClientId);
                } else {
                    $("#loginMessage").addClass('error').text(response.message);
                    localStorage.removeItem('dinovatech_cpf_cnpj');
                }
            },
            error: function() {
                $("#loginMessage").addClass('error').text("Erro de comunicação com o servidor.");
                localStorage.removeItem('dinovatech_cpf_cnpj');
            }
        });
    }

    const savedCpfCnpj = localStorage.getItem('dinovatech_cpf_cnpj');
    if (savedCpfCnpj) {
        $("#cpfCnpjLogin").val(savedCpfCnpj);
        $("#rememberMe").prop('checked', true);
        performLogin(savedCpfCnpj, true, true);
    }

    $("#loginForm").on("submit", function(e) {
        e.preventDefault();
        const cpfCnpj = $("#cpfCnpjLogin").val();
        const rememberMe = $("#rememberMe").is(':checked');
        if (cpfCnpj.length < 11) {
            $("#loginMessage").addClass('error').text("CPF/CNPJ inválido.");
            return;
        }
        performLogin(cpfCnpj, rememberMe, false);
    });

    function loadClientFaturas(clientId) {
        $("#clientFaturasList").html("<p>Carregando faturas...</p>");
        $.ajax({
            url: '../dinovatech/app.php', type: 'POST', dataType: 'json',
            data: { action: 'buscar_faturas_cliente', id_cliente: clientId },
            success: function(response) {
                if (response.success && response.data.length > 0) {
                    let faturaHtml = "<table><thead><tr><th>ID</th><th>Emissão</th><th>Vencimento</th><th>Total</th><th>Status</th><th>Ações</th></tr></thead><tbody>";
					const hoje = new Date();
					hoje.setHours(0, 0, 0, 0);
                    response.data.forEach(fatura => {
						const dataVencimento = new Date(fatura.data_vencimento);
						dataVencimento.setHours(0, 0, 0, 0);
						const isVencida = dataVencimento < hoje && fatura.status !== 'Liquidada';
						const isLiquidado = fatura.status === 'Liquidada';
						
						const rowClass = isLiquidado ? 'bg-green-50 hover:bg-green-100' : 
                                      isVencida ? 'bg-red-50 hover:bg-red-100' : 
                                      'hover:bg-gray-50';
									  
                        const total = parseFloat(fatura.valor_total_fatura).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
                        faturaHtml += `<tr class="${rowClass}" >
                            <td>${fatura.id_fatura}</td>
                            <td>${new Date(fatura.data_emissao).toLocaleDateString('pt-BR')}</td>
                            <td>${new Date(fatura.data_vencimento).toLocaleDateString('pt-BR')}</td>
                            <td>${total}</td>
                            <td>${fatura.status}</td>
                            <td class="action-buttons"><button class="btn-ver-fatura" data-id-fatura="${fatura.id_fatura}">Ver Detalhes</button></td>
                        </tr>`;
                    });
                    faturaHtml += "</tbody></table>";
                    $("#clientFaturasList").html(faturaHtml);
                } else { $("#clientFaturasList").html("<p>Nenhuma fatura encontrada.</p>"); }
            },
            error: function() { $("#clientFaturasList").html("<p style='color: red;'>Erro ao carregar faturas.</p>"); }
        });
    }

    $(document).on("click", ".btn-ver-fatura", function() {
        openFaturaDetalhesModal($(this).data("id-fatura"));
    });
    
    function openFaturaDetalhesModal(faturaId) {
        $("#modalFaturaDetalhes").dialog("open");
        $.ajax({
            url: '../dinovatech/app.php', type: 'POST', dataType: 'json',
            data: { 
                action: 'get_fatura_detalhes', 
                id_fatura: faturaId,
                visao_cliente: 'true'
            },
            success: function(response) {
                if (response.success && response.data.fatura) {
                    const fatura = response.data.fatura;
                    $("#detalheFaturaId").text(fatura.id_fatura);
                    $("#detalheFaturaCliente").text(fatura.nome_cliente);
                    $("#detalheFaturaEmissao").text(new Date(fatura.data_emissao).toLocaleDateString('pt-BR'));
                    $("#detalheFaturaVencimento").text(new Date(fatura.data_vencimento).toLocaleDateString('pt-BR'));
                    $("#detalheFaturaStatus").text(fatura.status);
                    $("#detalheFaturaTotal").text(parseFloat(fatura.valor_total_fatura).toLocaleString('pt-BR', { minimumFractionDigits: 2 }));
                    
                    let itensHtml = '';
                    response.data.itens.forEach(item => {
                        const subtotal = parseFloat(item.quantidade) * parseFloat(item.valor_unitario);
                        itensHtml += `<tr><td>${item.nome_servico}</td><td>${item.quantidade}</td><td>R$ ${parseFloat(item.valor_unitario).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}</td><td>R$ ${subtotal.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}</td><td>${item.tag || ''}</td></tr>`;
                    });
                    $("#faturaItensTable tbody").html(itensHtml);

                    populatePagamentosTable(response.data.pagamentos);

                    const actionContainer = $("#action-buttons-container");
                    actionContainer.empty();

                    const printButton = $('<button />', {
                        text: 'Imprimir Fatura', id: 'btnPrintFatura',
                        'class': 'bg-gray-500 hover:bg-gray-600 text-white font-bold py-3 px-4 rounded-lg transition-colors duration-300'
                    });
                    actionContainer.append(printButton);
                    
                    if (fatura.status === 'Em Aberto') {
                        const payButton = $('<button />', {
                            text: 'Pagar com PIX', id: 'btnPagarFaturaComPix',
                            'class': 'bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-4 rounded-lg transition-colors duration-300',
                            'data-id-fatura': fatura.id_fatura
                        });
                        actionContainer.append(payButton);
                    }
                }
            }
        });
    }

    function populatePagamentosTable(pagamentos) {
        let tbodyHtml = '';
        if (pagamentos && pagamentos.length > 0) {
            pagamentos.forEach(pgto => {
                tbodyHtml += `<tr>
                    <td>${new Date(pgto.data_pagamento).toLocaleDateString('pt-BR')}</td>
                    <td>${parseFloat(pgto.valor_pago).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })}</td>
                    <td>${pgto.observacao || ''}</td>
                </tr>`;
            });
        } else {
            tbodyHtml = '<tr><td colspan="3">Nenhum pagamento confirmado para esta fatura.</td></tr>';
        }
        $("#faturaPagamentosTable tbody").html(tbodyHtml);
    }

    $(document).on('click', '#btnPrintFatura', function() {
        const companyName = "Digital Inovation Tecnologia";
        const companyCnpj = "61.733.714/0001-01";
        const clientName = $("#detalheFaturaCliente").text();
        const faturaId = $("#detalheFaturaId").text();
        const emissao = $("#detalheFaturaEmissao").text();
        const vencimento = $("#detalheFaturaVencimento").text();
        const total = $("#detalheFaturaTotal").text();
        const status = $("#detalheFaturaStatus").text();
        const itensTableHtml = $("#faturaItensTable").prop('outerHTML');
        const pagamentosTableHtml = $("#faturaPagamentosTable").prop('outerHTML');
        const printWindow = window.open('', '_blank');
        printWindow.document.write(`
		<html><head>
		<title>Fatura #${faturaId}</title>
		<style>body{font-family:Arial,sans-serif;margin:20px}.print-container{max-width:1000px;margin:auto}.header,.footer{text-align:center;margin-bottom:20px}.header h1{margin:0}.details{border:1px solid #ccc;padding:15px;margin-bottom:20px}table{width:100%;border-collapse:collapse;margin-top:15px}th,td{border:1px solid #ddd;padding:8px;text-align:left}th{background-color:#f2f2f2}</style></head>
		<body><div class="print-container"><div class="header">
		<h1>${companyName}</h1>
		<p>${companyCnpj}</p></div>
		<h2>Fatura #${faturaId}</h2>
		<div class="details"><p><strong>Cliente:</strong> ${clientName}</p>
		<p><strong>Emissão:</strong> ${emissao} | <strong>Vencimento:</strong> ${vencimento}</p>
		<p><strong>Status:</strong> ${status} | <strong>Total:</strong> R$ ${total}</p></div><h3>Itens</h3>${itensTableHtml}<h3 style="margin-top:20px">Pagamentos</h3>${pagamentosTableHtml}<div class="footer"><p>Gerado em: ${new Date().toLocaleString('pt-BR')}</p></div></div><script>window.onload=function(){window.print();window.onafterprint=function(){window.close()}}<\/script></body></html>`);
        printWindow.document.close();
    });

    $("#btnLogout").on("click", function() {
        currentClientId = null;
        $("#loginSection").show();
        $("#faturasSection").hide();
        localStorage.removeItem('dinovatech_cpf_cnpj');
    });

    // --- LÓGICA DO MODAL PIX (VANILLA JS) ---
    const modalPix = document.getElementById('payment-modal');
    const modalLoading = document.getElementById('modal-loading');
    const modalPayment = document.getElementById('modal-payment');
    const modalSuccess = document.getElementById('modal-success');
    const modalError = document.getElementById('modal-error');
    const errorMessage = document.getElementById('error-message');
    const qrcodeDiv = document.getElementById('qrcode');
    const pixCopiaEColaText = document.getElementById('pixCopiaECola');
    const btnCopiar = document.getElementById('btnCopiar');
    const timerSpan = document.getElementById('timer');
    const pixTxidSpan = document.getElementById('pixTxid'); // ** NOVO **

    let pollingInterval;
    let timerInterval;

    $(document).on('click', '#btnPagarFaturaComPix', async function() {
        const idFatura = $(this).data('id-fatura');
        abrirModalPix();
        try {
            const response = await fetch('../inter/endpoint.php?action=obter_ou_criar_pix_pagamento', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id_fatura: idFatura })
            });
            const result = await response.json();
            if (!result.success) throw new Error(result.message);
            renderizarModalPagamento(result.data);
            iniciarVerificacao(result.data.txid, result.data.calendario);
        } catch (error) {
            mostrarErro(error.message);
        }
    });

    function renderizarModalPagamento(pixData) {
        const qrCodeElement = kjua({ text: pixData.pixCopiaECola, size: 250, fill: '#000', back: '#fff', quiet: 1 });
        qrcodeDiv.innerHTML = '';
        qrcodeDiv.appendChild(qrCodeElement);
        pixCopiaEColaText.value = pixData.pixCopiaECola;
        pixTxidSpan.textContent = pixData.txid; // ** NOVO **
        modalLoading.classList.add('hidden');
        modalPayment.classList.remove('hidden');
    }

    function iniciarVerificacao(txid, calendario) {
        if (pollingInterval) clearInterval(pollingInterval);
        if (timerInterval) clearInterval(timerInterval);

        const criacaoTimestamp = new Date(calendario.criacao).getTime();
        const expiracaoTimestamp = criacaoTimestamp + (calendario.expiracao * 1000);
        let tempoRestante = Math.max(0, Math.round((expiracaoTimestamp - Date.now()) / 1000));

        timerInterval = setInterval(() => {
            const minutos = Math.floor(tempoRestante / 60).toString().padStart(2, '0');
            const segundos = (tempoRestante % 60).toString().padStart(2, '0');
            timerSpan.textContent = `${minutos}:${segundos}`;
            if (tempoRestante <= 0) {
                clearInterval(timerInterval);
                clearInterval(pollingInterval);
                mostrarErro("O tempo para pagamento expirou.");
            }
            tempoRestante--;
        }, 1000);

        pollingInterval = setInterval(async () => {
            try {
                const response = await fetch(`../inter/endpoint.php?action=verificar_pagamento_pix&txid=${txid}`);
                const result = await response.json();
                if (result.success && result.data.status === 'CONCLUIDA') {
                    clearInterval(pollingInterval);
                    clearInterval(timerInterval);
                    mostrarSucesso();
                    if(currentClientId) loadClientFaturas(currentClientId); 
                }
            } catch (error) { console.error("Erro na verificação:", error); }
        }, 10000); // Intervalo ajustado para 10 segundos
    }

    function abrirModalPix() {
        modalPix.style.display = 'flex';
        modalLoading.classList.remove('hidden');
        modalPayment.classList.add('hidden');
        modalSuccess.classList.add('hidden');
        modalError.classList.add('hidden');
    }
    
    window.fecharModalPix = function() {
        modalPix.style.display = 'none';
        if (pollingInterval) clearInterval(pollingInterval);
        if (timerInterval) clearInterval(timerInterval);
        $("#modalFaturaDetalhes").dialog("close");
    }

    function mostrarSucesso() {
        modalPayment.classList.add('hidden');
        modalSuccess.classList.remove('hidden');
    }

    function mostrarErro(msg) {
        modalLoading.classList.add('hidden');
        modalPayment.classList.add('hidden');
        errorMessage.textContent = msg;
        modalError.classList.remove('hidden');
    }

    btnCopiar.addEventListener('click', () => {
        pixCopiaEColaText.select();
        document.execCommand('copy');
        btnCopiar.textContent = 'Copiado!';
        setTimeout(() => { btnCopiar.textContent = 'Copiar Código'; }, 2000);
    });
});
</script>
</body>
</html>
