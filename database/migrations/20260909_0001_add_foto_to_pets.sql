-- Migration: 20260909_0001_add_foto_to_pets.sql
-- Description: Adiciona suporte a foto/avatar do pet (Oracle Object Storage)
-- na tabela Pets. O tutor poderá enviar uma foto do pet pelo Portal do Tutor,
-- que será salva no Oracle Cloud e exibida como avatar.

ALTER TABLE `Pets`
    ADD COLUMN `foto_url` VARCHAR(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL
        COMMENT 'URL pública da foto/avatar do pet no Oracle Object Storage';
