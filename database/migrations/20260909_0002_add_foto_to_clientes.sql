-- Migration: 20260909_0002_add_foto_to_clientes.sql
-- Description: Adiciona campo de foto/avatar do cliente (Portal do Cliente)
-- O tutor (cliente) poderá definir uma foto de perfil exibida no portal.

ALTER TABLE `Clientes`
    ADD COLUMN `foto_url` VARCHAR(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL
        COMMENT 'URL pública da foto de perfil do cliente no Oracle Object Storage';
