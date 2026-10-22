-- Migration: 20261022_0002_add_db_host_to_saas_tenants.sql
-- Description: Adiciona colunas db_host e db_port na tabela saas_tenants para permitir banco remoto dedicado

SET FOREIGN_KEY_CHECKS = 0;

SET @dbname = DATABASE();
SET @tablename = "saas_tenants";

-- 1. db_host
SET @columnname = "db_host";
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE (table_name = @tablename) AND (table_schema = @dbname) AND (column_name = @columnname)) > 0,
  "SELECT 1",
  "ALTER TABLE saas_tenants ADD COLUMN db_host VARCHAR(150) NULL DEFAULT NULL COMMENT 'Host ou IP do servidor MariaDB/MySQL do tenant' AFTER db_password;"
));
PREPARE stmt FROM @preparedStatement;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2. db_port
SET @columnname = "db_port";
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE (table_name = @tablename) AND (table_schema = @dbname) AND (column_name = @columnname)) > 0,
  "SELECT 1",
  "ALTER TABLE saas_tenants ADD COLUMN db_port INT DEFAULT 3306 COMMENT 'Porta do servidor MariaDB/MySQL do tenant' AFTER db_host;"
));
PREPARE stmt FROM @preparedStatement;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET FOREIGN_KEY_CHECKS = 1;
