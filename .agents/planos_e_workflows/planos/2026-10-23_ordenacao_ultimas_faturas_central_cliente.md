# Plano de Implementação: Correção da Ordenação das Faturas na Central do Cliente

**Data**: 2026-10-23  
**Status**: Proposto  

---

## 1. Objetivo
Garantir que a seção **"Últimas Faturas"** na Central do Cliente (`cliente/index.php`) exiba sempre as faturas mais recentes (ordem cronológica decrescente), corrigindo a ordenação tanto no backend (`app.php`) quanto no frontend (`index.php`), mantendo a integridade da identificação da próxima fatura pendente para pagamento.

---

## 2. Ações Planejadas

### Etapa 1: Backend (`dinovatech/app.php`)
- No case `get_cliente_dashboard_data`:
  - Atualizar a query `$qFaturas` para `ORDER BY F.data_vencimento DESC, F.data_emissao DESC, F.id_fatura DESC`.
  - Refinar a lógica de seleção de `$proximaFaturaPendente` para escolher a fatura com status diferente de 'Liquidada' que tiver a data de vencimento mais próxima (menor `data_vencimento`), desvinculando-a da ordenação principal da lista.

### Etapa 2: Frontend (`cliente/index.php`)
- Na função `renderFaturasBreveList(faturas)`:
  - Ordenar as faturas prioritariamente pelas mais recentes cronologicamente (`data_vencimento DESC`, `data_emissao DESC`, `id_fatura DESC`), mantendo destaque visual de status (aberta/atrasada/paga).
- Na função `renderFaturasTabs(faturas)`:
  - Garantir que a aba "Em Aberto" ordene por urgência de vencimento (vencimento mais próximo primeiro).
  - Garantir que a aba "Pagas" continue ordenada pelas liquidadas mais recentes primeiro.

---

## 3. Critérios de Validação
- O card "Últimas Faturas" exibirá as faturas emitidas mais recentemente (mês atual e anteriores imediatos).
- O banner "Próxima Fatura" continuará apontando a fatura em aberto com vencimento mais imediato ou vencida mais urgente.
