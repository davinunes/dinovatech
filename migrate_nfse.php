<?php
// Script de Migração para NFS-e (Executar uma vez)
// Local: e:\DEV\dinovatech\migrate_nfse.php

include 'database.php';

// Conectar ao Banco
$link = DBConnect();
if (!$link) {
    die("Erro ao conectar ao banco de dados.");
}

echo "<h1>Iniciando Migração NFS-e...</h1>";

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

// --- 2. Alterar Servicos ---
$sql2 = "ALTER TABLE `Servicos`
ADD COLUMN IF NOT EXISTS `item_lista_servico` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `codigo_cnae` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `codigo_tributacao_municipio` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `codigo_nbs` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `aliquota_iss` decimal(5,2) DEFAULT '0.00',
ADD COLUMN IF NOT EXISTS `iss_retido` boolean DEFAULT false,
ADD COLUMN IF NOT EXISTS `descricao_nfse_padrao` text COLLATE utf8mb4_unicode_ci DEFAULT NULL;";

// MySQL < 8.0 não suporta 'IF NOT EXISTS' em ADD COLUMN diretamente de forma limpa, 
// mas vamos tentar rodar ignorando erro de coluna duplicada ou verificar antes.
// Para simplificar, vamos rodar e capturar erro se já existir.
tryExec($link, "Alterar tabela Servicos", $sql2);


// --- 3. Alterar Recorrencias ---
$sql3 = "ALTER TABLE `Recorrencias`
ADD COLUMN IF NOT EXISTS `item_lista_servico` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `aliquota_iss` decimal(5,2) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `iss_retido` boolean DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `descricao_personalizada` text COLLATE utf8mb4_unicode_ci DEFAULT NULL;";

tryExec($link, "Alterar tabela Recorrencias", $sql3);


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

// --- 5. Alterar Faturas (Flag) ---
$sql5 = "ALTER TABLE `Faturas`
ADD COLUMN IF NOT EXISTS `possui_nfse` boolean DEFAULT false;";
tryExec($link, "Alterar tabela Faturas", $sql5);


// --- 6. Inserir Configuração Inicial (DInova) ---
// Verifica se já existe
$check = mysqli_query($link, "SELECT count(*) as qtd FROM ConfiguracoesEmissor");
$row = mysqli_fetch_assoc($check);

if ($row['qtd'] == 0) {
    echo "<p>Inserindo configuração inicial...</p>";
    $razao = "DINOVA TECNOLOGIA LTDA"; // Exemplo
    $fantasia = "DInova Tech";
    $cnpj = "61733714000101"; // Do certificado de teste
    $im = "0841147200111"; // Do certificado de teste
    $cod_mun = "5300108";
    $certificado = "certificado/DInovaTech_1001347811.pfx"; // Relativo ao root

    // Inserção sem Inscricao Estadual (IE) e sem Senha (gerenciada via arquivo)
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

function tryExec($link, $desc, $sql)
{
    // Tenta executar, mas se der erro (ex: duplicate column), ignora mas avisa
    echo "<p>Tentando: $desc... ";
    try {
        if (@mysqli_query($link, $sql)) {
            echo "<span style='color:green'>OK</span></p>";
        } else {
            $err = mysqli_error($link);
            // Ignora erro 1060 (Duplicate column name)
            if (strpos($err, "Duplicate column") !== false || strpos($err, "1060") !== false) {
                echo "<span style='color:orange'>Já existia (Ignorado)</span></p>";
            } else {
                echo "<span style='color:red'>ERRO: $err</span></p>";
            }
        }
    } catch (Exception $e) {
        // Ignora erro específico de coluna já existente se for Exception logic
        echo "<span style='color:red'>ERRO EXCEPTION: " . $e->getMessage() . "</span></p>";
    }
}
?>