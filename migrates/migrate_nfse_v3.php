<?php
// migrate_nfse_v3.php
// Script para aplicar refinamentos de ENDEREÇO no banco de dados (Fase 7)

header('Content-Type: text/plain; charset=utf-8');
include "database.php";

$link = DBConnect();

if (!$link) {
    die("Erro de conexão: " . mysqli_connect_error());
}

echo "Iniciando Migração V3 (Endereçamento NFS-e)...\n\n";

// Helper para verificar coluna
function columnExists($link, $table, $column)
{
    $result = mysqli_query($link, "SHOW COLUMNS FROM `$table` LIKE '$column'");
    return mysqli_num_rows($result) > 0;
}

$tables = [
    'Clientes' => [
        'endereco' => 'VARCHAR(255) DEFAULT NULL',
        'numero' => 'VARCHAR(20) DEFAULT NULL',
        'complemento' => 'VARCHAR(255) DEFAULT NULL',
        'bairro' => 'VARCHAR(100) DEFAULT NULL',
        'cep' => 'VARCHAR(15) DEFAULT NULL',
        'uf' => 'VARCHAR(2) DEFAULT NULL',
        'codigo_municipio' => 'VARCHAR(20) DEFAULT NULL' // IBGE
    ],
    'ConfiguracoesEmissor' => [
        'endereco' => 'VARCHAR(255) DEFAULT NULL',
        'numero' => 'VARCHAR(20) DEFAULT NULL',
        'complemento' => 'VARCHAR(255) DEFAULT NULL',
        'bairro' => 'VARCHAR(100) DEFAULT NULL',
        'cep' => 'VARCHAR(15) DEFAULT NULL',
        'uf' => 'VARCHAR(2) DEFAULT NULL'
        // codigo_municipio já existe em Config
    ]
];

foreach ($tables as $table => $columns) {
    echo "Verificando tabela [$table]...\n";
    foreach ($columns as $col => $def) {
        if (!columnExists($link, $table, $col)) {
            $sql = "ALTER TABLE `$table` ADD COLUMN `$col` $def";
            if (mysqli_query($link, $sql)) {
                echo " - Coluna '$col' adicionada. OK\n";
            } else {
                echo " - Erro ao adicionar '$col': " . mysqli_error($link) . "\n";
            }
        } else {
            echo " - Coluna '$col' já existe.\n";
        }
    }
    echo "\n";
}

echo "\nMigração V3 Concluída!\n";
DBClose($link);
?>