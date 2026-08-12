# Plano de Implementação: Dashboard na Área do Cliente, Meus Dados & Convite Google Calendar

**Data:** 2026-08-12  
**Funcionalidade:**
1. Dashboard de Visão Geral como primeira tela na Área do Cliente (`cliente/index.php`).
2. Aba "Meus Dados" para edição cadastral e configuração do ID do Google Calendar do cliente.
3. Migration SQL para adicionar a coluna `google_calendar_id` na tabela `Clientes`.
4. Inclusão automática do cliente como convidado (`attendees`) nos eventos sincronizados no Google Calendar.
5. Aba "Carteira de Vacinação" no modo veterinário.

## Objetivos e Componentes

### 1. Banco de Dados (Migration)
- Arquivo: `database/migrations/20260812_0002_add_google_calendar_id_to_clientes.sql`
- SQL: `ALTER TABLE Clientes ADD COLUMN google_calendar_id VARCHAR(255) DEFAULT NULL;`

### 2. Backend (`dinovatech/app.php` e `helpers/GoogleCalendarHelper.php`)
- **`GoogleCalendarHelper.php`**: Suporte a `attendees` nos eventos do Google Calendar.
- **`app.php`**:
  - Endpoint `get_cliente_dashboard_data`: Retorna faturas, agendamentos, pets, atendimentos recentes, vacinas e perfil do cliente.
  - Endpoint `atualizar_dados_cliente`: Atualiza e-mail, telefone, endereço completo e `google_calendar_id`.

### 3. Sincronização Google Calendar (`modules/Agenda/api.php` e `modules/Vet/atendimento_form.php`)
- Ao criar ou atualizar agendamentos/atendimentos, inclui o cliente como convidado se ele possuir `google_calendar_id` ou `email`.

### 4. Portal do Cliente (`cliente/index.php`)
- Abas:
  - **Visão Geral** (Dashboard)
  - **Faturas em Aberto**
  - **Histórico / Pagas**
  - **Meus Dados** (Perfil + Configuração do ID do Google Agenda)
  - **Carteira de Vacinação** (Se modo Vet)

## Plano de Teste
1. Executar a migration no banco de dados.
2. Logar no portal do cliente e testar a aba "Meus Dados", salvando o e-mail, telefone, endereço e `google_calendar_id`.
3. Criar/atualizar um agendamento e verificar se o convidado é incluído no Google Calendar.
4. Navegar entre as abas do portal do cliente.
