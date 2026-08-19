-- Migration: 20260819_0001_expand_agendamentos_status.sql
-- Description: Alterar a coluna status da tabela Agendamentos para VARCHAR(50) para suportar todos os status da esteira e agendamento sem truncamento

SET FOREIGN_KEY_CHECKS = 0;

ALTER TABLE `Agendamentos` MODIFY COLUMN `status` VARCHAR(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Agendado';

SET FOREIGN_KEY_CHECKS = 1;
