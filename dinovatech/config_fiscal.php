<?php
// config_fiscal.php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}
// Carrega dados iniciais via PHP para preencher o form, ou faz via AJAX no load.
// Como app.php é JSON, melhor fazer via AJAX no load para consistência.
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <title>Configuração Fiscal (NFS-e) - Dinovatech</title>
    <?php include 'components/layout_head.php'; ?>
</head>

<body class="bg-gray-50 flex">

    <?php include 'components/sidebar.php'; ?>

    <div class="flex-1 flex flex-col lg:ml-64 min-h-screen transition-all duration-300">
        <main class="flex-1 p-6 mt-16 lg:mt-0">

            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                <div>
                    <h2 class="text-3xl font-bold text-gray-800">Configuração Fiscal (NFS-e)</h2>
                    <p class="text-gray-500">Dados da empresa emissora e certificado digital.</p>
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
                        </nav>
                    </div>

                    <!-- TAB: FISCAL -->
                    <div id="content-fiscal" class="tab-content active">
                        <h3 class="text-lg font-semibold text-gray-700 mb-4 flex items-center">
                            <span class="material-icons mr-2 text-cyan-600">business</span> Dados da Empresa e Emissão
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Razão Social</label>
                                <input type="text" name="razao_social" id="razao_social" required
                                    class="w-full rounded-lg border-gray-300 focus:border-cyan-500 focus:ring-cyan-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nome Fantasia</label>
                                <input type="text" name="nome_fantasia" id="nome_fantasia"
                                    class="w-full rounded-lg border-gray-300 focus:border-cyan-500 focus:ring-cyan-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">CNPJ</label>
                                <input type="text" name="cnpj" id="cnpj" required
                                    class="w-full rounded-lg border-gray-300 focus:border-cyan-500 focus:ring-cyan-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Inscrição Municipal</label>
                                <input type="text" name="inscricao_municipal" id="inscricao_municipal" required
                                    class="w-full rounded-lg border-gray-300 focus:border-cyan-500 focus:ring-cyan-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Inscrição Estadual
                                    (Opcional)</label>
                                <input type="text" name="inscricao_estadual" id="inscricao_estadual"
                                    class="w-full rounded-lg border-gray-300 focus:border-cyan-500 focus:ring-cyan-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Telefone</label>
                                <input type="text" name="telefone" id="telefone"
                                    class="w-full rounded-lg border-gray-300 focus:border-cyan-500 focus:ring-cyan-500">
                            </div>
                            <div class="md:col-span-2 border-t border-gray-100 pt-4 mt-2">
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
                                        <p class="text-xs text-gray-500 mt-1">Recomendado: 200x200px (PNG ou JPG). O
                                            arquivo será sobrescrito ao atualizar.</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Endereço -->
                            <div class="md:col-span-2 border-t pt-4 mt-2">
                                <h4 class="text-sm font-semibold text-gray-600 mb-3">Endereço (Obrigatório para NFSe)
                                </h4>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Logradouro</label>
                                        <input type="text" name="endereco" id="endereco" required
                                            class="w-full rounded-lg border-gray-300 focus:border-cyan-500 focus:ring-cyan-500">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Número</label>
                                        <input type="text" name="numero" id="numero" required
                                            class="w-full rounded-lg border-gray-300 focus:border-cyan-500 focus:ring-cyan-500">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Complemento</label>
                                        <input type="text" name="complemento" id="complemento"
                                            class="w-full rounded-lg border-gray-300 focus:border-cyan-500 focus:ring-cyan-500">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Bairro</label>
                                        <input type="text" name="bairro" id="bairro" required
                                            class="w-full rounded-lg border-gray-300 focus:border-cyan-500 focus:ring-cyan-500">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">CEP</label>
                                        <input type="text" name="cep" id="cep" required
                                            class="w-full rounded-lg border-gray-300 focus:border-cyan-500 focus:ring-cyan-500">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">UF</label>
                                        <input type="text" name="uf" id="uf" required maxlength="2"
                                            class="w-full rounded-lg border-gray-300 focus:border-cyan-500 focus:ring-cyan-500 uppercase">
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Cód. Município
                                    (IBGE)</label>
                                <input type="text" name="codigo_municipio" id="codigo_municipio" required
                                    value="5300108"
                                    class="w-full rounded-lg border-gray-300 focus:border-cyan-500 focus:ring-cyan-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Regime Tributário</label>
                                <select name="regime_tributario" id="regime_tributario"
                                    class="w-full rounded-lg border-gray-300 focus:border-cyan-500 focus:ring-cyan-500">
                                    <option value="simples">Simples Nacional</option>
                                    <option value="lucro_presumido">Lucro Presumido</option>
                                    <option value="lucro_real">Lucro Real</option>
                                </select>
                            </div>
                            <div class="flex items-center pt-6">
                                <input type="checkbox" name="optante_simples" id="optante_simples" value="1"
                                    class="h-4 w-4 text-cyan-600 focus:ring-cyan-500 border-gray-300 rounded">
                                <label for="optante_simples" class="ml-2 block text-sm text-gray-900">
                                    Optante pelo Simples Nacional
                                </label>
                            </div>
                        </div>

                        <div class="border-t pt-4 mt-6">
                            <h3 class="text-lg font-semibold text-gray-700 mb-4 flex items-center">
                                <span class="material-icons mr-2 text-cyan-600">settings_remote</span> Parâmetros NFS-e
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Ambiente Padrão</label>
                                    <select name="ambiente_padrao" id="ambiente_padrao"
                                        class="w-full rounded-lg border-gray-300 focus:border-cyan-500 focus:ring-cyan-500 bg-gray-50">
                                        <option value="homologacao">Homologação (Teste)</option>
                                        <option value="producao">Produção (Valendo)</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Série RPS</label>
                                    <input type="text" name="serie_rps" id="serie_rps" value="8"
                                        class="w-full rounded-lg border-gray-300 focus:border-cyan-500 focus:ring-cyan-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Último RPS
                                        (Homologação)</label>
                                    <input type="number" name="ultimo_rps_homologacao" id="ultimo_rps_homologacao"
                                        value="0"
                                        class="w-full rounded-lg border-gray-300 focus:border-cyan-500 focus:ring-cyan-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Último RPS
                                        (Produção)</label>
                                    <input type="number" name="ultimo_rps_producao" id="ultimo_rps_producao" value="0"
                                        class="w-full rounded-lg border-gray-300 focus:border-cyan-500 focus:ring-cyan-500 font-semibold bg-gray-50 border-orange-200">
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
                    $('#serie_rps').val(d.serie_rps);
                    $('#ultimo_rps_homologacao').val(d.ultimo_rps_homologacao);
                    $('#ultimo_rps_producao').val(d.ultimo_rps_producao);
                    $('#caminho_certificado').val(d.caminho_certificado);

                    // New Fields (Integration)
                    if (d.api_inter_client_id) $('#api_inter_client_id').val(d.api_inter_client_id);
                    if (d.api_inter_chave_pix) $('#api_inter_chave_pix').val(d.api_inter_chave_pix);
                    if (d.api_inter_conta_corrente) $('#api_inter_conta_corrente').val(d.api_inter_conta_corrente);

                    if (d.api_inter_cert_path) {
                        $('#caminho_inter_crt_display').text(d.api_inter_cert_path);
                    }
                    if (d.api_inter_key_path) {
                        $('#caminho_inter_key_display').text(d.api_inter_key_path);
                    }
                    if (d.api_inter_ca_path) {
                        $('#caminho_inter_ca_display').text(d.api_inter_ca_path);
                    }

                    if (d.api_oracle_user) $('#api_oracle_user').val(d.api_oracle_user);
                    if (d.api_oracle_url) $('#api_oracle_url').val(d.api_oracle_url);

                    if (d.caminho_certificado) {
                        $('#caminho_certificado_display').text(d.caminho_certificado);
                        $('#caminho_certificado').val(d.caminho_certificado);

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
                    }

                    if (d.optante_simples == 1) {
                        $('#optante_simples').prop('checked', true);
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
                }
            }, 'json');

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
                            Swal.fire('Sucesso!', response.message, 'success').then(() => {
                                location.reload(); // Reload to update file display
                            });
                        } else {
                            Swal.fire('Erro!', response.message, 'error');
                        }
                    },
                    error: function () {
                        Swal.fire('Erro!', 'Falha na comunicação com o servidor.', 'error');
                    }
                });
            });
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