<?php
require_once "database.php";

$link = DBConnect();

// Check if column exists
$check = DBExecute($link, "SHOW COLUMNS FROM Arquivos LIKE 'descricao'");
if (mysqli_num_rows($check) == 0) {
    echo "Adding 'descricao' column to Arquivos table...\n";
    $q = "ALTER TABLE Arquivos ADD COLUMN descricao TEXT NULL AFTER nome_original";
    if (DBExecute($link, $q)) {
        echo "Success!\n";
    } else {
        echo "Error: " . mysqli_error($link) . "\n";
    }
} else {
    echo "Column 'descricao' already exists.\n";
}

DBClose($link);
?>