<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IRR Manager - TC Registry</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        .glass { background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(10px); }
        body { background: #f0f4f8; font-family: 'Inter', sans-serif; }
        .tab-active { border-b-2 border-indigo-600 text-indigo-600; }
        .tooltip { position: relative; display: inline-block; cursor: help; }
        .tooltip .tooltiptext { visibility: hidden; width: 220px; background-color: #333; color: #fff; text-align: center; border-radius: 6px; padding: 8px; position: absolute; z-index: 10; bottom: 125%; left: 50%; margin-left: -110px; opacity: 0; transition: opacity 0.3s; font-size: 0.75rem; }
        .tooltip:hover .tooltiptext { visibility: visible; opacity: 1; }
    </style>
</head>
<body class="text-gray-800">

<div class="min-h-screen flex flex-col">
    <!-- Header -->
    <header class="bg-indigo-700 text-white p-6 shadow-lg">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-2xl font-bold flex items-center">
                <span class="material-icons mr-2">dns</span> IRR Manager <span class="ml-2 font-normal text-sm opacity-75">v1.0 (TC Registry)</span>
            </h1>
            <div class="text-sm">
                Provedor TC: <span class="font-mono">irr.tc.br</span>
            </div>
        </div>
    </header>

    <main class="flex-grow container mx-auto py-8 px-4">
        <!-- Dashboard / ASN List -->
        <div id="view-dashboard" class="space-y-6">
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-semibold">Configuração de ASNs</h2>
                <button onclick="showAddAsnModal()" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg flex items-center transition">
                    <span class="material-icons mr-1">add</span> Adicionar Novo ASN
                </button>
            </div>
            
            <div id="asn-list" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- ASNs will be listed here -->
            </div>
        </div>

        <!-- ASN Detail View -->
        <div id="view-asn-detail" class="hidden space-y-6">
            <div class="flex items-center space-x-4">
                <button onclick="showDashboard()" class="text-indigo-600 hover:underline flex items-center">
                    <span class="material-icons">arrow_back</span> Dashboard
                </button>
                <h2 id="current-asn-title" class="text-2xl font-bold">ASN Detail</h2>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-md space-y-4">
                <div class="flex flex-wrap gap-4 items-center">
                    <button id="btn-sync-whois" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg flex items-center">
                        <span class="material-icons mr-1">sync</span> Sincronizar via WHOIS
                    </button>
                    <button onclick="editAsnConfig()" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg flex items-center border">
                        <span class="material-icons mr-1">settings</span> Editar Configurações
                    </button>
                    <div class="tooltip ml-auto">
                        <span class="material-icons text-gray-400">help_outline</span>
                        <span class="tooltiptext">Sincroniza objetos RPSL (aut-num, route, etc) diretamente do servidor WHOIS rr.tc.br</span>
                    </div>
                </div>
                
                <div class="mt-6">
                    <h3 class="font-bold text-lg mb-4">Objetos JSON no Arquivo</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left bg-gray-50 rounded-lg">
                            <thead class="bg-gray-200">
                                <tr>
                                    <th class="p-3">Objeto</th>
                                    <th class="p-3">Tipo</th>
                                    <th class="p-3">Status</th>
                                    <th class="p-3">Ações</th>
                                </tr>
                            </thead>
                            <tbody id="object-list">
                                <!-- Objects will be listed here -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="p-6 text-center text-gray-500 text-sm">
        &copy; 2024 Dinovatech - IRR Manager (PHP 7.4)
    </footer>
</div>

<!-- Modal: Add/Edit ASN -->
<div id="modal-asn" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
    <div class="bg-white p-8 rounded-xl w-full max-w-md shadow-2xl">
        <h3 class="text-xl font-bold mb-4">Configurar ASN</h3>
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Número do ASN (ex: AS265138)</label>
                <input type="text" id="input-asn" class="mt-1 block w-full border border-gray-300 rounded-md p-2 shadow-sm focus:ring-indigo-500 focus:border-indigo-500" placeholder="ASXXXXX">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Nome da Organização</label>
                <input type="text" id="input-asn-name" class="mt-1 block w-full border border-gray-300 rounded-md p-2 shadow-sm" placeholder="Nome do Provedor">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Mantenedor (mnt-by) (ex: MAINT-AS265138)</label>
                <input type="text" id="input-mnt-by" class="mt-1 block w-full border border-gray-300 rounded-md p-2 shadow-sm" placeholder="MAINT-ASXXXXX">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Senha do Mantner (mnt-by)</label>
                <input type="password" id="input-mntner-pwd" class="mt-1 block w-full border border-gray-300 rounded-md p-2 shadow-sm" placeholder="Sua senha secreta">
            </div>
            <div class="flex justify-end space-x-2 pt-4">
                <button onclick="closeModal('modal-asn')" class="px-4 py-2 border border-gray-300 rounded-lg">Cancelar</button>
                <button id="btn-save-asn" class="px-4 py-2 bg-indigo-600 text-white rounded-lg">Salvar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Edit Object -->
<div id="modal-object" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
    <div class="bg-white p-8 rounded-xl w-full max-w-2xl shadow-2xl max-h-[90vh] overflow-y-auto">
        <h3 id="modal-object-title" class="text-xl font-bold mb-4">Editar Objeto</h3>
        <div class="space-y-4">
            <div id="object-attributes" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Attributes will be rendered here -->
            </div>
            <div class="pt-4 border-t">
                <label class="block text-sm font-medium text-gray-700 mb-1">Parâmetros Opcionais (RPSL Extra)</label>
                <textarea id="object-raw" class="w-full h-32 border border-gray-300 rounded-md p-2 font-mono text-xs" placeholder="Atributo: Valor"></textarea>
            </div>
            <div class="flex justify-end space-x-2 pt-4">
                <button onclick="closeModal('modal-object')" class="px-4 py-2 border border-gray-300 rounded-lg">Cancelar</button>
                <button id="btn-save-object" class="px-4 py-2 bg-indigo-600 text-white rounded-lg">Salvar Localmente</button>
            </div>
        </div>
    </div>
</div>

<script>
    let currentAsn = null;
    let currentAsnData = null;

    $(document).ready(function() {
        loadAsns();
        
        $('#btn-save-asn').click(function() {
            const data = {
                asn: $('#input-asn').val().toUpperCase().trim(),
                asn_name: $('#input-asn-name').val(),
                mnt_by: $('#input-mnt-by').val().toUpperCase().trim(),
                mntner_password: $('#input-mntner-pwd').val()
            };
            
            if (!data.asn.startsWith('AS')) {
                alert('O ASN deve começar com AS (ex: AS265138)');
                return;
            }

            $.post('api.php?action=save_asn', JSON.stringify(data), function(res) {
                if (res.success) {
                    closeModal('modal-asn');
                    loadAsns();
                } else alert('Erro: ' + res.message);
            });
        });

        $('#btn-sync-whois').click(function() {
            if (!currentAsn) return;
            const btn = $(this);
            btn.prop('disabled', true).text('Sincronizando...');
            
            $.getJSON('api.php?action=sync_whois&asn=' + currentAsn, function(res) {
                btn.prop('disabled', false).html('<span class="material-icons mr-1">sync</span> Sincronizar via WHOIS');
                if (res.success) {
                    alert('Sincronização concluída! ' + (res.count || 0) + ' objetos encontrados.');
                    loadAsnDetail(currentAsn);
                } else {
                    alert('Erro na sincronização: ' + res.message);
                }
            });
        });
    });

    function loadAsns() {
        $.getJSON('api.php?action=list_asns', function(res) {
            const list = $('#asn-list');
            list.empty();
            if (res.data.length === 0) {
                list.html('<div class="col-span-full text-center py-10 bg-white rounded-xl">Nenhum ASN configurado.</div>');
                return;
            }
            res.data.forEach(asn => {
                list.append(`
                    <div class="bg-white p-6 rounded-xl shadow-md border hover:border-indigo-500 transition cursor-pointer" onclick="loadAsnDetail('${asn.asn}')">
                        <div class="flex justify-between items-start">
                            <h4 class="text-xl font-bold text-indigo-700">${asn.asn}</h4>
                            <span class="bg-indigo-100 text-indigo-700 text-xs px-2 py-1 rounded-full">${asn.object_count} objetos</span>
                        </div>
                        <p class="text-gray-600 text-sm mt-2">${asn.name || 'Sem nome'}</p>
                        <div class="mt-4 flex justify-end">
                            <span class="text-indigo-600 text-sm flex items-center">Gerenciar <span class="material-icons text-sm ml-1 text-sm">arrow_forward</span></span>
                        </div>
                    </div>
                `);
            });
        });
    }

    function loadAsnDetail(asn) {
        currentAsn = asn;
        $.getJSON('api.php?action=get_asn&asn=' + asn, function(data) {
            currentAsnData = data;
            $('#current-asn-title').text(asn + ' - ' + (data.asn_name || 'Detalhes'));
            const tbody = $('#object-list');
            tbody.empty();
            
            if (!data.objects || data.objects.length === 0) {
                tbody.append('<tr><td colspan="4" class="p-6 text-center text-gray-500">Nenhum objeto encontrado. Use "Sincronizar via WHOIS".</td></tr>');
            } else {
                data.objects.forEach((obj, index) => {
                    const statusClass = obj.status === 'sincronizado' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700';
                    tbody.append(`
                        <tr class="border-t hover:bg-white transition">
                            <td class="p-3 font-mono text-sm">${obj.name}</td>
                            <td class="p-3 text-sm">${obj.type}</td>
                            <td class="p-3">
                                <span class="text-xs px-2 py-1 rounded-full ${statusClass}">${obj.status}</span>
                            </td>
                            <td class="p-3 space-x-2">
                                <button onclick="editObject(${index})" class="text-indigo-600 hover:text-indigo-900 flex items-center inline-flex">
                                    <span class="material-icons text-sm mr-1">edit</span> Editar
                                </button>
                                <button onclick="sendToTc(${index})" class="text-green-600 hover:text-green-900 flex items-center inline-flex">
                                    <span class="material-icons text-sm mr-1">send</span> Enviar TC
                                </button>
                            </td>
                        </tr>
                    `);
                });
            }
            
            $('#view-dashboard').hide();
            $('#view-asn-detail').removeClass('hidden').show();
        });
    }

    function showDashboard() {
        $('#view-asn-detail').hide();
        $('#view-dashboard').show();
        loadAsns();
    }

    function showAddAsnModal() {
        $('#input-asn').val('').prop('disabled', false);
        $('#input-asn-name').val('');
        $('#input-mnt-by').val('');
        $('#input-mntner-pwd').val('');
        $('#modal-asn').removeClass('hidden').addClass('flex');
    }

    function editAsnConfig() {
        if (!currentAsnData) return;
        $('#input-asn').val(currentAsnData.asn).prop('disabled', true);
        $('#input-asn-name').val(currentAsnData.asn_name || '');
        $('#input-mnt-by').val(currentAsnData.mnt_by || '');
        $('#input-mntner-pwd').val(currentAsnData.mntner_password || '');
        $('#modal-asn').removeClass('hidden').addClass('flex');
    }

    function closeModal(id) {
        $('#' + id).addClass('hidden').removeClass('flex');
    }

    function editObject(index) {
        const obj = currentAsnData.objects[index];
        $('#modal-object-title').text(`Editar ${obj.type}: ${obj.name}`);
        const container = $('#object-attributes');
        container.empty();
        
        const mainAttrs = ['descr', 'origin', 'mnt-by', 'changed', 'person', 'email', 'phone'];
        const commonTooltips = {
            'origin': 'O ASN de origem para a rota. Ex: AS265138',
            'mnt-by': 'O objeto Maintainer que protege este registro.',
            'descr': 'Descrição curta do objeto.',
            'changed': 'E-mail e data da última alteração. Ex: davi@exa.com 20240101'
        };

        // Separate main and others
        const attrsMap = {};
        obj.attributes.forEach(a => attrsMap[a.name] = a.value);

        mainAttrs.forEach(name => {
            const val = attrsMap[name] || '';
            const tooltip = commonTooltips[name] ? `<div class="tooltip ml-1"><span class="material-icons" style="font-size:14px">help_outline</span><span class="tooltiptext">${commonTooltips[name]}</span></div>` : '';
            container.append(`
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase flex items-center">${name} ${tooltip}</label>
                    <input type="text" data-attr="${name}" value="${val}" class="mt-1 block w-full border border-gray-300 rounded-md p-2 text-sm">
                </div>
            `);
        });

        // Other attributes in the textarea
        let otherText = '';
        obj.attributes.forEach(a => {
            if (!mainAttrs.includes(a.name) && a.name !== obj.type) {
                otherText += `${a.name}: ${a.value}\n`;
            }
        });
        $('#object-raw').val(otherText);
        
        $('#btn-save-object').off('click').on('click', function() {
            saveObjectChanges(index);
        });

        $('#modal-object').removeClass('hidden').addClass('flex');
    }

    function saveObjectChanges(index) {
        const obj = currentAsnData.objects[index];
        const newAttributes = [];
        
        // Always include the type/name first
        newAttributes.push({name: obj.type, value: obj.name});

        // Read main inputs
        $('#object-attributes input').each(function() {
            const name = $(this).data('attr');
            const val = $(this).val().trim();
            if (val) newAttributes.push({name: name, value: val});
        });

        // Read additional text
        const extraLines = $('#object-raw').val().split('\n');
        extraLines.forEach(line => {
            if (line.includes(':')) {
                const parts = line.split(':');
                const name = parts[0].trim().toLowerCase();
                const val = parts.slice(1).join(':').trim();
                if (name && val) newAttributes.push({name: name, value: val});
            }
        });

        currentAsnData.objects[index].attributes = newAttributes;
        currentAsnData.objects[index].status = 'local'; // Mark as edited locally

        $.post('api.php?action=save_asn', JSON.stringify({
            asn: currentAsn,
            asn_name: currentAsnData.asn_name,
            mnt_by: currentAsnData.mnt_by,
            mntner_password: currentAsnData.mntner_password,
            objects: currentAsnData.objects
        }), function(res) {
            if (res.success) {
                closeModal('modal-object');
                loadAsnDetail(currentAsn);
            } else alert('Erro ao salvar');
        });
    }

    function sendToTc(index) {
        if (!confirm('Deseja realmente enviar este objeto para o Registro TC?')) return;
        
        $.ajax({
            url: 'api.php?action=submit_to_tc',
            type: 'POST',
            data: JSON.stringify({
                asn: currentAsn,
                index: index
            }),
            contentType: 'application/json',
            success: function(res) {
                if (res.success) {
                    alert('Enviado com sucesso! Verifique a resposta da API nos logs.');
                } else {
                    alert('Erro no envio:\n' + JSON.stringify(res.data || res.message));
                }
            },
            error: function() {
                alert('Erro de conexão com o proxy api.php');
            }
        });
    }
</script>

</body>
</html>
