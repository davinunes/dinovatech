# Walkthrough: Correção de Tradução de Variáveis em Documentos Contratuais

**Data:** 2026-10-06  
**Status:** Concluído

---

## Resumo das Alterações

Identificamos por que as variáveis do modelo (`modelo2.txt`) não estavam sendo traduzidas quando vinculadas ao contrato (`contrato_form.php?id=5`) e corrigimos o mapeamento no backend e na renderização dos documentos.

### 1. Causa do Problema
1. **Nomes de Tags Sem Alias Mapeado:** O modelo utilizava `{{RAZAO_SOCIAL_CLIENTE}}`, `{{CNPJ_CLIENTE}}`, `{{IE_CLIENTE}}` e `{{MESES_VIGENCIA}}`, que não constavam no dicionário de substituição (`$vars`).
2. **Campos do Cliente Omitidos na SQL:** A consulta SQL no contexto de contratos trazia apenas nome, cpf_cnpj, endereço bruto, e-mail e telefone do cliente, omitindo Inscrição Estadual (`inscricao_estadual`), Inscrição Municipal (`inscricao_municipal`), Número, Bairro, Complemento, CEP e UF.
3. **Endereço Completo Incompleto:** `{{ENDERECO_CLIENTE}}` retornava apenas a rua sem número/bairro/UF/CEP.
4. **Dia de Vencimento:** `{{DIA_VENCIMENTO}}` não priorizava o campo `dia_vencimento` da tabela `Recorrencias`.

---

## 2. O que foi alterado

- **`dinovatech/app.php`:**
  - Atualizada a query de busca da recorrência em `get_modelo_vars_preview` e `save_document_emitted` para trazer todos os campos do cliente (`c.*`).
  - Adicionado suporte completo aos aliases:
    - `{{RAZAO_SOCIAL_CLIENTE}}` (Alias de `{{NOME_CLIENTE}}` / Razão Social)
    - `{{CNPJ_CLIENTE}}` / `{{CPF_CLIENTE}}` (Alias de `{{CPF_CNPJ_CLIENTE}}` formatado)
    - `{{IE_CLIENTE}}` / `{{INSCRICAO_ESTADUAL_CLIENTE}}` (Inscrição Estadual do Cliente)
    - `{{IM_CLIENTE}}` / `{{INSCRICAO_MUNICIPAL_CLIENTE}}` (Inscrição Municipal do Cliente)
    - `{{ENDERECO_CLIENTE}}` (Formatado com Logradouro, nº, complemento, Bairro, UF e CEP)
    - `{{MESES_VIGENCIA}}` (Duração calculada do contrato em meses, fallback 12)
    - `{{VALOR_RECORRENTE}}` (Alias de `{{VALOR_CONTRATO}}`)
    - `{{DATA_FIM}}` (Data final da cobrança)
  - `{{DIA_VENCIMENTO}}` agora utiliza com prioridade o campo `dia_vencimento` da tabela `Recorrencias`.

- **`dinovatech/modules/Vet/documento_print.php`:**
  - Replicado o mesmo dicionário expandido de variáveis para garantir que a impressão em tela e em PDF renderize todas as 13 tags de `modelo2.txt` perfeitamente.

- **`dinovatech/modules/Vet/modelos_documentos.php`:**
  - Adicionados botões de atalho no painel lateral do editor para `Razão Social`, `CNPJ Cliente`, `Insc. Estadual`, `Vigência (Meses)` e `Data Fim`.

---

## 3. Como Testar / Verificar

1. Acesse o contrato em `dinovatech/contrato_form.php?id=5`.
2. Vá até a aba **Documentos / Contratos**.
3. Selecione o modelo cadastrado (ex: modelo 2 / Contrato ISP).
4. Clique em **Gerar Documento (Impressão / PDF)** ou visualize o histórico.
5. Verifique se todas as tags (como `{{RAZAO_SOCIAL_CLIENTE}}`, `{{CNPJ_CLIENTE}}`, `{{IE_CLIENTE}}`, `{{VALOR_CONTRATO}}`, `{{DIA_VENCIMENTO}}`, `{{MESES_VIGENCIA}}`, `{{CIDADE_DATA}}`) agora aparecem preenchidas com os dados do cliente e do contrato.
