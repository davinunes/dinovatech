# Raciocínio Técnico: Pix Automático Banco Inter (Jornada 4 & Gestão de Ciclo de Vida)

**Data:** 2026-09-07  
**Tema:** Implementação da Jornada 4 do Pix Automático, Cancelamentos e Visualização na Central do Cliente  

---

## 1. Evolução dos Requisitos

Após análise e alinhamento com o usuário, o escopo foi expandido para cobrir o ciclo completo de vida das recorrências:

1. **Identificação Visual na Central do Cliente (`cliente/index.php`):**
   - Na lista de contratos/assinaturas do cliente, exibir um badge com ícone em destaque indicando `⚡ Débito Automático Pix Ativo` quando o contrato tiver uma autorização aprovada no Banco Inter.

2. **Cancelamento do Contrato de Recorrência (`PATCH /rec/{idRec}`):**
   - Quando o cliente cancela o serviço ou encerra o contrato, a empresa precisa cancelar a recorrência no Inter enviando `{"status": "CANCELADA"}` via `PATCH /rec/{idRec}`.
   - Isso bloqueia a geração de novas cobranças (`cobr`).
   - Disponibilizado via botão no Admin (`contrato_form.php` e `fatura_view.php`).

3. **Cancelamento de Cobrança Individual da Fatura (`PATCH /cobr/{txid}`):**
   - Se uma cobrança de um determinado mês já foi enviada ao banco e o admin precisar cancelar apenas aquele débito sem encerrar o contrato do cliente, utiliza-se `PATCH /cobr/{txid}` com `{"status": "CANCELADA"}` (respeitando o limite Bacen de até 22h do dia anterior ao vencimento).
   - Disponibilizado via botão na visualização da fatura (`fatura_view.php`).

4. **Higienização Preventiva no Cron Diário:**
   - Para evitar que contratos cancelados/inativados no Dinovatech continuem com débitos agendados no banco caso o operador esqueça de cancelar no Inter, a rotina diária do Cron faz uma varredura preventiva. Contratos inativos com `PixRecorrencias.status = 'APROVADA'` são cancelados automaticamente via `PATCH /rec/{idRec}` e registrados no log.
