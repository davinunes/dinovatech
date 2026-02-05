<?php
include "database.php";
$link = DBConnect();

$tables = ['Arquivos', 'Atendimentos', 'Pets', 'Receitas', 'ConfiguracoesEmissor'];

foreach ($tables as $t) {
    echo "--- TABLE: $t ---\n";
    $res = mysqli_query($link, "DESCRIBE $t");
    if ($res) {
        while ($r = mysqli_fetch_assoc($res)) {
            echo "{$r['Field']} - {$r['Type']}\n";
        }
    } else {
        echo "Table not found or error: " . mysqli_error($link) . "\n";
    }
    echo "\n";
}
DBClose($link);
?>