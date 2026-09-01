# Walkthrough: Catálogo Fiscal em JSON e Seletores Inteligentes

Implementamos o catálogo fiscal padronizado em JSON baseado na **Ficha Cadastral Oficial da SEFIN DF** e integramos seletores inteligentes com auto-preenchimento e alíquota de 2,00% nos formulários de **Serviços** e **Contratos**.

---

## 🛠️ O que foi implementado

### 1. Catálogo Fiscal Padronizado ([fiscal_catalog.json](file:///e:/DEV/dinovatech/dinovatech/data/fiscal_catalog.json))
- Arquivo centralizado contendo:
  - **CNAEs da Empresa:** Lista completa (CNAE Principal `6204000` e Secundários `6201501`, `6202300`, `6203100`, `6209100`, `6311900`, `8599603`, etc.) com descrições oficiais.
  - **Atividades Municipais:** Mapeamento de Código de Tributação, Item LC 116/03, Alíquotas oficiais (**2,00%** e 5,00%), CNAE sugerido e nomes amigáveis para cada serviço.

### 2. Helper PHP ([FiscalCatalogHelper.php](file:///e:/DEV/dinovatech/dinovatech/helpers/FiscalCatalogHelper.php))
- Métodos auxiliares `getCatalog()`, `getCnaes()`, `getAtividades()` e `getAtividadeByCodigo()` para leitura do JSON de forma desacoplada e personalizável por instância.

### 3. Seletores Inteligentes no Cadastro de Serviços ([servico_form.php](file:///e:/DEV/dinovatech/dinovatech/servico_form.php))
- Seletor de **Atividade Municipal / Item LC 116** com nomes descritivos.
- Seletor de **CNAE Autorizado** com classificação (Principal / Secundário).
- **Auto-Preenchimento Instantâneo:** Ao selecionar uma atividade (ex: *[105 - LC 01.05] Licenciamento de software*):
  - `Item LC 116/03`: `01.05`
  - `Cód. Tributação Municipal`: `105`
  - `Alíquota ISS`: `2.00%` (ajustado de 2.01% para a alíquota oficial)
  - `CNAE`: Seleciona automaticamente o CNAE compatível (`6202300`).

### 4. Seletores Inteligentes no Cadastro de Contratos ([contrato_form.php](file:///e:/DEV/dinovatech/dinovatech/contrato_form.php))
- Mesma experiência intuitiva de auto-preenchimento nos parâmetros fiscais de contratos e recorrências.
