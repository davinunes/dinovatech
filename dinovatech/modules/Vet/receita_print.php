<?php
session_start();
$usuario_logado = isset($_SESSION['usuario_id']);
$cliente_logado = isset($_SESSION['cliente_id']);

if (!$usuario_logado && !$cliente_logado) {
    die("Acesso negado");
}

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../helpers/AppHelper.php';
require_once __DIR__ . '/../../helpers/PdfHelper.php';
include "../../../database.php";

if (isset($_GET['pdf']) && $_GET['pdf'] == '1') {
    ob_start();
}

$id_receita = $_GET['id'] ?? null;
if (!$id_receita)
    die("Receita não especificada.");

$link = DBConnect();

// Fetch Recipe + Details
$q = "SELECT r.*, a.data_atendimento, 
             p.nome as pet_nome, p.especie, p.raca, p.peso,
             c.id_cliente, c.nome as tutor_nome, 
             v.nome as vet_nome, v.crmv, v.url_assinatura
      FROM Receitas r
      JOIN Atendimentos a ON r.id_atendimento = a.id_atendimento
      JOIN Pets p ON a.id_pet = p.id_pet
      JOIN Clientes c ON p.id_cliente = c.id_cliente
      JOIN Veterinarios v ON a.id_vet = v.id_vet
      WHERE r.id_receita = " . (int) $id_receita;

$res = DBExecute($link, $q);
if (!$res || mysqli_num_rows($res) == 0)
    die("Receita não encontrada.");
$receita = mysqli_fetch_assoc($res);

// Validação de acesso do cliente
if ($cliente_logado && !$usuario_logado && $receita['id_cliente'] != $_SESSION['cliente_id']) {
    die("Acesso negado.");
}

// Fetch Items
$itens = [];
$qi = "SELECT * FROM ItensReceita WHERE id_receita = " . (int) $id_receita;
$ri = DBExecute($link, $qi);
while ($item = mysqli_fetch_assoc($ri)) {
    $itens[] = $item;
}

// Fetch Company Info
$q_conf = "SELECT * FROM ConfiguracoesEmissor LIMIT 1";
$r_conf = DBExecute($link, $q_conf);
$empresa = mysqli_fetch_assoc($r_conf);

