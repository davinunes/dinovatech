-- Migration: Adicionar colunas para certificados e chaves da API do Banco Inter em Base64
-- Data: 2026-02-17

ALTER TABLE `ConfiguracoesEmissor`
  ADD COLUMN `api_inter_cert_base64` LONGTEXT DEFAULT NULL AFTER `api_inter_cert_path`,
  ADD COLUMN `api_inter_key_base64` LONGTEXT DEFAULT NULL AFTER `api_inter_key_path`,
  ADD COLUMN `api_inter_ca_base64` LONGTEXT DEFAULT NULL AFTER `api_inter_ca_path`;
