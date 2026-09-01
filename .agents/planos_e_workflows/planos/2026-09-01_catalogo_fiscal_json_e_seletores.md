# Plano de Implementação: Catálogo Fiscal Padronizado (JSON) e Seletores Inteligentes

Criar uma estrutura padronizada em JSON com os CNAEs e Atividades Municipais oficiais da empresa (extraídos da Ficha Cadastral da SEFIN DF) e integrar seletores inteligentes com auto-preenchimento nos formulários de cadastro e edição de serviços e contratos.

---

## Modificações Propostas

### 1. Catálogo de Dados em JSON & Helper PHP
- `dinovatech/data/fiscal_catalog.json`: Estrutura JSON com os CNAEs e Atividades Municipais oficiais extraídos da Ficha Cadastral SEFIN DF.
- `dinovatech/helpers/FiscalCatalogHelper.php`: Helper PHP para leitura, listagem e busca rápida no catálogo JSON.

### 2. Formulário de Serviços (`dinovatech/servico_form.php`)
- Inserção de seletores com busca e descrições humanas para Atividade Municipal / Item LC 116 e CNAE.
- Auto-preenchimento de `item_lista_servico`, `codigo_tributacao_municipio`, `aliquota_iss` (2.00%) e CNAE correspondente.

### 3. Formulário de Contratos (`dinovatech/contrato_form.php`)
- Inserção dos mesmos seletores na aba fiscal de contratos.
