-- Migration: 20260903_0001_add_nacional_nfse_support.sql
-- Description: Adiciona suporte ao Padrão Nacional da NFS-e (ISS-DF), mantendo coexistência paralela com o legado ABRASF 2.04

SET FOREIGN_KEY_CHECKS = 0;

SET @dbname = DATABASE();

-- 1. ConfiguracoesEmissor: adicionar campos de controle do provedor e contadores de DPS
SET @tablename = "ConfiguracoesEmissor";

-- Adiciona coluna nfse_provider
SET @columnname = "nfse_provider";
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE (table_name = @tablename) AND (table_schema = @dbname) AND (column_name = @columnname)) > 0,
  "SELECT 1",
  "ALTER TABLE ConfiguracoesEmissor ADD COLUMN nfse_provider ENUM('legacy', 'nacional') NOT NULL DEFAULT 'legacy' AFTER modulo_fiscal_ativo;"
));
PREPARE stmt FROM @preparedStatement;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Adiciona coluna serie_dps
SET @columnname = "serie_dps";
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE (table_name = @tablename) AND (table_schema = @dbname) AND (column_name = @columnname)) > 0,
  "SELECT 1",
  "ALTER TABLE ConfiguracoesEmissor ADD COLUMN serie_dps VARCHAR(5) NOT NULL DEFAULT '1' AFTER serie_rps;"
));
PREPARE stmt FROM @preparedStatement;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Adiciona coluna ultimo_dps_homologacao
SET @columnname = "ultimo_dps_homologacao";
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE (table_name = @tablename) AND (table_schema = @dbname) AND (column_name = @columnname)) > 0,
  "SELECT 1",
  "ALTER TABLE ConfiguracoesEmissor ADD COLUMN ultimo_dps_homologacao INT NOT NULL DEFAULT 0 AFTER ultimo_rps_homologacao;"
));
PREPARE stmt FROM @preparedStatement;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Adiciona coluna ultimo_dps_producao
SET @columnname = "ultimo_dps_producao";
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE (table_name = @tablename) AND (table_schema = @dbname) AND (column_name = @columnname)) > 0,
  "SELECT 1",
  "ALTER TABLE ConfiguracoesEmissor ADD COLUMN ultimo_dps_producao INT NOT NULL DEFAULT 0 AFTER ultimo_rps_producao;"
));
PREPARE stmt FROM @preparedStatement;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2. NfseEmissoes: adicionar campos específicos do Padrão Nacional
SET @tablename = "NfseEmissoes";

-- Adiciona coluna provider
SET @columnname = "provider";
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE (table_name = @tablename) AND (table_schema = @dbname) AND (column_name = @columnname)) > 0,
  "SELECT 1",
  "ALTER TABLE NfseEmissoes ADD COLUMN provider ENUM('legacy', 'nacional') NOT NULL DEFAULT 'legacy' AFTER ambiente;"
));
PREPARE stmt FROM @preparedStatement;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Adiciona coluna id_dps (Identificador oficial de 45 dígitos da DPS)
SET @columnname = "id_dps";
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE (table_name = @tablename) AND (table_schema = @dbname) AND (column_name = @columnname)) > 0,
  "SELECT 1",
  "ALTER TABLE NfseEmissoes ADD COLUMN id_dps VARCHAR(50) NULL AFTER serie_rps;"
));
PREPARE stmt FROM @preparedStatement;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Adiciona coluna chave_nfse (Chave de Acesso Nacional de 50 dígitos)
SET @columnname = "chave_nfse";
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE (table_name = @tablename) AND (table_schema = @dbname) AND (column_name = @columnname)) > 0,
  "SELECT 1",
  "ALTER TABLE NfseEmissoes ADD COLUMN chave_nfse VARCHAR(50) NULL AFTER numero_nota;"
));
PREPARE stmt FROM @preparedStatement;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Adiciona coluna url_visualizacao_nacional
SET @columnname = "url_visualizacao_nacional";
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE (table_name = @tablename) AND (table_schema = @dbname) AND (column_name = @columnname)) > 0,
  "SELECT 1",
  "ALTER TABLE NfseEmissoes ADD COLUMN url_visualizacao_nacional TEXT NULL AFTER url_pdf;"
));
PREPARE stmt FROM @preparedStatement;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET FOREIGN_KEY_CHECKS = 1;
