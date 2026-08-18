<?php
/**
 * Script: migrar_certificados_inter_base64.php
 * 
 * Finalidade: Ler os certificados e chaves (Banco Inter e Certificado Digital Fiscal A1 .pfx)
 * que estão atualmente salvos em disco, codificá-los em Base64 e persistir nas novas colunas
 * da tabela ConfiguracoesEmissor.
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

echo "=== INICIANDO MIGRAÇÃO DE CERTIFICADOS PARA BASE64 ===" . $nl;

$link = DBConnect();
if (!$link) {
    echo "ERRO: Falha ao conectar ao banco de dados." . $nl;
    exit(1);
}

// 1. Garantir que as colunas existem (auto-verificação)
$checkCols = mysqli_query($link, "SHOW COLUMNS FROM ConfiguracoesEmissor LIKE 'api_inter_cert_base64'");
if (mysqli_num_rows($checkCols) === 0) {
    echo "Adicionando colunas Base64 do Inter na tabela ConfiguracoesEmissor..." . $nl;
    $sqlAlter = "ALTER TABLE `ConfiguracoesEmissor` 
                 ADD COLUMN `api_inter_cert_base64` LONGTEXT DEFAULT NULL AFTER `api_inter_cert_path`,
                 ADD COLUMN `api_inter_key_base64` LONGTEXT DEFAULT NULL AFTER `api_inter_key_path`,
                 ADD COLUMN `api_inter_ca_base64` LONGTEXT DEFAULT NULL AFTER `api_inter_ca_path`";
    DBExecute($link, $sqlAlter);
}

$checkPfxCol = mysqli_query($link, "SHOW COLUMNS FROM ConfiguracoesEmissor LIKE 'certificado_pfx_base64'");
if (mysqli_num_rows($checkPfxCol) === 0) {
    echo "Adicionando coluna Base64 do Certificado A1 (.pfx) na tabela ConfiguracoesEmissor..." . $nl;
    $sqlAlterPfx = "ALTER TABLE `ConfiguracoesEmissor` 
                    ADD COLUMN `certificado_pfx_base64` LONGTEXT DEFAULT NULL AFTER `caminho_certificado`";
    DBExecute($link, $sqlAlterPfx);
}

// 2. Buscar configurações atuais
$q = "SELECT id_config, caminho_certificado, caminho_certificado_pfx, certificado_pfx_base64,
             api_inter_cert_path, api_inter_key_path, api_inter_ca_path, 
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

// 3. Processar Certificado Digital A1 (.pfx)
$pfxPathCandidate = $config['caminho_certificado'] ?? $config['caminho_certificado_pfx'] ?? '';
if (!empty($pfxPathCandidate)) {
    $possiblePfx = [
        $pfxPathCandidate,
        __DIR__ . '/../' . $pfxPathCandidate,
        __DIR__ . '/../certificado/' . basename($pfxPathCandidate)
    ];
    $resolvedPfx = null;
    foreach ($possiblePfx as $p) {
        if (file_exists($p) && !is_dir($p)) {
            $resolvedPfx = $p;
            break;
        }
    }

    if ($resolvedPfx) {
        $content = file_get_contents($resolvedPfx);
        if ($content !== false && strlen($content) > 0) {
            $b64 = base64_encode($content);
            $b64Safe = mysqli_real_escape_string($link, $b64);
            $updates[] = "certificado_pfx_base64 = '{$b64Safe}'";
            $filesToDelete[] = $resolvedPfx;
            echo "✅ Certificado Digital A1 (.pfx) lido do disco (" . strlen($content) . " bytes) e preparado para migração." . $nl;
        } else {
            echo "⚠️ Arquivo do certificado A1 está vazio: " . $resolvedPfx . $nl;
        }
    } else {
        echo "ℹ️ Arquivo do certificado A1 não encontrado no disco para o caminho: " . $pfxPathCandidate . $nl;
    }
} elseif (!empty($config['certificado_pfx_base64'])) {
    echo "ℹ️ Certificado A1 (.pfx) já está gravado em Base64 no banco." . $nl;
}

// 4. Processar Certificado CRT do Inter
if (!empty($config['api_inter_cert_path'])) {
    $certPath = __DIR__ . '/../' . $config['api_inter_cert_path'];
    if (file_exists($certPath)) {
        $content = file_get_contents($certPath);
        if ($content !== false && strlen($content) > 0) {
            $b64 = base64_encode($content);
            $b64Safe = mysqli_real_escape_string($link, $b64);
            $updates[] = "api_inter_cert_base64 = '{$b64Safe}'";
            $filesToDelete[] = $certPath;
            echo "✅ Certificado Inter (.crt) lido do disco (" . strlen($content) . " bytes) e preparado para migração." . $nl;
        } else {
            echo "⚠️ Arquivo do certificado Inter está vazio: " . $certPath . $nl;
        }
    } else {
        echo "ℹ️ Arquivo do certificado Inter não encontrado no caminho: " . $certPath . $nl;
    }
} elseif (!empty($config['api_inter_cert_base64'])) {
    echo "ℹ️ Certificado Inter já está gravado em Base64 no banco." . $nl;
}

// 5. Processar Chave Privada KEY do Inter
if (!empty($config['api_inter_key_path'])) {
    $keyPath = __DIR__ . '/../' . $config['api_inter_key_path'];
    if (file_exists($keyPath)) {
        $content = file_get_contents($keyPath);
        if ($content !== false && strlen($content) > 0) {
            $b64 = base64_encode($content);
            $b64Safe = mysqli_real_escape_string($link, $b64);
            $updates[] = "api_inter_key_base64 = '{$b64Safe}'";
            $filesToDelete[] = $keyPath;
            echo "✅ Chave privada Inter (.key) lida do disco (" . strlen($content) . " bytes) e preparada para migração." . $nl;
        } else {
            echo "⚠️ Arquivo de chave privada está vazio: " . $keyPath . $nl;
        }
    } else {
        echo "ℹ️ Arquivo de chave Inter não encontrado no caminho: " . $keyPath . $nl;
    }
} elseif (!empty($config['api_inter_key_base64'])) {
    echo "ℹ️ Chave privada Inter já está gravada em Base64 no banco." . $nl;
}

// 6. Processar Certificado CA do Inter
if (!empty($config['api_inter_ca_path'])) {
    $caPath = __DIR__ . '/../' . $config['api_inter_ca_path'];
    if (file_exists($caPath)) {
        $content = file_get_contents($caPath);
        if ($content !== false && strlen($content) > 0) {
            $b64 = base64_encode($content);
            $b64Safe = mysqli_real_escape_string($link, $b64);
            $updates[] = "api_inter_ca_base64 = '{$b64Safe}'";
            $filesToDelete[] = $caPath;
            echo "✅ Cadeia CA Inter (.crt) lida do disco (" . strlen($content) . " bytes) e preparada para migração." . $nl;
        } else {
            echo "⚠️ Arquivo CA está vazio: " . $caPath . $nl;
        }
    } else {
        echo "ℹ️ Arquivo CA não encontrado no caminho: " . $caPath . $nl;
    }
} elseif (!empty($config['api_inter_ca_base64'])) {
    echo "ℹ️ Cadeia CA Inter já está gravada em Base64 no banco." . $nl;
}

// 7. Executar Updates se houver dados
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
