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

if (!isset($_SESSION['usuario_id'])) {
    die("Acesso negado.");
}

include $pathDB;
$link = DBConnect();

$id_atendimento = $_REQUEST['id_atendimento'] ?? 0;
$id_recorrencia = $_REQUEST['id_recorrencia'] ?? 0;
$id_modelo = $_REQUEST['id_modelo'] ?? 0;

if ((!$id_atendimento && !$id_recorrencia) || !$id_modelo) {
    die("Parametros invalidos.");
}

$id_atendimento = mysqli_real_escape_string($link, $id_atendimento);
$id_recorrencia = mysqli_real_escape_string($link, $id_recorrencia);
$id_modelo = mysqli_real_escape_string($link, $id_modelo);

$dados = [];

if ($id_atendimento) {
    // 1. Fetch Attendance + Pet + Client + Vet
    $q = "SELECT a.*, p.id_cliente as pet_id_cliente, 
            p.nome as nome_pet, p.especie, p.raca, p.sexo, p.peso as peso_pet, p.data_nascimento as nascimento,
            c.id_cliente as client_id_final, c.nome as nome_tutor, c.cpf_cnpj as cpf_tutor, c.endereco as endereco_tutor, c.email as email_tutor, c.telefone as telefone_tutor,
            v.nome as nome_vet, v.crmv as crmv_vet, v.url_assinatura
            FROM Atendimentos a
            LEFT JOIN Pets p ON a.id_pet = p.id_pet
            LEFT JOIN Clientes c ON p.id_cliente = c.id_cliente
            LEFT JOIN Veterinarios v ON a.id_vet = v.id_vet
            WHERE a.id_atendimento = '$id_atendimento'";

    $r = DBExecute($link, $q);
    if (!$r || mysqli_num_rows($r) == 0) {
        die("Atendimento ID $id_atendimento nao encontrado.");
    }
    $dados = mysqli_fetch_assoc($r);
    $dados['tipo_origem'] = 'atendimento';
    $dados['id_cliente'] = $dados['pet_id_cliente'] ?? $dados['client_id_final']; // Ensure id_cliente is set
} elseif ($id_recorrencia) {
    // 1. Fetch Recurrence + Client + Service
    $q = "SELECT r.*, r.id_cliente as rec_id_cliente,
            c.id_cliente as client_id_final, c.nome as nome_tutor, c.cpf_cnpj as cpf_tutor, c.endereco as endereco_tutor, c.email as email_tutor, c.telefone as telefone_tutor,
            s.nome_servico
            FROM Recorrencias r
            LEFT JOIN Clientes c ON r.id_cliente = c.id_cliente
            LEFT JOIN Servicos s ON r.id_servico = s.id_servico
            WHERE r.id_recorrencia = '$id_recorrencia'";

    $r = DBExecute($link, $q);
    if (!$r || mysqli_num_rows($r) == 0) {
        die("Contrato/Recorrencia ID $id_recorrencia nao encontrado.");
    }
    $dados = mysqli_fetch_assoc($r);
    $dados['tipo_origem'] = 'contrato';
    $dados['id_cliente'] = $dados['rec_id_cliente'] ?? $dados['client_id_final']; // Ensure id_cliente is set

    // Normalize data for shared variables
    $dados['nome_pet'] = 'N/A';
    $dados['especie'] = 'N/A';
    $dados['raca'] = 'N/A';
    $dados['sexo'] = 'N/A';
    $dados['nome_vet'] = 'N/A';
    $dados['crmv_vet'] = 'N/A';
    $dados['nascimento'] = null;
}

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

// Calculate City Name
$nomeCidade = 'São Paulo'; // Fallback
if (!empty($empresa['codigo_municipio'])) {
    $ibgeCidade = AppHelper::getCidadePorCodigo($empresa['codigo_municipio']);
    if ($ibgeCidade) {
        $nomeCidade = $ibgeCidade;
    }
}

// Helper to format CPF/CNPJ
function formatCpfCnpj($pCpfCnpj)
{
    $cnpj_cpf = preg_replace("/\D/", '', $pCpfCnpj);
    if (strlen($cnpj_cpf) === 11) {
        return preg_replace("/(\d{3})(\d{3})(\d{3})(\d{2})/", "\$1.\$2.\$3-\$4", $cnpj_cpf);
    }
    if (strlen($cnpj_cpf) === 14) {
        return preg_replace("/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/", "\$1.\$2.\$3/\$4-\$5", $cnpj_cpf);
    }
    return $pCpfCnpj;
}

