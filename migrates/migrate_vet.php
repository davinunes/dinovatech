<?php
include "database.php";
$link = DBConnect();

if (!$link) {
    die("Erro de conexão: " . mysqli_connect_error());
}

echo "Iniciando migração do Módulo Vet...\n";

// 1. Tabela Receitas
$sql = "CREATE TABLE IF NOT EXISTS `Receitas` (
  `id_receita` int NOT NULL AUTO_INCREMENT,
  `id_atendimento` int NOT NULL,
  `data_receita` datetime DEFAULT CURRENT_TIMESTAMP,
  `observacoes` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id_receita`),
  KEY `id_atendimento` (`id_atendimento`),
  CONSTRAINT `Receitas_ibfk_1` FOREIGN KEY (`id_atendimento`) REFERENCES `Atendimentos` (`id_atendimento`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if (DBExecute($link, $sql)) {
    echo "Tabela 'Receitas' verificada/criada com sucesso.\n";
} else {
    echo "Erro ao criar 'Receitas': " . mysqli_error($link) . "\n";
}

// 2. Tabela ItensReceita
$sql = "CREATE TABLE IF NOT EXISTS `ItensReceita` (
  `id_item_receita` int NOT NULL AUTO_INCREMENT,
  `id_receita` int NOT NULL,
  `nome_medicamento` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantidade` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `uso` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Oral, Tópico, etc',
  `categoria` enum('Veterinaria','Humana','Manipulado') COLLATE utf8mb4_unicode_ci DEFAULT 'Veterinaria',
  `posologia` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id_item_receita`),
  KEY `id_receita` (`id_receita`),
  CONSTRAINT `ItensReceita_ibfk_1` FOREIGN KEY (`id_receita`) REFERENCES `Receitas` (`id_receita`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if (DBExecute($link, $sql)) {
    echo "Tabela 'ItensReceita' verificada/criada com sucesso.\n";
} else {
    echo "Erro ao criar 'ItensReceita': " . mysqli_error($link) . "\n";
}

// 3. Tabela AtendimentoArquivos
// Link N:N entre Atendimentos e Arquivos (usando a tabela Arquivos já existente)
$sql = "CREATE TABLE IF NOT EXISTS `AtendimentoArquivos` (
  `id_vinculo` int NOT NULL AUTO_INCREMENT,
  `id_atendimento` int NOT NULL,
  `id_arquivo` int NOT NULL,
  PRIMARY KEY (`id_vinculo`),
  KEY `id_atendimento` (`id_atendimento`),
  KEY `id_arquivo` (`id_arquivo`),
  CONSTRAINT `AtendimentoArquivos_ibfk_1` FOREIGN KEY (`id_atendimento`) REFERENCES `Atendimentos` (`id_atendimento`) ON DELETE CASCADE,
  CONSTRAINT `AtendimentoArquivos_ibfk_2` FOREIGN KEY (`id_arquivo`) REFERENCES `Arquivos` (`id_arquivo`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if (DBExecute($link, $sql)) {
    echo "Tabela 'AtendimentoArquivos' verificada/criada com sucesso.\n";
} else {
    echo "Erro ao criar 'AtendimentoArquivos': " . mysqli_error($link) . "\n";
}

DBClose($link);
echo "Migração concluída.\n";
?>