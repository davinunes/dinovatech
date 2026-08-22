<?php
// dinovatech/cron/gerar_faturas_recorrencias.php

// Define time limit e timezone
set_time_limit(300);
date_default_timezone_set('America/Sao_Paulo');

require_once __DIR__ . '/../../database.php';
require_once __DIR__ . '/../helpers/CronRecorrenciasHelper.php';

$isCli = (php_sapi_name() === 'cli' || defined('STDIN'));

// Se for acesso via Web (HTTP), valida autorização
if (!$isCli) {
    session_start();
    $tokenParam = $_GET['token'] ?? $_POST['token'] ?? '';

    // Token padrão ou verificação de sessão admin
    $tokenEsperado = 'dinovet_cron_' . date('Ymd'); // Token de contingência diário ou sessão
    $hasSession = isset($_SESSION['usuario_id']);
    $isValidToken = !empty($tokenParam) && (
        $tokenParam === 'dinovet_auto_cron_secret' ||
        $tokenParam === $tokenEsperado
    );

    if (!$hasSession && !$isValidToken) {
        header('Content-Type: application/json', true, 403);
        echo json_encode(['success' => false, 'message' => 'Acesso não autorizado ao Cron.']);
        exit;
    }

    header('Content-Type: application/json');
}

// Parâmetro opcional de competência (ex: ?competencia=08/2026)
$competencia = $isCli
    ? ($argv[1] ?? date('m/Y'))
    : ($_REQUEST['competencia'] ?? date('m/Y'));

$origem = $isCli ? 'cron_cli' : (isset($_SESSION['usuario_id']) ? 'manual' : 'web');

// Executa a rotina
$resultado = CronRecorrenciasHelper::processarRecorrencias($competencia, $origem);

if ($isCli) {
    echo "\n=======================================================\n";
    echo " [Dinovatech] Cron de Faturas Recorrentes\n";
    echo " Data/Hora: " . date('Y-m-d H:i:s') . "\n";
    echo " Competência: {$resultado['competencia']}\n";
    echo " Origem: $origem\n";
    echo " Status: " . ($resultado['success'] ? 'SUCESSO' : 'ERRO') . "\n";
    echo " Faturas Geradas: {$resultado['faturas_geradas']}\n";
    echo " Valor Total: R$ " . number_format($resultado['valor_total'], 2, ',', '.') . "\n";
    echo " Mensagem: {$resultado['message']}\n";

    if (!empty($resultado['faturas'])) {
        echo " -------------------------------------------------------\n";
        echo " Faturas Criadas:\n";
        foreach ($resultado['faturas'] as $f) {
            echo "  - Fatura #{$f['id_fatura']} | Cliente: {$f['cliente_nome']} | Serviço: {$f['servico_nome']} | Venc: {$f['vencimento']} | R$ " . number_format($f['valor'], 2, ',', '.') . "\n";
        }
    }

    if (!empty($resultado['erros'])) {
        echo " -------------------------------------------------------\n";
        echo " Erros Encontrados:\n";
        foreach ($resultado['erros'] as $err) {
            echo "  [ERRO] $err\n";
        }
    }
    echo "=======================================================\n\n";
} else {
    echo json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
?>
