-- Estrutura do Banco de Dados - Gerado em 2026-01-17 23:55:39


CREATE TABLE `Arquivos` (
  `id_arquivo` int(11) NOT NULL AUTO_INCREMENT,
  `nome_original` varchar(255) NOT NULL,
  `url_publica` text NOT NULL,
  `tamanho_bytes` int(11) DEFAULT NULL,
  `tipo_mime` varchar(100) DEFAULT NULL,
  `data_upload` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id_arquivo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



CREATE TABLE `Atendimentos` (
  `id_atendimento` int(11) NOT NULL AUTO_INCREMENT,
  `id_pet` int(11) NOT NULL,
  `id_vet` int(11) NOT NULL,
  `data_atendimento` datetime DEFAULT current_timestamp(),
  `queixa_principal` text DEFAULT NULL,
  `anamnese` text DEFAULT NULL,
  `exame_fisico` text DEFAULT NULL,
  `diagnostico` text DEFAULT NULL,
  `conduta_tratamento` text DEFAULT NULL,
  PRIMARY KEY (`id_atendimento`),
  KEY `id_pet` (`id_pet`),
  KEY `id_vet` (`id_vet`),
  CONSTRAINT `Atendimentos_ibfk_1` FOREIGN KEY (`id_pet`) REFERENCES `Pets` (`id_pet`),
  CONSTRAINT `Atendimentos_ibfk_2` FOREIGN KEY (`id_vet`) REFERENCES `Veterinarios` (`id_vet`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



CREATE TABLE `CarteiraVacinas` (
  `id_carteira` int(11) NOT NULL AUTO_INCREMENT,
  `id_pet` int(11) NOT NULL,
  `id_vacina` int(11) NOT NULL,
  `id_vet` int(11) DEFAULT NULL,
  `data_aplicacao` date NOT NULL,
  `data_vencimento` date DEFAULT NULL,
  `lote` varchar(50) DEFAULT NULL,
  `observacao` text DEFAULT NULL,
  PRIMARY KEY (`id_carteira`),
  KEY `id_pet` (`id_pet`),
  KEY `id_vacina` (`id_vacina`),
  CONSTRAINT `CarteiraVacinas_ibfk_1` FOREIGN KEY (`id_pet`) REFERENCES `Pets` (`id_pet`),
  CONSTRAINT `CarteiraVacinas_ibfk_2` FOREIGN KEY (`id_vacina`) REFERENCES `Vacinas` (`id_vacina`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



CREATE TABLE `Clientes` (
  `id_cliente` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) NOT NULL,
  `cpf_cnpj` varchar(18) NOT NULL,
  `inscricao_municipal` varchar(20) DEFAULT NULL,
  `inscricao_estadual` varchar(20) DEFAULT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `endereco` varchar(255) DEFAULT NULL,
  `numero` varchar(20) DEFAULT NULL,
  `complemento` varchar(255) DEFAULT NULL,
  `bairro` varchar(100) DEFAULT NULL,
  `cep` varchar(15) DEFAULT NULL,
  `uf` varchar(2) DEFAULT NULL,
  `codigo_municipio` varchar(20) DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id_cliente`),
  UNIQUE KEY `cpf_cnpj` (`cpf_cnpj`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



CREATE TABLE `ConfiguracoesEmissor` (
  `id_config` int(11) NOT NULL AUTO_INCREMENT,
  `razao_social` varchar(255) NOT NULL,
  `nome_fantasia` varchar(255) DEFAULT NULL,
  `cnpj` varchar(18) NOT NULL,
  `inscricao_municipal` varchar(20) NOT NULL,
  `codigo_municipio` varchar(20) NOT NULL DEFAULT '5300108',
  `regime_tributario` enum('simples','lucro_presumido','lucro_real') DEFAULT 'simples',
  `optante_simples` tinyint(1) DEFAULT 1,
  `caminho_certificado` varchar(255) DEFAULT NULL,
  `senha_certificado` varchar(255) DEFAULT NULL,
  `ambiente_padrao` enum('homologacao','producao') DEFAULT 'homologacao',
  `ultimo_rps_homologacao` int(11) DEFAULT 0,
  `ultimo_rps_producao` int(11) DEFAULT 0,
  `serie_rps` varchar(5) DEFAULT '8',
  `endereco` varchar(255) DEFAULT NULL,
  `numero` varchar(20) DEFAULT NULL,
  `complemento` varchar(255) DEFAULT NULL,
  `bairro` varchar(100) DEFAULT NULL,
  `cep` varchar(15) DEFAULT NULL,
  `uf` varchar(2) DEFAULT NULL,
  PRIMARY KEY (`id_config`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



CREATE TABLE `DocumentosEmitidos` (
  `id_documento` int(11) NOT NULL AUTO_INCREMENT,
  `id_atendimento` int(11) DEFAULT NULL,
  `id_pet` int(11) NOT NULL,
  `tipo` varchar(50) DEFAULT NULL,
  `conteudo` longtext DEFAULT NULL,
  `data_emissao` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id_documento`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



CREATE TABLE `FaturaArquivos` (
  `id_vinculo` int(11) NOT NULL AUTO_INCREMENT,
  `id_fatura` int(11) NOT NULL,
  `id_arquivo` int(11) NOT NULL,
  PRIMARY KEY (`id_vinculo`),
  KEY `id_fatura` (`id_fatura`),
  KEY `id_arquivo` (`id_arquivo`),
  CONSTRAINT `FaturaArquivos_ibfk_1` FOREIGN KEY (`id_fatura`) REFERENCES `Faturas` (`id_fatura`) ON DELETE CASCADE,
  CONSTRAINT `FaturaArquivos_ibfk_2` FOREIGN KEY (`id_arquivo`) REFERENCES `Arquivos` (`id_arquivo`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



CREATE TABLE `Faturas` (
  `id_fatura` int(11) NOT NULL AUTO_INCREMENT,
  `id_cliente` int(11) NOT NULL,
  `data_emissao` date NOT NULL,
  `data_vencimento` date NOT NULL,
  `valor_total_fatura` decimal(10,2) DEFAULT 0.00,
  `status` enum('Em Aberto','Liquidada','Atrasada','Cancelada') DEFAULT 'Em Aberto',
  `possui_nfse` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id_fatura`),
  KEY `id_cliente` (`id_cliente`),
  CONSTRAINT `Faturas_ibfk_1` FOREIGN KEY (`id_cliente`) REFERENCES `Clientes` (`id_cliente`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



CREATE TABLE `ItensFatura` (
  `id_item_fatura` int(11) NOT NULL AUTO_INCREMENT,
  `id_fatura` int(11) NOT NULL,
  `id_servico` int(11) NOT NULL,
  `quantidade` int(11) NOT NULL,
  `valor_unitario` decimal(10,2) NOT NULL,
  `tag` varchar(255) DEFAULT NULL,
  `id_recorrencia` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_item_fatura`),
  KEY `id_fatura` (`id_fatura`),
  KEY `id_servico` (`id_servico`),
  KEY `fk_itensfatura_recorrencia` (`id_recorrencia`),
  CONSTRAINT `ItensFatura_ibfk_1` FOREIGN KEY (`id_fatura`) REFERENCES `Faturas` (`id_fatura`) ON DELETE CASCADE,
  CONSTRAINT `ItensFatura_ibfk_2` FOREIGN KEY (`id_servico`) REFERENCES `Servicos` (`id_servico`),
  CONSTRAINT `fk_itensfatura_recorrencia` FOREIGN KEY (`id_recorrencia`) REFERENCES `Recorrencias` (`id_recorrencia`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



CREATE TABLE `NfseEmissoes` (
  `id_emissao` int(11) NOT NULL AUTO_INCREMENT,
  `id_fatura` int(11) NOT NULL,
  `id_usuario_responsavel` int(11) DEFAULT NULL,
  `data_emissao` datetime DEFAULT current_timestamp(),
  `ambiente` enum('homologacao','producao') NOT NULL,
  `valor_servico` decimal(10,2) NOT NULL,
  `aliquota_iss` decimal(5,2) NOT NULL,
  `iss_retido` tinyint(1) NOT NULL,
  `item_lista_servico` varchar(10) DEFAULT NULL,
  `discriminacao` text DEFAULT NULL,
  `numero_rps` int(11) DEFAULT NULL,
  `serie_rps` varchar(5) DEFAULT NULL,
  `numero_nota` varchar(20) DEFAULT NULL,
  `codigo_verificacao` varchar(50) DEFAULT NULL,
  `url_xml` text DEFAULT NULL,
  `url_pdf` text DEFAULT NULL,
  `status` enum('pendente','processando','concluido','erro','cancelado') DEFAULT 'pendente',
  `mensagem_erro` text DEFAULT NULL,
  `xml_envio` longtext DEFAULT NULL,
  `xml_retorno` longtext DEFAULT NULL,
  PRIMARY KEY (`id_emissao`),
  KEY `id_fatura` (`id_fatura`),
  CONSTRAINT `NfseEmissoes_ibfk_1` FOREIGN KEY (`id_fatura`) REFERENCES `Faturas` (`id_fatura`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



CREATE TABLE `Pagamentos` (
  `id_pagamento` int(11) NOT NULL AUTO_INCREMENT,
  `id_fatura` int(11) NOT NULL,
  `valor_pago` decimal(10,2) NOT NULL,
  `data_pagamento` date NOT NULL,
  `status_pagamento` enum('Pendente','Confirmado','Cancelado','Expirado') DEFAULT 'Pendente',
  `observacao` varchar(255) DEFAULT NULL,
  `itens_pagos_json` text DEFAULT NULL,
  `cod_qrcode` text DEFAULT NULL,
  `txid` varchar(255) DEFAULT NULL,
  `e2eid` varchar(255) DEFAULT NULL,
  `calendario` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`calendario`)),
  PRIMARY KEY (`id_pagamento`),
  UNIQUE KEY `txid` (`txid`),
  UNIQUE KEY `e2eid` (`e2eid`),
  KEY `id_fatura` (`id_fatura`),
  CONSTRAINT `Pagamentos_ibfk_1` FOREIGN KEY (`id_fatura`) REFERENCES `Faturas` (`id_fatura`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



CREATE TABLE `Pets` (
  `id_pet` int(11) NOT NULL AUTO_INCREMENT,
  `id_cliente` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `especie` varchar(50) NOT NULL,
  `raca` varchar(100) DEFAULT NULL,
  `sexo` char(1) DEFAULT NULL,
  `data_nascimento` date DEFAULT NULL,
  `peso` decimal(5,2) DEFAULT NULL,
  `chip_id` varchar(50) DEFAULT NULL,
  `obs` text DEFAULT NULL,
  `data_cadastro` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id_pet`),
  KEY `id_cliente` (`id_cliente`),
  CONSTRAINT `Pets_ibfk_1` FOREIGN KEY (`id_cliente`) REFERENCES `Clientes` (`id_cliente`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



CREATE TABLE `Recorrencias` (
  `id_recorrencia` int(11) NOT NULL AUTO_INCREMENT,
  `id_cliente` int(11) NOT NULL,
  `id_servico` int(11) NOT NULL,
  `quantidade` int(11) NOT NULL,
  `valor_sugerido_recorrencia` decimal(10,2) NOT NULL,
  `tipo_periodo` enum('diario','semanal','mensal','anual') NOT NULL,
  `intervalo` int(11) NOT NULL,
  `data_inicio_cobranca` date NOT NULL,
  `data_fim_cobranca` date DEFAULT NULL,
  `ultima_fatura_gerada_mes_ano` varchar(7) DEFAULT NULL,
  `item_lista_servico` varchar(10) DEFAULT NULL,
  `aliquota_iss` decimal(5,2) DEFAULT NULL,
  `iss_retido` tinyint(1) DEFAULT NULL,
  `descricao_personalizada` text DEFAULT NULL,
  `descricao_fiscal` text DEFAULT NULL,
  `codigo_cnae` varchar(20) DEFAULT NULL,
  `codigo_tributacao_municipio` varchar(20) DEFAULT NULL,
  `codigo_nbs` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id_recorrencia`),
  UNIQUE KEY `id_cliente` (`id_cliente`,`id_servico`,`tipo_periodo`,`intervalo`,`data_inicio_cobranca`),
  KEY `id_servico` (`id_servico`),
  CONSTRAINT `Recorrencias_ibfk_1` FOREIGN KEY (`id_cliente`) REFERENCES `Clientes` (`id_cliente`),
  CONSTRAINT `Recorrencias_ibfk_2` FOREIGN KEY (`id_servico`) REFERENCES `Servicos` (`id_servico`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



CREATE TABLE `Servicos` (
  `id_servico` int(11) NOT NULL AUTO_INCREMENT,
  `nome_servico` varchar(255) NOT NULL,
  `descricao_fiscal` text DEFAULT NULL,
  `valor_sugerido` decimal(10,2) NOT NULL,
  `item_lista_servico` varchar(10) DEFAULT NULL,
  `codigo_cnae` varchar(20) DEFAULT NULL,
  `codigo_tributacao_municipio` varchar(20) DEFAULT NULL,
  `codigo_nbs` varchar(20) DEFAULT NULL,
  `aliquota_iss` decimal(5,2) DEFAULT 0.00,
  `iss_retido` tinyint(1) DEFAULT 0,
  `descricao_nfse_padrao` text DEFAULT NULL,
  PRIMARY KEY (`id_servico`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



CREATE TABLE `Usuarios` (
  `id_usuario` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `nivel_acesso` enum('admin','colaborador') NOT NULL DEFAULT 'colaborador',
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



CREATE TABLE `Vacinas` (
  `id_vacina` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `descricao` text DEFAULT NULL,
  `recorrencia_dias` int(11) DEFAULT 365,
  PRIMARY KEY (`id_vacina`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



CREATE TABLE `Veterinarios` (
  `id_vet` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `crmv` varchar(20) NOT NULL,
  `uf_crmv` char(2) NOT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id_vet`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

