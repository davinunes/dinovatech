<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../../login.php");
    exit();
}
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../helpers/AppHelper.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../../login.php");
    exit();
}
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../helpers/AppHelper.php';
include __DIR__ . '/../../../database.php';

$link = DBConnect();
$q_conf = "SELECT logo_url FROM ConfiguracoesEmissor LIMIT 1";
$r_conf = DBExecute($link, $q_conf);
$logo_url_val = '';
if ($r_conf && mysqli_num_rows($r_conf) > 0) {
    $row = mysqli_fetch_assoc($r_conf);
    $logo_url_val = $row['logo_url'];
    // Make it absolute if needed, or relative to root
    if ($logo_url_val && strpos($logo_url_val, 'http') !== 0) {
        $logo_url_val = '../../' . $logo_url_val;
    }
}
DBClose($link);

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <title>Modelos de Documentos - Dinovatech</title>
    <?php include '../../components/layout_head.php'; ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.2/tinymce.min.js" referrerpolicy="origin"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .variable-tag {
            cursor: pointer;
            background-color: #e0f2fe;
            color: #0369a1;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.85rem;
            font-weight: 600;
            border: 1px solid #bae6fd;
            display: inline-block;
            margin: 2px;
            transition: all 0.2s;
        }

        .variable-tag:hover {
            background-color: #0284c7;
            color: white;
            border-color: #0284c7;
        }

        .var-group-title {
            font-size: 0.75rem;
            text-transform: uppercase;
            color: #64748b;
            font-weight: 700;
            margin-top: 8px;
            margin-bottom: 4px;
            display: block;
        }
    </style>
</head>

