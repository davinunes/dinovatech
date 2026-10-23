<?php

/**
 * Helper para provisionamento e inicialização automática de bancos de dados
 * para novos clientes (tenants) no Dinovatech SaaS Multi-Tenant.
 *
 * Cria o banco isolado, executa todas as migrações SQL da aplicação
 * e inicializa o usuário padrão (admin@dinovatech.com.br / admin1234).
 */
class TenantDbProvisionerHelper
{
    /**
     * Cria e inicializa um banco de dados completo para um novo tenant
     *
     * @param string $dbName Nome do banco a criar (ex: dino_clinica_alfa)
     * @param string $host Host do servidor MariaDB/MySQL
     * @param string $user Usuário administrativo do MariaDB (com permissão CREATE DATABASE)
     * @param string $pass Senha do usuário administrativo
     * @param int $port Porta de conexão (padrão 3306)
     * @param string $adminEmail E-mail do usuário inicial
     * @param string $adminPass Senha do usuário inicial em texto puro
     * @return array Resultado detalhado da operação com logs
     */
    public static function provisionTenantDatabase(
        string $dbName,
        string $host,
        string $user,
        string $pass,
        int $port = 3306,
        string $adminEmail = 'admin@dinovatech.com.br',
        string $adminPass = 'admin1234'
    ): array {
        // Validação básica do nome do banco
        $dbName = trim($dbName);
        if (!preg_match('/^[a-zA-Z0-9_]{3,64}$/', $dbName)) {
            return [
                'success' => false,
                'message' => 'Nome de banco de dados inválido. Use apenas letras, números e underline (3 a 64 caracteres).',
                'logs' => ['Nome de banco inválido: ' . $dbName]
            ];
        }

        $logs = [];
        $startTime = microtime(true);

        // 1. Conexão ao servidor MySQL sem selecionar banco específico
        $logs[] = "Conectando ao MariaDB em {$host}:{$port} como '{$user}'...";
        
        if (function_exists('mysqli_report')) {
            @mysqli_report(MYSQLI_REPORT_OFF);
        }

        $linkServer = @mysqli_connect($host, $user, $pass, null, $port);
        if (!$linkServer) {
            $err = mysqli_connect_error();
            $logs[] = "FALHA de conexão ao MariaDB: {$err}";
            return [
                'success' => false,
                'message' => "Falha ao conectar no servidor de banco de dados: {$err}",
                'logs' => $logs
            ];
        }
        mysqli_set_charset($linkServer, 'utf8mb4');
        $logs[] = "Conexão inicial estabelecida com sucesso.";

        // 2. Criação do Banco de Dados
        $logs[] = "Criando banco de dados `{$dbName}` se não existir...";
        $createDbSql = "CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
        if (!mysqli_query($linkServer, $createDbSql)) {
            $err = mysqli_error($linkServer);
            $logs[] = "FALHA ao criar database: {$err}";
            mysqli_close($linkServer);
            return [
                'success' => false,
                'message' => "Erro ao criar banco de dados: {$err}",
                'logs' => $logs
            ];
        }
        $logs[] = "Banco de dados `{$dbName}` criado/garantido com charset utf8mb4.";
        mysqli_close($linkServer);

        // 3. Conexão direta ao banco de dados recém-criado
        $logs[] = "Conectando diretamente à base `{$dbName}`...";
        $linkDb = @mysqli_connect($host, $user, $pass, $dbName, $port);
        if (!$linkDb) {
            $err = mysqli_connect_error();
            $logs[] = "FALHA ao conectar na base criada: {$err}";
            return [
                'success' => false,
                'message' => "Erro ao conectar no banco recém-criado: {$err}",
                'logs' => $logs
            ];
        }
        mysqli_set_charset($linkDb, 'utf8mb4');
        @mysqli_query($linkDb, "SET time_zone = '-03:00'");

        // 4. Criação da tabela de controle de migrações
        $logs[] = "Verificando tabela de histórico de migrações (migrations_history)...";
        $createHistorySql = "CREATE TABLE IF NOT EXISTS `migrations_history` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `migration_name` VARCHAR(255) NOT NULL UNIQUE,
            `executed_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        if (!mysqli_query($linkDb, $createHistorySql)) {
            $err = mysqli_error($linkDb);
            $logs[] = "FALHA ao criar migrations_history: {$err}";
            mysqli_close($linkDb);
            return [
                'success' => false,
                'message' => "Erro ao preparar histórico de migrações: {$err}",
                'logs' => $logs
            ];
        }

        // 5. Escaneamento e Execução das Migrations
        $migrationsDir = __DIR__ . '/../../database/migrations/';
        if (!is_dir($migrationsDir)) {
            $migrationsDir = __DIR__ . '/../database/migrations/';
        }
        
        $files = glob($migrationsDir . '*.sql');
        if ($files) {
            sort($files);
        } else {
            $files = [];
        }

        $logs[] = "Identificados " . count($files) . " arquivos de migração em {$migrationsDir}.";

        // Obtém migrações já executadas anteriormente neste banco
        $executed = [];
        $resHist = mysqli_query($linkDb, "SELECT migration_name FROM migrations_history");
        if ($resHist) {
            while ($row = mysqli_fetch_assoc($resHist)) {
                $executed[] = $row['migration_name'];
            }
        }

        $migrationsRun = 0;
        foreach ($files as $file) {
            $filename = basename($file);
            if (in_array($filename, $executed)) {
                continue;
            }

            $sqlContent = file_get_contents($file);
            if (empty(trim($sqlContent))) {
                continue;
            }

            $logs[] = "Executando: {$filename}...";

            // Executa script completo com suporte a múltiplos comandos (multi_query)
            if (mysqli_multi_query($linkDb, $sqlContent)) {
                do {
                    if ($res = mysqli_store_result($linkDb)) {
                        mysqli_free_result($res);
                    }
                } while (mysqli_more_results($linkDb) && mysqli_next_result($linkDb));

                if (mysqli_errno($linkDb)) {
                    $err = mysqli_error($linkDb);
                    $logs[] = "FALHA na migração {$filename}: {$err}";
                    mysqli_close($linkDb);
                    return [
                        'success' => false,
                        'message' => "Erro ao executar migração {$filename}: {$err}",
                        'logs' => $logs
                    ];
                }

                // Registra migração executada com sucesso
                $fnEscaped = mysqli_real_escape_string($linkDb, $filename);
                mysqli_query($linkDb, "INSERT INTO migrations_history (migration_name) VALUES ('{$fnEscaped}')");
                $migrationsRun++;
            } else {
                $err = mysqli_error($linkDb);
                $logs[] = "FALHA de sintaxe na migração {$filename}: {$err}";
                mysqli_close($linkDb);
                return [
                    'success' => false,
                    'message' => "Erro na migração {$filename}: {$err}",
                    'logs' => $logs
                ];
            }
        }
        $logs[] = "Migrações concluídas: {$migrationsRun} nova(s) migração(ões) aplicada(s).";

        // 6. Inicialização do Usuário Administrador
        $logs[] = "Inicializando usuário administrador ({$adminEmail})...";
        $adminHash = password_hash($adminPass, PASSWORD_DEFAULT);
        $emailSafe = mysqli_real_escape_string($linkDb, $adminEmail);
        $hashSafe = mysqli_real_escape_string($linkDb, $adminHash);

        // Verifica se a tabela Usuarios existe
        $checkUsersTable = mysqli_query($linkDb, "SHOW TABLES LIKE 'Usuarios'");
        if ($checkUsersTable && mysqli_num_rows($checkUsersTable) > 0) {
            $userUpsertSql = "INSERT INTO `Usuarios` (`nome`, `email`, `senha`, `nivel_acesso`)
                VALUES ('Administrador Dinovatech', '{$emailSafe}', '{$hashSafe}', 'admin')
                ON DUPLICATE KEY UPDATE `senha` = '{$hashSafe}', `nivel_acesso` = 'admin'";

            if (!mysqli_query($linkDb, $userUpsertSql)) {
                $err = mysqli_error($linkDb);
                $logs[] = "AVISO ao criar usuário administrador: {$err}";
            } else {
                $logs[] = "Usuário `{$adminEmail}` configurado com sucesso como administrador.";
            }
        } else {
            $logs[] = "AVISO: Tabela `Usuarios` não foi localizada após as migrações.";
        }

        // 7. Inicialização de Registro Básico em ConfiguracoesEmissor (Evita queries nulas)
        $logs[] = "Garantindo configurações básicas da empresa em ConfiguracoesEmissor...";
        $checkConfigTable = mysqli_query($linkDb, "SHOW TABLES LIKE 'ConfiguracoesEmissor'");
        if ($checkConfigTable && mysqli_num_rows($checkConfigTable) > 0) {
            $resCount = mysqli_query($linkDb, "SELECT COUNT(*) as total FROM ConfiguracoesEmissor");
            $totalCfg = 0;
            if ($resCount && $rowC = mysqli_fetch_assoc($resCount)) {
                $totalCfg = (int)$rowC['total'];
            }

            if ($totalCfg === 0) {
                $cleanDbName = str_replace(['dino_', '_'], ['', ' '], $dbName);
                $empresaNome = ucwords(trim($cleanDbName)) ?: 'Empresa Cliente';
                $empresaNomeSafe = mysqli_real_escape_string($linkDb, $empresaNome);

                $seedConfigSql = "INSERT INTO `ConfiguracoesEmissor` (
                    `razao_social`, `nome_fantasia`, `cnpj`, `inscricao_municipal`, 
                    `codigo_municipio`, `regime_tributario`, `optante_simples`, `ambiente_padrao`
                ) VALUES (
                    '{$empresaNomeSafe} LTDA', '{$empresaNomeSafe}', '00000000000191', 
                    '000000', '5300108', 'simples', 1, 'homologacao'
                )";
                @mysqli_query($linkDb, $seedConfigSql);
                $logs[] = "Configuração inicial criada para '{$empresaNome}'.";
            }
        }

        mysqli_close($linkDb);

        $elapsed = round(microtime(true) - $startTime, 2);
        $logs[] = "SUCESSO: Banco de dados `{$dbName}` totalmente provisionado e pronto para uso em {$elapsed}s!";

        return [
            'success' => true,
            'message' => "Banco de dados `{$dbName}` provisionado com sucesso! {$migrationsRun} migrações executadas.",
            'database' => $dbName,
            'host' => $host,
            'port' => $port,
            'migrations_count' => $migrationsRun,
            'admin_user' => $adminEmail,
            'admin_pass' => $adminPass,
            'elapsed_sec' => $elapsed,
            'logs' => $logs
        ];
    }
}