// Map variables
$vars = [
    // Global / Company
    '{{DATA_ATUAL}}' => date('d/m/Y'),
    '{{HORA_ATUAL}}' => date('H:i'),
    '{{CIDADE_DATA}}' => $nomeCidade . ', ' . date('d/m/Y'),
    '{{LOGO_URL}}' => $logo_url,

    // Company / Emissor
    '{{EMPRESA_NOME}}' => $empresa['razao_social'] ?? '',
    '{{RAZAO_SOCIAL}}' => $empresa['razao_social'] ?? '', // Alias
    '{{NOME_FANTASIA}}' => $empresa['nome_fantasia'] ?? '',
    '{{EMPRESA_CNPJ}}' => formatCpfCnpj($empresa['cnpj'] ?? ''),
    '{{CNPJ_EMISSOR}}' => formatCpfCnpj($empresa['cnpj'] ?? ''), // Alias
    '{{EMPRESA_ENDERECO}}' => ($empresa['endereco'] ?? '') . ', ' . ($empresa['numero'] ?? '') . ' - ' . ($empresa['bairro'] ?? ''),
    '{{EMPRESA_CIDADE}}' => $nomeCidade,
    '{{EMPRESA_UF}}' => $empresa['uf'] ?? '',
    '{{EMPRESA_TELEFONE}}' => $empresa['telefone'] ?? '',
    '{{EMPRESA_EMAIL}}' => '', // Not in DB yet
    '{{EMPRESA_IE}}' => $empresa['inscricao_estadual'] ?? '',
    '{{EMPRESA_IM}}' => $empresa['inscricao_municipal'] ?? '',

    // Client / Tutor
    '{{NOME_TUTOR}}' => $dados['nome_tutor'],
    '{{NOME_CLIENTE}}' => $dados['nome_tutor'], // Alias
    '{{CLIENTE_NOME_FANTASIA}}' => '', // Field not in DB yet
    '{{CPF_TUTOR}}' => formatCpfCnpj($dados['cpf_tutor'] ?? ''),
    '{{CPF_CNPJ_CLIENTE}}' => formatCpfCnpj($dados['cpf_tutor'] ?? ''), // Alias
    '{{ENDERECO_TUTOR}}' => $dados['endereco_tutor'] ?? '',
    '{{ENDERECO_CLIENTE}}' => $dados['endereco_tutor'] ?? '', // Alias
    '{{EMAIL_CLIENTE}}' => $dados['email_tutor'] ?? '',
    '{{TELEFONE_CLIENTE}}' => $dados['telefone_tutor'] ?? '',

    // Pet / Vet (Only relevant if Atendimento)
    '{{NOME_PET}}' => $dados['nome_pet'],
    '{{ESPECIE_PET}}' => $dados['especie'],
    '{{RACA_PET}}' => $dados['raca'],
    '{{PELAGEM_PET}}' => '',
    '{{NASCIMENTO_PET}}' => $data_nascimento,
    '{{IDADE_PET}}' => $idade,
    '{{PESO_PET}}' => $dados['peso'] ?? $dados['peso_pet'] ?? '',
    '{{SEXO_PET}}' => $dados['sexo'],
    '{{NOME_VET}}' => $dados['nome_vet'],
    '{{CRMV_VET}}' => $dados['crmv_vet'],
    '{{ASSINATURA_VET}}' => !empty($dados['url_assinatura']) ? '<img src="' . $dados['url_assinatura'] . '" style="max-height: 80px; display: block;" />' : '',
    '{{ASSINATURA_COLABORADOR}}' => !empty($dados['url_assinatura']) ? '<img src="' . $dados['url_assinatura'] . '" style="max-height: 80px; display: block;" />' : '',
    '{{ASSINATURADOVER}}' => !empty($dados['url_assinatura']) ? '<img src="' . $dados['url_assinatura'] . '" style="max-height: 80px; display: block;" />' : '',
    '{{ASSINATURACOLABORADOR}}' => !empty($dados['url_assinatura']) ? '<img src="' . $dados['url_assinatura'] . '" style="max-height: 80px; display: block;" />' : '',

    // Contract / Recurrence (Only relevant if Contrato)
    '{{SERVICO_NOME}}' => $dados['nome_servico'] ?? '',
    '{{VALOR_CONTRATO}}' => isset($dados['valor_sugerido_recorrencia']) ? 'R$ ' . number_format($dados['valor_sugerido_recorrencia'], 2, ',', '.') : '',
    '{{DATA_INICIO}}' => isset($dados['data_inicio_cobranca']) ? date('d/m/Y', strtotime($dados['data_inicio_cobranca'])) : '',
    '{{DIA_VENCIMENTO}}' => isset($dados['data_inicio_cobranca']) ? date('d', strtotime($dados['data_inicio_cobranca'])) : '',

    // Fiscal / Service Details
    '{{DESCRICAO_FISCAL}}' => $dados['descricao_fiscal'] ?? $dados['descricao_personalizada'] ?? '',
    '{{ISS_RETIDO}}' => (isset($dados['iss_retido']) && $dados['iss_retido'] == '1') ? 'Sim' : 'Não',
    '{{TEXTO_PERSONALIZADO}}' => '', // Default empty, override via REQUEST
];

