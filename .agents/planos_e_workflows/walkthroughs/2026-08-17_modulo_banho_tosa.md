# Walkthrough - Módulo de Banho & Tosa: Solicitação de Agendamento Online pelo Tutor

Data: 2026-08-17
Status: Concluído e Atualizado

## 1. Banner e Atalhos na Área do Cliente (`cliente/index.php`)
- Adicionado banner destacado na tela inicial do portal do cliente: *"Agendamento de Banho & Tosa Online"*.
- Adicionado botão de atalho *"Novo Agendamento"* no card de Próximos Agendamentos e na aba de Estética/Pacotes.

## 2. Consulta de Disponibilidade na Esteira (`get_horarios_disponiveis_banho`)
- Endpoint criado em `dinovatech/app.php` que calcula dinamicamente a disponibilidade de horários no dia selecionado com base em:
  - Duração estimada do serviço (considerando multiplicador de porte do pet: P, M, G, GG e tipo de pelagem).
  - Capacidade simultânea de colaboradores/banhistas.
  - Ocupação de agendamentos existentes e esteira em tempo real no dia.
  - Bloqueio automático de horários já passados caso a consulta seja para a data atual.

## 3. Grade Interativa de Horários no Modal do Tutor
- O tutor seleciona o **Pet**, o **Serviço**, e a **Data desejada**.
- O sistema consulta a esteira em tempo real e exibe uma grade de chips com os horários disponíveis e número de vagas livres.
- O tutor clica no horário preferido (ex: `09:30`), seleciona se deseja utilizar saldo de pacote ativo (sem custo extra), digita eventuais instruções e confirma o agendamento.
- O agendamento é gerado com status `"Em Andamento"` ou `"Agendado"` e entra no fluxo automático da agenda e da esteira no dia marcado.
