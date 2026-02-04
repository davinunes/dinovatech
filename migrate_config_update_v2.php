<?php
// migrate_config_update_v2.php
// Script para adicionar colunas extras de configuração (Inter Pix, Conta, Oracle URL)

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

echo "Adicionando novos campos de configuração...\n";

$columns = [
    'api_inter_chave_pix' => 'VARCHAR(255)',
    'api_inter_conta_corrente' => 'VARCHAR(50)',
    'api_oracle_url' => 'TEXT'
];

foreach ($columns as $col => $type) {
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

echo "Concluído.\n";
DBClose($link);
?>