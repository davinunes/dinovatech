<?php

/**
 * StorageMigrationHelper
 *
 * Utilitário para auditoria e migração de arquivos locais (disco) para o Oracle Object Storage (S3 / PAR).
 * Estratégia: Varrer PRIMEIRAMENTE O BANCO DE DADOS em busca de referências relativas (NOT LIKE 'http%')
 * e migrar apenas os arquivos referenciados que de fato existem no disco, ignorando arquivos órfãos em disco.
 */
class StorageMigrationHelper
{
    /**
     * Tabelas e colunas que armazenam caminhos de arquivos/mídias
     */
    public static function getTargetTablesConfig(): array
    {
        return [
            'ConfiguracoesEmissor' => [
                'id_col' => 'id_config',
                'url_col' => 'logo_url',
                'folder' => 'logos',
                'prefix' => 'logo_empresa_'
            ],
            'Pets' => [
                'id_col' => 'id_pet',
                'url_col' => 'foto_url',
                'folder' => 'pets',
                'prefix' => 'pet_'
            ],
            'Clientes' => [
                'id_col' => 'id_cliente',
                'url_col' => 'foto_url',
                'folder' => 'clientes',
                'prefix' => 'cli_'
            ],
            'BanhoCheckinFotos' => [
                'id_col' => 'id_foto',
                'url_col' => 'foto_url',
                'folder' => 'banho_fotos',
                'prefix' => 'banho_'
            ],
            'Arquivos' => [
                'id_col' => 'id_arquivo',
                'url_col' => 'url_publica',
                'folder' => 'arquivos',
                'prefix' => 'arq_'
            ],
            'Veterinarios' => [
                'id_col' => 'id_vet',
                'url_col' => 'url_assinatura',
                'folder' => 'assinaturas',
                'prefix' => 'vet_'
            ]
        ];
    }

    /**
     * Obtém a URL do Bucket Oracle Pre-Authenticated (PAR)
     */
    public static function getBucketUrl($link = null): string
    {
        // 1. Tentar pegar do banco de dados na ConfiguracoesEmissor
        if ($link) {
            $hasTable = @mysqli_query($link, "SHOW TABLES LIKE 'ConfiguracoesEmissor'");
            if ($hasTable && mysqli_num_rows($hasTable) > 0) {
                $resConf = @mysqli_query($link, "SELECT api_oracle_url FROM ConfiguracoesEmissor LIMIT 1");
                if ($resConf && $rConf = mysqli_fetch_assoc($resConf)) {
                    $url = trim($rConf['api_oracle_url'] ?? '');
                    if (!empty($url)) {
                        return (substr($url, -1) === '/') ? $url : $url . '/';
                    }
                }
            }
        }

        // 2. Tentar arquivo oci-s3.php
        $candidates = [
            __DIR__ . '/../../oci-s3.php',
            __DIR__ . '/../oci-s3.php',
            dirname(__DIR__, 2) . '/oci-s3.php',
            '/var/www/html/oci-s3.php',
            '/var/www/oci-s3.php'
        ];

        foreach ($candidates as $candidate) {
            if (file_exists($candidate)) {
                $urlBucketPreauth = null;
                @include $candidate;
                if (!empty($urlBucketPreauth)) {
                    $url = trim($urlBucketPreauth);
                    return (substr($url, -1) === '/') ? $url : $url . '/';
                }
            }
        }

        return '';
    }

    /**
     * Resolve o caminho físico do arquivo no disco a partir da URL relativa salva no banco
     */
    public static function resolveLocalFilePath(string $rawPath, ?string $baseDir = null): ?string
    {
        $rawPath = trim($rawPath);
        if (empty($rawPath) || preg_match('/^https?:\/\//i', $rawPath)) {
            return null;
        }

        // Remove parâmetros de querystring se existirem (ex: logo.png?v=123)
        $cleanPath = explode('?', $rawPath)[0];
        $cleanPath = explode('#', $cleanPath)[0];

        // Se já for um arquivo absoluto válido
        if (file_exists($cleanPath) && is_file($cleanPath)) {
            return realpath($cleanPath);
        }

        // Remove prefixos relativos comuns
        $relative = preg_replace('/^(\.\.\/|\.\/|\/)+/', '', $cleanPath);

        // Raízes candidatas
        $roots = [];
        if (!empty($baseDir)) {
            $roots[] = rtrim($baseDir, '/\\');
            $roots[] = rtrim($baseDir, '/\\') . '/dinovatech';
        }

        $appDir = dirname(__DIR__); // .../dinovatech
        $projectRoot = dirname($appDir); // .../

        $roots[] = $appDir;
        $roots[] = $projectRoot;
        $roots[] = '/var/www/html';
        $roots[] = '/var/www/html/dinovatech';

        foreach (array_unique($roots) as $root) {
            $target = $root . '/' . $relative;
            if (file_exists($target) && is_file($target)) {
                return realpath($target);
            }
        }

        return null;
    }

