-- Migration: 20261126_0001_register_rustdesk_service.sql
-- Description: Registra o serviço de infraestrutura RustDesk Server em saas_tenants e torna id_recorrencia opcional para serviços internos
-- Atenção: Esta migração NÃO altera o modo vet (app_mode_vet) de nenhum tenant existente.

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Permite que id_recorrencia seja NULL na tabela saas_tenants (para acomodar serviços de infra sem contrato comercial)
SET @dbname = DATABASE();
SET @tablename = "saas_tenants";
SET @columnname = "id_recorrencia";

ALTER TABLE `saas_tenants` MODIFY COLUMN `id_recorrencia` INT NULL COMMENT 'Vínculo com o contrato/recorrência (NULL para serviços de infraestrutura interna)';

-- 2. Registra a instância de infraestrutura RustDesk (apenas se ainda não existir)
-- O nome do cliente é obtido via JOIN com a tabela Clientes (id_cliente).
-- O campo app_mode_vet é definido como 0 estritamente para o RustDesk, preservando o modo vet (1) de todos os outros tenants.
INSERT INTO `saas_tenants` (
    `id_cliente`,
    `id_recorrencia`,
    `slug_instancia`,
    `subdominio`,
    `dominio_customizado`,
    `namespace_k8s`,
    `db_name`,
    `db_user`,
    `db_password`,
    `db_host`,
    `db_port`,
    `app_mode_vet`,
    `versao_imagem`,
    `status_pod`,
    `limite_memoria`,
    `limite_cpu`
) VALUES (
    1,
    NULL,
    'rustdesk',
    'remote.dinovatech.com.br',
    NULL,
    'app-rustdesk',
    'sqlite',
    'root',
    '',
    '127.0.0.1',
    3306,
    0,
    'rustdesk/rustdesk-server:latest',
    'PENDING',
    '384Mi',
    '500m'
) ON DUPLICATE KEY UPDATE
    `namespace_k8s` = 'app-rustdesk',
    `versao_imagem` = 'rustdesk/rustdesk-server:latest',
    `app_mode_vet` = 0;

SET FOREIGN_KEY_CHECKS = 1;
