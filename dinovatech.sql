-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 192.168.10.135
-- Tempo de geração: 07/01/2026 às 23:26
-- Versão do servidor: 9.5.2-cloud
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
(5, 'LAYER7 TECNOLOGIA LTDA', '21706269000168', '', 'financeiro@layer7tecnologia.com.br'),
(6, 'Davi Nunes', '01691128104', '61996757676', 'davi.nunes@gmail.com'),
(8, 'MARIANA ', '70521212120', '', 'mariana@gmail.com'),
(9, 'Condomínio Residencial Top Life II Taguatinga - Long Beach Bloco B', '23196654000138', '', 'blocob.longbeach@gmail.com'),
(10, 'Condomínio Residencial Top Life II Taguatinga - Long Beach Bloco A', '23196523000150', '', '');

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
(5, 1, '2025-07-19', '2025-07-15', 4500.00, 'Liquidada'),
(6, 1, '2025-07-19', '2025-07-15', 84.00, 'Liquidada'),
(7, 1, '2025-07-19', '2025-08-15', 302.82, 'Liquidada'),
(8, 6, '2025-07-19', '2025-07-31', 0.10, 'Liquidada'),
(9, 5, '2025-07-19', '2025-08-06', 4500.00, 'Liquidada'),
(10, 1, '2025-07-19', '2025-08-15', 98.00, 'Liquidada'),
(11, 1, '2025-07-19', '2025-08-15', 4500.00, 'Liquidada'),
(12, 8, '2025-07-19', '2025-07-31', 0.00, 'Liquidada'),
(13, 9, '2025-07-21', '2025-08-15', 200.00, 'Liquidada'),
(14, 1, '2025-07-22', '2025-08-15', 539.00, 'Liquidada'),
(15, 5, '2025-08-02', '2025-09-05', 5116.50, 'Liquidada'),
(16, 9, '2025-08-22', '2025-09-15', 200.00, 'Liquidada'),
(17, 1, '2025-08-24', '2025-09-15', 4500.00, 'Liquidada'),
(18, 1, '2025-08-26', '2025-09-15', 98.00, 'Liquidada'),
(19, 1, '2025-08-29', '2025-09-16', 306.12, 'Liquidada'),
(20, 5, '2025-09-02', '2025-10-06', 4625.00, 'Liquidada'),
(21, 9, '2025-09-02', '2025-10-15', 200.00, 'Liquidada'),
(22, 1, '2025-09-02', '2025-10-15', 4500.00, 'Liquidada'),
(23, 1, '2025-09-02', '2025-10-15', 98.00, 'Liquidada'),
(24, 5, '2025-09-27', '2025-11-06', 4337.50, 'Liquidada'),
(25, 9, '2025-09-27', '2025-11-15', 195.98, 'Liquidada'),
(26, 1, '2025-09-27', '2025-11-15', 4500.00, 'Liquidada'),
(27, 1, '2025-09-27', '2025-11-15', 98.00, 'Liquidada'),
(28, 5, '2025-10-10', '2025-12-08', 5000.00, 'Liquidada'),
(29, 9, '2025-11-06', '2025-12-15', 195.98, 'Liquidada'),
(30, 10, '2025-11-24', '2026-01-15', 200.00, 'Liquidada'),
(31, 1, '2025-11-24', '2025-12-15', 98.00, 'Liquidada'),
(32, 1, '2025-11-24', '2025-12-15', 4500.00, 'Liquidada'),
(33, 1, '2025-12-02', '2025-12-30', 4500.00, 'Em Aberto'),
(34, 5, '2025-12-04', '2026-01-06', 4750.00, 'Liquidada'),
(35, 9, '2025-12-10', '2026-01-15', 200.00, 'Liquidada'),
(36, 10, '2025-12-22', '2025-12-30', 200.00, 'Liquidada'),
(37, 1, '2025-12-26', '2026-01-15', 98.00, 'Em Aberto'),
(38, 1, '2025-12-26', '2026-01-15', 4500.00, 'Em Aberto'),
(39, 5, '2026-01-03', '2026-02-06', 4500.00, 'Em Aberto'),
(40, 9, '2026-01-07', '2026-02-16', 200.00, 'Em Aberto'),
(41, 10, '2026-01-07', '2026-02-16', 200.00, 'Em Aberto');

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
(23, 5, 1, 1, 4500.00, 'Recorrência - Consultoria em Tecnologia da Informação (2025-07)', 1),
(26, 6, 3, 1, 84.00, 'Recorrência - Google Workspace (2025-07)', 2),
(27, 7, 2, 1, 302.82, 'Licença MPLS Switch Ceilandia', NULL),
(28, 8, 4, 1, 0.10, 'Teste', NULL),
(29, 9, 1, 1, 4250.00, 'Mensalidade', NULL),
(32, 10, 3, 1, 98.00, 'Recorrência - Google Workspace (2025-08)', 5),
(33, 11, 1, 1, 4500.00, 'Recorrência - Consultoria em Tecnologia da Informação (2025-08)', 1),
(36, 13, 4, 1, 200.00, 'Mensalidade - Monitoramento de Reservatorio (2025-08)', 6),
(37, 9, 1, 1, 250.00, 'Plantao 06-07-2025', NULL),
(38, 14, 5, 1, 539.00, 'Tablet', NULL),
(40, 15, 1, 1, 250.00, 'Plantao 02-08-2025', NULL),
(41, 15, 1, 1, 4250.00, 'Mensalidade', 4),
(42, 15, 1, 1, 250.00, 'Plantao 23-08-2025', NULL),
(43, 15, 1, 1, 250.00, 'Plantao 31-08-2025', NULL),
(44, 15, 5, 1, 116.50, '15-08-2025 0:00h-1:20h', NULL),
(45, 16, 4, 1, 200.00, 'Recorrência - Monitoramento de Reservatorio (2025-09)', 6),
(46, 17, 1, 1, 4500.00, 'Recorrência - Consultoria em Tecnologia da Informação (2025-09)', 1),
(49, 18, 3, 1, 98.00, 'Recorrência - Google Workspace (2025-09)', 5),
(50, 19, 2, 1, 306.12, 'Licença MPLS Switch Aguas Lindas', NULL),
(51, 20, 1, 1, 4250.00, 'Recorrência - Consultoria em Tecnologia da Informação (2025-10)', 4),
(52, 21, 4, 1, 200.00, 'Recorrência - Monitoramento de Reservatorio (2025-10)', 6),
(53, 22, 1, 1, 4500.00, 'Recorrência - Consultoria em Tecnologia da Informação (2025-10)', 1),
(56, 23, 3, 1, 98.00, 'Recorrência - Google Workspace (2025-10)', 5),
(58, 20, 1, 1, 250.00, 'Plantao 27-09-2025', NULL),
(59, 24, 1, 1, 4250.00, 'Mensalidade - Consultoria em Tecnologia da Informação (2025-11)', 4),
(60, 25, 4, 1, 195.98, 'Mensalidade - Monitoramento de Reservatorio (2025-11)', 6),
(61, 26, 1, 1, 4500.00, 'Mensalidade - Consultoria em Tecnologia da Informação (2025-11)', 1),
(64, 27, 3, 1, 98.00, 'Mensalidade - Google Workspace (2025-11)', 5),
(65, 20, 1, 1, 125.00, 'Suporte de Plantão', NULL),
(66, 24, 5, 1, 87.50, 'TK25100323 09/10 TURBO NET - Diego 01:10h', NULL),
(67, 28, 1, 1, 4250.00, 'Mensalidade - Consultoria em Tecnologia da Informação (2025-12)', 4),
(68, 29, 4, 1, 195.98, 'Mensalidade - Monitoramento de Reservatorio (2025-12)', 6),
(69, 28, 6, 1, 250.00, 'Plantão 08-11-2025', NULL),
(70, 28, 6, 1, 250.00, 'Plantão 15-11-2025 16-24h', NULL),
(71, 28, 6, 1, 250.00, 'Plantão 30-11-2025', NULL),
(72, 30, 4, 1, 200.00, 'Mensalidade - Monitoramento de Reservatorio (2026-01)', 9),
(74, 31, 3, 1, 98.00, 'Mensalidade - Google Workspace (2025-12)', 5),
(75, 32, 1, 1, 4500.00, 'Mensalidade - Consultoria em Tecnologia da Informação (2025-12)', 1),
(77, 33, 1, 1, 4500.00, '13', NULL),
(78, 34, 1, 1, 4250.00, 'Mensalidade - Consultoria em Tecnologia da Informação (2026-01)', 4),
(79, 34, 6, 1, 250.00, 'Plantao 06-12-2025', NULL),
(80, 35, 4, 1, 200.00, 'Mensalidade - Monitoramento de Reservatorio (2026-01)', 6),
(81, 34, 6, 1, 250.00, 'Plantao 13-12-2025', NULL),
(82, 36, 4, 1, 200.00, 'Mensalidade - Monitoramento de Reservatorio (2025-12)', 9),
(84, 37, 3, 1, 98.00, 'Mensalidade - Google Workspace (2026-01)', 5),
(85, 38, 1, 1, 4500.00, 'Mensalidade - Consultoria em Tecnologia da Informação (2026-01)', 1),
(87, 39, 1, 1, 4250.00, 'Mensalidade - Consultoria em Tecnologia da Informação (2026-02)', 4),
(88, 39, 6, 1, 250.00, '04-01-2026', NULL),
(89, 40, 4, 1, 200.00, 'Mensalidade - Monitoramento de Reservatorio (2026-02)', 6),
(90, 41, 4, 1, 200.00, 'Mensalidade - Monitoramento de Reservatorio (2026-02)', 9);

