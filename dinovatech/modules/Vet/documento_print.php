<?php
session_start();
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../helpers/AppHelper.php';

if (!isset($_SESSION['usuario_id']) || !AppHelper::isVetMode()) {
    die("Acesso negado.");
}

include "../../../database.php";
$link = DBConnect();

$id_atendimento = $_GET['id_atendimento'] ?? 0;
$id_modelo = $_GET['id_modelo'] ?? 0;

$id_atendimento = mysqli_real_escape_string($link, $id_atendimento);
$id_modelo = mysqli_real_escape_string($link, $id_modelo);

// 1. Fetch Attendance + Pet + Client + Vet
$q = "SELECT a.*, 
        p.nome as nome_pet, p.especie, p.raca, p.sexo, p.cor as pelagem, p.nascimento, p.peso,
        c.nome as nome_tutor, c.cpf as cpf_tutor, c.endereco as endereco_tutor,
        v.nome as nome_vet, v.crmv as crmv_vet
      FROM Atendimentos a
      JOIN Pets p ON a.id_pet = p.id_pet
      JOIN Clientes c ON p.id_cliente = c.id_cliente
      LEFT JOIN Veterinarios v ON a.id_vet = v.id_vet
      WHERE a.id_atendimento = '$id_atendimento'";

$r = DBExecute($link, $q);
if (!$r || mysqli_num_rows($r) == 0) {
    die("Atendimento não encontrado.");
}
$dados = mysqli_fetch_assoc($r);

// 2. Fetch Model
$q_mod = "SELECT * FROM ModelosDocumentos WHERE id_modelo = '$id_modelo'";
$r_mod = DBExecute($link, $q_mod);
if (!$r_mod || mysqli_num_rows($r_mod) == 0) {
    die("Modelo não encontrado.");
}
$modelo = mysqli_fetch_assoc($r_mod);

// 2b. Fetch Company Info for Logo
$q_conf = "SELECT * FROM ConfiguracoesEmissor LIMIT 1";
$r_conf = DBExecute($link, $q_conf);
$empresa = mysqli_fetch_assoc($r_conf);

// 3. Prepare Variables
$logo_url = '';
if (!empty($empresa['logo_url'])) {
    $logo_url = $basePath . $empresa['logo_url'];
} else {
    $logo_url = $basePath . 'assets/img/logo_dino.png'; // Fallback
}

// Calculate Age
$idade = 'N/I';
if (!empty($dados['nascimento'])) {
    $nasc = new DateTime($dados['nascimento']);
    $hoje = new DateTime();
    $diff = $hoje->diff($nasc);
    $idade = $diff->y . ' anos';
    if ($diff->y < 1)
        $idade = $diff->m . ' meses';
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
    '{{PELAGEM_PET}}' => $dados['pelagem'] ?? '',
    '{{IDADE_PET}}' => $idade,
    '{{PESO_PET}}' => $dados['peso'] ?? $dados['peso_tmp'] ?? '', // peso_tmp from attendance if exists, else pet
    '{{SEXO_PET}}' => $dados['sexo'],
    '{{NOME_VET}}' => $dados['nome_vet'],
    '{{CRMV_VET}}' => $dados['crmv_vet'],
    '{{DATA_ATUAL}}' => date('d/m/Y'),
    '{{HORA_ATUAL}}' => date('H:i'),
    '{{CIDADE_DATA}}' => 'São Paulo, ' . date('d/m/Y'), // Could make city dynamic later
];

// 4. Replace Content
$conteudo_final = $modelo['conteudo'];
foreach ($vars as $key => $val) {
    $conteudo_final = str_replace($key, $val, $conteudo_final);
}

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>
        <?= htmlspecialchars($modelo['titulo']) ?>
    </title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
            line-height: 1.5;
        }

        .document-container {
            max-width: 210mm;
            /* A4 width */
            margin: 0 auto;
            background: white;
            min-height: 297mm;
        }

        /* Print Styles */
        @media print {
            body {
                margin: 0;
                padding: 0;
                background: white;
            }

            .document-container {
                width: 100%;
                margin: 0;
                box-shadow: none;
            }

            .no-print {
                display: none;
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
        }

        .btn-print:hover {
            background: #0e7490;
        }
    </style>
</head>

<body>

    <button onclick="window.print()" class="btn-print no-print">Imprimir / Salvar PDF</button>

    <div class="document-container">
        <!-- Content Injected Here -->
        <?= $conteudo_final ?>
    </div>

    <script>
        // Auto print on load
        window.onload = function () {
            setTimeout(function () {
                window.print();
            }, 500);
        }
    </script>

</body>

</html>