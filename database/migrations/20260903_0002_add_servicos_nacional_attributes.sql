-- Migration: 20260903_0002_add_servicos_nacional_attributes.sql
-- Description: Adiciona atributos exigidos pelo Novo Padrão Nacional da NFS-e (cTribNac, tributação ISSQN, Reforma Tributária IBS/CBS) na tabela Servicos

SET FOREIGN_KEY_CHECKS = 0;

SET @dbname = DATABASE();
SET @tablename = "Servicos";

-- 1. Coluna codigo_tributacao_nacional (6 dígitos: Item LC116 + Subitem + Desdobro, ex: 010701)
SET @columnname = "codigo_tributacao_nacional";
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE (table_name = @tablename) AND (table_schema = @dbname) AND (column_name = @columnname)) > 0,
  "SELECT 1",
  "ALTER TABLE Servicos ADD COLUMN codigo_tributacao_nacional VARCHAR(6) NULL AFTER item_lista_servico;"
));
PREPARE stmt FROM @preparedStatement;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2. Coluna tributacao_issqn (1=Tributável, 2=Imunidade, 3=Exportação, 4=Não Incidência)
SET @columnname = "tributacao_issqn";
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE (table_name = @tablename) AND (table_schema = @dbname) AND (column_name = @columnname)) > 0,
  "SELECT 1",
  "ALTER TABLE Servicos ADD COLUMN tributacao_issqn TINYINT NOT NULL DEFAULT 1 AFTER aliquota_iss;"
));
PREPARE stmt FROM @preparedStatement;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 3. Coluna cst_ibs_cbs (3 dígitos - Situação Tributária IBS/CBS, padrão '000')
SET @columnname = "cst_ibs_cbs";
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE (table_name = @tablename) AND (table_schema = @dbname) AND (column_name = @columnname)) > 0,
  "SELECT 1",
  "ALTER TABLE Servicos ADD COLUMN cst_ibs_cbs VARCHAR(3) NOT NULL DEFAULT '000' AFTER iss_retido;"
));
PREPARE stmt FROM @preparedStatement;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 4. Coluna classificacao_trib_ibs_cbs (6 dígitos - Classificação Tributária IBS/CBS, padrão '000000')
SET @columnname = "classificacao_trib_ibs_cbs";
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE (table_name = @tablename) AND (table_schema = @dbname) AND (column_name = @columnname)) > 0,
  "SELECT 1",
  "ALTER TABLE Servicos ADD COLUMN classificacao_trib_ibs_cbs VARCHAR(6) NOT NULL DEFAULT '000000' AFTER cst_ibs_cbs;"
));
PREPARE stmt FROM @preparedStatement;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 5. Coluna indicador_operacao (6 dígitos - Código Indicador de Fornecimento IBS/CBS, padrão '050101')
SET @columnname = "indicador_operacao";
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE (table_name = @tablename) AND (table_schema = @dbname) AND (column_name = @columnname)) > 0,
  "SELECT 1",
  "ALTER TABLE Servicos ADD COLUMN indicador_operacao VARCHAR(6) NOT NULL DEFAULT '050101' AFTER classificacao_trib_ibs_cbs;"
));
PREPARE stmt FROM @preparedStatement;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 6. Preenchimento retroativo inteligente de codigo_tributacao_nacional baseado no item_lista_servico existente
-- Se item_lista_servico = '01.07', vira '010701'; se '01.05', vira '010501', etc.
UPDATE Servicos 
SET codigo_tributacao_nacional = CONCAT(LPAD(REPLACE(item_lista_servico, '.', ''), 4, '0'), '01')
WHERE (codigo_tributacao_nacional IS NULL OR codigo_tributacao_nacional = '')
  AND item_lista_servico IS NOT NULL 
  AND item_lista_servico != '';
