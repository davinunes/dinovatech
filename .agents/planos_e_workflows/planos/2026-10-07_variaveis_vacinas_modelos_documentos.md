# Plano de Implementação: Variáveis Estruturadas de Vacinas em Modelos de Documentos

**Data:** 2026-10-07  
**Módulo:** Vet / Documentos (`AppHelper.php`, `app.php`, `documento_print.php`, `modelos_documentos.php`)

---

## 1. Alterações no Backend (`helpers/AppHelper.php`)

- Adicionar o método estático `public static function gerarVariaveisVacinas($link, $id_pet): array`.
- Mapeamento das tags de substituição individuais:
  - `{{vacina_v4_data}}`, `{{vacina_v4_proxima}}`, `{{vacina_v4_lote}}`
  - `{{vacina_felv_data}}`, `{{vacina_felv_proxima}}`, `{{vacina_felv_lote}}`
  - `{{vacina_antirrabica_data}}`, `{{vacina_antirrabica_proxima}}`, `{{vacina_antirrabica_lote}}`
  - E seus respectivos aliases em caixa alta (`{{VACINA_V4_DATA}}`, etc.).
- Construção da tabela HTML dinâmica em `{{tabela_vacinas}}` e `{{TABELA_VACINAS}}`.
- Construção do formato em parágrafo em `{{paragrafo_vacinas}}` e `{{PARAGRAFO_VACINAS}}`, aplicando a regra de omitir linhas com valores vazios/nulos.

---

## 2. Alterações nas Ações do Backend (`app.php`)

- **`case 'get_modelo_vars_preview':`**
  - Obter `$id_pet` a partir de `$dados['id_pet']` (do atendimento) ou diretamente via `$_POST['id_pet']`.
  - Executar `AppHelper::gerarVariaveisVacinas($link, $id_pet)` e mesclar no array `$vars`.

- **`case 'save_document_emitted':`**
  - Obter `$id_pet` a partir de `$dados['id_pet']` (do atendimento).
  - Executar `AppHelper::gerarVariaveisVacinas($link, $id_pet)` e mesclar no array `$vars`.

---

## 3. Alterações na Emissão/Impressão (`modules/Vet/documento_print.php`)

- Ao carregar dados do atendimento (`$id_atendimento`), extrair `$id_pet = $dados['id_pet']`.
- Executar `AppHelper::gerarVariaveisVacinas($link, $id_pet)` e mesclar no mapa de substituição `$vars`.

---

## 4. Alterações na Interface do Editor (`modules/Vet/modelos_documentos.php`)

- No modal de edição de modelos, na seção **Vet / Pet**, adicionar botões para inserção rápida no TinyMCE:
  - `Tabela de Vacinas` (`{{tabela_vacinas}}`)
  - `Parágrafo de Vacinas` (`{{paragrafo_vacinas}}`)
  - `V4 Data` (`{{vacina_v4_data}}`), `V4 Próxima` (`{{vacina_v4_proxima}}`), `V4 Lote` (`{{vacina_v4_lote}}`)
  - `FeLV Data` (`{{vacina_felv_data}}`), `FeLV Próxima` (`{{vacina_felv_proxima}}`), `FeLV Lote` (`{{vacina_felv_lote}}`)
  - `Antirrábica Data` (`{{vacina_antirrabica_data}}`), `Antirrábica Próxima` (`{{vacina_antirrabica_proxima}}`), `Antirrábica Lote` (`{{vacina_antirrabica_lote}}`)

---

## 5. Passos de Validação

1. Testar sintaxe PHP nos arquivos modificados.
2. Garantir que, quando o Pet possui vacinas cadastradas, as tags individuais, tabela e parágrafo são preenchidos corretamente.
3. Garantir que campos não preenchidos (ex: sem lote) não gerem a linha correspondente no parágrafo.
