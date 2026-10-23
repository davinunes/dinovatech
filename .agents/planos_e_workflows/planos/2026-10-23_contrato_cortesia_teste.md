# Plano de Implementação: Recurso de Cortesia e Teste em Contratos (Deploy SaaS sem Faturas)

**Data**: 2026-10-23  
**Status**: Proposto  

---

## 1. Objetivo
Permitir que um contrato de cliente seja classificado como **Comercial**, **Cortesia** ou **Teste**, viabilizando o deploy e gestão de instâncias SaaS dedicadas no K3s para clientes parceiros, demonstrações ou homologações, sem gerar faturas automáticas (cron) ou manuais no sistema financeiro.

---

## 2. Detalhamento das Etapas

### Etapa 1: Migração de Banco de Dados
- Criar migração idempotente: `database/migrations/20261023_0002_add_tipo_cobranca_to_recorrencias.sql`.
- Adicionar as colunas:
  - `tipo_cobranca` ENUM('COMERCIAL', 'CORTESIA', 'TESTE') NOT NULL DEFAULT 'COMERCIAL'
  - `motivo_cortesia` VARCHAR(255) NULL COMMENT 'Justificativa ou nota interna da cortesia/teste'

### Etapa 2: Blindagem no Motor de Faturamento
- Em `dinovatech/helpers/CronRecorrenciasHelper.php`:
  - Na query principal de busca de contratos ativos para faturamento, adicionar a cláusula:
    `AND (R.tipo_cobranca = 'COMERCIAL' OR R.tipo_cobranca IS NULL)`
  - Isso garante que nunca será gerada fatura (nem débito Pix) para contratos de Cortesia ou Teste.
- Em `dinovatech/app.php` na ação `incorporar_recorrencias_na_fatura`:
  - Adicionar o mesmo filtro para evitar incorporação indevida de itens em faturas avulsas.

### Etapa 3: Backend da API (`app.php`)
- Em `vincular_recorrencia` e `editar_recorrencia`:
  - Capturar `tipo_cobranca` e `motivo_cortesia`.
  - Flexibilizar a validação de valor:
    - Se for `COMERCIAL`: exige `valor_sugerido_recorrencia > 0`.
    - Se for `CORTESIA` ou `TESTE`: permite `valor_sugerido_recorrencia >= 0`.
  - Persistir as novas colunas no `INSERT` e `UPDATE` da tabela `Recorrencias`.

### Etapa 4: Interface do Formulário de Contrato (`contrato_form.php`)
- Inserir seletor intuitivo com cards de rádio ou botões de seleção de modalidade:
  - 💳 **Comercial (Padrão)**: Faturamento ativo, gera fatura mensal.
  - 🎁 **Cortesia**: 100% Isento, não gera faturas, ideal para parcerias ou SaaS grátis.
  - 🧪 **Teste / Trial**: Período de avaliação / homologação, não gera faturas durante o teste.
- Exibir campo contextual de "Justificativa / Motivo da Cortesia ou Teste" quando selecionado.
- Adicionar botões de atalho de prazo caso seja Teste (ex: 7 dias, 15 dias, 30 dias) para preencher automaticamente a `data_fim_cobranca`.
- Na aba "Pix Automático", orientar que para contratos sem cobrança o Pix Automático não se aplica.

### Etapa 5: Interface de Listagem de Contratos (`contratos.php`)
- Exibir badges estilizados na listagem:
  - `🎁 Cortesia` (badge verde/roxo elegante com ícone de presente).
  - `🧪 Teste` (badge âmbar com contagem de dias restantes se houver prazo).
  - Na coluna Valor: indicar `Isento (Cortesia)` ou `R$ 0,00 (Gratuito)` ou o valor referencial riscado com badge.
- Adicionar filtros rápidos por modalidade (Todos, Comerciais, Cortesias, Testes).

### Etapa 6: Central SaaS (`saas_tenants.php`)
- Na tabela de instâncias SaaS, exibir badge de modalidade do contrato ao lado do cliente/serviço.
- No cálculo do MRR total, somar apenas contratos `COMERCIAL` com valores faturáveis reais, evitando inflar o faturamento projetado.

---

## 3. Critérios de Aceite
1. Contratos cadastrados como "Cortesia" ou "Teste" com valor R$ 0,00 ou de tabela são salvos com sucesso.
2. A rotina do cron de faturamento (`gerar_faturas_recorrencias.php` / `CronRecorrenciasHelper`) ignora contratos de cortesia/teste.
3. A tela de contratos exibe claramente quais contratos são cortesias ou testes.
4. O provisionamento e deploy do SaaS no K3s funciona normalmente a partir do contrato marcado como cortesia/teste.
