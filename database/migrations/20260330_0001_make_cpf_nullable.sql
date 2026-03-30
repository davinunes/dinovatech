-- Alterar a coluna cpf_cnpj para permitir valores nulos (NULL)
-- Isso é necessário para permitir múltiplos cadastros sem CPF quando a configuração global está ativa,
-- já que uma coluna NOT NULL com UNIQUE KEY não permite múltiplos valores vazios (ou falha ao inserir NULL).

ALTER TABLE `Clientes` MODIFY COLUMN `cpf_cnpj` varchar(18) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL;

-- ROLLBACK:
-- ALTER TABLE `Clientes` MODIFY COLUMN `cpf_cnpj` varchar(18) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL;
