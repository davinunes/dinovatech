-- Migration for Signature feature in Veterinarios table
-- Created at: 2026-02-20

ALTER TABLE `Veterinarios` ADD COLUMN `url_assinatura` TEXT DEFAULT NULL AFTER `google_calendar_id`;
