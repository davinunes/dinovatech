<?php

/**
 * Script CLI: Provisionamento de Banco de Dados para Novo Tenant Dinovatech
 * Uso:
 *   php scripts/provision_tenant_db.php <nome_do_banco> [host] [user] [pass] [port]
 *
 * Exemplo:
 *   php scripts/provision_tenant_db.php dino_mariana_costa
 */

require_once __DIR__ . '/../dinovatech/config.php';
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../dinovatech/helpers/TenantDbProvisionerHelper.php';

// Trava mandatória: disponível estritamente no modo novo (SAAS_MASTER_ENABLED=true)
if (!defined('SAAS_MASTER_ENABLED') || !SAAS_MASTER_ENABLED) {
    fwrite(STDERR, "[ERRO] Este script só pode ser executado quando SAAS_MASTER_ENABLED=true no .env.\n");
    exit(1);
}

if ($argc < 2) {
    echo "========================================================\n";
    echo "Dinovatech SaaS: Provisionador de Banco de Dados Tenant\n";
    echo "========================================================\n";
    echo "Uso:\n";
    echo "  php scripts/provision_tenant_db.php <nome_do_banco> [host] [user] [pass] [port]\n\n";
    echo "Exemplo:\n";
    echo "  php scripts/provision_tenant_db.php dino_mariana_costa\n\n";
    exit(1);
}

$dbName = trim($argv[1]);
$host = isset($argv[2]) && !empty($argv[2]) ? trim($argv[2]) : (defined('SAAS_DEFAULT_DB_HOST') ? SAAS_DEFAULT_DB_HOST : DB_HOSTNAME);
$user = isset($argv[3]) && !empty($argv[3]) ? trim($argv[3]) : DB_USERNAME;
$pass = isset($argv[4]) ? $argv[4] : DB_PASSWORD;
$port = isset($argv[5]) ? (int)$argv[5] : 3306;

echo "--- Iniciando Provisionamento de Banco para Tenant: {$dbName} ---\n";
echo "Host: {$host}:{$port} | Usuário MySQL: {$user}\n";
echo "Admin Padrão: admin@dinovatech.com.br / admin1234\n\n";

$result = TenantDbProvisionerHelper::provisionTenantDatabase(
    $dbName,
    $host,
    $user,
    $pass,
    $port,
    'admin@dinovatech.com.br',
    'admin1234'
);

foreach ($result['logs'] as $log) {
    echo "> {$log}\n";
}

echo "\n";
if ($result['success']) {
    echo "[SUCESSO] {$result['message']}\n";
    echo "Tempo total: {$result['elapsed_sec']}s\n";
    exit(0);
} else {
    fwrite(STDERR, "[FALHA] {$result['message']}\n");
    exit(1);
}
