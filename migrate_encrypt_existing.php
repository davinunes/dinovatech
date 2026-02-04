<?php
// migrate_encrypt_existing.php
// Script para criptografar dados sensíveis existentes (Ex: senha do certificado)

require_once __DIR__ . '/dinovatech/config.php';
require_once __DIR__ . '/dinovatech/helpers/EncryptionHelper.php';

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

echo "Iniciando criptografia de dados legados...\n";

// 1. Encrypt 'senha_certificado' in ConfiguracoesEmissor
$query = "SELECT id_config, senha_certificado FROM ConfiguracoesEmissor WHERE senha_certificado IS NOT NULL AND senha_certificado != ''";
$result = DBExecute($link, $query);

if ($result) {
    $count = 0;
    while ($row = mysqli_fetch_assoc($result)) {
        $id = $row['id_config'];
        $pass = $row['senha_certificado'];

        // Tenta descriptografar para ver se já está criptografado
        $test = EncryptionHelper::decrypt($pass);

        if ($test !== null) {
            echo "[INFO] ID $id: Senha já parece estar criptografada. Pulando.\n";
            continue;
        }

        // Se não for descriptografável, assume plaintext e criptografa
        try {
            $encrypted = EncryptionHelper::encrypt($pass);
            $safe_enc = mysqli_real_escape_string($link, $encrypted);

            $update = "UPDATE ConfiguracoesEmissor SET senha_certificado = '$safe_enc' WHERE id_config = $id";
            if (DBExecute($link, $update)) {
                echo "[SUCESSO] ID $id: Senha criptografada.\n";
                $count++;
            } else {
                echo "[ERRO] ID $id: Falha ao atualizar.\n";
            }
        } catch (Exception $e) {
            echo "[ERRO] ID $id: Exception ao criptografar: " . $e->getMessage() . "\n";
        }
    }
    echo "Total de registros processados: $count\n";
} else {
    echo "Nenhum registro encontrado ou erro na query.\n";
}

DBClose($link);
?>