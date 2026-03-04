-- Adicionar data de nascimento na tabela Veterinarios (colaboradores/vets)

ALTER TABLE `Veterinarios` ADD COLUMN `data_nascimento` DATE DEFAULT NULL;

-- ROLLBACK
-- ALTER TABLE `Veterinarios` DROP COLUMN `data_nascimento`;
