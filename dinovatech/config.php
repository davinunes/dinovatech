<?php

// Simple .env loader since we might not have composer/phpdotenv
if (!function_exists('loadEnv')) {
    function loadEnv($path)
    {
        if (!file_exists($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);

            if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                putenv(sprintf('%s=%s', $name, $value));
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
}

// Tenta carregar .env da raiz do projeto (subindo níveis a partir de dinovatech/config.php)
// Estrutura esperada: root/.env
// IMPORTANTE: Variáveis do ambiente do container (getenv) têm prioridade sobre o .env local.
// A função loadEnv acima já respeita isso pois checa !array_key_exists antes de setar.
$rootDir = dirname(__DIR__, 1); // e:\DEV\dinovatech
loadEnv($rootDir . '/.env');

// Definição de Constantes Globais
// 1. Chave Mestra para Criptografia (Critical)
define('APP_MASTER_KEY', getenv('APP_MASTER_KEY'));

// Verifica saúde da chave mestra (apenas aviso no log, health check real deve ser no boot da app)
if (empty(APP_MASTER_KEY)) {
    error_log("AVISO DE SEGURANÇA: APP_MASTER_KEY não definida. Criptografia não funcionará.");
}

// 2. Modo Veterinário
define('APP_MODE_VET', getenv('APP_MODE_VET') === 'true' || getenv('APP_MODE_VET') === '1');

// 3. Timezone Default
date_default_timezone_set(getenv('TZ') ?: 'America/Sao_Paulo');

// 4. Módulo SaaS Master & Orquestração K3s (Flag de Ativação)
define('SAAS_MASTER_ENABLED', getenv('SAAS_MASTER_ENABLED') === 'true' || getenv('SAAS_MASTER_ENABLED') === '1');
define('K3S_API_URL', rtrim(getenv('K3S_API_URL') ?: 'https://137.131.208.19:6443', '/'));
define('K3S_API_TOKEN', getenv('K3S_API_TOKEN') ?: '');
define('K3S_INSECURE_SKIP_TLS', getenv('K3S_INSECURE_SKIP_TLS') === 'true' || getenv('K3S_INSECURE_SKIP_TLS') === '1' || getenv('K3S_INSECURE_SKIP_TLS') === null);
define('K3S_DEFAULT_IMAGE', getenv('K3S_DEFAULT_IMAGE') ?: 'dinovatech:latest');
define('SAAS_DEFAULT_DB_HOST', getenv('SAAS_DEFAULT_DB_HOST') ?: (defined('DB_HOSTNAME') ? DB_HOSTNAME : '127.0.0.1'));

