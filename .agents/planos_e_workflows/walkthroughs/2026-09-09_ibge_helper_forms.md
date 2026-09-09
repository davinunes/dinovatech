# Walkthrough / Entregas: Helper de Municípios IBGE e Reorganização Visual do Layout

- **Data**: 09/09/2026
- **Status**: Concluído e Reorganizado

---

## Alterações de Layout e UX

Reestruturação dos formulários de cadastro de clientes ([`cliente_form.php`](file:///e:/DEV/dinovatech/dinovatech/cliente_form.php)) e configuração fiscal ([`config_fiscal.php`](file:///e:/DEV/dinovatech/dinovatech/config_fiscal.php)) em um grid organizado de **4 colunas por linha**:

1. **Linha 1**: `CEP` (1 col), `UF` (1 col), `Cód. Mun. IBGE` (2 cols + badge de cidade).
2. **Linha 2**: `Buscar e Selecionar Município pela UF` (4 cols em bloco destacado `bg-slate-50`).
3. **Linha 3**: `Logradouro / Rua` (3 cols), `Número` (1 col).
4. **Linha 4**: `Bairro` (2 cols), `Complemento` (2 cols).
5. **Seção Separada de Regime Tributário** (em `config_fiscal.php`).

---

## Módulo JS `dinovatech/helpers/ibge_helper.js`

- Mantido módulo `window.AppIbge` com consulta assíncrona ao IBGE e ViaCEP, preenchendo automaticamente os campos reorganizados.
