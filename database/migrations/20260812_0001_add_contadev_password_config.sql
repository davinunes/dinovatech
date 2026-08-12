-- Migration: 20260812_0001_add_contadev_password_config.sql
-- Description: Adiciona coluna contadev_password em ConfiguracoesEmissor para re-autenticação automática de token

ALTER TABLE ConfiguracoesEmissor ADD COLUMN contadev_password TEXT DEFAULT NULL;
