-- Migration: 20260205_0003_create_agendamentos.sql
-- Description: Create Agendamentos table and add Google Sync fields

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Create Agendamentos table
CREATE TABLE IF NOT EXISTS `Agendamentos` (
  `id_agendamento` int NOT NULL AUTO_INCREMENT,
  `id_vet` int NOT NULL,
  `id_cliente` int DEFAULT NULL,
  `id_pet` int DEFAULT NULL,
  `titulo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descricao` text COLLATE utf8mb4_unicode_ci,
  `data_inicio` datetime NOT NULL,
  `data_fim` datetime NOT NULL,
  `status` enum('Agendado','Confirmado','Realizado','Cancelado','Falta') COLLATE utf8mb4_unicode_ci DEFAULT 'Agendado',
  `google_event_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `criado_em` datetime DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_agendamento`),
  KEY `id_vet` (`id_vet`),
  KEY `id_cliente` (`id_cliente`),
  KEY `id_pet` (`id_pet`),
  CONSTRAINT `Agendamentos_ibfk_1` FOREIGN KEY (`id_vet`) REFERENCES `Veterinarios` (`id_vet`) ON DELETE CASCADE,
  CONSTRAINT `Agendamentos_ibfk_2` FOREIGN KEY (`id_cliente`) REFERENCES `Clientes` (`id_cliente`) ON DELETE SET NULL,
  CONSTRAINT `Agendamentos_ibfk_3` FOREIGN KEY (`id_pet`) REFERENCES `Pets` (`id_pet`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Add google_calendar_id to Veterinarios
SET @dbname = DATABASE();
SET @tablename = "Veterinarios";
SET @columnname = "google_calendar_id";
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE (table_name = @tablename) AND (table_schema = @dbname) AND (column_name = @columnname)) > 0,
  "SELECT 1",
  "ALTER TABLE Veterinarios ADD COLUMN google_calendar_id varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'ID da Agenda Google do Profissional';"
));
PREPARE stmt FROM @preparedStatement;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 3. Add google_service_account_json to ConfiguracoesEmissor (Encrypted content)
SET @tablename = "ConfiguracoesEmissor";
SET @columnname = "google_service_account_json";
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE (table_name = @tablename) AND (table_schema = @dbname) AND (column_name = @columnname)) > 0,
  "SELECT 1",
  "ALTER TABLE ConfiguracoesEmissor ADD COLUMN google_service_account_json longtext COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Conteúdo JSON da Service Account (Criptografado)';"
));
PREPARE stmt FROM @preparedStatement;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET FOREIGN_KEY_CHECKS = 1;
