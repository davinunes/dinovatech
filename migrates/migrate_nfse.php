<?php
// Script de Migração para NFS-e (Executar uma vez) - VERSÃO COMPATÍVEL MYSQL ANTIGO
// Local: e:\DEV\dinovatech\migrate_nfse.php

include 'database.php';

// Conectar ao Banco
$link = DBConnect();
if (!$link) {
    die("Erro ao conectar ao banco de dados.");
}

echo "<h1>Iniciando Migração NFS-e (Modo Compatibilidade)...</h1>";

// --- 1. Tabela ConfiguracoesEmissor ---
$sql1 = "CREATE TABLE IF NOT EXISTS `ConfiguracoesEmissor` (
  `id_config` int NOT NULL AUTO_INCREMENT,
  `razao_social` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nome_fantasia` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cnpj` varchar(18) COLLATE utf8mb4_unicode_ci NOT NULL,
  `inscricao_municipal` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `codigo_municipio` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '5300108',
  `regime_tributario` enum('simples','lucro_presumido','lucro_real') COLLATE utf8mb4_unicode_ci DEFAULT 'simples',
  `optante_simples` boolean DEFAULT true,
  `caminho_certificado` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `senha_certificado` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ambiente_padrao` enum('homologacao','producao') COLLATE utf8mb4_unicode_ci DEFAULT 'homologacao',
  `ultimo_rps` int DEFAULT 0,
  `serie_rps` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT '8',
  PRIMARY KEY (`id_config`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

execSql($link, "Criar tabela ConfiguracoesEmissor", $sql1);

// --- 2. Alterar Servicos (Coluna por Coluna) ---
addColumnSafe($link, "Servicos", "item_lista_servico", "varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL");
addColumnSafe($link, "Servicos", "codigo_cnae", "varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL");
addColumnSafe($link, "Servicos", "codigo_tributacao_municipio", "varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL");
addColumnSafe($link, "Servicos", "codigo_nbs", "varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL");
addColumnSafe($link, "Servicos", "aliquota_iss", "decimal(5,2) DEFAULT '0.00'");
addColumnSafe($link, "Servicos", "iss_retido", "boolean DEFAULT false");
addColumnSafe($link, "Servicos", "descricao_nfse_padrao", "text COLLATE utf8mb4_unicode_ci DEFAULT NULL");


// --- 3. Alterar Recorrencias (Coluna por Coluna) ---
addColumnSafe($link, "Recorrencias", "item_lista_servico", "varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL");
addColumnSafe($link, "Recorrencias", "aliquota_iss", "decimal(5,2) DEFAULT NULL");
addColumnSafe($link, "Recorrencias", "iss_retido", "boolean DEFAULT NULL");
addColumnSafe($link, "Recorrencias", "descricao_personalizada", "text COLLATE utf8mb4_unicode_ci DEFAULT NULL");


// --- 4. Tabela NfseEmissoes ---
$sql4 = "CREATE TABLE IF NOT EXISTS `NfseEmissoes` (
  `id_emissao` int NOT NULL AUTO_INCREMENT,
  `id_fatura` int NOT NULL,
  `id_usuario_responsavel` int DEFAULT NULL,
  `data_emissao` datetime DEFAULT CURRENT_TIMESTAMP,
  `ambiente` enum('homologacao','producao') COLLATE utf8mb4_unicode_ci NOT NULL,
  `valor_servico` decimal(10,2) NOT NULL,
  `aliquota_iss` decimal(5,2) NOT NULL,
  `iss_retido` boolean NOT NULL,
  `item_lista_servico` varchar(10) COLLATE utf8mb4_unicode_ci,
  `discriminacao` text COLLATE utf8mb4_unicode_ci,
  `numero_rps` int DEFAULT NULL,
  `serie_rps` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `numero_nota` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `codigo_verificacao` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `url_xml` text COLLATE utf8mb4_unicode_ci,
  `url_pdf` text COLLATE utf8mb4_unicode_ci,
  `status` enum('pendente','processando','concluido','erro','cancelado') COLLATE utf8mb4_unicode_ci DEFAULT 'pendente',
  `mensagem_erro` text COLLATE utf8mb4_unicode_ci,
  `xml_envio` longtext COLLATE utf8mb4_unicode_ci,
  `xml_retorno` longtext COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id_emissao`),
  KEY `id_fatura` (`id_fatura`),
  CONSTRAINT `NfseEmissoes_ibfk_1` FOREIGN KEY (`id_fatura`) REFERENCES `Faturas` (`id_fatura`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

execSql($link, "Criar tabela NfseEmissoes", $sql4);


// --- 5. Alterar Faturas ---
addColumnSafe($link, "Faturas", "possui_nfse", "boolean DEFAULT false");


// --- 6. Inserir Configuração Inicial (DInova) ---
// Verifica se já existe
$check = mysqli_query($link, "SELECT count(*) as qtd FROM ConfiguracoesEmissor");
$resCheck = mysqli_fetch_assoc($check);

if ($resCheck['qtd'] == 0) {
    echo "<p>Inserindo configuração inicial...</p>";
    $razao = "DINOVA TECNOLOGIA LTDA";
    $fantasia = "DInova Tech";
    $cnpj = "61733714000101";
    $im = "0841147200111";
    $cod_mun = "5300108";
    $certificado = "certificado/DInovaTech_1001347811.pfx";

    $insert = "INSERT INTO ConfiguracoesEmissor 
    (razao_social, nome_fantasia, cnpj, inscricao_municipal, codigo_municipio, caminho_certificado, ambiente_padrao)
    VALUES 
    ('$razao', '$fantasia', '$cnpj', '$im', '$cod_mun', '$certificado', 'homologacao')";

    if (mysqli_query($link, $insert)) {
        echo "<p style='color:green'>Configuração inicial inserida com sucesso!</p>";
    } else {
        echo "<p style='color:red'>Erro ao inserir config: " . mysqli_error($link) . "</p>";
    }
} else {
    echo "<p>Configuração já existe. Pulando insert.</p>";
}

echo "<h2>Migração Concluída!</h2>";
DBClose($link);


// Helpers
function execSql($link, $desc, $sql)
{
    echo "<p>Executando: $desc... ";
    if (mysqli_query($link, $sql)) {
        echo "<span style='color:green'>OK</span></p>";
    } else {
        echo "<span style='color:red'>ERRO: " . mysqli_error($link) . "</span></p>";
    }
}

function addColumnSafe($link, $table, $column, $def)
{
    echo "<p>Checando coluna <b>$column</b> em <b>$table</b>... ";

    // Check existance
    $checkSql = "SHOW COLUMNS FROM `$table` LIKE '$column'";
    $result = mysqli_query($link, $checkSql);

    if (mysqli_num_rows($result) > 0) {
        echo "<span style='color:orange'>Já existe (Ignorado)</span></p>";
    } else {
        // Add
        $alterSql = "ALTER TABLE `$table` ADD COLUMN `$column` $def";
        if (mysqli_query($link, $alterSql)) {
            echo "<span style='color:green'>Adicionada com Sucesso</span></p>";
        } else {
            echo "<span style='color:red'>ERRO ao adicionar: " . mysqli_error($link) . "</span></p>";
        }
    }
}
?>