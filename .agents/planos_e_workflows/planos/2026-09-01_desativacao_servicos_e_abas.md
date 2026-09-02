# Plano de Implementação: Desativação de Serviços, Separação em Abas e Bloqueio por Vigência de Contrato

Permitir que serviços sejam desativados no sistema, separando os serviços **Ativos** e **Desativados** em abas dedicadas, respeitando a regra de negócio que impede a desativação de serviços vinculados a contratos (recorrências) em vigência.

---

## 1. Visão Geral e Regras de Negócio

1. **Campo de Status (`ativo`)**:
   - Adição da coluna `ativo TINYINT(1) NOT NULL DEFAULT 1` na tabela `Servicos`.
   - Migration SQL segura e idempotente em `database/migrations/20260901_0001_add_ativo_to_servicos.sql`.
2. **Regra de Bloqueio de Desativação**:
   - Antes de desativar qualquer serviço (seja pela listagem rápida ou pelo formulário de edição), o sistema verifica se o serviço possui vínculo na tabela `Recorrencias` com vigência aberta/ativa (`data_fim_cobranca IS NULL OR data_fim_cobranca >= CURDATE()`).
   - Se houver contrato vigente, a ação é **bloqueada** e uma mensagem clara de erro informa os contratos/clientes impeditivos.
3. **Interface em Abas (`servicos.php`)**:
   - Aba **"Serviços Ativos"** com contador de registros, ações de editar e botão para **Desativar**.
   - Aba **"Serviços Desativados"** com contador de registros, estilização diferenciada e botão para **Reativar**.
4. **Formulário de Cadastro/Edição (`servico_form.php`)**:
   - Exibição e controle do campo de status Ativo/Desativado.
5. **Filtros em Novos Cadastros**:
   - Em telas como novos contratos (`contrato_form.php`) e seletores de serviços ativos, exibir apenas serviços com `ativo = 1` para evitar novas contratações de serviços inativos.

---

## 2. Mudanças Propostas

### Banco de Dados (Migrations)

#### [NEW] [20260901_0001_add_ativo_to_servicos.sql](file:///e:/DEV/dinovatech/database/migrations/20260901_0001_add_ativo_to_servicos.sql)
- Criação do script de migration idempotente para adicionar a coluna `ativo` (`TINYINT(1) NOT NULL DEFAULT 1`) e respectivo índice na tabela `Servicos`.

---

### Backend (`dinovatech/app.php`)

#### [MODIFY] [app.php](file:///e:/DEV/dinovatech/dinovatech/app.php)
- **`alterar_status_servico`** / **`toggle_servico_ativo`**:
  - Nova ação no backend para desativar ou reativar um serviço via AJAX.
  - Ao tentar desativar (`ativo = 0`), executa query em `Recorrencias` + `Clientes` para identificar contratos em vigência (`data_fim_cobranca IS NULL OR data_fim_cobranca >= CURDATE()`).
  - Se existirem contratos vigentes, rejeita a requisição retornando `{ success: false, message: "Não é possível desativar o serviço pois existem X contrato(s) em vigência vinculado(s) a este serviço (...)" }`.
  - Se não houver impedimentos, atualiza `Servicos.ativo = 0` (ou `1`).
- **`criar_servico`** e **`editar_servico`**:
  - Tratar o campo `ativo` (padrão 1 ao criar; no update, se alterado para 0, aplicar a mesma validação de vigência de contratos).
- **Consultas de autocomplete/listagem rápida de serviços**:
  - Garantir que buscas para novas seleções (ex: `buscar_servicos`, etc.) priorizem ou filtrem serviços ativos.

---

### Frontend / Telas

#### [MODIFY] [servicos.php](file:///e:/DEV/dinovatech/dinovatech/servicos.php)
- Implementação da navegação por abas:
  - **Aba "Serviços Ativos"** (Tab padrão com badge com contagem total de ativos).
  - **Aba "Serviços Desativados"** (Tab secundária com badge com contagem total de inativos).
- Inclusão do botão de **Desativar** com modal/confirmação rápida para serviços ativos.
- Inclusão do botão de **Reativar** para serviços desativados.
- Tratamento AJAX com feedback instantâneo e mensagens informativas em caso de bloqueio por contrato.

#### [MODIFY] [servico_form.php](file:///e:/DEV/dinovatech/dinovatech/servico_form.php)
- Inclusão do campo de controle de status (Ativo / Desativado) com aviso contextual caso o serviço possua contratos vigentes.

#### [MODIFY] [contrato_form.php](file:///e:/DEV/dinovatech/dinovatech/contrato_form.php)
- Ajustar a query de listagem de serviços para exibir apenas serviços ativos (`WHERE ativo = 1` ou ordenar mantendo o serviço atual selecionado se for edição).

---

## 3. Plano de Verificação

### Testes Manuais e Verificação de Regras
1. **Verificação de Migration**:
   - Validar sintaxe SQL da migration para MariaDB 10.x.
2. **Cenário 1 - Desativação Bloqueada**:
   - Tentar desativar um serviço que possua contrato com `data_fim_cobranca` no futuro ou `NULL`.
   - Confirmar se a operação é bloqueada com mensagem informativa citando os contratos vigentes.
3. **Cenário 2 - Desativação Permitida**:
   - Desativar um serviço sem contratos ou com contratos já expirados (`data_fim_cobranca < CURDATE()`).
   - Confirmar se o serviço é movido para a aba "Serviços Desativados".
4. **Cenário 3 - Reativação**:
   - Reativar o serviço na aba "Serviços Desativados".
   - Confirmar retorno imediato para a aba "Serviços Ativos".
5. **Cenário 4 - Criação de Novo Contrato**:
   - Confirmar que serviços desativados não aparecem para novas assinaturas em `contrato_form.php`.