// 4. Apply Overrides (if any)
if (isset($_REQUEST['overrides']) && is_array($_REQUEST['overrides'])) {
    foreach ($_REQUEST['overrides'] as $key => $val) {
        if (array_key_exists($key, $vars)) {
            $vars[$key] = $val;
        }
    }
}

// 5. Replace Content
$conteudo_final = $modelo['conteudo'];
foreach ($vars as $key => $val) {
    if ($key === '{{TEXTO_PERSONALIZADO}}') {
        // Ensure we don't break HTML if user pasted weird stuff, but str_replace is safe enough for basic injection
        // The editor content is HTML, so we just put it in.
    }
    $conteudo_final = str_replace($key, $val, $conteudo_final);
}

// Custom Title
$titulo_final = $modelo['titulo'];
if (isset($_REQUEST['titulo_custom']) && !empty($_REQUEST['titulo_custom'])) {
    $titulo_final = mysqli_real_escape_string($link, $_REQUEST['titulo_custom']);
}
$titulo_final_safe = mysqli_real_escape_string($link, $titulo_final);


// 6. Save to DocumentosEmitidos
if (isset($_REQUEST['salvar']) && $_REQUEST['salvar'] == '1') {
    $tipo = mysqli_real_escape_string($link, $modelo['tipo']);
    $conteudo_html_safe = mysqli_real_escape_string($link, $conteudo_final);
    $texto_personalizado_safe = mysqli_real_escape_string($link, $vars['{{TEXTO_PERSONALIZADO}}'] ?? '');

    // Determine IDs
    $id_cliente_val = $dados['id_cliente'] ?? 'NULL';
    $id_pet_val = isset($dados['id_pet']) ? $dados['id_pet'] : 'NULL';
    $id_atend_val = $id_atendimento ? $id_atendimento : 'NULL';
    $id_rec_val = $id_recorrencia ? $id_recorrencia : 'NULL';
    $usuario_id = $_SESSION['usuario_id'] ?? 'NULL';

    $id_pet_val = isset($dados['id_pet']) ? $dados['id_pet'] : 'NULL';
    $id_atend_val = $id_atendimento ? $id_atendimento : 'NULL';
    $id_rec_val = $id_recorrencia ? $id_recorrencia : 'NULL';
    $usuario_id = $_SESSION['usuario_id'] ?? 'NULL';

    $qSave = "INSERT INTO DocumentosEmitidos (id_cliente, id_pet, id_atendimento, id_recorrencia, titulo, tipo, conteudo_html, texto_personalizado, data_emissao, usuario_emissor)
              VALUES ('$id_cliente_val', $id_pet_val, $id_atend_val, $id_rec_val, '$titulo_final_safe', '$tipo', '$conteudo_html_safe', '$texto_personalizado_safe', NOW(), $usuario_id)";

    if (DBExecute($link, $qSave)) {
        if (isset($_REQUEST['ajax']) && $_REQUEST['ajax'] == '1') {
            echo json_encode(['success' => true]);
            exit;
        }
    } else {
        if (isset($_REQUEST['ajax']) && $_REQUEST['ajax'] == '1') {
            echo json_encode(['success' => false, 'message' => mysqli_error($link)]);
            exit;
        }
    }
}

// If Ajax and NOT saving (shouldn't happen with current logic but for safety)
if (isset($_REQUEST['ajax']) && $_REQUEST['ajax'] == '1') {
    echo json_encode(['success' => false, 'message' => 'Nenhuma ação realizada.']);
    exit;
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
                min-height: 0;
                /* CRITICAL: Prevent forcing a 2nd page if content is short */
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