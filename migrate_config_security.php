<?php
// migrate_config_security.php
// Adiciona colunas para chaves de API e integração na tabela ConfiguracoesEmissor

require_once 'dinovatech/config.php';
include 'dinovatech/database.php';

$link = DBConnect();

echo "<h2>Migração de Segurança e Integrações</h2>";
echo "<pre>";

function addColumnIfNotExists($link, $table, $column, $definition)
{
    $check = mysqli_query($link, "SHOW COLUMNS FROM `$table` LIKE '$column'");
    if (mysqli_num_rows($check) == 0) {
        $sql = "ALTER TABLE `$table` ADD COLUMN `$column` $definition";
        if (mysqli_query($link, $sql)) {
            echo "[SUCCESS] Coluna '$column' adicionada em '$table'.\n";
        } else {
            echo "[ERROR] Erro ao adicionar '$column': " . mysqli_error($link) . "\n";
        }
    } else {
        echo "[INFO] Coluna '$column' já existe em '$table'.\n";
    }
}

// 1. Inter API
addColumnIfNotExists($link, 'ConfiguracoesEmissor', 'api_inter_client_id', "TEXT DEFAULT NULL AFTER uf");
addColumnIfNotExists($link, 'ConfiguracoesEmissor', 'api_inter_client_secret', "TEXT DEFAULT NULL AFTER api_inter_client_id"); // Encrypted
addColumnIfNotExists($link, 'ConfiguracoesEmissor', 'api_inter_cert_path', "VARCHAR(255) DEFAULT NULL AFTER api_inter_client_secret"); // Path to .crt
addColumnIfNotExists($link, 'ConfiguracoesEmissor', 'api_inter_key_path', "VARCHAR(255) DEFAULT NULL AFTER api_inter_cert_path"); // Path to .key

// 2. Oracle API (Placeholder for future)
addColumnIfNotExists($link, 'ConfiguracoesEmissor', 'api_oracle_user', "VARCHAR(255) DEFAULT NULL AFTER api_inter_key_path");
addColumnIfNotExists($link, 'ConfiguracoesEmissor', 'api_oracle_password', "TEXT DEFAULT NULL AFTER api_oracle_user"); // Encrypted

echo "\nConcluído.</pre>";
DBClose($link);
?>