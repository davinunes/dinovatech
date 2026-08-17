-- Migration: 20260817_0005_add_banho_capacidade_to_config.sql
-- Description: Adiciona campo banho_capacidade_simultanea na tabela ConfiguracoesEmissor

SET FOREIGN_KEY_CHECKS = 0;

SET @dbname = DATABASE();
SET @tablename = "ConfiguracoesEmissor";
SET @columnname = "banho_capacidade_simultanea";

SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE (table_name = @tablename) AND (table_schema = @dbname) AND (column_name = @columnname)) > 0,
  "SELECT 1",
  "ALTER TABLE ConfiguracoesEmissor ADD COLUMN banho_capacidade_simultanea INT NOT NULL DEFAULT 2 AFTER banho_checkin_foto_ativo;"
));
PREPARE stmt FROM @preparedStatement;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET FOREIGN_KEY_CHECKS = 1;
