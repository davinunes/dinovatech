<?php
require_once '../database.php';

$link = DBConnect();
if (!$link) {
    die("Falha na conexão com o banco de dados.");
}

header('Content-Type: text/plain; charset=utf-8');

echo "-- Esquema atual do banco de dados (Apenas Estrutura)\n";
echo "-- Gerado em " . date('Y-m-d H:i:s') . "\n\n";

$tables = [];
$result = DBExecute($link, "SHOW TABLES");
if ($result) {
    while ($row = mysqli_fetch_row($result)) {
        $tables[] = $row[0];
    }
} else {
    die("Erro ao listar tabelas.");
}

foreach ($tables as $table) {
    $resultCreate = DBExecute($link, "SHOW CREATE TABLE `$table`");
    if ($resultCreate) {
        $rowCreate = mysqli_fetch_row($resultCreate);
        echo $rowCreate[1] . ";\n\n";
    }
}

DBClose($link);
?>