-- Migration: 20261101_0001_expand_nfse_emissoes_provider.sql
-- Description: Garante compatibilidade do provider 'sefin_nacional' e amplia colunas da tabela NfseEmissoes

ALTER TABLE NfseEmissoes MODIFY COLUMN provider VARCHAR(50) NOT NULL DEFAULT 'legacy';
ALTER TABLE NfseEmissoes MODIFY COLUMN codigo_verificacao VARCHAR(100) NULL;
ALTER TABLE NfseEmissoes MODIFY COLUMN numero_nota VARCHAR(50) NULL;
