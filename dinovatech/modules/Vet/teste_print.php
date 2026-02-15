<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
?>
<!DOCTYPE html>
<html>

<head>
    <title>Teste de Impressão</title>
</head>

<body>
    <h1>TESTE DE VIDA (NOVO ARQUIVO)</h1>
    <p>Se você vê isso, o problema era o arquivo anterior.</p>
    <pre><?php print_r($_GET); ?></pre>
</body>

</html>