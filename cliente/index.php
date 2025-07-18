<?php
// cliente/index.php

// Inclui o arquivo de configuração do banco de dados.
// O caminho "../database.php" significa "subir um nível de diretório"
// para encontrar o database.php, o que é correto se 'cliente' está no mesmo nível
// que 'database.php' e 'app.php'.
include "../database.php"; 
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Área do Cliente Dinovatech</title>
    <!-- Inclua jQuery via CDN -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <!-- Inclua jQuery UI para autocomplete e dialog (modal) - Opcional, mas útil para mensagens/futuras interações -->
    <script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.13.2/jquery-ui.min.js"></script>
    <link rel="stylesheet" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.13.2/themes/smoothness/jquery-ui.css">

    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f0f2f5; display: flex; justify-content: center; align-items: flex-start; min-height: 100vh; }
        .container { max-width: 800px; width: 100%; padding: 30px; background-color: #fff; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        h1, h2 { text-align: center; color: #333; margin-bottom: 25px; }

        /* Login Section */
        #loginSection {
            background-color: #e9ecef;
            padding: 25px;
            border-radius: 8px;
            box-shadow: inset 0 1px 3px rgba(0,0,0,0.1);
            max-width: 400px;
            margin: 0 auto;
        }
        #loginSection label { display: block; margin-bottom: 8px; font-weight: bold; color: #555; }
        #loginSection input[type="text"] {
            width: calc(100% - 22px);
            padding: 12px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 1.1em;
        }
        #loginSection button {
            background-color: #007bff;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1.1em;
            width: 100%;
            transition: background-color 0.3s ease;
        }
        #loginSection button:hover { background-color: #0056b3; }
        .message { text-align: center; margin-top: 15px; font-weight: bold; }
        .message.error { color: #dc3545; }
        .message.success { color: #28a745; }

        /* Faturas Section */
        #faturasSection { display: none; margin-top: 30px; }
        #faturasHeader { background-color: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px; text-align: center; }
        #faturasHeader p { margin: 5px 0; font-size: 1.1em; }
        #faturasHeader button { background-color: #6c757d; color: white; padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer; font-size: 0.9em; margin-top: 10px; }
        #faturasHeader button:hover { background-color: #5a6268; }

        #clientFaturasList table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        #clientFaturasList th, #clientFaturasList td { border: 1px solid #dee2e6; padding: 10px; text-align: left; }
        #clientFaturasList th { background-color: #e9ecef; font-weight: bold; }
        #clientFaturasList tr:nth-child(even) { background-color: #f8f9fa; }
        #clientFaturasList .action-buttons button { padding: 5px 10px; margin-right: 5px; font-size: 0.9em; background-color: #17a2b8; color: white; border: none; border-radius: 4px; cursor: pointer; }
        #clientFaturasList .action-buttons button:hover { background-color: #138496; }

        /* Modal for Fatura Details (reusing admin's modal structure) */
        .ui-dialog { z-index: 1000 !important; }
        .ui-dialog .ui-dialog-content { padding: 1.5em !important; }
        #modalFaturaDetalhes .fatura-header-modal { background-color: #f9f9f9; padding: 10px; border-radius: 5px; margin-bottom: 20px; border: 1px dashed #ccc; }
        #modalFaturaDetalhes .fatura-header-modal p { margin: 5px 0; }
        #modalFaturaDetalhes table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        #modalFaturaDetalhes th, #modalFaturaDetalhes td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        #modalFaturaDetalhes th { background-color: #f2f2f2; }
        /* Estilo para o ícone de recorrência */
        .recorrente-icon { margin-left: 5px; font-size: 0.9em; color: #28a745; } /* Cor verde para destaque */
    </style>
</head>
<body>
    <div class="container">
        <h1>Área do Cliente</h1>

        <!-- Login Section -->
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

        <!-- Faturas Section (Hidden until login) -->
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

    <!-- Modal de Detalhes da Fatura (Reutilizado do painel admin) -->
    <div id="modalFaturaDetalhes" title="Detalhes da Fatura" style="display: none;">
        <div class="fatura-header-modal">
            <p><strong>Fatura ID:</strong> <span id="detalheFaturaId"></span></p>
            <p><strong>Cliente:</strong> <span id="detalheFaturaCliente"></span></p>
            <p><strong>Emissão:</strong> <span id="detalheFaturaEmissao"></span> | <strong>Vencimento:</strong> <span id="detalheFaturaVencimento"></span></p>
            <p><strong>Status:</strong> <span id="detalheFaturaStatus"></span> | <strong>Total:</strong> R$ <span id="detalheFaturaTotal"></span></p>
        </div>

        <h3>Itens da Fatura</h3>
        <div id="itensFaturaList">
            <table id="faturaItensTable">
                <thead>
                    <tr>
                        <th>Serviço</th>
                        <th>Qtd</th>
                        <th>Valor Unit.</th>
                        <th>Subtotal</th>
                        <th>Tag</th> <!-- NOVA COLUNA -->
                    </tr>
                </thead>
                <tbody>
                    <!-- Itens serão carregados aqui via JS -->
                </tbody>
            </table>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            let currentClientId = null; // Armazena o ID do cliente logado

            // Configuração do modal de detalhes da fatura
            $("#modalFaturaDetalhes").dialog({
                autoOpen: false,
                modal: true,
                // Largura dinâmica: 90% da largura da janela, mínimo de 700px, máximo de 1200px
                width: Math.min($(window).width() * 0.9, 1200),
                minWidth: 700,
                buttons: {
                    "Fechar": function() { $(this).dialog("close"); }
                },
                // Opcional: Ajustar largura ao redimensionar a janela (pode ser pesado para muitos modais)
                // resize: function(event, ui) {
                //     $(this).dialog("option", "width", Math.min($(window).width() * 0.9, 1200));
                // }
            });

            // SANITIZAÇÃO CPF/CNPJ: Garante que apenas números sejam digitados
            $("#cpfCnpjLogin").on("keyup", function() {
                let value = $(this).val();
                $(this).val(value.replace(/[^0-9]/g, '')); // Remove tudo que não for número
            });

            // Função para realizar o login (usada tanto no submit do form quanto no auto-login)
            function performLogin(cpfCnpj, rememberMe, isAutoLogin = false) {
                if (!isAutoLogin) { // Apenas mostra mensagem se não for auto-login
                    $("#loginMessage").removeClass('error success').text("Verificando...");
                } else {
                    $("#loginMessage").removeClass('error success').text("Login automático...");
                }

                $.ajax({
                    url: '../dinovatech/app.php', 
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'validar_cpf_cnpj',
                        cpf_cnpj: cpfCnpj
                    },
                    success: function(response) {
                        if (response.success) {
                            $("#loginMessage").addClass('success').text(response.message);
                            currentClientId = response.data.id_cliente;
                            $("#loggedInClientName").text(response.data.nome_cliente);

                            if (rememberMe) {
                                localStorage.setItem('dinovatech_cpf_cnpj', cpfCnpj);
                            } else {
                                localStorage.removeItem('dinovatech_cpf_cnpj');
                            }

                            $("#loginSection").hide();
                            $("#faturasSection").show();
                            loadClientFaturas(currentClientId);
                        } else {
                            $("#loginMessage").addClass('error').text(response.message);
                            // Se o login falhar (mesmo automático), remove do localStorage para evitar loop
                            localStorage.removeItem('dinovatech_cpf_cnpj'); 
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        $("#loginMessage").addClass('error').text("Erro de comunicação com o servidor.");
                        console.error("AJAX Error (validar_cpf_cnpj):", textStatus, errorThrown, jqXHR);
                        // Em caso de erro de comunicação, também remove do localStorage
                        localStorage.removeItem('dinovatech_cpf_cnpj'); 
                    }
                });
            }

            // NOVO: Verifica se há CPF/CNPJ salvo no localStorage ao carregar a página
            const savedCpfCnpj = localStorage.getItem('dinovatech_cpf_cnpj');
            if (savedCpfCnpj) {
                $("#cpfCnpjLogin").val(savedCpfCnpj);
                $("#rememberMe").prop('checked', true); // Marca o checkbox
                // Tenta logar automaticamente sem submeter o formulário diretamente
                performLogin(savedCpfCnpj, true, true); 
            }


            // Lógica de Login (submissão manual do formulário)
            $("#loginForm").on("submit", function(e) {
                e.preventDefault();
                const cpfCnpj = $("#cpfCnpjLogin").val();
                const rememberMe = $("#rememberMe").is(':checked');

                if (cpfCnpj.length < 11) {
                    $("#loginMessage").addClass('error').text("CPF/CNPJ inválido. Digite pelo menos 11 dígitos.");
                    return;
                }
                performLogin(cpfCnpj, rememberMe, false); // Chama a função de login
            });

            // Função para carregar faturas do cliente (reutilizada do admin)
            function loadClientFaturas(clientId) {
                $("#clientFaturasList").html("<p>Carregando faturas...</p>");
                $.ajax({
                    // Caminho relativo para o app.php:
                    // De 'cliente/index.php' para 'dinovatech/app.php'
                    url: '../dinovatech/app.php', 
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'buscar_faturas_cliente',
                        id_cliente: clientId
                    },
                    success: function(response) {
                        console.log("Response from buscar_faturas_cliente (cliente area):", response);
                        if (response.success && response.data.length > 0) {
                            let faturaHtml = "<table><thead><tr><th>ID</th><th>Emissão</th><th>Vencimento</th><th>Total</th><th>Status</th><th>Ações</th></tr></thead><tbody>";
                            response.data.forEach(fatura => {
                                const total = parseFloat(fatura.valor_total_fatura).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
                                faturaHtml += `
                                    <tr>
                                        <td>${fatura.id_fatura}</td>
                                        <td>${new Date(fatura.data_emissao).toLocaleDateString('pt-BR')}</td>
                                        <td>${new Date(fatura.data_vencimento).toLocaleDateString('pt-BR')}</td>
                                        <td>${total}</td>
                                        <td>${fatura.status}</td>
                                        <td class="action-buttons">
                                            <button class="btn-ver-fatura primary" data-id-fatura="${fatura.id_fatura}">Ver Detalhes</button>
                                        </td>
                                    </tr>
                                `;
                            });
                            faturaHtml += "</tbody></table>";
                            $("#clientFaturasList").html(faturaHtml);
                        } else {
                            $("#clientFaturasList").html("<p>Nenhuma fatura encontrada para você.</p>");
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        $("#clientFaturasList").html("<p style='color: red;'>Erro ao carregar faturas. Tente novamente mais tarde.</p>");
                        console.error("AJAX Error (buscar_faturas_cliente - cliente area):", textStatus, errorThrown, jqXHR);
                    }
                });
            }

            // Abrir Modal de Detalhes da Fatura (reutilizada do admin)
            $(document).on("click", ".btn-ver-fatura", function() {
                const faturaId = $(this).data("id-fatura");
                openFaturaDetalhesModal(faturaId);
            });

            // Função para preencher e abrir o modal de detalhes da fatura (reutilizada do admin, simplificada sem edição/remoção)
            function openFaturaDetalhesModal(faturaId) {
                // Ajusta a largura do modal antes de abrir
                const newWidth = Math.min($(window).width() * 0.9, 1200);
                $("#modalFaturaDetalhes").dialog("option", "width", newWidth);

                $("#modalFaturaDetalhes").dialog("open");
                $("#faturaDetalhesHeader").html("<p>Carregando detalhes da fatura...</p>");
                $("#faturaItensTable tbody").html("<tr><td colspan='5'>Carregando itens...</td></tr>"); // 5 colunas agora (Serviço, Qtd, Valor Unit., Subtotal, Tag)

                $.ajax({
                    // Caminho relativo para o app.php:
                    // De 'cliente/index.php' para 'dinovatech/app.php'
                    url: '../dinovatech/app.php', 
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'get_fatura_detalhes',
                        id_fatura: faturaId
                    },
                    success: function(response) {
                        console.log("Response from get_fatura_detalhes (cliente area):", response);
                        if (response.success && response.data.fatura) {
                            const fatura = response.data.fatura;
                            $("#detalheFaturaId").text(fatura.id_fatura);
                            $("#detalheFaturaCliente").text(fatura.nome_cliente);
                            $("#detalheFaturaEmissao").text(new Date(fatura.data_emissao).toLocaleDateString('pt-BR'));
                            $("#detalheFaturaVencimento").text(new Date(fatura.data_vencimento).toLocaleDateString('pt-BR'));
                            $("#detalheFaturaStatus").text(fatura.status);
                            $("#detalheFaturaTotal").text(parseFloat(fatura.valor_total_fatura).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }));
                            
                            populateItensFaturaTable(response.data.itens); // Popula a tabela de itens
                        } else {
                            $("#faturaDetalhesHeader").html("<p style='color: red;'>" + response.message + "</p>");
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        console.error("AJAX Error (get_fatura_detalhes - cliente area):", textStatus, errorThrown, jqXHR);
                        $("#faturaDetalhesHeader").html("<p style='color: red;'>Erro ao carregar detalhes da fatura. Tente novamente mais tarde.</p>");
                    }
                });
            }

            // Popula a tabela de itens da fatura (simplificada para o cliente, sem botões de edição/remoção)
            function populateItensFaturaTable(itens) {
                let tbodyHtml = '';
                let totalItensCalculated = 0;
                if (itens && itens.length > 0) {
                    itens.forEach(item => {
                        const subtotal = parseFloat(item.quantidade) * parseFloat(item.valor_unitario);
                        totalItensCalculated += subtotal;
                        // Verifica se a tag indica um serviço recorrente
                        const isRecorrente = item.tag && item.tag.includes('Recorrência'); 
                        const recorrenteIcon = isRecorrente ? '<span class="recorrente-icon" title="Serviço Recorrente">&#x1F504;</span>' : ''; // Ícone de setas circulares

                        tbodyHtml += `
                            <tr>
                                <td>${item.nome_servico} ${recorrenteIcon}</td>
                                <td>${item.quantidade}</td>
                                <td>R$ ${parseFloat(item.valor_unitario).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                                <td>R$ ${subtotal.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                                <td>${item.tag ? item.tag : ''}</td> <!-- Exibe a tag -->
                            </tr>
                        `;
                    });
                    tbodyHtml += `
                        <tr>
                            <td colspan="4" style="text-align: right; font-weight: bold;">Total dos Itens:</td>
                            <td style="font-weight: bold;">R$ ${totalItensCalculated.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                        </tr>
                    `;
                } else {
                    tbodyHtml = '<tr><td colspan="5">Nenhum item adicionado a esta fatura.</td></tr>'; // 5 colunas
                }
                $("#faturaItensTable tbody").html(tbodyHtml);
            }

            // Lógica de Logout
            $("#btnLogout").on("click", function() {
                currentClientId = null;
                $("#loggedInClientName").text("");
                $("#loginForm")[0].reset();
                $("#loginMessage").text("");
                $("#faturasSection").hide();
                $("#loginSection").show();
                $("#clientFaturasList").html("<p>Carregando suas faturas...</p>"); // Limpa a lista
                localStorage.removeItem('dinovatech_cpf_cnpj'); // Remove o CPF/CNPJ do localStorage ao sair
            });

            // Verifica se já há um cliente logado (ex: via session storage, se implementado)
            // Para este exemplo simples, o cliente precisa logar a cada visita.
            // Se você quiser persistência de login, precisaria de sessões PHP ou LocalStorage.
        });
    </script>
</body>
</html>
