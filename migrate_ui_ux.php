<?php
include "dinovatech/database.php";

$link = DBConnect();

if ($link) {
    // Check if column 'ativo' exists in 'Clientes' table
    $checkQuery = "SHOW COLUMNS FROM Clientes LIKE 'ativo'";
    $checkResult = DBExecute($link, $checkQuery);

    if (mysqli_num_rows($checkResult) == 0) {
        // Add 'ativo' column
        $query = "ALTER TABLE Clientes ADD COLUMN ativo TINYINT(1) DEFAULT 1 AFTER telefone";
        if (DBExecute($link, $query)) {
            echo "Column 'ativo' added successfully to 'Clientes' table.<br>";
        } else {
            echo "Error adding 'ativo' column: " . mysqli_error($link) . "<br>";
        }
    } else {
        echo "Column 'ativo' already exists in 'Clientes' table.<br>";
    }

    DBClose($link);
} else {
    echo "Database connection failed.<br>";
}
?>