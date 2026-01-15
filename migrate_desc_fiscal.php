<?php
// migrate_desc_fiscal.php
include 'database.php'; // Adjust path if necessary, assuming same dir structure as other scripts or root

$link = DBConnect();

$queries = [
    "ALTER TABLE `Servicos` ADD COLUMN `descricao_fiscal` TEXT NULL AFTER `descricao_servico`",
    "ALTER TABLE `Recorrencias` ADD COLUMN `descricao_fiscal` TEXT NULL AFTER `descricao_personalizada`"
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