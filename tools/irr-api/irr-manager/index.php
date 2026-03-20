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
        .tooltip { position: relative; display: inline-block; cursor: help; }
        .tooltip .tooltiptext { visibility: hidden; width: 220px; background-color: #333; color: #fff; text-align: center; border-radius: 6px; padding: 8px; position: absolute; z-index: 10; bottom: 125%; left: 50%; margin-left: -110px; opacity: 0; transition: opacity 0.3s; font-size: 0.75rem; }
        .tooltip:hover .tooltiptext { visibility: visible; opacity: 1; }
        .field-error { border-color: #ef4444 !important; background-color: #fef2f2; }
    </style>
</head>
<body class="text-gray-800">

<div class="min-h-screen flex flex-col">
    <!-- Header -->
    <header class="bg-indigo-700 text-white p-6 shadow-lg">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-2xl font-bold flex items-center">
                <span class="material-icons mr-2">dns</span> IRR Manager <span class="ml-2 font-normal text-sm opacity-75">v1.1 (TC Registry)</span>
            </h1>
            <div class="text-sm">
                Provedor TC: <span class="font-mono">bgp.net.br</span>
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
                        <span class="tooltiptext">Sincroniza objetos RPSL (aut-num, route, etc) diretamente do servidor bgp.net.br</span>
                    </div>
                </div>
                
                <div class="mt-6">
                    <h3 class="font-bold text-lg mb-4">Objetos Gerenciados</h3>
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
                <button onclick="closeModal('modal-asn')" class="px-4 py-2 border border-gray-300 rounded-lg transition hover:bg-gray-100">Cancelar</button>
                <button id="btn-save-asn" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">Salvar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Edit Object (DYNAMIC) -->
<div id="modal-object" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
    <div class="bg-white p-8 rounded-xl w-full max-w-3xl shadow-2xl max-h-[90vh] flex flex-col">
        <div class="flex justify-between items-center mb-4">
            <h3 id="modal-object-title" class="text-xl font-bold">Editar Objeto</h3>
            <button onclick="closeModal('modal-object')" class="text-gray-400 hover:text-gray-600">
                <span class="material-icons">close</span>
            </button>
        </div>
        
        <div id="object-form-container" class="space-y-6 overflow-y-auto flex-grow pr-2">
            <!-- Dynamic Form Fields -->
            <div id="form-fields-required" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Required fields here -->
            </div>
            
            <div class="border-t pt-4">
                <h4 class="text-sm font-bold text-gray-400 uppercase mb-3">Atributos Opcionais</h4>
                <div id="form-fields-optional" class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <!-- Optional fields injected here -->
                </div>
                
                <div class="flex items-center space-x-3">
                    <select id="select-add-optional" class="border border-gray-300 rounded-md p-2 text-sm">
                        <option value="">+ Adicionar Atributo Opcional</option>
                    </select>
                    <button id="btn-add-optional" class="bg-indigo-100 text-indigo-700 px-3 py-2 rounded-lg text-sm font-bold hover:bg-indigo-200 transition">Adicionar</button>
                </div>
            </div>
        </div>

        <div class="flex justify-end space-x-2 pt-6 border-t mt-4">
            <button onclick="closeModal('modal-object')" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-100 transition">Cancelar</button>
            <button id="btn-save-object" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">Salvar Localmente</button>
        </div>
    </div>
</div>

<script>
    let currentAsn = null;
    let currentAsnData = null;
    let globalContacts = [];

    const SCHEMAS = {
        'mntner': {
            required: ['mntner', 'descr', 'admin-c', 'upd-to', 'auth', 'mnt-by', 'referral-by', 'source'],
            optional: ['remarks', 'address', 'phone', 'e-mail']
        },
        'aut-num': {
            required: ['aut-num', 'as-name', 'descr', 'admin-c', 'tech-c', 'mnt-by', 'source'],
            optional: ['import', 'export', 'remarks', 'mnt-routes']
        },
        'route': {
            required: ['route', 'descr', 'origin', 'mnt-by', 'source'],
            optional: ['holes', 'member-of', 'remarks']
        },
        'route6': {
            required: ['route6', 'descr', 'origin', 'mnt-by', 'source'],
            optional: ['holes', 'member-of', 'remarks']
        },
        'person': {
            required: ['person', 'address', 'phone', 'e-mail', 'nic-hdl', 'mnt-by', 'source'],
            optional: ['fax-no', 'remarks']
        },
        'role': {
            required: ['role', 'address', 'phone', 'e-mail', 'nic-hdl', 'mnt-by', 'source'],
            optional: ['fax-no', 'remarks']
        }
    };

    const TOOLTIPS = {
        'admin-c': 'Contato administrativo do objeto (NIC-HDL).',
        'tech-c': 'Contato técnico do objeto (NIC-HDL).',
        'upd-to': 'E-mail para notificações de alteração.',
        'auth': 'Método de autenticação (ex: MD5-PW).',
        'referral-by': 'Quem indicou este mantenedor.',
        'import': 'Política de importação de rotas.',
        'export': 'Política de exportação de rotas.',
        'nic-hdl': 'Identificador único da pessoa/cargo.',
        'mnt-by': 'Mantenedor que protege este registro.',
        'descr': 'Descrição curta do objeto.',
        'origin': 'O ASN de origem para a rota. Ex: AS265138',
        'source': 'Fonte do registro (TC, RIPE, etc).',
        'mnt-routes': 'Mantenedor permitido criar rotas para este ASN.'
    };

    $(document).ready(function() {
        loadAsns();
        loadAllContacts();
        
        $('#btn-save-asn').click(handleSaveAsn);
        $('#btn-sync-whois').click(handleSyncWhois);
        $('#btn-add-optional').click(handleAddOptional);
    });

    function loadAllContacts() {
        $.getJSON('api.php?action=get_all_contacts', function(res) {
            if (res.success) globalContacts = res.data;
        });
    }

    function handleSaveAsn() {
        const data = {
            asn: $('#input-asn').val().toUpperCase().trim(),
            asn_name: $('#input-asn-name').val(),
            mnt_by: $('#input-mnt-by').val().toUpperCase().trim(),
            mntner_password: $('#input-mntner-pwd').val()
        };
        if (!data.asn.startsWith('AS')) return alert('O ASN deve começar com AS.');
        
        $.post('api.php?action=save_asn', JSON.stringify(data), function(res) {
            if (res.success) { closeModal('modal-asn'); loadAsns(); } 
            else alert('Erro: ' + res.message);
        });
    }

    function handleSyncWhois() {
        if (!currentAsn) return;
        const btn = $(this);
        btn.prop('disabled', true).text('Sincronizando...');
        $.getJSON('api.php?action=sync_whois&asn=' + currentAsn, function(res) {
            btn.prop('disabled', false).html('<span class="material-icons mr-1">sync</span> Sincronizar via WHOIS');
            if (res.success) {
                alert('Sincronização concluída! ' + (res.count || 0) + ' objetos encontrados.');
                loadAsnDetail(currentAsn);
                loadAllContacts();
            } else alert('Erro na sincronização: ' + res.message);
        });
    }

    function loadAsns() {
        $.getJSON('api.php?action=list_asns', function(res) {
            const list = $('#asn-list').empty();
            if (res.data.length === 0) return list.html('<div class="col-span-full py-10 bg-white rounded-xl text-center">Nenhum ASN.</div>');
            res.data.forEach(asn => {
                list.append(`
                    <div class="bg-white p-6 rounded-xl shadow-md border hover:border-indigo-500 transition cursor-pointer" onclick="loadAsnDetail('${asn.asn}')">
                        <div class="flex justify-between items-start">
                            <h4 class="text-xl font-bold text-indigo-700">${asn.asn}</h4>
                            <span class="bg-indigo-100 text-indigo-700 text-xs px-2 py-1 rounded-full">${asn.object_count}</span>
                        </div>
                        <p class="text-gray-600 text-sm mt-2">${asn.name || 'Provedor'}</p>
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
            const tbody = $('#object-list').empty();
            if (!data.objects || data.objects.length === 0) tbody.append('<tr><td colspan="4" class="p-6 text-center text-gray-500">Nenhum objeto.</td></tr>');
            else {
                data.objects.forEach((obj, index) => {
                    const statusClass = obj.status === 'sincronizado' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700';
                    tbody.append(`
                        <tr class="border-t hover:bg-white transition">
                            <td class="p-3 font-mono text-sm">${obj.name}</td>
                            <td class="p-3 text-sm">${obj.type}</td>
                            <td class="p-3"><span class="text-xs px-2 py-1 rounded-full ${statusClass}">${obj.status}</span></td>
                            <td class="p-3 space-x-2">
                                <button onclick="editObject(${index})" class="text-indigo-600 hover:text-indigo-900 border px-2 py-1 rounded">Editar</button>
                                <button onclick="sendToTc(${index})" class="text-green-600 hover:text-green-900 border px-2 py-1 rounded">Enviar TC</button>
                            </td>
                        </tr>
                    `);
                });
            }
            $('#view-dashboard').hide();
            $('#view-asn-detail').removeClass('hidden').show();
        });
    }

    function editObject(index) {
        const obj = currentAsnData.objects[index];
        const schema = SCHEMAS[obj.type] || { required: [], optional: [] };
        
        $('#modal-object-title').text(`Editar ${obj.type}: ${obj.name}`);
        $('#form-fields-required').empty();
        $('#form-fields-optional').empty();
        
        const dropdown = $('#select-add-optional').empty();
        dropdown.append('<option value="">+ Adicionar Atributo Opcional</option>');
        schema.optional.forEach(opt => dropdown.append(`<option value="${opt}">${opt}</option>`));

        const objAttrs = {};
        obj.attributes.forEach(a => {
            if (!objAttrs[a.name]) objAttrs[a.name] = [];
            objAttrs[a.name].push(a.value);
        });

        // Add required fields
        schema.required.forEach(name => {
            renderField(name, objAttrs[name] ? objAttrs[name][0] : '', '#form-fields-required', true);
        });

        // Add present optional fields
        Object.keys(objAttrs).forEach(name => {
            if (schema.optional.includes(name)) {
                objAttrs[name].forEach(val => renderField(name, val, '#form-fields-optional', false));
            }
        });

        $('#btn-save-object').off('click').on('click', () => saveObjectChanges(index));
        $('#modal-object').removeClass('hidden').addClass('flex');
    }

    function renderField(name, val, container, isRequired) {
        const tooltip = TOOLTIPS[name] ? `<div class="tooltip ml-1"><span class="material-icons" style="font-size:14px">help_outline</span><span class="tooltiptext">${TOOLTIPS[name]}</span></div>` : '';
        const inputId = `field-${Math.random().toString(36).substr(2, 9)}`;
        let fieldHtml = `
            <div class="field-wrapper" data-name="${name}">
                <label class="block text-xs font-bold text-gray-500 uppercase flex items-center">${name} ${isRequired ? '<span class="text-red-500 ml-1">*</span>' : ''} ${tooltip}</label>
        `;

        if (name === 'admin-c' || name === 'tech-c' || name === 'upd-to') {
            fieldHtml += `<div class="flex space-x-2 mt-1">
                <select id="${inputId}" class="flex-grow border border-gray-300 rounded-md p-2 text-sm">
                    <option value="">-- Selecione ou digite --</option>
                    ${globalContacts.map(c => `<option value="${c.nic}" ${c.nic === val ? 'selected' : ''}>${c.nic} (${c.name})</option>`).join('')}
                </select>
                <input type="text" placeholder="Novo NIC" onchange="$(this).prev().val(this.value)" class="w-1/3 border border-gray-300 rounded-md p-2 text-sm">
            </div>`;
        } else {
            fieldHtml += `<input type="text" id="${inputId}" value="${val}" class="mt-1 block w-full border border-gray-300 rounded-md p-2 text-sm shadow-sm">`;
        }

        if (!isRequired) {
            fieldHtml += `<button onclick="$(this).parent().remove()" class="text-red-500 text-xs mt-1 hover:underline">Remover atributo</button>`;
        }
        fieldHtml += `</div>`;
        $(container).append(fieldHtml);
    }

    function handleAddOptional() {
        const name = $('#select-add-optional').val();
        if (!name) return;
        renderField(name, '', '#form-fields-optional', false);
    }

    function saveObjectChanges(index) {
        const obj = currentAsnData.objects[index];
        const newAttributes = [];
        
        // Always include the object type: name
        // Not handled in schema traversal for simplicity, let's keep it
        newAttributes.push({ name: obj.type, value: obj.name });

        $('#modal-object .field-wrapper').each(function() {
            const name = $(this).data('name');
            const val = $(this).find('input, select').first().val().trim();
            if (val && name !== obj.type) newAttributes.push({ name: name, value: val });
        });

        currentAsnData.objects[index].attributes = newAttributes;
        currentAsnData.objects[index].status = 'local';

        $.post('api.php?action=save_asn', JSON.stringify({
            asn: currentAsn,
            asn_name: currentAsnData.asn_name,
            mnt_by: currentAsnData.mnt_by,
            mntner_password: currentAsnData.mntner_password,
            objects: currentAsnData.objects
        }), function(res) {
            if (res.success) { closeModal('modal-object'); loadAsnDetail(currentAsn); loadAllContacts(); } 
            else alert('Erro ao salvar');
        });
    }

    function sendToTc(index) {
        if (!confirm('Enviar para o Registro TC?')) return;
        const btn = event.target;
        btn.innerText = 'Enviando...';
        $.post('api.php?action=submit_to_tc', JSON.stringify({ asn: currentAsn, index: index }), function(res) {
            btn.innerText = 'Enviar TC';
            if (res.success) alert('Enviado com sucesso! Log gerado.');
            else {
                alert('Erro na submissão: Verifique os campos destacados.');
                if (res.data && res.data.errors) highlightErrors(res.data.errors);
            }
        });
    }

    function highlightErrors(errors) {
        // Simple highlight by field name
        errors.forEach(err => {
            $(`.field-wrapper[data-name="${err.attribute}"] input`).addClass('field-error');
        });
    }

    function showDashboard() { $('#view-asn-detail').hide(); $('#view-dashboard').show(); loadAsns(); }
    function showAddAsnModal() { $('#input-asn').val('').prop('disabled', false); $('#input-asn-name').val(''); $('#input-mnt-by').val(''); $('#input-mntner-pwd').val(''); $('#modal-asn').removeClass('hidden').addClass('flex'); }
    function closeModal(id) { $('#' + id).addClass('hidden').removeClass('flex'); }
    function editAsnConfig() { if (!currentAsnData) return; $('#input-asn').val(currentAsnData.asn).prop('disabled', true); $('#input-asn-name').val(currentAsnData.asn_name || ''); $('#input-mnt-by').val(currentAsnData.mnt_by || ''); $('#input-mntner-pwd').val(currentAsnData.mntner_password || ''); $('#modal-asn').removeClass('hidden').addClass('flex'); }
</script>
</body>
</html>
