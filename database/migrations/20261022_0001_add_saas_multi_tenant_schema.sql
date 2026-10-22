-- Migration: 20261022_0001_add_saas_multi_tenant_schema.sql
-- Description: Adiciona campos de suporte a SaaS em Servicos e cria tabela saas_tenants para orquestração de containers K3s

SET FOREIGN_KEY_CHECKS = 0;

SET @dbname = DATABASE();

-- 1. Colunas adicionais na tabela Servicos
-- 1.1 tipo_servico
SET @tablename = "Servicos";
SET @columnname = "tipo_servico";
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE (table_name = @tablename) AND (table_schema = @dbname) AND (column_name = @columnname)) > 0,
  "SELECT 1",
  "ALTER TABLE Servicos ADD COLUMN tipo_servico VARCHAR(30) NOT NULL DEFAULT 'PADRAO' COMMENT 'PADRAO ou SAAS_INSTANCE' AFTER ativo;"
));
PREPARE stmt FROM @preparedStatement;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 1.2 saas_default_image
SET @columnname = "saas_default_image";
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE (table_name = @tablename) AND (table_schema = @dbname) AND (column_name = @columnname)) > 0,
  "SELECT 1",
  "ALTER TABLE Servicos ADD COLUMN saas_default_image VARCHAR(100) DEFAULT 'dinovatech:latest' COMMENT 'Imagem Docker template para novos tenants' AFTER tipo_servico;"
));
PREPARE stmt FROM @preparedStatement;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 1.3 saas_default_memory
SET @columnname = "saas_default_memory";
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE (table_name = @tablename) AND (table_schema = @dbname) AND (column_name = @columnname)) > 0,
  "SELECT 1",
  "ALTER TABLE Servicos ADD COLUMN saas_default_memory VARCHAR(20) DEFAULT '128Mi' COMMENT 'Limite de memória padrão (ex: 128Mi)' AFTER saas_default_image;"
));
PREPARE stmt FROM @preparedStatement;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 1.4 saas_default_cpu
SET @columnname = "saas_default_cpu";
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE (table_name = @tablename) AND (table_schema = @dbname) AND (column_name = @columnname)) > 0,
  "SELECT 1",
  "ALTER TABLE Servicos ADD COLUMN saas_default_cpu VARCHAR(20) DEFAULT '250m' COMMENT 'Limite de CPU padrão (ex: 250m)' AFTER saas_default_memory;"
));
PREPARE stmt FROM @preparedStatement;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2. Criação da tabela saas_tenants
CREATE TABLE IF NOT EXISTS `saas_tenants` (
  `id_tenant` INT AUTO_INCREMENT PRIMARY KEY,
  `id_recorrencia` INT NOT NULL COMMENT 'Vínculo com o contrato/recorrência',
  `id_cliente` INT NOT NULL COMMENT 'Cliente proprietário da instância',
  `slug_instancia` VARCHAR(60) NOT NULL UNIQUE COMMENT 'Identificador único (ex: clinica-alfa)',
  `subdominio` VARCHAR(100) NOT NULL UNIQUE COMMENT 'Subdomínio principal (ex: alfa.dinovatech.com.br)',
  `dominio_customizado` VARCHAR(150) NULL COMMENT 'Domínio próprio via CNAME (ex: sistema.clinicavet.com.br)',
  `namespace_k8s` VARCHAR(63) NOT NULL UNIQUE COMMENT 'Namespace isolado no Kubernetes K3s',
  `db_name` VARCHAR(64) NOT NULL COMMENT 'Nome do banco de dados isolado',
  `db_user` VARCHAR(64) NOT NULL COMMENT 'Usuário do banco de dados do tenant',
  `db_password` VARCHAR(100) NOT NULL COMMENT 'Senha do banco de dados do tenant',
  `versao_imagem` VARCHAR(100) NOT NULL DEFAULT 'dinovatech:latest',
  `limite_memoria` VARCHAR(20) NOT NULL DEFAULT '128Mi',
  `limite_cpu` VARCHAR(20) NOT NULL DEFAULT '250m',
  `status_pod` ENUM('PENDING', 'RUNNING', 'STOPPED', 'SUSPENDED', 'ERROR') NOT NULL DEFAULT 'PENDING',
  `ultimo_erro` TEXT NULL,
  `data_criacao` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `data_atualizacao` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_saas_recorrencia` (`id_recorrencia`),
  INDEX `idx_saas_cliente` (`id_cliente`),
  INDEX `idx_saas_status` (`status_pod`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
