<?php
// migrate_inter_certs.php
require_once 'dinovatech/config.php';
require_once 'database.php';

$link = DBConnect();

if (!$link) {
    die("Erro de conexão com o banco de dados.\n");
}

// Add columns for Inter Certificates if they don't exist
$columnsToAdd = [
    'api_inter_cert_path' => "TEXT DEFAULT NULL COMMENT 'Caminho para o arquivo .crt do Inter'",
    'api_inter_key_path' => "TEXT DEFAULT NULL COMMENT 'Caminho para o arquivo .key do Inter'",
];

foreach ($columnsToAdd as $col => $definition) {
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
}

DBClose($link);

function checkColumnExists($link, $table, $column)
{
    $result = DBExecute($link, "SHOW COLUMNS FROM $table LIKE '$column'");
    return (mysqli_num_rows($result) > 0);
}
?>