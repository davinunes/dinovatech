-- Migration: 20260817_0002_fix_banho_etapa_and_sync.sql
-- Description: Ajuste da coluna etapa para VARCHAR(50) e adição de horario_saida / id_agendamento em BanhoProducaoFila

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Alterar tipo da coluna etapa de ENUM para VARCHAR(50)
ALTER TABLE BanhoProducaoFila MODIFY COLUMN etapa VARCHAR(50) NOT NULL DEFAULT 'aguardando';

-- 2. Garantir coluna horario_saida
SET @dbname = DATABASE();
SET @tablename = "BanhoProducaoFila";
SET @columnname = "horario_saida";
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE (table_name = @tablename) AND (table_schema = @dbname) AND (column_name = @columnname)) > 0,
  "SELECT 1",
  "ALTER TABLE BanhoProducaoFila ADD COLUMN horario_saida DATETIME DEFAULT NULL;"
));
PREPARE stmt FROM @preparedStatement;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 3. Garantir coluna id_agendamento
SET @columnname = "id_agendamento";
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE (table_name = @tablename) AND (table_schema = @dbname) AND (column_name = @columnname)) > 0,
  "SELECT 1",
  "ALTER TABLE BanhoProducaoFila ADD COLUMN id_agendamento INT DEFAULT NULL;"
));
PREPARE stmt FROM @preparedStatement;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 4. Garantir tabela BanhoCheckinFotos se ainda não existir
CREATE TABLE IF NOT EXISTS `BanhoCheckinFotos` (
  `id_foto` INT NOT NULL AUTO_INCREMENT,
  `id_fila` INT NOT NULL,
  `id_pet` INT DEFAULT NULL,
  `foto_url` VARCHAR(255) NOT NULL,
  `descricao` VARCHAR(255) DEFAULT NULL,
  `criado_em` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_foto`),
  KEY `id_fila` (`id_fila`),
  CONSTRAINT `BanhoCheckinFotos_ibfk_1` FOREIGN KEY (`id_fila`) REFERENCES `BanhoProducaoFila` (`id_fila`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
