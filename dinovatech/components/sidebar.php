<?php
$currentPage = basename($_SERVER['PHP_SELF']);
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/helpers/AppHelper.php';

// Determine base path for links
// If we are deep in modules, we need to go up.
// Simple check: are we in modules/Vet?
$basePath = './';
if (strpos($_SERVER['PHP_SELF'], '/modules/Vet/') !== false) {
    $basePath = '../../';
}

// Security Health Check
// Apenas exibe se a chave for inválida ou inexistente.
// A criptografia real falhará (Exception) se tentarem usar, interrompendo a operação.
$hasSecurityIssue = !defined('APP_MASTER_KEY') || empty(APP_MASTER_KEY);
?>
<?php if ($hasSecurityIssue): ?>
    <div
        class="fixed top-0 left-0 w-full bg-red-600 text-white text-center p-3 z-[60] font-bold shadow-lg flex items-center justify-center">
        <span class="material-icons text-xl mr-2">gpp_maybe</span>
        CUIDADO: APP_MASTER_KEY não configurada ou insegura. Criptografia desativada.
    </div>
<?php endif; ?>
<!-- Mobile Overlay -->
<div id="sidebar-overlay" onclick="toggleSidebar()"
    class="fixed inset-0 bg-black bg-opacity-50 z-20 hidden lg:hidden transition-opacity"></div>

<!-- Sidebar -->
<aside id="sidebar"
    class="fixed inset-y-0 left-0 z-30 w-64 bg-slate-900 text-white transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out shadow-xl flex flex-col">
    <!-- Logo area -->
    <div class="h-16 flex items-center justify-center border-b border-slate-800">
        <h2 class="text-xl font-bold tracking-wider text-cyan-400"><a
                href="<?= $basePath ?>index.php"><?= strtoupper(AppHelper::getCompanyName()) ?></a></h2>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto">
        <a href="<?= $basePath ?>dashboard.php"
            class="flex items-center px-4 py-3 rounded-lg transition-colors <?= $currentPage == 'dashboard.php' ? 'bg-cyan-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' ?>">
            <span class="material-icons text-xl mr-3">dashboard</span>
            <span class="font-medium">Dashboard</span>
        </a>

        <div class="pt-4 pb-2">
            <p class="px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Cadastros</p>
        </div>

        <a href="<?= $basePath ?>clientes.php"
            class="flex items-center px-4 py-3 rounded-lg transition-colors <?= $currentPage == 'clientes.php' || $currentPage == 'cliente_form.php' || $currentPage == 'cliente_detalhes.php' ? 'bg-cyan-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' ?>">
            <span class="material-icons text-xl mr-3">people</span>
            <span class="font-medium">Clientes</span>
        </a>

        <?php if (AppHelper::isVetMode()): ?>
            <a href="<?= $basePath ?>modules/Vet/pets.php"
                class="flex items-center px-4 py-3 rounded-lg transition-colors <?= $currentPage == 'pets.php' || $currentPage == 'pet_form.php' || $currentPage == 'pet_detalhes.php' ? 'bg-cyan-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' ?>">
                <span class="material-icons text-xl mr-3">pets</span>
                <span class="font-medium">Pets</span>
            </a>
        <?php endif; ?>

        <a href="<?= $basePath ?>servicos.php"
            class="flex items-center px-4 py-3 rounded-lg transition-colors <?= $currentPage == 'servicos.php' || $currentPage == 'servico_form.php' ? 'bg-cyan-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' ?>">
            <span class="material-icons text-xl mr-3">build</span>
            <span class="font-medium">Serviços</span>
        </a>

        <?php if (AppHelper::isVetMode()): ?>
            <div class="pt-4 pb-2">
                <p class="px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Clínico</p>
            </div>

            <a href="<?= $basePath ?>modules/Vet/vacinas.php"
                class="flex items-center px-4 py-3 rounded-lg transition-colors <?= $currentPage == 'vacinas.php' || $currentPage == 'vacina_form.php' ? 'bg-cyan-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' ?>">
                <span class="material-icons text-xl mr-3">vaccines</span>
                <span class="font-medium">Vacinas (Catálogo)</span>
            </a>

            <a href="<?= $basePath ?>modules/Vet/veterinarios.php"
                class="flex items-center px-4 py-3 rounded-lg transition-colors <?= $currentPage == 'veterinarios.php' || $currentPage == 'veterinario_form.php' ? 'bg-cyan-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' ?>">
                <span class="material-icons text-xl mr-3">medical_services</span>
                <span class="font-medium">Veterinários</span>
            </a>
        <?php endif; ?>

        <div class="pt-4 pb-2">
            <p class="px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Gestão</p>
        </div>

        <a href="<?= $basePath ?>config_fiscal.php"
            class="flex items-center px-4 py-3 rounded-lg transition-colors <?= $currentPage == 'config_fiscal.php' ? 'bg-cyan-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' ?>">
            <span class="material-icons text-xl mr-3">settings</span>
            <span class="font-medium">Configurações</span>
        </a>

        <a href="<?= $basePath ?>contratos.php"
            class="flex items-center px-4 py-3 rounded-lg transition-colors <?= $currentPage == 'contratos.php' || $currentPage == 'contrato_form.php' ? 'bg-cyan-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' ?>">
            <span class="material-icons text-xl mr-3">repeat</span>
            <span class="font-medium">Recorrência</span>
        </a>

        <!-- Futuro Financeiro 
        <a href="financeiro.php" class="flex items-center px-4 py-3 rounded-lg transition-colors <?= $currentPage == 'financeiro.php' ? 'bg-cyan-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' ?>">
            <span class="material-icons text-xl mr-3">attach_money</span>
            <span class="font-medium">Financeiro</span>
        </a>
        -->
        <a href="#" onclick="fazerBackup(event)"
            class="flex items-center px-4 py-3 rounded-lg text-slate-300 hover:bg-slate-800 hover:text-white transition-colors group">
            <span class="material-icons text-xl mr-3 text-slate-400 group-hover:text-white">backup</span>
            <span class="font-medium">Backup BD</span>
        </a>
    </nav>

    <!-- User Profile / Logout -->
    <div class="p-4 border-t border-slate-800">
        <a href="<?= $basePath ?>logout.php"
            class="flex items-center px-4 py-2 text-slate-400 hover:text-white hover:bg-red-600/20 rounded-lg transition-colors">
            <span class="material-icons text-xl mr-3">logout</span>
            <span class="font-medium">Sair</span>
        </a>
    </div>
