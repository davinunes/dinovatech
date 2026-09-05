<?php
// dinovatech/nfse_nacional_test/index.php - Campo de Prova Interativo da NFS-e Padrão Nacional
date_default_timezone_set('America/Sao_Paulo');
$today = date('Y-m-d');
$nowUtc = date('Y-m-d\TH:i:sP');
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campo de Prova — NFS-e Padrão Nacional (Nota Control / DF)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism-okaidia.min.css" rel="stylesheet" />
    <style>
        body {
            background-color: #0f172a;
            color: #e2e8f0;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
        }

        .card-custom {
            background-color: #1e293b;
            border: 1px solid #334155;
            border-radius: 12px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3);
        }

        .form-control, .form-select {
            background-color: #0f172a;
            border: 1px solid #475569;
            color: #f8fafc;
            font-size: 0.9rem;
        }

        .form-control:focus, .form-select:focus {
            background-color: #020617;
            border-color: #3b82f6;
            color: #ffffff;
            box-shadow: 0 0 0 0.25rem rgba(59, 130, 246, 0.25);
        }

        .form-label {
            font-size: 0.82rem;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            color: #94a3b8;
            font-weight: 600;
        }

        .section-title {
            color: #38bdf8;
            font-weight: 700;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px dashed #334155;
            margin-bottom: 1rem;
        }

        pre[class*="language-"] {
            border-radius: 8px;
            max-height: 500px;
            font-size: 12px;
        }

        .nav-tabs .nav-link {
            color: #94a3b8;
            font-weight: 600;
            border: none;
            border-bottom: 2px solid transparent;
        }

        .nav-tabs .nav-link.active {
            color: #38bdf8;
            background: transparent;
            border-bottom: 2px solid #38bdf8;
        }

        .btn-glow-primary {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            border: none;
            color: white;
            box-shadow: 0 4px 15px rgba(37, 99, 235, 0.4);
            transition: all 0.2s;
        }

        .btn-glow-primary:hover {
            background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%);
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(37, 99, 235, 0.6);
            color: white;
        }

        .btn-glow-success {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            border: none;
            color: white;
            box-shadow: 0 4px 15px rgba(5, 150, 105, 0.4);
            transition: all 0.2s;
        }

        .btn-glow-success:hover {
            background: linear-gradient(135deg, #047857 0%, #065f46 100%);
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(5, 150, 105, 0.6);
            color: white;
        }
    </style>
</head>

<body class="py-4">
    <div class="container-fluid px-4">

        <!-- Top Header Navigation -->
        <header class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom border-slate-700">
            <div>
                <h1 class="h3 fw-bold text-white mb-1">
                    <i class="bi bi-shield-check text-cyan-400 me-2"></i>Campo de Prova NFS-e Padrão Nacional
                </h1>
                <p class="text-slate-400 small mb-0">
                    Laboratório Dedicado de Validação e Transmissão de Parâmetros — Nota Control / SEFAZ-DF
                </p>
            </div>
            <div class="d-flex gap-2">
                <a href="../../nfse_test/index.php" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-archive me-1"></i> Ir para Painel Legado (ABRASF)
                </a>
                <a href="../fatura_view.php?id=88" class="btn btn-outline-info btn-sm">
                    <i class="bi bi-receipt me-1"></i> Voltar ao Dinovatech
                </a>
            </div>
        </header>

        <form id="formNacionalTest">
            <div class="row g-4">
                
                <!-- LEFT COLUMN: Form Controls for Parameters -->
                <div class="col-lg-6">
                    <div class="card-custom p-4 space-y-4">

                        <!-- 1. PROTOCOLO & AMBIENTE -->
                        <div class="section-title">
                            <i class="bi bi-gear-wide-connected text-sky-400"></i> 1. Protocolo SOAP & Parâmetros do Envelope
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Ambiente WebService</label>
                                <select class="form-select" name="ambiente" id="ambiente" onchange="ajustarAmbiente()">
                                    <option value="homologacao" selected>Homologação (https://nfse.issnetonline.com.br/wsnfsenacional/homologacao/nfse.asmx)</option>
                                    <option value="producao">Produção (https://nfse.fazenda.df.gov.br/wsnfsenacional/nfse.asmx)</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Versão do Schema (`versaoDados`)</label>
                                <select class="form-select" name="versao_schema" id="versao_schema">
                                    <option value="1.00" selected>1.00 (DPS sem grupo IBS/CBS)</option>
                                    <option value="1.01">1.01 (DPS com grupo IBS/CBS)</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Formato dos Parâmetros SOAP</label>
                                <select class="form-select" name="envelope_format">
                                    <option value="cdata" selected>CDATA (<![CDATA[...]]>)</option>
                                    <option value="entities">HTML Entities (&lt;...&gt;)</option>
                                    <option value="raw">XML Direto (Sem CDATA/Entidades)</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Namespace na Tag do Método</label>
                                <select class="form-select" name="envelope_namespace">
                                    <option value="default_ns" selected>Sem Prefixo (`GerarNfse xmlns="..."`)</option>
                                    <option value="prefixed_ns">Com Prefixo (`nfse:GerarNfse`)</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Prólogo XML no Cabeçalho (&lt;?xml ...?&gt;)</label>
                                <select class="form-select" name="prologo_cabecalho" id="prologo_cabecalho">
                                    <option value="sem_prologo" selected>Sem Prólogo (&lt;cabecalho ...&gt;)</option>
                                    <option value="com_prologo">Com Prólogo (&lt;?xml ...?&gt;&lt;cabecalho ...&gt;)</option>
                                </select>
                            </div>
                        </div>

                        <!-- 2. IDENTIFICAÇÃO DA DPS -->
                        <div class="section-title">
                            <i class="bi bi-file-earmark-code text-cyan-400"></i> 2. Identificação da DPS (infDPS)
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label text-warning">Série da DPS (`serie`)</label>
                                <select class="form-select border-warning" name="serie_dps" id="serie_dps">
                                    <option value="3" selected>Série 3 (Cadastrada no Portal ISS-DF)</option>
                                    <option value="1">Série 1 (Padrão)</option>
                                    <option value="7">Série 7</option>
                                    <option value="8">Série 8</option>
                                    <option value="9">Série 9</option>
                                    <option value="10">Série 10</option>
                                    <option value="11">Série 11</option>
                                    <option value="12">Série 12</option>
                                    <option value="13">Série 13</option>
                                    <option value="14">Série 14</option>
                                    <option value="15">Série 15</option>
                                    <option value="16">Série 16</option>
                                    <option value="A">Série A</option>
                                    <option value="RPS">Série RPS</option>
                                    <option value="NF">Série NF</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Número DPS (`nDPS`)</label>
                                <input type="number" class="form-control" name="numero_dps" value="1">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Ambiente (`tpAmb`)</label>
                                <select class="form-select" name="tp_amb">
                                    <option value="2" selected>2 - Homologação</option>
                                    <option value="1">1 - Produção</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Data Competência</label>
                                <input type="date" class="form-control" name="d_compet" value="<?= $today ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Data/Hora Emissão (UTC)</label>
                                <input type="text" class="form-control" name="dh_emi" value="<?= $nowUtc ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Tipo Emitente</label>
                                <select class="form-select" name="tp_emit">
                                    <option value="1" selected>1 - Prestador</option>
                                    <option value="2">2 - Tomador</option>
                                    <option value="3">3 - Intermediário</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Cód. Município Emissor</label>
                                <input type="text" class="form-control" name="c_loc_emi" value="5300108">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Versão Aplicativo</label>
                                <input type="text" class="form-control" name="ver_aplic" value="Dinovatech_1.0">
                            </div>
                        </div>

                        <!-- 3. PRESTADOR -->
                        <div class="section-title">
                            <i class="bi bi-building text-indigo-400"></i> 3. Dados do Prestador
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">CNPJ Prestador</label>
                                <input type="text" class="form-control" name="prest_cnpj" value="61733714000101">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Inscrição Municipal</label>
                                <input type="text" class="form-control" name="prest_im" value="0841147200111">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Simples Nacional (`opSimpNac`)</label>
                                <select class="form-select" name="op_simp_nac">
                                    <option value="3" selected>3 - Optante ME/EPP</option>
                                    <option value="1">1 - Não Optante</option>
                                    <option value="2">2 - MEI</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Regime Especial (`regEspTrib`)</label>
                                <select class="form-select" name="reg_esp_trib">
                                    <option value="0" selected>0 - Nenhum</option>
                                    <option value="1">1 - Cooperativa</option>
                                    <option value="2">2 - Estimativa</option>
                                    <option value="3">3 - Microempresa Municipal</option>
                                    <option value="4">4 - Notário / Registrador</option>
                                    <option value="5">5 - Autônomo</option>
                                    <option value="6">6 - Sociedade de Profissionais</option>
                                </select>
                            </div>
                        </div>

                        <!-- 4. TOMADOR -->
                        <div class="section-title">
                            <i class="bi bi-person-badge text-emerald-400"></i> 4. Dados do Tomador (Cliente)
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">CPF / CNPJ Tomador</label>
                                <input type="text" class="form-control" name="toma_cpf_cnpj" value="01691128104">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nome / Razão Social</label>
                                <input type="text" class="form-control" name="toma_nome" value="DAVI NUNES DE FRANCA">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Logradouro</label>
                                <input type="text" class="form-control" name="toma_logradouro" value="Qi 24">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Número</label>
                                <input type="text" class="form-control" name="toma_numero" value="1">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Bairro</label>
                                <input type="text" class="form-control" name="toma_bairro" value="Taguatinga Norte">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Cód. Município IBGE</label>
                                <input type="text" class="form-control" name="toma_cmun" value="5300108">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">CEP</label>
                                <input type="text" class="form-control" name="toma_cep" value="72135902">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Telefone</label>
                                <input type="text" class="form-control" name="toma_fone" value="61996757676">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">E-mail</label>
                                <input type="email" class="form-control" name="toma_email" value="davi.nunes@gmail.com">
                            </div>
                        </div>

                        <!-- 5. SERVIÇO & VALORES -->
                        <div class="section-title">
                            <i class="bi bi-card-checklist text-amber-400"></i> 5. Serviço & Tributação
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Tributação Nacional (`cTribNac`)</label>
                                <input type="text" class="form-control" name="c_trib_nac" value="010701">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Tributação Municipal (`cTribMun`)</label>
                                <input type="text" class="form-control" name="c_trib_mun" value="107">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Código NBS (`cNBS`)</label>
                                <input type="text" class="form-control" name="c_nbs" value="115011000">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Valor Serviço (R$)</label>
                                <input type="text" class="form-control" name="v_serv" value="10.00">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Tributação ISSQN</label>
                                <select class="form-select" name="trib_issqn">
                                    <option value="1" selected>1 - Operação Tributável</option>
                                    <option value="2">2 - Imunidade</option>
                                    <option value="3">3 - Exportação</option>
                                    <option value="4">4 - Não Incidência</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Alíquota ISS (%)</label>
                                <input type="text" class="form-control" name="p_aliq" value="2.00">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Discriminação do Serviço</label>
                                <textarea class="form-control" name="x_desc_serv" rows="2">Consultoria em Tecnologia da Informacao - Teste de Transmissao</textarea>
                            </div>
                        </div>

                        <!-- ACTION BUTTONS -->
                        <div class="d-flex flex-wrap gap-2 pt-3 border-top border-slate-700">
                            <button type="button" class="btn btn-glow-primary flex-grow-1 py-2" onclick="executarTeste('preview')">
                                <i class="bi bi-code-slash me-2"></i> 1. Preview (GerarNfse)
                            </button>
                            <button type="button" class="btn btn-glow-success flex-grow-1 py-2" onclick="executarTeste('gerar')">
                                <i class="bi bi-send-check-fill me-2"></i> 2. Transmitir (GerarNfse)
                            </button>
                            <button type="button" class="btn btn-outline-info flex-grow-1 py-2" onclick="executarTeste('consultar_disponivel')">
                                <i class="bi bi-search me-2"></i> 3. Consultar DPS Disponível
                            </button>
                        </div>

                    </div>
                </div>

                <!-- RIGHT COLUMN: Interactive Console Output & Live Inspectors -->
                <div class="col-lg-6">
                    <div class="card-custom p-4 h-100">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="section-title mb-0 border-0 pb-0">
                                <i class="bi bi-terminal text-emerald-400"></i> Resposta do Teste & Inspetor XML
                            </div>
                            <div id="statusBadge" class="badge bg-secondary">Aguardando Execução</div>
                        </div>

                        <!-- Result Tabs -->
                        <ul class="nav nav-tabs mb-3" id="resultTabs" role="tablist">
                            <li class="nav-item">
                                <button class="nav-link active" id="res-status-tab" data-bs-toggle="tab" data-bs-target="#resStatus" type="button">
                                    <i class="bi bi-info-circle me-1"></i> Retorno SEFAZ
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" id="res-soap-tab" data-bs-toggle="tab" data-bs-target="#resSoap" type="button">
                                    <i class="bi bi-envelope-paper me-1"></i> Envelope SOAP
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" id="res-dps-tab" data-bs-toggle="tab" data-bs-target="#resDps" type="button">
                                    <i class="bi bi-filetype-xml me-1"></i> DPS Assinada
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" id="res-raw-tab" data-bs-toggle="tab" data-bs-target="#resRaw" type="button">
                                    <i class="bi bi-code-square me-1"></i> Resposta Bruta XML
                                </button>
                            </li>
                        </ul>

                        <div class="tab-content">
                            <!-- TAB STATUS -->
                            <div class="tab-pane fade show active" id="resStatus">
                                <div id="summaryAlert" class="alert alert-dark border border-slate-700">
                                    Preencha os parâmetros e clique em <strong>Gerar (Preview)</strong> ou <strong>Transmitir ao WebService</strong>.
                                </div>
                                <div class="mb-2 d-flex justify-content-between">
                                    <span class="small text-slate-400">ID da DPS (45 dígitos):</span>
                                    <strong id="displayDpsId" class="small text-cyan-400 font-monospace">-</strong>
                                </div>
                                <div class="mb-2 d-flex justify-content-between">
                                    <span class="small text-slate-400">Status HTTP Server:</span>
                                    <span id="displayHttpCode" class="badge bg-slate-700">-</span>
                                </div>
                                <div class="mt-3">
                                    <label class="form-label">Detalhes da Resposta / Lista de Erros:</label>
                                    <pre id="displayDetails" class="language-none" style="min-height: 180px;">-</pre>
                                </div>
                            </div>

                            <!-- TAB ENVELOPE SOAP -->
                            <div class="tab-pane fade" id="resSoap">
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="small text-slate-400">Envelope SOAP enviado via cURL:</span>
                                    <button type="button" class="btn btn-link btn-sm text-cyan-400 p-0" onclick="copiarTexto('codeSoap')">
                                        <i class="bi bi-clipboard me-1"></i>Copiar Envelope
                                    </button>
                                </div>
                                <pre><code id="codeSoap" class="language-markup"><!-- Envelope SOAP aparecerá aqui --></code></pre>
                            </div>

                            <!-- TAB DPS ASSINADA -->
                            <div class="tab-pane fade" id="resDps">
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="small text-slate-400">DPS XML gerada e assinada com XMLDSig:</span>
                                    <button type="button" class="btn btn-link btn-sm text-cyan-400 p-0" onclick="copiarTexto('codeDps')">
                                        <i class="bi bi-clipboard me-1"></i>Copiar DPS
                                    </button>
                                </div>
                                <pre><code id="codeDps" class="language-markup"><!-- DPS assinada aparecerá aqui --></code></pre>
                            </div>

                            <!-- TAB RESPOSTA BRUTA -->
                            <div class="tab-pane fade" id="resRaw">
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="small text-slate-400">Resposta Bruta retornada pelo WebService:</span>
                                    <button type="button" class="btn btn-link btn-sm text-cyan-400 p-0" onclick="copiarTexto('codeRaw')">
                                        <i class="bi bi-clipboard me-1"></i>Copiar Resposta
                                    </button>
                                </div>
                                <pre><code id="codeRaw" class="language-markup"><!-- Resposta XML bruta do servidor --></code></pre>
                            </div>
                        </div>

                    </div>
                </div>

            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/prism.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-markup.min.js"></script>
    <script>
        function ajustarAmbiente() {
            const amb = $('#ambiente').val();
            if (amb === 'producao') {
                $('#versao_schema').val('1.01');
            } else {
                $('#versao_schema').val('1.00');
            }
        }

        function executarTeste(actionType) {
            const formData = $('#formNacionalTest').serialize() + '&action=' + actionType;

            $('#statusBadge').removeClass().addClass('badge bg-warning text-dark').text('Processando...');
            $('#summaryAlert').removeClass().addClass('alert alert-warning').html('<i class="bi bi-arrow-repeat spin me-2"></i> Processando requisição...');

            $.post('api.php', formData, function (res) {
                $('#displayDpsId').text(res.dps_id || '-');
                $('#displayHttpCode').text(res.http_code ? res.http_code : 'Preview Local');

                $('#codeSoap').text(res.envelope_soap || '');
                $('#codeDps').text(res.signed_xml || '');
                $('#codeRaw').text(res.response_xml || '');
                Prism.highlightAll();

                let detailsText = res.details || res.message || '';
                if (res.erros && res.erros.length > 0) {
                    detailsText += "\n\nErros Retornados:\n" + res.erros.join("\n");
                }
                $('#displayDetails').text(detailsText || 'Nenhum detalhe adicional.');

                if (res.success) {
                    $('#statusBadge').removeClass().addClass('badge bg-success').text('SUCESSO');
                    $('#summaryAlert').removeClass().addClass('alert alert-success').html('<strong>Sucesso!</strong> ' + res.message);
                } else {
                    $('#statusBadge').removeClass().addClass('badge bg-danger').text('REJEITADO / ERRO');
                    $('#summaryAlert').removeClass().addClass('alert alert-danger').html('<strong>Erro / Rejeição:</strong> ' + res.message);
                }
            }, 'json').fail(function (jqXHR, textStatus, errorThrown) {
                $('#statusBadge').removeClass().addClass('badge bg-danger').text('FALHA AJAX');
                $('#summaryAlert').removeClass().addClass('alert alert-danger').html('<strong>Falha de comunicação:</strong> ' + textStatus + ' / ' + errorThrown);
                $('#displayDetails').text(jqXHR.responseText || 'Erro ao conectar ao script PHP local.');
            });
        }

        function copiarTexto(elementId) {
            const text = $('#' + elementId).text();
            navigator.clipboard.writeText(text).then(() => {
                alert('Conteúdo copiado para a área de transferência!');
            });
        }
    </script>
</body>

</html>
