-- Adiciona colunas de desconto e pagamento parcial na tabela Faturas
ALTER TABLE Faturas 
ADD COLUMN desconto_valor DECIMAL(10,2) DEFAULT 0.00 AFTER valor_total_fatura,
ADD COLUMN desconto_tipo ENUM('percentual', 'fixo') DEFAULT 'percentual' AFTER desconto_valor,
ADD COLUMN permitir_pagamento_parcial TINYINT(1) DEFAULT 0 AFTER desconto_tipo;
