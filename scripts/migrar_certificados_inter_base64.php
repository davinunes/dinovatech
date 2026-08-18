<?php
/**
 * Script: migrar_certificados_inter_base64.php
 * 
 * Finalidade: Ler os certificados e chaves do Banco Inter que estão atualmente
 * salvos em disco (definidos nas colunas api_inter_cert_path, api_inter_key_path, api_inter_ca_path),
 * codificá-los em Base64 e persistir nas novas colunas da tabela ConfiguracoesEmissor.
 * 
 * Uso via CLI:
 *   php scripts/migrar_certificados_inter_base64.php
 *   php scripts/migrar_certificados_inter_base64.php --delete-files
 */

if (php_sapi_name() !== 'cli') {
    header('Content-Type: text/plain; charset=utf-8');
}

require_once __DIR__ . '/../database.php';

$deleteFiles = in_array('--delete-files', $argv ?? []);
$isCli = (php_sapi_name() === 'cli');
$nl = $isCli ? "\n" : "<br>\n";

echo "=== INICIANDO MIGRAÇÃO DE CERTIFICADOS INTER PARA BASE64 ===" . $nl;

$link = DBConnect();
if (!$link) {
    echo "ERRO: Falha ao conectar ao banco de dados." . $nl;
    exit(1);
}

// 1. Garantir que as colunas existem (auto-verificação)
$checkCols = mysqli_query($link, "SHOW COLUMNS FROM ConfiguracoesEmissor LIKE 'api_inter_cert_base64'");
if (mysqli_num_rows($checkCols) === 0) {
    echo "Adicionando colunas Base64 na tabela ConfiguracoesEmissor..." . $nl;
    $sqlAlter = "ALTER TABLE `ConfiguracoesEmissor` 
                 ADD COLUMN `api_inter_cert_base64` LONGTEXT DEFAULT NULL AFTER `api_inter_cert_path`,
                 ADD COLUMN `api_inter_key_base64` LONGTEXT DEFAULT NULL AFTER `api_inter_key_path`,
                 ADD COLUMN `api_inter_ca_base64` LONGTEXT DEFAULT NULL AFTER `api_inter_ca_path`";
    if (DBExecute($link, $sqlAlter)) {
        echo "✅ Colunas adicionadas com sucesso!" . $nl;
    } else {
        echo "ERRO ao adicionar colunas: " . mysqli_error($link) . $nl;
        exit(1);
    }
}

// 2. Buscar configurações atuais
$q = "SELECT id_config, api_inter_cert_path, api_inter_key_path, api_inter_ca_path, 
             api_inter_cert_base64, api_inter_key_base64, api_inter_ca_base64 
      FROM ConfiguracoesEmissor LIMIT 1";
$res = DBExecute($link, $q);
if (!$res || mysqli_num_rows($res) === 0) {
    echo "Nenhuma configuração encontrada em ConfiguracoesEmissor." . $nl;
    DBClose($link);
    exit(0);
}

$config = mysqli_fetch_assoc($res);
$idConfig = $config['id_config'];
$updates = [];
$filesToDelete = [];

echo "Config ID: " . $idConfig . $nl;

// 3. Processar Certificado CRT
if (!empty($config['api_inter_cert_path'])) {
    $certPath = __DIR__ . '/../' . $config['api_inter_cert_path'];
    if (file_exists($certPath)) {
        $content = file_get_contents($certPath);
        if ($content !== false && strlen($content) > 0) {
            $b64 = base64_encode($content);
            $b64Safe = mysqli_real_escape_string($link, $b64);
            $updates[] = "api_inter_cert_base64 = '{$b64Safe}'";
            $filesToDelete[] = $certPath;
            echo "✅ Certificado (.crt) lido do disco (" . strlen($content) . " bytes) e preparado para migração." . $nl;
        } else {
            echo "⚠️ Arquivo do certificado está vazio: " . $certPath . $nl;
        }
    } else {
        echo "ℹ️ Arquivo do certificado não encontrado no caminho: " . $certPath . $nl;
    }
} elseif (!empty($config['api_inter_cert_base64'])) {
    echo "ℹ️ Certificado já está gravado em Base64 no banco." . $nl;
}

// 4. Processar Chave Privada KEY
if (!empty($config['api_inter_key_path'])) {
    $keyPath = __DIR__ . '/../' . $config['api_inter_key_path'];
    if (file_exists($keyPath)) {
        $content = file_get_contents($keyPath);
        if ($content !== false && strlen($content) > 0) {
            $b64 = base64_encode($content);
            $b64Safe = mysqli_real_escape_string($link, $b64);
            $updates[] = "api_inter_key_base64 = '{$b64Safe}'";
            $filesToDelete[] = $keyPath;
            echo "✅ Chave privada (.key) lida do disco (" . strlen($content) . " bytes) e preparada para migração." . $nl;
        } else {
            echo "⚠️ Arquivo de chave privada está vazio: " . $keyPath . $nl;
        }
    } else {
        echo "ℹ️ Arquivo de chave não encontrado no caminho: " . $keyPath . $nl;
    }
} elseif (!empty($config['api_inter_key_base64'])) {
    echo "ℹ️ Chave privada já está gravada em Base64 no banco." . $nl;
}

// 5. Processar Certificado CA
if (!empty($config['api_inter_ca_path'])) {
    $caPath = __DIR__ . '/../' . $config['api_inter_ca_path'];
    if (file_exists($caPath)) {
        $content = file_get_contents($caPath);
        if ($content !== false && strlen($content) > 0) {
            $b64 = base64_encode($content);
            $b64Safe = mysqli_real_escape_string($link, $b64);
            $updates[] = "api_inter_ca_base64 = '{$b64Safe}'";
            $filesToDelete[] = $caPath;
            echo "✅ Cadeia CA (.crt) lida do disco (" . strlen($content) . " bytes) e preparada para migração." . $nl;
        } else {
            echo "⚠️ Arquivo CA está vazio: " . $caPath . $nl;
        }
    } else {
        echo "ℹ️ Arquivo CA não encontrado no caminho: " . $caPath . $nl;
    }
} elseif (!empty($config['api_inter_ca_base64'])) {
    echo "ℹ️ Cadeia CA já está gravada em Base64 no banco." . $nl;
}

// 6. Executar Updates se houver dados
if (!empty($updates)) {
    $sqlUpdate = "UPDATE ConfiguracoesEmissor SET " . implode(', ', $updates) . " WHERE id_config = '{$idConfig}'";
    if (DBExecute($link, $sqlUpdate)) {
        echo $nl . "🎉 SUCESSO: Certificados migrados com sucesso para o banco de dados!" . $nl;

        if ($deleteFiles) {
            echo $nl . "Removendo arquivos antigos do disco..." . $nl;
            foreach ($filesToDelete as $f) {
                if (file_exists($f)) {
                    if (@unlink($f)) {
                        echo "🗑️ Removido: " . $f . $nl;
                    } else {
                        echo "⚠️ Falha ao remover: " . $f . $nl;
                    }
                }
            }
        } else {
            echo $nl . "💡 DICA: Os arquivos em disco foram mantidos. Para removê-los após validar o funcionamento, execute novamente com o parâmetro: --delete-files" . $nl;
        }
    } else {
        echo "ERRO ao atualizar banco: " . mysqli_error($link) . $nl;
    }
} else {
    echo $nl . "Nenhuma alteração necessária ou nenhum arquivo novo encontrado para migrar." . $nl;
}

DBClose($link);
echo "=== FIM DA MIGRAÇÃO ===" . $nl;
