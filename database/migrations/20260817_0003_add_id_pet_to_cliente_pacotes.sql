-- Migration: 20260817_0003_add_id_pet_to_cliente_pacotes.sql
-- Description: Adiciona coluna id_pet à tabela ClientePacotes para vincular pacotes a pets específicos

SET FOREIGN_KEY_CHECKS = 0;

SET @dbname = DATABASE();
SET @tablename = "ClientePacotes";
SET @columnname = "id_pet";
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE (table_name = @tablename) AND (table_schema = @dbname) AND (column_name = @columnname)) > 0,
  "SELECT 1",
  "ALTER TABLE ClientePacotes ADD COLUMN id_pet INT DEFAULT NULL AFTER id_pacote;"
));
PREPARE stmt FROM @preparedStatement;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET FOREIGN_KEY_CHECKS = 1;
