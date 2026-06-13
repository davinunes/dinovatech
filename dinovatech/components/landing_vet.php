<?php
// dinovatech/components/landing_vet.php
// Elegant, fresh clinic/veterinary landing page.
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($empresaNome) ?> | Centro Clínico Veterinário</title>
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
<body class="bg-slate-50">
    <div class="min-h-screen bg-slate-50 text-slate-800 flex flex-col font-sans relative overflow-hidden selection:bg-teal-500 selection:text-white">
        
        <!-- Background decorations -->
        <div class="absolute inset-0 bg-[linear-gradient(to_right,#e2e8f0_1px,transparent_1px),linear-gradient(to_bottom,#e2e8f0_1px,transparent_1px)] bg-[size:3rem_3rem] [mask-image:radial-gradient(ellipse_60%_50%_at_50%_0%,#fff_80%,transparent_100%)] opacity-50"></div>
        <div class="absolute top-0 right-1/4 w-96 h-96 bg-teal-400/10 rounded-full blur-3xl"></div>
        <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-sky-400/10 rounded-full blur-3xl"></div>

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
                    <div class="h-10 w-10 rounded-xl bg-gradient-to-tr from-teal-500 to-sky-600 flex items-center justify-center shadow-lg shadow-teal-500/20">
                        <span class="material-icons text-white text-xl">pets</span>
                    </div>
                <?php endif; ?>
                <span class="text-xl font-bold tracking-tight bg-gradient-to-r from-slate-900 to-slate-700 bg-clip-text text-transparent"><?= $empresaNome ?></span>
            </div>
            
            <div>
                <?php if (isset($_SESSION['usuario_id'])): ?>
                    <a href="./dinovatech/dashboard.php" class="inline-flex items-center justify-center px-4 py-2 text-sm font-semibold rounded-lg bg-white text-slate-700 border border-slate-200 hover:bg-slate-50 transition-all duration-300 shadow-sm">
                        Painel Clínico &rarr;
                    </a>
                <?php else: ?>
                    <a href="./dinovatech/login.php" class="inline-flex items-center justify-center px-4 py-2 text-sm font-semibold rounded-lg bg-white text-slate-700 border border-slate-200 hover:bg-slate-50 transition-all duration-300 shadow-sm">
                        Acesso Veterinário
                    </a>
                <?php endif; ?>
            </div>
        </header>

        <!-- Hero Section -->
        <section class="flex-1 flex flex-col justify-center items-center px-6 text-center max-w-4xl mx-auto z-10 relative py-12 md:py-24">
            <span class="px-3 py-1 text-xs font-semibold text-teal-700 bg-teal-50 border border-teal-100 rounded-full mb-6 tracking-wide uppercase">
                Centro Clínico Veterinário
            </span>
            
            <h1 class="text-4xl md:text-6xl font-extrabold tracking-tight text-slate-900 mb-6 leading-tight">
                Cuidado Especializado & Carinho para o
                <span class="bg-gradient-to-r from-teal-600 via-emerald-600 to-sky-600 bg-clip-text text-transparent">Seu Melhor Amigo</span>
            </h1>
            
            <p class="text-lg md:text-xl text-slate-600 max-w-2xl mb-10 leading-relaxed">
                Plataforma de gestão integrada para cuidados veterinários, acompanhamento de exames, histórico de vacinas e emissão simplificada de receitas digitais.
            </p>

            <div class="flex flex-col sm:flex-row gap-4 w-full sm:w-auto justify-center">
                <a href="./cliente/" class="inline-flex items-center justify-center px-8 py-4 text-base font-bold text-white bg-gradient-to-r from-teal-500 to-sky-500 hover:from-teal-600 hover:to-sky-600 rounded-xl shadow-lg shadow-teal-500/25 transition-all duration-300 hover:-translate-y-0.5">
                    <span class="material-icons mr-2 text-lg">pets</span>
                    Área do Tutor (Acessar Carteira)
                </a>
                
                <?php if (!isset($_SESSION['usuario_id'])): ?>
                    <a href="./dinovatech/login.php" class="inline-flex items-center justify-center px-8 py-4 text-base font-semibold text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 rounded-xl transition-all duration-300 shadow-sm">
                        Painel do Profissional
                    </a>
                <?php else: ?>
                    <a href="./dinovatech/dashboard.php" class="inline-flex items-center justify-center px-8 py-4 text-base font-semibold text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 rounded-xl transition-all duration-300 shadow-sm">
                        Entrar no Painel Clínico
                    </a>
                <?php endif; ?>
            </div>
        </section>

        <!-- Features Section -->
        <section class="max-w-7xl mx-auto px-6 py-16 z-10 relative border-t border-slate-200/80">
            <h2 class="text-2xl md:text-3xl font-bold text-center text-slate-900 mb-12">Serviços Clínicos & Administrativos</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Prontuário & Atendimento -->
                <div class="p-8 rounded-2xl bg-white border border-slate-200 shadow-sm hover:border-teal-500/30 hover:shadow-md transition-all duration-300 group">
                    <div class="h-12 w-12 rounded-xl bg-teal-500/10 text-teal-600 flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300">
                        <span class="material-icons">assignment</span>
                    </div>
                    <h3 class="text-xl font-bold text-slate-950 mb-3">Prontuário de Atendimento</h3>
                    <p class="text-slate-600 text-sm leading-relaxed">
                        Histórico médico unificado com queixas principais, anamnese detalhada, condutas, tratamentos e diagnósticos veterinários seguros para cada pet.
                    </p>
                </div>

                <!-- Carteira de Vacinas -->
                <div class="p-8 rounded-2xl bg-white border border-slate-200 shadow-sm hover:border-sky-500/30 hover:shadow-md transition-all duration-300 group">
                    <div class="h-12 w-12 rounded-xl bg-sky-500/10 text-sky-600 flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300">
                        <span class="material-icons">vaccines</span>
                    </div>
                    <h3 class="text-xl font-bold text-slate-950 mb-3">Controle Vacinal Inteligente</h3>
                    <p class="text-slate-600 text-sm leading-relaxed">
                        Acompanhamento do ciclo de vacinas aplicadas, datas de vencimento e alertas automáticos para as próximas aplicações de cada animal.
                    </p>
                </div>

                <!-- Receitas & Documentos -->
                <div class="p-8 rounded-2xl bg-white border border-slate-200 shadow-sm hover:border-emerald-500/30 hover:shadow-md transition-all duration-300 group">
                    <div class="h-12 w-12 rounded-xl bg-emerald-500/10 text-emerald-600 flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300">
                        <span class="material-icons">receipt_long</span>
                    </div>
                    <h3 class="text-xl font-bold text-slate-950 mb-3">Receitas & Documentos</h3>
                    <p class="text-slate-600 text-sm leading-relaxed">
                        Geração e impressão de receitas digitais completas, laudos e termos autorizativos com layout profissional e assinatura do veterinário.
                    </p>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="w-full border-t border-slate-200/80 bg-white mt-auto py-8 z-10 relative">
            <div class="max-w-7xl mx-auto px-6 flex flex-col md:flex-row items-center justify-between gap-4 text-xs text-slate-500">
                <div class="text-center md:text-left">
                    <span class="block">&copy; <?= date('Y') ?> <?= $empresaNome ?>. Todos os direitos reservados.</span>
                    <?php if (!empty($empresaRazaoSocial) || !empty($empresaCNPJ)): ?>
                        <span class="block mt-1 text-slate-400">
                            <?= htmlspecialchars($empresaRazaoSocial) ?> 
                            <?= !empty($empresaCNPJ) ? ' | CNPJ: ' . htmlspecialchars($empresaCNPJ) : '' ?>
                        </span>
                    <?php endif; ?>
                </div>
                <div class="flex gap-6">
                    <a href="termos.html" class="hover:text-slate-700 transition-colors">Termos de Uso</a>
                    <a href="privacidade.html" class="hover:text-slate-700 transition-colors">Política de Privacidade</a>
                </div>
            </div>
        </footer>
    </div>
</body>
</html>
