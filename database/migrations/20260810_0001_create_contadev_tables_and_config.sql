-- Migration: 20260810_0001_create_contadev_tables_and_config.sql
-- Description: Estrutura para integração com a plataforma ContaDev-Contabilidade

-- 1. Colunas de Configuração do ContaDev em ConfiguracoesEmissor
ALTER TABLE ConfiguracoesEmissor ADD COLUMN contadev_email VARCHAR(255) DEFAULT NULL;
ALTER TABLE ConfiguracoesEmissor ADD COLUMN contadev_token TEXT DEFAULT NULL;
ALTER TABLE ConfiguracoesEmissor ADD COLUMN contadev_user_id VARCHAR(255) DEFAULT NULL;
ALTER TABLE ConfiguracoesEmissor ADD COLUMN contadev_cnpj_id VARCHAR(255) DEFAULT NULL;
ALTER TABLE ConfiguracoesEmissor ADD COLUMN contadev_company_name VARCHAR(255) DEFAULT NULL;
ALTER TABLE ConfiguracoesEmissor ADD COLUMN contadev_user_name VARCHAR(255) DEFAULT NULL;
ALTER TABLE ConfiguracoesEmissor ADD COLUMN contadev_ativo TINYINT(1) DEFAULT 0;

-- 2. Tabela de Sincronização Relacional de Notas Fiscais com ContaDev
CREATE TABLE IF NOT EXISTS `nf_contadev_sync` (
  `id_sync` INT AUTO_INCREMENT PRIMARY KEY,
  `id_fatura` INT NOT NULL,
  `contadev_nf_id` VARCHAR(255) DEFAULT NULL,
  `external_id` VARCHAR(100) DEFAULT NULL,
  `tomador_id` VARCHAR(255) DEFAULT NULL,
  `pdf_s3_uri` VARCHAR(500) DEFAULT NULL,
  `xml_s3_uri` VARCHAR(500) DEFAULT NULL,
  `valor` DECIMAL(10,2) DEFAULT NULL,
  `issued_at` DATE DEFAULT NULL,
  `status_importacao` VARCHAR(50) DEFAULT 'pendente',
  `import_dedup_key` VARCHAR(255) DEFAULT NULL,
  `detalhes_resposta` LONGTEXT DEFAULT NULL,
  `criado_em` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `id_fatura` (`id_fatura`),
  KEY `contadev_nf_id` (`contadev_nf_id`),
  CONSTRAINT `fk_nf_contadev_sync_fatura` FOREIGN KEY (`id_fatura`) REFERENCES `Faturas` (`id_fatura`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Tabela de Histórico e Logs da API ContaDev
CREATE TABLE IF NOT EXISTS `config_contadev_logs` (
  `id_log` INT AUTO_INCREMENT PRIMARY KEY,
  `id_fatura` INT DEFAULT NULL,
  `acao` VARCHAR(100) NOT NULL,
  `status` ENUM('sucesso', 'erro', 'info') NOT NULL,
  `mensagem` TEXT DEFAULT NULL,
  `payload_requisicao` LONGTEXT DEFAULT NULL,
  `payload_resposta` LONGTEXT DEFAULT NULL,
  `criado_em` DATETIME DEFAULT CURRENT_TIMESTAMP,
  KEY `id_fatura` (`id_fatura`),
  KEY `acao` (`acao`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
