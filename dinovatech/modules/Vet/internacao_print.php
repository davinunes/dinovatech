<?php
session_start();
$usuario_logado = isset($_SESSION['usuario_id']);
$cliente_logado = isset($_SESSION['cliente_id']);

if (!$usuario_logado && !$cliente_logado) {
    die("Acesso negado");
}

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../helpers/AppHelper.php';
include "../../../database.php";

$id_internacao = $_GET['id'] ?? null;
if (!$id_internacao) {
    die("Internação não especificada.");
}

$link = DBConnect();

// Fetch Internacao + Pet + Client + Vet
$query = "SELECT i.*, 
                 p.nome as pet_nome, p.especie, p.raca, '' as cor, p.peso as pet_peso, p.sexo, p.data_nascimento,
                 c.id_cliente, c.nome as tutor_nome, c.telefone as tel_tutor,
                 v.nome as vet_nome, v.crmv as vet_crmv
          FROM Internacoes i
          JOIN Pets p ON i.id_pet = p.id_pet
          JOIN Clientes c ON p.id_cliente = c.id_cliente
          LEFT JOIN Veterinarios v ON i.id_vet = v.id_vet
          WHERE i.id_internacao = " . (int)$id_internacao;

$res = DBExecute($link, $query);
if (!$res || mysqli_num_rows($res) == 0) {
    die("Internação não encontrada. (Erro DB: " . mysqli_error($link) . ")");
}
$internacao = mysqli_fetch_assoc($res);

// Validação de acesso do cliente
if ($cliente_logado && !$usuario_logado && $internacao['id_cliente'] != $_SESSION['cliente_id']) {
    die("Acesso negado.");
}

// Fetch Days and Medications
$dias = [];
$q_dias = "SELECT * FROM InternacaoDias WHERE id_internacao = " . (int)$id_internacao . " ORDER BY data_dia ASC";
$res_dias = DBExecute($link, $q_dias);
while ($dia = mysqli_fetch_assoc($res_dias)) {
    $id_d = $dia['id_dia'];
    $dia['medicacoes'] = [];
    $q_meds = "SELECT * FROM InternacaoMedicacoes WHERE id_dia = $id_d ORDER BY ordem ASC, id_medicacao ASC";
    $res_meds = DBExecute($link, $q_meds);
    while ($med = mysqli_fetch_assoc($res_meds)) {
        $dia['medicacoes'][] = $med;
    }
    $dias[] = $dia;
}

DBClose($link);

// Helper Idade
function calcularIdadePrint($data_nasc) {
    if (!$data_nasc) return "Não informada";
    $dob = new DateTime($data_nasc);
    $now = new DateTime();
    $diff = $now->diff($dob);
    $parts = [];
    if ($diff->y > 0) $parts[] = $diff->y . " ano" . ($diff->y > 1 ? 's' : '');
    if ($diff->m > 0) $parts[] = $diff->m . " mês" . ($diff->m > 1 ? 'es' : '');
    return empty($parts) ? "Menos de 1 mês" : implode(' e ', $parts);
}

// Organizar dias em páginas de no máximo 3 blocos
$blocos_dias = $dias;
if (empty($blocos_dias)) {
    $blocos_dias = [null]; // Ao menos 1 bloco vazio
}
$paginas_dias = array_chunk($blocos_dias, 3);
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ficha de Internação - <?= htmlspecialchars($internacao['pet_nome']) ?></title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Configurações de página e impressão */
        @page {
            size: A4 portrait;
            margin: 8mm;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            color: #000;
            margin: 0;
            padding: 0;
            background-color: #f0f0f0;
        }

        .page {
            width: 190mm;
            min-height: 277mm;
            margin: 10mm auto;
            background: #fff;
            padding: 5mm;
            box-sizing: border-box;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            page-break-after: always;
        }

        .page:last-child {
            page-break-after: avoid;
        }

        @media print {
            .no-print {
                display: none !important;
            }
            body {
                background: none;
            }
            .page {
                margin: 0;
                box-shadow: none;
                width: 100%;
                min-height: auto;
                padding: 0;
            }
        }

        /* Estrutura de Tabelas e Layout */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }

        th, td {
            border: 1px solid #000;
            padding: 4px;
            text-align: left;
            vertical-align: middle;
        }

        .header-title {
            text-align: center;
            font-weight: bold;
            font-size: 14px;
            background-color: #f2f2f2;
            padding: 6px;
        }

        .label {
            font-weight: bold;
            text-transform: uppercase;
            font-size: 10px;
        }

        .val {
            font-size: 11px;
            font-weight: normal;
        }

        /* Seções de medicação repetidas */
        .section-block {
            border: 2px solid #000;
            margin-top: 6px;
            padding: 0;
        }

        .med-table th {
            background-color: #f9f9f9;
            text-align: center;
            font-weight: bold;
            font-size: 10px;
        }

        .checkbox-cell {
            text-align: center;
            width: 16%;
            font-size: 8px;
            white-space: nowrap;
        }

        .checkbox-container {
            display: flex;
            justify-content: space-between;
            width: 100%;
        }

        .checkbox-box {
            display: inline-block;
            width: 11px;
            height: 11px;
            border: 1px solid #000;
            margin-right: 1px;
            vertical-align: middle;
            text-align: center;
            line-height: 10px;
            font-size: 9px;
            font-weight: bold;
        }

        .fluid-info {
            display: flex;
            border-top: 1px solid #000;
            background: #fff;
        }

        .fluid-field {
            flex: 1;
            padding: 3px 5px;
            border-right: 1px solid #000;
        }

        .fluid-field:last-child {
            border-right: none;
            flex: 2;
        }
    </style>
