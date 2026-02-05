<?php
// scripts/dump_schema.php
// Usage: php scripts/dump_schema.php > schema.sql

require_once __DIR__ . '/../dinovatech/config.php';
include __DIR__ . '/../database.php';

$link = DBConnect();
if (!$link) {
    die("Connection failed: " . mysqli_connect_error() . "\n");
}

$tables = [];
$result = DBExecute($link, "SHOW TABLES");
while ($row = mysqli_fetch_row($result)) {
    $tables[] = $row[0];
}

echo "-- Schema Dump: " . date('Y-m-d H:i:s') . "\n";
echo "-- Host: " . DB_HOSTNAME . "\n";
echo "-- Database: " . DB_DATABASE . "\n\n";

foreach ($tables as $table) {
    $res = DBExecute($link, "SHOW CREATE TABLE `$table`");
    $row = mysqli_fetch_row($res);

    $createSql = $row[1];

    // Normalize: Remove AUTO_INCREMENT=... to avoid noise in diffs
    $createSql = preg_replace('/AUTO_INCREMENT=\d+\s*/', '', $createSql);

    echo $createSql . ";\n\n";
}

DBClose($link);
?>