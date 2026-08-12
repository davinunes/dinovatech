-- Migration: 20260812_0004_create_internacoes_tables.sql
-- Description: Create tables for Pet Hospitalization module (Internações, Dias e Medicações)

SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `Internacoes` (
  `id_internacao` INT NOT NULL AUTO_INCREMENT,
  `id_pet` INT NOT NULL,
  `id_vet` INT DEFAULT NULL,
  `data_internacao` DATETIME NOT NULL,
  `data_alta` DATETIME DEFAULT NULL,
  `suspeita_clinica` TEXT DEFAULT NULL,
  `status` ENUM('internado', 'alta', 'obito', 'cancelado') NOT NULL DEFAULT 'internado',
  `observacoes` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_internacao`),
  KEY `id_pet` (`id_pet`),
  KEY `id_vet` (`id_vet`),
  CONSTRAINT `Internacoes_ibfk_1` FOREIGN KEY (`id_pet`) REFERENCES `Pets` (`id_pet`) ON DELETE CASCADE,
  CONSTRAINT `Internacoes_ibfk_2` FOREIGN KEY (`id_vet`) REFERENCES `Veterinarios` (`id_vet`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `InternacaoDias` (
  `id_dia` INT NOT NULL AUTO_INCREMENT,
  `id_internacao` INT NOT NULL,
  `data_dia` DATE NOT NULL,
  `soro` VARCHAR(255) DEFAULT NULL,
  `volume` VARCHAR(100) DEFAULT NULL,
  `frequencia` VARCHAR(100) DEFAULT NULL,
  `observacoes` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_dia`),
  KEY `id_internacao` (`id_internacao`),
  KEY `data_dia` (`data_dia`),
  CONSTRAINT `InternacaoDias_ibfk_1` FOREIGN KEY (`id_internacao`) REFERENCES `Internacoes` (`id_internacao`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `InternacaoMedicacoes` (
  `id_medicacao` INT NOT NULL AUTO_INCREMENT,
  `id_dia` INT NOT NULL,
  `medicacao` VARCHAR(255) NOT NULL,
  `dose` VARCHAR(100) DEFAULT NULL,
  `via` VARCHAR(100) DEFAULT NULL,
  `horarios` TEXT DEFAULT NULL,
  `ordem` INT DEFAULT '0',
  PRIMARY KEY (`id_medicacao`),
  KEY `id_dia` (`id_dia`),
  CONSTRAINT `InternacaoMedicacoes_ibfk_1` FOREIGN KEY (`id_dia`) REFERENCES `InternacaoDias` (`id_dia`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
