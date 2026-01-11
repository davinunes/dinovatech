<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NFS-e DF - Campo de Provas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }

        .card {
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .response-area {
            background: #2d2d2d;
            color: #f8f8f2;
            padding: 15px;
            border-radius: 5px;
            font-family: monospace;
            white-space: pre-wrap;
            font-size: 0.85rem;
            max-height: 500px;
            overflow-y: auto;
        }

        .nav-tabs .nav-link {
            cursor: pointer;
        }
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
                                <a class="nav-link active" id="tab-a1" data-bs-toggle="tab"
                                    data-bs-target="#content-a1">Certificado A1 (Servidor)</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="tab-a3" data-bs-toggle="tab"
                                    data-bs-target="#content-a3">Certificado A3 (Navegador)</a>
                            </li>
                        </ul>

                        <div class="tab-content">
                            <!-- A1 Content -->
                            <div class="tab-pane fade show active" id="content-a1">
                                <form id="formA1">
                                    <div class="mb-3">
                                        <label class="form-label">Ambiente</label>
                                        <select class="form-select" id="endpoint">
                                            <option value="fictitious" selected>Homologação Fictícia (issnetonline)
                                            </option>
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
                                    <hr>
                                    <div class="mb-3">
                                        <label class="form-label">CPF (Opcional - Substitui CNPJ)</label>
                                        <input type="text" class="form-control" id="cpf" placeholder="Apenas números">
                                    </div>
                                    <hr>
                                    <label class="form-label">Filtro de Consulta</label>
                                    <div class="mb-2">
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="filterType"
                                                id="filterNumber" value="NUMBER">
                                            <label class="form-check-label" for="filterNumber">Número Nota</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="filterType"
                                                id="filterPeriod" value="PERIOD" checked>
                                            <label class="form-check-label" for="filterPeriod">Período</label>
                                        </div>
                                    </div>

                                    <div id="groupNumber" class="d-none">
                                        <input type="text" class="form-control mb-2" id="numero" value="1"
                                            placeholder="Número da Nota">
                                    </div>

                                    <div id="groupPeriod">
                                        <div class="row">
                                            <div class="col">
                                                <input type="date" class="form-control" id="dataInicial"
                                                    value="2026-01-01">
                                            </div>
                                            <div class="col">
                                                <input type="date" class="form-control" id="dataFinal"
                                                    value="2026-01-31">
                                            </div>
                                        </div>
                                    </div>

                                    <button type="button" class="btn btn-success w-100 mt-3"
                                        onclick="doTestA1()">Executar Teste (A1)</button>
                                </form>
                            </div>

                            <!-- A3 Content -->
                            <div class="tab-pane fade" id="content-a3">
                                <div class="alert alert-warning">
                                    <strong>Experimental:</strong> Testes com A3 requerem que o XML seja assinado via
                                    JavaScript usando uma extensão de navegador (WebPKI ou similar), pois o PHP no
                                    servidor não acessa o token USB.
                                </div>
                                <p class="small text-muted">Feature futura. Por enquanto foque no teste A1.</p>
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
                            <p class="mt-2">Consultando API...</p>
                        </div>

                        <div id="results">
                            <h5>Status HTTP: <span id="resStatus" class="badge bg-secondary">-</span></h5>

                            <ul class="nav nav-tabs mt-3" id="resTab">
                                <li class="nav-item">
                                    <a class="nav-link active" data-bs-toggle="tab" data-bs-target="#res-body">Response
                                        Body</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-bs-toggle="tab" data-bs-target="#res-headers">Headers</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-bs-toggle="tab" data-bs-target="#res-request">Request
                                        Envelope</a>
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
        // Toggle input groups
        document.querySelectorAll('input[name="filterType"]').forEach(radio => {
            radio.addEventListener('change', (e) => {
                if (e.target.value === 'NUMBER') {
                    document.getElementById('groupNumber').classList.remove('d-none');
                    document.getElementById('groupPeriod').classList.add('d-none');
                } else {
                    document.getElementById('groupNumber').classList.add('d-none');
                    document.getElementById('groupPeriod').classList.remove('d-none');
                }
            });
        });

        async function doTestA1() {
            // UI Update
            document.getElementById('loader').classList.remove('d-none');
            document.getElementById('results').classList.add('d-none');

            // Gather Data
            const payload = {
                endpoint: document.getElementById('endpoint').value,
                cnpj: document.getElementById('cnpj').value,
                cpf: document.getElementById('cpf').value,
                im: document.getElementById('im').value,
                numero: document.getElementById('numero').value,
                dataInicial: document.querySelector('input[name="filterType"]:checked').value === 'PERIOD' ? document.getElementById('dataInicial').value : '',
                dataFinal: document.querySelector('input[name="filterType"]:checked').value === 'PERIOD' ? document.getElementById('dataFinal').value : ''
            };

            try {
                const req = await fetch('api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });

                const res = await req.json();

                // Render Response
                document.getElementById('resStatus').innerText = res.http_code;
                document.getElementById('resStatus').className = 'badge ' + (res.http_code == 200 ? 'bg-success' : 'bg-danger');

                document.getElementById('bodyContent').innerText = res.response_body || res.curl_error || 'No response';
                document.getElementById('headersContent').innerText = res.headers ? res.headers.join('\n') : 'No headers';
                document.getElementById('requestContent').innerText = res.request_envelope || 'No request generated';

            } catch (err) {
                document.getElementById('bodyContent').innerText = 'Fatal JS Error: ' + err.message;
            } finally {
                document.getElementById('loader').classList.add('d-none');
                document.getElementById('results').classList.remove('d-none');
            }
        }
    </script>
</body>

</html>