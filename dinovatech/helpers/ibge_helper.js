/**
 * AppIbgeHelper - Módulo de integração com a API de Localidades do IBGE e ViaCEP
 * Dinovatech System
 */
window.AppIbge = (function () {
    const cacheMunicipios = new Map();
    const cacheUfMunicipios = new Map();

    /**
     * Busca informações de um município pelo código IBGE (7 dígitos)
     * @param {string|number} code 
     * @returns {Promise<{id: number, nome: string, uf: string, nomeCompleto: string}|null>}
     */
    async function getMunicipio(code) {
        const cleanCode = String(code).replace(/\D/g, '');
        if (!cleanCode || cleanCode.length < 5) {
            return null;
        }

        if (cacheMunicipios.has(cleanCode)) {
            return cacheMunicipios.get(cleanCode);
        }

        try {
            const resp = await fetch(`https://servicodados.ibge.gov.br/api/v1/localidades/municipios/${cleanCode}`);
            if (!resp.ok) return null;
            const data = await resp.json();

            if (data && data.nome) {
                const uf = data.microrregiao?.mesorregiao?.UF?.sigla ||
                           data['regiao-imediata']?.['regiao-intermediaria']?.UF?.sigla || '';
                const result = {
                    id: data.id,
                    nome: data.nome,
                    uf: uf,
                    nomeCompleto: uf ? `${data.nome} - ${uf}` : data.nome
                };
                cacheMunicipios.set(cleanCode, result);
                return result;
            }
        } catch (err) {
            console.warn('[AppIbge] Erro ao consultar município IBGE:', err);
        }

        return null;
    }

    /**
     * Busca a lista de municípios pertencentes a uma UF (ex: 'DF', 'SP', 'RJ')
     * @param {string} uf 
     * @returns {Promise<Array<{id: number, nome: string}>>}
     */
    async function getMunicipiosPorUF(uf) {
        const cleanUf = String(uf).trim().toUpperCase();
        if (!cleanUf || cleanUf.length !== 2) {
            return [];
        }

        if (cacheUfMunicipios.has(cleanUf)) {
            return cacheUfMunicipios.get(cleanUf);
        }

        try {
            const resp = await fetch(`https://servicodados.ibge.gov.br/api/v1/localidades/estados/${cleanUf}/municipios`);
            if (!resp.ok) return [];
            const list = await resp.json();

            if (Array.isArray(list)) {
                const result = list.map(m => ({
                    id: m.id,
                    nome: m.nome
                })).sort((a, b) => a.nome.localeCompare(b.nome, 'pt-BR'));

                cacheUfMunicipios.set(cleanUf, result);
                return result;
            }
        } catch (err) {
            console.warn('[AppIbge] Erro ao consultar municípios da UF:', err);
        }

        return [];
    }

    /**
     * Consulta endereço por CEP no ViaCEP
     * @param {string} cep 
     * @returns {Promise<{cep: string, logradouro: string, complemento: string, bairro: string, localidade: string, uf: string, ibge: string, erro?: boolean}|null>}
     */
    async function buscarCep(cep) {
        const cleanCep = String(cep).replace(/\D/g, '');
        if (cleanCep.length !== 8) return null;

        try {
            const resp = await fetch(`https://viacep.com.br/ws/${cleanCep}/json/`);
            if (!resp.ok) return null;
            const data = await resp.json();
            if (data.erro) return null;

            return {
                cep: data.cep,
                logradouro: data.logradouro || '',
                complemento: data.complemento || '',
                bairro: data.bairro || '',
                localidade: data.localidade || '',
                uf: data.uf || '',
                ibge: data.ibge || ''
            };
        } catch (err) {
            console.warn('[AppIbge] Erro ao consultar ViaCEP:', err);
        }
        return null;
    }

    /**
     * Vincula o helper a um formulário com suporte a atualizações dinâmicas
     * @param {Object} options
     */
    function bindForm(options = {}) {
        const ibgeSelector = options.ibgeInput || '#codigo_municipio';
        const ufSelector = options.ufInput || '#uf';
        const cepSelector = options.cepInput || '#cep';
        const enderecoSelector = options.enderecoInput || '#endereco';
        const bairroSelector = options.bairroInput || '#bairro';
        const displaySelector = options.displayContainer || '#ibge_cidade_display';
        const citySelectSelector = options.citySelect || '#select_municipio_helper';

        const $ibge = $(ibgeSelector);
        const $uf = $(ufSelector);
        const $cep = $(cepSelector);
        const $endereco = $(enderecoSelector);
        const $bairro = $(bairroSelector);
        const $citySelect = $(citySelectSelector);

        if (!$ibge.length) return;

        // Container de exibição da cidade (criar se não existir)
        let $display = $(displaySelector);
        if (!$display.length) {
            $display = $('<div id="ibge_cidade_display" class="mt-1.5 min-h-[22px]"></div>');
            $ibge.after($display);
        }

        // Função para atualizar o badge da cidade
        async function updateCityBadge() {
            const code = $ibge.val() ? String($ibge.val()).trim() : '';
            if (!code || code.length < 5) {
                $display.html('');
                return;
            }

            $display.html(`
                <span class="inline-flex items-center gap-1.5 text-xs text-slate-500">
                    <svg class="animate-spin h-3.5 w-3.5 text-cyan-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Buscando município IBGE...
                </span>
            `);

            const city = await getMunicipio(code);
            if (city && city.nome) {
                $display.html(`
                    <span class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-800 bg-emerald-50 border border-emerald-200 px-2.5 py-1 rounded-md shadow-sm">
                        <span class="material-icons text-sm text-emerald-600">location_on</span>
                        ${city.nomeCompleto}
                    </span>
                `);

                // Se o campo UF estiver vazio, preenche com a UF retornada
                if ($uf.length && !$uf.val() && city.uf) {
                    $uf.val(city.uf);
                    updateCitySelectOptions(city.uf, city.id);
                }
            } else {
                $display.html(`
                    <span class="inline-flex items-center gap-1 text-xs font-medium text-amber-800 bg-amber-50 border border-amber-200 px-2.5 py-1 rounded-md">
                        <span class="material-icons text-sm text-amber-500">warning</span>
                        Cód. IBGE não localizado (${code})
                    </span>
                `);
            }
        }

        // Função para atualizar as opções do select de município auxiliar por UF
        async function updateCitySelectOptions(uf, selectedIbgeCode = null) {
            if (!$citySelect.length) return;

            const cleanUf = String(uf || '').trim().toUpperCase();
            if (!cleanUf || cleanUf.length !== 2) {
                $citySelect.html('<option value="">-- Selecione uma UF primeiro --</option>').prop('disabled', true);
                return;
            }

            $citySelect.html(`<option value="">Carregando cidades de ${cleanUf}...</option>`).prop('disabled', true);

            const list = await getMunicipiosPorUF(cleanUf);
            if (list.length > 0) {
                let optionsHtml = `<option value="">-- Selecionar Cidade de ${cleanUf} (${list.length}) --</option>`;
                const currentIbge = selectedIbgeCode || $ibge.val();

                list.forEach(m => {
                    const isSelected = String(m.id) === String(currentIbge) ? 'selected' : '';
                    optionsHtml += `<option value="${m.id}" ${isSelected}>${m.nome} (${m.id})</option>`;
                });

                $citySelect.html(optionsHtml).prop('disabled', false);
            } else {
                $citySelect.html(`<option value="">Nenhuma cidade encontrada para ${cleanUf}</option>`).prop('disabled', true);
            }
        }

        // Eventos
        $ibge.on('input change blur', function () {
            updateCityBadge();
        });

        if ($uf.length) {
            $uf.on('input change blur', function () {
                const ufVal = String($(this).val() || '').trim().toUpperCase();
                if (ufVal.length === 2) {
                    updateCitySelectOptions(ufVal);
                }
            });
        }

        if ($citySelect.length) {
            $citySelect.on('change', function () {
                const selectedCode = $(this).val();
                if (selectedCode) {
                    $ibge.val(selectedCode).trigger('change');
                }
            });
        }

        if ($cep.length) {
            $cep.on('blur change', async function () {
                const rawCep = $(this).val();
                if (!rawCep) return;

                const address = await buscarCep(rawCep);
                if (address) {
                    if ($endereco.length && address.logradouro) $endereco.val(address.logradouro);
                    if ($bairro.length && address.bairro) $bairro.val(address.bairro);
                    if ($uf.length && address.uf) {
                        $uf.val(address.uf);
                        updateCitySelectOptions(address.uf, address.ibge);
                    }
                    if (address.ibge) {
                        $ibge.val(address.ibge).trigger('change');
                    }
                }
            });
        }

        // Execução inicial no carregamento da página
        updateCityBadge();
        if ($uf.length && $uf.val()) {
            updateCitySelectOptions($uf.val(), $ibge.val());
        }
    }

    return {
        getMunicipio,
        getMunicipiosPorUF,
        buscarCep,
        bindForm
    };
})();
