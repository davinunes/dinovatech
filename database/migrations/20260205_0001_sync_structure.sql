-- Migration: 20260205_0001_sync_structure.sql
-- Description: Baseline synchronization (Vet Module + Core) based on Server 10.0.1.11

SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `Arquivos` (
  `id_arquivo` int NOT NULL AUTO_INCREMENT,
  `nome_original` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `descricao` text COLLATE utf8mb4_unicode_ci,
  `url_publica` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tamanho_bytes` int DEFAULT NULL,
  `tipo_mime` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data_upload` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_arquivo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `Atendimentos` (
  `id_atendimento` int NOT NULL AUTO_INCREMENT,
  `id_pet` int NOT NULL,
  `id_vet` int NOT NULL,
  `data_atendimento` datetime DEFAULT CURRENT_TIMESTAMP,
  `queixa_principal` text COLLATE utf8mb4_unicode_ci,
  `anamnese` text COLLATE utf8mb4_unicode_ci,
  `exame_fisico` text COLLATE utf8mb4_unicode_ci,
  `diagnostico` text COLLATE utf8mb4_unicode_ci,
  `conduta_tratamento` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id_atendimento`),
  KEY `id_pet` (`id_pet`),
  KEY `id_vet` (`id_vet`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `AtendimentoArquivos` (
  `id_vinculo` int NOT NULL AUTO_INCREMENT,
  `id_atendimento` int NOT NULL,
  `id_arquivo` int NOT NULL,
  PRIMARY KEY (`id_vinculo`),
  KEY `id_atendimento` (`id_atendimento`),
  KEY `id_arquivo` (`id_arquivo`),
  CONSTRAINT `AtendimentoArquivos_ibfk_1` FOREIGN KEY (`id_atendimento`) REFERENCES `Atendimentos` (`id_atendimento`) ON DELETE CASCADE,
  CONSTRAINT `AtendimentoArquivos_ibfk_2` FOREIGN KEY (`id_arquivo`) REFERENCES `Arquivos` (`id_arquivo`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `Vacinas` (
  `id_vacina` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descricao` text COLLATE utf8mb4_unicode_ci,
  `recorrencia_dias` int DEFAULT '365',
  PRIMARY KEY (`id_vacina`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `CarteiraVacinas` (
  `id_carteira` int NOT NULL AUTO_INCREMENT,
  `id_pet` int NOT NULL,
  `id_vacina` int NOT NULL,
  `id_vet` int DEFAULT NULL,
  `data_aplicacao` date NOT NULL,
  `data_vencimento` date DEFAULT NULL,
  `lote` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `observacao` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id_carteira`),
  KEY `id_pet` (`id_pet`),
  KEY `id_vacina` (`id_vacina`),
  CONSTRAINT `CarteiraVacinas_ibfk_1` FOREIGN KEY (`id_pet`) REFERENCES `Pets` (`id_pet`),
  CONSTRAINT `CarteiraVacinas_ibfk_2` FOREIGN KEY (`id_vacina`) REFERENCES `Vacinas` (`id_vacina`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `Clientes` (
  `id_cliente` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `cpf_cnpj` varchar(18) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `inscricao_municipal` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `inscricao_estadual` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `endereco` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `numero` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `complemento` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bairro` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cep` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `uf` varchar(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `codigo_municipio` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id_cliente`),
  UNIQUE KEY `cpf_cnpj` (`cpf_cnpj`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ConfiguracoesEmissor` (
  `id_config` int NOT NULL AUTO_INCREMENT,
  `razao_social` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `nome_fantasia` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cnpj` varchar(18) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `inscricao_municipal` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `codigo_municipio` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '5300108',
  `regime_tributario` enum('simples','lucro_presumido','lucro_real') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'simples',
  `optante_simples` tinyint(1) DEFAULT '1',
  `caminho_certificado` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `senha_certificado` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ambiente_padrao` enum('homologacao','producao') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'homologacao',
  `ultimo_rps_homologacao` int DEFAULT '0',
  `ultimo_rps_producao` int DEFAULT '0',
  `serie_rps` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '8',
  `endereco` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `numero` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `complemento` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bairro` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cep` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `uf` varchar(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `inscricao_estadual` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `caminho_certificado_pfx` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `api_inter_client_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `api_inter_client_secret` text COLLATE utf8mb4_unicode_ci,
  `api_inter_chave_pix` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `api_inter_conta_corrente` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `api_inter_cert_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `api_inter_key_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `api_inter_ca_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `api_oracle_user` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `api_oracle_password` text COLLATE utf8mb4_unicode_ci,
  `api_oracle_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `logo_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_config`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `DocumentosEmitidos` (
  `id_documento` int NOT NULL AUTO_INCREMENT,
  `id_atendimento` int DEFAULT NULL,
  `id_pet` int NOT NULL,
  `tipo` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `conteudo` longtext COLLATE utf8mb4_unicode_ci,
  `data_emissao` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_documento`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `Faturas` (
  `id_fatura` int NOT NULL AUTO_INCREMENT,
  `id_cliente` int NOT NULL,
  `data_emissao` date NOT NULL,
  `data_vencimento` date NOT NULL,
  `valor_total_fatura` decimal(10,2) DEFAULT '0.00',
  `status` enum('Em Aberto','Liquidada','Atrasada','Cancelada') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'Em Aberto',
  `possui_nfse` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id_fatura`),
  KEY `id_cliente` (`id_cliente`),
  CONSTRAINT `Faturas_ibfk_1` FOREIGN KEY (`id_cliente`) REFERENCES `Clientes` (`id_cliente`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `FaturaArquivos` (
  `id_vinculo` int NOT NULL AUTO_INCREMENT,
  `id_fatura` int NOT NULL,
  `id_arquivo` int NOT NULL,
  PRIMARY KEY (`id_vinculo`),
  KEY `id_fatura` (`id_fatura`),
  KEY `id_arquivo` (`id_arquivo`),
  CONSTRAINT `FaturaArquivos_ibfk_1` FOREIGN KEY (`id_fatura`) REFERENCES `Faturas` (`id_fatura`) ON DELETE CASCADE,
  CONSTRAINT `FaturaArquivos_ibfk_2` FOREIGN KEY (`id_arquivo`) REFERENCES `Arquivos` (`id_arquivo`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `Servicos` (
  `id_servico` int NOT NULL AUTO_INCREMENT,
  `nome_servico` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `descricao_fiscal` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `valor_sugerido` decimal(10,2) NOT NULL,
  `item_lista_servico` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `codigo_cnae` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `codigo_tributacao_municipio` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `codigo_nbs` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `aliquota_iss` decimal(5,2) DEFAULT '0.00',
  `iss_retido` tinyint(1) DEFAULT '0',
  `descricao_nfse_padrao` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id_servico`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `Recorrencias` (
  `id_recorrencia` int NOT NULL AUTO_INCREMENT,
  `id_cliente` int NOT NULL,
  `id_servico` int NOT NULL,
  `quantidade` int NOT NULL,
  `valor_sugerido_recorrencia` decimal(10,2) NOT NULL,
  `tipo_periodo` enum('diario','semanal','mensal','anual') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `intervalo` int NOT NULL,
  `data_inicio_cobranca` date NOT NULL,
  `data_fim_cobranca` date DEFAULT NULL,
  `ultima_fatura_gerada_mes_ano` varchar(7) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `item_lista_servico` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `aliquota_iss` decimal(5,2) DEFAULT NULL,
  `iss_retido` tinyint(1) DEFAULT NULL,
  `descricao_personalizada` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `descricao_fiscal` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `codigo_cnae` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `codigo_tributacao_municipio` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `codigo_nbs` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_recorrencia`),
  UNIQUE KEY `id_cliente` (`id_cliente`,`id_servico`,`tipo_periodo`,`intervalo`,`data_inicio_cobranca`),
  KEY `id_servico` (`id_servico`),
  CONSTRAINT `Recorrencias_ibfk_1` FOREIGN KEY (`id_cliente`) REFERENCES `Clientes` (`id_cliente`),
  CONSTRAINT `Recorrencias_ibfk_2` FOREIGN KEY (`id_servico`) REFERENCES `Servicos` (`id_servico`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ItensFatura` (
  `id_item_fatura` int NOT NULL AUTO_INCREMENT,
  `id_fatura` int NOT NULL,
  `id_servico` int NOT NULL,
  `quantidade` int NOT NULL,
  `valor_unitario` decimal(10,2) NOT NULL,
  `tag` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_recorrencia` int DEFAULT NULL,
  `item_recorrencia_id` int DEFAULT NULL,
  `descricao_fiscal` text COLLATE utf8mb4_unicode_ci,
  `codigo_cnae` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `codigo_nbs` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `item_lista_servico` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `codigo_tributacao_municipio` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `aliquota_iss` decimal(5,2) DEFAULT '0.00',
  `iss_retido` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id_item_fatura`),
  KEY `id_fatura` (`id_fatura`),
  KEY `id_servico` (`id_servico`),
  KEY `fk_itensfatura_recorrencia` (`id_recorrencia`),
  CONSTRAINT `fk_itensfatura_recorrencia` FOREIGN KEY (`id_recorrencia`) REFERENCES `Recorrencias` (`id_recorrencia`),
  CONSTRAINT `ItensFatura_ibfk_1` FOREIGN KEY (`id_fatura`) REFERENCES `Faturas` (`id_fatura`) ON DELETE CASCADE,
  CONSTRAINT `ItensFatura_ibfk_2` FOREIGN KEY (`id_servico`) REFERENCES `Servicos` (`id_servico`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `Receitas` (
  `id_receita` int NOT NULL AUTO_INCREMENT,
  `id_atendimento` int NOT NULL,
  `data_receita` datetime DEFAULT CURRENT_TIMESTAMP,
  `observacoes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id_receita`),
  KEY `id_atendimento` (`id_atendimento`),
  CONSTRAINT `Receitas_ibfk_1` FOREIGN KEY (`id_atendimento`) REFERENCES `Atendimentos` (`id_atendimento`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ItensReceita` (
  `id_item_receita` int NOT NULL AUTO_INCREMENT,
  `id_receita` int NOT NULL,
  `nome_medicamento` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantidade` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `uso` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Oral, Tópico, etc',
  `categoria` enum('Veterinaria','Humana','Manipulado') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'Veterinaria',
  `posologia` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id_item_receita`),
  KEY `id_receita` (`id_receita`),
  CONSTRAINT `ItensReceita_ibfk_1` FOREIGN KEY (`id_receita`) REFERENCES `Receitas` (`id_receita`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `NfseEmissoes` (
  `id_emissao` int NOT NULL AUTO_INCREMENT,
  `id_fatura` int NOT NULL,
  `id_usuario_responsavel` int DEFAULT NULL,
  `data_emissao` datetime DEFAULT CURRENT_TIMESTAMP,
  `ambiente` enum('homologacao','producao') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `valor_servico` decimal(10,2) NOT NULL,
  `aliquota_iss` decimal(5,2) NOT NULL,
  `iss_retido` tinyint(1) NOT NULL,
  `item_lista_servico` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `discriminacao` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `numero_rps` int DEFAULT NULL,
  `serie_rps` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `numero_nota` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `codigo_verificacao` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `url_xml` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `url_pdf` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `status` enum('pendente','processando','concluido','erro','cancelado') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'pendente',
  `mensagem_erro` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `xml_envio` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `xml_retorno` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id_emissao`),
  KEY `id_fatura` (`id_fatura`),
  CONSTRAINT `NfseEmissoes_ibfk_1` FOREIGN KEY (`id_fatura`) REFERENCES `Faturas` (`id_fatura`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `Pagamentos` (
  `id_pagamento` int NOT NULL AUTO_INCREMENT,
  `id_fatura` int NOT NULL,
  `valor_pago` decimal(10,2) NOT NULL,
  `data_pagamento` date NOT NULL,
  `status_pagamento` enum('Pendente','Confirmado','Cancelado','Expirado') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'Pendente',
  `observacao` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `itens_pagos_json` text COLLATE utf8mb4_unicode_ci,
  `cod_qrcode` text COLLATE utf8mb4_unicode_ci,
  `txid` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `e2eid` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `calendario` json DEFAULT NULL,
  PRIMARY KEY (`id_pagamento`),
  UNIQUE KEY `txid` (`txid`),
  UNIQUE KEY `e2eid` (`e2eid`),
  KEY `id_fatura` (`id_fatura`),
  CONSTRAINT `Pagamentos_ibfk_1` FOREIGN KEY (`id_fatura`) REFERENCES `Faturas` (`id_fatura`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `Pets` (
  `id_pet` int NOT NULL AUTO_INCREMENT,
  `id_cliente` int NOT NULL,
  `nome` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `especie` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `raca` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sexo` char(1) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data_nascimento` date DEFAULT NULL,
  `peso` decimal(5,2) DEFAULT NULL,
  `chip_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `obs` text COLLATE utf8mb4_unicode_ci,
  `data_cadastro` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_pet`),
  KEY `id_cliente` (`id_cliente`),
  CONSTRAINT `Pets_ibfk_1` FOREIGN KEY (`id_cliente`) REFERENCES `Clientes` (`id_cliente`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `Usuarios` (
  `id_usuario` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `senha` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `nivel_acesso` enum('admin','colaborador') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'colaborador',
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `VacinaCiclos` (
  `id_ciclo` int NOT NULL AUTO_INCREMENT,
  `id_vacina` int NOT NULL,
  `nome` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `intervalo` int NOT NULL,
  PRIMARY KEY (`id_ciclo`),
  KEY `id_vacina` (`id_vacina`),
  CONSTRAINT `VacinaCiclos_ibfk_1` FOREIGN KEY (`id_vacina`) REFERENCES `Vacinas` (`id_vacina`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `Veterinarios` (
  `id_vet` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `crmv` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `uf_crmv` char(2) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telefone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_vet`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- Add Constraint Checks/Fixes (Optional: if we need to enforce column types like varchar(20) vs 50)
