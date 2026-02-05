<?php
// migrate_nfse_v2.php
// Script para aplicar refinamentos no banco de dados (Fase 6.5)

header('Content-Type: text/plain; charset=utf-8');
include "database.php";

$link = DBConnect();

if (!$link) {
    die("Erro de conexão: " . mysqli_connect_error());
}

echo "Iniciando Migração V2 (Refinamentos NFS-e)...\n\n";

// Helper para verificar coluna
function columnExists($link, $table, $column)
{
    $result = mysqli_query($link, "SHOW COLUMNS FROM `$table` LIKE '$column'");
    return mysqli_num_rows($result) > 0;
}

// 1. ConfiguracoesEmissor: Separar ultimo_rps em Homologação e Produção
echo "1. Atualizando ConfiguracoesEmissor (Separar RPS)...\n";

// Se 'ultimo_rps' existe e 'ultimo_rps_homologacao' NÃO existe, vamos renomear.
if (columnExists($link, 'ConfiguracoesEmissor', 'ultimo_rps') && !columnExists($link, 'ConfiguracoesEmissor', 'ultimo_rps_homologacao')) {
    $sql = "ALTER TABLE ConfiguracoesEmissor CHANGE COLUMN `ultimo_rps` `ultimo_rps_homologacao` INT DEFAULT 0";
    if (mysqli_query($link, $sql)) {
        echo " - Coluna 'ultimo_rps' renomeada para 'ultimo_rps_homologacao'. OK\n";
    } else {
        echo " - Erro ao renomear 'ultimo_rps': " . mysqli_error($link) . "\n";
    }
} else {
    echo " - Coluna 'ultimo_rps' já não existe ou 'ultimo_rps_homologacao' já existe. Pulando renomeação.\n";
}

// Adicionar 'ultimo_rps_producao' se não existir
if (!columnExists($link, 'ConfiguracoesEmissor', 'ultimo_rps_producao')) {
    $sql = "ALTER TABLE ConfiguracoesEmissor ADD COLUMN `ultimo_rps_producao` INT DEFAULT 0 AFTER `ultimo_rps_homologacao`";
    if (mysqli_query($link, $sql)) {
        echo " - Coluna 'ultimo_rps_producao' adicionada. OK\n";
    } else {
        echo " - Erro ao adicionar 'ultimo_rps_producao': " . mysqli_error($link) . "\n";
    }
} else {
    echo " - Coluna 'ultimo_rps_producao' já existe.\n";
}


// 2. Recorrencias: Adicionar campos de override (CNAE, NBS, TributacaoMunicipio)
echo "\n2. Atualizando Tabela Recorrencias (Novos Overrides)...\n";

$colsRecorrencia = [
    'codigo_cnae' => 'VARCHAR(20) DEFAULT NULL',
    'codigo_tributacao_municipio' => 'VARCHAR(20) DEFAULT NULL',
    'codigo_nbs' => 'VARCHAR(20) DEFAULT NULL'
];

foreach ($colsRecorrencia as $col => $def) {
    if (!columnExists($link, 'Recorrencias', $col)) {
        $sql = "ALTER TABLE Recorrencias ADD COLUMN `$col` $def";
        if (mysqli_query($link, $sql)) {
            echo " - Coluna '$col' adicionada em Recorrencias. OK\n";
        } else {
            echo " - Erro ao adicionar '$col': " . mysqli_error($link) . "\n";
        }
    } else {
        echo " - Coluna '$col' já existe em Recorrencias.\n";
    }
}

echo "\nMigração V2 Concluída!\n";
DBClose($link);
?>