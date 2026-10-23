-- Migration: 20261023_0002_add_tipo_cobranca_to_recorrencias.sql
-- Description: Adiciona as colunas tipo_cobranca e motivo_cortesia na tabela Recorrencias para suportar contratos Cortesia e Teste (sem geração de faturas)

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Adiciona coluna tipo_cobranca na tabela Recorrencias se não existir
SET @dbname = DATABASE();
SET @tablename = "Recorrencias";
SET @columnname = "tipo_cobranca";
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE (table_name = @tablename) AND (table_schema = @dbname) AND (column_name = @columnname)) > 0,
  "SELECT 1",
  "ALTER TABLE Recorrencias ADD COLUMN tipo_cobranca ENUM('COMERCIAL', 'CORTESIA', 'TESTE') NOT NULL DEFAULT 'COMERCIAL' COMMENT 'Modalidade de faturamento: COMERCIAL (gera fatura), CORTESIA (isento) ou TESTE (trial)' AFTER tipo_periodo;"
));
PREPARE stmt FROM @preparedStatement;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2. Adiciona coluna motivo_cortesia na tabela Recorrencias se não existir
SET @columnname = "motivo_cortesia";
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE (table_name = @tablename) AND (table_schema = @dbname) AND (column_name = @columnname)) > 0,
  "SELECT 1",
  "ALTER TABLE Recorrencias ADD COLUMN motivo_cortesia VARCHAR(255) DEFAULT NULL COMMENT 'Justificativa ou detalhes da concessão de cortesia ou teste' AFTER tipo_cobranca;"
));
PREPARE stmt FROM @preparedStatement;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 3. Adiciona índice para otimização das consultas de faturamento e listagens
SET @indexname = "idx_recorrencias_tipo_cobranca";
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE (table_name = @tablename) AND (table_schema = @dbname) AND (index_name = @indexname)) > 0,
  "SELECT 1",
  "ALTER TABLE Recorrencias ADD INDEX idx_recorrencias_tipo_cobranca (tipo_cobranca);"
));
PREPARE stmt FROM @preparedStatement;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET FOREIGN_KEY_CHECKS = 1;
