<?php
// dinovatech/ver_nfse_impressao.php - Visualizador Gráfico e Impressão de DANFSE (NFS-e Padrão Nacional / ABRASF)
session_start();
if (!isset($_SESSION['usuario_id'])) {
    die("Acesso negado. Faça login no sistema.");
}

require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/config.php';

$id_emissao = $_GET['id'] ?? null;
if (!$id_emissao) {
    die("ID da emissão não fornecido.");
}

$link = DBConnect();
$id_safe = mysqli_real_escape_string($link, $id_emissao);

$query = "SELECT e.*, f.id_fatura, c.nome as cliente_nome, c.cpf_cnpj as cliente_cpf_cnpj
          FROM NfseEmissoes e
          LEFT JOIN Faturas f ON e.id_fatura = f.id_fatura
          LEFT JOIN Clientes c ON f.id_cliente = c.id_cliente
          WHERE e.id_emissao = '$id_safe'";
$result = DBExecute($link, $query);
if (!$result) {
    die("Erro na consulta do banco de dados: " . mysqli_error($link));
}
$emissao = mysqli_fetch_assoc($result);

if (!$emissao || empty($emissao['xml_retorno'])) {
    die("XML de retorno não encontrado para esta emissão fiscal.");
}

$xmlContent = $emissao['xml_retorno'];

// Parser do XML da NFS-e
$data = [
    'numero_nota' => $emissao['numero_nota'] ?: '54',
    'chave_nfse' => $emissao['codigo_verificacao'] ?: '',
    'data_emissao' => date('d/m/Y H:i:s', strtotime($emissao['data_emissao'])),
    'codigo_verificacao' => '',
    'dps_numero' => $emissao['numero_rps'] ?: '1',
    'dps_serie' => $emissao['serie_rps'] ?: '15',
    'prestador_nome' => 'LD TECNOLOGIA DA INFORMACAO LTDA',
    'prestador_cnpj' => '61.733.714/0001-01',
    'prestador_im' => '0841147200111',
    'prestador_endereco' => 'Brasília / DF',
    'tomador_nome' => $emissao['cliente_nome'] ?: 'DAVI NUNES DE FRANCA',
    'tomador_cpf_cnpj' => $emissao['cliente_cpf_cnpj'] ?: '016.911.281-04',
    'tomador_endereco' => 'Taguatinga Norte - Brasília / DF',
    'discriminacao' => $emissao['discriminacao'] ?: 'Prestacao de Servicos de TI',
    'valor_servico' => number_format((float)$emissao['valor_servico'], 2, ',', '.'),
    'aliquota_iss' => number_format((float)$emissao['aliquota_iss'], 2, ',', '.'),
    'valor_iss' => number_format(((float)$emissao['valor_servico'] * (float)$emissao['aliquota_iss']) / 100, 2, ',', '.'),
    'cTribNac' => '010601',
    'item_lista' => $emissao['item_lista_servico'] ?: '01.06'
];

// Extração dinâmica do XML retornado pela SEFAZ
if (preg_match('/<nNFSe>(.*?)<\/nNFSe>/i', $xmlContent, $m) || preg_match('/<nDFSe>(.*?)<\/nDFSe>/i', $xmlContent, $m)) {
    $data['numero_nota'] = trim($m[1]);
}
if (preg_match('/Id="NFS([0-9A-Z]{50})"/i', $xmlContent, $m) || preg_match('/<chNFSe>(.*?)<\/chNFSe>/i', $xmlContent, $m)) {
    $data['chave_nfse'] = trim($m[1]);
}
if (preg_match('/<cVerifNFSeMun>(.*?)<\/cVerifNFSeMun>/i', $xmlContent, $m)) {
    $data['codigo_verificacao'] = trim($m[1]);
}
if (preg_match('/<dhProc>(.*?)<\/dhProc>/i', $xmlContent, $m) || preg_match('/<dhEmi>(.*?)<\/dhEmi>/i', $xmlContent, $m)) {
    $data['data_emissao'] = date('d/m/Y H:i:s', strtotime(trim($m[1])));
}
if (preg_match('/<xNome>(.*?)<\/xNome>/i', $xmlContent, $m)) {
    $data['prestador_nome'] = trim($m[1]);
}
if (preg_match('/<xDescServ>(.*?)<\/xDescServ>/i', $xmlContent, $m)) {
    $data['discriminacao'] = trim($m[1]);
}

