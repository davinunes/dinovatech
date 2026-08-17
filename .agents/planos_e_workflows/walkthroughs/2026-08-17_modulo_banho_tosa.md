# Walkthrough - Módulo de Banho & Tosa: Gestão de Cards Avulsos na Esteira

Data: 2026-08-17
Status: Concluído e Atualizado

## 1. Migração Incremental para Correção do Banco
- Criada a migração incremental `database/migrations/20260817_0002_fix_banho_etapa_and_sync.sql` para aplicar a alteração de `etapa` para `VARCHAR(50)` e garantir as colunas `horario_saida` e `id_agendamento` em bases já existentes.
- Atualizada a resolução de caminhos em `app.php` e `scripts/migrate.php`.

## 2. Gestão de Cards na Esteira / Linha de Produção (`banho_producao.php`)
- **Cards Avulsos (Check-in direto)**:
  - **Botão Editar (Ícone de Lápis)**: Abre o modal `modalEditarCheckin` permitindo alterar o colaborador/banhista responsável, o serviço desejado, observações/cortes e anexar mais fotos de vistoria.
  - **Botão Excluir (Ícone de Lixeira)**:
    - Exibe confirmação amigável.
    - Se a entrada tiver consumido crédito de algum pacote/combo do cliente, **restitui automaticamente o saldo** no pacote (`ClientePacoteSaldos.qtd_utilizada - 1`) e reativa o pacote se estava esgotado.
    - Remove o agendamento gerado e as fotos, limpando o card da esteira em tempo real.
- **Cards Originários da Agenda**:
  - Exibem o botão de calendário `[📅 Agenda]` direcionando para a edição completa na agenda (`banho_agenda.php`).
  - Podem ser removidos da esteira via botão de lixeira caso o atendimento tenha sido cancelado.
