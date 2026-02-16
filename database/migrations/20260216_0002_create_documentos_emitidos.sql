-- Create DocumentosEmitidos table
CREATE TABLE IF NOT EXISTS DocumentosEmitidos (
    id_documento_emitido INT AUTO_INCREMENT PRIMARY KEY,
    id_cliente INT,
    id_pet INT NULL,
    id_atendimento INT NULL,
    id_recorrencia INT NULL,
    titulo VARCHAR(255),
    tipo VARCHAR(50),
    conteudo_html LONGTEXT,
    texto_personalizado TEXT NULL,
    data_emissao DATETIME,
    usuario_emissor INT,
    FOREIGN KEY (id_cliente) REFERENCES Clientes(id_cliente) ON DELETE SET NULL
);
