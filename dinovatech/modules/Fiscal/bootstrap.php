<?php
// dinovatech/modules/Fiscal/bootstrap.php

spl_autoload_register(function ($class) {
    $prefix = 'Dinovatech\\Modules\\Fiscal\\';
    $baseDir = __DIR__ . '/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});
