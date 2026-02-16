-- Adiciona coluna para permitir cadastro sem CPF
ALTER TABLE ConfiguracoesEmissor ADD COLUMN permitir_cadastro_sem_cpf tinyint(1) DEFAULT '0';
