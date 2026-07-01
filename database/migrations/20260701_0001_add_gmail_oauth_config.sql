-- Migration: 20260701_0001_add_gmail_oauth_config.sql
-- Description: Adiciona colunas para controle do Gmail OAuth, ID do modelo de e-mail e Token de Acesso para Faturas

ALTER TABLE ConfiguracoesEmissor ADD COLUMN google_oauth_client_id VARCHAR(255) DEFAULT NULL;
ALTER TABLE ConfiguracoesEmissor ADD COLUMN google_oauth_client_secret TEXT DEFAULT NULL;
ALTER TABLE ConfiguracoesEmissor ADD COLUMN google_oauth_email VARCHAR(255) DEFAULT NULL;
ALTER TABLE ConfiguracoesEmissor ADD COLUMN google_oauth_refresh_token TEXT DEFAULT NULL;
ALTER TABLE ConfiguracoesEmissor ADD COLUMN email_fatura_template_id INT DEFAULT NULL;

ALTER TABLE Faturas ADD COLUMN token_acesso VARCHAR(64) DEFAULT NULL;
