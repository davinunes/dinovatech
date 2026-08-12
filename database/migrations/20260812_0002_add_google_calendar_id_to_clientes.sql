-- Migration: Add google_calendar_id column to Clientes
ALTER TABLE Clientes ADD COLUMN google_calendar_id VARCHAR(255) DEFAULT NULL;
