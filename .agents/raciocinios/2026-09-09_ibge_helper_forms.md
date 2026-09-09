# Raciocínio Analítico: Integração da API IBGE nos Formulários de Cadastro

- **Data**: 09/09/2026
- **Contexto**: O sistema Dinovatech utiliza o código IBGE do município (`codigo_municipio`) em cadastros de clientes e configurações fiscais da empresa para emissão de NFS-e e validações de endereço.
- **Objetivo**: 
  1. Ao preencher ou carregar o campo de Cód. Município (IBGE), recuperar dinamicamente o nome da cidade e estado via API do IBGE (`https://servicodados.ibge.gov.br/api/v1/localidades/municipios/{codigo}`) e exibir um badge/label descritivo no formulário.
  2. Oferecer um helper nos formulários para selecionar a cidade/município a partir da UF informada (`https://servicodados.ibge.gov.br/api/v1/localidades/estados/{UF}/municipios`), preenchendo automaticamente o código IBGE correto.
  3. Integrar com a busca por CEP (ViaCEP), que já retorna o código IBGE de 7 dígitos do município.

---

## 1. Mapeamento de Arquivos e Formulários Existentes

Após varredura no código do Dinovatech:
1. **`cliente_form.php`**: Formulário de criação/edição de clientes (`#uf`, `#codigo_municipio`, `#cep`, `#endereco`, `#bairro`).
2. **`config_fiscal.php`**: Configurações fiscais da empresa (`#uf`, `#codigo_municipio`, `#cep`, `#endereco`, `#bairro`).
3. **`helpers/AppHelper.php`**: Já possui o método PHP `AppHelper::getCidadePorCodigo($codigo)` com cache em sessão cURL.
4. **`components/layout_scripts.php`**: Arquivo incluído no final de todas as páginas com os scripts JS compartilhados.

---

## 2. Arquitetura da Solução Frontend

Para garantir código limpo, reutilizável e sem duplicidade, desenvolveremos um módulo em JavaScript (`helpers/ibge_helper.js`) que será incluído globalmente via `layout_scripts.php` ou `layout_head.php`.

### Funcionalidades do Módulo JS (`AppIbgeHelper`):
1. **`fetchCityByCode(ibgeCode)`**: Consulta `https://servicodados.ibge.gov.br/api/v1/localidades/municipios/{ibgeCode}` via `fetch()` assíncrono com cache local em memória (para evitar requisições repetidas).
2. **`fetchCitiesByUF(uf)`**: Consulta `https://servicodados.ibge.gov.br/api/v1/localidades/estados/{uf}/municipios`, ordenando os municípios por nome.
3. **`bindForm(options)`**:
   - Conecta aos elementos do formulário (`input#codigo_municipio`, `input#uf`, `input#cep`, etc.).
   - Ao alterar o `codigo_municipio` ou carregar a página: faz lookup no IBGE e exibe `📍 Nome da Cidade - UF`.
   - Ao alterar/digitar a `UF`: disponibiliza um seletor `<select>` ou modal/dropdown com os municípios daquela UF.
   - Ao selecionar um município na lista: atualiza o input `#codigo_municipio` e a label de exibição.
   - Ao pesquisar CEP (`#cep` blur/change): realiza a busca no ViaCEP, preenche os campos do endereço e dispara automaticamente o lookup do IBGE!

---

## 3. Decisão de Design e Usabilidade (UX/UI)

- **Badge de Exibição**: Um elemento visual elegante em Tailwind CSS (`<div class="mt-1.5 flex items-center text-xs font-medium text-slate-600 bg-slate-100 rounded-md px-2.5 py-1 inline-flex">`) exibido logo abaixo do campo `codigo_municipio`.
- **Seletor de Município por UF**:
  - Próximo aos campos de UF e Cód. IBGE, incluiremos um botão/select inteligente: "Buscar município por UF...".
  - Quando a UF for informada (ex: `DF`, `SP`, `RJ`), o campo converte-se em um `<select>` com suporte a busca rápida de municípios.

---

## 4. Plano de Verificação

1. **Teste do Badge no Carregamento**: Abrir `cliente_form.php?id=...` ou `config_fiscal.php` com o código `5300108` padrão. Verificar se o badge "📍 Brasília - DF" é carregado imediatamente.
2. **Teste de Digitação de Código IBGE**: Alterar o código para `3550308`. Verificar se atualiza para "📍 São Paulo - SP".
3. **Teste de Seleção por UF**: Digitar `MG` no campo de UF e acionar o helper de municípios. Selecionar "Belo Horizonte". Verificar se o campo `codigo_municipio` é atualizado para `3106200`.
4. **Teste de CEP**: Digitar CEP válido (ex: `01001-000`). Verificar preenchimento do endereço, UF (`SP`), código IBGE (`3550308`) e atualização da cidade ("📍 São Paulo - SP").
