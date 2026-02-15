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

if (!isset($_SESSION['usuario_id']) || !AppHelper::isVetMode()) {
    die("Acesso negado.");
}

include $pathDB;
$link = DBConnect();

$id_atendimento = $_REQUEST['id_atendimento'] ?? 0;
$id_modelo = $_REQUEST['id_modelo'] ?? 0;

if (!$id_atendimento || !$id_modelo) {
    die("Parametros invalidos.");
}

$id_atendimento = mysqli_real_escape_string($link, $id_atendimento);
$id_modelo = mysqli_real_escape_string($link, $id_modelo);

// 1. Fetch Attendance + Pet + Client + Vet
$q = "SELECT a.*, 
        p.nome as nome_pet, p.especie, p.raca, p.sexo, p.peso as peso_pet, p.data_nascimento as nascimento,
        c.nome as nome_tutor, c.cpf_cnpj as cpf_tutor, c.endereco as endereco_tutor,
        v.nome as nome_vet, v.crmv as crmv_vet
        FROM Atendimentos a
        LEFT JOIN Pets p ON a.id_pet = p.id_pet
        LEFT JOIN Clientes c ON p.id_cliente = c.id_cliente
        LEFT JOIN Veterinarios v ON a.id_vet = v.id_vet
        WHERE a.id_atendimento = '$id_atendimento'";

$r = DBExecute($link, $q);
if (!$r || mysqli_num_rows($r) == 0) {
    // Debug: Check if attendance exists at all
    $check = DBExecute($link, "SELECT * FROM Atendimentos WHERE id_atendimento = '$id_atendimento'");
    if (mysqli_num_rows($check) > 0) {
        $row = mysqli_fetch_assoc($check);
        die("Atendimento ID $id_atendimento encontrado, mas falha nos JOINS.<br>Pet ID: " . $row['id_pet'] . "<br>Vet ID: " . $row['id_vet']);
    } else {
        die("Atendimento ID $id_atendimento nao existe no banco de dados.");
    }
}
$dados = mysqli_fetch_assoc($r);

// 2. Fetch Model
$q_mod = "SELECT * FROM ModelosDocumentos WHERE id_modelo = '$id_modelo'";
$r_mod = DBExecute($link, $q_mod);
if (!$r_mod || mysqli_num_rows($r_mod) == 0) {
    die("Modelo nao encontrado.");
}
$modelo = mysqli_fetch_assoc($r_mod);

// 2b. Fetch Company Info for Logo
$q_conf = "SELECT * FROM ConfiguracoesEmissor LIMIT 1";
$r_conf = DBExecute($link, $q_conf);
$empresa = mysqli_fetch_assoc($r_conf);

// 3. Prepare Variables
$basePath = '../../'; // Relative path to root from modules/Vet/
$logo_url = '';
if (!empty($empresa['logo_url'])) {
    $logo_url = $basePath . $empresa['logo_url'];
} else {
    $logo_url = $basePath . 'assets/img/logo_dino.png'; // Fallback
}

// Calculate Age and Date
$idade = 'N/I';
$data_nascimento = '';
if (!empty($dados['nascimento'])) {
    $nasc = new DateTime($dados['nascimento']);
    $hoje = new DateTime();
    $diff = $hoje->diff($nasc);
    $idade = $diff->y . ' anos';
    if ($diff->y < 1)
        $idade = $diff->m . ' meses';

    $data_nascimento = date('d/m/Y', strtotime($dados['nascimento']));
}

// Map variables
$vars = [
    '{{LOGO_URL}}' => '<img src="' . $logo_url . '" style="max-height: 80px;"/>',
    '{{NOME_TUTOR}}' => $dados['nome_tutor'],
    '{{CPF_TUTOR}}' => $dados['cpf_tutor'] ?? '',
    '{{ENDERECO_TUTOR}}' => $dados['endereco_tutor'] ?? '',
    '{{NOME_PET}}' => $dados['nome_pet'],
    '{{ESPECIE_PET}}' => $dados['especie'],
    '{{RACA_PET}}' => $dados['raca'],
    '{{PELAGEM_PET}}' => '', // Campo inexistente no banco atual
    '{{NASCIMENTO_PET}}' => $data_nascimento,
    '{{IDADE_PET}}' => $idade,
    '{{PESO_PET}}' => $dados['peso'] ?? $dados['peso_pet'] ?? '',
    '{{SEXO_PET}}' => $dados['sexo'],
    '{{NOME_VET}}' => $dados['nome_vet'],
    '{{CRMV_VET}}' => $dados['crmv_vet'],
    '{{DATA_ATUAL}}' => date('d/m/Y'),
    '{{HORA_ATUAL}}' => date('H:i'),
    '{{CIDADE_DATA}}' => (isset($empresa['cidade']) && !empty($empresa['cidade']) ? $empresa['cidade'] : 'São Paulo') . ', ' . date('d/m/Y'),
];

// 4. Apply Overrides (if any)
if (isset($_REQUEST['overrides']) && is_array($_REQUEST['overrides'])) {
    foreach ($_REQUEST['overrides'] as $key => $val) {
        // Security: only allow overriding known keys if necessary, or just blindly accept
        // For flexibility, we update if key exists in defaults or is a new one (though template only has specific placeholders)
        if (array_key_exists($key, $vars)) {
            $vars[$key] = $val;
        }
    }
}

// 5. Replace Content
$conteudo_final = $modelo['conteudo'];
foreach ($vars as $key => $val) {
    $conteudo_final = str_replace($key, $val, $conteudo_final);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($modelo['titulo']) ?></title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            margin: 0;
            padding: 0;
            color: #333;
            line-height: 1.5;
        }

        .document-container {
            width: 100%;
            max-width: 210mm;
            margin: 0 auto;
            background: white;
            min-height: 297mm;
            padding: 20mm;
            /* Default padding for screen view */
            box-sizing: border-box;
        }

        @page {
            size: A4;
            margin: 10mm;
            /* Force print margin */
        }

        @media print {
            body {
                margin: 0;
                padding: 0;
                background: white;
            }

            .document-container {
                width: 100%;
                margin: 0;
                padding: 0;
                /* Let @page handle margins */
                box-shadow: none;
                border: none;
            }

            .no-print {
                display: none !important;
            }

            /* Avoid blank pages */
            html,
            body {
                height: auto;
                overflow: visible;
            }
        }

        .btn-print {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #0891b2;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            z-index: 9999;
        }

        .btn-print:hover {
            background: #0e7490;
        }
    </style>
</head>

<body>
    <button onclick="window.print()" class="btn-print no-print">Imprimir / Salvar PDF</button>
    <div class="document-container">
        <?= $conteudo_final ?>
    </div>
    <script>
        window.onload = function () {
            setTimeout(function () { window.print(); }, 500);
        }
    </script>
</body>

</html>