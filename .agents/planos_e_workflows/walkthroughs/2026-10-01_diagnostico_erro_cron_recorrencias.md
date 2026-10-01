# Walkthrough: Remoção do Override de Descrição no Item da Fatura & Script de Limpeza

**Data**: 2026-10-01  
**Módulo**: Faturas / CronRecorrenciasHelper  

---

## Alterações Realizadas

1. **[`dinovatech/helpers/CronRecorrenciasHelper.php`](file:///e:/DEV/dinovatech/dinovatech/helpers/CronRecorrenciasHelper.php)**:
   - Removido o uso de `descricao_personalizada` como override da `$tag` do item da fatura.
   - O item da fatura recorrente agora é sempre gerado com o nome padrão:
     `Mensalidade - [Nome do Serviço] (MM/YYYY)`

2. **Limpeza do Banco de Dados Remoto**:
   - Para normalizar qualquer item em `ItensFatura` que contenha código HTML ou texto excedente retido anteriormente:
     ```sql
     UPDATE ItensFatura I
     JOIN Servicos S ON I.id_servico = S.id_servico
     JOIN Faturas F ON I.id_fatura = F.id_fatura
     SET I.tag = CONCAT('Mensalidade - ', S.nome_servico, ' (', DATE_FORMAT(F.data_vencimento, '%m/%Y'), ')')
     WHERE I.tag LIKE '%<%' OR CHAR_LENGTH(I.tag) > 255;
     ```
