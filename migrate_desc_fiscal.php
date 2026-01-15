<?php
// migrate_desc_fiscal.php
include 'db.php'; // Adjust path if necessary, assuming same dir structure as other scripts or root

$link = DBConnect();

$queries = [
    "ALTER TABLE `Servicos` ADD COLUMN `descricao_fiscal` TEXT NULL AFTER `descricao_servico`",
    "ALTER TABLE `Contratos` ADD COLUMN `descricao_fiscal` TEXT NULL AFTER `obs`" // Assuming 'obs' exists, simply appending is safer usually but user approved SQL using AFTER. Let's try simpler if unsure of column order, but AFTER is fine if column exists. Checking servicos first.
];

foreach ($queries as $sql) {
    if (DBExecute($link, $sql)) {
        echo "Success: $sql<br>";
    } else {
        echo "Error/Skipped (maybe exists): $sql - " . mysqli_error($link) . "<br>";
    }
}

DBClose($link);
echo "Migration finished.";
?>