-- Migration: 20260817_0001_create_banho_tosa_schema.sql
-- Description: Criação do esquema de Banho e Tosa, Pacotes/Combos, Saldos, Produção/Kanban e preferências

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Alterações na tabela Servicos 
SET @dbname = DATABASE();
SET @tablename = "Servicos";

SET @columnname = "disponivel_clinica";
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE (table_name = @tablename) AND (table_schema = @dbname) AND (column_name = @columnname)) > 0,
  "SELECT 1",
  "ALTER TABLE Servicos ADD COLUMN disponivel_clinica TINYINT(1) NOT NULL DEFAULT 1;"
));
PREPARE stmt FROM @preparedStatement;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @columnname = "disponivel_banho";
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE (table_name = @tablename) AND (table_schema = @dbname) AND (column_name = @columnname)) > 0,
  "SELECT 1",
  "ALTER TABLE Servicos ADD COLUMN disponivel_banho TINYINT(1) NOT NULL DEFAULT 0;"
));
PREPARE stmt FROM @preparedStatement;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @columnname = "duracao_minutos";
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE (table_name = @tablename) AND (table_schema = @dbname) AND (column_name = @columnname)) > 0,
  "SELECT 1",
  "ALTER TABLE Servicos ADD COLUMN duracao_minutos INT NOT NULL DEFAULT 30;"
));
PREPARE stmt FROM @preparedStatement;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @columnname = "icone_servico";
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE (table_name = @tablename) AND (table_schema = @dbname) AND (column_name = @columnname)) > 0,
  "SELECT 1",
  "ALTER TABLE Servicos ADD COLUMN icone_servico VARCHAR(100) DEFAULT 'pets';"
));
PREPARE stmt FROM @preparedStatement;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @columnname = "imagem_url";
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE (table_name = @tablename) AND (table_schema = @dbname) AND (column_name = @columnname)) > 0,
  "SELECT 1",
  "ALTER TABLE Servicos ADD COLUMN imagem_url VARCHAR(255) DEFAULT NULL;"
));
PREPARE stmt FROM @preparedStatement;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2. Alterações na tabela Pets
SET @tablename = "Pets";

SET @columnname = "porte";
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE (table_name = @tablename) AND (table_schema = @dbname) AND (column_name = @columnname)) > 0,
  "SELECT 1",
  "ALTER TABLE Pets ADD COLUMN porte ENUM('P', 'M', 'G', 'GG') NOT NULL DEFAULT 'P';"
));
PREPARE stmt FROM @preparedStatement;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @columnname = "tipo_pelagem";
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE (table_name = @tablename) AND (table_schema = @dbname) AND (column_name = @columnname)) > 0,
  "SELECT 1",
  "ALTER TABLE Pets ADD COLUMN tipo_pelagem ENUM('Curto', 'Medio', 'Longo', 'Dupla Pelagem') NOT NULL DEFAULT 'Curto';"
));
PREPARE stmt FROM @preparedStatement;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @columnname = "preferencias_banho";
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE (table_name = @tablename) AND (table_schema = @dbname) AND (column_name = @columnname)) > 0,
  "SELECT 1",
  "ALTER TABLE Pets ADD COLUMN preferencias_banho TEXT DEFAULT NULL;"
));
PREPARE stmt FROM @preparedStatement;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 3. Alterações na tabela ConfiguracoesEmissor
SET @tablename = "ConfiguracoesEmissor";

SET @columnname = "banho_checkin_foto_ativo";
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE (table_name = @tablename) AND (table_schema = @dbname) AND (column_name = @columnname)) > 0,
  "SELECT 1",
  "ALTER TABLE ConfiguracoesEmissor ADD COLUMN banho_checkin_foto_ativo TINYINT(1) NOT NULL DEFAULT 0;"
));
PREPARE stmt FROM @preparedStatement;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 4. Alterações na tabela Agendamentos
SET @tablename = "Agendamentos";

