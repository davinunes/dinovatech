-- Migration: 20260205_0002_fix_config_columns.sql
-- Description: Align ConfiguracoesEmissor columns with Master (Server 1)

-- 1. Add caminho_certificado_pfx if missing
SET @dbname = DATABASE();
SET @tablename = "ConfiguracoesEmissor";
SET @columnname = "caminho_certificado_pfx";
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  "SELECT 1",
  "ALTER TABLE ConfiguracoesEmissor ADD COLUMN camino_certificado_pfx varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER inscricao_estadual;"
));
PREPARE stmt FROM @preparedStatement;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2. Modify columns to match Master types
ALTER TABLE ConfiguracoesEmissor 
  MODIFY COLUMN `inscricao_estadual` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  MODIFY COLUMN `api_inter_client_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  MODIFY COLUMN `api_inter_cert_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  MODIFY COLUMN `api_inter_key_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  MODIFY COLUMN `api_inter_ca_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  MODIFY COLUMN `api_oracle_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
