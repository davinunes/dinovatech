# Raciocínio Diagnóstico: Ordenação Incorreta de Faturas na Central do Cliente

**Data**: 2026-10-23  
**Problema**: Na Central do Cliente (`cliente/index.php`), o bloco "Últimas Faturas" estava exibindo as faturas mais antigas em vez das mais recentes.

---

## 1. Causa Raiz Identificada

1. **Backend (`dinovatech/app.php` - case `get_cliente_dashboard_data`)**:
   - A query SQL de faturas (`$qFaturas`) realizava:
     ```sql
     ORDER BY F.data_vencimento ASC
     ```
   - O intuito original de quem escreveu era encontrar `$proximaFaturaPendente`, mas o efeito colateral foi que todo o array `$faturasList` retornava em ordem cronológica ascendente (do passado para o futuro).

2. **Frontend (`cliente/index.php` - função `renderFaturasBreveList`)**:
   - O card "Últimas Faturas" ordenava as faturas por:
     1. Status (Pendentes antes de Liquidadas).
     2. Maior valor antes de menor valor (`valB - valA`).
     3. Desempate: data de vencimento ascendente `(a.data_vencimento).localeCompare(b.data_vencimento)`.
   - Ao executar `faturasOrdenadas.slice(0, 5)`, faturas antigas de mesmo valor ou faturas de valores mais altos do passado eram exibidas em vez das faturas mais recentes do cliente.

## 2. Correções Propostas

1. **Backend (`dinovatech/app.php`)**:
   - Alterar a query `$qFaturas` para ordenar por:
     `ORDER BY F.data_vencimento DESC, F.data_emissao DESC, F.id_fatura DESC`.
   - Calcular `$proximaFaturaPendente` procurando a fatura não liquidada com o menor `data_vencimento` (ou seja, a mais urgente/próxima de vencer), garantindo que ela não dependa da ordem da lista retornada.

2. **Frontend (`cliente/index.php`)**:
   - Na função `renderFaturasBreveList(faturas)`:
     Ordenar as faturas para o card "Últimas Faturas" estritamente por ordem cronológica decrescente (mais recentes primeiro: `data_vencimento DESC`, `data_emissao DESC`, `id_fatura DESC`), ou mantendo prioridade para faturas em aberto recentes, mas sempre das mais recentes para as mais antigas.
   - Na aba "Em Aberto" (`renderFaturasTabs`):
     Garantir que as faturas em aberto sejam ordenadas por data de vencimento crescente (a que vence mais cedo em primeiro lugar), e que as faturas pagas continuem ordenadas da mais recente para a mais antiga.
