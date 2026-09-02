-- Migration: 20260901_0001_add_ativo_to_servicos.sql
-- Description: Adiciona a coluna 'ativo' na tabela Servicos para permitir desativação de serviços

SET FOREIGN_KEY_CHECKS = 0;

SET @dbname = DATABASE();
SET @tablename = "Servicos";
SET @columnname = "ativo";

SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE (table_name = @tablename) AND (table_schema = @dbname) AND (column_name = @columnname)) > 0,
  "SELECT 1",
  "ALTER TABLE Servicos ADD COLUMN ativo TINYINT(1) NOT NULL DEFAULT 1 AFTER valor_sugerido;"
));
PREPARE stmt FROM @preparedStatement;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Adiciona índice para a coluna ativo caso não exista
SET @indexname = "idx_servicos_ativo";
SET @preparedStatementIdx = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE (table_name = @tablename) AND (table_schema = @dbname) AND (index_name = @indexname)) > 0,
  "SELECT 1",
  "ALTER TABLE Servicos ADD INDEX idx_servicos_ativo (ativo);"
));
PREPARE stmtIdx FROM @preparedStatementIdx;
EXECUTE stmtIdx;
DEALLOCATE PREPARE stmtIdx;
