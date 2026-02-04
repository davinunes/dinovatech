<?php
// migrate_config_security.php
// Script para adicionar colunas de segurança na tabela ConfiguracoesEmissor

// Ajuste o caminho se necessário. Assumindo que este arquivo está na raiz (e:\DEV\dinovatech\)
// e database.php está em dinovatech/database.php
if (file_exists(__DIR__ . '/dinovatech/database.php')) {
    require_once __DIR__ . '/dinovatech/database.php';
} elseif (file_exists(__DIR__ . '/database.php')) {
    require_once __DIR__ . '/database.php';
} else {
    die("Erro: database.php não encontrado.");
}

$link = DBConnect();
if (!$link) {
    die("Erro de conexão com o banco de dados.");
}

echo "Iniciando migração de segurança...\n";

// Colunas a adicionar
$columns = [
    'api_inter_client_id' => 'TEXT',
    'api_inter_client_secret' => 'TEXT', // Criptografado
    'api_oracle_user' => 'VARCHAR(255)',
    'api_oracle_password' => 'TEXT'      // Criptografado
];

foreach ($columns as $col => $type) {
    // Verifica se a coluna existe
    $checkQuery = "SHOW COLUMNS FROM ConfiguracoesEmissor LIKE '$col'";
    $res = DBExecute($link, $checkQuery);

    if ($res && mysqli_num_rows($res) == 0) {
        $alterQuery = "ALTER TABLE ConfiguracoesEmissor ADD COLUMN $col $type DEFAULT NULL";
        if (DBExecute($link, $alterQuery)) {
            echo "[SUCESSO] Coluna '$col' adicionada.\n";
        } else {
            echo "[ERRO] Falha ao adicionar coluna '$col': " . mysqli_error($link) . "\n";
        }
    } else {
        echo "[INFO] Coluna '$col' já existe.\n";
    }
}

echo "Migração concluída.\n";
DBClose($link);
?>