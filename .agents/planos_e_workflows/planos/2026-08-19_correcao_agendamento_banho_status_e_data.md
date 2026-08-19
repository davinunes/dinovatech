# Plano de Implementação: Correção de Status e Data Padrão no Banho & Tosa

- **Data**: 19/08/2026
- **Contexto**: Erro `Data truncated for column 'status' at row 1` ao agendar para hoje e necessidade de sugerir a data de hoje por padrão se houver horários.

## Diagnóstico
1. `Agendamentos.status` foi criado como `ENUM('Agendado','Confirmado','Realizado','Cancelado','Falta')`.
2. Em `cliente_agendar_banho` e `agendar_banho_presencial` (`app.php`), o código enviava `'Em Andamento'` quando `data_inicio == hoje`.
3. O valor `'Em Andamento'` estoura o ENUM existente, causando a falha `Data truncated for column 'status'` do MySQL em modo estrito.
4. No frontend (`cliente/index.php`), o modal sempre inicializava com `Date + 1` (amanhã).

## Arquivos Envolvidos
1. `database/migrations/20260819_0001_expand_agendamentos_status.sql`
2. `dinovatech/app.php`
3. `cliente/index.php`