</aside>

<!-- Topbar (Mobile Only mostly) -->
<header
    class="lg:hidden fixed top-0 w-full bg-white border-b z-10 h-16 flex items-center px-4 justify-between shadow-sm">
    <div class="flex items-center">
        <button onclick="toggleSidebar()" class="text-slate-600 focus:outline-none">
            <span class="material-icons text-2xl">menu</span>
        </button>
        <span class="ml-4 font-bold text-gray-800">DinoVet</span>
    </div>
</header>

<script>
    function fazerBackup(e) {
        e.preventDefault();
        if (!confirm('Deseja gerar um backup completo do banco de dados?\n\nIsso criará os arquivos "estrutura.sql" e "dados.sql" na raiz do sistema, substituindo versões anteriores.')) return;

        const btn = e.currentTarget;
        const iconSpan = btn.querySelector('.material-icons');
        const textSpan = btn.querySelector('.font-medium');
        const originalIcon = iconSpan.innerText;

        iconSpan.innerText = 'refresh';
        iconSpan.classList.add('animate-spin');
        textSpan.innerText = 'Gerando...';

        $.post('app.php', { action: 'fazer_backup' }, function (res) {
            if (res.success) {
                alert(res.message);
            } else {
                alert('Erro: ' + res.message);
            }
        }, 'json')
            .fail(function () {
                alert('Erro de comunicação ao gerar backup.');
            })
            .always(function () {
                iconSpan.innerText = originalIcon;
                iconSpan.classList.remove('animate-spin');
                textSpan.innerText = 'Backup BD';
            });
    }
</script>