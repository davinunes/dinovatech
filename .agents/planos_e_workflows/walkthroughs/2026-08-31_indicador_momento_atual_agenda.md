# Walkthrough: Linha Indicadora do Momento Atual na Agenda

**Data:** 31/08/2026  
**Arquivo Modificado:** [dashboard.php](file:///e:/DEV/dinovatech/dinovatech/modules/Agenda/dashboard.php)

---

## Modificações Realizadas

1. **Ativação do `nowIndicator` no FullCalendar**:
   - Adicionada a propriedade `nowIndicator: true` nas opções de inicialização do calendário.
   - Definida a função `now()` para assegurar que a linha marque o horário atual do navegador em total sincronia com o modo `timeZone: 'UTC'` do sistema.

2. **Estilização Visual**:
   - Inseridas regras CSS para `.fc-timegrid-now-indicator-line` e `.fc-timegrid-now-indicator-arrow` destacando o traço na cor vermelha (`#ef4444`) com espessura de `2px` e `z-index` adequado sobre a grade.

---

## Como Validar
1. Acesse o módulo de Agenda em `modules/Agenda/dashboard.php`.
2. Alterne para a visualização semanal (`timeGridWeek`) ou diária (`timeGridDay`).
3. Verifique a linha horizontal vermelha e o marcador posicionados exatamente no horário e dia atuais.
