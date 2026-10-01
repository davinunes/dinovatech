# Raciocínio Analítico: Diagnóstico de Erro no Cron de Faturas Recorrentes (Recorrência ID 6)

**Data**: 2026-10-01  
**Contexto**: Na segunda execução do cron, com o erro de banco desbloqueado e legível, a causa exata da falha na Recorrência ID 6 foi identificada:
`[ERRO] Erro ao inserir item para Fatura ID 104 (Recorrência ID 6): Data too long for column 'tag' at row 1`.

---

## 1. Origem do Problema

- O campo `descricao_personalizada` na tabela `Recorrencias` (editado via TinyMCE com HTML de anotações internas do contrato) estava sendo usado em `CronRecorrenciasHelper.php` como *override* da `$tag` do item da fatura.
- Como o campo do contrato continha código HTML longo (anotações genéricas do contrato), o texto excedia 255 caracteres e gerava estouro na coluna `tag` da tabela `ItensFatura`.

---

## 2. Ajuste Efetuado no Backend PHP

- Em [`dinovatech/helpers/CronRecorrenciasHelper.php`](file:///e:/DEV/dinovatech/dinovatech/helpers/CronRecorrenciasHelper.php), removeu-se o uso do campo `descricao_personalizada` para a `$tag` da fatura.
- Agora, a `$tag` do item é sempre padronizada no formato:
  `"Mensalidade - " . $rec['nome_servico'] . " (" . $mesAnoSafe . ")"` (idêntico ao comportamento já adotado em `app.php`).

---

## 3. Query SQL para Limpeza de Itens Afetados no Banco Remoto

Para corrigir itens que porventura ficaram salvos com o HTML gigante no banco de dados:

```sql
UPDATE ItensFatura I
JOIN Servicos S ON I.id_servico = S.id_servico
JOIN Faturas F ON I.id_fatura = F.id_fatura
SET I.tag = CONCAT('Mensalidade - ', S.nome_servico, ' (', DATE_FORMAT(F.data_vencimento, '%m/%Y'), ')')
WHERE I.tag LIKE '%<%' OR CHAR_LENGTH(I.tag) > 255;
```
