<?php
// migrate_nfse_v4.php
// Add fiscal registration columns to Clientes table

include 'database.php';

echo "<h2>DInovaTech - Migration DB (NFS-e V4) - IE/IM Clients</h2>";
echo "
<pre>";

// 1. Add columns to Clientes
$table = 'Clientes';
$columns_to_add = [
    'inscricao_municipal' => "VARCHAR(20) DEFAULT NULL AFTER cpf_cnpj",
    'inscricao_estadual' => "VARCHAR(20) DEFAULT NULL AFTER inscricao_municipal",
];

echo "Checking table $table...<br>";
$query_cols = "SHOW COLUMNS FROM $table";
$result_cols = mysqli_query($link, $query_cols);
$existing_cols = [];
while ($row = mysqli_fetch_assoc($result_cols)) {
    $existing_cols[] = $row['Field'];
}

foreach ($columns_to_add as $col => $def) {
    if (!in_array($col, $existing_cols)) {
        $sql = "ALTER TABLE $table ADD $col $def";
        if (mysqli_query($link, $sql)) {
            echo "Column '$col' added successfully.<br>";
        } else {
            echo "Error adding '$col': " . mysqli_error($link) . "<br>";
        }
    } else {
        echo "Column '$col' already exists.<br>";
    }
}

echo "<br><strong>Migration V4 Finished!</strong>";
echo "</pre>";
?>