<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel de Testes - API PIX Inter</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- ** CORREÇÃO: Trocada a biblioteca de QR Code por uma mais robusta ** -->
    <script src="https://cdn.jsdelivr.net/npm/kjua@0.9.0/dist/kjua.min.js"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .spinner {
            border: 4px solid rgba(0, 0, 0, 0.1);
            width: 36px;
            height: 36px;
            border-radius: 50%;
            border-left-color: #06b6d4;
            animation: spin 1s ease infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="bg-gray-100 text-gray-800">

    <div class="container mx-auto p-4 md:p-8 max-w-4xl">
        <header class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Painel de Testes - API PIX Banco Inter</h1>
            <p class="text-gray-600 mt-1">Interface para interagir com os endpoints da API PIX.</p>
        </header>

        <main class="space-y-8">
            <!-- Seção para Criar Cobrança -->
            <section class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-xl font-semibold mb-4">1. Criar Cobrança PIX</h2>
                <button id="btnCriarCobranca" class="bg-cyan-600 hover:bg-cyan-700 text-white font-bold py-2 px-4 rounded-lg transition-colors duration-300">
                    Gerar Nova Cobrança
                </button>
                <!-- Container para o QR Code -->
                <div id="qrcode-container" class="mt-6 hidden text-center">
                    <h3 class="text-lg font-semibold mb-2">QR Code para Pagamento</h3>
                    <div id="qrcode" class="inline-block p-2 bg-white border rounded-lg shadow-sm"></div>
                    <p class="text-sm text-gray-600 mt-2">Aponte a câmera do seu app de banco</p>
                </div>
            </section>

            <!-- Seção para Consultar Cobrança -->
            <section class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-xl font-semibold mb-4">2. Consultar Cobrança PIX por TXID</h2>
                <div class="flex flex-col sm:flex-row gap-4">
                    <input type="text" id="inputTxid" placeholder="O txid aparecerá aqui após criar" class="flex-grow p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500">
                    <button id="btnConsultarCobranca" class="bg-gray-700 hover:bg-gray-800 text-white font-bold py-2 px-4 rounded-lg transition-colors duration-300">
                        Consultar
                    </button>
                </div>
            </section>
            
            <!-- Seção Pagar Cobrança -->
            <section class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-xl font-semibold mb-4 text-orange-600">3. Pagar Cobrança (Sandbox)</h2>
                <p class="mb-4 text-gray-700">Use o TXID da cobrança criada para simular um pagamento.</p>
                <div class="flex flex-col sm:flex-row gap-4">
                    <input type="text" id="inputValorPagar" placeholder="Valor (ex: 1.50)" class="w-full sm:w-1/3 p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                    <button id="btnPagarCobranca" class="w-full sm:w-auto bg-orange-500 hover:bg-orange-600 text-white font-bold py-2 px-4 rounded-lg transition-colors duration-300">
                        Pagar Cobrança
                    </button>
                </div>
            </section>

            <!-- Seção Consultar Recibo -->
            <section class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-xl font-semibold mb-4 text-indigo-600">4. Consultar Recibo por E2EID</h2>
                <p class="mb-4 text-gray-700">Após um pagamento, a consulta da cobrança retornará um `e2eid`. Use-o aqui.</p>
                <div class="flex flex-col sm:flex-row gap-4">
                    <input type="text" id="inputE2eid" placeholder="Cole o e2eid aqui" class="flex-grow p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <button id="btnConsultarRecibo" class="w-full sm:w-auto bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-lg transition-colors duration-300">
                        Consultar Recibo
                    </button>
                </div>
            </section>
            
            <!-- Seção: Consultar Lista de PIX -->
            <section class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-xl font-semibold mb-4 text-teal-600">5. Consultar Lista de PIX Recebidos</h2>
                <p class="mb-4 text-gray-700">Selecione um período para buscar todos os PIX recebidos.</p>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label for="dateInicio" class="block text-sm font-medium text-gray-700">Data Início</label>
                        <input type="date" id="dateInicio" class="mt-1 w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                    </div>
                    <div>
                        <label for="dateFim" class="block text-sm font-medium text-gray-700">Data Fim</label>
                        <input type="date" id="dateFim" class="mt-1 w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                    </div>
                    <div class="self-end">
                        <button id="btnConsultarLista" class="w-full bg-teal-600 hover:bg-teal-700 text-white font-bold py-2 px-4 rounded-lg transition-colors duration-300">
                            Buscar Período
                        </button>
                    </div>
                </div>
            </section>

            <!-- Seção de Resultados -->
            <section>
                <h2 class="text-xl font-semibold mb-4">Resultados</h2>
                <div id="resultado" class="bg-gray-900 text-white p-4 rounded-lg shadow-inner min-h-[200px] overflow-x-auto transition-colors duration-300">
                    <div id="placeholder" class="text-gray-400">Aguardando ação...</div>
                    <div id="loader" class="hidden spinner mx-auto"></div>
                    <pre id="json-output" class="text-sm"></pre>
                </div>
            </section>
        </main>
    </div>

    <script>
        const btnCriar = document.getElementById('btnCriarCobranca');
        const btnConsultar = document.getElementById('btnConsultarCobranca');
        const btnPagar = document.getElementById('btnPagarCobranca');
        const btnConsultarRecibo = document.getElementById('btnConsultarRecibo');
        const btnConsultarLista = document.getElementById('btnConsultarLista');

        const inputTxid = document.getElementById('inputTxid');
        const inputValor = document.getElementById('inputValorPagar');
        const inputE2eid = document.getElementById('inputE2eid');
        const dateInicio = document.getElementById('dateInicio');
        const dateFim = document.getElementById('dateFim');

        const resultadoDiv = document.getElementById('resultado');
        const jsonOutput = document.getElementById('json-output');
        const placeholder = document.getElementById('placeholder');
        const loader = document.getElementById('loader');
        const qrcodeContainer = document.getElementById('qrcode-container');
        const qrcodeDiv = document.getElementById('qrcode');

        function showLoading() {
            placeholder.classList.add('hidden');
            jsonOutput.textContent = '';
            resultadoDiv.classList.remove('bg-red-900', 'bg-green-900');
            qrcodeContainer.classList.add('hidden');
            qrcodeDiv.innerHTML = '';
            loader.classList.remove('hidden');
        }

        function hideLoading() {
            loader.classList.add('hidden');
        }

        function displayResult(data, isSuccess = true) {
            hideLoading();
            jsonOutput.textContent = JSON.stringify(data, null, 2);
            if(isSuccess) {
                resultadoDiv.classList.add('bg-green-900');
            }
        }

        function displayError(errorMessage) {
            hideLoading();
            const errorObj = { error: "Erro na Requisição", message: errorMessage };
            jsonOutput.textContent = JSON.stringify(errorObj, null, 2);
            resultadoDiv.classList.add('bg-red-900');
        }

        btnCriar.addEventListener('click', async () => {
            showLoading();
            try {
                const response = await fetch('endpoint.php?action=criar_cobranca');
                const result = await response.json();
                if (!response.ok || !result.success) throw new Error(result.message);
                
                displayResult(result.data);
                if(result.data?.txid) inputTxid.value = result.data.txid;
                if(result.data?.valor?.original) inputValor.value = result.data.valor.original;

                // ** CORREÇÃO: Gera e exibe o QR Code com a nova biblioteca **
                if (result.data?.pixCopiaECola) {
                    const qrCodeElement = kjua({
                        text: result.data.pixCopiaECola,
                        size: 200,
                        fill: '#000',
                        back: '#fff',
                        quiet: 1, // Margem
                    });
                    qrcodeDiv.innerHTML = ''; // Limpa o container
                    qrcodeDiv.appendChild(qrCodeElement); // Adiciona o novo QR Code
                    qrcodeContainer.classList.remove('hidden');
                }

            } catch (error) { displayError(error.message); }
        });

        btnConsultar.addEventListener('click', async () => {
            const txid = inputTxid.value.trim();
            if (!txid) return alert('Insira um TXID para consultar.');
            showLoading();
            try {
                const response = await fetch(`endpoint.php?action=consultar_cobranca&txid=${txid}`);
                const result = await response.json();
                if (!response.ok || !result.success) throw new Error(result.message);
                displayResult(result.data);

                if(result.data?.pix?.[0]?.endToEndId) {
                    inputE2eid.value = result.data.pix[0].endToEndId;
                }

            } catch (error) { displayError(error.message); }
        });
        
        btnPagar.addEventListener('click', async () => {
            const txid = inputTxid.value.trim();
            const valor = inputValor.value.trim();
            if (!txid || !valor) return alert('TXID e Valor são necessários para pagar.');
            
            showLoading();
            try {
                const response = await fetch('endpoint.php?action=pagar_cobranca', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ txid: txid, valor: parseFloat(valor) })
                });
                const result = await response.json();
                if (!response.ok || !result.success) throw new Error(result.message);
                displayResult(result.data);

                setTimeout(() => btnConsultar.click(), 2000);

            } catch (error) { displayError(error.message); }
        });

        btnConsultarRecibo.addEventListener('click', async () => {
            const e2eid = inputE2eid.value.trim();
            if (!e2eid) return alert('Insira um E2EID para consultar o recibo.');
            showLoading();
            try {
                const response = await fetch(`endpoint.php?action=consultar_recibo&e2eid=${e2eid}`);
                const result = await response.json();
                if (!response.ok || !result.success) throw new Error(result.message);
                displayResult(result.data);
            } catch (error) { displayError(error.message); }
        });

        btnConsultarLista.addEventListener('click', async () => {
            const inicioVal = dateInicio.value;
            const fimVal = dateFim.value;
            if (!inicioVal || !fimVal) return alert('Selecione a data de início e fim.');

            const inicio = `${inicioVal}T00:00:00.000Z`;
            const fim = `${fimVal}T23:59:59.999Z`;
            
            showLoading();
            try {
                const url = `endpoint.php?action=consultar_lista_pix&inicio=${encodeURIComponent(inicio)}&fim=${encodeURIComponent(fim)}`;
                const response = await fetch(url);
                const result = await response.json();
                if (!response.ok || !result.success) throw new Error(result.message);
                displayResult(result.data);
            } catch (error) { displayError(error.message); }
        });
    </script>
</body>
</html>
