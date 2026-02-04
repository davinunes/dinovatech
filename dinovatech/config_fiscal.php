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
                        <p class="text-gray-500 text-sm mb-4">Esta senha será criptografada no banco de dados.</p>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Caminho do Arquivo
                                    (.pfx)</label>
                                <input type="text" name="caminho_certificado" id="caminho_certificado"
                                    placeholder="Ex: certificado/meu_arquivo.pfx"
                                    class="w-full rounded-lg border-gray-300 focus:border-cyan-500 focus:ring-cyan-500 font-mono text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Senha do Certificado</label>
                                <input type="password" name="senha_certificado" id="senha_certificado"
                                    placeholder="Preencha apenas se quiser alterar a senha atual"
                                    class="w-full rounded-lg border-gray-300 focus:border-cyan-500 focus:ring-cyan-500">
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
                            <div class="flex items-center mb-4">
                                <img src="https://upload.wikimedia.org/wikipedia/commons/f/fa/Banco_Inter_logo.svg"
                                    alt="Inter" class="h-6 mr-3">
                                <h4 class="font-bold text-gray-800">Banco Inter (API Cobrança)</h4>
                            </div>
                            <div class="grid grid-cols-1 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Client ID</label>
                                    <input type="text" name="api_inter_client_id" id="api_inter_client_id"
                                        class="w-full rounded-lg border-gray-300 focus:border-cyan-500 focus:ring-cyan-500 font-mono text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Client Secret</label>
                                    <input type="password" name="api_inter_client_secret" id="api_inter_client_secret"
                                        placeholder="Preencha apenas para alterar"
                                        class="w-full rounded-lg border-gray-300 focus:border-cyan-500 focus:ring-cyan-500">
                                </div>
                            </div>
                        </div>

                        <!-- Oracle -->
                        <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                            <div class="flex items-center mb-4">
                                <span class="material-icons text-red-600 mr-2">cloud</span>
                                <h4 class="font-bold text-gray-800">Oracle OCI (Object Storage)</h4>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Usuário / Key ID</label>
                                    <input type="text" name="api_oracle_user" id="api_oracle_user"
                                        class="w-full rounded-lg border-gray-300 focus:border-cyan-500 focus:ring-cyan-500 font-mono text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Chave Secreta
                                        (Password)</label>
                                    <input type="password" name="api_oracle_password" id="api_oracle_password"
                                        placeholder="Preencha apenas para alterar"
                                        class="w-full rounded-lg border-gray-300 focus:border-cyan-500 focus:ring-cyan-500">
                                </div>
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
                    $('#codigo_municipio').val(d.codigo_municipio);

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
                    if (d.api_oracle_user) $('#api_oracle_user').val(d.api_oracle_user);

                    if (d.optante_simples == 1) {
                        $('#optante_simples').prop('checked', true);
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
                $.post('app.php', $(this).serialize(), function (response) {
                    if (response.success) {
                        Swal.fire('Sucesso!', response.message, 'success');
                    } else {
                        Swal.fire('Erro!', response.message, 'error');
                    }
                }, 'json').fail(function () {
                    Swal.fire('Erro!', 'Falha na comunicação com o servidor.', 'error');
                });
            });
        });
    </script>
</body>

</html>