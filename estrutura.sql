-- Estrutura do Banco de Dados - Gerado em 2026-01-15 10:01:14


CREATE TABLE `Arquivos` (
  `id_arquivo` int NOT NULL AUTO_INCREMENT,
  `nome_original` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `url_publica` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `tamanho_bytes` int DEFAULT NULL,
  `tipo_mime` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data_upload` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_arquivo`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



CREATE TABLE `Clientes` (
  `id_cliente` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cpf_cnpj` varchar(18) COLLATE utf8mb4_unicode_ci NOT NULL,
  `inscricao_municipal` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `inscricao_estadual` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `endereco` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `numero` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `complemento` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bairro` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cep` varchar(15) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `uf` varchar(2) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `codigo_municipio` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_cliente`),
  UNIQUE KEY `cpf_cnpj` (`cpf_cnpj`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



CREATE TABLE `ConfiguracoesEmissor` (
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
  `endereco` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `numero` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `complemento` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bairro` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cep` varchar(15) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `uf` varchar(2) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_config`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



CREATE TABLE `FaturaArquivos` (
  `id_vinculo` int NOT NULL AUTO_INCREMENT,
  `id_fatura` int NOT NULL,
  `id_arquivo` int NOT NULL,
  PRIMARY KEY (`id_vinculo`),
  KEY `id_fatura` (`id_fatura`),
  KEY `id_arquivo` (`id_arquivo`),
  CONSTRAINT `FaturaArquivos_ibfk_1` FOREIGN KEY (`id_fatura`) REFERENCES `Faturas` (`id_fatura`) ON DELETE CASCADE,
  CONSTRAINT `FaturaArquivos_ibfk_2` FOREIGN KEY (`id_arquivo`) REFERENCES `Arquivos` (`id_arquivo`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



CREATE TABLE `Faturas` (
  `id_fatura` int NOT NULL AUTO_INCREMENT,
  `id_cliente` int NOT NULL,
  `data_emissao` date NOT NULL,
  `data_vencimento` date NOT NULL,
  `valor_total_fatura` decimal(10,2) DEFAULT '0.00',
  `status` enum('Em Aberto','Liquidada','Atrasada','Cancelada') COLLATE utf8mb4_unicode_ci DEFAULT 'Em Aberto',
  `possui_nfse` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id_fatura`),
  KEY `id_cliente` (`id_cliente`),
  CONSTRAINT `Faturas_ibfk_1` FOREIGN KEY (`id_cliente`) REFERENCES `Clientes` (`id_cliente`)
) ENGINE=InnoDB AUTO_INCREMENT=44 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



CREATE TABLE `ItensFatura` (
  `id_item_fatura` int NOT NULL AUTO_INCREMENT,
  `id_fatura` int NOT NULL,
  `id_servico` int NOT NULL,
  `quantidade` int NOT NULL,
  `valor_unitario` decimal(10,2) NOT NULL,
  `tag` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_recorrencia` int DEFAULT NULL,
  PRIMARY KEY (`id_item_fatura`),
  KEY `id_fatura` (`id_fatura`),
  KEY `id_servico` (`id_servico`),
  KEY `fk_itensfatura_recorrencia` (`id_recorrencia`),
  CONSTRAINT `fk_itensfatura_recorrencia` FOREIGN KEY (`id_recorrencia`) REFERENCES `Recorrencias` (`id_recorrencia`),
  CONSTRAINT `ItensFatura_ibfk_1` FOREIGN KEY (`id_fatura`) REFERENCES `Faturas` (`id_fatura`) ON DELETE CASCADE,
  CONSTRAINT `ItensFatura_ibfk_2` FOREIGN KEY (`id_servico`) REFERENCES `Servicos` (`id_servico`)
) ENGINE=InnoDB AUTO_INCREMENT=94 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



CREATE TABLE `NfseEmissoes` (
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
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



CREATE TABLE `Pagamentos` (
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
) ENGINE=InnoDB AUTO_INCREMENT=119 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



CREATE TABLE `Recorrencias` (
  `id_recorrencia` int NOT NULL AUTO_INCREMENT,
  `id_cliente` int NOT NULL,
  `id_servico` int NOT NULL,
  `quantidade` int NOT NULL,
  `valor_sugerido_recorrencia` decimal(10,2) NOT NULL,
  `tipo_periodo` enum('diario','semanal','mensal','anual') COLLATE utf8mb4_unicode_ci NOT NULL,
  `intervalo` int NOT NULL,
  `data_inicio_cobranca` date NOT NULL,
  `data_fim_cobranca` date DEFAULT NULL,
  `ultima_fatura_gerada_mes_ano` varchar(7) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `item_lista_servico` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `aliquota_iss` decimal(5,2) DEFAULT NULL,
  `iss_retido` tinyint(1) DEFAULT NULL,
  `descricao_personalizada` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `descricao_fiscal` text COLLATE utf8mb4_unicode_ci,
  `codigo_cnae` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `codigo_tributacao_municipio` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `codigo_nbs` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_recorrencia`),
  UNIQUE KEY `id_cliente` (`id_cliente`,`id_servico`,`tipo_periodo`,`intervalo`,`data_inicio_cobranca`),
  KEY `id_servico` (`id_servico`),
  CONSTRAINT `Recorrencias_ibfk_1` FOREIGN KEY (`id_cliente`) REFERENCES `Clientes` (`id_cliente`),
  CONSTRAINT `Recorrencias_ibfk_2` FOREIGN KEY (`id_servico`) REFERENCES `Servicos` (`id_servico`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



CREATE TABLE `Servicos` (
  `id_servico` int NOT NULL AUTO_INCREMENT,
  `nome_servico` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descricao_fiscal` text COLLATE utf8mb4_unicode_ci,
  `valor_sugerido` decimal(10,2) NOT NULL,
  `item_lista_servico` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `codigo_cnae` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `codigo_tributacao_municipio` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `codigo_nbs` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `aliquota_iss` decimal(5,2) DEFAULT '0.00',
  `iss_retido` tinyint(1) DEFAULT '0',
  `descricao_nfse_padrao` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id_servico`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



CREATE TABLE `Usuarios` (
  `id_usuario` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `senha` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nivel_acesso` enum('admin','colaborador') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'colaborador',
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

