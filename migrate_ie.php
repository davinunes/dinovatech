<?php
// migrate_ie.php
require_once 'dinovatech/config.php';
require_once 'database.php';

$link = DBConnect();
if (!$link)
    die("Erro de conexão DB");

// Add 'inscricao_estadual' to ConfiguracoesEmissor
$col = 'inscricao_estadual';
$def = "VARCHAR(50) DEFAULT NULL COMMENT 'Inscrição Estadual'";

if (!checkColumn($link, 'ConfiguracoesEmissor', $col)) {
    $sql = "ALTER TABLE ConfiguracoesEmissor ADD COLUMN $col $def AFTER inscricao_municipal";
    if (DBExecute($link, $sql))
        echo "Coluna $col adicionada.\n";
    else
        echo "Erro ao adicionar $col: " . mysqli_error($link) . "\n";
} else {
    echo "Coluna $col já existe.\n";
}

DBClose($link);

function checkColumn($link, $table, $column)
{
    $r = DBExecute($link, "SHOW COLUMNS FROM $table LIKE '$column'");
    return (mysqli_num_rows($r) > 0);
}
?>