<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NFS-e DF - Campo de Provas (A1 & A3)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Lacuna WebPKI -->
    <script src="https://cdn.lacunasoftware.com/libs/web-pki/lacuna-web-pki-2.14.0.min.js"></script>
    <style>
        body { background-color: #f8f9fa; }
        .card { box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .response-area { background: #2d2d2d; color: #f8f8f2; padding: 15px; border-radius: 5px; font-family: monospace; white-space: pre-wrap; font-size: 0.85rem; max-height: 500px; overflow-y: auto; }
        .nav-tabs .nav-link { cursor: pointer; }
    </style>
</head>
<body class="py-4">
    <div class="container">
        <h1 class="mb-4 text-center">NFS-e DF <small class="text-muted">Campo de Provas</small></h1>

        <div class="row g-4">
            <!-- Controls -->
            <div class="col-md-4">
                <div class="card mb-3">
                    <div class="card-header bg-primary text-white">Configuração</div>
                    <div class="card-body">
                        <ul class="nav nav-tabs mb-3" id="mainTab">
                            <li class="nav-item">
                                <a class="nav-link active" id="tab-a1" data-bs-toggle="tab" data-bs-target="#content-a1">A1 (Servidor)</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="tab-a3" data-bs-toggle="tab" data-bs-target="#content-a3">A3 (WebPKI)</a>
                            </li>
                        </ul>

                        <div class="tab-content">
                            <!-- SHARED INPUTS -->
                            <div class="mb-3">
                                <label class="form-label">Ambiente</label>
                                <select class="form-select" id="endpoint">
                                    <option value="fictitious" selected>Homologação Fictícia (issnetonline)</option>
                                    <option value="official">Homologação Oficial (issnetonline)</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">CNPJ Prestador</label>
                                <input type="text" class="form-control" id="cnpj" value="61733714000101">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">IM Prestador</label>
                                <input type="text" class="form-control" id="im" value="0841147200111">
                            </div>

                            <!-- FILTER SETTINGS (Toggle) -->
                            <hr>
                            <label class="form-label">Filtro de Consulta</label>
                            <div class="mb-2">
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="filterType" id="filterNumber" value="NUMBER">
                                    <label class="form-check-label" for="filterNumber">Núm. Nota</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="filterType" id="filterPeriod" value="PERIOD" checked>
                                    <label class="form-check-label" for="filterPeriod">Período</label>
                                </div>
                            </div>
                            
                            <div id="groupNumber" class="d-none">
                                <input type="text" class="form-control mb-2" id="numero" value="1" placeholder="Número da Nota">
                            </div>
                            
                            <div id="groupPeriod">
                                <div class="row">
                                    <div class="col">
                                        <input type="date" class="form-control" id="dataInicial" value="2026-01-01">
                                    </div>
                                    <div class="col">
                                        <input type="date" class="form-control" id="dataFinal" value="2026-01-31">
                                    </div>
                                </div>
                            </div>

                            <hr>

                            <!-- A1 Specific -->
                            <div class="tab-pane fade show active" id="content-a1">
                                <div class="mb-3">
                                    <label class="form-label fw-bold text-success">Variação de Protocolo</label>
                                    <select class="form-select" id="variation">
                                        <option value="support_combo" selected>Modelo Suporte (Sem Prefixo / URI="")</option>
                                        <option value="standard">Padrão (ds: / URI=#ID)</option>
                                        <option value="uri_empty">URI Vazia (URI="")</option>
                                        <option value="no_prefix">Sem Prefixo (ds: removido)</option>
                                        <option value="no_cdata">Sem CDATA (Escaped XML)</option>
                                    </select>
                                    <div class="form-text">O modo <strong>Modelo Suporte</strong> segue estritamente o XML enviado pela ISSNET.</div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">CPF (Opcional)</label>
                                    <input type="text" class="form-control form-control-sm" id="cpf" placeholder="Apenas números">
                                </div>
                                <div class="d-grid gap-2">
                                    <button type="button" class="btn btn-success" onclick="doTestA1('consultar')">Consultar Notas (Testada)</button>
                                    <button type="button" class="btn btn-warning" onclick="doTestA1('gerar')">Gerar RPS de Teste (NOVO)</button>
                                </div>
                            </div>

                            <!-- A3 Specific -->
                            <div class="tab-pane fade" id="content-a3">
                                <div class="mb-3">
                                    <label class="form-label">Selecionar Certificado (WebPKI)</label>
                                    <select class="form-select" id="certificateSelect">
                                        <option value="">Carregando...</option>
                                    </select>
                                    <button class="btn btn-sm btn-outline-secondary mt-2 w-100" onclick="initWebPKI()">Recarregar Certificados</button>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">CPF (Opcional - A3)</label>
                                    <input type="text" class="form-control form-control-sm" id="cpf_a3" placeholder="Apenas números">
                                </div>
                                <div class="alert alert-secondary small">
                                    Requer licença WebPKI ou ambiente localhost.
                                </div>
                                <button type="button" class="btn btn-primary w-100 mt-3" onclick="doTestA3()">Assinar e Enviar (A3)</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Results -->
            <div class="col-md-8">
                <div class="card h-100">
                    <div class="card-header">Resultado da Requisição</div>
                    <div class="card-body d-flex flex-column">
                        <div id="loader" class="text-center py-5 d-none">
                            <div class="spinner-border text-primary" role="status"></div>
                            <p class="mt-2" id="loaderText">Processando...</p>
                        </div>
                        
                        <div id="results">
                            <h5>Status HTTP: <span id="resStatus" class="badge bg-secondary">-</span></h5>
                            
                            <ul class="nav nav-tabs mt-3" id="resTab">
                                <li class="nav-item">
                                    <a class="nav-link active" data-bs-toggle="tab" data-bs-target="#res-body">Response Body</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-bs-toggle="tab" data-bs-target="#res-headers">Headers</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-bs-toggle="tab" data-bs-target="#res-request">Request Envelope</a>
                                </li>
                            </ul>
                            
                            <div class="tab-content flex-grow-1">
                                <div class="tab-pane fade show active" id="res-body">
                                    <pre class="response-area mt-2" id="bodyContent">Aguardando execução...</pre>
                                </div>
                                <div class="tab-pane fade" id="res-headers">
                                    <pre class="response-area mt-2" id="headersContent">...</pre>
                                </div>
                                <div class="tab-pane fade" id="res-request">
                                    <pre class="response-area mt-2" id="requestContent">...</pre>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // --- UI ---
        document.querySelectorAll('input[name="filterType"]').forEach(radio => {
            radio.addEventListener('change', (e) => {
                const isNumber = e.target.value === 'NUMBER';
                document.getElementById('groupNumber').classList.toggle('d-none', !isNumber);
                document.getElementById('groupPeriod').classList.toggle('d-none', isNumber);
            });
        });

        // --- WEB PKI (A3) ---
        let pki = new LacunaWebPKI();
        
        function initWebPKI() {
            pki.init({
                ready: function () {
                    pki.listCertificates({
                        selectId: 'certificateSelect',
                        selectOptionFormatter: function (cert) {
                            return cert.subjectName + ' (Exp: ' + new Date(cert.validityEnd).toLocaleDateString() + ')';
                        }
                    }).success(function (certs) {
                        console.log("Certificates loaded.");
                    });
                },
                defaultError: function (message, error, origin, code) {
                    console.error('Web PKI: ' + message);
                }
            });
        }
        
        window.addEventListener('load', initWebPKI);

        // --- EXECUTION ---

        function getCommonData(isA3 = false) {
             const cpfVal = isA3 ? document.getElementById('cpf_a3').value : document.getElementById('cpf').value;
             
            return {
                endpoint: document.getElementById('endpoint').value,
                cnpj: document.getElementById('cnpj').value,
                cpf: cpfVal,
                im: document.getElementById('im').value,
                numero: document.getElementById('numero').value,
                dataInicial: document.querySelector('input[name="filterType"]:checked').value === 'PERIOD' ? document.getElementById('dataInicial').value : '',
                dataFinal: document.querySelector('input[name="filterType"]:checked').value === 'PERIOD' ? document.getElementById('dataFinal').value : ''
            };
        }

        async function doTestA1(method = 'consultar') {
            showLoader(method === 'gerar' ? "Gerando RPS de Teste..." : "Consultando API...");
            
            const payload = getCommonData(false);
            payload.action = 'direct_a1';
            payload.method = method; // Pass method
            payload.variation = document.getElementById('variation').value; 
            
            try {
                const req = await fetch('api.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(payload)
                });
                const res = await req.json();
                renderResponse(res);
            } catch (err) {
                renderError(err);
            } finally {
                hideLoader();
            }
        }

        async function doTestA3() {
            // (A3 Logic Reuse if needed)
            const thumbprint = document.getElementById('certificateSelect').value;
            if(!thumbprint) { alert("Selecione um certificado!"); return; }
            alert("A3 implementado apenas para Consulta por enquanto.");
        }

        function renderResponse(res) {
            document.getElementById('resStatus').innerText = res.http_code;
            document.getElementById('resStatus').className = 'badge ' + (res.http_code == 200 ? 'bg-success' : 'bg-danger');
            
            document.getElementById('bodyContent').innerText = res.response_body || res.curl_error || 'No response';
            document.getElementById('headersContent').innerText = res.headers ? res.headers.join('\n') : 'No headers';
            document.getElementById('requestContent').innerText = res.request_envelope || 'No request generated';
        }

        function renderError(err) {
            document.getElementById('bodyContent').innerText = 'Fatal JS Error: ' + err.message;
        }

        function showLoader(msg) {
            document.getElementById('loader').classList.remove('d-none');
            document.getElementById('loaderText').innerText = msg;
            document.getElementById('results').classList.add('d-none');
        }

        function hideLoader() {
            document.getElementById('loader').classList.add('d-none');
            document.getElementById('results').classList.remove('d-none');
        }
    </script>
</body>
</html>