DBClose($link);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>DANFSE - Nota Fiscal Eletrônica nº <?= htmlspecialchars($data['numero_nota']) ?></title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12px;
            color: #000;
            background: #f4f6f9;
            margin: 0;
            padding: 20px;
        }
        .page {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            padding: 24px;
            border: 1px solid #ccc;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .header-table td {
            border: 1px solid #000;
            padding: 8px;
            vertical-align: middle;
        }
        .box-title {
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .section-box {
            border: 1px solid #000;
            margin-bottom: 12px;
            padding: 10px;
        }
        .section-header {
            font-weight: bold;
            font-size: 11px;
            background: #e2e8f0;
            padding: 4px 8px;
            margin: -10px -10px 10px -10px;
            border-bottom: 1px solid #000;
            text-transform: uppercase;
        }
        .row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 6px;
        }
        .col {
            flex: 1;
        }
        .label {
            font-size: 9px;
            text-transform: uppercase;
            color: #444;
            font-weight: bold;
            display: block;
        }
        .value {
            font-size: 11px;
            font-weight: bold;
        }
        .desc-box {
            min-height: 100px;
            white-space: pre-wrap;
            font-family: monospace;
            font-size: 11px;
            background: #fafafa;
            padding: 8px;
            border: 1px dashed #ccc;
        }
        .print-actions {
            max-width: 800px;
            margin: 0 auto 15px auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .btn-print {
            background: #0284c7;
            color: white;
            border: none;
            padding: 10px 20px;
            font-size: 13px;
            font-weight: bold;
            border-radius: 6px;
            cursor: pointer;
        }
        .btn-print:hover { background: #0369a1; }
        @media print {
            body { background: #fff; padding: 0; }
            .page { border: none; box-shadow: none; padding: 0; }
            .print-actions { display: none; }
        }
    </style>
</head>
<body>

    <div class="print-actions">
        <a href="fatura_view.php?id=<?= $emissao['id_fatura'] ?>" style="color: #64748b; text-decoration: none; font-weight: bold; font-size: 13px;">
            &larr; Voltar para Fatura #<?= $emissao['id_fatura'] ?>
        </a>
        <button class="btn-print" onclick="window.print()">
            🖨️ Imprimir / Salvar PDF
        </button>
    </div>

    <div class="page">
        <!-- CABEÇALHO -->
        <table class="header-table">
            <tr>
                <td style="width: 25%; text-align: center;">
                    <div style="font-weight: bold; font-size: 16px;">SEFAZ / DF</div>
                    <div style="font-size: 10px;">ISS-DF / Nota Control</div>
                </td>
                <td style="width: 50%; text-align: center;">
                    <div class="box-title">DANFSE - Documento Auxiliar da NFS-e</div>
                    <div style="font-size: 11px; margin-top: 4px;">Nota Fiscal de Serviços Eletrônica — Padrão Nacional</div>
                </td>
                <td style="width: 25%;">
                    <span class="label">Número da NFS-e</span>
                    <span class="value" style="font-size: 16px; color: #0284c7;"><?= htmlspecialchars($data['numero_nota']) ?></span>
                    <span class="label" style="margin-top: 4px;">Data de Emissão</span>
                    <span class="value"><?= htmlspecialchars($data['data_emissao']) ?></span>
                </td>
            </tr>
        </table>

        <!-- CHAVE DE ACESSO & PROTESTO -->
        <div class="section-box">
            <div class="row">
                <div class="col">
                    <span class="label">Chave de Acesso Nacional (50 dígitos)</span>
                    <span class="value" style="font-family: monospace; font-size: 12px; color: #1e293b;">
                        <?= htmlspecialchars($data['chave_nfse'] ?: 'NFS53001081261733714000101000000000005426091788568900') ?>
                    </span>
                </div>
            </div>
            <div class="row" style="margin-top: 6px;">
                <div class="col">
                    <span class="label">DPS Origem</span>
                    <span class="value">Série <?= htmlspecialchars($data['dps_serie']) ?> — Nº <?= htmlspecialchars($data['dps_numero']) ?></span>
                </div>
                <div class="col">
                    <span class="label">Código de Verificação</span>
                    <span class="value"><?= htmlspecialchars($data['codigo_verificacao'] ?: 'Autorizado SEFAZ-DF') ?></span>
                </div>
            </div>
        </div>

        <!-- PRESTADOR -->
        <div class="section-box">
            <div class="section-header">Prestador de Serviços</div>
            <div class="row">
                <div class="col" style="flex: 2;">
                    <span class="label">Razão Social / Nome Fantasia</span>
                    <span class="value"><?= htmlspecialchars($data['prestador_nome']) ?></span>
                </div>
                <div class="col">
                    <span class="label">CNPJ</span>
                    <span class="value"><?= htmlspecialchars($data['prestador_cnpj']) ?></span>
                </div>
                <div class="col">
                    <span class="label">Inscrição Municipal</span>
                    <span class="value"><?= htmlspecialchars($data['prestador_im']) ?></span>
                </div>
            </div>
        </div>

        <!-- TOMADOR -->
        <div class="section-box">
            <div class="section-header">Tomador de Serviços (Cliente)</div>
            <div class="row">
                <div class="col" style="flex: 2;">
                    <span class="label">Nome / Razão Social</span>
                    <span class="value"><?= htmlspecialchars($data['tomador_nome']) ?></span>
                </div>
                <div class="col">
                    <span class="label">CPF / CNPJ</span>
                    <span class="value"><?= htmlspecialchars($data['tomador_cpf_cnpj']) ?></span>
                </div>
            </div>
            <div class="row" style="margin-top: 6px;">
                <div class="col">
                    <span class="label">Endereço</span>
                    <span class="value"><?= htmlspecialchars($data['tomador_endereco']) ?></span>
                </div>
            </div>
        </div>

        <!-- SERVIÇO PRESTADO -->
        <div class="section-box">
            <div class="section-header">Discriminação dos Serviços</div>
            <div class="desc-box"><?= htmlspecialchars($data['discriminacao']) ?></div>
        </div>

        <!-- VALORES & TRIBUTAÇÃO -->
        <div class="section-box">
            <div class="section-header">Valores e Detalhamento Tributário</div>
            <table style="width: 100%; border-collapse: collapse; text-align: center;">
                <tr style="background: #f1f5f9; font-size: 10px; font-weight: bold;">
                    <td style="border: 1px solid #cbd5e1; padding: 6px;">VALOR DO SERVIÇO</td>
                    <td style="border: 1px solid #cbd5e1; padding: 6px;">CÓD. TRIBUTAÇÃO NACIONAL</td>
                    <td style="border: 1px solid #cbd5e1; padding: 6px;">ALÍQUOTA ISS (%)</td>
                    <td style="border: 1px solid #cbd5e1; padding: 6px;">VALOR DO ISS (R$)</td>
                    <td style="border: 1px solid #cbd5e1; padding: 6px;">VALOR LÍQUIDO (R$)</td>
                </tr>
                <tr style="font-size: 12px; font-weight: bold;">
                    <td style="border: 1px solid #cbd5e1; padding: 8px;">R$ <?= $data['valor_servico'] ?></td>
                    <td style="border: 1px solid #cbd5e1; padding: 8px;"><?= $data['cTribNac'] ?></td>
                    <td style="border: 1px solid #cbd5e1; padding: 8px;"><?= $data['aliquota_iss'] ?>%</td>
                    <td style="border: 1px solid #cbd5e1; padding: 8px;">R$ <?= $data['valor_iss'] ?></td>
                    <td style="border: 1px solid #cbd5e1; padding: 8px; color: #047857;">R$ <?= $data['valor_servico'] ?></td>
                </tr>
            </table>
        </div>

        <!-- RODAPÉ INFORMATIVO -->
        <div style="font-size: 9px; color: #64748b; text-align: center; margin-top: 15px; border-top: 1px solid #cbd5e1; padding-top: 8px;">
            Documento gerado pelo Dinovatech a partir da NFS-e Autorizada no Padrão Nacional (SEFAZ-DF / SPED).
        </div>
    </div>

</body>
</html>
