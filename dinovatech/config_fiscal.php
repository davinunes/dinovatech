<?php
// config_fiscal.php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers/AppHelper.php';
// Carrega dados iniciais via PHP para preencher o form, ou faz via AJAX no load.
// Como app.php é JSON, melhor fazer via AJAX no load para consistência.
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <title>Configurações - <?= htmlspecialchars(AppHelper::getCompanyName()) ?></title>
    <?php include 'components/layout_head.php'; ?>
</head>

<body class="bg-gray-50 flex">

    <?php include 'components/sidebar.php'; ?>

    <div class="flex-1 flex flex-col lg:ml-64 min-h-screen transition-all duration-300">
        <main class="flex-1 p-6 mt-16 lg:mt-0">

            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                <div>
                    <h2 class="text-3xl font-bold text-gray-800">Configurações</h2>
                    <p class="text-gray-500">Dados da empresa, parâmetros fiscais, integrações e sistema.</p>
                </div>
            </div>

            <div class="max-w-4xl mx-auto bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <form id="formConfigFiscal">
                    <input type="hidden" name="action" value="save_config_fiscal">
                    <input type="hidden" name="id_config" id="id_config">

                    <!-- Tabs Header -->
                    <div class="border-b border-gray-200 mb-6">
                        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                            <button type="button" onclick="switchTab('fiscal')" id="tab-fiscal"
                                class="border-cyan-500 text-cyan-600 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                                Dados Fiscais
                            </button>
                            <button type="button" onclick="switchTab('certificado')" id="tab-certificado"
                                class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                                Certificado Digital
                            </button>
                            <button type="button" onclick="switchTab('integracoes')" id="tab-integracoes"
                                class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                                Integrações (API)
                            </button>
                            <button type="button" onclick="switchTab('atualizacoes')" id="tab-atualizacoes"
                                class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                                Atualizações
                            </button>
                            <button type="button" onclick="switchTab('usuarios')" id="tab-usuarios"
                                class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                                Usuários
                            </button>
                        </nav>
                    </div>

                    <!-- TAB: FISCAL -->
                    <!-- TAB: GERAL / FISCAL -->
                    <div id="content-fiscal" class="tab-content active space-y-6">
                        
                        <!-- CARD 1: DADOS GERAIS DA EMPRESA & IDENTIDADE -->
                        <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm">
                            <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                                <span class="material-icons mr-2 text-cyan-600">business</span> Dados Gerais da Empresa
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Razão Social *</label>
                                    <input type="text" name="razao_social" id="razao_social" required
                                        class="w-full rounded-lg border-gray-300 focus:border-cyan-500 focus:ring-cyan-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Nome Fantasia</label>
                                    <input type="text" name="nome_fantasia" id="nome_fantasia"
                                        class="w-full rounded-lg border-gray-300 focus:border-cyan-500 focus:ring-cyan-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">CNPJ *</label>
                                    <input type="text" name="cnpj" id="cnpj" required
                                        class="w-full rounded-lg border-gray-300 focus:border-cyan-500 focus:ring-cyan-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Telefone / WhatsApp</label>
                                    <input type="text" name="telefone" id="telefone"
                                        class="w-full rounded-lg border-gray-300 focus:border-cyan-500 focus:ring-cyan-500">
                                </div>
                            </div>

                            <div class="border-t border-gray-100 pt-4 mt-4">
                                <label
                                    class="flex items-center space-x-2 cursor-pointer bg-gray-50 p-3 rounded-lg border border-gray-200">
                                    <input type="checkbox" name="permitir_cadastro_sem_cpf" id="permitir_cadastro_sem_cpf"
                                        value="1" class="form-checkbox h-5 w-5 text-cyan-600 rounded">
                                    <span class="text-gray-700 font-medium">Permitir cadastro de clientes sem CPF</span>
                                </label>
                                <p class="text-xs text-gray-500 mt-1 ml-1">Se habilitado, o campo CPF/CNPJ será opcional no cadastro de novos clientes.</p>
                            </div>

                            <div class="border-t border-gray-100 pt-4 mt-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Logo da Empresa</label>
                                <div class="flex items-center space-x-4">
                                    <div
                                        class="flex-shrink-0 h-16 w-16 bg-gray-100 rounded-lg border border-gray-200 flex items-center justify-center overflow-hidden">
                                        <img id="logo_preview" src="" alt="Logo"
                                            class="h-full w-full object-contain hidden">
                                        <span id="logo_placeholder" class="material-icons text-gray-400">image</span>
                                    </div>
                                    <div class="flex-1">
                                        <input type="file" name="arquivo_logo" id="arquivo_logo"
                                            accept=".png, .jpg, .jpeg, .webp"
                                            class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-cyan-50 file:text-cyan-700 hover:file:bg-cyan-100 transition-colors">
                                        <p class="text-xs text-gray-500 mt-1">Recomendado: 200x200px (PNG ou JPG).</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Customização da Landing Page -->
                            <div class="border-t border-gray-100 pt-4 mt-4">
                                <h4 class="text-sm font-bold text-gray-700 mb-3 flex items-center">
                                    <span class="material-icons mr-1.5 text-cyan-600 text-sm">home</span> Customização da Página Inicial (Landing Page)
                                </h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Modelo/Tema da Página Inicial</label>
                                        <select name="landing_page_theme" id="landing_page_theme" onchange="toggleLandingCustomPath()"
                                            class="w-full rounded-lg border-gray-300 focus:border-cyan-500 focus:ring-cyan-500 bg-gray-50 text-sm">
                                            <option value="default">Padrão (Empresa de Tecnologia / Serviços)</option>
                                            <?php if (AppHelper::isVetMode()): ?>
                                                <option value="vet">Clínica Veterinária (Tema DinoVet)</option>
                                            <?php endif; ?>
                                            <option value="custom">Diretório Personalizado (Avançado)</option>
                                        </select>
                                    </div>
                                    <div id="landing_path_container" class="hidden">
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Caminho do Diretório Customizado</label>
                                        <input type="text" name="landing_page_path" id="landing_page_path" placeholder="Ex: custom_home"
                                            class="w-full rounded-lg border-gray-300 focus:border-cyan-500 focus:ring-cyan-500 text-sm">
                                    </div>
                                </div>
                            </div>

                            <!-- Módulo Banho & Tosa (DinoVet) -->
                            <?php if (AppHelper::isVetMode()): ?>
                            <div class="border-t border-gray-100 pt-4 mt-4 space-y-4">
                                <h4 class="text-sm font-bold text-gray-700 flex items-center">
                                    <span class="material-icons mr-1.5 text-teal-600 text-sm">shower</span> Módulo de Estética & Banho (DinoVet)
                                </h4>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="bg-gray-50 border border-gray-200 rounded-xl p-4">
                                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Vagas / Atendimentos Simultâneos por Horário *</label>
                                        <input type="number" name="banho_capacidade_simultanea" id="banho_capacidade_simultanea" min="1" max="50" value="2" required
                                            class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500 font-bold text-base p-2.5">
                                        <p class="text-[11px] text-gray-500 mt-1.5 leading-relaxed">
                                            Define quantos pets podem ser agendados simultaneamente no mesmo slot de horário (baseado no espaço físico, banheiras e mesas de tosa disponíveis).
                                        </p>
                                    </div>

                                    <div class="bg-teal-50/70 border border-teal-200 rounded-xl p-4 flex items-center">
                                        <label class="flex items-start space-x-3 cursor-pointer">
                                            <input type="checkbox" name="banho_checkin_foto_ativo" id="banho_checkin_foto_ativo" value="1"
                                                class="h-5 w-5 text-teal-600 focus:ring-teal-500 border-gray-300 rounded mt-0.5">
                                            <div>
                                                <span class="text-sm font-bold text-gray-800">Check-in Fotográfico na Recepção</span>
                                                <p class="text-xs text-gray-500 mt-0.5">Permite à equipe anexar fotos de nós, avarias ou ferimentos pré-existentes na esteira de produção do banho e tosa.</p>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- CARD 2: EMISSÃO DE NOTA FISCAL (NFS-E) - SEPARADO VISUALMENTE -->
                        <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-gray-100 pb-4 mb-4">
                                <div>
                                    <h3 class="text-lg font-bold text-gray-800 flex items-center">
                                        <span class="material-icons mr-2 text-indigo-600">receipt_long</span> Integração & Emissão de Nota Fiscal (NFS-e)
                                    </h3>
                                    <p class="text-xs text-gray-500 mt-0.5">Configure aqui a tributação municipal e parâmetros para emissão automática de NFS-e.</p>
                                </div>
                                
                                <!-- Toggle de Ativação Fiscal -->
                                <label class="flex items-center space-x-2 cursor-pointer bg-indigo-50 border border-indigo-200 px-3.5 py-2 rounded-xl">
                                    <input type="checkbox" name="modulo_fiscal_ativo" id="toggle_modulo_fiscal" value="1" onchange="toggleCardFiscal()"
                                        class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                    <span class="text-xs font-bold text-indigo-900">Ativar Módulo Fiscal (NFS-e)</span>
                                </label>
                            </div>

                            <!-- Fiscal Inactive Banner -->
                            <div id="bannerFiscalInativo" class="p-6 text-center bg-gray-50 rounded-xl border border-dashed border-gray-200">
                                <span class="material-icons text-4xl text-gray-300 mb-1">receipt</span>
                                <p class="text-sm font-semibold text-gray-600">Emissão de NFS-e não ativada</p>
                                <p class="text-xs text-gray-400 mt-0.5">Marque a opção acima caso sua empresa emita notas fiscais de serviço.</p>
                            </div>

                            <!-- Fiscal Fields Container -->
                            <div id="containerCamposFiscais" class="hidden space-y-6">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Inscrição Municipal *</label>
                                        <input type="text" name="inscricao_municipal" id="inscricao_municipal"
                                            class="w-full rounded-lg border-gray-300 focus:border-cyan-500 focus:ring-cyan-500">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Inscrição Estadual (Opcional)</label>
                                        <input type="text" name="inscricao_estadual" id="inscricao_estadual"
                                            class="w-full rounded-lg border-gray-300 focus:border-cyan-500 focus:ring-cyan-500">
                                    </div>
                                </div>

                                <!-- Endereço Fiscal -->
                                <div class="border-t border-gray-100 pt-4">
                                    <h4 class="text-sm font-semibold text-gray-700 mb-3">Endereço da Empresa (Obrigatório para NFS-e)</h4>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div class="md:col-span-2">
                                            <label class="block text-xs font-medium text-gray-700 mb-1">Logradouro</label>
                                            <input type="text" name="endereco" id="endereco"
                                                class="w-full rounded-lg border-gray-300 focus:border-cyan-500 focus:ring-cyan-500 text-sm">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 mb-1">Número</label>
                                            <input type="text" name="numero" id="numero"
                                                class="w-full rounded-lg border-gray-300 focus:border-cyan-500 focus:ring-cyan-500 text-sm">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 mb-1">Complemento</label>
                                            <input type="text" name="complemento" id="complemento"
                                                class="w-full rounded-lg border-gray-300 focus:border-cyan-500 focus:ring-cyan-500 text-sm">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 mb-1">Bairro</label>
                                            <input type="text" name="bairro" id="bairro"
                                                class="w-full rounded-lg border-gray-300 focus:border-cyan-500 focus:ring-cyan-500 text-sm">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 mb-1">CEP</label>
                                            <input type="text" name="cep" id="cep"
                                                class="w-full rounded-lg border-gray-300 focus:border-cyan-500 focus:ring-cyan-500 text-sm">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 mb-1">UF</label>
                                            <input type="text" name="uf" id="uf" maxlength="2"
                                                class="w-full rounded-lg border-gray-300 focus:border-cyan-500 focus:ring-cyan-500 uppercase text-sm">
                                        </div>
                                    </div>
                                </div>

                                <!-- Tributação -->
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 border-t border-gray-100 pt-4">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Cód. Município (IBGE)</label>
                                        <input type="text" name="codigo_municipio" id="codigo_municipio" value="5300108"
                                            class="w-full rounded-lg border-gray-300 focus:border-cyan-500 focus:ring-cyan-500 text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Regime Tributário</label>
                                        <select name="regime_tributario" id="regime_tributario"
                                            class="w-full rounded-lg border-gray-300 focus:border-cyan-500 focus:ring-cyan-500 text-sm">
                                            <option value="simples">Simples Nacional</option>
                                            <option value="lucro_presumido">Lucro Presumido</option>
                                            <option value="lucro_real">Lucro Real</option>
                                        </select>
                                    </div>
                                    <div class="flex items-center pt-5">
                                        <input type="checkbox" name="optante_simples" id="optante_simples" value="1"
                                            class="h-4 w-4 text-cyan-600 focus:ring-cyan-500 border-gray-300 rounded">
                                        <label for="optante_simples" class="ml-2 block text-xs font-medium text-gray-900">
                                            Optante pelo Simples Nacional
                                        </label>
                                    </div>
                                </div>

                                <!-- Parâmetros NFS-e e Provedor -->
                                <div class="border-t border-gray-100 pt-4">
                                    <div class="flex items-center justify-between mb-3">
                                        <h4 class="text-sm font-semibold text-gray-700">Padrão de Emissão e Numeração</h4>
                                        <span class="text-xs bg-purple-100 text-purple-700 font-bold px-2 py-0.5 rounded">Transição Nacional 2026</span>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                        <div>
                                            <label class="block text-xs font-semibold text-gray-700 mb-1">Padrão da Integração NFS-e</label>
                                            <select name="nfse_provider" id="nfse_provider"
                                                class="w-full rounded-lg border-gray-300 focus:border-cyan-500 focus:ring-cyan-500 text-sm font-bold bg-white">
                                                <option value="legacy">ABRASF 2.04 (Provedor Legado ISS.net / DF)</option>
                                                <option value="nacional">Padrão Nacional (Nota Control / SPED Fazenda DF)</option>
                                            </select>
                                            <p class="text-[11px] text-gray-500 mt-1">Permite alternar entre a emissão antiga e o novo padrão nacional sem alterar código.</p>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 mb-1">Ambiente Padrão</label>
                                            <select name="ambiente_padrao" id="ambiente_padrao"
                                                class="w-full rounded-lg border-gray-300 focus:border-cyan-500 focus:ring-cyan-500 bg-gray-50 text-sm">
                                                <option value="homologacao">Homologação (Teste)</option>
                                                <option value="producao">Produção (Valendo)</option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Campos Legado RPS -->
                                    <div class="p-3 bg-gray-50 rounded-lg border border-gray-200 mb-3">
                                        <h5 class="text-xs font-bold text-gray-700 mb-2 flex items-center">
                                            <span class="material-icons text-sm mr-1 text-gray-500">history</span> Parâmetros Legado (RPS / ABRASF 2.04)
                                        </h5>
                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                            <div>
                                                <label class="block text-[11px] font-medium text-gray-600 mb-0.5">Série RPS</label>
                                                <input type="text" name="serie_rps" id="serie_rps" value="8"
                                                    class="w-full rounded-lg border-gray-300 focus:border-cyan-500 focus:ring-cyan-500 text-xs">
                                            </div>
                                            <div>
                                                <label class="block text-[11px] font-medium text-gray-600 mb-0.5">Último RPS (Homologação)</label>
                                                <input type="number" name="ultimo_rps_homologacao" id="ultimo_rps_homologacao" value="0"
                                                    class="w-full rounded-lg border-gray-300 focus:border-cyan-500 focus:ring-cyan-500 text-xs">
                                            </div>
                                            <div>
                                                <label class="block text-[11px] font-medium text-gray-600 mb-0.5">Último RPS (Produção)</label>
                                                <input type="number" name="ultimo_rps_producao" id="ultimo_rps_producao" value="0"
                                                    class="w-full rounded-lg border-gray-300 focus:border-cyan-500 focus:ring-cyan-500 text-xs font-semibold bg-white">
                                            </div>
                                        </div>
                                        <div class="mt-2 flex items-center justify-between">
                                            <button type="button" id="btnSincronizarRps" onclick="sincronizarRpsIss()"
                                                class="text-xs text-gray-700 hover:text-gray-900 bg-white border border-gray-300 px-2.5 py-1 rounded font-medium flex items-center gap-1 transition shadow-sm">
                                                <span class="material-icons text-xs text-gray-500">sync</span> Sincronizar RPS com ISS DF
                                            </button>
                                            <span id="sync_rps_status" class="text-[11px] text-gray-500 italic"></span>
                                        </div>
                                    </div>

                                    <!-- Campos Novo Padrão DPS -->
                                    <div class="p-3 bg-purple-50/50 rounded-lg border border-purple-200">
                                        <h5 class="text-xs font-bold text-purple-900 mb-2 flex items-center">
                                            <span class="material-icons text-sm mr-1 text-purple-600">verified</span> Parâmetros Novo Padrão Nacional (DPS)
                                        </h5>
                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                            <div>
                                                <label class="block text-[11px] font-medium text-purple-800 mb-0.5">Série DPS</label>
                                                <input type="text" name="serie_dps" id="serie_dps" value="1"
                                                    class="w-full rounded-lg border-purple-300 focus:border-purple-500 focus:ring-purple-500 text-xs bg-white">
                                            </div>
                                            <div>
                                                <label class="block text-[11px] font-medium text-purple-800 mb-0.5">Última DPS (Homologação)</label>
                                                <input type="number" name="ultimo_dps_homologacao" id="ultimo_dps_homologacao" value="0"
                                                    class="w-full rounded-lg border-purple-300 focus:border-purple-500 focus:ring-purple-500 text-xs bg-white">
                                            </div>
                                            <div>
                                                <label class="block text-[11px] font-medium text-purple-800 mb-0.5">Última DPS (Produção)</label>
                                                <input type="number" name="ultimo_dps_producao" id="ultimo_dps_producao" value="0"
                                                    class="w-full rounded-lg border-purple-300 focus:border-purple-500 focus:ring-purple-500 text-xs font-semibold bg-white">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- TAB: CERTIFICADO -->
                    <div id="content-certificado" class="tab-content hidden">
                        <h3 class="text-lg font-semibold text-gray-700 mb-4 flex items-center">
                            <span class="material-icons mr-2 text-cyan-600">vpn_key</span> Certificado Digital
                        </h3>
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6 text-sm text-blue-800">
                            O certificado digital (A1 / PFX) é essencial para a emissão de notas fiscais.
                            <br>A senha será armazenada de forma segura (criptografada).
                        </div>

                        <div class="space-y-4">
                            <!-- Upload Area -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Arquivo do Certificado
                                    (.pfx)</label>
                                <div class="flex items-center gap-4">
                                    <div class="flex-1">
                                        <input type="file" name="arquivo_pfx" id="arquivo_pfx" accept=".pfx" class="block w-full text-sm text-slate-500
                                            file:mr-4 file:py-2 file:px-4
                                            file:rounded-full file:border-0
                                            file:text-sm file:font-semibold
                                            file:bg-cyan-50 file:text-cyan-700
                                            hover:file:bg-cyan-100
                                            cursor-pointer border border-gray-300 rounded-lg">
                                    </div>
                                    <div class="text-xs text-gray-500" id="current_cert_info">
                                        <!-- Will be populated via JS if exists -->
                                        <div class="flex flex-col gap-1">
                                            <div class="flex items-center">
                                                <span
                                                    class="material-icons text-gray-400 text-sm mr-1">insert_drive_file</span>
                                                <span id="caminho_certificado_display" class="font-mono">Nenhum
                                                    salvo</span>
                                            </div>
                                            <div id="cert_status_badge" class="hidden">
                                                <!-- Populated via JS -->
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <input type="hidden" name="caminho_certificado" id="caminho_certificado">
                            </div>

                            <div class="pt-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Senha do Certificado</label>
                                <div class="relative">
                                    <input type="password" name="senha_certificado" id="senha_certificado"
                                        placeholder="Preencha apenas se fez novo upload ou deseja alterar"
                                        class="w-full rounded-lg border-gray-300 focus:border-cyan-500 focus:ring-cyan-500 pr-10">
                                    <button type="button" onclick="togglePass('senha_certificado')"
                                        class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-400 hover:text-gray-600">
                                        <span class="material-icons text-sm">visibility</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB: INTEGRAÇÕES -->
                    <div id="content-integracoes" class="tab-content hidden">
                        <h3 class="text-lg font-semibold text-gray-700 mb-4 flex items-center">
                            <span class="material-icons mr-2 text-cyan-600">api</span> Integrações Externas
                        </h3>

                        <!-- Banco Inter -->
                        <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 mb-6">
                            <div class="flex items-center mb-4 border-b border-gray-200 pb-2">
                                <img src="https://api-financeiro.agilize.com.br/api/image/inter-ce9e01981d.png"
                                    alt="Inter" class="h-6 mr-3">
                                <h4 class="font-bold text-gray-800">Banco Inter (API Cobrança / PIX)</h4>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Client ID</label>
                                    <input type="text" name="api_inter_client_id" id="api_inter_client_id"
                                        class="w-full rounded-lg border-gray-300 focus:border-cyan-500 focus:ring-cyan-500 font-mono text-sm">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Client Secret</label>
                                    <div class="relative">
                                        <input type="password" name="api_inter_client_secret"
                                            id="api_inter_client_secret" placeholder="••••••••••••••••"
                                            autocomplete="new-password"
                                            class="w-full rounded-lg border-gray-300 focus:border-cyan-500 focus:ring-cyan-500 pr-10">
                                        <button type="button" onclick="togglePass('api_inter_client_secret')"
                                            class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-400 hover:text-gray-600">
                                            <span class="material-icons text-sm">visibility</span>
                                        </button>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Chave PIX</label>
                                    <input type="text" name="api_inter_chave_pix" id="api_inter_chave_pix"
                                        class="w-full rounded-lg border-gray-300 focus:border-cyan-500 focus:ring-cyan-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Conta Corrente</label>
                                    <input type="text" name="api_inter_conta_corrente" id="api_inter_conta_corrente"
                                        class="w-full rounded-lg border-gray-300 focus:border-cyan-500 focus:ring-cyan-500">
                                </div>

                                <!-- Inter Certificates -->
                                <div
                                    class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-4 border-t border-gray-200 pt-4 mt-2">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Arquivo Certificado
                                            (.crt)</label>
                                        <input type="file" name="arquivo_inter_crt" id="arquivo_inter_crt" accept=".crt"
                                            class="block w-full text-xs text-slate-500 file:mr-2 file:py-2 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-orange-50 file:text-orange-700 hover:file:bg-orange-100 cursor-pointer border border-gray-300 rounded-lg">
                                        <div class="text-xs text-gray-500 mt-1" id="current_inter_crt_info">
                                            <span
                                                class="material-icons text-gray-400 text-[10px] mr-1">description</span>
                                            <span id="caminho_inter_crt_display" class="font-mono">Nenhum salvo</span>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Arquivo Chave
                                            (.key)</label>
                                        <input type="file" name="arquivo_inter_key" id="arquivo_inter_key" accept=".key"
                                            class="block w-full text-xs text-slate-500 file:mr-2 file:py-2 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-orange-50 file:text-orange-700 hover:file:bg-orange-100 cursor-pointer border border-gray-300 rounded-lg">
                                        <div class="text-xs text-gray-500 mt-1" id="current_inter_key_info">
                                            <span class="material-icons text-gray-400 text-[10px] mr-1">vpn_key</span>
                                            <span id="caminho_inter_key_display" class="font-mono">Nenhum salvo</span>
                                        </div>
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Arquivo Cadeia CA
                                            (.crt) - Opcional</label>
                                        <input type="file" name="arquivo_inter_ca" id="arquivo_inter_ca" accept=".crt"
                                            class="block w-full text-xs text-slate-500 file:mr-2 file:py-2 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-gray-50 file:text-gray-700 hover:file:bg-gray-100 cursor-pointer border border-gray-300 rounded-lg">
                                        <div class="text-xs text-gray-500 mt-1" id="current_inter_ca_info">
                                            <span class="material-icons text-gray-400 text-[10px] mr-1">security</span>
                                            <span id="caminho_inter_ca_display" class="font-mono">Nenhum salvo</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Oracle -->
                        <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                            <div class="flex items-center mb-4 border-b border-gray-200 pb-2">
                                <span class="material-icons text-red-600 mr-2">cloud</span>
                                <h4 class="font-bold text-gray-800">Oracle OCI (Object Storage)</h4>
                            </div>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">URL Bucket
                                        (Pre-Authenticated)</label>
                                    <input type="text" name="api_oracle_url" id="api_oracle_url"
                                        placeholder="https://objectstorage..."
                                        class="w-full rounded-lg border-gray-300 focus:border-cyan-500 focus:ring-cyan-500 font-mono text-sm">
                                    <p class="text-xs text-gray-500 mt-1">URL pública para upload direto sem necessidade
                                        de auth adicional.</p>
                                </div>

                                <!-- Opcional: Manter User/Pass mas deixar claro que pode não ser usado -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 opacity-50">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">User / Key ID
                                            (Opcional)</label>
                                        <input type="text" name="api_oracle_user" id="api_oracle_user"
                                            class="w-full rounded-lg border-gray-300 focus:border-cyan-500 focus:ring-cyan-500 font-mono text-xs">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Secret
                                            (Opcional)</label>
                                        <input type="password" name="api_oracle_password" id="api_oracle_password"
                                            autocomplete="new-password"
                                            class="w-full rounded-lg border-gray-300 focus:border-cyan-500 focus:ring-cyan-500 font-mono text-xs">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Google Service Account -->
                        <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 mt-6">
                            <div class="flex items-center mb-4 border-b border-gray-200 pb-2">
                                <span class="material-icons text-blue-600 mr-2">event</span>
                                <h4 class="font-bold text-gray-800">Google Calendar (Service Account)</h4>
                            </div>
                            <p class="text-sm text-gray-600 mb-4">Upload do arquivo JSON da conta de serviço para
                                sincronização com Google Agenda.</p>

                            <div class="grid grid-cols-1 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Arquivo JSON (Service
                                        Account)</label>
                                    <input type="file" name="arquivo_google_json" id="arquivo_google_json"
                                        accept=".json"
                                        class="block w-full text-xs text-slate-500 file:mr-2 file:py-2 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer border border-gray-300 rounded-lg">

                                    <div class="text-xs text-gray-500 mt-2" id="current_google_json_info">
                                        <div class="flex items-center">
                                            <span class="material-icons text-gray-400 text-sm mr-1">check_circle</span>
                                            <span id="google_json_status" class="font-medium text-gray-600">Não
                                                configurado</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Google Gmail OAuth 2.0 -->
                        <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 mt-6">
                            <div class="flex items-center mb-4 border-b border-gray-200 pb-2">
                                <span class="material-icons text-cyan-600 mr-2">email</span>
                                <h4 class="font-bold text-gray-800">Google Gmail (OAuth 2.0 - Envio de Faturas)</h4>
                            </div>
                            <p class="text-sm text-gray-600 mb-4">Configuração para enviar faturas, NFS-e e arquivos anexados diretamente por e-mail.</p>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Google Client ID</label>
                                    <input type="text" name="google_oauth_client_id" id="google_oauth_client_id"
                                        placeholder="Ex: 12345678-abcde.apps.googleusercontent.com"
                                        class="w-full rounded-lg border-gray-300 focus:border-cyan-500 focus:ring-cyan-500 font-mono text-xs p-2.5 border">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Google Client Secret</label>
                                    <div class="relative">
                                        <input type="password" name="google_oauth_client_secret" id="google_oauth_client_secret"
                                            placeholder="Preencha apenas para alterar ou cadastrar"
                                            class="w-full rounded-lg border-gray-300 focus:border-cyan-500 focus:ring-cyan-500 font-mono text-xs p-2.5 border pr-10">
                                        <button type="button" onclick="togglePass('google_oauth_client_secret')"
                                            class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-400 hover:text-gray-600">
                                            <span class="material-icons text-sm">visibility</span>
                                        </button>
                                    </div>
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">URI de Redirecionamento Autorizada</label>
                                    <div class="bg-white p-3 rounded-lg text-xs font-mono select-all border border-gray-200" id="google_redirect_uri_display">
                                        <!-- Preenchido via JavaScript -->
                                    </div>
                                    <p class="text-[10px] text-gray-500 mt-1">Copie esta URI e cadastre-na no console de credenciais do Google Cloud para este app.</p>
                                </div>

                                <div class="md:col-span-2 border-t border-gray-200 pt-4 mt-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Template de E-mail de Fatura (Modelo de Documento)</label>
                                    <select name="email_fatura_template_id" id="email_fatura_template_id"
                                        class="w-full rounded-lg border-gray-300 focus:border-cyan-500 focus:ring-cyan-500 p-2.5 border bg-white">
                                        <option value="">(Recomendado) Usar Modelo Premium Padrão da Dinovatech</option>
                                        <!-- Preenchido via JavaScript -->
                                    </select>
                                    <p class="text-[10px] text-gray-500 mt-1">Escolha um modelo de documento cadastrado para servir de corpo do e-mail. Utilize tags como {{NOME_CLIENTE}}, {{VALOR_FATURA}}, {{DATA_VENCIMENTO}}, {{LINK_PAGAMENTO}}, {{BLOCO_NFSE}}, {{ITENS_FATURA}}.</p>
                                </div>

                                <!-- OAuth Bind Status -->
                                <div class="md:col-span-2 bg-blue-50 border border-blue-100 rounded-lg p-4 mt-2">
                                    <h5 class="text-xs font-bold text-blue-900 uppercase tracking-wider mb-2">Vínculo de Conta de Envio</h5>
                                    <div id="gmail_connection_status" class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                                        <div class="flex items-center text-sm text-blue-800">
                                            <span class="material-icons mr-2 text-blue-600 text-base" id="gmail_status_icon">help_outline</span>
                                            <span id="gmail_status_text">Carregando status de vinculação...</span>
                                        </div>
                                        <div id="gmail_action_buttons">
                                            <!-- Renderizado via JavaScript -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ContaDev-Contabilidade Integration -->
                        <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 mt-6">
                            <div class="flex items-center mb-4 border-b border-gray-200 pb-2">
                                <span class="material-icons text-emerald-600 mr-2">account_balance</span>
                                <h4 class="font-bold text-gray-800">ContaDev-Contabilidade</h4>
                            </div>
                            <p class="text-sm text-gray-600 mb-4">Sincronização e importação direta de notas fiscais emitidas (PDF e XML) para o ContaDev.</p>

                            <div id="contadev_form_container" class="space-y-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">E-mail do Usuário ContaDev</label>
                                        <input type="email" id="contadev_email_input" placeholder="seu-email@dominio.com"
                                            class="w-full rounded-lg border-gray-300 focus:border-cyan-500 focus:ring-cyan-500 font-mono text-xs p-2.5 border">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Senha do ContaDev</label>
                                        <div class="relative">
                                            <input type="password" id="contadev_password_input" placeholder="••••••••••••"
                                                class="w-full rounded-lg border-gray-300 focus:border-cyan-500 focus:ring-cyan-500 font-mono text-xs p-2.5 border pr-10">
                                            <button type="button" onclick="togglePass('contadev_password_input')"
                                                class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-400 hover:text-gray-600">
                                                <span class="material-icons text-sm">visibility</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <button type="button" onclick="loginContaDev()" id="btn_login_contadev"
                                        class="bg-emerald-600 hover:bg-emerald-700 text-white text-xs px-4 py-2 rounded-lg font-medium transition-colors shadow-sm inline-flex items-center">
                                        <span class="material-icons text-sm mr-1">login</span> Conectar ContaDev
                                    </button>
                                </div>
                            </div>

                            <!-- Vínculo Ativo Status Card -->
                            <div id="contadev_status_container" class="hidden bg-emerald-50 border border-emerald-200 rounded-lg p-4">
                                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                                    <div>
                                        <div class="flex items-center text-sm text-emerald-900 font-bold mb-1">
                                            <span class="material-icons text-emerald-600 text-base mr-1">check_circle</span>
                                            ContaDev Conectado e Ativo
                                        </div>
                                        <div class="text-xs text-emerald-800 space-y-0.5">
                                            <p><strong>E-mail:</strong> <span id="contadev_status_email">...</span></p>
                                            <p><strong>Usuário:</strong> <span id="contadev_status_user">...</span></p>
                                            <p><strong>Empresa Vinculada:</strong> <span id="contadev_status_company">...</span></p>
                                            <p class="text-[10px] text-emerald-600 font-mono"><strong>CNPJ ID:</strong> <span id="contadev_status_cnpj_id">...</span></p>
                                        </div>
                                    </div>
                                    <div>
                                        <button type="button" onclick="desconectarContaDev()"
                                            class="bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 text-xs px-3 py-1.5 rounded-lg font-medium transition-colors">
                                            Desconectar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- TAB: ATUALIZAÇÕES -->
                    <div id="content-atualizacoes" class="tab-content hidden">
                        <h3 class="text-lg font-semibold text-gray-700 mb-4 flex items-center">
                            <span class="material-icons mr-2 text-cyan-600">system_update</span> Atualizações de Banco
                            de Dados
                        </h3>

                        <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <span class="material-icons text-blue-500">info</span>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-blue-700">
                                        Use esta área para aplicar atualizações de esquema do banco de dados (migrações)
                                        que podem ser necessárias após uma atualização de código.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="mb-6">
                            <button type="button" id="btnRunMigrations"
                                class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-6 rounded-lg shadow-md transition-colors flex items-center">
                                <span class="material-icons mr-2">play_arrow</span> Verificar e Executar Migrações
                            </button>
                        </div>

                        <div id="migrationLogsContainer" class="hidden">
                            <h4 class="text-sm font-bold text-gray-700 mb-2">Log de Execução:</h4>
                            <div id="migrationLogs"
                                class="bg-gray-900 text-green-400 font-mono text-xs p-4 rounded-lg h-64 overflow-y-auto whitespace-pre-wrap shadow-inner border border-gray-700">
                            </div>
                        </div>
                    </div>

                    <!-- TAB: USUÁRIOS -->
                    <div id="content-usuarios" class="tab-content hidden">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold text-gray-700 flex items-center">
                                <span class="material-icons mr-2 text-cyan-600">people</span> Gestão de Usuários
                            </h3>
                            <button type="button" onclick="openUsuarioModal()"
                                class="bg-cyan-600 hover:bg-cyan-700 text-white px-4 py-2 rounded-lg text-sm font-medium flex items-center">
                                <span class="material-icons text-sm mr-1">add</span> Novo Usuário
                            </button>
                        </div>

                        <div class="overflow-x-auto rounded-lg border border-gray-200">
                            <table class="min-w-full divide-y divide-gray-200" id="tabela-usuarios">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Nome</th>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Email</th>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Nível</th>
                                        <th
                                            class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Ações</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200" id="lista-usuarios">
                                    <!-- Populated by JS -->
                                    <tr>
                                        <td colspan="4" class="px-6 py-4 text-center text-gray-500">Carregando...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="flex justify-end pt-6 mt-4 border-t border-gray-100">
                        <button type="submit"
                            class="bg-cyan-600 hover:bg-cyan-700 text-white font-bold py-3 px-8 rounded-lg transition-colors flex items-center shadow-lg">
                            <span class="material-icons mr-2">save</span> Salvar Configurações
                        </button>
                    </div>
                </form>
            </div>

        </main>
    </div>

    <!-- MODAL USUÁRIO -->
    <div id="modalUsuario" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3 text-center">
                <h3 class="text-lg leading-6 font-medium text-gray-900" id="modalUsuarioTitle">Novo Usuário</h3>
                <div class="mt-2 text-left">
                    <form id="formUsuario">
                        <input type="hidden" name="action" value="save_usuario">
                        <input type="hidden" name="id_usuario" id="form_id_usuario">

                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Nome</label>
                            <input type="text" name="nome" id="form_nome_usuario" required
                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Email</label>
                            <input type="email" name="email" id="form_email_usuario" required
                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Senha</label>
                            <input type="password" name="senha" id="form_senha_usuario"
                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            <p class="text-xs text-gray-500 mt-1" id="senha_hint">Deixe em branco para manter a atual
                                (ao editar).</p>
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Nível de Acesso</label>
                            <select name="nivel_acesso" id="form_nivel_acesso"
                                class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                <option value="admin">Administrador</option>
                                <option value="padrao">Padrão</option>
                            </select>
                        </div>

                        <div class="flex items-center justify-end mt-4">
                            <button type="button" onclick="closeUsuarioModal()"
                                class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded mr-2 focus:outline-none focus:shadow-outline">
                                Cancelar
                            </button>
                            <button type="submit"
                                class="bg-cyan-600 hover:bg-cyan-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                                Salvar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php include 'components/layout_scripts.php'; ?>

    <script>
        $(document).ready(function () {
            // Carregar dados existentes
            $.post('app.php', {
                action: 'get_config_fiscal'
            }, function (response) {
                if (response.success && response.data) {
                    const d = response.data;
                    $('#id_config').val(d.id_config);
                    $('#razao_social').val(d.razao_social);
                    $('#nome_fantasia').val(d.nome_fantasia);
                    $('#cnpj').val(d.cnpj);
                    $('#inscricao_municipal').val(d.inscricao_municipal);
                    $('#inscricao_estadual').val(d.inscricao_estadual);
                    $('#codigo_municipio').val(d.codigo_municipio);
                    $('#telefone').val(d.telefone);
                    $('#permitir_cadastro_sem_cpf').prop('checked', d.permitir_cadastro_sem_cpf == 1);

                    if (d.logo_url) {
                        $('#logo_preview').attr('src', d.logo_url + '?' + new Date().getTime()).removeClass('hidden'); // Timestamp to force refresh
                        $('#logo_placeholder').addClass('hidden');
                    } else {
                        $('#logo_preview').addClass('hidden');
                        $('#logo_placeholder').removeClass('hidden');
                    }

                    // Address
                    $('#endereco').val(d.endereco);
                    $('#numero').val(d.numero);
                    $('#complemento').val(d.complemento);
                    $('#bairro').val(d.bairro);
                    $('#cep').val(d.cep);
                    $('#uf').val(d.uf);

                    $('#regime_tributario').val(d.regime_tributario);
                    $('#ambiente_padrao').val(d.ambiente_padrao);
                    if (d.nfse_provider) $('#nfse_provider').val(d.nfse_provider);
                    $('#serie_rps').val(d.serie_rps || '8');
                    $('#ultimo_rps_homologacao').val(d.ultimo_rps_homologacao || 0);
                    $('#ultimo_rps_producao').val(d.ultimo_rps_producao || 0);
                    $('#serie_dps').val(d.serie_dps || '1');
                    $('#ultimo_dps_homologacao').val(d.ultimo_dps_homologacao || 0);
                    $('#ultimo_dps_producao').val(d.ultimo_dps_producao || 0);
                    $('#caminho_certificado').val(d.caminho_certificado);

                    if (d.landing_page_theme) {
                        $('#landing_page_theme').val(d.landing_page_theme);
                    }
                    if (d.landing_page_path) {
                        $('#landing_page_path').val(d.landing_page_path);
                    }
                    toggleLandingCustomPath();

                    // New Fields (Integration)
                    if (d.api_inter_client_id) $('#api_inter_client_id').val(d.api_inter_client_id);
                    if (d.api_inter_chave_pix) $('#api_inter_chave_pix').val(d.api_inter_chave_pix);
                    if (d.api_inter_conta_corrente) $('#api_inter_conta_corrente').val(d.api_inter_conta_corrente);

                    if (d.has_inter_crt || d.api_inter_cert_path) {
                        $('#caminho_inter_crt_display').html('<span class="text-green-600 font-medium">Salvo no banco de dados</span>');
                    } else {
                        $('#caminho_inter_crt_display').text('Nenhum salvo');
                    }

                    if (d.has_inter_key || d.api_inter_key_path) {
                        $('#caminho_inter_key_display').html('<span class="text-green-600 font-medium">Salva no banco de dados</span>');
                    } else {
                        $('#caminho_inter_key_display').text('Nenhum salvo');
                    }

                    if (d.has_inter_ca || d.api_inter_ca_path) {
                        $('#caminho_inter_ca_display').html('<span class="text-green-600 font-medium">Salva no banco de dados</span>');
                    } else {
                        $('#caminho_inter_ca_display').text('Nenhum salvo');
                    }

                    if (d.api_oracle_user) $('#api_oracle_user').val(d.api_oracle_user);
                    if (d.api_oracle_url) $('#api_oracle_url').val(d.api_oracle_url);

                    if (d.has_certificado_pfx || d.caminho_certificado) {
                        $('#caminho_certificado_display').html('<span class="text-green-600 font-medium">Salvo no banco de dados</span>');
                        if (d.caminho_certificado) {
                            $('#caminho_certificado').val(d.caminho_certificado);
                        }

                        // Validate Status
                        const statusBadge = $('#cert_status_badge');
                        statusBadge.removeClass('hidden flex items-center bg-green-100 text-green-800 bg-red-100 text-red-800 px-2 py-0.5 rounded text-xs mt-1');

                        if (d.cert_validation) {
                            if (d.cert_validation.valid) {
                                statusBadge.addClass('flex items-center bg-green-100 text-green-800 px-2 py-0.5 rounded text-xs mt-1 w-fit');
                                statusBadge.html(`<span class="material-icons text-green-600 text-[10px] mr-1">check_circle</span> 
                                                   ${d.cert_validation.message}`);
                            } else {
                                statusBadge.addClass('flex items-center bg-red-100 text-red-800 px-2 py-0.5 rounded text-xs mt-1 w-fit');
                                statusBadge.html(`<span class="material-icons text-red-600 text-[10px] mr-1">error</span> 
                                                   ${d.cert_validation.message}`);
                            }
                        }
                    } else {
                        $('#caminho_certificado_display').text('Nenhum salvo');
                    }

                    if (d.optante_simples == 1) {
                        $('#optante_simples').prop('checked', true);
                    }

                    if (d.banho_checkin_foto_ativo == 1) {
                        $('#banho_checkin_foto_ativo').prop('checked', true);
                    }

                    if (d.banho_capacidade_simultanea) {
                        $('#banho_capacidade_simultanea').val(d.banho_capacidade_simultanea);
                    } else {
                        $('#banho_capacidade_simultanea').val(2);
                    }

                    // Google JSON Status
                    const gStatus = $('#google_json_status');
                    const gInfo = $('#current_google_json_info');
                    if (d.google_json_configured) {
                        gStatus.text('Configurado').removeClass('text-gray-600').addClass('text-green-600');
                        gInfo.find('.material-icons').removeClass('text-gray-400').addClass('text-green-600');

                        // Show Email Hint
                        if (d.google_email) {
                            gInfo.append('<div class="mt-2 p-2 bg-blue-50 text-blue-800 text-xs rounded border border-blue-100">' +
                                '<strong>Dica:</strong> Compartilhe suas agendas do Google Calendar com este e-mail:<br>' +
                                '<code class="select-all font-mono bg-white px-1 rounded border border-blue-200">' + d.google_email + '</code>' +
                                '</div>');
                        }
                    } else {
                        gStatus.text('Não configurado').removeClass('text-green-600').addClass('text-gray-600');
                        gInfo.find('.material-icons').removeClass('text-green-600').addClass('text-gray-400');
                    }

                    // Google Gmail OAuth 2.0
                    const protocol = window.location.protocol;
                    const host = window.location.host;
                    const callbackUrl = `${protocol}//${host}/dinovatech/google_oauth_callback.php`;
                    $('#google_redirect_uri_display').text(callbackUrl);

                    // Carrega a lista de templates (modelos de documentos)
                    if (d.templates_list) {
                        const select = $('#email_fatura_template_id');
                        select.find('option:not(:first)').remove(); // Mantém apenas a primeira padrão
                        d.templates_list.forEach(function (t) {
                            select.append(`<option value="${t.id_modelo}">${t.titulo}</option>`);
                        });
                    }
                    if (d.email_fatura_template_id) {
                        $('#email_fatura_template_id').val(d.email_fatura_template_id);
                    }

                    if (d.google_oauth_client_id) {
                        $('#google_oauth_client_id').val(d.google_oauth_client_id);
                    }

                    // Renderiza o status da vinculação do e-mail do Gmail
                    const gmailStatusIcon = $('#gmail_status_icon');
                    const gmailStatusText = $('#gmail_status_text');
                    const gmailButtons = $('#gmail_action_buttons');

                    if (d.google_oauth_email) {
                        gmailStatusIcon.text('check_circle').removeClass('text-amber-500').addClass('text-green-600');
                        gmailStatusText.html(`Vinculado com o e-mail: <strong class="font-semibold">${d.google_oauth_email}</strong>`);
                        gmailButtons.html(`
                            <button type="button" onclick="desvincularGmail()" class="bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 text-xs px-3 py-1.5 rounded font-medium transition-colors">
                                Desvincular Conta
                            </button>
                        `);
                    } else {
                        gmailStatusIcon.text('warning').removeClass('text-green-600').addClass('text-amber-500');
                        gmailStatusText.text('E-mail de envio não vinculado.');
                        
                        if (d.google_oauth_client_id) {
                            const stateParam = btoa(callbackUrl);
                            const authUrl = `https://accounts.google.com/o/oauth2/v2/auth?client_id=${d.google_oauth_client_id}&redirect_uri=${encodeURIComponent(callbackUrl)}&response_type=code&scope=${encodeURIComponent('https://www.googleapis.com/auth/gmail.send https://www.googleapis.com/auth/userinfo.email')}&access_type=offline&prompt=consent&state=${stateParam}`;
                            gmailButtons.html(`
                                <a href="${authUrl}" class="bg-cyan-600 hover:bg-cyan-700 text-white text-xs px-3 py-2 rounded font-medium transition-colors shadow-sm inline-block">
                                    Vincular Conta Google
                                </a>
                            `);
                        } else {
                            gmailButtons.html(`
                                <span class="text-xs text-gray-500 italic">Cadastre e salve o Client ID acima para poder vincular.</span>
                            `);
                        }
                    }

                    // Toggle Fiscal Module Card Visibility
                    const isFiscalAtivo = (d.modulo_fiscal_ativo !== undefined && d.modulo_fiscal_ativo !== null) 
                        ? (parseInt(d.modulo_fiscal_ativo) === 1) 
                        : (!!d.caminho_certificado || !!d.inscricao_municipal);
                    $('#toggle_modulo_fiscal').prop('checked', isFiscalAtivo);
                    toggleCardFiscal();
                }
            }, 'json');

            // Toggle Fiscal Card display
            window.toggleCardFiscal = function () {
                const isAtivo = $('#toggle_modulo_fiscal').is(':checked');
                if (isAtivo) {
                    $('#containerCamposFiscais').removeClass('hidden');
                    $('#bannerFiscalInativo').addClass('hidden');
                } else {
                    $('#containerCamposFiscais').addClass('hidden');
                    $('#bannerFiscalInativo').removeClass('hidden');
                }
            };

            // Toggle landing path input display
            window.toggleLandingCustomPath = function () {
                const val = $('#landing_page_theme').val();
                if (val === 'custom') {
                    $('#landing_path_container').removeClass('hidden');
                } else {
                    $('#landing_path_container').addClass('hidden');
                }
            };

            // Switch Tab Function
            window.switchTab = function (tabName) {
                // Headers
                $('nav button').removeClass('border-cyan-500 text-cyan-600').addClass('border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300');
                $('#tab-' + tabName).removeClass('border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300').addClass('border-cyan-500 text-cyan-600');

                // Contents
                $('.tab-content').addClass('hidden').removeClass('active');
                $('#content-' + tabName).removeClass('hidden').addClass('active');
            };

            // Salvar
            $('#formConfigFiscal').on('submit', function (e) {
                e.preventDefault();

                var formData = new FormData(this);

                $.ajax({
                    url: 'app.php',
                    type: 'POST',
                    data: formData,
                    dataType: 'json',
                    processData: false, // Don't process the files
                    contentType: false, // Set content type to false as jQuery will tell the server its a query string request
                    success: function (response) {
                        if (response.success) {
                            alert(response.message);
                            location.reload();
                        } else {
                            alert(response.message);
                        }
                    },
                    error: function (xhr) {
                        var msg = 'Erro ao salvar configurações.';
                        if (xhr.responseText) {
                            try {
                                var res = JSON.parse(xhr.responseText);
                                if (res && res.message) msg = res.message;
                            } catch (e) {
                                console.error("Resposta do servidor não-JSON:", xhr.responseText);
                            }
                        }
                        alert(msg);
                    }
                });
            });

            // --- USER MANAGEMENT JS ---

            window.loadUsuarios = function () {
                $.post('app.php', { action: 'get_usuarios' }, function (res) {
                    const tbody = $('#lista-usuarios');
                    tbody.empty();

                    if (res.success && res.data.length > 0) {
                        res.data.forEach(u => {
                            tbody.append(`
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${u.nome}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${u.email}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 capitalize">${u.nivel_acesso}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <button type="button" onclick='editUsuario(${JSON.stringify(u)})' class="text-cyan-600 hover:text-cyan-900 mr-3">Editar</button>
                                        <button type="button" onclick="deleteUsuario('${u.id_usuario}')" class="text-red-600 hover:text-red-900">Excluir</button>
                                    </td>
                                </tr>
                            `);
                        });
                    } else {
                        tbody.append('<tr><td colspan="4" class="px-6 py-4 text-center text-gray-500">Nenhum usuário encontrado.</td></tr>');
                    }
                }, 'json');
            };

            // Load users when tab is switched
            const originalSwitchTab = window.switchTab;
            window.switchTab = function (tabName) {
                originalSwitchTab(tabName);
                if (tabName === 'usuarios') {
                    // Hide main save button in Users tab to avoid confusion
                    $('#btnSalvarGeral').hide();
                    loadUsuarios();
                } else {
                    $('#btnSalvarGeral').show();
                }
            };

            window.openUsuarioModal = function () {
                $('#formUsuario')[0].reset();
                $('#form_id_usuario').val('');
                $('#modalUsuarioTitle').text('Novo Usuário');
                $('#senha_hint').text('Senha é obrigatória para novos usuários.');
                $('#form_senha_usuario').attr('placeholder', 'Digite a senha');
                $('#modalUsuario').removeClass('hidden');
            };

            window.closeUsuarioModal = function () {
                $('#modalUsuario').addClass('hidden');
            };

            window.editUsuario = function (user) {
                $('#form_id_usuario').val(user.id_usuario);
                $('#form_nome_usuario').val(user.nome);
                $('#form_email_usuario').val(user.email);
                $('#form_nivel_acesso').val(user.nivel_acesso);

                $('#modalUsuarioTitle').text('Editar Usuário');
                $('#senha_hint').text('Deixe em branco para manter a atual.');
                $('#form_senha_usuario').attr('placeholder', '(Não alterada)');

                $('#modalUsuario').removeClass('hidden');
            };

            window.deleteUsuario = function (id) {
                if (confirm('Tem certeza que deseja excluir este usuário?')) {
                    $.post('app.php', { action: 'excluir_usuario', id_usuario: id }, function (res) {
                        if (res.success) {
                            loadUsuarios(); // Reload list
                        } else {
                            alert(res.message);
                        }
                    }, 'json');
                }
            };

            $('#formUsuario').on('submit', function (e) {
                e.preventDefault();
                $.post('app.php', $(this).serialize(), function (res) {
                    if (res.success) {
                        alert(res.message);
                        closeUsuarioModal();
                        loadUsuarios();
                    } else {
                        alert(res.message);
                    }
                }, 'json');
            });

            // Desvincular e-mail do Gmail
            window.desvincularGmail = function () {
                if (confirm('Deseja realmente desvincular esta conta de envio do Gmail? O envio de faturas por e-mail ficará desabilitado.')) {
                    $.post('app.php', { action: 'desvincular_gmail' }, function (res) {
                        alert(res.message);
                        if (res.success) {
                            window.location.href = 'config_fiscal.php?tab=integracoes';
                        }
                    }, 'json');
                }
            };

            // --- CONTADEV INTEGRATION JS ---
            window.loadContaDevStatus = function() {
                $.post('app.php', { action: 'contadev_status' }, function(res) {
                    if (res.success && res.data && res.data.active) {
                        $('#contadev_form_container').addClass('hidden');
                        $('#contadev_status_container').removeClass('hidden');
                        $('#contadev_status_email').text(res.data.email || '-');
                        $('#contadev_status_user').text(res.data.user_name || '-');
                        $('#contadev_status_company').text(res.data.company_name || '-');
                        $('#contadev_status_cnpj_id').text(res.data.cnpj_id || '-');
                    } else {
                        $('#contadev_form_container').removeClass('hidden');
                        $('#contadev_status_container').addClass('hidden');
                    }
                }, 'json');
            };

            window.loginContaDev = function() {
                const email = $('#contadev_email_input').val().trim();
                const password = $('#contadev_password_input').val().trim();

                if (!email || !password) {
                    alert('Por favor, preencha o e-mail e a senha do ContaDev.');
                    return;
                }

                const btn = $('#btn_login_contadev');
                btn.prop('disabled', true).addClass('opacity-75');

                $.post('app.php', { action: 'contadev_login', email: email, password: password }, function(res) {
                    btn.prop('disabled', false).removeClass('opacity-75');
                    if (res.success) {
                        alert(res.message);
                        loadContaDevStatus();
                    } else {
                        alert(res.message || 'Erro ao conectar com o ContaDev.');
                    }
                }, 'json').fail(function() {
                    btn.prop('disabled', false).removeClass('opacity-75');
                    alert('Erro de conexão com o servidor.');
                });
            };

            window.desconectarContaDev = function() {
                if (confirm('Deseja realmente desconectar a integração com o ContaDev-Contabilidade?')) {
                    $.post('app.php', { action: 'contadev_disconnect' }, function(res) {
                        alert(res.message);
                        loadContaDevStatus();
                    }, 'json');
                }
            };

            window.sincronizarRpsIss = function() {
                const btn = $('#btnSincronizarRps');
                const origHtml = btn.html();
                const statusSpan = $('#sync_rps_status');
                
                btn.prop('disabled', true).addClass('opacity-75');
                btn.html('<span class="material-icons animate-spin text-sm">sync</span> Consultando ISS DF...');
                statusSpan.text('Buscando último RPS na prefeitura...');

                $.post('app.php', { action: 'sincronizar_rps_iss' }, function(res) {
                    btn.prop('disabled', false).removeClass('opacity-75').html(origHtml);
                    if (res.success) {
                        if (res.data && res.data.ultimo_rps_producao !== undefined) {
                            $('#ultimo_rps_producao').val(res.data.ultimo_rps_producao);
                        }
                        if (res.data && res.data.ultimo_rps_homologacao !== undefined) {
                            $('#ultimo_rps_homologacao').val(res.data.ultimo_rps_homologacao);
                        }
                        statusSpan.html('<span class="text-green-600 font-bold">✓ Sincronizado!</span> RPS ' + res.data.ultimo_rps_producao);
                        alert(res.message);
                    } else {
                        statusSpan.html('<span class="text-red-500 font-bold">Falha ao sincronizar</span>');
                        alert('Erro: ' + (res.message || 'Falha ao sincronizar com ISS DF'));
                    }
                }, 'json').fail(function() {
                    btn.prop('disabled', false).removeClass('opacity-75').html(origHtml);
                    statusSpan.text('Erro de comunicação.');
                    alert('Erro de comunicação com o servidor.');
                });
            };

            loadContaDevStatus();

            // Exibir alertas e mudar de aba de acordo com retorno do OAuth
            const urlParams = new URLSearchParams(window.location.search);
            const successMsg = urlParams.get('success');
            const errorMsg = urlParams.get('error');
            const activeTab = urlParams.get('tab');

            if (successMsg) {
                alert(successMsg);
            }
            if (errorMsg) {
                alert(errorMsg);
            }
            if (activeTab) {
                switchTab(activeTab);
            }

            // Executar Migrações
            $('#btnRunMigrations').click(function () {
                const btn = $(this);
                const icon = btn.find('.material-icons');
                const logs = $('#migrationLogs');
                const container = $('#migrationLogsContainer');

                // UI State
                btn.prop('disabled', true).addClass('opacity-75 cursor-wait');
                icon.text('settings_backup_restore').addClass('animate-spin');

                container.removeClass('hidden');
                logs.text('Iniciando verificação de migrações...\n');

                $.post('app.php', { action: 'run_migrations' }, function (res) {
                    if (res.logs && res.logs.length > 0) {
                        res.logs.forEach(function (line) {
                            logs.append(line + '\n');
                        });
                    }

                    if (res.success) {
                        logs.append('\n[SUCESSO] Processo finalizado corretamente.');
                    } else {
                        logs.append('\n[ERRO] Ocorreu um problema durante a migração.');
                    }

                    // Scroll to bottom
                    logs.scrollTop(logs[0].scrollHeight);

                }, 'json')
                    .fail(function () {
                        logs.append('\n[ERRO FATAL] Falha de comunicação com o servidor.');
                    })
                    .always(function () {
                        btn.prop('disabled', false).removeClass('opacity-75 cursor-wait');
                        icon.text('play_arrow').removeClass('animate-spin');
                    });
            });
        });
    </script>
</body>

</html>