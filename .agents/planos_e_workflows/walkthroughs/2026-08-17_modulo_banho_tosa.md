# Walkthrough - Módulo de Banho & Tosa: Renomeação para Esteira & Filtro Diário

Data: 2026-08-17
Status: Concluído e Atualizado

## 1. Renomeação do Menu
- O item do menu na barra lateral (`sidebar.php`) e os botões de atalho em `banho_agenda.php` foram renomeados de "Linha de Produção (TV)" para **"Esteira"**.

## 2. Exibição Inteligente dos Agendamentos do Dia
- **Auto-Sync Restrito ao Dia Atual**: O mecanismo automático de sincronização agora insere na esteira apenas agendamentos de Banho & Tosa cuja data de início seja **hoje** (`DATE(data_inicio) = CURDATE()`).
- **Filtro de Visualização da Esteira**:
  - Cards na coluna *"1. Recepção / Aguardando"* só são exibidos se forem agendados para a data de hoje ou check-ins avulsos do dia.
  - Agendamentos futuros permanecem na Agenda e só entrarão na fila de espera no seu respectivo dia.
  - Caso algum pet de agendamento futuro chegue com antecedência e o atendente avance o atendimento para *"Em Banho"*, *"Secagem"*, etc. (`etapa != 'aguardando'`), ele será exibido normalmente na esteira.
