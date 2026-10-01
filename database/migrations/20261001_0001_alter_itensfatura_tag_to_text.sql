-- Migration: 20261001_0001_alter_itensfatura_tag_to_text.sql
-- Description: Altera a coluna tag da tabela ItensFatura de VARCHAR(255) para TEXT para suportar descrições personalizadas longas das recorrências/contratos

ALTER TABLE `ItensFatura` MODIFY COLUMN `tag` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL;
