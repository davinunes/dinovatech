# Raciocínio Analítico: Dashboard do Cliente, Meus Dados, Sincronização Google e Carteira de Vacinação

**Data:** 2026-08-12  
**Contexto:** Atualização do planejamento da Área do Cliente (`cliente/index.php`) a pedido do usuário para incluir:
1. Dashboard de Visão Geral (primeira tela) com faturas, agendamentos, pets e atendimentos.
2. Nova aba **"Meus Dados"**, permitindo que o cliente atualize e-mail, telefone, endereço e seu próprio **ID de Agenda Google**.
3. Migration SQL para adicionar a coluna `google_calendar_id` na tabela `Clientes`.
4. Convite/Sincronização no Google Calendar: ao criar/atualizar agendamentos e atendimentos, se o cliente possuir `google_calendar_id` ou e-mail configurado, o cliente é adicionado como convidado (`attendees`) no evento do Google Calendar.

## 1. Análise Técnica

### Banco de Dados
- Criar migration `database/migrations/20260812_0002_add_google_calendar_id_to_clientes.sql`:
  `ALTER TABLE Clientes ADD COLUMN google_calendar_id VARCHAR(255) DEFAULT NULL;`

### Backend (`dinovatech/app.php`)
- Criar a ação `get_cliente_dashboard_data`:
  - Retorna dados consolidados para o Dashboard, Faturas, Agendamentos, Atendimentos, Vacinas e Dados do Perfil do cliente.
- Criar a ação `atualizar_dados_cliente`:
  - Valida a sessão do cliente e atualiza: `email`, `telefone`, `endereco`, `numero`, `complemento`, `bairro`, `cep`, `uf`, `codigo_municipio` e `google_calendar_id`.

### Sincronização de Agenda com Convidado (`GoogleCalendarHelper.php` & `modules/Agenda/api.php` & `atendimento_form.php`)
- Atualizar `GoogleCalendarHelper.php` para aceitar o parâmetro `attendees` no payload da API do Google Calendar (`createEvent` e `updateEvent`).
- Ao sincronizar o evento da agenda, buscar o `google_calendar_id` (ou `email`) do cliente vinculado e adicioná-lo como convidado:
  `'attendees' => [['email' => $cliente_google_email_ou_calendar_id]]`

### Frontend (`cliente/index.php`)
- Abas disponíveis:
  1. **Visão Geral** (Dashboard)
  2. **Faturas em Aberto**
  3. **Histórico / Pagas**
  4. **Meus Dados** (Atualização cadastral e ID do Google Calendar)
  5. **Carteira de Vacinação** (Apenas Modo Clínico)