DBClose($link);
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Receita Veterinária - #
        <?= $id_receita ?>
    </title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', Arial, sans-serif;
            background: #f3f4f6;
            color: #1f2937;
            line-height: 1.5;
        }

        .print-container {
            background: white;
            width: 210mm;
            min-height: 297mm;
            margin: 20px auto;
            padding: 20mm;
            position: relative;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        /* Layout & Flexbox */
        .flex { display: flex; }
        .flex-col { flex-direction: column; }
        .justify-between { justify-content: space-between; }
        .justify-center { justify-content: center; }
        .items-center { align-items: center; }
        .items-baseline { align-items: baseline; }
        .flex-1 { flex: 1; }
        .grid { display: grid; }
        .grid-cols-2 { grid-template-columns: 1fr 1fr; }
        .gap-4 { gap: 16px; }

        /* Typography */
        .text-xs { font-size: 11px; }
        .text-sm { font-size: 13px; }
        .text-base { font-size: 14px; }
        .text-lg { font-size: 17px; }
        .text-xl { font-size: 20px; }
        .text-3xl { font-size: 26px; }
        .font-bold { font-weight: bold; }
        .font-medium { font-weight: 500; }
        .uppercase { text-transform: uppercase; }
        .tracking-widest { letter-spacing: 0.1em; }
        .tracking-tight { letter-spacing: -0.025em; }
        .tracking-wide { letter-spacing: 0.05em; }
        .leading-snug { line-height: 1.375; }
        .italic { font-style: italic; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .block { display: block; }

        /* Colors */
        .text-cyan-800 { color: #155e75; }
        .text-cyan-900 { color: #164e63; }
        .text-gray-800 { color: #1f2937; }
        .text-gray-700 { color: #374151; }
        .text-gray-600 { color: #4b5563; }
        .text-gray-500 { color: #6b7280; }
        .text-gray-400 { color: #9ca3af; }
        .bg-gray-50 { background-color: #f9fafb; }
        .bg-gray-100 { background-color: #f3f4f6; }

        /* Borders & Spacing */
        .border { border: 1px solid #e5e7eb; }
        .border-b { border-bottom: 1px solid #e5e7eb; }
        .border-t { border-top: 1px solid #e5e7eb; }
        .border-b-2 { border-bottom: 2px solid; }
        .border-t-2 { border-top: 2px solid; }
        .border-l-4 { border-left: 4px solid; }
        .border-dashed { border-style: dashed; }
        .border-cyan-800 { border-color: #155e75; }
        .border-cyan-200 { border-color: #a5f3fc; }
        .border-gray-200 { border-color: #e5e7eb; }
        .border-gray-300 { border-color: #d1d5db; }
        .rounded-lg { border-radius: 8px; }
        .rounded { border-radius: 4px; }
        .p-4 { padding: 16px; }
        .pb-4 { padding-bottom: 16px; }
        .pb-2 { padding-bottom: 8px; }
        .pb-1 { padding-bottom: 4px; }
        .pt-4 { padding-top: 16px; }
        .pt-8 { padding-top: 32px; }
        .pl-4 { padding-left: 16px; }
        .py-1 { padding-top: 4px; padding-bottom: 4px; }
        .px-2 { padding-left: 8px; padding-right: 8px; }
        .py-0.5 { padding-top: 2px; padding-bottom: 2px; }
        .mb-4 { margin-bottom: 16px; }
        .mb-2 { margin-bottom: 8px; }
        .mb-1 { margin-bottom: 4px; }
        .mb-0.5 { margin-bottom: 2px; }
        .mb-8 { margin-bottom: 32px; }
        .mt-12 { margin-top: 48px; }
        .mt-4 { margin-top: 16px; }
        .mt-2 { margin-top: 8px; }
        .mt-1 { margin-top: 4px; }
        .space-y-4 > * + * { margin-top: 16px; }
        .w-64 { width: 16rem; }
        .mx-auto { margin-left: auto; margin-right: auto; }
        .border-black { border-color: #000; }

        @page {
            size: A4;
            margin: 10mm;
        }

        @media print {
            .no-print {
                display: none !important;
            }

            body {
                background: white;
                -webkit-print-color-adjust: exact;
                margin: 0;
            }

            .print-container {
                box-shadow: none;
                border: none;
                padding: 0;
                width: 100%;
                max-width: 100%;
                margin: 0;
                min-height: auto !important;
                height: auto !important;
            }
        }
    </style>
</head>

<body>

<?php if (!isset($_GET['pdf']) || $_GET['pdf'] != '1'): ?>
    <div class="no-print fixed top-4 right-4 flex gap-2">
        <a href="?id=<?= $id_receita ?>&pdf=1" target="_blank"
            class="bg-emerald-600 text-white px-4 py-2 rounded shadow flex items-center hover:bg-emerald-700">
            <span class="material-icons mr-2">picture_as_pdf</span> Baixar PDF
        </a>
        <button onclick="window.print()"
            class="bg-cyan-600 text-white px-4 py-2 rounded shadow flex items-center hover:bg-cyan-700">
            <span class="material-icons mr-2">print</span> Imprimir
        </button>
        <button onclick="window.close()" class="bg-gray-500 text-white px-4 py-2 rounded shadow hover:bg-gray-600">
            Fechar
        </button>
    </div>
<?php endif; ?>

    <div class="print-container flex flex-col justify-between">

        <!-- Header -->
        <header class="border-b-2 border-cyan-800 pb-4 mb-4 flex items-center justify-between">
            <div>
                <?php if (!empty($empresa['logo_url'])): ?>
                    <img src="../../<?= $empresa['logo_url'] ?>" alt="Logo" class="h-20 object-contain" style="max-height: 75px; max-width: 220px; width: auto; object-fit: contain;">
                <?php else: ?>
                    <h1 class="text-3xl font-bold text-cyan-800 tracking-tight">
                        <?= htmlspecialchars($empresa['razao_social'] ?? 'Clínica Veterinária') ?>
                    </h1>
                <?php endif; ?>
                <div class="mt-2 text-sm text-gray-500">
                    <p>
                        <?= htmlspecialchars($empresa['endereco_logradouro'] ?? '') ?>,
                        <?= htmlspecialchars($empresa['endereco_numero'] ?? '') ?>
                    </p>
                    <p>CNPJ:
                        <?= htmlspecialchars($empresa['cnpj'] ?? '') ?>
                    </p>
                    <p>Tel:
                        <?= htmlspecialchars($empresa['telefone'] ?? '') ?>
                    </p>
                </div>
            </div>
            <div class="text-right">
                <h2 class="text-xl font-bold text-gray-400 uppercase tracking-widest">Receita Veterinária</h2>
                <p class="text-gray-500 mt-1">Nº
                    <?= str_pad($id_receita, 6, '0', STR_PAD_LEFT) ?>
                </p>
                <p class="text-sm text-gray-400">
                    <?= date('d/m/Y', strtotime($receita['data_receita'])) ?>
                </p>
            </div>
        </header>

        <!-- Body -->
        <div class="flex-1">

            <!-- Info Cards Grid -->
            <div class="grid grid-cols-2 gap-4 mb-4 text-sm">

                <!-- Vet Info (Left) -->
                <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                    <h5 class="text-xs font-bold text-gray-500 uppercase mb-2 border-b pb-1">Veterinário Responsável
                    </h5>
                    <p class="font-bold text-gray-800 text-lg"><?= htmlspecialchars($receita['vet_nome']) ?></p>
                    <p class="text-gray-600">CRMV <?= htmlspecialchars($receita['crmv']) ?></p>
                </div>

                <!-- Patient Info (Right) -->
                <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                    <h5 class="text-xs font-bold text-gray-500 uppercase mb-2 border-b pb-1">Paciente / Tutor</h5>
                    <div class="flex justify-between">
                        <div>
                            <span class="block text-xs text-gray-400 uppercase">Paciente</span>
                            <span class="font-bold text-gray-800"><?= htmlspecialchars($receita['pet_nome']) ?></span>
                            <span class="text-xs text-gray-500">(<?= htmlspecialchars($receita['especie']) ?>)</span>
                        </div>
                        <div class="text-right">
                            <span class="block text-xs text-gray-400 uppercase">Tutor</span>
                            <span class="text-gray-800"><?= htmlspecialchars($receita['tutor_nome']) ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Prescription Items -->
            <div class="mb-4">
                <h3 class="text-lg font-bold text-cyan-900 border-b border-gray-200 pb-2 mb-4">Prescrição Terapêutica
                </h3>
                <div class="space-y-4">
                    <?php foreach ($itens as $idx => $item): ?>
                        <div class="pl-4 border-l-4 border-cyan-200 py-1">
                            <div class="flex justify-between items-baseline mb-1">
                                <h4 class="text-lg font-bold text-gray-800">
                                    <?= htmlspecialchars($item['nome_medicamento']) ?>
                                </h4>
                                <span class="text-sm font-medium bg-gray-100 px-2 py-0.5 rounded text-gray-600">Qtd:
                                    <?= htmlspecialchars($item['quantidade']) ?></span>
                            </div>
                            <div class="text-gray-700 text-sm">
                                <span class="font-bold text-xs uppercase tracking-wide text-gray-400 block mb-0.5">Uso
                                    <?= htmlspecialchars($item['uso']) ?></span>
                                <p class="leading-snug"><?= nl2br(htmlspecialchars($item['posologia'])) ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <?php if (!empty($receita['observacoes'])): ?>
                <div class="mt-4 pt-4 border-t border-dashed border-gray-300">
                    <h4 class="text-xs font-bold text-gray-500 uppercase mb-1">Observações</h4>
                    <p class="text-sm text-gray-600 italic"><?= nl2br(htmlspecialchars($receita['observacoes'])) ?></p>
                </div>
            <?php endif; ?>

        </div>

        <!-- Footer -->
        <footer class="mt-12 pt-8 border-t-2 border-gray-200 text-center">
            <div class="mb-8">
                <?php if (!empty($receita['url_assinatura'])): ?>
                    <div class="flex justify-center mb-1">
                        <img src="<?= $receita['url_assinatura'] ?>" alt="Assinatura" class="h-20 object-contain" style="max-height: 70px; max-width: 220px; width: auto; object-fit: contain;">
                    </div>
                <?php else: ?>
                    <div class="w-64 border-b border-black mx-auto mb-2"></div>
                <?php endif; ?>
                <p class="font-bold text-gray-800">Dr(a).
                    <?= htmlspecialchars($receita['vet_nome']) ?>
                </p>
                <p class="text-sm text-gray-500">M.V. CRMV
                    <?= htmlspecialchars($receita['crmv']) ?>
                </p>
            </div>
            <p class="text-xs text-gray-400">Documento gerado em
                <?= date('d/m/Y \à\s H:i') ?> via DinoVet System.
            </p>
        </footer>

    </div>
<?php
if (isset($_GET['pdf']) && $_GET['pdf'] == '1') {
    $htmlContent = ob_get_clean();
    $filename = "receita_" . str_pad($id_receita, 5, '0', STR_PAD_LEFT) . ".pdf";
    PdfHelper::streamPdf($htmlContent, $filename, true);
}
?>
</body>

</html>