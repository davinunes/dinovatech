<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$pathConfig = __DIR__ . '/../../config.php';
$pathHelper = __DIR__ . '/../../helpers/AppHelper.php';
$pathDB = __DIR__ . '/../../../database.php';

if (!file_exists($pathConfig) || !file_exists($pathDB)) {
    die("Erro critico: Arquivos de configuracao nao encontrados.");
}

require_once $pathConfig;
require_once $pathHelper;
require_once __DIR__ . '/../../helpers/PdfHelper.php';

if (isset($_REQUEST['pdf']) && $_REQUEST['pdf'] == '1') {
    ob_start();
}

$usuario_logado = isset($_SESSION['usuario_id']);
$cliente_logado = isset($_SESSION['cliente_id']);

if (!$usuario_logado && !$cliente_logado) {
    die("Acesso negado.");
}

include $pathDB;
$link = DBConnect();

$id = $_REQUEST['id'] ?? 0;
$id = mysqli_real_escape_string($link, $id);

if (!$id) {
    die("ID invalido.");
}

// Fetch Document
$query = "SELECT * FROM DocumentosEmitidos WHERE id_documento_emitido = '$id'";
$result = DBExecute($link, $query);

if (!$result || mysqli_num_rows($result) == 0) {
    die("Documento nao encontrado.");
}

$doc = mysqli_fetch_assoc($result);

// Validação de segurança para acesso do cliente
if ($cliente_logado && !$usuario_logado && $doc['id_cliente'] != $_SESSION['cliente_id']) {
    die("Acesso negado.");
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>
        <?= htmlspecialchars($doc['titulo']) ?>
    </title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            margin: 0;
            padding: 0;
            color: #333;
            line-height: 1.5;
            background: #f3f4f6;
        }

        .document-container {
            width: 100%;
            max-width: 210mm;
            margin: 20px auto;
            background: white;
            min-height: 297mm;
            padding: 20mm;
            box-sizing: border-box;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        @page {
            size: A4;
            margin: 10mm;
        }

        @media print {
            body {
                background: white;
                margin: 0;
            }

            .document-container {
                width: 100%;
                margin: 0;
                padding: 0;
                box-shadow: none;
                border: none;
                min-height: 0;
            }

            .no-print {
                display: none !important;
            }
        }

        .btn-action-group {
            position: fixed;
            top: 20px;
            right: 20px;
            display: flex;
            gap: 10px;
            z-index: 9999;
        }

        .btn-pdf {
            background: #059669;
            color: white;
            padding: 10px 18px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            display: inline-flex;
            align-items: center;
            font-size: 14px;
        }

        .btn-pdf:hover {
            background: #047857;
        }

        .btn-print {
            background: #0891b2;
            color: white;
            padding: 10px 18px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            font-size: 14px;
        }

        .btn-print:hover {
            background: #0e7490;
        }
    </style>
</head>

<body>
    <div class="btn-action-group no-print">
        <a href="<?= htmlspecialchars($_SERVER['REQUEST_URI'] . (strpos($_SERVER['REQUEST_URI'], '?') !== false ? '&' : '?') . 'pdf=1') ?>" target="_blank" class="btn-pdf">
            Baixar PDF
        </a>
        <button onclick="window.print()" class="btn-print">Imprimir</button>
    </div>

    <div class="document-container">
        <?= $doc['conteudo_html'] ?>
    </div>

<?php
if (isset($_REQUEST['pdf']) && $_REQUEST['pdf'] == '1') {
    $htmlContent = ob_get_clean();
    $safeTitle = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $doc['titulo'] ?: 'documento');
    $filename = "{$safeTitle}_" . date('Ymd') . ".pdf";
    PdfHelper::streamPdf($htmlContent, $filename, true);
}
?>
</body>

</html>