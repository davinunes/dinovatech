<?php
// migrate1.php
require_once 'dinovatech/config.php';
require_once 'database.php';

$link = DBConnect();
if (!$link)
    die("Erro de conexão DB");

echo "Iniciando Migração 1...\n";

// --- Helper Function ---
function addColumnIfNotExists($link, $table, $column, $definition)
{
    $check = DBExecute($link, "SHOW COLUMNS FROM `$table` LIKE '$column'");
    if (mysqli_num_rows($check) == 0) {
        $sql = "ALTER TABLE `$table` ADD COLUMN `$column` $definition";
        if (DBExecute($link, $sql)) {
            echo "[OK] Tabela '$table': Coluna '$column' adicionada.\n";
        } else {
            echo "[ERRO] Tabela '$table': Falha ao adicionar '$column' - " . mysqli_error($link) . "\n";
        }
    } else {
        echo "[SKIP] Tabela '$table': Coluna '$column' já existe.\n";
    }
}

// --- 1. ConfiguracoesEmissor ---
echo "\n--- ConfiguracoesEmissor ---\n";
addColumnIfNotExists($link, 'ConfiguracoesEmissor', 'inscricao_estadual', "VARCHAR(20) DEFAULT NULL");
addColumnIfNotExists($link, 'ConfiguracoesEmissor', 'regime_tributario', "VARCHAR(50) DEFAULT 'simples'");
addColumnIfNotExists($link, 'ConfiguracoesEmissor', 'optante_simples', "TINYINT(1) DEFAULT 1");
addColumnIfNotExists($link, 'ConfiguracoesEmissor', 'ambiente_padrao', "VARCHAR(20) DEFAULT 'homologacao'");
addColumnIfNotExists($link, 'ConfiguracoesEmissor', 'serie_rps', "VARCHAR(10) DEFAULT '8'");
addColumnIfNotExists($link, 'ConfiguracoesEmissor', 'ultimo_rps_homologacao', "INT DEFAULT 0");
addColumnIfNotExists($link, 'ConfiguracoesEmissor', 'ultimo_rps_producao', "INT DEFAULT 0");
addColumnIfNotExists($link, 'ConfiguracoesEmissor', 'caminho_certificado', "VARCHAR(255) DEFAULT NULL"); // App uses this
addColumnIfNotExists($link, 'ConfiguracoesEmissor', 'caminho_certificado_pfx', "VARCHAR(255) DEFAULT NULL"); // API might read this? Let's keep both if unsure or alias them logic-side.
addColumnIfNotExists($link, 'ConfiguracoesEmissor', 'senha_certificado', "TEXT DEFAULT NULL"); // Encrypted can be long

// Address details often missing in minimal install
addColumnIfNotExists($link, 'ConfiguracoesEmissor', 'endereco', "VARCHAR(255) DEFAULT NULL");
addColumnIfNotExists($link, 'ConfiguracoesEmissor', 'numero', "VARCHAR(20) DEFAULT NULL");
addColumnIfNotExists($link, 'ConfiguracoesEmissor', 'complemento', "VARCHAR(100) DEFAULT NULL");
addColumnIfNotExists($link, 'ConfiguracoesEmissor', 'bairro', "VARCHAR(100) DEFAULT NULL");
addColumnIfNotExists($link, 'ConfiguracoesEmissor', 'cep', "VARCHAR(20) DEFAULT NULL");
addColumnIfNotExists($link, 'ConfiguracoesEmissor', 'uf', "CHAR(2) DEFAULT NULL");

// Inter API
addColumnIfNotExists($link, 'ConfiguracoesEmissor', 'api_inter_client_id', "VARCHAR(255) DEFAULT NULL");
addColumnIfNotExists($link, 'ConfiguracoesEmissor', 'api_inter_client_secret', "TEXT DEFAULT NULL");
addColumnIfNotExists($link, 'ConfiguracoesEmissor', 'api_inter_chave_pix', "VARCHAR(255) DEFAULT NULL");
addColumnIfNotExists($link, 'ConfiguracoesEmissor', 'api_inter_conta_corrente', "VARCHAR(50) DEFAULT NULL");
addColumnIfNotExists($link, 'ConfiguracoesEmissor', 'api_inter_cert_path', "VARCHAR(255) DEFAULT NULL");
addColumnIfNotExists($link, 'ConfiguracoesEmissor', 'api_inter_key_path', "VARCHAR(255) DEFAULT NULL");
addColumnIfNotExists($link, 'ConfiguracoesEmissor', 'api_inter_ca_path', "VARCHAR(255) DEFAULT NULL");

