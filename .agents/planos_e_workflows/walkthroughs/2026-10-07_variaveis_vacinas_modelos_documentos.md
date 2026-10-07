# Walkthrough: Suporte Dinâmico a Tabela, Parágrafo e Variáveis de Vacinas em Modelos de Documentos

**Data:** 2026-10-07  
**Módulo:** Vet (`AppHelper.php`, `app.php`, `documento_print.php`, `modelos_documentos.php`)

---

## 1. Destaque Principal: Dinamismo Total dos Registros de Vacinas

Como o cadastro de vacinas é **100% dinâmico**, a solução foi focada em processar automaticamente todas as vacinas da carteira do paciente registradas no banco de dados para os seguintes blocos principais:

1. **`{{tabela_vacinas}}` / `{{TABELA_VACINAS}}` (Tabela Dinâmica HTML):**
   - Renderiza uma tabela HTML limpa e formatada com CSS inline adequada para WYSIWYG e impressão/PDF.
   - Lista dinamicamente **todas** as vacinas aplicadas ao Pet (com colunas **Vacina**, **Data Realizada**, **Próxima Dose** e **Lote/Part.**).

2. **`{{paragrafo_vacinas}}` / `{{PARAGRAFO_VACINAS}}` (Formato em Parágrafos):**
   - Gera um bloco textual dinâmico formatado por item para cada vacina aplicada ao Pet:
     - • **Nome da Vacina:** ...
     - • **Data de Realização:** ...
     - • **Data da Próxima Dose:** ...
     - • **Lote/Partícula (Part):** ...
   - **Regra de Omissão:** Se um campo (ex: lote ou data da próxima dose) não estiver preenchido no registro da vacinação, a respectiva linha é omitida do parágrafo.

3. **Geração Dinâmica de Tags Individuais:**
   - Além das tags pré-definidas (V4, FeLV, Antirrábica), a função converte dinamicamente o nome de qualquer vacina cadastrada em slug, gerando tags como `{{vacina_[nome_slug]_data}}`, `{{vacina_[nome_slug]_proxima}}` e `{{vacina_[nome_slug]_lote}}`.

---

## 2. Estrutura dos Arquivos Atualizados

- [`AppHelper.php`](file:///d:/dev/github/dinovatech/dinovatech/helpers/AppHelper.php): Adicionado método estático `AppHelper::gerarVariaveisVacinas($link, $id_pet)` com consulta SQL e montagem dinâmica dos blocos.
- [`app.php`](file:///d:/dev/github/dinovatech/dinovatech/app.php): Integrado em `get_modelo_vars_preview` e `save_document_emitted`.
- [`documento_print.php`](file:///d:/dev/github/dinovatech/dinovatech/modules/Vet/documento_print.php): Integrada a substituição nas telas de pré-visualização, impressão e PDF.
- [`modelos_documentos.php`](file:///d:/dev/github/dinovatech/dinovatech/modules/Vet/modelos_documentos.php): Adicionados os botões de atalho no editor TinyMCE.
