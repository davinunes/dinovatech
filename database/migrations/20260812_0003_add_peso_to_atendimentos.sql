-- Migration: 20260812_0003_add_peso_to_atendimentos.sql
-- Description: Adiciona coluna peso na tabela Atendimentos para manter o histórico de evolução do peso dos pets
ALTER TABLE Atendimentos ADD COLUMN peso DECIMAL(5,2) DEFAULT NULL;
