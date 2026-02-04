<?php
// inter/debug_config.php
require_once __DIR__ . '/config.php';

header('Content-Type: text/plain');

echo "Ambiente Selecionado: " . $ambiente . "\n";
echo "Ambiente DB: " . ($dbConfig['ambiente_padrao'] ?? 'N/A') . "\n";
echo "---------------------------------------------------\n";

$conf = $configuracoes[$ambiente];

echo "URL Token: " . $conf['url_token'] . "\n";
echo "Client ID: " . ($conf['client_id'] ? substr($conf['client_id'], 0, 5) . '...' : 'EMPTY') . "\n";
echo "Client Secret: " . ($conf['client_secret'] ? 'LOADED (Length: ' . strlen($conf['client_secret']) . ')' : 'EMPTY/FAILED') . "\n";
echo "Cert Path: " . $conf['cert_path_abs'] . "\n";
echo "Cert Exists: " . (file_exists($conf['cert_path_abs']) ? 'YES' : 'NO') . "\n";
echo "Key Path: " . $conf['key_path_abs'] . "\n";
echo "Key Exists: " . (file_exists($conf['key_path_abs']) ? 'YES' : 'NO') . "\n";
echo "CA Path: " . $conf['ca_path_abs'] . "\n";
echo "CA Exists: " . (file_exists($conf['ca_path_abs']) ? 'YES' : 'NO') . "\n";

echo "---------------------------------------------------\n";
echo "Scope: " . $conf['scope'] . "\n";
?>