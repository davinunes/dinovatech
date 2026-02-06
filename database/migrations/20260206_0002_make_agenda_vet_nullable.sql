-- Migration: Make id_vet nullable in Agendamentos for General Mode
ALTER TABLE Agendamentos MODIFY COLUMN id_vet INT NULL;
