<?php
include "dinovatech/database.php";

$link = DBConnect();
echo "<h2>Debug Clientes</h2>";

if ($link) {
    // Check columns
    $cols = DBExecute($link, "SHOW COLUMNS FROM Clientes");
    echo "<h3>Columns:</h3><ul>";
    while ($row = mysqli_fetch_assoc($cols)) {
        echo "<li>" . $row['Field'] . " (" . $row['Type'] . ") - Default: " . $row['Default'] . "</li>";
    }
    echo "</ul>";

    // Check Counts
    $total = mysqli_fetch_assoc(DBExecute($link, "SELECT COUNT(*) as c FROM Clientes"))['c'];
    echo "Total Clients: $total<br>";

    $active = mysqli_fetch_assoc(DBExecute($link, "SELECT COUNT(*) as c FROM Clientes WHERE ativo = 1"))['c'];
    echo "Ativo = 1: $active<br>";

    $inactive = mysqli_fetch_assoc(DBExecute($link, "SELECT COUNT(*) as c FROM Clientes WHERE ativo = 0"))['c'];
    echo "Ativo = 0: $inactive<br>";

    $nulls = mysqli_fetch_assoc(DBExecute($link, "SELECT COUNT(*) as c FROM Clientes WHERE ativo IS NULL"))['c'];
    echo "Ativo IS NULL: $nulls<br>";

    // Check raw values dump of first 5
    echo "<h3>First 5 Rows Raw:</h3>";
    $res = DBExecute($link, "SELECT id_cliente, nome, ativo FROM Clientes LIMIT 5");
    while ($r = mysqli_fetch_assoc($res)) {
        var_dump($r);
        echo "<br>";
    }

    // Auto-fix if needed
    if ($active == 0 && $inactive == 0 && $total > 0) {
        echo "<h3>Attempting Fix...</h3>";
        DBExecute($link, "UPDATE Clientes SET ativo = 1 WHERE ativo IS NULL OR ativo = ''");
        echo "Updated NULL/Empty to 1.<br>";
    }

    DBClose($link);
} else {
    echo "DB Connection Failed";
}
?>