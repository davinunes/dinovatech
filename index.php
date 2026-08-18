<?php
session_start();
require_once 'dinovatech/helpers/AppHelper.php';
AppHelper::checkRememberLogin();
$empresaNome = AppHelper::getCompanyName();

// Dynamic Landing Page Router
$landingTheme = 'default';
$landingPath = '';
$empresaCNPJ = '';
$empresaRazaoSocial = '';

$dbPath = __DIR__ . '/database.php';
if (file_exists($dbPath)) {
    require_once $dbPath;
    $link = DBConnect();
    if ($link) {
        $res = @mysqli_query($link, "SELECT landing_page_theme, landing_page_path, cnpj, razao_social FROM ConfiguracoesEmissor LIMIT 1");
        if ($res && $row = mysqli_fetch_assoc($res)) {
            $landingTheme = $row['landing_page_theme'] ?? 'default';
            $landingPath = $row['landing_page_path'] ?? '';
            $empresaCNPJ = $row['cnpj'] ?? '';
            $empresaRazaoSocial = $row['razao_social'] ?? '';
        }
        DBClose($link);
    }
}

// 1. Custom Directory Routing
if ($landingTheme === 'custom' && !empty($landingPath)) {
    // Prevent directory traversal
    $safePath = str_replace(['..', '\\'], ['', '/'], $landingPath);
    $safePath = trim($safePath, '/');
    if (!empty($safePath) && is_dir(__DIR__ . '/' . $safePath)) {
        $fullPath = __DIR__ . '/' . $safePath;
        if (file_exists($fullPath . '/index.php')) {
            include $fullPath . '/index.php';
            exit();
        } elseif (file_exists($fullPath . '/index.html')) {
            include $fullPath . '/index.html';
            exit();
        }
    }
}

// 2. Built-in Themes Routing
if ($landingTheme === 'vet') {
    $themeFile = __DIR__ . '/dinovatech/components/landing_vet.php';
    if (file_exists($themeFile)) {
        include $themeFile;
        exit();
    }
} elseif ($landingTheme === 'default') {
    $themeFile = __DIR__ . '/dinovatech/components/landing_default.php';
    if (file_exists($themeFile)) {
        include $themeFile;
        exit();
    }
}

// 3. Fallback - Legacy Clean Login Page
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bem-vindo ao DinoVet</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="shortcut icon" href="favicon.ico" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>

<body class="bg-gray-100 flex items-center justify-center h-screen">
    <div class="container mx-auto p-8 max-w-lg text-center">
        <div class="bg-white p-10 rounded-xl shadow-lg">

            <h1 class="text-3xl font-bold text-gray-800 mb-4">
                <span class="text-cyan-600">
                    <?= $empresaNome ?>
                </span>
            </h1>
            <p class="text-gray-600 mb-8">
                Sistema de Gestão Integrado
            </p>
            <a href="./cliente/"
                class="block w-full bg-cyan-600 hover:bg-cyan-700 text-white font-bold py-3 px-4 rounded-lg transition-colors duration-300 text-lg">
                Acessar Área do Cliente
            </a>
        </div>
        <div class="mt-6 text-center">
            <?php if (isset($_SESSION['usuario_id'])): ?>
                <a href="./dinovatech/dashboard.php"
                    class="text-sm font-bold text-cyan-700 hover:text-cyan-900 hover:underline">
                    &raquo; Acessar Painel Administrativo
                </a></br>
            <?php else: ?>
                <a href="./dinovatech/login.php" class="text-sm text-gray-500 hover:text-gray-700 hover:underline">
                    Acesso Restrito (Login)
                </a></br>
            <?php endif; ?>

            <a href="termos.html" class="text-sm text-gray-500 hover:text-gray-700 hover:underline">
                Termos de Uso
            </a></br>
            <a href="privacidade.html" class="text-sm text-gray-500 hover:text-gray-700 hover:underline">
                Politica de Privacidade
            </a>
        </div>
    </div>
</body>

</html>