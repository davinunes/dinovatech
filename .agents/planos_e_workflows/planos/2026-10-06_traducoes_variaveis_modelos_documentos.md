# Plano de Implementação: Suporte Completo a Variáveis em Modelos de Documentos Contratuais

**Data:** 2026-10-06  
**Objetivo:** Garantir a tradução de todas as variáveis de documentos (incluindo `{{RAZAO_SOCIAL_CLIENTE}}`, `{{CNPJ_CLIENTE}}`, `{{IE_CLIENTE}}`, `{{ENDERECO_CLIENTE}}`, `{{MESES_VIGENCIA}}`, `{{DIA_VENCIMENTO}}`) ao emitir ou visualizar documentos vinculados a contratos/recorrências.

---

## 1. Alterações no Backend (`app.php`)

### 1.1 `case 'get_modelo_vars_preview':` (Ação AJAX para preview)
- **Consulta SQL:** Alterar `SELECT r.*, c.nome as nome_tutor...` para buscar todos os campos do cliente (`c.*`).
- **Composição de Endereço:** Gerar `$enderecoCompleto` concatenando Logradouro, Número, Complemento, Bairro, UF e CEP.
- **Cálculo de Vigência:** Adicionar lógica de cálculo para `$mesesVigencia` (com fallback para 12 meses).
- **Mapeamento de Aliases (`$vars`):** Adicionar aliases para `{{RAZAO_SOCIAL_CLIENTE}}`, `{{CNPJ_CLIENTE}}`, `{{CPF_CLIENTE}}`, `{{IE_CLIENTE}}`, `{{INSCRICAO_ESTADUAL_CLIENTE}}`, `{{IM_CLIENTE}}`, `{{INSCRICAO_MUNICIPAL_CLIENTE}}`, `{{MESES_VIGENCIA}}`, `{{VALOR_RECORRENTE}}`, etc.

### 1.2 `case 'save_document_emitted':` (Ação AJAX para salvar documento no histórico)
- Aplicar as mesmas melhorias na SQL, na formação do endereço completo, no cálculo de vigência e no mapa `$vars` que é gravado no banco de dados.

---

## 2. Alterações na Impressão/Geração de PDF (`modules/Vet/documento_print.php`)

- **Consulta SQL:** Incluir `c.*` na query de recorrências.
- **Composição do Endereço:** Gerar `$enderecoCompleto`.
- **Lógica do `$vars`:** Replicar exatamente o mesmo dicionário de variáveis com aliases para garantir paridade total entre pré-visualização, salvamento e geração de PDF / visualização para impressão.

---

## 3. Alterações na Interface de Gestão de Modelos (`modules/Vet/modelos_documentos.php`)

- Adicionar as novas tags de variáveis no painel lateral de atalhos ("Cliente / Tutor" e "Contrato / Recorrência"), permitindo ao usuário clicar e inserir facilmente:
  - `{{RAZAO_SOCIAL_CLIENTE}}`
  - `{{CNPJ_CLIENTE}}`
  - `{{IE_CLIENTE}}`
  - `{{MESES_VIGENCIA}}`

---

## 4. Passos de Verificação

1. Verificar se `documento_print.php` e `app.php` não contêm erros de sintaxe PHP.
2. Conferir que todas as 13 variáveis de `modelo2.txt` são devidamente substituídas quando vinculadas a um contrato (ID = 5).
