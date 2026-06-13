-- Adiciona colunas para controle da Landing Page na tabela ConfiguracoesEmissor
ALTER TABLE ConfiguracoesEmissor
ADD COLUMN landing_page_theme VARCHAR(50) DEFAULT 'default',
ADD COLUMN landing_page_path VARCHAR(255) DEFAULT '';
