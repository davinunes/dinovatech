-- Adicionar data de nascimento na tabela Clientes

ALTER TABLE `Clientes` ADD COLUMN `data_nascimento` DATE DEFAULT NULL;

-- ROLLBACK
-- ALTER TABLE `Clientes` DROP COLUMN `data_nascimento`;