SET @columnname = "tipo_agenda";
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE (table_name = @tablename) AND (table_schema = @dbname) AND (column_name = @columnname)) > 0,
  "SELECT 1",
  "ALTER TABLE Agendamentos ADD COLUMN tipo_agenda ENUM('clinica', 'banho_tosa') NOT NULL DEFAULT 'clinica';"
));
PREPARE stmt FROM @preparedStatement;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @columnname = "id_cliente_pacote";
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE (table_name = @tablename) AND (table_schema = @dbname) AND (column_name = @columnname)) > 0,
  "SELECT 1",
  "ALTER TABLE Agendamentos ADD COLUMN id_cliente_pacote INT DEFAULT NULL;"
));
PREPARE stmt FROM @preparedStatement;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @columnname = "id_servico";
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE (table_name = @tablename) AND (table_schema = @dbname) AND (column_name = @columnname)) > 0,
  "SELECT 1",
  "ALTER TABLE Agendamentos ADD COLUMN id_servico INT DEFAULT NULL;"
));
PREPARE stmt FROM @preparedStatement;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 5. Tabelas de Pacotes / Combos
CREATE TABLE IF NOT EXISTS `Pacotes` (
  `id_pacote` INT NOT NULL AUTO_INCREMENT,
  `nome_pacote` VARCHAR(150) NOT NULL,
  `descricao` TEXT DEFAULT NULL,
  `valor_total` DECIMAL(10,2) NOT NULL DEFAULT '0.00',
  `is_recorrente` TINYINT(1) NOT NULL DEFAULT 0,
  `intervalo_dias_recorrencia` INT NOT NULL DEFAULT 30,
  `icone` VARCHAR(100) DEFAULT 'card_giftcard',
  `imagem_url` VARCHAR(255) DEFAULT NULL,
  `ativo` TINYINT(1) NOT NULL DEFAULT 1,
  `criado_em` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_pacote`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `PacoteItens` (
  `id_item` INT NOT NULL AUTO_INCREMENT,
  `id_pacote` INT NOT NULL,
  `id_servico` INT NOT NULL,
  `quantidade` INT NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_item`),
  KEY `id_pacote` (`id_pacote`),
  KEY `id_servico` (`id_servico`),
  CONSTRAINT `PacoteItens_ibfk_1` FOREIGN KEY (`id_pacote`) REFERENCES `Pacotes` (`id_pacote`) ON DELETE CASCADE,
  CONSTRAINT `PacoteItens_ibfk_2` FOREIGN KEY (`id_servico`) REFERENCES `Servicos` (`id_servico`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ClientePacotes` (
  `id_cliente_pacote` INT NOT NULL AUTO_INCREMENT,
  `id_cliente` INT NOT NULL,
  `id_pacote` INT NOT NULL,
  `id_recorrencia` INT DEFAULT NULL,
  `data_aquisicao` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `status` ENUM('ativo', 'esgotado', 'cancelado') NOT NULL DEFAULT 'ativo',
  PRIMARY KEY (`id_cliente_pacote`),
  KEY `id_cliente` (`id_cliente`),
  KEY `id_pacote` (`id_pacote`),
  KEY `id_recorrencia` (`id_recorrencia`),
  CONSTRAINT `ClientePacotes_ibfk_1` FOREIGN KEY (`id_cliente`) REFERENCES `Clientes` (`id_cliente`) ON DELETE CASCADE,
  CONSTRAINT `ClientePacotes_ibfk_2` FOREIGN KEY (`id_pacote`) REFERENCES `Pacotes` (`id_pacote`) ON DELETE CASCADE,
  CONSTRAINT `ClientePacotes_ibfk_3` FOREIGN KEY (`id_recorrencia`) REFERENCES `Recorrencias` (`id_recorrencia`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ClientePacoteSaldos` (
  `id_saldo` INT NOT NULL AUTO_INCREMENT,
  `id_cliente_pacote` INT NOT NULL,
  `id_servico` INT NOT NULL,
  `qtd_total` INT NOT NULL DEFAULT 1,
  `qtd_utilizada` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id_saldo`),
  KEY `id_cliente_pacote` (`id_cliente_pacote`),
  KEY `id_servico` (`id_servico`),
  CONSTRAINT `ClientePacoteSaldos_ibfk_1` FOREIGN KEY (`id_cliente_pacote`) REFERENCES `ClientePacotes` (`id_cliente_pacote`) ON DELETE CASCADE,
  CONSTRAINT `ClientePacoteSaldos_ibfk_2` FOREIGN KEY (`id_servico`) REFERENCES `Servicos` (`id_servico`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ClientePacoteConsumo` (
  `id_consumo` INT NOT NULL AUTO_INCREMENT,
  `id_cliente_pacote` INT NOT NULL,
  `id_servico` INT NOT NULL,
  `id_pet` INT NOT NULL,
  `id_agendamento` INT DEFAULT NULL,
  `data_consumo` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `observacao` VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`id_consumo`),
  KEY `id_cliente_pacote` (`id_cliente_pacote`),
  KEY `id_servico` (`id_servico`),
  KEY `id_pet` (`id_pet`),
  KEY `id_agendamento` (`id_agendamento`),
  CONSTRAINT `ClientePacoteConsumo_ibfk_1` FOREIGN KEY (`id_cliente_pacote`) REFERENCES `ClientePacotes` (`id_cliente_pacote`) ON DELETE CASCADE,
  CONSTRAINT `ClientePacoteConsumo_ibfk_2` FOREIGN KEY (`id_servico`) REFERENCES `Servicos` (`id_servico`) ON DELETE CASCADE,
  CONSTRAINT `ClientePacoteConsumo_ibfk_3` FOREIGN KEY (`id_pet`) REFERENCES `Pets` (`id_pet`) ON DELETE CASCADE,
  CONSTRAINT `ClientePacoteConsumo_ibfk_4` FOREIGN KEY (`id_agendamento`) REFERENCES `Agendamentos` (`id_agendamento`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Linha de Produção / Kanban Banho e Tosa
CREATE TABLE IF NOT EXISTS `BanhoProducaoFila` (
  `id_fila` INT NOT NULL AUTO_INCREMENT,
  `id_agendamento` INT DEFAULT NULL,
  `id_pet` INT NOT NULL,
  `id_colaborador` INT DEFAULT NULL,
  `etapa` VARCHAR(50) NOT NULL DEFAULT 'aguardando',
  `horario_entrada` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `horario_inicio` DATETIME DEFAULT NULL,
  `horario_fim` DATETIME DEFAULT NULL,
  `horario_saida` DATETIME DEFAULT NULL,
  `observacoes_estetica` TEXT DEFAULT NULL,
  `ordem` INT DEFAULT '0',
  PRIMARY KEY (`id_fila`),
  KEY `id_agendamento` (`id_agendamento`),
  KEY `id_pet` (`id_pet`),
  KEY `id_colaborador` (`id_colaborador`),
  CONSTRAINT `BanhoProducaoFila_ibfk_1` FOREIGN KEY (`id_agendamento`) REFERENCES `Agendamentos` (`id_agendamento`) ON DELETE SET NULL,
  CONSTRAINT `BanhoProducaoFila_ibfk_2` FOREIGN KEY (`id_pet`) REFERENCES `Pets` (`id_pet`) ON DELETE CASCADE,
  CONSTRAINT `BanhoProducaoFila_ibfk_3` FOREIGN KEY (`id_colaborador`) REFERENCES `Veterinarios` (`id_vet`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ajuste preventivo caso a tabela já tenha sido criada anteriormente com ENUM
ALTER TABLE BanhoProducaoFila MODIFY COLUMN etapa VARCHAR(50) NOT NULL DEFAULT 'aguardando';

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
