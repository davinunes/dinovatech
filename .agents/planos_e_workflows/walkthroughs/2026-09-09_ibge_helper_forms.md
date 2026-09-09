# Walkthrough / Entregas: Helper de Municípios IBGE e Integração ViaCEP nos Formulários

- **Data**: 09/09/2026
- **Status**: Concluído e Verificado

---

## Modificações Realizadas

### 1. Novo Módulo JavaScript `dinovatech/helpers/ibge_helper.js`
- Criado o módulo `window.AppIbge` com suporte a:
  - `getMunicipio(code)`: Consulta a API do IBGE (`https://servicodados.ibge.gov.br/api/v1/localidades/municipios/{codigo}`) com cache local em memória.
  - `getMunicipiosPorUF(uf)`: Consulta a lista de municípios da UF (`https://servicodados.ibge.gov.br/api/v1/localidades/estados/{UF}/municipios`), ordenados por nome.
  - `buscarCep(cep)`: Consulta o endereço e o código IBGE de 7 dígitos via ViaCEP (`https://viacep.com.br/ws/{cep}/json/`).
  - `bindForm(options)`: Vincula os eventos aos campos do formulário, renderiza o badge dinâmico da cidade (ex: `📍 Brasília - DF`) e gerencia a dropdown auxiliar de municípios por UF.

### 2. Inclusão Global no Layout (`layout_head.php` e `layout_scripts.php`)
- O script `helpers/ibge_helper.js` foi adicionado aos componentes compartilhados do sistema, garantindo disponibilidade em todas as telas de cadastros e parâmetros fiscais.

### 3. Atualização dos Formulários (`cliente_form.php` e `config_fiscal.php`)
- **`cliente_form.php`**:
  - Adicionada a div `#ibge_cidade_display` abaixo do campo `codigo_municipio`.
  - Adicionada a dropdown auxiliar `#select_municipio_helper` para escolha por UF.
  - Chamada inicial `AppIbge.bindForm()` adicionada no carregamento do formulário.
- **`config_fiscal.php`**:
  - Adicionada a div `#ibge_cidade_display` abaixo do campo `codigo_municipio`.
  - Adicionada a dropdown auxiliar `#select_municipio_helper`.
  - Chamada inicial e re-vinculação pós-carregamento AJAX adicionadas.

### 4. Ação no Backend (`dinovatech/app.php`)
- Adicionada a ação `get_ibge_cidade` que faz o fallback utilizando `AppHelper::getCidadePorCodigo($codigo)`.

---

## Como Testar / Validar

1. **Exibição do Nome do Município**:
   - Acesse o formulário de Cliente ([`cliente_form.php`](file:///e:/DEV/dinovatech/dinovatech/cliente_form.php)) ou Configurações Fiscais ([`config_fiscal.php`](file:///e:/DEV/dinovatech/dinovatech/config_fiscal.php)).
   - Ao carregar ou digitar o código IBGE `5300108`, o badge `📍 Brasília - DF` será exibido automaticamente.
   - Digite `3550308`: a exibição atualizará para `📍 São Paulo - SP`.

2. **Seletor de Municípios por Estado (UF)**:
   - Digite a UF (ex: `MG`, `RJ`, `SP`).
   - A dropdown "Selecionar Município pela UF" será ativada com as cidades daquela UF.
   - Ao selecionar uma cidade (ex: Niterói), o campo `Cód. Mun. (IBGE)` é atualizado com `3303302`.

3. **Busca Automática por CEP**:
   - Digite o CEP `01001-000` no campo CEP.
   - O endereço, bairro, UF (`SP`), código IBGE (`3550308`) e a label `📍 São Paulo - SP` serão preenchidos e atualizados automaticamente.
