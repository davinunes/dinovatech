# Raciocínio de Diagnóstico e Implementação: Variáveis Estruturadas de Vacinas para Modelos de Documentos

**Data:** 2026-10-07  
**Módulo:** Vet / Modelos de Documentos (`app.php`, `AppHelper.php`, `documento_print.php`, `modelos_documentos.php`)

---

## 1. Análise da Necessidade

O usuário precisa de uma solução no módulo Vet para disponibilizar variáveis estruturadas relativas à carteira de vacinas de um paciente (Pet) no editor de modelos de documentos (WYSIWYG TinyMCE).

### Requisitos Especificados:
1. **Variáveis Individuais (Tags de Substituição):**
   - V4: `{{vacina_v4_data}}`, `{{vacina_v4_proxima}}`, `{{vacina_v4_lote}}` (e aliases em caixa alta).
   - FeLV: `{{vacina_felv_data}}`, `{{vacina_felv_proxima}}`, `{{vacina_felv_lote}}` (e aliases em caixa alta).
   - Antirrábica: `{{vacina_antirrabica_data}}`, `{{vacina_antirrabica_proxima}}`, `{{vacina_antirrabica_lote}}` (e aliases em caixa alta).

2. **Bloco de Tabela Dinâmica (HTML):**
   - Tag global `{{tabela_vacinas}}` (e `{{TABELA_VACINAS}}`).
   - Renderização de uma tabela HTML limpa, formatada com CSS inline adequado para editores WYSIWYG e exportação em PDF/impressão.

3. **Bloco em Formato de Parágrafo:**
   - Tag global `{{paragrafo_vacinas}}` (e `{{PARAGRAFO_VACINAS}}` / `{{vacinas_paragrafo}}`).
   - Formato textual estruturado por item:
     - • Nome da Vacina: ...
     - • Data de Realização: ...
     - • Data da Próxima Dose: ...
     - • Lote/Partícula (Part): ...
   - **Regra Importante:** Omitir a linha caso o campo correspondente não esteja preenchido no registro da vacina.

---

## 2. Modelagem e Arquitetura da Solução

### 2.1 Método Centralizador em `AppHelper.php`
Criaremos o método estático `AppHelper::gerarVariaveisVacinas($link, $id_pet)`.
Este método:
1. Valida a conexão e o `$id_pet`.
2. Executa consulta SQL ordenando por `cv.data_aplicacao DESC, cv.id_carteira DESC` para pegar os registros de vacinação mais recentes.
3. Processa e mapeia as vacinas V4, FeLV e Antirrábica para preencher as tags individuais.
4. Constrói a tabela HTML dinâmica em `{{tabela_vacinas}}`.
5. Constrói a lista de parágrafos estruturados em `{{paragrafo_vacinas}}` filtrando linhas com valores vazios/nulos.

### 2.2 Integração nas Ações de Substituição
Integraremos a chamada em:
- `app.php` -> `get_modelo_vars_preview`: Para que as variáveis e o preview de vacinas funcionem na modal ao selecionar modelo em um prontuário/atendimento.
- `app.php` -> `save_document_emitted`: Para que a emissão e gravação de documentos com vacinas salve a substituição correta no histórico.
- `modules/Vet/documento_print.php`: Para a pré-visualização, impressão direta e geração em PDF do documento emitido.
- `modules/Vet/modelos_documentos.php`: Inclusão dos botões de tag no painel auxiliar "Vet / Pet" do editor TinyMCE.

---

## 3. Sanitização e Boas Práticas
- Utilização de `mysqli_real_escape_string` no `$id_pet`.
- Utilização de `htmlspecialchars` em todos os valores de texto vindos do banco de dados (nomes de vacinas, lotes) injetados no HTML.
- Tratamento de datas com checagem para evitar `0000-00-00` ou `null`.
