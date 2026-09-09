# Plano de Implementação: Helper de Municípios e Integração com API IBGE / ViaCEP nos Formulários

- **Data**: 09/09/2026
- **Status**: Em Elaboração
- **Arquivos Envolvidos**:
  - `dinovatech/helpers/ibge_helper.js` [NOVO]
  - `dinovatech/components/layout_scripts.php` [MODIFICAR]
  - `dinovatech/cliente_form.php` [MODIFICAR]
  - `dinovatech/config_fiscal.php` [MODIFICAR]
  - `dinovatech/app.php` [MODIFICAR]

---

## 1. Objetivos

1. **Recuperação e Exibição Dinâmica do Nome do Município**:
   - Exibir o nome da cidade e estado (ex: `📍 Brasília - DF`) em tempo real logo abaixo do campo `Cód. Município (IBGE)` (`codigo_municipio`).
   - Consultar diretamente a API pública do IBGE: `https://servicodados.ibge.gov.br/api/v1/localidades/municipios/{codigo}`.

2. **Helper de Seleção por Estado (UF)**:
   - Ao preencher/selecionar a UF, carregar a lista de municípios da UF via `https://servicodados.ibge.gov.br/api/v1/localidades/estados/{UF}/municipios`.
   - Permitir a escolha fácil da cidade através de um dropdown/select com busca rápida, preenchendo automaticamente o código IBGE correspondente.

3. **Autopreenchimento Inteligente por CEP**:
   - Ao digitar um CEP válido, consultar a API ViaCEP (`https://viacep.com.br/ws/{cep}/json/`) para preencher Logradouro, Bairro, UF e o Código IBGE de 7 dígitos, atualizando imediatamente a exibição da cidade.

---

## 2. Alterações Detalhadas por Componente

### A. Módulo JS Reutilizável (`dinovatech/helpers/ibge_helper.js`)
- Criar a classe/objeto `AppIbge` com suporte a:
  - Cache de requisições em memória JS (`Map` ou objeto chave-valor) para evitar requisições duplicadas.
  - Métodos `getMunicipio(codIbge)`, `getMunicipiosPorUF(uf)`, `consultarCep(cep)`.
  - Método `bindFormOptions(config)` para vinculação automatizada a qualquer formulário com os IDs dos campos (`#codigo_municipio`, `#uf`, `#cep`, etc.).

### B. Inclusão no Layout (`dinovatech/components/layout_scripts.php`)
- Incluir o script `<script src="helpers/ibge_helper.js"></script>` para que esteja disponível em todas as telas de cadastros e configurações.

### C. Ajustes nos Formulários (`cliente_form.php` e `config_fiscal.php`)
- **`cliente_form.php`**:
  - Adicionar o container do badge de exibição da cidade (`<div id="ibge_cidade_display"></div>`).
  - Adicionar o seletor auxiliar de municípios (`<select id="select_municipio_helper">`).
  - Inicializar `AppIbge.init(...)` ao carregar a página.
- **`config_fiscal.php`**:
  - Adicionar o container do badge de exibição da cidade.
  - Adicionar o seletor auxiliar de municípios.
  - Inicializar `AppIbge.init(...)` ao carregar a página.

### D. Endpoint de Backup no Backend (`dinovatech/app.php`)
- Adicionar ação `get_ibge_info` no `app.php` que invoca `AppHelper::getCidadePorCodigo($codigo)` para casos de fallback ou integrações server-side se necessário.

---

## 3. Plano de Teste e Validação

1. **Exibição do Nome do Município**:
   - Testar com código de Brasília (`5300108`), São Paulo (`3550308`), Rio de Janeiro (`3304557`).
   - Verificar retorno e exibição do formato "Cidade - UF".

2. **Seletor por UF**:
   - Alterar a UF para `SP`, `RJ`, `MG`, `GO`, `DF`.
   - Verificar o carregamento dos municípios da UF na dropdown auxiliar.
   - Selecionar um município e checar se o input `codigo_municipio` é atualizado corretamente.

3. **Busca por CEP**:
   - Inserir CEPs de teste (ex: `70040-010`, `01001-000`).
   - Confirmar se todos os campos de endereço, UF e IBGE são preenchidos corretamente.