</head>

<body>

    <!-- Bar de Ações (Apenas na Tela) -->
    <div class="no-print bg-slate-800 text-white p-4 mb-4 shadow-md flex justify-between items-center max-w-4xl mx-auto rounded-lg mt-4">
        <div class="flex items-center space-x-2">
            <span class="material-icons text-cyan-400">local_hospital</span>
            <span class="font-medium">Ficha de Internação Veterinária</span>
        </div>
        <div class="flex items-center space-x-3">
            <button onclick="window.print()" class="bg-cyan-600 hover:bg-cyan-500 text-white px-4 py-2 rounded-lg font-medium transition flex items-center shadow">
                <span class="material-icons text-sm mr-2">print</span> Imprimir / Salvar PDF
            </button>
            <button onclick="window.close()" class="bg-slate-700 hover:bg-slate-600 text-slate-300 px-4 py-2 rounded-lg font-medium transition">
                Fechar
            </button>
        </div>
    </div>

    <?php foreach ($paginas_dias as $page_index => $dias_pagina): ?>
        <div class="page">

            <!-- Cabeçalho Principal da Ficha -->
            <table>
                <tr>
                    <td colspan="4" class="header-title">FICHA DE INTERNAÇÃO</td>
                </tr>
                <tr>
                    <td style="width: 45%;">
                        <span class="label">Nome:</span> 
                        <span class="val"><?= htmlspecialchars($internacao['pet_nome']) ?></span>
                    </td>
                    <td style="width: 18%;">
                        <span class="label">Espécie:</span> 
                        <span class="val"><?= htmlspecialchars($internacao['especie']) ?></span>
                    </td>
                    <td style="width: 22%;">
                        <span class="label">Raça:</span> 
                        <span class="val"><?= htmlspecialchars($internacao['raca'] ?: '-') ?></span>
                    </td>
                    <td style="width: 15%;">
                        <span class="label">Cor:</span> 
                        <span class="val"><?= htmlspecialchars($internacao['cor'] ?: '-') ?></span>
                    </td>
                </tr>
                <tr>
                    <td>
                        <span class="label">Idade:</span> 
                        <span class="val"><?= calcularIdadePrint($internacao['data_nascimento']) ?></span>
                    </td>
                    <td>
                        <span class="label">Peso:</span> 
                        <span class="val"><?= $internacao['pet_peso'] ? number_format($internacao['pet_peso'], 2) . ' kg' : '-' ?></span>
                    </td>
                    <td colspan="2">
                        <span class="label">Suspeita Clínica:</span> 
                        <span class="val"><?= htmlspecialchars($internacao['suspeita_clinica'] ?: '-') ?></span>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <span class="label">Proprietário:</span> 
                        <span class="val"><?= htmlspecialchars($internacao['tutor_nome']) ?></span>
                    </td>
                    <td colspan="2">
                        <span class="label">Fone:</span> 
                        <span class="val"><?= htmlspecialchars($internacao['tel_tutor'] ?: '-') ?></span>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <span class="label">Data da Internação:</span> 
                        <span class="val"><?= date('d/m/Y', strtotime($internacao['data_internacao'])) ?></span>
                        <span class="label" style="margin-left: 20px;">Horário:</span> 
                        <span class="val"><?= date('H:i', strtotime($internacao['data_internacao'])) ?></span>
                    </td>
                    <td colspan="2">
                        <span class="label">Médico Veterinário:</span> 
                        <span class="val"><?= htmlspecialchars($internacao['vet_nome'] ? $internacao['vet_nome'] . ($internacao['vet_crmv'] ? ' (CRMV: '.$internacao['vet_crmv'].')' : '') : '-') ?></span>
                    </td>
                </tr>
            </table>

            <!-- Renderizar até 3 blocos por folha -->
            <?php
            for ($b = 0; $b < 3; $b++) {
                $dia = $dias_pagina[$b] ?? null;
                $data_formatada = $dia ? date('d / m / Y', strtotime($dia['data_dia'])) : '&nbsp;&nbsp;&nbsp;&nbsp; / &nbsp;&nbsp;&nbsp;&nbsp; /';
                $medicacoes = $dia ? ($dia['medicacoes'] ?? []) : [];
                $soro = $dia['soro'] ?? '';
                $volume = $dia['volume'] ?? '';
                $frequencia = $dia['frequencia'] ?? '';
                $obs_dia = $dia['observacoes'] ?? '';
                ?>
                <div class="section-block">
                    <table class="med-table" style="margin-bottom: 0;">
                        <tr>
                            <td colspan="4" style="border: none; padding: 4px; background: #fff;">
                                <span class="label">Data:</span> <?= $data_formatada ?>
                            </td>
                        </tr>
                        <tr>
                            <th style="width: 45%;">MEDICAÇÃO</th>
                            <th style="width: 12%;">DOSE</th>
                            <th style="width: 10%;">VIA</th>
                            <th style="width: 33%;">HORÁRIO</th>
                        </tr>

                        <!-- Total de 11 linhas por bloco -->
                        <?php for ($r = 0; $r < 11; $r++): ?>
                            <?php 
                            $med = $medicacoes[$r] ?? null;
                            $horarios_arr = [];
                            if ($med && !empty($med['horarios'])) {
                                $parsed = json_decode($med['horarios'], true);
                                if (is_array($parsed)) {
                                    $horarios_arr = $parsed;
                                }
                            }
                            ?>
                            <tr>
                                <td style="height: 15px; font-weight: 500;">
                                    <?= $med ? htmlspecialchars($med['medicacao']) : '' ?>
                                </td>
                                <td><?= $med ? htmlspecialchars($med['dose']) : '' ?></td>
                                <td><?= $med ? htmlspecialchars($med['via']) : '' ?></td>
                                <td>
                                    <div class="checkbox-container">
                                        <?php for ($h = 0; $h < 6; $h++): ?>
                                            <?php 
                                            $slot = $horarios_arr[$h] ?? null;
                                            $time_str = '';
                                            $checked = false;
                                            if (is_array($slot)) {
                                                $time_str = $slot['hora'] ?? ($slot['time'] ?? '');
                                                $checked = !empty($slot['checked']);
                                            } elseif (is_string($slot)) {
                                                $time_str = $slot;
                                            }
                                            ?>
                                            <div class="checkbox-cell">
                                                <span class="checkbox-box"><?= $checked ? 'X' : '' ?></span>
                                                <span style="font-size: 8px; font-weight: 600;"><?= htmlspecialchars($time_str) ?></span>
                                            </div>
                                        <?php endfor; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endfor; ?>
                    </table>
                    <div class="fluid-info">
                        <div class="fluid-field">
                            <span class="label">Soro:</span> 
                            <span class="val"><?= htmlspecialchars($soro) ?></span>
                        </div>
                        <div class="fluid-field">
                            <span class="label">Volume:</span> 
                            <span class="val"><?= htmlspecialchars($volume) ?></span>
                        </div>
                        <div class="fluid-field">
                            <span class="label">Frequência:</span> 
                            <span class="val"><?= htmlspecialchars($frequencia) ?></span>
                        </div>
                        <div class="fluid-field">
                            <span class="label">Obs:</span> 
                            <span class="val"><?= htmlspecialchars($obs_dia) ?></span>
                        </div>
                    </div>
                </div>
            <?php } ?>

        </div>
    <?php endforeach; ?>

</body>

</html>
