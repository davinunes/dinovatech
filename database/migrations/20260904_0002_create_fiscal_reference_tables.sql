-- Migration: 20260904_0002_create_fiscal_reference_tables.sql
-- Descrição: Criação das tabelas de referência fiscal nacional com prefixo TribRef

CREATE TABLE IF NOT EXISTS TribRefTributacaoNacional (
    codigo_trib_nac CHAR(6) NOT NULL PRIMARY KEY,
    item_lc116 VARCHAR(10) NULL,
    descricao TEXT NULL,
    cnae_sugerido VARCHAR(10) NULL,
    aliquota_padrao DECIMAL(5,2) DEFAULT 2.00,
    INDEX idx_item_lc116 (item_lc116),
    INDEX idx_cnae (cnae_sugerido)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS TribRefNbs (
    codigo_nbs VARCHAR(9) NOT NULL PRIMARY KEY,
    descricao TEXT NULL,
    item_lc116_relacionado VARCHAR(10) NULL,
    INDEX idx_item_lc116 (item_lc116_relacionado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS TribRefCorrelacaoIbsCbs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo_trib_nac CHAR(6) NULL,
    codigo_nbs VARCHAR(9) NULL,
    cst_ibs_cbs VARCHAR(3) DEFAULT '000',
    classificacao_trib VARCHAR(6) DEFAULT '000000',
    indicador_operacao VARCHAR(6) DEFAULT '050101',
    INDEX idx_trib_nac (codigo_trib_nac),
    INDEX idx_nbs (codigo_nbs)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
