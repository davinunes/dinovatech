-- Migration: 20260907_0001_create_pix_recorrencias_tables.sql
-- Description: Criação da tabela relacional PixRecorrencias para gerenciamento do Pix Automático (Jornada 4) do Banco Inter

SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `PixRecorrencias` (
  `id_pix_recorrencia` int NOT NULL AUTO_INCREMENT,
  `id_recorrencia` int NOT NULL COMMENT 'FK para Recorrencias.id_recorrencia',
  `id_cliente` int NOT NULL COMMENT 'FK para Clientes.id_cliente',
  `id_fatura_inicial` int DEFAULT NULL COMMENT 'Fatura de origem onde foi gerada a proposta Jornada 4',
  `id_rec` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ID da recorrência retornado pelo Banco Inter (ex: RN...)',
  `id_location` bigint DEFAULT NULL COMMENT 'ID da location gerada no POST /locrec',
  `txid_inicial` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'TXID da cobrança CobV vinculada no Passo 2 da Jornada 4',
  `status` enum('CRIADA','PENDENTE','APROVADA','REJEITADA','CANCELADA') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PENDENTE',
  `valor_recorrente` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT 'Valor mensal da recorrência',
  `data_inicial` date NOT NULL COMMENT 'Data de início da vigência do débito automático',
  `data_final` date DEFAULT NULL COMMENT 'Data final da vigência (se houver)',
  `qr_code_adesao` text COLLATE utf8mb4_unicode_ci COMMENT 'String Pix Copia e Cola combinado da Jornada 4',
  `dados_bancarios_json` json DEFAULT NULL COMMENT 'Payload completo de resposta da API do Inter',
  `data_criacao` datetime DEFAULT CURRENT_TIMESTAMP COMMENT 'Data/hora de criação do registro no sistema',
  `data_aceite` datetime DEFAULT NULL COMMENT 'Data/hora de aprovação pelo pagador',
  `data_ultima_consulta` datetime DEFAULT NULL COMMENT 'Data/hora da última sincronização com o Inter',
  `motivo_cancelamento` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Motivo do cancelamento (se cancelada)',
  PRIMARY KEY (`id_pix_recorrencia`),
  UNIQUE KEY `uk_id_rec` (`id_rec`),
  KEY `idx_recorrencia` (`id_recorrencia`),
  KEY `idx_cliente` (`id_cliente`),
  KEY `idx_fatura_inicial` (`id_fatura_inicial`),
  KEY `idx_status` (`status`),
  KEY `idx_txid_inicial` (`txid_inicial`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
