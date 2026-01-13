<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel NFS-e DF (DInova)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/themes/prism-okaidia.min.css" rel="stylesheet" />
    <style>
        body {
            background-color: #f4f6f9;
            font-family: 'Segoe UI', system-ui, sans-serif;
        }

        .card {
            border: none;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .nav-tabs .nav-link {
            color: #6c757d;
            font-weight: 500;
        }

        .nav-tabs .nav-link.active {
            color: #0d6efd;
            border-bottom: 2px solid #0d6efd;
        }

        pre {
            background: #272822;
            color: #f8f8f2;
            padding: 15px;
            border-radius: 6px;
            max-height: 600px;
            overflow-y: auto;
            font-size: 13px;
        }

        .badge-status {
            font-size: 0.9em;
            padding: 0.5em 1em;
        }
    </style>
</head>

<body class="py-5">
    <div class="container">

        <header class="mb-5 text-center">
            <h1 class="display-6 fw-bold text-primary">Painel de Integração NFS-e DF</h1>
            <p class="text-muted">Ambiente de Validação e Testes - Digital Inovation</p>
        </header>

        <!-- Environment Config -->
        <div class="card mb-4 border-start border-4 border-warning">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-3">
                        <strong class="text-uppercase text-muted small">Ambiente de Destino</strong>
                    </div>
                    <div class="col-md-6">
                        <select class="form-select" id="endpoint">
                            <option value="fictitious" selected>Homologação Fictícia (Sem WAF)</option>
                            <option value="official">Homologação Oficial (WAF Blocked)</option>
                        </select>
                    </div>
                    <div class="col-md-3 text-end">
                        <span class="badge bg-success bg-opacity-10 text-success border border-success">Certificado A1
                            Carregado</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Workspace -->
        <div class="card">
            <div class="card-header bg-white pt-3 px-4">
                <ul class="nav nav-tabs card-header-tabs" id="mainTab" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active" id="emitir-tab" data-bs-toggle="tab" data-bs-target="#emitir"
                            type="button">
                            <i class="bi bi-file-earmark-plus me-2"></i>Emitir Nota (Simulação)
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" id="consultar-tab" data-bs-toggle="tab" data-bs-target="#consultar"
                            type="button">
                            <i class="bi bi-search me-2"></i>Consultar / Recuperar
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" id="cadastro-tab" data-bs-toggle="tab" data-bs-target="#cadastro"
                            type="button">
                            <i class="bi bi-person-lines-fill me-2"></i>Dados Cadastrais
                        </button>
                    </li>
                </ul>
            </div>

            <div class="card-body p-4 bg-white">
                <div class="tab-content">

                    <!-- TAB: EMITIR (GERAR) -->
                    <div class="tab-pane fade show active" id="emitir" role="tabpanel">
                        <div class="row g-4">
                            <!-- Service Data -->
                            <div class="col-md-12">
                                <h6 class="text-uppercase text-muted border-bottom pb-2 mb-3">Dados da Emissão (RPS)
                                </h6>
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label small fw-bold">Número RPS</label>
                                        <input type="text" class="form-control" id="numero_rps" placeholder="Auto"
                                            title="Deixe vazio para gerar aleatório">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small fw-bold">Série</label>
                                        <input type="text" class="form-control" id="serie_rps" value="A">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small fw-bold">Tipo</label>
                                        <select class="form-select" id="tipo_rps">
                                            <option value="1" selected>1 - RPS</option>
                                            <option value="2">2 - Nota Conjugada</option>
                                            <option value="3">3 - Cupom</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-12">
                                <h6 class="text-uppercase text-muted border-bottom pb-2 mb-3 mt-3">Dados do Serviço
                                    Prestado</h6>
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label small fw-bold">Valor (R$)</label>
                                        <div class="input-group">
                                            <span class="input-group-text">R$</span>
                                            <input type="text" class="form-control" id="valor" value="10.00">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small fw-bold">Item (LC 116)</label>
                                        <input type="text" class="form-control" id="item_lista" value="01.07">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small fw-bold">CNAE</label>
                                        <input type="text" class="form-control" id="codigo_cnae" value="6204000">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small fw-bold">Cód. Tributação</label>
                                        <input type="text" class="form-control" id="codigo_tributacao" value="7">
                                    </div>
                                    <!-- NEW: NBS & Aliquota -->
                                    <div class="col-md-3">
                                        <label class="form-label small fw-bold">Cód. NBS</label>
                                        <input type="text" class="form-control" id="codigo_nbs" value="115080000">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small fw-bold">Alíquota (%)</label>
                                        <input type="text" class="form-control" id="aliquota" value="0">
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label small fw-bold">Simples Nacional</label>
                                        <select class="form-select" id="optante_simples">
                                            <option value="1">1 - Sim</option>
                                            <option value="2" selected>2 - Não</option>
                                        </select>
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label small fw-bold">Discriminação</label>
                                        <textarea class="form-control" id="discriminacao"
                                            rows="2">Suporte e Manutenção Técnica em Informática - Teste de Integração</textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- Tomador Data -->
                            <div class="col-md-12">
                                <h6 class="text-uppercase text-muted border-bottom pb-2 mb-3 mt-2">Dados do Cliente
                                    (Tomador)</h6>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">CPF do Tomador</label>
                                        <input type="text" class="form-control" id="cpf_tomador" value="01691128104">
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 text-end">
                                <button class="btn btn-primary btn-lg px-5" onclick="testarAPI('gerar')">
                                    <i class="bi bi-send-fill me-2"></i>Emitir Nota (GerarNfse)
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- TAB: CONSULTAR -->
                    <div class="tab-pane fade" id="consultar" role="tabpanel">
                        <div class="row justify-content-center">
                            <div class="col-md-8">
                                <div class="alert alert-info border-0 bg-info bg-opacity-10">
                                    <i class="bi bi-info-circle me-2"></i>
                                    A consulta busca por <strong>Data de Emissão</strong> (Competência).
                                </div>
                                <div class="card card-body bg-light border-0">
                                    <div class="row g-3 align-items-end">
                                        <div class="col-md-5">
                                            <label class="form-label fw-bold">Data Inicial</label>
                                            <input type="date" class="form-control" id="dataInicial" value="2026-01-01">
                                        </div>
                                        <div class="col-md-5">
                                            <label class="form-label fw-bold">Data Final</label>
                                            <input type="date" class="form-control" id="dataFinal"
                                                value="<?php echo date('Y-m-d'); ?>">
                                        </div>
                                        <div class="col-md-2">
                                            <button class="btn btn-dark w-100" onclick="testarAPI('consultar')">
                                                <i class="bi bi-search"></i> Buscar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB: CADASTRO -->
                    <div class="tab-pane fade" id="cadastro" role="tabpanel">
                        <div class="row justify-content-center">
                            <div class="col-md-8">
                                <div class="alert alert-warning border-0 bg-warning bg-opacity-10">
                                    <i class="bi bi-lightbulb me-2"></i>
                                    Utilize esta opção para descobrir os códigos corretos (CNAE, NBS, Tributação)
                                    vinculados à sua inscrição.
                                </div>
                                <div class="card card-body bg-light border-0 text-center py-5">
                                    <h5 class="card-title mb-4">Consultar Dados do Prestador</h5>
                                    <p class="text-muted mb-4">Esta consulta recupera as atividades e códigos
                                        autorizados para o CNPJ/IM configurados.</p>
                                    <button class="btn btn-dark btn-lg" onclick="testarAPI('consultar_cadastral')">
                                        <i class="bi bi-search me-2"></i>Consultar Cadastro
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- RESULTS -->
        <div id="resultArea" class="mt-5" style="display:none;">
            <div class="card border-dark">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                    <div>
                        <i class="bi bi-terminal me-2"></i>Log de Execução
                    </div>
                    <span id="resultStatus" class="badge">Aguardando...</span>
                </div>
                <div class="card-body p-0">
                    <ul class="nav nav-tabs nav-tabs-dark bg-secondary bg-opacity-10 px-3 pt-2" id="resTab"
                        role="tablist">
                        <li class="nav-item">
                            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#res-response"
                                type="button">Resposta (XML)</button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#res-request"
                                type="button">Request (Envelope)</button>
                        </li>
                    </ul>
                    <div class="tab-content p-0 position-relative">
                        <div class="position-absolute top-0 end-0 m-2" style="z-index: 10;">
                            <button class="btn btn-sm btn-outline-light bg-dark" onclick="copyToClipboard()">
                                <i class="bi bi-clipboard"></i> Copiar
                            </button>
                        </div>
                        <div class="tab-pane fade show active" id="res-response">
                            <pre
                                class="m-0 rounded-0 border-0"><code id="xmlResponse" class="language-xml"></code></pre>
                        </div>
                        <div class="tab-pane fade" id="res-request">
                            <pre class="m-0 rounded-0 border-0"><code id="xmlRequest" class="language-xml"></code></pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/prism.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/components/prism-xml-doc.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/components/prism-xml.min.js"></script>
    <script>
        function copyToClipboard() {
            const activeTab = document.querySelector('#resTab .active').getAttribute('data-bs-target');
            const codeId = activeTab === '#res-response' ? 'xmlResponse' : 'xmlRequest';
            const text = document.getElementById(codeId).innerText;
            navigator.clipboard.writeText(text).then(() => {
                alert('Conteúdo copiado!');
            });
        }

        async function testarAPI(method) {
            const resultArea = document.getElementById('resultArea');
            const resultStatus = document.getElementById('resultStatus');
            const xmlResponse = document.getElementById('xmlResponse');
            const xmlRequest = document.getElementById('xmlRequest');

            resultArea.style.display = 'block';
            resultStatus.className = 'badge bg-warning text-dark';
            resultStatus.innerText = 'ENVIANDO...';
            xmlResponse.innerText = 'Processando requisição...';
            xmlRequest.innerText = 'Gerando envelope...';

            // Fixed Protocol: Proven Protocol (ID + URI Ref + No Prefix + Entities)
            const payload = {
                action: 'direct_a1',
                method: method,
                endpoint: document.getElementById('endpoint').value,
                variation: 'proven_protocol',

                // Common Params
                cnpj: '61733714000101',
                im: '0841147200111',

                // Consultar Params
                dataInicial: document.getElementById('dataInicial').value,
                dataFinal: document.getElementById('dataFinal').value,

                // Gerar Params
                valor: document.getElementById('valor').value,
                item_lista: document.getElementById('item_lista').value,
                codigo_cnae: document.getElementById('codigo_cnae').value,
                codigo_tributacao: document.getElementById('codigo_tributacao').value,
                discriminacao: document.getElementById('discriminacao').value,
                cpf_tomador: document.getElementById('cpf_tomador').value,
                numero_rps: document.getElementById('numero_rps').value,
                serie_rps: document.getElementById('serie_rps').value,
                tipo_rps: document.getElementById('tipo_rps').value,
                optante_simples: document.getElementById('optante_simples').value,
                // NEW
                codigo_nbs: document.getElementById('codigo_nbs').value,
                aliquota: document.getElementById('aliquota').value
            };

            try {
                const response = await fetch('api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });

                const data = await response.json();

                const formatXML = (xml) => {
                    if (!xml) return '';
                    let formatted = '';
                    let reg = /(>)(<)(\/*)/g;
                    xml = xml.replace(reg, '$1\r\n$2$3');
                    return xml;
                };

                xmlResponse.innerText = formatXML(data.response_body || data.curl_error || 'Sem resposta');
                xmlRequest.innerText = formatXML(data.request_envelope || 'Sem request');

                if (data.status === 'success') {
                    resultStatus.className = 'badge bg-success';
                    resultStatus.innerText = 'HTTP 200 OK';
                } else {
                    resultStatus.className = 'badge bg-danger';
                    resultStatus.innerText = 'ERRO ' + (data.http_code || 'API');
                }

            } catch (error) {
                resultStatus.className = 'badge bg-danger';
                resultStatus.innerText = 'FALHA JS';
                xmlResponse.innerText = 'Erro Javascript: ' + error.message;
            }
    }
    </script>
    </script>
    <script>
        function formatXml(xml) {
             let formatted = '';
             let pad = 0;
             const nodes = xml.replace(/>\s*</g, '><').replace(/</g, '\n<').split('\n');
             
             for (let node of nodes) {
                 if (!node.trim()) continue;
                 let indent = 0;
                 if (node.match(/^<\//)) {
                     pad = Math.max(0, pad - 1); // Closing tag
                 } else if (node.match(/^<[^/].*[^/]>$/) && !node.match(/^<\?/) && !node.match(/^<!/)) {
                     indent = 1; // Opening tag
                 }
                 
                 formatted += '  '.repeat(pad) + node + '\n';
                 pad += indent;
             }
             return formatted;
        }
    </script>
</body>
</html>