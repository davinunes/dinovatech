-- Migration: Expand codigo_verificacao and numero_nota columns in NfseEmissoes table for Padrão Nacional 50-digit keys
ALTER TABLE NfseEmissoes MODIFY COLUMN codigo_verificacao VARCHAR(100) NULL;
ALTER TABLE NfseEmissoes MODIFY COLUMN numero_nota VARCHAR(50) NULL;
