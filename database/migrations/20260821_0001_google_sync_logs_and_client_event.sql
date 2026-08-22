-- Migration: 20260821_0001_google_sync_logs_and_client_event.sql
-- Description: Create GoogleSyncLogs table and add google_event_id_cliente to Agendamentos

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Create GoogleSyncLogs table
CREATE TABLE IF NOT EXISTS `GoogleSyncLogs` (
  `id_log` int NOT NULL AUTO_INCREMENT,
  `data_hora` datetime DEFAULT CURRENT_TIMESTAMP,
  `id_agendamento` int DEFAULT NULL,
  `calendar_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tipo_operacao` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'create, update, delete, test, list',
  `status` enum('sucesso','erro','aviso') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'sucesso',
  `http_code` int DEFAULT NULL,
  `mensagem` text COLLATE utf8mb4_unicode_ci,
  `payload_resumo` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id_log`),
  KEY `idx_id_agendamento` (`id_agendamento`),
  KEY `idx_calendar_id` (`calendar_id`),
  KEY `idx_data_hora` (`data_hora`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Add google_event_id_cliente to Agendamentos
SET @dbname = DATABASE();
SET @tablename = "Agendamentos";
SET @columnname = "google_event_id_cliente";
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE (table_name = @tablename) AND (table_schema = @dbname) AND (column_name = @columnname)) > 0,
  "SELECT 1",
  "ALTER TABLE Agendamentos ADD COLUMN google_event_id_cliente varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'ID do Evento na Agenda Google do Cliente' AFTER google_event_id;"
));
PREPARE stmt FROM @preparedStatement;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET FOREIGN_KEY_CHECKS = 1;
