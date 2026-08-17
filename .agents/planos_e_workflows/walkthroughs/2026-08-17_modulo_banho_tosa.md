# Walkthrough - Integração Bidirecional: Agenda, Esteira (Kanban) e Consumo de Pacotes

Data: 2026-08-17
Status: Concluído e Atualizado

## 1. Correção do Erro de Truncamento na Coluna 'etapa'
- **Problema**: `Data truncated for column 'etapa' at row 1` ao movimentar cards para `secagem`, `tosa_finalizacao` ou `finalizado` devido ao tipo `ENUM` restrito no MySQL.
- **Solução**: Ajustada a definição no script de migração `database/migrations/20260817_0001_create_banho_tosa_schema.sql` para `VARCHAR(50) NOT NULL DEFAULT 'aguardando'` com comando preventivo `ALTER TABLE BanhoProducaoFila MODIFY COLUMN etapa VARCHAR(50) NOT NULL DEFAULT 'aguardando';`.

## 2. Integração Bidirecional entre Agenda e Esteira (Kanban)
- **Banhos Agendados entram na Esteira**: Ao consultar a esteira (`get_banho_producao_fila`), qualquer agendamento de Banho & Tosa do dia que ainda não esteja na esteira é inserido automaticamente na coluna **"1. Recepção / Aguardando"**.
- **Entrada Direta na Esteira (Walk-in) gera Agendamento**:
  - O modal de check-in em `banho_producao.php` agora permite selecionar o serviço desejado.
  - Detecta automaticamente se o tutor possui créditos de pacote para aquele serviço e marca a opção de abatimento.
  - Ao salvar o check-in, o sistema calcula a duração estimada (com multiplicador de porte e pelagem), cria o registro em `Agendamentos` (`tipo_agenda = 'banho_tosa'`, `status = 'Em Andamento'`) e insere na esteira já vinculado.
- **Sincronização de Status**:
  - Mover para `em_banho`, `secagem`, `tosa_finalizacao` atualiza `Agendamentos.status = 'Em Andamento'`.
  - Mover para `pronto` atualiza `Agendamentos.status = 'Realizado'`.
  - Mover para `finalizado` (Entregue) atualiza `Agendamentos.status = 'Concluído'` e preenche `horario_saida = NOW()`.

## 3. Indicadores Visuais na Agenda (`banho_agenda.php` & `api.php`)
- Eventos de banho no calendário exibem prefixos dinâmicos do status da produção: `⏳ [Fila]`, `🛁 [Banho]`, `💨 [Secagem]`, `✂️ [Tosa]`, `🐾 [Pronto]`, `✅ [Concluído]`.
- Ao abrir o agendamento no modal da agenda, é exibido um card com a etapa atual da esteira e um botão direto **"Ver Esteira"**.
