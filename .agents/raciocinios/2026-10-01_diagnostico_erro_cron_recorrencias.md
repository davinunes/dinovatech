# Raciocínio Analítico: Diagnóstico de Erro no Cron de Faturas Recorrentes (Recorrência ID 6)

**Data**: 2026-10-01  
**Contexto**: Na segunda execução do cron, com o erro de banco desbloqueado e legível, a causa exata da falha na Recorrência ID 6 foi identificada:
`[ERRO] Erro ao inserir item para Fatura ID 104 (Recorrência ID 6): Data too long for column 'tag' at row 1`.

---

## 1. O que é a coluna `tag` em `ItensFatura`?

Ao mapear a utilização do campo no sistema:
- A coluna `tag` na tabela `ItensFatura` armazena a **descrição estendida ou detalhamento do item da fatura** (subtítulo que é exibido logo abaixo do nome do serviço na visualização da fatura `fatura_view.php`).
- Ao gerar faturas recorrentes em `CronRecorrenciasHelper.php`, o sistema preenche a `$tag` com o conteúdo de `$rec['descricao_personalizada']` (a descrição personalizada cadastrada no contrato).

---

## 2. Causa Raiz do Erro

- Na tabela `Recorrencias`, a coluna `descricao_personalizada` é do tipo `TEXT` (capacidade ilimitada para descrições longas de contrato).
- Na tabela `ItensFatura`, a coluna `tag` foi originalmente criada como `VARCHAR(255)`.
- A **Recorrência ID 6** possui uma `descricao_personalizada` cadastrada com mais de 255 caracteres.
- Ao tentar gravar o item da Fatura ID 104, o MariaDB/MySQL rejeitou o `INSERT` por estouro do limite da coluna: `Data too long for column 'tag' at row 1`.

---

## 3. Solução Adotada

1. **Migration de Banco de Dados**:
   - Criada a migration [`database/migrations/20261001_0001_alter_itensfatura_tag_to_text.sql`](file:///e:/DEV/dinovatech/database/migrations/20261001_0001_alter_itensfatura_tag_to_text.sql) para alterar o tipo da coluna `tag` na tabela `ItensFatura` de `VARCHAR(255)` para `TEXT`.
2. **Execução no Servidor Remoto**:
   - O comando SQL a seguir deve ser executado no banco de dados de produção:
     ```sql
     ALTER TABLE `ItensFatura` MODIFY COLUMN `tag` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL;
     ```
   - Após executar a alteração no banco, a Recorrência ID 6 (e qualquer outra com descrição longa) será processada com sucesso no próximo ciclo do cron ou execução manual.
