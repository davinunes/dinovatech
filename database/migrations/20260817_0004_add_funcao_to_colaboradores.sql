-- Migration: 20260817_0004_add_funcao_to_colaboradores.sql
-- Description: Adiciona coluna funcao e flags de modulo em Veterinarios, e torna CRMV opcional para colaboradores de outras funcoes

SET FOREIGN_KEY_CHECKS = 0;

SET @dbname = DATABASE();
SET @tablename = "Veterinarios";

-- 1. Add funcao column
SET @columnname = "funcao";
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE (table_name = @tablename) AND (table_schema = @dbname) AND (column_name = @columnname)) > 0,
  "SELECT 1",
  "ALTER TABLE Veterinarios ADD COLUMN funcao VARCHAR(50) NOT NULL DEFAULT 'veterinario' AFTER nome;"
));
PREPARE stmt FROM @preparedStatement;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2. Add realiza_banho column
SET @columnname = "realiza_banho";
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE (table_name = @tablename) AND (table_schema = @dbname) AND (column_name = @columnname)) > 0,
  "SELECT 1",
  "ALTER TABLE Veterinarios ADD COLUMN realiza_banho TINYINT(1) NOT NULL DEFAULT 0 AFTER funcao;"
));
PREPARE stmt FROM @preparedStatement;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 3. Add realiza_clinica column
SET @columnname = "realiza_clinica";
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE (table_name = @tablename) AND (table_schema = @dbname) AND (column_name = @columnname)) > 0,
  "SELECT 1",
  "ALTER TABLE Veterinarios ADD COLUMN realiza_clinica TINYINT(1) NOT NULL DEFAULT 1 AFTER realiza_banho;"
));
PREPARE stmt FROM @preparedStatement;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 4. Make CRMV & UF optional for non-vet roles
ALTER TABLE Veterinarios MODIFY COLUMN crmv VARCHAR(20) DEFAULT NULL;
ALTER TABLE Veterinarios MODIFY COLUMN uf_crmv CHAR(2) DEFAULT NULL;

SET FOREIGN_KEY_CHECKS = 1;
