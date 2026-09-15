# Plano de Implementação: Módulo de Despesas e Financeiro (Contas a Pagar & Receber)

**Data**: 2026-09-15  
**Autor**: Antigravity  
**Status**: Proposto / Aguardando Aprovação  

---

## 1. Visão Geral do Módulo

O objetivo é estruturar o módulo de **Despesas (Contas a Pagar)** integrado ao ecossistema do Dinovatech, reorganizando o menu lateral sob a guia expansível **Financeiro** (Receitas e Despesas), provendo gerenciamento embutido de **Fornecedores** e **Centros de Custo** sem sobrecarregar a navegação lateral, automatizando despesas **Avulsas**, **Parceladas** e **Recorrentes**, suporte a anexos (boletos, notas, faturas, comprovantes) via Oracle Object Storage, liquidação de contas, consolidação do balanço mensal na Dashboard principal e exibição de vencimentos na Agenda Geral.

---

## 2. Modelagem do Banco de Dados (Migration SQL)

**Arquivo**: `database/migrations/20260915_0001_create_modulo_despesas.sql`

1. **`Fornecedores`**:
   - `id_fornecedor`, `razao_social`, `nome_fantasia`, `cpf_cnpj`, `telefone`, `email`, `contato_responsavel`, `observacoes`, `ativo`, `created_at`, `updated_at`.
2. **`CentrosCusto`**:
   - `id_centro_custo`, `nome`, `descricao`, `cor`, `ativo`, `created_at`.
   - Carga inicial: Operacional/Insumos, Aluguel & Infraestrutura, Salários & Pró-labore, Serviços & Terceiros, Marketing & Vendas, Impostos & Tributos.
3. **`Despesas`**:
   - `id_despesa`, `id_fornecedor`, `id_centro_custo`, `descricao`, `tipo` (`avulsa`, `parcelada`, `recorrente`), `status` (`Em Aberto`, `Liquidada`, `Cancelada`), `data_competencia` (`YYYY-MM`), `data_vencimento`, `data_pagamento`, `forma_pagamento`, `valor`, `valor_pago`, `numero_documento`, `observacoes`.
   - Vínculo de parcelas: `id_despesa_pai`, `parcela_atual`, `total_parcelas`.
   - Matriz recorrente: `recorrencia_ativa`, `dia_vencimento_recorrencia`.
4. **`DespesaArquivos`**:
   - `id_vinculo`, `id_despesa`, `id_arquivo`, `tipo_documento` (`Boleto`, `Nota Fiscal`, `Fatura`, `Comprovante`, `Outro`), `created_at`.
   - Relacionada com a tabela `Arquivos` do sistema (upload OCI S3).

---

## 3. Estrutura de Navegação (Sidebar)

**Arquivo**: `dinovatech/components/sidebar.php`
- Criação do menu expansível **Financeiro**:
  - Submenu **Receitas** (`receitas.php`): contas a receber, faturas pagas/abertas, total faturado e liquidação.
  - Submenu **Despesas** (`despesas.php`): painel de contas a pagar, filtros, fornecedores, centros de custo e liquidação.

---

## 4. Telas e Interfaces

### 4.1 Dashboard de Despesas (`dinovatech/despesas.php`)
- **Barra Superior**:
  - Filtro por mês/ano de competência (`YYYY-MM`), Centro de Custo e Status.
  - Botão ➕ **Nova Despesa** (modal para avulsa, parcelada ou recorrente).
  - Botão 🏢 **Fornecedores** (abre modal para gestão rápida de fornecedores sem sair da página).
  - Botão 🏷️ **Centros de Custo** (abre modal para personalização e cadastro de centros de custo).
  - Botão 🔄 **Sincronizar Recorrências** (dispara a geração do mês em tela caso ainda não gerado).
  - Botão 📋 **Regras Recorrentes** (visualização e edição das contas que se repetem todo mês).
- **Cards de Indicadores do Mês**:
  - Total a Pagar no Mês, Total Pago no Mês, Total em Atraso e Previsão Total.
- **Tabela de Despesas**:
  - Data de Vencimento, Fornecedor, Centro de Custo (com badge colorido), Descrição / Parcela, Valor, Status (Em Aberto / Liquidada / Atrasada), Anexos, Ações (Liquidar, Editar, Anexar, Cancelar).
- **Modais**:
  - Modal de Nova Despesa (com abas/seleção de Avulsa, Parcelada e Recorrente).
  - Modal de Baixa/Liquidação (com preenchimento de data, valor pago, forma de pagamento e anexo de comprovante).
  - Modal de Gerenciamento de Anexos (upload e download de arquivos).
  - Modal de Fornecedores e Modal de Centros de Custo.

### 4.2 Nova Tela de Receitas (`dinovatech/receitas.php`)
- Focada exclusivamente em contas a receber (faturas pagas vs faturas em aberto do mês, inadimplência e controle de recebimentos).

### 4.3 Dashboard Principal (`dinovatech/dashboard.php`)
- Incorporação dos cards de **Balanço Financeiro do Mês**:
  - Receitas Realizadas vs Despesas Realizadas = **Saldo Realizado (Caixa)**.
  - Receitas Previstas vs Despesas Previstas = **Saldo Previsto**.

### 4.4 Agenda Geral (`dinovatech/modules/Agenda/`)
- Atualização da API (`modules/Agenda/api.php`) para retornar eventos de vencimento de despesas (`💸 [Pagar] R$ Valor - Fornecedor`).
- Adição de filtro alternador na interface da Agenda (`modules/Agenda/dashboard.php`) para ligar/desligar visualização de despesas.

---

## 5. Backend (`dinovatech/app.php`)

Implementação das actions:
- `listar_fornecedores`, `salvar_fornecedor`, `excluir_fornecedor`
- `listar_centros_custo`, `salvar_centro_custo`, `excluir_centro_custo`
- `listar_despesas`, `obter_totais_despesas`, `salvar_despesa`
- `sincronizar_recorrencias_mes`, `liquidar_despesa`, `cancelar_despesa`, `excluir_despesa`
- `upload_anexo_despesa`, `listar_anexos_despesa`, `excluir_anexo_despesa`

---

## 6. Verificação e Testes

1. Validar criação das tabelas via migration SQL.
2. Cadastrar fornecedor e centro de custo.
3. Cadastrar despesa avulsa com upload de boleto e efetuar liquidação com comprovante.
4. Cadastrar despesa parcelada (ex: 3 parcelas) e verificar a geração dos vencimentos subsequentes.
5. Cadastrar despesa recorrente (ex: aluguel) e rodar a sincronização mensal.
6. Verificar o balanço mensal consolidado na `dashboard.php`.
7. Verificar a plotagem dos vencimentos na Agenda Geral (`modules/Agenda/dashboard.php`).