    /**
     * Envia o arquivo físico para o Oracle Object Storage via PUT
     */
    public static function uploadToOracle(string $bucketUrl, string $bucketKey, string $filePath): array
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return [
                'success' => false,
                'error' => 'Arquivo não encontrado ou sem permissão de leitura: ' . $filePath
            ];
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            return [
                'success' => false,
                'error' => 'Falha ao ler conteúdo do arquivo: ' . $filePath
            ];
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($filePath) ?: 'application/octet-stream';

        $fullUrl = rtrim($bucketUrl, '/') . '/' . ltrim($bucketKey, '/');

        $ch = curl_init($fullUrl);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: ' . $mimeType,
            'Content-Length: ' . strlen($content)
        ]);

        $responseBody = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            return [
                'success' => true,
                'http_code' => $httpCode,
                'public_url' => $fullUrl,
                'bytes' => strlen($content),
                'mime' => $mimeType
            ];
        }

        return [
            'success' => false,
            'http_code' => $httpCode,
            'error' => "Falha HTTP $httpCode no upload OCI: $curlError. Resposta: " . substr((string)$responseBody, 0, 200)
        ];
    }

    /**
     * Executa a auditoria e migração orientada ao banco de dados
     *
     * @param mysqli $link Conexão com o banco de dados
     * @param array $options [dry_run => bool, bucket_url => string, base_dir => string, target_tables => array]
     */
    public static function runMigration($link, array $options = []): array
    {
        $dryRun = $options['dry_run'] ?? true;
        $bucketUrl = $options['bucket_url'] ?? self::getBucketUrl($link);
        $baseDir = $options['base_dir'] ?? dirname(__DIR__, 2);
        $targetTables = $options['target_tables'] ?? array_keys(self::getTargetTablesConfig());

        $summary = [
            'dry_run' => $dryRun,
            'bucket_url' => $bucketUrl,
            'scanned_in_db' => 0,
            'found_on_disk' => 0,
            'orphans_in_db' => 0,
            'migrated_count' => 0,
            'error_count' => 0,
            'total_bytes' => 0,
            'tables_summary' => [],
            'logs' => [],
            'details' => []
        ];

        if (empty($bucketUrl)) {
            $summary['logs'][] = "ERRO: Nenhuma URL de bucket Oracle (PAR) configurada no banco (ConfiguracoesEmissor.api_oracle_url) ou em oci-s3.php.";
            return $summary;
        }

        $allConfigs = self::getTargetTablesConfig();

        foreach ($targetTables as $tableName) {
            if (!isset($allConfigs[$tableName])) {
                continue;
            }

            $conf = $allConfigs[$tableName];
            $idCol = $conf['id_col'];
            $urlCol = $conf['url_col'];
            $folder = $conf['folder'];
            $prefix = $conf['prefix'];

            // Verifica se tabela existe
            $tCheck = @mysqli_query($link, "SHOW TABLES LIKE '$tableName'");
            if (!$tCheck || mysqli_num_rows($tCheck) === 0) {
                continue;
            }

            // Verifica se a coluna existe
            $cCheck = @mysqli_query($link, "SHOW COLUMNS FROM `$tableName` LIKE '$urlCol'");
            if (!$cCheck || mysqli_num_rows($cCheck) === 0) {
                continue;
            }

            // Consulta apenas caminhos relativos (não http) e preenchidos
            $sql = "SELECT `$idCol`, `$urlCol` FROM `$tableName` 
                    WHERE `$urlCol` IS NOT NULL 
                      AND `$urlCol` != '' 
                      AND `$urlCol` NOT LIKE 'http%'";

            $res = mysqli_query($link, $sql);
            if (!$res) {
                $summary['logs'][] = "Erro ao consultar tabela $tableName: " . mysqli_error($link);
                continue;
            }

            $tableCount = mysqli_num_rows($res);
            $tableFound = 0;
            $tableOrphan = 0;
            $tableMigrated = 0;
            $tableErrors = 0;
            $tableBytes = 0;

            if ($tableCount > 0) {
                $summary['logs'][] = "Tabela [$tableName]: Encontrados $tableCount registros com caminhos relativos no banco.";
            }

            while ($row = mysqli_fetch_assoc($res)) {
                $summary['scanned_in_db']++;
                $id = $row[$idCol];
                $rawUrl = $row[$urlCol];

                $localFile = self::resolveLocalFilePath($rawUrl, $baseDir);

                if (!$localFile) {
                    // Arquivo referenciado no banco não existe no disco
                    $tableOrphan++;
                    $summary['orphans_in_db']++;
                    $summary['details'][] = [
                        'table' => $tableName,
                        'id' => $id,
                        'raw_url' => $rawUrl,
                        'status' => 'orphan_in_db',
                        'message' => 'Arquivo ausente no disco local.'
                    ];
                    continue;
                }

                $tableFound++;
                $summary['found_on_disk']++;
                $fileSize = filesize($localFile);
                $tableBytes += $fileSize;
                $summary['total_bytes'] += $fileSize;

                $ext = strtolower(pathinfo($localFile, PATHINFO_EXTENSION));
                if (empty($ext)) {
                    $ext = 'bin';
                }

                // Nome único no bucket
                $bucketKey = "{$folder}/{$prefix}{$id}_" . time() . '_' . substr(md5(uniqid()), 0, 6) . ".{$ext}";

                if ($dryRun) {
                    $summary['details'][] = [
                        'table' => $tableName,
                        'id' => $id,
                        'raw_url' => $rawUrl,
                        'local_file' => $localFile,
                        'size_bytes' => $fileSize,
                        'planned_bucket_key' => $bucketKey,
                        'status' => 'ready'
                    ];
                } else {
                    // Execução real do upload
                    $uploadRes = self::uploadToOracle($bucketUrl, $bucketKey, $localFile);

                    if ($uploadRes['success']) {
                        $publicUrl = $uploadRes['public_url'];
                        $safeUrl = mysqli_real_escape_string($link, $publicUrl);
                        $safeId = mysqli_real_escape_string($link, $id);

                        $updateSql = "UPDATE `$tableName` SET `$urlCol` = '$safeUrl' WHERE `$idCol` = '$safeId'";
                        $updOk = mysqli_query($link, $updateSql);

                        if ($updOk) {
                            $tableMigrated++;
                            $summary['migrated_count']++;
                            $summary['details'][] = [
                                'table' => $tableName,
                                'id' => $id,
                                'raw_url' => $rawUrl,
                                'new_url' => $publicUrl,
                                'size_bytes' => $fileSize,
                                'status' => 'migrated'
                            ];
                        } else {
                            $tableErrors++;
                            $summary['error_count']++;
                            $summary['details'][] = [
                                'table' => $tableName,
                                'id' => $id,
                                'raw_url' => $rawUrl,
                                'status' => 'db_update_failed',
                                'error' => mysqli_error($link)
                            ];
                        }
                    } else {
                        $tableErrors++;
                        $summary['error_count']++;
                        $summary['details'][] = [
                            'table' => $tableName,
                            'id' => $id,
                            'raw_url' => $rawUrl,
                            'status' => 'upload_failed',
                            'error' => $uploadRes['error']
                        ];
                    }
                }
            }

            $summary['tables_summary'][$tableName] = [
                'scanned' => $tableCount,
                'found_on_disk' => $tableFound,
                'orphans_in_db' => $tableOrphan,
                'migrated' => $tableMigrated,
                'errors' => $tableErrors,
                'bytes' => $tableBytes
            ];
        }

        return $summary;
    }
}