<body class="bg-gray-50 flex">
    <?php include '../../components/sidebar.php'; ?>

    <div class="flex-1 flex flex-col lg:ml-64 min-h-screen transition-all duration-300">
        <main class="flex-1 p-6 mt-16 lg:mt-0">

            <div class="flex flex-col md:flex-row items-center justify-between mb-6">
                <div>
                    <h2 class="text-3xl font-bold text-gray-800">Modelos de Documentos</h2>
                    <p class="text-gray-500">Crie modelos para contratos, atestados e termos.</p>
                </div>
                <button onclick="novoModelo()"
                    class="bg-cyan-600 hover:bg-cyan-700 text-white font-bold py-2 px-6 rounded-lg shadow flex items-center mt-4 md:mt-0">
                    <span class="material-icons mr-2">add</span> Novo Modelo
                </button>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Título</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Tipo</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Ações</th>
                        </tr>
                    </thead>
                    <tbody id="lista-modelos" class="bg-white divide-y divide-gray-200">
                        <!-- Loaded via AJAX -->
                    </tbody>
                </table>
            </div>

        </main>
    </div>

    <!-- MODAL EDITOR -->
    <div id="modal-modelo" class="fixed inset-0 z-50 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog"
        aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"
                onclick="fecharModal()"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div
                class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-5xl sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-xl font-bold text-gray-900" id="modal-title">Editar Modelo</h3>
                        <button onclick="fecharModal()" class="text-gray-400 hover:text-gray-500"><span
                                class="material-icons">close</span></button>
                    </div>

                    <form id="form-modelo" onsubmit="salvarModelo(event)">
                        <input type="hidden" name="id" id="modelo-id">

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Título do Documento</label>
                                <input type="text" name="titulo" id="modelo-titulo" required
                                    class="w-full border-gray-300 rounded-md shadow-sm p-2 border"
                                    placeholder="Ex: Contrato de Prestação de Serviços">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tipo / Categoria</label>
                                <select name="tipo" id="modelo-tipo"
                                    class="w-full border-gray-300 rounded-md shadow-sm p-2 border bg-white">
                                    <option value="Geral">Geral</option>
                                    <option value="Contrato">Contrato</option>
                                    <?php if (AppHelper::isVetMode()): ?>
                                        <option value="Cirurgia">Cirurgia</option>
                                        <option value="Internacao">Internação</option>
                                        <option value="Atestado">Atestado</option>
                                        <option value="Encaminhamento">Encaminhamento</option>
                                        <option value="Exames">Solicitação de Exames</option>
                                        <option value="Eutanasia">Eutanásia</option>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Variables Helper -->
                        <div class="mb-4 bg-gray-50 p-3 rounded-lg border border-gray-200 text-sm">
                            <div class="flex justify-between items-center cursor-pointer select-none"
                                onclick="toggleVariaveis()">
                                <span class="font-bold text-gray-700">Variáveis Disponíveis</span>
                                <span id="icon-toggle-vars" class="material-icons text-gray-500">expand_less</span>
                            </div>

                            <div id="lista-variaveis-container" class="mt-2 transition-all duration-300">
                                <div class="flex flex-wrap gap-1 mb-2">
                                    <span
                                        class="variable-tag bg-pink-100 text-pink-700 border-pink-200 hover:bg-pink-200"
                                        onclick="inserirVariavel('{{TEXTO_PERSONALIZADO}}')">Texto Personalizado (Editor
                                        no
                                        Uso)</span>
                                </div>

                                <div class="flex flex-wrap gap-1">
                                    <!-- Common -->
                                    <span class="variable-tag" onclick="inserirImagemLogo()">Logo (Imagem)</span>
                                    <span class="variable-tag" onclick="inserirVariavel('{{DATA_ATUAL}}')">Data
                                        Atual</span>
                                    <span class="variable-tag" onclick="inserirVariavel('{{CIDADE_DATA}}')">Cidade,
                                        Data</span>
                                    <span class="variable-tag" onclick="inserirVariavel('{{EMPRESA_NOME}}')">Empresa
                                        Nome</span>
                                    <span class="variable-tag" onclick="inserirVariavel('{{EMPRESA_CNPJ}}')">Empresa
                                        CNPJ</span>
                                    <span class="variable-tag" onclick="inserirVariavel('{{NOME_FANTASIA}}')">Nome
                                        Fantasia</span>
                                    <span class="variable-tag"
                                        onclick="inserirVariavel('{{EMPRESA_ENDERECO}}')">Endereço
                                        Empresa</span>
                                    <span class="variable-tag" onclick="inserirVariavel('{{EMPRESA_CIDADE}}')">Cidade
                                        Empresa</span>
                                    <span class="variable-tag" onclick="inserirVariavel('{{EMPRESA_TELEFONE}}')">Tel.
                                        Empresa</span>
                                    <span class="variable-tag" onclick="inserirVariavel('{{EMPRESA_IE}}')">Insc.
                                        Estadual</span>
                                    <span class="variable-tag" onclick="inserirVariavel('{{EMPRESA_IM}}')">Insc.
                                        Municipal</span>

                                    <span class="var-group-title">Cliente / Tutor</span>
                                    <div class="flex flex-wrap gap-1">
                                        <span class="variable-tag" onclick="inserirVariavel('{{NOME_CLIENTE}}')">Nome
                                            Cliente</span>
                                        <span class="variable-tag"
                                            onclick="inserirVariavel('{{CPF_CNPJ_CLIENTE}}')">CPF/CNPJ</span>
                                        <span class="variable-tag"
                                            onclick="inserirVariavel('{{ENDERECO_CLIENTE}}')">Endereço</span>
                                        <span class="variable-tag"
                                            onclick="inserirVariavel('{{EMAIL_CLIENTE}}')">Email</span>
                                        <span class="variable-tag"
                                            onclick="inserirVariavel('{{TELEFONE_CLIENTE}}')">Telefone</span>
                                    </div>

                                    <span class="var-group-title">Contrato / Recorrência</span>
                                    <div class="flex flex-wrap gap-1">
                                        <span class="variable-tag"
                                            onclick="inserirVariavel('{{SERVICO_NOME}}')">Serviço</span>
                                        <span class="variable-tag"
                                            onclick="inserirVariavel('{{VALOR_CONTRATO}}')">Valor</span>
                                        <span class="variable-tag" onclick="inserirVariavel('{{DATA_INICIO}}')">Data
                                            Início</span>
                                        <span class="variable-tag" onclick="inserirVariavel('{{DIA_VENCIMENTO}}')">Dia
                                            Venc.</span>
                                        <span class="variable-tag"
                                            onclick="inserirVariavel('{{DESCRICAO_FISCAL}}')">Desc.
                                            Fiscal</span>
                                        <span class="variable-tag" onclick="inserirVariavel('{{ISS_RETIDO}}')">ISS
                                            Retido?</span>
                                    </div>

                                    <?php if (AppHelper::isVetMode()): ?>
                                        <span class="var-group-title">Vet / Pet</span>
                                        <div class="flex flex-wrap gap-1">
                                            <span class="variable-tag" onclick="inserirVariavel('{{NOME_PET}}')">Nome
                                                Pet</span>
                                            <span class="variable-tag"
                                                onclick="inserirVariavel('{{ESPECIE_PET}}')">Espécie</span>
                                            <span class="variable-tag" onclick="inserirVariavel('{{RACA_PET}}')">Raça</span>
                                            <span class="variable-tag"
                                                onclick="inserirVariavel('{{IDADE_PET}}')">Idade</span>
                                            <span class="variable-tag" onclick="inserirVariavel('{{SEXO_PET}}')">Sexo</span>
                                            <span class="variable-tag" onclick="inserirVariavel('{{PESO_PET}}')">Peso</span>
                                            <span class="variable-tag" onclick="inserirVariavel('{{NOME_VET}}')">Nome
                                                Vet</span>
                                            <span class="variable-tag" onclick="inserirVariavel('{{CRMV_VET}}')">CRMV
                                                Vet</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Editor -->
                        <div class="mb-4">
                            <textarea id="editor-conteudo" name="conteudo"></textarea>
                        </div>

                        <div class="flex justify-end pt-4 border-t">
                            <button type="button" onclick="fecharModal()"
                                class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 mr-3">Cancelar</button>
                            <button type="submit"
                                class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-cyan-600 hover:bg-cyan-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-cyan-500">Salvar
                                Modelo</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        const BASE_URL = "../../app.php";
        const LOGO_URL_VAL = "<?= $logo_url_val ?>";

        // Init TinyMCE
        // Init TinyMCE
        tinymce.init({
            selector: '#editor-conteudo',
            height: 400,
            menubar: true, // Enable menubar
            plugins: [
                'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
                'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
                'insertdatetime', 'media', 'table', 'help', 'wordcount'
            ],
            toolbar: 'undo redo | blocks fontfamily fontsize | ' +
                'bold italic backcolor forecolor | lineheight | alignleft aligncenter ' +
                'alignright alignjustify | bullist numlist outdent indent | ' +
                'table hr | code removeformat | help',
            content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:14px } ',
            font_size_formats: '8pt 10pt 12pt 14pt 16pt 18pt 24pt 36pt 48pt',
        });

        function inserirVariavel(tag) {
            tinymce.get('editor-conteudo').insertContent(tag);
        }

        function inserirImagemLogo() {
            if (!LOGO_URL_VAL) {
                alert('Configure o logotipo da empresa nas Configurações Fiscais primeiro.');
                return;
            }
            // Insert real image
            let html = `<img src="${LOGO_URL_VAL}" alt="Logo" style="max-width:200px; height:auto;" />`;
            tinymce.get('editor-conteudo').insertContent(html);
        }

        function toggleVariaveis() {
            let container = $('#lista-variaveis-container');
            let icon = $('#icon-toggle-vars');
            container.toggleClass('hidden');
            if (container.hasClass('hidden')) {
                icon.text('expand_more');
            } else {
                icon.text('expand_less');
            }
        }

        $(document).ready(function () {
            carregarModelos();
        });

        function carregarModelos() {
            $.post(BASE_URL, { action: 'get_modelos_documentos' }, function (res) {
                if (res.success) {
                    let html = '';
                    if (res.data.length === 0) {
                        html = '<tr><td colspan="3" class="px-6 py-4 text-center text-gray-500">Nenhum modelo cadastrado.</td></tr>';
                    } else {
                        res.data.forEach(m => {
                            html += `
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${m.titulo}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${m.tipo}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <button onclick="editarModelo(${m.id_modelo})" class="text-indigo-600 hover:text-indigo-900 mr-3">Editar</button>
                                        <button onclick="excluirModelo(${m.id_modelo})" class="text-red-600 hover:text-red-900">Excluir</button>
                                    </td>
                                </tr>
                            `;
                        });
                    }
                    $('#lista-modelos').html(html);
                }
            }, 'json');
        }

        function novoModelo() {
            $('#modelo-id').val('');
            $('#modelo-titulo').val('');
            $('#modelo-tipo').val('Geral');
            tinymce.get('editor-conteudo').setContent('');
            $('#modal-modelo').removeClass('hidden');
        }

        function fecharModal() {
            $('#modal-modelo').addClass('hidden');
        }

        function editarModelo(id) {
            $.post(BASE_URL, { action: 'get_modelo_detalhes', id: id }, function (res) {
                if (res.success) {
                    $('#modelo-id').val(res.data.id_modelo);
                    $('#modelo-titulo').val(res.data.titulo);
                    $('#modelo-tipo').val(res.data.tipo);

                    let content = res.data.conteudo || '';
                    if (LOGO_URL_VAL) {
                        // Replace variable with real URL for editing
                        content = content.replace(/{{LOGO_URL}}/g, LOGO_URL_VAL);
                    }
                    tinymce.get('editor-conteudo').setContent(content);
                    $('#modal-modelo').removeClass('hidden');
                } else {
                    alert(res.message);
                }
            }, 'json');
        }

        function salvarModelo(e) {
            e.preventDefault();
            let content = tinymce.get('editor-conteudo').getContent();

            if (LOGO_URL_VAL) {
                // Replace real URL back to variable
                // Use a regex to catch cases where user might have resized or modified attributes, but the src is the key
                // It is safer to replace the strict src string
                content = content.split(LOGO_URL_VAL).join('{{LOGO_URL}}');
            }

            const data = {
                action: 'salvar_modelo_documento',
                id: $('#modelo-id').val(),
                titulo: $('#modelo-titulo').val(),
                tipo: $('#modelo-tipo').val(),
                conteudo: content
            };

            $.post(BASE_URL, data, function (res) {
                if (res.success) {
                    alert('Salvo com sucesso!');
                    fecharModal();
                    carregarModelos();
                } else {
                    alert('Erro: ' + res.message);
                }
            }, 'json');
        }

        function excluirModelo(id) {
            if (!confirm('Deseja realmente excluir este modelo?')) return;
            $.post(BASE_URL, { action: 'excluir_modelo_documento', id: id }, function (res) {
                if (res.success) {
                    carregarModelos();
                } else {
                    alert('Erro: ' + res.message);
                }
            }, 'json');
        }
    </script>
</body>

</html>