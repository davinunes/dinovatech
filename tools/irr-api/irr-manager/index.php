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
        .glass {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
        }

        body {
            background: #f0f4f8;
            font-family: 'Inter', sans-serif;
        }

        .tab-active {
            border-b-2 border-indigo-600 text-indigo-600;
        }

        .tooltip {
            position: relative;
            display: inline-block;
            cursor: help;
        }

        .tooltip .tooltiptext {
            visibility: hidden;
            width: 220px;
            background-color: #333;
            color: #fff;
            text-align: center;
            border-radius: 6px;
            padding: 8px;
            position: absolute;
            z-index: 10;
            bottom: 125%;
            left: 50%;
            margin-left: -110px;
            opacity: 0;
            transition: opacity 0.3s;
            font-size: 0.75rem;
        }

        .tooltip:hover .tooltiptext {
            visibility: visible;
            opacity: 1;
        }

        .field-error {
            border-color: #ef4444 !important;
            background-color: #fef2f2;
        }

        #toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .toast {
            min-width: 300px;
            padding: 16px;
            border-radius: 8px;
            color: white;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            animation: slideIn 0.3s ease-out forwards;
        }
        .toast-success { background-color: #10b981; }
        .toast-error { background-color: #ef4444; }
        .toast-info { background-color: #3b82f6; }
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes fadeOut {
            from { opacity: 1; }
            to { opacity: 0; }
        }
    </style>
</head>

<body class="text-gray-800">
    <div id="toast-container"></div>

    <div class="min-h-screen flex flex-col">
        <!-- Header -->
        <header class="bg-indigo-700 text-white p-6 shadow-lg">
            <div class="container mx-auto flex justify-between items-center">
                <h1 class="text-2xl font-bold flex items-center">
                    <span class="material-icons mr-2">dns</span> IRR Manager <span
                        class="ml-2 font-normal text-sm opacity-75">v1.2 (TC Registry)</span>
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
                    <button onclick="showAddAsnModal()"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg flex items-center transition">
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
                        <div class="flex space-x-2">
                            <button id="btn-sync-whois"
                                class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg flex items-center shadow-sm">
                                <span class="material-icons mr-1">sync</span> Sincronizar via WHOIS
                            </button>
                            <button onclick="showCreateObjectModal()"
                                class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg flex items-center shadow-sm">
                                <span class="material-icons mr-1">add_circle</span> Novo Objeto
                            </button>
                        </div>
                        <button onclick="editAsnConfig()"
                            class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg flex items-center border">
                            <span class="material-icons mr-1">settings</span> Editar Configurações
                        </button>
                        <div class="tooltip ml-auto">
                            <span class="material-icons text-gray-400">help_outline</span>
                            <span class="tooltiptext">Sincroniza objetos RPSL (aut-num, route, etc) diretamente do
                                servidor bgp.net.br</span>
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
                                    <!-- Objects listed here -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </main>

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
                    <label class="block text-sm font-medium text-gray-700">Número do ASN</label>
                    <div class="mt-1 flex rounded-md shadow-sm">
                        <span
                            class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm">AS</span>
                        <input type="number" id="input-asn-num"
                            class="focus:ring-indigo-500 focus:border-indigo-500 flex-1 block w-full rounded-none rounded-r-md sm:text-sm border-gray-300 p-2"
                            placeholder="265138" autocomplete="off">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Nome da Organização</label>
                    <input type="text" id="input-asn-name"
                        class="mt-1 block w-full border border-gray-300 rounded-md p-2 shadow-sm focus:ring-indigo-500 sm:text-sm"
                        placeholder="Ex: Meu Provedor LTDA" autocomplete="off">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Mantenedor (mnt-by)</label>
                    <input type="text" id="input-mnt-by"
                        class="mt-1 block w-full border border-gray-300 rounded-md p-2 shadow-sm focus:ring-indigo-500 sm:text-sm"
                        placeholder="Ex: MAINT-AS265138" autocomplete="off">
                    <p class="text-xs text-gray-400 mt-1">Sugerido: MAINT-AS + número do ASN</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Senha do Mantner</label>
                    <input type="password" id="input-mntner-pwd"
                        class="mt-1 block w-full border border-gray-300 rounded-md p-2 shadow-sm focus:ring-indigo-500 sm:text-sm"
                        placeholder="Sua senha secreta" autocomplete="new-password">
                </div>
                <div class="flex justify-end space-x-2 pt-4">
                    <button onclick="closeModal('modal-asn')"
                        class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-100 transition">Cancelar</button>
                    <button id="btn-save-asn"
                        class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">Salvar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Create New Object -->
    <div id="modal-create-object"
        class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white p-8 rounded-xl w-full max-w-md shadow-2xl">
            <h3 class="text-xl font-bold mb-4">Novo Objeto IRR</h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Tipo de Objeto</label>
                    <select id="create-obj-type"
                        class="mt-1 block w-full border border-gray-300 rounded-md p-2 shadow-sm focus:ring-indigo-500">
                        <option value="aut-num">aut-num</option>
                        <option value="route">route (IPv4)</option>
                        <option value="route6">route6 (IPv6)</option>
                        <option value="as-set">as-set (AS-XXXX)</option>
                        <option value="route-set">route-set (RS-XXXX)</option>
                        <option value="mntner">mntner</option>
                        <option value="person">person</option>
                        <option value="role">role</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Nome/Chave (ex: AS-MEU-SET ou
                        192.168.0.0/24)</label>
                    <input type="text" id="create-obj-name"
                        class="mt-1 block w-full border border-gray-300 rounded-md p-2 shadow-sm"
                        placeholder="Identificador do objeto">
                    <p id="create-obj-warning" class="text-red-500 text-xs mt-1 hidden">O nome para este tipo deve
                        seguir as regras de prefixo.</p>
                </div>
                <div class="flex justify-end space-x-2 pt-4">
                    <button onclick="closeModal('modal-create-object')"
                        class="px-4 py-2 border border-gray-300 rounded-lg">Cancelar</button>
                    <button onclick="handleCreateObject()"
                        class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">Confirmar</button>
                </div>
            </div>
        </div>
    </div>

    <div id="modal-object" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white p-8 rounded-xl w-full max-w-5xl shadow-2xl max-h-[90vh] flex flex-col">
            <div class="flex justify-between items-center mb-4">
                <h3 id="modal-object-title" class="text-xl font-bold">Editar Objeto</h3>
                <button onclick="closeModal('modal-object')" class="text-gray-400 hover:text-gray-600">
                    <span class="material-icons">close</span>
                </button>
            </div>

            <div id="object-form-container" class="space-y-6 overflow-y-auto flex-grow pr-2">
                <div id="form-fields-required" class="grid grid-cols-1 md:grid-cols-2 gap-4"></div>
                <div class="border-t pt-4">
                    <h4 class="text-sm font-bold text-gray-400 uppercase mb-3">Atributos Opcionais</h4>
                    <div id="form-fields-optional" class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4"></div>
                    <div class="flex items-center space-x-3">
                        <select id="select-add-optional" class="border border-gray-300 rounded-md p-2 text-sm"></select>
                        <button id="btn-add-optional"
                            class="bg-indigo-100 text-indigo-700 px-3 py-2 rounded-lg text-sm font-bold hover:bg-indigo-200 transition">Adicionar</button>
                    </div>
                </div>
            </div>

            <div class="flex justify-end space-x-2 pt-6 border-t mt-4">
                <button onclick="closeModal('modal-object')"
                    class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-100 transition">Cancelar</button>
                <button id="btn-save-object"
                    class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">Salvar
                    Localmente</button>
            </div>
        </div>
    </div>

    <script>
        let currentAsn = null;
        let currentAsnData = null;
        let globalContacts = [];
        let globalSets = [];

        const SCHEMAS = {
            'mntner': {
                required: ['mntner', 'descr', 'admin-c', 'upd-to', 'auth', 'mnt-by', 'referral-by', 'source'],
                optional: ['remarks', 'address', 'phone', 'e-mail', 'notify']
            },
            'aut-num': {
                required: ['aut-num', 'as-name', 'descr', 'admin-c', 'tech-c', 'mnt-by', 'source'],
                optional: ['import', 'export', 'remarks', 'mnt-routes', 'upd-to', 'notify']
            },
            'as-set': {
                required: ['as-set', 'descr', 'admin-c', 'tech-c', 'mnt-by', 'source'],
                optional: ['members', 'remarks', 'upd-to', 'notify']
            },
            'route-set': {
                required: ['route-set', 'descr', 'admin-c', 'tech-c', 'mnt-by', 'source'],
                optional: ['members', 'mp-members', 'remarks', 'upd-to', 'notify']
            },
            'route': {
                required: ['route', 'descr', 'origin', 'mnt-by', 'source'],
                optional: ['holes', 'member-of', 'remarks', 'upd-to', 'notify']
            },
            'route6': {
                required: ['route6', 'descr', 'origin', 'mnt-by', 'source'],
                optional: ['holes', 'member-of', 'remarks', 'upd-to', 'notify']
            },
            'person': {
                required: ['person', 'address', 'phone', 'e-mail', 'nic-hdl', 'mnt-by', 'source'],
                optional: ['fax-no', 'remarks', 'upd-to', 'notify']
            },
            'role': {
                required: ['role', 'address', 'phone', 'e-mail', 'nic-hdl', 'mnt-by', 'source'],
                optional: ['fax-no', 'remarks', 'upd-to', 'notify']
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
            'mnt-routes': 'Mantenedor permitido criar rotas para este ASN.',
            'members': 'Membros do grupo (um por linha para múltiplos).',
            'mp-members': 'Membros IPv6 para route-set (um por linha).',
            'member-of': 'O as-set ou route-set que este objeto pertence.',
            'notify': 'E-mail para ser notificado sobre mudanças.'
        };

        $(document).ready(function () {
            loadAsns();
            loadAllContacts();
            loadAllSets();
            $('#btn-save-asn').click(handleSaveAsn);
            $('#btn-sync-whois').click(handleSyncWhois);
            $('#btn-add-optional').click(handleAddOptional);

            $('#input-asn-num').on('input', function () {
                const val = $(this).val();
                if (val && !$('#input-mnt-by').val().startsWith('MAINT-AS')) {
                    $('#input-mnt-by').val('MAINT-AS' + val);
                } else if (val && $('#input-mnt-by').val().startsWith('MAINT-AS')) {
                    // Update existing if it still follows the pattern
                    $('#input-mnt-by').val('MAINT-AS' + val);
                }
            });
        });

        function loadAllContacts() { $.getJSON('api.php?action=get_all_contacts', res => { if (res.success) globalContacts = res.data; }); }
        function loadAllSets() { $.getJSON('api.php?action=get_all_sets', res => { if (res.success) globalSets = res.data; }); }

        function showToast(message, type = 'info') {
            const id = 'toast-' + Math.random().toString(36).substr(2, 9);
            const icons = { success: 'check_circle', error: 'error', info: 'info' };
            const html = `
                <div id="${id}" class="toast toast-${type}">
                    <span class="material-icons mr-2">${icons[type]}</span>
                    <span class="flex-grow">${message}</span>
                </div>
            `;
            $('#toast-container').append(html);
            setTimeout(() => {
                $(`#${id}`).css('animation', 'fadeOut 0.5s forwards');
                setTimeout(() => $(`#${id}`).remove(), 500);
            }, 6000);
        }

        function handleSaveAsn() {
            const num = $('#input-asn-num').val().trim();
            if (!num) return showToast('Informe o número do ASN.', 'error');

            const data = {
                asn: 'AS' + num,
                asn_name: $('#input-asn-name').val().trim(),
                mnt_by: $('#input-mnt-by').val().toUpperCase().trim(),
                mntner_password: $('#input-mntner-pwd').val()
            };

            $.post('api.php?action=save_asn', JSON.stringify(data), res => {
                if (res.success) { closeModal('modal-asn'); loadAsns(); showToast('ASN salvo com sucesso!', 'success'); }
                else showToast('Erro: ' + res.message, 'error');
            });
        }

        function handleSyncWhois() {
            if (!currentAsn) return;
            const btn = $(this); btn.prop('disabled', true).text('Sincronizando...');
            $.getJSON('api.php?action=sync_whois&asn=' + currentAsn, res => {
                btn.prop('disabled', false).html('<span class="material-icons mr-1">sync</span> Sincronizar via WHOIS');
                if (res.success) { showToast('Sucesso! ' + (res.count || 0) + ' objetos sincronizados.', 'success'); loadAsnDetail(currentAsn); loadAllContacts(); loadAllSets(); }
                else showToast('Erro: ' + res.message, 'error');
            });
        }

        function loadAsns() {
            $.getJSON('api.php?action=list_asns', res => {
                const list = $('#asn-list').empty();
                if (res.data.length === 0) return list.html('<div class="col-span-full py-10 bg-white rounded-xl text-center">Nenhum ASN.</div>');
                res.data.forEach(asn => {
                    list.append(`<div class="bg-white p-6 rounded-xl shadow-md border hover:border-indigo-500 transition cursor-pointer" onclick="loadAsnDetail('${asn.asn}')">
                    <div class="flex justify-between items-start"><h4 class="text-xl font-bold text-indigo-700">${asn.asn}</h4><span class="bg-indigo-100 text-indigo-700 text-xs px-2 py-1 rounded-full">${asn.object_count}</span></div>
                    <p class="text-gray-600 text-sm mt-2">${asn.name || 'Provedor'}</p>
                </div>`);
                });
            });
        }

        function loadAsnDetail(asn) {
            currentAsn = asn;
            $.getJSON('api.php?action=get_asn&asn=' + asn, data => {
                currentAsnData = data;
                $('#current-asn-title').text(asn + ' - ' + (data.asn_name || 'Detalhes'));
                const tbody = $('#object-list').empty();
                if (!data.objects || data.objects.length === 0) tbody.append('<tr><td colspan="4" class="p-6 text-center text-gray-500">Nenhum objeto.</td></tr>');
                else {
                    data.objects.forEach((obj, index) => {
                        let statusClass = 'bg-gray-100 text-gray-700';
                        let statusLabel = obj.status || 'novo';

                        if (obj.status === 'importado') statusClass = 'bg-blue-100 text-blue-700';
                        else if (obj.status === 'editado') statusClass = 'bg-yellow-100 text-yellow-700';
                        else if (obj.status === 'enviado') statusClass = 'bg-green-100 text-green-700';
                        else if (obj.status === 'erro') statusClass = 'bg-red-100 text-red-700';

                        tbody.append(`
                            <tr class="border-t hover:bg-gray-50 transition">
                                <td class="p-3 font-mono text-sm">${obj.name}</td>
                                <td class="p-3 text-sm">${obj.type}</td>
                                <td class="p-3">
                                    <span class="text-xs px-2 py-1 rounded-full uppercase font-bold ${statusClass}">${statusLabel}</span>
                                </td>
                                <td class="p-3">
                                    <div class="flex items-center space-x-2">
                                        <button onclick="editObject(${index})" 
                                                class="text-indigo-600 hover:bg-indigo-50 border border-indigo-200 px-2 py-1 rounded text-sm bg-white transition">
                                            Editar
                                        </button>
                                        
                                        <button onclick="sendToTc(${index})" 
                                                class="text-green-600 hover:bg-green-50 border border-green-200 px-2 py-1 rounded text-sm bg-white transition">
                                            Enviar TC
                                        </button>

                                        <div class="h-4 border-l border-gray-300 mx-1"></div>

                                        <button onclick="deleteFromTc(${index})" 
                                                class="text-red-600 hover:bg-red-600 hover:text-white border border-red-200 px-2 py-1 rounded text-sm bg-white transition flex items-center" 
                                                title="Excluir no TC (API)">
                                            <span class="material-icons text-xs mr-1">delete_forever</span> TC
                                        </button>

                                        <button onclick="deleteObject(${index})" 
                                                class="text-gray-500 hover:text-red-600 hover:bg-gray-100 border border-gray-200 px-2 py-1 rounded text-sm bg-white transition" 
                                                title="Remover apenas do JSON local">
                                            <span class="material-icons text-xs mr-1">delete_forever</span> Local
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        `);
                    });
                }
                $('#view-dashboard').hide(); $('#view-asn-detail').removeClass('hidden').show();
            });
        }

        function deleteFromTc(index) {
            if (!confirm('Tem certeza que deseja remover este objeto do TC? Esta ação não pode ser desfeita no servidor remoto.')) return;
            const obj = currentAsnData.objects[index];
            const btn = event.currentTarget;
            const oldHtml = btn.innerHTML;
            btn.innerHTML = '<span class="material-icons text-sm animate-spin">sync</span>';

            $.post('api.php?action=delete_from_tc', JSON.stringify({ asn: currentAsn, index: index }), function (res) {
                btn.innerHTML = oldHtml;
                loadAsnDetail(currentAsn);
                
                if (res.data && res.data.objects && res.data.objects[0]) {
                    const objRes = res.data.objects[0];
                    if (objRes.successful) {
                        showToast('Objeto removido com sucesso do TC!', 'success');
                    } else {
                        showToast('Falha na remoção do TC.', 'error');
                        if (objRes.error_messages) {
                            objRes.error_messages.forEach(msg => showToast(msg, 'error'));
                        }
                    }
                } else {
                    showToast('Erro na remoção do TC: Verifique os logs.', 'error');
                }
            });
        }

        function showCreateObjectModal() { $('#create-obj-name').val(''); $('#create-obj-warning').hide(); $('#modal-create-object').removeClass('hidden').addClass('flex'); }

        function handleCreateObject() {
            const type = $('#create-obj-type').val();
            const name = $('#create-obj-name').val().trim();
            if (!name) return showToast('Informe o nome/chave do objeto.', 'error');

            // Validation for Sets
            if (type === 'as-set' && !name.toUpperCase().startsWith('AS-')) return $('#create-obj-warning').text('Objeto as-set deve iniciar com AS-').show();
            if (type === 'route-set' && !name.toUpperCase().startsWith('RS-')) return $('#create-obj-warning').text('Objeto route-set deve iniciar com RS-').show();

            const newObj = { type: type, name: name.toUpperCase(), attributes: [{ name: type, value: name.toUpperCase() }], status: 'local' };
            if (!currentAsnData.objects) currentAsnData.objects = [];
            currentAsnData.objects.push(newObj);
            closeModal('modal-create-object');
            editObject(currentAsnData.objects.length - 1);
        }

        function editObject(index) {
            const obj = currentAsnData.objects[index];
            const schema = SCHEMAS[obj.type] || { required: [], optional: [] };
            $('#modal-object-title').text(`Editar ${obj.type}: ${obj.name}`);
            $('#form-fields-required').empty(); $('#form-fields-optional').empty();
            const dropdown = $('#select-add-optional').empty().append('<option value="">+ Adicionar Atributo Opcional</option>');
            schema.optional.forEach(opt => dropdown.append(`<option value="${opt}">${opt}</option>`));
            const objAttrs = {};
            obj.attributes.forEach(a => {
                if (!objAttrs[a.name]) objAttrs[a.name] = [];
                objAttrs[a.name].push(a.value);
            });

            schema.required.forEach(name => {
                const val = (objAttrs[name] || []).join('\n');
                renderField(name, val, '#form-fields-required', true);
            });

            Object.keys(objAttrs).forEach(name => {
                if (schema.optional.includes(name)) {
                    const val = objAttrs[name].join('\n');
                    renderField(name, val, '#form-fields-optional', false);
                }
            });
            $('#btn-save-object').off('click').on('click', () => saveObjectChanges(index));
            $('#modal-object').removeClass('hidden').addClass('flex');
        }

        function renderField(name, val, container, isRequired) {
            const tooltip = TOOLTIPS[name] ? `<div class="tooltip ml-1"><span class="material-icons" style="font-size:14px">help_outline</span><span class="tooltiptext">${TOOLTIPS[name]}</span></div>` : '';
            const inputId = `field-${Math.random().toString(36).substr(2, 9)}`;
            let fieldHtml = `<div class="field-wrapper" data-name="${name}"><label class="block text-xs font-bold text-gray-500 uppercase flex items-center">${name} ${isRequired ? '<span class="text-red-500 ml-1">*</span>' : ''} ${tooltip}</label>`;
            if (['admin-c', 'tech-c', 'upd-to', 'mnt-by', 'referral-by'].includes(name)) {
                const contacts = globalContacts.filter(c => c.type !== 'mntner');
                const mntners = globalContacts.filter(c => c.type === 'mntner');

                fieldHtml += `<div class="flex space-x-2 mt-1">
                    <select id="${inputId}" class="flex-grow border border-gray-300 rounded-md p-2 text-sm">
                        <option value="">-- Selecione ou digite --</option>
                        <optgroup label="Mantenedores (MAINT-...)">
                            ${mntners.map(c => `<option value="${c.nic}" ${c.nic === val ? 'selected' : ''}>${c.nic}</option>`).join('')}
                        </optgroup>
                        <optgroup label="Contatos (NIC-HDL)">
                            ${contacts.map(c => `<option value="${c.nic}" ${c.nic === val ? 'selected' : ''}>${c.nic} (${c.name})</option>`).join('')}
                        </optgroup>
                    </select>
                    <input type="text" placeholder="Novo" onchange="$(this).prev().val(this.value)" class="w-1/3 border border-gray-300 rounded-md p-2 text-sm">
                </div>`;
            } else if (name === 'member-of') {
                fieldHtml += `<div class="mt-1 flex space-x-2">
                    <select class="flex-grow border border-gray-300 rounded-md p-2 text-sm" onchange="$(this).next().val(this.value)">
                        <option value="">-- Selecione ou digite --</option>
                        ${globalSets.map(s => `<option value="${s.name}" ${s.name === val ? 'selected' : ''}>${s.name} (${s.type})</option>`).join('')}
                    </select>
                    <input type="text" id="${inputId}" value="${val}" class="w-1/3 border border-gray-300 rounded-md p-2 text-sm shadow-sm" placeholder="ID do Set">
                </div>`;
            } else if (['members', 'mp-members', 'remarks', 'address', 'import', 'export', 'notify', 'upd-to'].includes(name)) {
                fieldHtml += `<textarea id="${inputId}" class="mt-1 block w-full border border-gray-300 rounded-md p-2 text-sm h-32 font-mono whitespace-pre overflow-y-auto">${val}</textarea>`;
            } else {
                fieldHtml += `<input type="text" id="${inputId}" value="${val}" class="mt-1 block w-full border border-gray-300 rounded-md p-2 text-sm shadow-sm">`;
            }
            if (!isRequired) fieldHtml += `<button onclick="$(this).parent().remove()" class="text-red-500 text-xs mt-1 hover:underline">Remover atributo</button>`;
            fieldHtml += `</div>`; $(container).append(fieldHtml);
        }

        function handleAddOptional() { const name = $('#select-add-optional').val(); if (name) renderField(name, '', '#form-fields-optional', false); }

        function saveObjectChanges(index) {
            const obj = currentAsnData.objects[index]; const newAttributes = [];
            newAttributes.push({ name: obj.type, value: obj.name });
            $('#modal-object .field-wrapper').each(function () {
                const name = $(this).data('name');
                const textarea = $(this).find('textarea');
                let values = [];

                if (textarea.length > 0) {
                    // Split lines for multiple entries
                    values = textarea.val().split('\n').map(v => v.trim()).filter(v => v !== '');
                } else {
                    const val = $(this).find('input, select').first().val().trim();
                    if (val) values = [val];
                }

                values.forEach(v => {
                    if (v && name !== obj.type) newAttributes.push({ name: name, value: v });
                });
            });
            currentAsnData.objects[index].attributes = newAttributes; currentAsnData.objects[index].status = 'editado';
            $.post('api.php?action=save_asn', JSON.stringify({ asn: currentAsn, asn_name: currentAsnData.asn_name, mnt_by: currentAsnData.mnt_by, mntner_password: currentAsnData.mntner_password, objects: currentAsnData.objects }), res => {
                if (res.success) { closeModal('modal-object'); loadAsnDetail(currentAsn); loadAllContacts(); loadAllSets(); showToast('Alterações salvas localmente!', 'success'); } else showToast('Erro ao salvar', 'error');
            });
        }

        function deleteObject(index) {
            if (!confirm('Excluir este objeto localmente? Esta ação não pode ser desfeita.')) return;
            currentAsnData.objects.splice(index, 1);
            $.post('api.php?action=save_asn', JSON.stringify({ asn: currentAsn, asn_name: currentAsnData.asn_name, mnt_by: currentAsnData.mnt_by, mntner_password: currentAsnData.mntner_password, objects: currentAsnData.objects }), res => {
                if (res.success) { loadAsnDetail(currentAsn); showToast('Objeto removido localmente.', 'info'); } else showToast('Erro ao excluir', 'error');
            });
        }

        function sendToTc(index) {
            if (!confirm('Enviar para o Registro TC?')) return;
            const btn = event.target; btn.innerText = 'Enviando...';
            $.post('api.php?action=submit_to_tc', JSON.stringify({ asn: currentAsn, index: index }), function (res) {
                btn.innerText = 'Enviar TC';
                loadAsnDetail(currentAsn); 
                
                if (res.data && res.data.objects && res.data.objects[0]) {
                    const objRes = res.data.objects[0];
                    if (objRes.successful) {
                        showToast('Enviado com sucesso ao TC! Log gerado.', 'success');
                    } else {
                        showToast('Falha na submissão ao TC.', 'error');
                        if (objRes.error_messages) {
                            objRes.error_messages.forEach(msg => showToast(msg, 'error'));
                        }
                    }
                } else if (!res.success) {
                    showToast('Erro na requisição: ' + (res.message || 'Verifique os logs.'), 'error');
                }
            });
        }

        function showDashboard() { $('#view-asn-detail').hide(); $('#view-dashboard').show(); loadAsns(); }
        function showAddAsnModal() {
            $('#input-asn-num').val('').prop('disabled', false);
            $('#input-asn-name').val('');
            $('#input-mnt-by').val('');
            $('#input-mntner-pwd').val('');
            $('#modal-asn').removeClass('hidden').addClass('flex');
        }
        function closeModal(id) { $('#' + id).addClass('hidden').removeClass('flex'); }
        function editAsnConfig() {
            if (!currentAsnData) return;
            const asnNum = currentAsnData.asn.replace('AS', '');
            $('#input-asn-num').val(asnNum).prop('disabled', true);
            $('#input-asn-name').val(currentAsnData.asn_name || '');
            $('#input-mnt-by').val(currentAsnData.mnt_by || '');
            $('#input-mntner-pwd').val(currentAsnData.mntner_password || '');
            $('#modal-asn').removeClass('hidden').addClass('flex');
        }
    </script>
</body>

</html>