# Walkthrough: Desativação de Serviços com Abas e Regra de Vigência de Contratos

Implementamos a funcionalidade de desativação de serviços no catálogo com divisão em abas dedicadas (**Serviços Ativos** e **Serviços Desativados**), acompanhada pela regra de negócio que impede a desativação de serviços que estejam vinculados a contratos em vigência.

---

## 1. O que foi implementado

### 1.1. Banco de Dados & Migração
- **Arquivo**: [20260901_0001_add_ativo_to_servicos.sql](file:///e:/DEV/dinovatech/database/migrations/20260901_0001_add_ativo_to_servicos.sql)
- Adição da coluna `ativo TINYINT(1) NOT NULL DEFAULT 1` e do índice `idx_servicos_ativo` na tabela `Servicos`.
- Script seguro e idempotente com verificação no `INFORMATION_SCHEMA`.

### 1.2. Regra de Negócio & Backend
- **Arquivo**: [app.php](file:///e:/DEV/dinovatech/dinovatech/app.php)
- **Ação `alterar_status_servico` / `toggle_status_servico`**:
  - Ao tentar desativar um serviço (`ativo = 0`), o backend consulta a tabela `Recorrencias` buscando contratos em vigência:
    ```sql
    SELECT R.id_recorrencia, C.nome AS nome_cliente, R.data_inicio_cobranca, R.data_fim_cobranca 
    FROM Recorrencias R 
    JOIN Clientes C ON R.id_cliente = C.id_cliente 
    WHERE R.id_servico = '$id_servico' 
      AND (R.data_fim_cobranca IS NULL OR R.data_fim_cobranca >= CURDATE())
    ```
  - Se houver contratos em vigência, a requisição é **rejeitada** e retorna uma mensagem informativa contendo a quantidade de contratos e os nomes dos clientes impactados.
  - Se não houver contratos vigentes, o serviço é atualizado para `ativo = 0`.
  - Permite a reativação imediata (`ativo = 1`).
- **Ações `criar_servico` e `editar_servico`**:
  - Tratamento do campo `ativo` com a mesma validação no salvamento do formulário.
- **Filtros e Autocomplete**:
  - `buscar_servicos` e `get_servicos` atualizados para priorizar/filtrar serviços ativos (`WHERE ativo = 1 OR ativo IS NULL`).

### 1.3. Interface de Gestão de Serviços em Abas
- **Arquivo**: [servicos.php](file:///e:/DEV/dinovatech/dinovatech/servicos.php)
- Criação de navegação por abas:
  - **Aba "Serviços Ativos"**: Contém badge com total de ativos, listagem com layout responsivo (tabela desktop e cards mobile), botão de "Editar" e botão de ação rápida para "Desativar".
  - **Aba "Serviços Desativados"**: Contém badge com total de inativos, estilização diferenciada (tons de cinza e badge 'Inativo'), botão de "Editar" e botão de ação rápida para "Reativar".
- Alertas dinâmicos com retorno amigável e tratamento claro de bloqueios por contratos.

### 1.4. Formulários e Seletores de Novos Cadastros
- **Arquivo**: [servico_form.php](file:///e:/DEV/dinovatech/dinovatech/servico_form.php)
  - Inclusão do seletor "Status do Catálogo" (Ativo / Desativado) e badge visual de status no cabeçalho.
- **Arquivo**: [contrato_form.php](file:///e:/DEV/dinovatech/dinovatech/contrato_form.php)
  - O dropdown de seleção de serviços para contratos agora filtra apenas serviços ativos, preservando o serviço atual caso seja edição de um contrato existente.
- **Arquivos**: [servicos_prestados.php](file:///e:/DEV/dinovatech/dinovatech/servicos_prestados.php) e [pacote_form.php](file:///e:/DEV/dinovatech/dinovatech/modules/Vet/pacote_form.php)
  - Dropdowns ajustados para listar apenas serviços ativos.

---

## 2. Como Testar / Validar

1. **Executar a Migração**:
   - Rodar a migration [20260901_0001_add_ativo_to_servicos.sql](file:///e:/DEV/dinovatech/database/migrations/20260901_0001_add_ativo_to_servicos.sql) no banco de dados.
2. **Navegar em Serviços ([servicos.php](file:///e:/DEV/dinovatech/dinovatech/servicos.php))**:
   - Acessar o menu *Serviços* e alternar entre as abas *Serviços Ativos* e *Serviços Desativados*.
3. **Testar Desativação com Contrato Vigente**:
   - Tentar desativar um serviço que possua contrato ativo.
   - Observar o alerta de impedimento bloqueando a operação e listando os clientes vinculados.
4. **Testar Desativação e Reativação Livre**:
   - Desativar um serviço avulso sem contratos ativos e verificar se ele é movido para a aba *Serviços Desativados*.
   - Clicar em *Reativar* e verificar seu retorno à aba *Serviços Ativos*.
5. **Testar Novo Contrato ([contrato_form.php](file:///e:/DEV/dinovatech/dinovatech/contrato_form.php))**:
   - Abrir a tela de novo contrato e verificar que serviços desativados não são exibidos no select de serviços.
