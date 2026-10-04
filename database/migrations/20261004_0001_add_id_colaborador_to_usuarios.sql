-- Migration: 20261004_0001_add_id_colaborador_to_usuarios.sql
-- Description: Adiciona coluna id_colaborador na tabela Usuarios vinculando ao colaborador/veterinário (Veterinarios.id_vet)

SET FOREIGN_KEY_CHECKS = 0;

SET @dbname = DATABASE();
SET @tablename = "Usuarios";
SET @columnname = "id_colaborador";

-- 1. Adiciona coluna id_colaborador se não existir
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE (table_name = @tablename) AND (table_schema = @dbname) AND (column_name = @columnname)) > 0,
  "SELECT 1",
  "ALTER TABLE Usuarios ADD COLUMN id_colaborador INT NULL DEFAULT NULL AFTER nivel_acesso;"
));
PREPARE stmt FROM @preparedStatement;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2. Adiciona índice para performance de busca
SET @indexname = "idx_usuarios_colaborador";
SET @prepIndex = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE (table_name = @tablename) AND (table_schema = @dbname) AND (index_name = @indexname)) > 0,
  "SELECT 1",
  "ALTER TABLE Usuarios ADD INDEX idx_usuarios_colaborador (id_colaborador);"
));
PREPARE stmtIdx FROM @prepIndex;
EXECUTE stmtIdx;
DEALLOCATE PREPARE stmtIdx;

-- 3. Adiciona constraint foreign key se não existir
SET @fkname = "fk_usuarios_colaborador";
SET @fkStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE (table_name = @tablename) AND (constraint_schema = @dbname) AND (constraint_name = @fkname)) > 0,
  "SELECT 1",
  "ALTER TABLE Usuarios ADD CONSTRAINT fk_usuarios_colaborador FOREIGN KEY (id_colaborador) REFERENCES Veterinarios(id_vet) ON DELETE SET NULL ON UPDATE CASCADE;"
));
PREPARE stmtFk FROM @fkStatement;
EXECUTE stmtFk;
DEALLOCATE PREPARE stmtFk;

SET FOREIGN_KEY_CHECKS = 1;
