-- Migration: Add id_agendamento column to Atendimentos
ALTER TABLE Atendimentos ADD COLUMN id_agendamento INT DEFAULT NULL;
ALTER TABLE Atendimentos ADD CONSTRAINT fk_atendimento_agenda FOREIGN KEY (id_agendamento) REFERENCES Agendamentos(id_agendamento) ON DELETE SET NULL;
