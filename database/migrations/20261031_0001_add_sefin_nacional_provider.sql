-- Migração para suportar o provedor SefinNacional REST (Emissor Público Nacional direto)
-- Data: 2026-10-31

ALTER TABLE ConfiguracoesEmissor MODIFY COLUMN nfse_provider VARCHAR(30) NOT NULL DEFAULT 'nacional';

