-- Migration: 20260817_0006_add_modulo_fiscal_ativo_to_config.sql
-- Description: Adiciona a coluna modulo_fiscal_ativo na tabela ConfiguracoesEmissor para persistir a ativacao/desativacao do modulo fiscal

SET FOREIGN_KEY_CHECKS = 0;

SET @dbname = DATABASE();
SET @tablename = "ConfiguracoesEmissor";
SET @columnname = "modulo_fiscal_ativo";

SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE (table_name = @tablename) AND (table_schema = @dbname) AND (column_name = @columnname)) > 0,
  "SELECT 1",
  "ALTER TABLE ConfiguracoesEmissor ADD COLUMN modulo_fiscal_ativo TINYINT(1) NOT NULL DEFAULT 0 AFTER optante_simples;"
));
PREPARE stmt FROM @preparedStatement;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Se já existia certificado configurado ou inscricao municipal, marcar como ativo por compatibilidade
UPDATE ConfiguracoesEmissor 
SET modulo_fiscal_ativo = 1 
WHERE (caminho_certificado IS NOT NULL AND caminho_certificado != '') 
   OR (inscricao_municipal IS NOT NULL AND inscricao_municipal != '');

SET FOREIGN_KEY_CHECKS = 1;
