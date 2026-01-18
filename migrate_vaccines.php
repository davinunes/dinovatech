<?php
include "database.php";

$link = DBConnect();

echo "<h2>Migrando Vacinas (Ciclos)</h2>";
echo "<pre>";

function execSql($link, $label, $sql)
{
    if (mysqli_query($link, $sql)) {
        echo "$label: <span style='color:green'>OK</span><br>";
    } else {
        echo "$label: <span style='color:red'>ERRO: " . mysqli_error($link) . "</span><br>";
    }
}

// VacinaCiclos
execSql($link, "Tabela: VacinaCiclos", "CREATE TABLE IF NOT EXISTS `VacinaCiclos` (
  `id_ciclo` int NOT NULL AUTO_INCREMENT,
  `id_vacina` int NOT NULL,
  `nome` varchar(100) NOT NULL,
  `intervalo` int NOT NULL,
  PRIMARY KEY (`id_ciclo`),
  FOREIGN KEY (`id_vacina`) REFERENCES `Vacinas`(`id_vacina`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

echo "</pre>";
DBClose($link);
?>