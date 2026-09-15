-- Migration: 20260915_0001_create_modulo_despesas.sql
-- Descrição: Criação das tabelas do Módulo de Despesas (Contas a Pagar), Fornecedores, Centros de Custo e Anexos

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Tabela de Fornecedores
CREATE TABLE IF NOT EXISTS `Fornecedores` (
  `id_fornecedor` INT NOT NULL AUTO_INCREMENT,
  `razao_social` VARCHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nome_fantasia` VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cpf_cnpj` VARCHAR(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefone` VARCHAR(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` VARCHAR(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contato_responsavel` VARCHAR(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `observacoes` TEXT COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ativo` TINYINT(1) DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_fornecedor`),
  KEY `idx_fornecedor_nome` (`razao_social`, `nome_fantasia`),
  KEY `idx_fornecedor_doc` (`cpf_cnpj`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Tabela de Centros de Custo
CREATE TABLE IF NOT EXISTS `CentrosCusto` (
  `id_centro_custo` INT NOT NULL AUTO_INCREMENT,
  `nome` VARCHAR(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descricao` VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cor` VARCHAR(20) COLLATE utf8mb4_unicode_ci DEFAULT '#0284c7',
  `ativo` TINYINT(1) DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_centro_custo`),
  UNIQUE KEY `uniq_centrocusto_nome` (`nome`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Centros de Custo Padrão
INSERT IGNORE INTO `CentrosCusto` (`nome`, `descricao`, `cor`) VALUES
('Operacional / Insumos', 'Medicamentos, materiais e produtos de uso contínuo', '#0284c7'),
('Aluguel & Infraestrutura', 'Locação, condomínio, luz, água e internet', '#7c3aed'),
('Salários & Pró-labore', 'Folha de pagamento e retiradas', '#059669'),
('Serviços & Terceiros', 'Contabilidade, consultorias e limpeza terceirizada', '#d97706'),
('Marketing & Vendas', 'Anúncios, mídias e materiais promocionais', '#db2777'),
('Impostos & Tributos', 'DAS, impostos municipais e federais', '#dc2626');

-- 3. Tabela de Despesas (Contas a Pagar)
CREATE TABLE IF NOT EXISTS `Despesas` (
  `id_despesa` INT NOT NULL AUTO_INCREMENT,
  `id_fornecedor` INT NOT NULL,
  `id_centro_custo` INT DEFAULT NULL,
  `descricao` VARCHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo` ENUM('avulsa', 'parcelada', 'recorrente') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'avulsa',
  `status` ENUM('Em Aberto', 'Liquidada', 'Cancelada') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Em Aberto',
  `data_competencia` VARCHAR(7) COLLATE utf8mb4_unicode_ci NOT NULL,
  `data_vencimento` DATE NOT NULL,
  `data_pagamento` DATETIME DEFAULT NULL,
  `forma_pagamento` VARCHAR(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `valor` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `valor_pago` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `numero_documento` VARCHAR(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `observacoes` TEXT COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_despesa_pai` INT DEFAULT NULL,
  `parcela_atual` INT DEFAULT 1,
  `total_parcelas` INT DEFAULT 1,
  `recorrencia_ativa` TINYINT(1) DEFAULT 0,
  `dia_vencimento_recorrencia` INT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_despesa`),
  KEY `idx_despesa_fornecedor` (`id_fornecedor`),
  KEY `idx_despesa_centro_custo` (`id_centro_custo`),
  KEY `idx_despesa_competencia` (`data_competencia`),
  KEY `idx_despesa_vencimento` (`data_vencimento`),
  KEY `idx_despesa_status` (`status`),
  KEY `idx_despesa_pai` (`id_despesa_pai`),
  CONSTRAINT `Despesas_ibfk_1` FOREIGN KEY (`id_fornecedor`) REFERENCES `Fornecedores` (`id_fornecedor`),
  CONSTRAINT `Despesas_ibfk_2` FOREIGN KEY (`id_centro_custo`) REFERENCES `CentrosCusto` (`id_centro_custo`) ON DELETE SET NULL,
  CONSTRAINT `Despesas_ibfk_3` FOREIGN KEY (`id_despesa_pai`) REFERENCES `Despesas` (`id_despesa`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Tabela de Vinculação de Arquivos / Comprovantes
CREATE TABLE IF NOT EXISTS `DespesaArquivos` (
  `id_vinculo` INT NOT NULL AUTO_INCREMENT,
  `id_despesa` INT NOT NULL,
  `id_arquivo` INT NOT NULL,
  `tipo_documento` VARCHAR(50) COLLATE utf8mb4_unicode_ci DEFAULT 'Outro',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_vinculo`),
  KEY `idx_despesa_anexo` (`id_despesa`),
  KEY `idx_arquivo_anexo` (`id_arquivo`),
  CONSTRAINT `DespesaArquivos_ibfk_1` FOREIGN KEY (`id_despesa`) REFERENCES `Despesas` (`id_despesa`) ON DELETE CASCADE,
  CONSTRAINT `DespesaArquivos_ibfk_2` FOREIGN KEY (`id_arquivo`) REFERENCES `Arquivos` (`id_arquivo`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
