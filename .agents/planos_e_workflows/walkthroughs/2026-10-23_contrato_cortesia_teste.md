# Walkthrough: Implementação de Contratos como Cortesia e Teste (Deploy SaaS sem Faturamento)

**Data**: 2026-10-23  
**Status**: Concluído e Testado no Código  

---

## 1. Visão Geral da Funcionalidade
Implementamos o recurso completo para classificação de Contratos nas seguintes modalidades de faturamento:
1. **💳 Comercial (Padrão)**: Faturamento ativo com geração periódica de faturas, débitos de Pix Automático e contabilização no MRR.
2. **🎁 Cortesia (100% Isento)**: Totalmente isento de faturamento. Não gera faturas pelo Cron nem faturas manuais, permite valor R$ 0,00 ou referencial, e mantém o vínculo com instâncias SaaS K3s operando perfeitamente.
3. **🧪 Teste / Trial (Degustação)**: Período de avaliação de SaaS. Não gera cobranças durante a homologação e oferece botões de atalho (+7, +15, +30 dias) para controle da data de expiração.

---

## 2. Arquivos Criados e Alterados

| Arquivo | Descrição das Modificações |
|---|---|
| [`database/migrations/20261023_0002_add_tipo_cobranca_to_recorrencias.sql`](file:///d:/dev/github/dinovatech/database/migrations/20261023_0002_add_tipo_cobranca_to_recorrencias.sql) | Migração idempotente adicionando `tipo_cobranca` (ENUM), `motivo_cortesia` (VARCHAR) e índice na tabela `Recorrencias`. |
| [`dinovatech/helpers/CronRecorrenciasHelper.php`](file:///d:/dev/github/dinovatech/dinovatech/helpers/CronRecorrenciasHelper.php) | Blindagem na consulta de faturamento automático por cron: ignora expressamente contratos `CORTESIA` e `TESTE`. |
| [`dinovatech/app.php`](file:///d:/dev/github/dinovatech/dinovatech/app.php) | Flexibilização das validações de valor em `vincular_recorrencia` e `editar_recorrencia` (permitindo R$ 0,00 quando isento) e filtro na ação `incorporar_recorrencias_na_fatura`. |
| [`dinovatech/contrato_form.php`](file:///d:/dev/github/dinovatech/dinovatech/contrato_form.php) | Novo seletor visual em formato de cards (Comercial, Cortesia, Teste), banner explicativo, campo de motivo opcional, atalhos de prazo de teste e aviso contextual na aba Pix Automático. |
| [`dinovatech/contratos.php`](file:///d:/dev/github/dinovatech/dinovatech/contratos.php) | Badges estilizados de Cortesia e Teste, contadores de quantidade por modalidade e botões de filtro rápido instantâneo na listagem. |
| [`dinovatech/saas_tenants.php`](file:///d:/dev/github/dinovatech/dinovatech/saas_tenants.php) | Ajuste no cálculo de MRR para não inflar a receita com cortesias/testes, e exibição dos badges de modalidade junto aos Pods do K3s. |

---

## 3. Guia de Uso

### Criando um Contrato Cortesia ou Teste:
1. Acesse **Recorrência (Contratos)** &rarr; **Novo Contrato**.
2. Selecione o Cliente e o Serviço desejado (inclusive serviços do tipo SaaS K3s).
3. Na seção **Modalidade de Cobrança do Contrato**, clique em:
   - **Cortesia 🎁**: O valor é ajustado para R$ 0,00 e o sistema confirma que nenhuma cobrança será gerada.
   - **Teste / Trial 🧪**: Clique nos botões de atalho `+7 dias`, `+15 dias` ou `+30 dias` para definir o prazo de degustação.
4. Preencha as credenciais do SaaS K3s normalmente e salve.
5. O Pod e o Tenant serão vinculados e provisionados normalmente, sem gerar faturas no fechamento do mês.
