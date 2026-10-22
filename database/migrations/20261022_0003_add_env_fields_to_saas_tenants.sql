-- Migration: 20261022_0003_add_env_fields_to_saas_tenants.sql
-- Description: Adiciona colunas de controle de variáveis de ambiente (APP_MODE_VET, APP_MASTER_KEY, CUSTOM_ENVS) na tabela saas_tenants

SET FOREIGN_KEY_CHECKS = 0;

SET @dbname = DATABASE();
SET @tablename = "saas_tenants";

-- 1. app_mode_vet
SET @columnname = "app_mode_vet";
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE (table_name = @tablename) AND (table_schema = @dbname) AND (column_name = @columnname)) > 0,
  "SELECT 1",
  "ALTER TABLE saas_tenants ADD COLUMN app_mode_vet TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1 para ativar APP_MODE_VET, 0 para desativar' AFTER db_port;"
));
PREPARE stmt FROM @preparedStatement;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2. app_master_key
SET @columnname = "app_master_key";
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE (table_name = @tablename) AND (table_schema = @dbname) AND (column_name = @columnname)) > 0,
  "SELECT 1",
  "ALTER TABLE saas_tenants ADD COLUMN app_master_key VARCHAR(128) NULL DEFAULT NULL COMMENT 'Chave de criptografia APP_MASTER_KEY dedicada do tenant' AFTER app_mode_vet;"
));
PREPARE stmt FROM @preparedStatement;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 3. custom_envs
SET @columnname = "custom_envs";
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE (table_name = @tablename) AND (table_schema = @dbname) AND (column_name = @columnname)) > 0,
  "SELECT 1",
  "ALTER TABLE saas_tenants ADD COLUMN custom_envs TEXT NULL DEFAULT NULL COMMENT 'Variáveis de ambiente customizadas no formato CHAVE=VALOR por linha' AFTER app_master_key;"
));
PREPARE stmt FROM @preparedStatement;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET FOREIGN_KEY_CHECKS = 1;
