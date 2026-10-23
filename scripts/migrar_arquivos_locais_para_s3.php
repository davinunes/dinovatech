#!/usr/bin/env php
<?php
/**
 * Script CLI: Migração de Arquivos Locais (Disco) para o Oracle Object Storage (S3 / PAR)
 *
 * Estratégia orientada ao Banco de Dados:
 * 1. Consulta registros com caminhos relativos (NOT LIKE 'http%') nas tabelas do sistema.
 * 2. Verifica se o arquivo físico apontado pelo banco realmente existe no disco.
 * 3. Ignora arquivos órfãos em disco (não sobe lixo para o bucket).
 * 4. Faz upload via PUT para a URL pré-autenticada (PAR) e atualiza o banco com a URL definitiva.
 *
 * Uso:
 *   php scripts/migrar_arquivos_locais_para_s3.php --dry-run
 *   php scripts/migrar_arquivos_locais_para_s3.php --execute
 *   php scripts/migrar_arquivos_locais_para_s3.php --execute --db=dino_mariana
 */

if (php_sapi_name() !== 'cli') {
    die("Este script deve ser executado exclusivamente via linha de comando (CLI).\n");
}

$projectRoot = dirname(__DIR__);
require_once $projectRoot . '/database.php';
require_once $projectRoot . '/dinovatech/helpers/StorageMigrationHelper.php';

// Argumentos CLI
$options = getopt('', ['dry-run', 'execute', 'db::', 'bucket::', 'help']);

if (isset($options['help']) || (!isset($options['dry-run']) && !isset($options['execute']))) {
    echo "\n=== MIGRACAO DE STORAGE DINOVATECH (DISCO -> ORACLE S3) ===\n";
    echo "Uso:\n";
    echo "  php scripts/migrar_arquivos_locais_para_s3.php --dry-run\n";
    echo "  php scripts/migrar_arquivos_locais_para_s3.php --execute\n\n";
    echo "Opções opcionais:\n";
    echo "  --db=NOME_BANCO   Especifica o banco a conectar (default: banco configurado no database.php)\n";
    echo "  --bucket=URL_PAR  Sobrescreve a URL Pre-Auth do Bucket Oracle\n";
    echo "  --dry-run         Apenas simula a varredura e lista o que seria migrado (recomendado primeiro)\n";
    echo "  --execute         Efetua os uploads e grava as URLs definitivas no banco de dados\n\n";
    exit(0);
}

$isDryRun = isset($options['dry-run']) || !isset($options['execute']);
$targetDb = $options['db'] ?? null;
$overrideBucket = $options['bucket'] ?? null;

echo "\n--------------------------------------------------------------\n";
echo " DINOVATECH: Auditoria e Migração de Arquivos para OCI Storage\n";
echo " Modo: " . ($isDryRun ? "SIMULAÇÃO (--dry-run)" : "EXECUÇÃO REAL (--execute)") . "\n";
echo "--------------------------------------------------------------\n\n";

// Conectar ao Banco
$link = DBConnect();
if (!$link) {
    fwrite(STDERR, "ERRO: Falha ao conectar no MariaDB. Verifique as credenciais no database.php\n");
    exit(1);
}

if ($targetDb) {
    if (!mysqli_select_db($link, $targetDb)) {
        fwrite(STDERR, "ERRO: Não foi possível selecionar a base de dados: $targetDb (" . mysqli_error($link) . ")\n");
        exit(1);
    }
    echo "Base de dados selecionada: [$targetDb]\n";
} else {
    echo "Base de dados ativa: [" . DB_DATABASE . "]\n";
}

// Obter URL do bucket
$bucketUrl = $overrideBucket ?: StorageMigrationHelper::getBucketUrl($link);
if (empty($bucketUrl)) {
    fwrite(STDERR, "\nERRO CRÍTICO: URL do Bucket Oracle não encontrada no banco (ConfiguracoesEmissor.api_oracle_url) nem em oci-s3.php.\n");
    fwrite(STDERR, "Configure o bucket ou informe via parâmetro: --bucket=\"https://...\"\n\n");
    exit(1);
}

echo "Bucket Oracle PAR: " . substr($bucketUrl, 0, 45) . "... [OK]\n";
echo "Diretório base de busca: $projectRoot\n\n";
echo "Iniciando varredura das tabelas...\n";

$result = StorageMigrationHelper::runMigration($link, [
    'dry_run' => $isDryRun,
    'bucket_url' => $bucketUrl,
    'base_dir' => $projectRoot
]);

// Exibir resumo por tabela
echo "\n================= RESUMO POR TABELA =================\n";
printf("%-22s | %-8s | %-8s | %-8s | %-10s\n", "Tabela", "No Banco", "No Disco", "Orfãos", "Tamanho");
echo str_repeat("-", 65) . "\n";

foreach ($result['tables_summary'] as $table => $s) {
    $sizeMb = round($s['bytes'] / (1024 * 1024), 2) . " MB";
    printf(
        "%-22s | %-8d | %-8d | %-8d | %-10s\n",
        $table,
        $s['scanned'],
        $s['found_on_disk'],
        $s['orphans_in_db'],
        $sizeMb
    );
}

echo str_repeat("=", 65) . "\n";
echo "Total de referências no banco:    " . $result['scanned_in_db'] . "\n";
echo "Arquivos existentes no disco:     " . $result['found_on_disk'] . "\n";
echo "Arquivos órfãos (falta no disco): " . $result['orphans_in_db'] . "\n";
echo "Volume total a transferir:        " . round($result['total_bytes'] / (1024 * 1024), 2) . " MB\n";

if ($isDryRun) {
    echo "\n[INFO] Esta foi uma simulacao (--dry-run). Nenhum arquivo foi enviado e o banco nao foi alterado.\n";
    echo "Para executar a migração real, execute:\n";
    $cmdDb = $targetDb ? " --db=$targetDb" : "";
    echo "  php scripts/migrar_arquivos_locais_para_s3.php --execute$cmdDb\n\n";
} else {
    echo "\nMigrados com sucesso: " . $result['migrated_count'] . "\n";
    echo "Erros de processamento: " . $result['error_count'] . "\n";
    echo "\n[SUCESSO] Migração de arquivos para o Oracle S3 concluída!\n\n";
}

DBClose($link);
exit(0);
