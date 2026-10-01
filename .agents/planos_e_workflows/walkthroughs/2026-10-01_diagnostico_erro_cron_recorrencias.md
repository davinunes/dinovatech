# Walkthrough: Solução para Erro de Tamanho da Coluna Tag no Cron de Recorrências

**Data**: 2026-10-01  
**Módulo**: Faturas / CronRecorrenciasHelper / Banco de Dados  

---

## O que é o campo `tag` na tabela `ItensFatura`?

A coluna `tag` na tabela `ItensFatura` armazena a **descrição do item da fatura**, exibida como o subtítulo explicativo do serviço em `fatura_view.php` (logo abaixo do nome do serviço).

Na geração automática de faturas recorrentes ([`CronRecorrenciasHelper.php`](file:///e:/DEV/dinovatech/dinovatech/helpers/CronRecorrenciasHelper.php)), o sistema preenche esse campo com a `descricao_personalizada` definida no contrato do cliente.

---

## Solução Aplicada

1. **Migration SQL Criada**:
   - Arquivo: [`database/migrations/20261001_0001_alter_itensfatura_tag_to_text.sql`](file:///e:/DEV/dinovatech/database/migrations/20261001_0001_alter_itensfatura_tag_to_text.sql)
   - Conteúdo:
     ```sql
     ALTER TABLE `ItensFatura` MODIFY COLUMN `tag` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL;
     ```

2. **Ação Requerida no Banco de Dados Remoto**:
   - Executar a alteração acima no MariaDB para expandir a coluna `tag` de `VARCHAR(255)` para `TEXT`.
   - Isso permite descrições de contratos com texto longo sem risco de estouro de tamanho.
