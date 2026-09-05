<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    die("Acesso negado");
}
include "../database.php";

$id_emissao = $_GET['id'] ?? null;
if (!$id_emissao)
    die("ID não fornecido");

$link = DBConnect();
$id_safe = mysqli_real_escape_string($link, $id_emissao);

// Fetch XML
$query = "SELECT xml_retorno, numero_nota FROM NfseEmissoes WHERE id_emissao = '$id_safe'";
$result = DBExecute($link, $query);
$row = mysqli_fetch_assoc($result);

if (!$row || empty($row['xml_retorno'])) {
    die("XML não encontrado para esta emissão.");
}

$xmlContent = $row['xml_retorno'];

// Trata tags HTML não fechadas (como <br> ou <hr>) dentro do XML do Fisco para evitar erro de parser no navegador
$xmlContent = preg_replace('/<br\s*\/?>/i', '<br/>', $xmlContent);
$xmlContent = preg_replace('/<hr\s*\/?>/i', '<hr/>', $xmlContent);

$filename = "nfse_" . ($row['numero_nota'] ?: $id_emissao) . ".xml";

// Force download or just view? Let's generic XML view.
header('Content-Type: application/xml; charset=utf-8');
header('Content-Disposition: inline; filename="' . $filename . '"');

echo $xmlContent;
?>