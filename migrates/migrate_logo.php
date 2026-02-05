<?php
// migrate_logo.php
require_once 'dinovatech/config.php';
require_once 'database.php';

$link = DBConnect();
if (!$link)
    die("Erro de conexão DB");

echo "Checking Schema for Logo and Phone...\n";

// Function to add column safe
function addColumn($link, $table, $col, $def)
{
    $res = mysqli_query($link, "SHOW COLUMNS FROM `$table` LIKE '$col'");
    if (mysqli_num_rows($res) == 0) {
        if (mysqli_query($link, "ALTER TABLE `$table` ADD COLUMN `$col` $def")) {
            echo "[OK] Added $col to $table\n";
        } else {
            echo "[ERR] Failed to add $col: " . mysqli_error($link) . "\n";
        }
    } else {
        echo "[SKIP] $col already exists in $table\n";
    }
}

// Add columns
addColumn($link, 'ConfiguracoesEmissor', 'logo_url', "VARCHAR(255) DEFAULT NULL");
addColumn($link, 'ConfiguracoesEmissor', 'telefone', "VARCHAR(20) DEFAULT NULL");

echo "Migration Complete.\n";
DBClose($link);
?>