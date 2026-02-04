<?php
// migrate_inter_ca.php
require_once 'dinovatech/config.php';
require_once 'database.php';

$link = DBConnect();

if (!$link) {
    die("Erro de conexão com o banco de dados.\n");
}

// Add column for Inter CA Certificate
$col = 'api_inter_ca_path';
$definition = "TEXT DEFAULT NULL COMMENT 'Caminho para o arquivo ca.crt do Inter'";

if (!checkColumnExists($link, 'ConfiguracoesEmissor', $col)) {
    $sql = "ALTER TABLE ConfiguracoesEmissor ADD COLUMN $col $definition";
    if (DBExecute($link, $sql)) {
        echo "Coluna '$col' adicionada com sucesso.\n";
    } else {
        echo "Erro ao adicionar coluna '$col': " . mysqli_error($link) . "\n";
    }
} else {
    echo "Coluna '$col' já existe.\n";
}

DBClose($link);

function checkColumnExists($link, $table, $column)
{
    $result = DBExecute($link, "SHOW COLUMNS FROM $table LIKE '$column'");
    return (mysqli_num_rows($result) > 0);
}
?>