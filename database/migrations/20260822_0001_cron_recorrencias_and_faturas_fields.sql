-- Migration: 20260822_0001_cron_recorrencias_and_faturas_fields.sql
-- Description: Cria tabela CronLogs, adiciona dia_vencimento em Recorrencias e data_emissao_nfse em Faturas

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Criação da tabela CronLogs
CREATE TABLE IF NOT EXISTS `CronLogs` (
  `id_cron_log` int NOT NULL AUTO_INCREMENT,
  `data_execucao` datetime DEFAULT CURRENT_TIMESTAMP,
  `tipo_tarefa` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'faturas_recorrencias',
  `status` enum('sucesso','erro','aviso') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'sucesso',
  `faturas_geradas` int DEFAULT '0',
  `valor_total_gerado` decimal(10,2) DEFAULT '0.00',
  `detalhes_json` longtext COLLATE utf8mb4_unicode_ci,
  `origem` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'cron',
  PRIMARY KEY (`id_cron_log`),
  KEY `idx_data_execucao` (`data_execucao`),
  KEY `idx_tipo_tarefa` (`tipo_tarefa`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Adiciona coluna dia_vencimento na tabela Recorrencias
SET @dbname = DATABASE();
SET @tablename = "Recorrencias";
SET @columnname = "dia_vencimento";
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE (table_name = @tablename) AND (table_schema = @dbname) AND (column_name = @columnname)) > 0,
  "SELECT 1",
  "ALTER TABLE Recorrencias ADD COLUMN dia_vencimento tinyint DEFAULT NULL COMMENT 'Dia preferencial de vencimento da fatura (1 a 31)' AFTER intervalo;"
));
PREPARE stmt FROM @preparedStatement;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 3. Adiciona coluna data_emissao_nfse na tabela Faturas
SET @tablename = "Faturas";
SET @columnname = "data_emissao_nfse";
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE (table_name = @tablename) AND (table_schema = @dbname) AND (column_name = @columnname)) > 0,
  "SELECT 1",
  "ALTER TABLE Faturas ADD COLUMN data_emissao_nfse datetime DEFAULT NULL COMMENT 'Data/hora de emissão da NFSe' AFTER possui_nfse;"
));
PREPARE stmt FROM @preparedStatement;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET FOREIGN_KEY_CHECKS = 1;