// Oracle API
addColumnIfNotExists($link, 'ConfiguracoesEmissor', 'api_oracle_user', "VARCHAR(255) DEFAULT NULL");
addColumnIfNotExists($link, 'ConfiguracoesEmissor', 'api_oracle_password', "TEXT DEFAULT NULL");
addColumnIfNotExists($link, 'ConfiguracoesEmissor', 'api_oracle_url', "VARCHAR(255) DEFAULT NULL");


// --- 2. Clientes ---
echo "\n--- Clientes ---\n";
addColumnIfNotExists($link, 'Clientes', 'inscricao_estadual', "VARCHAR(20) DEFAULT NULL");
addColumnIfNotExists($link, 'Clientes', 'inscricao_municipal', "VARCHAR(20) DEFAULT NULL");


// --- 3. NfseEmissoes ---
echo "\n--- NfseEmissoes ---\n";
addColumnIfNotExists($link, 'NfseEmissoes', 'url_pdf', "TEXT DEFAULT NULL");
addColumnIfNotExists($link, 'NfseEmissoes', 'iss_retido', "TINYINT(1) DEFAULT 0");
addColumnIfNotExists($link, 'NfseEmissoes', 'item_lista_servico', "VARCHAR(10) DEFAULT NULL");
addColumnIfNotExists($link, 'NfseEmissoes', 'aliquota_iss', "DECIMAL(5,2) DEFAULT 0.00");
addColumnIfNotExists($link, 'NfseEmissoes', 'valor_servico', "DECIMAL(10,2) DEFAULT 0.00");
addColumnIfNotExists($link, 'NfseEmissoes', 'discriminacao', "TEXT DEFAULT NULL");

// --- 4. Recorrencias ---
echo "\n--- Recorrencias ---\n";
// Fields for overriding tax settings per recurrence
addColumnIfNotExists($link, 'Recorrencias', 'codigo_cnae', "VARCHAR(20) DEFAULT NULL");
addColumnIfNotExists($link, 'Recorrencias', 'codigo_nbs', "VARCHAR(20) DEFAULT NULL");
addColumnIfNotExists($link, 'Recorrencias', 'codigo_tributacao_municipio', "VARCHAR(20) DEFAULT NULL");
addColumnIfNotExists($link, 'Recorrencias', 'aliquota_iss', "DECIMAL(5,2) DEFAULT NULL");
addColumnIfNotExists($link, 'Recorrencias', 'iss_retido', "TINYINT(1) DEFAULT NULL");
// Description override
addColumnIfNotExists($link, 'Recorrencias', 'descricao_fiscal', "TEXT DEFAULT NULL");

// --- 5. ItensFatura ---
echo "\n--- ItensFatura ---\n";
// Ensure link to recurrence if generated from one
addColumnIfNotExists($link, 'ItensFatura', 'item_recorrencia_id', "INT DEFAULT NULL");
addColumnIfNotExists($link, 'ItensFatura', 'descricao_fiscal', "TEXT DEFAULT NULL");
addColumnIfNotExists($link, 'ItensFatura', 'codigo_cnae', "VARCHAR(20) DEFAULT NULL");
addColumnIfNotExists($link, 'ItensFatura', 'codigo_nbs', "VARCHAR(20) DEFAULT NULL");
addColumnIfNotExists($link, 'ItensFatura', 'item_lista_servico', "VARCHAR(10) DEFAULT NULL");
addColumnIfNotExists($link, 'ItensFatura', 'codigo_tributacao_municipio', "VARCHAR(20) DEFAULT NULL");
addColumnIfNotExists($link, 'ItensFatura', 'aliquota_iss', "DECIMAL(5,2) DEFAULT 0.00");
addColumnIfNotExists($link, 'ItensFatura', 'iss_retido', "TINYINT(1) DEFAULT 0");


echo "\nMigração concluída.\n";
DBClose($link);
?>