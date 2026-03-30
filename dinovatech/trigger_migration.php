<?php
// trigger_migration.php
define('ALLOW_MIGRATION', true);
$_POST['action'] = 'run_migrations';
$_SERVER['REQUEST_METHOD'] = 'POST';

// Mock DB connection if needed? No, app.php does it.
include 'app.php';

echo "\nMigration result: " . json_encode($response, JSON_PRETTY_PRINT) . "\n";