-- --------------------------------------------------------

--
-- Estrutura para tabela `Pagamentos`
--

CREATE TABLE `Pagamentos` (
  `id_pagamento` int NOT NULL,
  `id_fatura` int NOT NULL,
  `valor_pago` decimal(10,2) NOT NULL,
  `data_pagamento` date NOT NULL,
  `status_pagamento` enum('Pendente','Confirmado','Cancelado','Expirado') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'Pendente',
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
(38, 6, 84.00, '2025-07-19', 'Cancelado', 'Pago com pix - E2EID: E00416968202507182256LgdOiDIKzen - TXID: 145afxkwjyu4jrl9x1o2eb5rbaxx6j1coq8', NULL, '00020101021226960014BR.GOV.BCB.PIX2574cdpj-sandbox.partners.uatinter.co/pj-s/v2/dd125930026f4bf58bd16eb88dbc4e86520400005303986540584.005802BR5901*6013BELO HORIZONT61089999999962070503***6304F478', '145afxkwjyu4jrl9x1o2eb5rbaxx6j1coq8', 'E00416968202507182256LgdOiDIKzen', '{\"criacao\": \"2025-07-19T01:55:47.145Z\", \"expiracao\": 3600}'),
(39, 8, 0.10, '2025-07-19', 'Cancelado', 'Pago com pix - E2EID: E18236120202507190239s00a75f62fd - TXID: 4aycd024s6y3igvfbbd2jvnnjoxvp5xx02c', NULL, '00020101021226930014BR.GOV.BCB.PIX2571spi-qrcode.bancointer.com.br/spi/pj/v2/f8ab3f251f6346fda622685cf634729352040000530398654040.105802BR5901*6008BRASILIA61087213524062070503***6304233E', '4aycd024s6y3igvfbbd2jvnnjoxvp5xx02c', 'E18236120202507190239s00a75f62fd', '{\"criacao\": \"2025-07-19T02:38:48.533Z\", \"expiracao\": 3600}'),
(40, 8, 0.10, '2025-07-19', 'Confirmado', 'Pago com pix - E2EID: E0000000020250719025258401196306 - TXID: 5ic8qvenwt4bk3ph3fjlkzrl0ttdog0i6ni', NULL, '00020101021226930014BR.GOV.BCB.PIX2571spi-qrcode.bancointer.com.br/spi/pj/v2/5a01b00dc50e4e7b98be230e6533bc0152040000530398654040.105802BR5901*6008BRASILIA61087213524062070503***6304CE6D', '5ic8qvenwt4bk3ph3fjlkzrl0ttdog0i6ni', 'E0000000020250719025258401196306', '{\"criacao\": \"2025-07-19T02:52:11.13Z\", \"expiracao\": 3600}'),
(68, 6, 84.00, '2025-07-21', 'Expirado', NULL, NULL, '00020101021226930014BR.GOV.BCB.PIX2571spi-qrcode.bancointer.com.br/spi/pj/v2/6ddc8c12631242bf86509744c9b6df5d520400005303986540584.005802BR5901*6008BRASILIA61087213524062070503***63043E93', 'tvu1fnkbqxhz5tb4zdwapugfc7z3ltbv27l', NULL, '{\"criacao\": \"2025-07-21T02:35:15.428Z\", \"expiracao\": 3600}'),
(69, 6, 84.00, '2025-07-21', 'Expirado', NULL, NULL, '00020101021226930014BR.GOV.BCB.PIX2571spi-qrcode.bancointer.com.br/spi/pj/v2/abb4a16042e84267840a0b1573c77949520400005303986540584.005802BR5901*6008BRASILIA61087213524062070503***63042F42', 'xe73zsxntiibskznaoe815gdqc21po68nt3', NULL, '{\"criacao\": \"2025-07-21T03:37:39.378Z\", \"expiracao\": 3600}'),
(70, 6, 84.00, '2025-07-21', 'Expirado', NULL, NULL, '00020101021226930014BR.GOV.BCB.PIX2571spi-qrcode.bancointer.com.br/spi/pj/v2/8fc0d92bfc4f457ea55ceee2407c97df520400005303986540584.005802BR5901*6008BRASILIA61087213524062070503***6304D129', 'rv3kqf6bk3p1pek2ql42t398nkxljzuqhvj', NULL, '{\"criacao\": \"2025-07-21T09:42:19.211Z\", \"expiracao\": 3600}'),
(71, 6, 84.00, '2025-07-24', 'Confirmado', 'E2EID: E02338666202507241532QcgnZl5vKqK - TXID: vcoqbz7jgfvmxudij7ht8cp7j5xz1w8eoe3', NULL, '00020101021226930014BR.GOV.BCB.PIX2571spi-qrcode.bancointer.com.br/spi/pj/v2/4b627b134d234703a2131e6c42bcbd9b520400005303986540584.005802BR5901*6008BRASILIA61087213524062070503***6304D5C5', 'vcoqbz7jgfvmxudij7ht8cp7j5xz1w8eoe3', 'E02338666202507241532QcgnZl5vKqK', '{\"criacao\": \"2025-07-24T15:15:13.622Z\", \"expiracao\": 3600}'),
(72, 5, 4500.00, '2025-07-24', 'Confirmado', 'E2EID: E02338666202507241814SbGbthLGGIq - TXID: n1vrid76kellavczow9xlngnttegywcl80e', NULL, '00020101021226930014BR.GOV.BCB.PIX2571spi-qrcode.bancointer.com.br/spi/pj/v2/81d298d583ac486eaaa23cc364429edf52040000530398654074500.005802BR5901*6008BRASILIA61087213524062070503***6304F34E', 'n1vrid76kellavczow9xlngnttegywcl80e', 'E02338666202507241814SbGbthLGGIq', '{\"criacao\": \"2025-07-24T18:14:32.618Z\", \"expiracao\": 3600}'),
(73, 12, 0.10, '2025-07-27', 'Cancelado', NULL, NULL, '00020101021226930014BR.GOV.BCB.PIX2571spi-qrcode.bancointer.com.br/spi/pj/v2/25c3dd0d38314cc5b42fe5cd9da6bb5652040000530398654040.105802BR5901*6008BRASILIA61087213524062070503***63040D44', 'kstz09ts3kwuiit56tar8j0cat4kiabny6w', NULL, '{\"criacao\": \"2025-07-27T15:00:05.658Z\", \"expiracao\": 3600}'),
(74, 10, 98.00, '2025-08-03', 'Expirado', NULL, NULL, '00020101021226930014BR.GOV.BCB.PIX2571spi-qrcode.bancointer.com.br/spi/pj/v2/e317c5e0104c40a39306faf637eb5308520400005303986540598.005802BR5901*6008BRASILIA61087213524062070503***6304D55F', 'y66wv7q02nbim3ofv1jq5aoescigxqsh5nh', NULL, '{\"criacao\": \"2025-08-03T02:16:34.559Z\", \"expiracao\": 3600}'),
(75, 9, 4500.00, '2025-08-05', 'Confirmado', 'Pago por pix chave CNPJ', 'W3siaWRfaXRlbSI6MjksInZhbG9yIjo0MjUwfSx7ImlkX2l0ZW0iOjM3LCJ2YWxvciI6MjUwfV0=', NULL, NULL, NULL, NULL),
(76, 10, 98.00, '2025-08-08', 'Confirmado', 'E2EID: E02338666202508081720vibzAD16YYB - TXID: xgla084mo1b1hy9hy15tnt37cjub2znn99g', NULL, '00020101021226930014BR.GOV.BCB.PIX2571spi-qrcode.bancointer.com.br/spi/pj/v2/622b815f5c884d9ba9ed13dd42385f23520400005303986540598.005802BR5901*6008BRASILIA61087213524062070503***6304BB63', 'xgla084mo1b1hy9hy15tnt37cjub2znn99g', 'E02338666202508081720vibzAD16YYB', '{\"criacao\": \"2025-08-08T17:20:15.766Z\", \"expiracao\": 3600}'),
(77, 7, 302.82, '2025-08-18', 'Confirmado', 'E2EID: E02338666202508181852utAVSiui0bD - TXID: eipci8njs1sfn8kk99iiv2erjirxmlurarf', NULL, '00020101021226930014BR.GOV.BCB.PIX2571spi-qrcode.bancointer.com.br/spi/pj/v2/abf64a0b161d4a9689a3a3ffc91fd46b5204000053039865406302.825802BR5901*6008BRASILIA61087213524062070503***63049289', 'eipci8njs1sfn8kk99iiv2erjirxmlurarf', 'E02338666202508181852utAVSiui0bD', '{\"criacao\": \"2025-08-18T18:51:55.983Z\", \"expiracao\": 3600}'),
(78, 13, 200.00, '2025-08-22', 'Confirmado', '', 'W3siaWRfaXRlbSI6MzYsInZhbG9yIjoyMDB9XQ==', NULL, NULL, NULL, NULL),
(79, 14, 539.00, '2025-08-26', 'Confirmado', 'E2EID: E02338666202508261143jPOlSQlzduV - TXID: taq73dj6r84mpxmr68s130fgo365cdv9qfs', NULL, '00020101021226930014BR.GOV.BCB.PIX2571spi-qrcode.bancointer.com.br/spi/pj/v2/74679a84a65e4b4dbeb68b099d43f9b55204000053039865406539.005802BR5901*6008BRASILIA61087213524062070503***63040480', 'taq73dj6r84mpxmr68s130fgo365cdv9qfs', 'E02338666202508261143jPOlSQlzduV', '{\"criacao\": \"2025-08-26T11:43:02.184Z\", \"expiracao\": 3600}'),
(80, 11, 4500.00, '2025-08-26', 'Confirmado', 'E2EID: E1073621420250826150425U4NtVC0fd - TXID: 8xehgtxcqqvct07xj7za3j0d51p5giv9l8v', NULL, '00020101021226930014BR.GOV.BCB.PIX2571spi-qrcode.bancointer.com.br/spi/pj/v2/a9d6b398595c45e7b42ab9cdb850102352040000530398654074500.005802BR5901*6008BRASILIA61087213524062070503***6304C715', '8xehgtxcqqvct07xj7za3j0d51p5giv9l8v', 'E1073621420250826150425U4NtVC0fd', '{\"criacao\": \"2025-08-26T15:03:15.749Z\", \"expiracao\": 3600}'),
(81, 15, 5116.50, '2025-09-04', 'Confirmado', '', 'W3siaWRfaXRlbSI6NDAsInZhbG9yIjoyNTB9LHsiaWRfaXRlbSI6NDEsInZhbG9yIjo0MjUwfSx7ImlkX2l0ZW0iOjQyLCJ2YWxvciI6MjUwfSx7ImlkX2l0ZW0iOjQzLCJ2YWxvciI6MjUwfSx7ImlkX2l0ZW0iOjQ0LCJ2YWxvciI6MTE2LjV9XQ==', NULL, NULL, NULL, NULL),
(82, 20, 4250.00, '2025-09-06', 'Expirado', NULL, NULL, '00020101021226930014BR.GOV.BCB.PIX2571spi-qrcode.bancointer.com.br/spi/pj/v2/5db5b760d3d74f62a8c3ffc5a623dd0d52040000530398654074250.005802BR5901*6008BRASILIA61087213524062070503***63042DCB', 'r7d18o99s6vehxdqe0ttoke2nnqkfze6ocx', NULL, '{\"criacao\": \"2025-09-06T02:28:50.216Z\", \"expiracao\": 3600}'),
(83, 16, 200.00, '2025-09-15', 'Confirmado', '', 'W3siaWRfaXRlbSI6NDUsInZhbG9yIjoyMDB9XQ==', NULL, NULL, NULL, NULL),
(84, 18, 98.00, '2025-09-16', 'Confirmado', 'E2EID: E02338666202509161616OBZi98yGOAv - TXID: mdl6lgyx2gmm7cn48m8wghtcxblgbfwgnr8', NULL, '00020101021226930014BR.GOV.BCB.PIX2571spi-qrcode.bancointer.com.br/spi/pj/v2/130527fee1c34a529e9038ff3cd0ddec520400005303986540598.005802BR5901*6008BRASILIA61087213524062070503***63048C23', 'mdl6lgyx2gmm7cn48m8wghtcxblgbfwgnr8', 'E02338666202509161616OBZi98yGOAv', '{\"criacao\": \"2025-09-16T16:15:40.641Z\", \"expiracao\": 3600}'),
(85, 19, 306.12, '2025-09-16', 'Confirmado', 'E2EID: E02338666202509161757q9VLtF98zWA - TXID: r63p3ig7e55ve3eoqv9ehwzewbuzpesbvju', NULL, '00020101021226930014BR.GOV.BCB.PIX2571spi-qrcode.bancointer.com.br/spi/pj/v2/86e871a222f54309a241c7e7e08784e15204000053039865406306.125802BR5901*6008BRASILIA61087213524062070503***6304B691', 'r63p3ig7e55ve3eoqv9ehwzewbuzpesbvju', 'E02338666202509161757q9VLtF98zWA', '{\"criacao\": \"2025-09-16T17:55:36.281Z\", \"expiracao\": 3600}'),
(86, 17, 4500.00, '2025-09-23', 'Confirmado', 'E2EID: E10736214202509231501307UlX1xTQQ - TXID: 0ufyeelicl3mxais9v0j3oqssl52loigd0n', NULL, '00020101021226930014BR.GOV.BCB.PIX2571spi-qrcode.bancointer.com.br/spi/pj/v2/8d21839b867444dbafcab7000a70ad9052040000530398654074500.005802BR5901*6008BRASILIA61087213524062070503***63043692', '0ufyeelicl3mxais9v0j3oqssl52loigd0n', 'E10736214202509231501307UlX1xTQQ', '{\"criacao\": \"2025-09-23T14:59:30.324Z\", \"expiracao\": 3600}'),
(87, 20, 4500.00, '2025-09-27', 'Expirado', NULL, NULL, '00020101021226930014BR.GOV.BCB.PIX2571spi-qrcode.bancointer.com.br/spi/pj/v2/31f1dadc75884c4ca7d0a806b28eae8752040000530398654074500.005802BR5901*6008BRASILIA61087213524062070503***6304C8D7', 'nkbq5nkcq52stu6co5058uq8tdlkfrevl9z', NULL, '{\"criacao\": \"2025-09-27T20:57:22.467Z\", \"expiracao\": 3600}'),
(88, 20, 4625.00, '2025-10-02', 'Pendente', NULL, NULL, '00020101021226930014BR.GOV.BCB.PIX2571spi-qrcode.bancointer.com.br/spi/pj/v2/8f53f7a33b35411495c16c99c6c27d7c52040000530398654074625.005802BR5901*6008BRASILIA61087213524062070503***63046A63', 'hac8vqbbrsdmg6z9xc5l2e9uhlk5g05wmtu', NULL, '{\"criacao\": \"2025-10-02T15:08:49.732Z\", \"expiracao\": 3600}'),
(89, 20, 4625.00, '2025-10-04', 'Confirmado', 'Pago no pix CNPJ', 'W3siaWRfaXRlbSI6NTEsInZhbG9yIjo0MjUwfSx7ImlkX2l0ZW0iOjU4LCJ2YWxvciI6MjUwfSx7ImlkX2l0ZW0iOjY1LCJ2YWxvciI6MTI1fV0=', NULL, NULL, NULL, NULL),
(90, 21, 200.00, '2025-10-03', 'Confirmado', 'Pago na chave pix', 'W3siaWRfaXRlbSI6NTIsInZhbG9yIjoyMDB9XQ==', NULL, NULL, NULL, NULL),
(91, 23, 98.00, '2025-10-16', 'Confirmado', 'E2EID: E1073621420251016172625mhN25VwaB - TXID: i0oqyq2k28n7c9mpizh4qydanu94axiq8k8', NULL, '00020101021226930014BR.GOV.BCB.PIX2571spi-qrcode.bancointer.com.br/spi/pj/v2/ece94c5ccd4446c4ba589042ff028260520400005303986540598.005802BR5901*6008BRASILIA61087213524062070503***6304C96D', 'i0oqyq2k28n7c9mpizh4qydanu94axiq8k8', 'E1073621420251016172625mhN25VwaB', '{\"criacao\": \"2025-10-16T17:25:29.037Z\", \"expiracao\": 3600}'),
(92, 22, 4500.00, '2025-10-22', 'Confirmado', 'E2EID: E1073621420251022124312UeSHM9JkL - TXID: 1z2fovyo7mhwarlppgilg9msynidwycpuxm', NULL, '00020101021226930014BR.GOV.BCB.PIX2571spi-qrcode.bancointer.com.br/spi/pj/v2/cb05804f4a604479b5955f702b26178052040000530398654074500.005802BR5901*6008BRASILIA61087213524062070503***6304933A', '1z2fovyo7mhwarlppgilg9msynidwycpuxm', 'E1073621420251022124312UeSHM9JkL', '{\"criacao\": \"2025-10-22T12:42:14.845Z\", \"expiracao\": 3600}'),
(93, 27, 98.00, '2025-10-25', 'Expirado', NULL, NULL, '00020101021226930014BR.GOV.BCB.PIX2571spi-qrcode.bancointer.com.br/spi/pj/v2/1307a934193e452cb1e29e19d7a6344b520400005303986540598.005802BR5901*6008BRASILIA61087213524062070503***6304DBC9', 'cbdwyhzhanug2333xedebnqmkmesp0ie5gu', NULL, '{\"criacao\": \"2025-10-25T14:55:34.595Z\", \"expiracao\": 3600}'),
(94, 25, 200.00, '2025-11-06', 'Cancelado', NULL, NULL, '00020101021226930014BR.GOV.BCB.PIX2571spi-qrcode.bancointer.com.br/spi/pj/v2/37e40977c4624e519ce2363d1c01d1255204000053039865406200.005802BR5901*6008BRASILIA61087213524062070503***630469CC', '1k8g3q6b5cp7ke7fkp9kakbnkwd5ja6v52a', NULL, '{\"criacao\": \"2025-11-06T01:58:38.299Z\", \"expiracao\": 3600}'),
(95, 24, 4337.50, '2025-11-06', 'Confirmado', 'Pago com chave pix CNPJ', 'W3siaWRfaXRlbSI6NTksInZhbG9yIjo0MjUwfSx7ImlkX2l0ZW0iOjY2LCJ2YWxvciI6ODcuNX1d', NULL, NULL, NULL, NULL),
(96, 25, 195.98, '2025-11-06', 'Confirmado', 'Pago no PIX CNPJ', 'W3siaWRfaXRlbSI6NjAsInZhbG9yIjoxOTUuOTh9XQ==', NULL, NULL, NULL, NULL),
(97, 27, 98.00, '2025-11-17', 'Confirmado', 'E2EID: E02338666202511171957i3fMIFH8HCB - TXID: t9wq9qorlwq8teznum4d87ferudiwkg7hfd', NULL, '00020101021226930014BR.GOV.BCB.PIX2571spi-qrcode.bancointer.com.br/spi/pj/v2/038b32f31c054d86ac3ba51f83896a78520400005303986540598.005802BR5901*6008BRASILIA61087213524062070503***630458DE', 't9wq9qorlwq8teznum4d87ferudiwkg7hfd', 'E02338666202511171957i3fMIFH8HCB', '{\"criacao\": \"2025-11-17T19:57:35.261Z\", \"expiracao\": 3600}'),
(98, 28, 4500.10, '2025-11-18', 'Pendente', NULL, NULL, '00020101021226930014BR.GOV.BCB.PIX2571spi-qrcode.bancointer.com.br/spi/pj/v2/806f5cd7610648f49ca03a5e167bdc4652040000530398654074500.105802BR5901*6008BRASILIA61087213524062070503***6304ED5E', 'bpzvyo4xgi1m43m1p48eki4xbfi2ic3ka87', NULL, '{\"criacao\": \"2025-11-18T02:39:46.601Z\", \"expiracao\": 3600}'),
(99, 26, 4500.00, '2025-12-02', 'Confirmado', 'Pago via pix avulso na chave CNPJ em 02-12-2025', 'W3siaWRfaXRlbSI6NjEsInZhbG9yIjo0NTAwfV0=', NULL, NULL, NULL, NULL),
(100, 28, 5000.00, '2025-12-06', 'Confirmado', 'Pago na CHave Pix CNPJ', 'W3siaWRfaXRlbSI6NjcsInZhbG9yIjo0MjUwfSx7ImlkX2l0ZW0iOjY5LCJ2YWxvciI6MjUwfSx7ImlkX2l0ZW0iOjcwLCJ2YWxvciI6MjUwfSx7ImlkX2l0ZW0iOjcxLCJ2YWxvciI6MjUwfV0=', NULL, NULL, NULL, NULL),
(101, 34, 4750.00, '2025-12-14', 'Pendente', NULL, NULL, '00020101021226930014BR.GOV.BCB.PIX2571spi-qrcode.bancointer.com.br/spi/pj/v2/c8a59a711bf245c99c4207e450fac7c652040000530398654074750.005802BR5901*6008BRASILIA61087213524062070503***6304B545', 'wfsrbb516kekzzvf11t5hlip2ie8iuep4p3', NULL, '{\"criacao\": \"2025-12-14T22:37:32.244Z\", \"expiracao\": 3600}'),
(102, 31, 98.00, '2025-12-15', 'Confirmado', 'E2EID: E023386662025121517587VHmPnLWwix - TXID: ups8r8msuva8okhublznvnkdensulrhrsbl', NULL, '00020101021226930014BR.GOV.BCB.PIX2571spi-qrcode.bancointer.com.br/spi/pj/v2/91fdad3c2dde4278981b80ae8889e8ed520400005303986540598.005802BR5901*6008BRASILIA61087213524062070503***6304D239', 'ups8r8msuva8okhublznvnkdensulrhrsbl', 'E023386662025121517587VHmPnLWwix', '{\"criacao\": \"2025-12-15T17:58:25.779Z\", \"expiracao\": 3600}'),
(103, 32, 4500.00, '2025-12-23', 'Confirmado', 'E2EID: E02338666202512231901GK8kwfO2r5c - TXID: m5wp5iwl88jtp1lhycerw1qdcefqzyec67p', NULL, '00020101021226930014BR.GOV.BCB.PIX2571spi-qrcode.bancointer.com.br/spi/pj/v2/535c5cb170b748ff992815649ff062a852040000530398654074500.005802BR5901*6008BRASILIA61087213524062070503***6304F65C', 'm5wp5iwl88jtp1lhycerw1qdcefqzyec67p', 'E02338666202512231901GK8kwfO2r5c', '{\"criacao\": \"2025-12-23T19:01:04.26Z\", \"expiracao\": 3600}'),
(104, 36, 200.00, '2025-12-26', 'Confirmado', 'Pix cnpj', 'W3siaWRfaXRlbSI6ODIsInZhbG9yIjoyMDB9XQ==', NULL, NULL, NULL, NULL),
(105, 29, 195.98, '2025-12-26', 'Confirmado', 'Pix cnpj', 'W3siaWRfaXRlbSI6NjgsInZhbG9yIjoxOTUuOTh9XQ==', NULL, NULL, NULL, NULL),
(106, 34, 4750.00, '2026-01-07', 'Confirmado', 'Pago no Pix CNPJ em 07-01-2026', 'W3siaWRfaXRlbSI6NzgsInZhbG9yIjo0MjUwfSx7ImlkX2l0ZW0iOjc5LCJ2YWxvciI6MjUwfSx7ImlkX2l0ZW0iOjgxLCJ2YWxvciI6MjUwfV0=', NULL, NULL, NULL, NULL),
(107, 35, 200.00, '2026-01-07', 'Confirmado', 'Pago no PIX CNPJ', 'W3siaWRfaXRlbSI6ODAsInZhbG9yIjoyMDB9XQ==', NULL, NULL, NULL, NULL),
(108, 30, 200.00, '2026-01-07', 'Confirmado', 'Pago no PIX CNPJ', 'W3siaWRfaXRlbSI6NzIsInZhbG9yIjoyMDB9XQ==', NULL, NULL, NULL, NULL);

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
(1, 1, 1, 1, 4500.00, 'mensal', 1, '2025-07-18', NULL, '2026-01'),
(2, 1, 3, 1, 84.00, 'mensal', 1, '2025-07-18', '2025-07-30', '2025-07'),
(3, 6, 2, 1, 0.10, 'mensal', 1, '2025-07-19', NULL, NULL),
(4, 5, 1, 1, 4250.00, 'mensal', 1, '2025-06-02', NULL, '2026-02'),
(5, 1, 3, 1, 98.00, 'mensal', 1, '2025-08-01', NULL, NULL),
(6, 9, 4, 1, 200.00, 'mensal', 1, '2025-07-01', NULL, '2026-02'),
(9, 10, 4, 1, 200.00, 'mensal', 1, '2025-11-24', NULL, '2026-02');

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
(1, 'Consultoria em Tecnologia da Informação', 4500.00),
(2, 'Licenciamento de Software', 100.00),
(3, 'Google Workspace', 98.00),
(4, 'Monitoramento de Reservatorio', 200.00),
(5, 'Avulso', 87.50),
(6, 'Plantão TI ', 250.00),
(7, 'Suporte Plantão TI', 125.00);

-- --------------------------------------------------------

--
-- Estrutura para tabela `Usuarios`
--

CREATE TABLE `Usuarios` (
  `id_usuario` int NOT NULL,
  `nome` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `senha` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nivel_acesso` enum('admin','colaborador') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'colaborador'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `Usuarios`
--

INSERT INTO `Usuarios` (`id_usuario`, `nome`, `email`, `senha`, `nivel_acesso`) VALUES
(1, 'Administrador Principal', 'davi.nunes@gmail.com', '$2y$10$sdIKtqpF00T7iQKhozs8mOE0k2S46p5dZG8MfFQlIwjz/z2a1RUOm', 'admin');

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
-- Índices de tabela `Usuarios`
--
ALTER TABLE `Usuarios`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `Clientes`
--
ALTER TABLE `Clientes`
  MODIFY `id_cliente` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de tabela `Faturas`
--
ALTER TABLE `Faturas`
  MODIFY `id_fatura` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT de tabela `ItensFatura`
--
ALTER TABLE `ItensFatura`
  MODIFY `id_item_fatura` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=91;

--
-- AUTO_INCREMENT de tabela `Pagamentos`
--
ALTER TABLE `Pagamentos`
  MODIFY `id_pagamento` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=109;

--
-- AUTO_INCREMENT de tabela `Recorrencias`
--
ALTER TABLE `Recorrencias`
  MODIFY `id_recorrencia` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de tabela `Servicos`
--
ALTER TABLE `Servicos`
  MODIFY `id_servico` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de tabela `Usuarios`
--
ALTER TABLE `Usuarios`
  MODIFY `id_usuario` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

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
