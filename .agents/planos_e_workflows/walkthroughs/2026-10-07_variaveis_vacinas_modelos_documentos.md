# Walkthrough: Suporte a Variáveis Estruturadas de Vacinas em Modelos de Documentos

**Data:** 2026-10-07  
**Módulo:** Vet (`AppHelper.php`, `app.php`, `documento_print.php`, `modelos_documentos.php`)

---

## 1. Resumo das Alterações Executadas

Foi implementada a geração automática e estruturada das variáveis de vacinas do paciente para utilização no editor de documentos WYSIWYG e na emissão de atestados/documentos a partir do prontuário (atendimento).

### 1.1 Novo Método `AppHelper::gerarVariaveisVacinas($link, $id_pet)`
- Localização: `dinovatech/helpers/AppHelper.php`
- Responsabilidade: Buscar os registros da carteira de vacinas do Pet no banco de dados e expor variáveis prontas para substituição.

### 1.2 Variáveis Disponibilizadas
1. **Tags Individuais (V4, FeLV, Antirrábica):**
   - `{{vacina_v4_data}}` / `{{vacina_v4_proxima}}` / `{{vacina_v4_lote}}`
   - `{{vacina_felv_data}}` / `{{vacina_felv_proxima}}` / `{{vacina_felv_lote}}`
   - `{{vacina_antirrabica_data}}` / `{{vacina_antirrabica_proxima}}` / `{{vacina_antirrabica_lote}}`
   - E variações correspondentes em caixa alta (ex: `{{VACINA_V4_DATA}}`).

2. **Bloco de Tabela Dinâmica (HTML):**
   - `{{tabela_vacinas}}` / `{{TABELA_VACINAS}}`: Renderiza uma tabela HTML limpa, formatada com CSS inline contendo as colunas **Vacina**, **Data Realizada**, **Próxima Dose** e **Lote/Part.**.

3. **Bloco em Formato de Parágrafo:**
   - `{{paragrafo_vacinas}}` / `{{PARAGRAFO_VACINAS}}` / `{{vacinas_paragrafo}}`: Renderiza itens formatados por vacina:
     - • **Nome da Vacina:** ...
     - • **Data de Realização:** ...
     - • **Data da Próxima Dose:** ...
     - • **Lote/Partícula (Part):** ...
   - **Regra de Omissão:** Linhas para campos não preenchidos (ex: sem lote ou sem data de próxima dose) são omitidas automaticamente.

### 1.3 Integrações nos Fluxos do Sistema
- **`app.php` (`get_modelo_vars_preview`)**: Mescla as variáveis de vacina no preview de substituição quando o atendimento/pet é selecionado.
- **`app.php` (`save_document_emitted`)**: Substitui as variáveis de vacinas antes de salvar o documento final no banco.
- **`modules/Vet/documento_print.php`**: Mescla as variáveis de vacinas para pré-visualização, impressão direta e exportação em PDF.
- **`modules/Vet/modelos_documentos.php`**: Adiciona os atalhos de tag de vacinas no painel auxiliar do editor para fácil inserção via clique pelo usuário.

---

## 2. Instruções de Validação pelo Usuário

1. Acesse o menu **Módulo Vet > Modelos de Documentos**.
2. Clique em **Editar** um modelo de atestado de sanidade ou crie um novo modelo.
3. No painel auxiliar de **Variáveis Disponíveis**, utilize as novas tags da seção **Vacinas (Carteira)**:
   - Clique em `Tabela de Vacinas` (`{{tabela_vacinas}}`) ou `Parágrafo de Vacinas` (`{{paragrafo_vacinas}}`) para inserir os blocos dinâmicos.
   - Ou insira as tags individuais como `{{vacina_v4_data}}`, `{{vacina_felv_proxima}}`, `{{vacina_antirrabica_lote}}`.
4. Ao emitir um documento a partir do prontuário de um paciente com vacinas registradas na carteira, verifique que as informações são substituídas corretamente na pré-visualização e no PDF final.
