# Walkthrough: Implementação do Módulo de Despesas e Gestão Financeira

**Data**: 2026-09-15  
**Objetivo**: Disponibilizar controle financeiro de saídas (contas a pagar), reestruturar o menu **Financeiro** em formato expansível (Receitas e Despesas), centralizar o acesso a **Fornecedores** e **Centros de Custo** embutidos na tela de despesas, prover automação de recorrências e parcelas, upload de documentos via Oracle Object Storage, consolidar o balanço mensal na Dashboard principal e integrar vencimentos à Agenda Geral.

---

## 1. Modificações Efetuadas

### 1.1 Banco de Dados (Migration SQL)
- **Arquivo**: `database/migrations/20260915_0001_create_modulo_despesas.sql`
  - Criada a tabela `Fornecedores` com campos completos (Razão Social, Nome Fantasia, CPF/CNPJ, Telefone, E-mail, Contato Responsável e Observações).
  - Criada a tabela `CentrosCusto` com cores personalizadas e carga inicial de categorias padrão (`Operacional / Insumos`, `Aluguel & Infraestrutura`, `Salários & Pró-labore`, `Serviços & Terceiros`, `Marketing & Vendas`, `Impostos & Tributos`).
  - Criada a tabela `Despesas` preparada para despesas **Avulsas**, **Parceladas** (`parcela_atual / total_parcelas`, `id_despesa_pai`) e **Recorrentes** (`recorrencia_ativa`, `dia_vencimento_recorrencia`).
  - Criada a tabela de junção `DespesaArquivos` vinculada à tabela `Arquivos` do ecossistema.

### 1.2 Backend REST/JSON
- **Arquivo**: `dinovatech/app.php`
  - **Fornecedores**: `listar_fornecedores`, `salvar_fornecedor`, `obter_fornecedor`, `excluir_fornecedor` (com proteção de integridade referencial).
  - **Centros de Custo**: `listar_centros_custo`, `salvar_centro_custo`, `excluir_centro_custo`.
  - **Despesas**:
    - `listar_despesas`: suporta filtros por mês, fornecedor, centro de custo, status e busca textual.
    - **Regra de Recorrência**: sincroniza automaticamente as regras ativas caso o mês aberto seja o corrente ou futuro; meses passados somente sincronizam se o botão for acionado explicitamente.
    - `obter_totais_despesas`: cálculo instantâneo dos cards de indicadores (Aberto, Pago, Atrasado, Geral).
    - `salvar_despesa`: suporta criação de despesa avulsa, geração em lote de parcelas mensais (`1/N` a `N/N`) e matriz de recorrência contínua.
    - `sincronizar_recorrencias_mes`: gera despesas do mês selecionado respeitando o dia de vencimento e evitando duplicidades.
    - `liquidar_despesa`: baixa com registro de data, valor efetivo pago e forma de pagamento.
    - `upload_anexo_despesa`: integração cURL PUT com Oracle Object Storage (com fallback para pasta local).
  - **Balanço Consolidado**: `get_dashboard_stats` enriquecido com as métricas de receitas e despesas.

### 1.3 Navegação e Menus
- **Arquivo**: `dinovatech/components/sidebar.php`
  - Substituído o link inativo pelo menu expansível **Financeiro**:
    - Submenu **Receitas** (`receitas.php`): foco em contas a receber, faturas pagas vs em aberto e faturamento.
    - Submenu **Despesas** (`despesas.php`): dashboard de contas a pagar.

### 1.4 Dashboard de Despesas
- **Arquivo**: `dinovatech/despesas.php`
  - Layout limpo em Tailwind CSS.
  - Acesso direto sem poluir o menu lateral para gerenciar **Fornecedores** e **Centros de Custo** via modais dedicados.
  - Botão **Sincronizar Mês** para acionar a geração de contas recorrentes sob demanda.
  - Modal de **Lançamento de Despesa** com abas: Avulsa, Parcelada e Recorrente.
  - Modal de **Liquidação** com preenchimento de valor pago e upload imediato do comprovante.
  - Modal de **Anexos** para visualizar, baixar e enviar múltiplos documentos (Boletos, NF, Faturas, Comprovantes).

### 1.5 Dashboard de Receitas
- **Arquivo**: `dinovatech/receitas.php`
  - Visão especializada para recebimentos e faturamento: indicadores de recebido, a receber e inadimplência acumulada, gráfico mensal e tabela de faturas.

### 1.6 Balanço na Dashboard Principal
- **Arquivo**: `dinovatech/dashboard.php`
  - Substituída a visualização estrita de receitas por **4 Cards de Balanço Consolidado**:
    1. **Receitas (Pagas)** + Subtotal a Receber
    2. **Despesas (Pagas)** + Subtotal a Pagar
    3. **Saldo Realizado (Caixa)**: Superávit (verde) ou Déficit (vermelho)
    4. **Saldo Previsto (Mês)**: Projeção de fluxo de caixa

### 1.7 Integração com a Agenda Geral
- **Arquivos**: `dinovatech/modules/Agenda/api.php` e `dinovatech/modules/Agenda/dashboard.php`
  - Consulta despesas vencendo no intervalo visível do calendário.
  - Eventos destacados: `💸 [Pagar] R$ ... - Fornecedor` (vermelho para aberto, verde para liquidado).
  - Checkbox alternador `[x] Vencimento Despesas` no cabeçalho da Agenda para ligar/desligar a visualização a qualquer momento.

---

## 2. Instruções de Verificação e Homologação

1. **Executar a Migration**:
   - Aplique a migration `20260915_0001_create_modulo_despesas.sql` para criar as tabelas `Fornecedores`, `CentrosCusto`, `Despesas` e `DespesaArquivos`.
2. **Navegação**:
   - Abra o menu **Financeiro** e teste os submenus **Receitas** e **Despesas**.
3. **Fornecedores e Centros de Custo**:
   - Teste a criação/edição embutida na tela de despesas.
4. **Despesas (Avulsa, Parcelada e Recorrente)**:
   - Cadastre uma despesa avulsa, uma parcelada e uma recorrente.
   - Teste a sincronização de recorrências para o mês selecionado.
5. **Liquidação e Comprovantes**:
   - Efetue a baixa de uma despesa e anexe um comprovante de pagamento.
6. **Balanço e Agenda**:
   - Verifique o balanço consolidado na `dashboard.php` e os vencimentos plotados na Agenda Geral.
