-- Migration: Adicionar coluna para Certificado Digital A1 (.pfx) em Base64
-- Data: 2026-02-17

ALTER TABLE `ConfiguracoesEmissor`
  ADD COLUMN `certificado_pfx_base64` LONGTEXT DEFAULT NULL AFTER `caminho_certificado`;
