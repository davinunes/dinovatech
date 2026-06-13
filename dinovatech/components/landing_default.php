<?php
// dinovatech/components/landing_default.php
// Elegant, premium dark theme landing page for Technology & Automation Company.
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($empresaNome) ?> | Consultoria & Automação Inteligente</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="shortcut icon" href="favicon.ico" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body class="bg-slate-950">
    <div class="min-h-screen bg-slate-950 text-slate-100 flex flex-col font-sans relative overflow-hidden selection:bg-cyan-500 selection:text-slate-900">
        
        <!-- Background decorations -->
        <div class="absolute inset-0 bg-[linear-gradient(to_right,#0f172a_1px,transparent_1px),linear-gradient(to_bottom,#0f172a_1px,transparent_1px)] bg-[size:4rem_4rem] [mask-image:radial-gradient(ellipse_60%_50%_at_50%_0%,#000_70%,transparent_100%)] opacity-60"></div>
        <div class="absolute top-0 left-1/4 -translate-x-1/2 w-96 h-96 bg-cyan-500/10 rounded-full blur-3xl"></div>
        <div class="absolute top-1/3 right-1/4 translate-x-1/2 w-96 h-96 bg-indigo-500/10 rounded-full blur-3xl"></div>

        <!-- Header / Nav -->
        <header class="w-full max-w-7xl mx-auto px-6 h-20 flex items-center justify-between z-10 relative">
            <div class="flex items-center gap-3">
                <?php 
                $logoUrl = AppHelper::getCompanyLogo();
                if ($logoUrl) {
                    if (strpos($logoUrl, 'assets/') === 0) {
                        $logoUrl = 'dinovatech/' . $logoUrl;
                    }
                }
                if ($logoUrl): ?>
                    <img src="<?= $logoUrl ?>" alt="Logo <?= $empresaNome ?>" class="h-10 w-auto object-contain">
                <?php else: ?>
                    <div class="h-10 w-10 rounded-xl bg-gradient-to-tr from-cyan-500 to-indigo-600 flex items-center justify-center shadow-lg shadow-cyan-500/20">
                        <span class="material-icons text-white text-xl">layers</span>
                    </div>
                <?php endif; ?>
                <span class="text-xl font-bold tracking-tight bg-gradient-to-r from-white via-slate-200 to-slate-400 bg-clip-text text-transparent"><?= $empresaNome ?></span>
            </div>
            
            <div>
                <?php if (isset($_SESSION['usuario_id'])): ?>
                    <a href="./dinovatech/dashboard.php" class="inline-flex items-center justify-center px-4 py-2 text-sm font-semibold rounded-lg bg-slate-900 text-slate-200 border border-slate-800 hover:bg-slate-800 transition-all duration-300">
                        Painel Admin &rarr;
                    </a>
                <?php else: ?>
                    <a href="./dinovatech/login.php" class="inline-flex items-center justify-center px-4 py-2 text-sm font-semibold rounded-lg bg-slate-900 text-slate-200 border border-slate-800 hover:bg-slate-800 transition-all duration-300">
                        Acesso Restrito
                    </a>
                <?php endif; ?>
            </div>
        </header>

        <!-- Hero Section -->
        <section class="flex-1 flex flex-col justify-center items-center px-6 text-center max-w-4xl mx-auto z-10 relative py-12 md:py-24">
            <span class="px-3 py-1 text-xs font-semibold text-cyan-400 bg-cyan-500/10 border border-cyan-500/20 rounded-full mb-6 tracking-wide uppercase">
                Inovação & Soluções Digitais
            </span>
            
            <h1 class="text-4xl md:text-6xl font-extrabold tracking-tight text-white mb-6 leading-tight">
                Consultoria de Redes, Software & 
                <span class="bg-gradient-to-r from-cyan-400 via-sky-400 to-indigo-500 bg-clip-text text-transparent">Automação Inteligente</span>
            </h1>
            
            <p class="text-lg md:text-xl text-slate-400 max-w-2xl mb-10 leading-relaxed">
                Desenvolvemos sistemas customizados, estruturamos redes corporativas de alta performance e automatizamos ambientes com CFTV e dispositivos IoT inteligentes.
            </p>

            <div class="flex flex-col sm:flex-row gap-4 w-full sm:w-auto justify-center">
                <a href="./cliente/" class="inline-flex items-center justify-center px-8 py-4 text-base font-bold text-slate-950 bg-gradient-to-r from-cyan-400 to-sky-500 hover:from-cyan-300 hover:to-sky-400 rounded-xl shadow-lg shadow-cyan-500/25 transition-all duration-300 hover:-translate-y-0.5">
                    <span class="material-icons mr-2 text-lg">person</span>
                    Acessar Área do Cliente
                </a>
                
                <?php if (!isset($_SESSION['usuario_id'])): ?>
                    <a href="./dinovatech/login.php" class="inline-flex items-center justify-center px-8 py-4 text-base font-semibold text-slate-300 bg-slate-900/60 border border-slate-800 hover:bg-slate-800/80 rounded-xl hover:text-white transition-all duration-300 backdrop-blur-md">
                        Painel Administrativo
                    </a>
                <?php else: ?>
                    <a href="./dinovatech/dashboard.php" class="inline-flex items-center justify-center px-8 py-4 text-base font-semibold text-slate-300 bg-slate-900/60 border border-slate-800 hover:bg-slate-800/80 rounded-xl hover:text-white transition-all duration-300 backdrop-blur-md">
                        Entrar no Painel
                    </a>
                <?php endif; ?>
            </div>
        </section>

        <!-- Services Section -->
        <section class="max-w-7xl mx-auto px-6 py-16 z-10 relative border-t border-slate-900">
            <h2 class="text-2xl md:text-3xl font-bold text-center text-white mb-12">Nossas Áreas de Atuação</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Redes & Infraestrutura -->
                <div class="p-8 rounded-2xl bg-slate-900/40 border border-slate-800/60 backdrop-blur-sm hover:border-cyan-500/30 hover:bg-slate-900/60 transition-all duration-300 group">
                    <div class="h-12 w-12 rounded-xl bg-cyan-500/10 border border-cyan-500/20 text-cyan-400 flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300">
                        <span class="material-icons">router</span>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-3">Redes & Infraestrutura</h3>
                    <p class="text-slate-400 text-sm leading-relaxed">
                        Consultoria completa em infraestrutura de TI, roteamento estruturado, segurança corporativa, firewalls e redes de alto desempenho com alta disponibilidade.
                    </p>
                </div>

                <!-- Desenvolvimento de Software -->
                <div class="p-8 rounded-2xl bg-slate-900/40 border border-slate-800/60 backdrop-blur-sm hover:border-indigo-500/30 hover:bg-slate-900/60 transition-all duration-300 group">
                    <div class="h-12 w-12 rounded-xl bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300">
                        <span class="material-icons">code</span>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-3">Sistemas & Software</h3>
                    <p class="text-slate-400 text-sm leading-relaxed">
                        Criação de softwares customizados, portais corporativos e integrações via API. Soluções escaláveis desenhadas sob medida para otimizar os processos da sua empresa.
                    </p>
                </div>

                <!-- Automação & Segurança -->
                <div class="p-8 rounded-2xl bg-slate-900/40 border border-slate-800/60 backdrop-blur-sm hover:border-emerald-500/30 hover:bg-slate-900/60 transition-all duration-300 group">
                    <div class="h-12 w-12 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300">
                        <span class="material-icons">settings_remote</span>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-3">Automação & Segurança</h3>
                    <p class="text-slate-400 text-sm leading-relaxed">
                        Projetos de automação residencial e predial: controle de portões eletrônicos, CFTV de alta resolução, iluminação inteligente com interruptores IoT e sensores de segurança.
                    </p>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="w-full border-t border-slate-900/60 bg-slate-950 mt-auto py-8 z-10 relative">
            <div class="max-w-7xl mx-auto px-6 flex flex-col md:flex-row items-center justify-between gap-4 text-xs text-slate-500">
                <div>
                    &copy; <?= date('Y') ?> <?= $empresaNome ?>. Todos os direitos reservados.
                </div>
                <div class="flex gap-6">
                    <a href="termos.html" class="hover:text-slate-300 transition-colors">Termos de Uso</a>
                    <a href="privacidade.html" class="hover:text-slate-300 transition-colors">Política de Privacidade</a>
                </div>
            </div>
        </footer>
    </div>
</body>
</html>
