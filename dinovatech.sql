-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 192.168.10.135
-- Tempo de geração: 18/07/2025 às 17:39
-- Versão do servidor: 9.3.1-u1-cloud
-- Versão do PHP: 8.2.8

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `dinovatech`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `Clientes`
--

CREATE TABLE `Clientes` (
  `id_cliente` int NOT NULL,
  `nome` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cpf_cnpj` varchar(18) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telefone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `Clientes`
--

INSERT INTO `Clientes` (`id_cliente`, `nome`, `cpf_cnpj`, `telefone`, `email`) VALUES
(1, 'ACESSO COM SERVICOS DE TELECOMUNICACOES E SUPRIMENTOS LTDA', '30063355000190', '6134791091', 'financeiroacesso.comdf@gmail.com'),
(5, 'LAYER7 TECNOLOGIA LTDA', '21706269000168', '', '');

-- --------------------------------------------------------

--
-- Estrutura para tabela `Faturas`
--

CREATE TABLE `Faturas` (
  `id_fatura` int NOT NULL,
  `id_cliente` int NOT NULL,
  `data_emissao` date NOT NULL,
  `data_vencimento` date NOT NULL,
  `valor_total_fatura` decimal(10,2) DEFAULT '0.00',
  `status` enum('Em Aberto','Liquidada','Atrasada','Cancelada') COLLATE utf8mb4_unicode_ci DEFAULT 'Em Aberto'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `Faturas`
--

INSERT INTO `Faturas` (`id_fatura`, `id_cliente`, `data_emissao`, `data_vencimento`, `valor_total_fatura`, `status`) VALUES
(1, 1, '2025-07-18', '2025-07-31', 4584.00, 'Liquidada'),
(2, 1, '2025-07-18', '2025-08-30', 4869.66, 'Em Aberto');

-- --------------------------------------------------------

--
-- Estrutura para tabela `ItensFatura`
--

CREATE TABLE `ItensFatura` (
  `id_item_fatura` int NOT NULL,
  `id_fatura` int NOT NULL,
  `id_servico` int NOT NULL,
  `quantidade` int NOT NULL,
  `valor_unitario` decimal(10,2) NOT NULL,
  `tag` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_recorrencia` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `ItensFatura`
--

INSERT INTO `ItensFatura` (`id_item_fatura`, `id_fatura`, `id_servico`, `quantidade`, `valor_unitario`, `tag`, `id_recorrencia`) VALUES
(5, 2, 2, 1, 285.66, 'Licença MPLS Switch Ceilandia', NULL),
(10, 1, 1, 1, 4500.00, 'Recorrência - Consultoria em Tecnologia da Informação (2025-07)', NULL),
(11, 1, 3, 1, 84.00, 'Recorrência - Google Mail (2025-07)', NULL),
(15, 2, 3, 1, 84.00, 'Recorrência - Google Workspace (2025-08)', 2),
(16, 2, 1, 1, 4500.00, 'Recorrência - Consultoria em Tecnologia da Informação (2025-08)', 1);

-- --------------------------------------------------------

--
-- Estrutura para tabela `Pagamentos`
--

CREATE TABLE `Pagamentos` (
  `id_pagamento` int NOT NULL,
  `id_fatura` int NOT NULL,
  `valor_pago` decimal(10,2) NOT NULL,
  `data_pagamento` date NOT NULL,
  `status_pagamento` enum('Pendente','Confirmado','Cancelado') COLLATE utf8mb4_unicode_ci DEFAULT 'Pendente',
  `observacao` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `itens_pagos_json` text COLLATE utf8mb4_unicode_ci,
  `cod_qrcode` text COLLATE utf8mb4_unicode_ci,
  `txid` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `e2eid` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `calendario` json DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `Pagamentos`
--

INSERT INTO `Pagamentos` (`id_pagamento`, `id_fatura`, `valor_pago`, `data_pagamento`, `status_pagamento`, `observacao`, `itens_pagos_json`, `cod_qrcode`, `txid`, `e2eid`, `calendario`) VALUES
(1, 1, 84.00, '2025-07-18', 'Cancelado', 'Recebido Google', 'W3siaWRfaXRlbSI6MTEsInZhbG9yIjo4NH1d', NULL, NULL, NULL, NULL),
(2, 1, 4500.00, '2025-07-18', 'Cancelado', 'Quitação', 'W3siaWRfaXRlbSI6MTAsInZhbG9yIjo0NTAwfV0=', NULL, NULL, NULL, NULL),
(3, 1, 4584.00, '2025-07-18', 'Confirmado', 'Quitação', 'W3siaWRfaXRlbSI6MTAsInZhbG9yIjo0NTAwfSx7ImlkX2l0ZW0iOjExLCJ2YWxvciI6ODR9XQ==', NULL, NULL, NULL, NULL),
(4, 2, 84.00, '2025-07-18', 'Cancelado', NULL, 'W3siaWRfaXRlbSI6OSwidmFsb3IiOjg0fV0=', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `Recorrencias`
--

CREATE TABLE `Recorrencias` (
  `id_recorrencia` int NOT NULL,
  `id_cliente` int NOT NULL,
  `id_servico` int NOT NULL,
  `quantidade` int NOT NULL,
  `valor_sugerido_recorrencia` decimal(10,2) NOT NULL,
  `tipo_periodo` enum('diario','semanal','mensal','anual') COLLATE utf8mb4_unicode_ci NOT NULL,
  `intervalo` int NOT NULL,
  `data_inicio_cobranca` date NOT NULL,
  `data_fim_cobranca` date DEFAULT NULL,
  `ultima_fatura_gerada_mes_ano` varchar(7) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `Recorrencias`
--

INSERT INTO `Recorrencias` (`id_recorrencia`, `id_cliente`, `id_servico`, `quantidade`, `valor_sugerido_recorrencia`, `tipo_periodo`, `intervalo`, `data_inicio_cobranca`, `data_fim_cobranca`, `ultima_fatura_gerada_mes_ano`) VALUES
(1, 1, 1, 1, 4500.00, 'mensal', 1, '2025-07-18', NULL, '2025-08'),
(2, 1, 3, 1, 84.00, 'mensal', 1, '2025-07-18', NULL, '2025-08');

-- --------------------------------------------------------

--
-- Estrutura para tabela `Servicos`
--

CREATE TABLE `Servicos` (
  `id_servico` int NOT NULL,
  `nome_servico` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `valor_sugerido` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `Servicos`
--

INSERT INTO `Servicos` (`id_servico`, `nome_servico`, `valor_sugerido`) VALUES
(1, 'Consultoria em Tecnologia da Informação', 100.00),
(2, 'Licenciamento de equipamento de rede', 100.00),
(3, 'Google Workspace', 84.00);

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `Clientes`
--
ALTER TABLE `Clientes`
  ADD PRIMARY KEY (`id_cliente`),
  ADD UNIQUE KEY `cpf_cnpj` (`cpf_cnpj`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Índices de tabela `Faturas`
--
ALTER TABLE `Faturas`
  ADD PRIMARY KEY (`id_fatura`),
  ADD KEY `id_cliente` (`id_cliente`);

--
-- Índices de tabela `ItensFatura`
--
ALTER TABLE `ItensFatura`
  ADD PRIMARY KEY (`id_item_fatura`),
  ADD KEY `id_fatura` (`id_fatura`),
  ADD KEY `id_servico` (`id_servico`),
  ADD KEY `fk_itensfatura_recorrencia` (`id_recorrencia`);

--
-- Índices de tabela `Pagamentos`
--
ALTER TABLE `Pagamentos`
  ADD PRIMARY KEY (`id_pagamento`),
  ADD UNIQUE KEY `txid` (`txid`),
  ADD UNIQUE KEY `e2eid` (`e2eid`),
  ADD KEY `id_fatura` (`id_fatura`);

--
-- Índices de tabela `Recorrencias`
--
ALTER TABLE `Recorrencias`
  ADD PRIMARY KEY (`id_recorrencia`),
  ADD UNIQUE KEY `id_cliente` (`id_cliente`,`id_servico`,`tipo_periodo`,`intervalo`,`data_inicio_cobranca`),
  ADD KEY `id_servico` (`id_servico`);

--
-- Índices de tabela `Servicos`
--
ALTER TABLE `Servicos`
  ADD PRIMARY KEY (`id_servico`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `Clientes`
--
ALTER TABLE `Clientes`
  MODIFY `id_cliente` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `Faturas`
--
ALTER TABLE `Faturas`
  MODIFY `id_fatura` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `ItensFatura`
--
ALTER TABLE `ItensFatura`
  MODIFY `id_item_fatura` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de tabela `Pagamentos`
--
ALTER TABLE `Pagamentos`
  MODIFY `id_pagamento` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `Recorrencias`
--
ALTER TABLE `Recorrencias`
  MODIFY `id_recorrencia` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `Servicos`
--
ALTER TABLE `Servicos`
  MODIFY `id_servico` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `Faturas`
--
ALTER TABLE `Faturas`
  ADD CONSTRAINT `Faturas_ibfk_1` FOREIGN KEY (`id_cliente`) REFERENCES `Clientes` (`id_cliente`);

--
-- Restrições para tabelas `ItensFatura`
--
ALTER TABLE `ItensFatura`
  ADD CONSTRAINT `fk_itensfatura_recorrencia` FOREIGN KEY (`id_recorrencia`) REFERENCES `Recorrencias` (`id_recorrencia`),
  ADD CONSTRAINT `ItensFatura_ibfk_1` FOREIGN KEY (`id_fatura`) REFERENCES `Faturas` (`id_fatura`) ON DELETE CASCADE,
  ADD CONSTRAINT `ItensFatura_ibfk_2` FOREIGN KEY (`id_servico`) REFERENCES `Servicos` (`id_servico`);

--
-- Restrições para tabelas `Pagamentos`
--
ALTER TABLE `Pagamentos`
  ADD CONSTRAINT `Pagamentos_ibfk_1` FOREIGN KEY (`id_fatura`) REFERENCES `Faturas` (`id_fatura`);

--
-- Restrições para tabelas `Recorrencias`
--
ALTER TABLE `Recorrencias`
  ADD CONSTRAINT `Recorrencias_ibfk_1` FOREIGN KEY (`id_cliente`) REFERENCES `Clientes` (`id_cliente`),
  ADD CONSTRAINT `Recorrencias_ibfk_2` FOREIGN KEY (`id_servico`) REFERENCES `Servicos` (`id_servico`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
