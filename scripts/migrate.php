<?php
// scripts/migrate.php
// Usage: php scripts/migrate.php

require_once __DIR__ . '/../dinovatech/config.php';
include __DIR__ . '/../database.php';

$link = DBConnect();
if (!$link) {
    die("Connection failed: " . mysqli_connect_error() . "\n");
}

echo "--- DinoVet Migration Manager ---\n";

// 1. Ensure History Table Exists
$checkTable = DBExecute($link, "SHOW TABLES LIKE 'migrations_history'");
if (mysqli_num_rows($checkTable) == 0) {
    echo "Creating 'migrations_history' table...\n";
    $sql = "CREATE TABLE migrations_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        migration_name VARCHAR(255) NOT NULL UNIQUE,
        executed_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )";
    if (!DBExecute($link, $sql)) {
        die("Error creating table: " . mysqli_error($link) . "\n");
    }
}

// 2. Scan Files
$migrationsDir = __DIR__ . '/../database/migrations/';
if (!is_dir($migrationsDir)) {
    if (is_dir(__DIR__ . '/database/migrations/')) {
        $migrationsDir = __DIR__ . '/database/migrations/';
    } elseif (is_dir(__DIR__ . '/../dinovatech/database/migrations/')) {
        $migrationsDir = __DIR__ . '/../dinovatech/database/migrations/';
    } else {
        mkdir($migrationsDir, 0755, true);
    }
}

$files = glob($migrationsDir . '*.sql');
if ($files) {
    sort($files); // Ensure alphabetical order
} else {
    $files = [];
}

// 3. Get Executed Migrations
$executed = [];
$res = DBExecute($link, "SELECT migration_name FROM migrations_history");
while ($row = mysqli_fetch_assoc($res)) {
    $executed[] = $row['migration_name'];
}

// 4. Run Pending
$pending = 0;
foreach ($files as $file) {
    $filename = basename($file);

    if (in_array($filename, $executed)) {
        continue;
    }

    echo "Migrating: $filename... ";

    $content = file_get_contents($file);
    if (empty(trim($content))) {
        echo "SKIPPED (Empty).\n";
        continue;
    }

    // Split by semicolons for basic support of multiple statements?
    // DBExecute usually handles single query. Multi_query needed for multiple.
    // Let's use mysqli_multi_query for robust SQL script execution.

    if (mysqli_multi_query($link, $content)) {
        do {
            // consume results
            if ($result = mysqli_store_result($link)) {
                mysqli_free_result($result);
            }
        } while (mysqli_more_results($link) && mysqli_next_result($link));

        if (mysqli_errno($link)) {
            echo "ERROR: " . mysqli_error($link) . "\n";
            exit(1);
        }

        // Record Success
        $filenameEscaped = mysqli_real_escape_string($link, $filename);
        DBExecute($link, "INSERT INTO migrations_history (migration_name) VALUES ('$filenameEscaped')");
        echo "DONE.\n";
        $pending++;

    } else {
        echo "ERROR: " . mysqli_error($link) . "\n";
        exit(1);
    }
}

if ($pending == 0) {
    echo "Nothing to migrate.\n";
} else {
    echo "All done. Executed $pending migrations.\n";
}

DBClose($link);
?>