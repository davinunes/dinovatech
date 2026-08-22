# Raciocínio Diagnóstico: Integração Google Agenda e Quebra com Convidados (Attendees)

**Data**: 2026-08-21  
**Autor**: Antigravity Assistant  
**Status**: Análise Concluída / Plano Proposto

---

## 1. Contexto & Solicitação do Usuário

O usuário relatou que:
1. Fez alterações para parar de salvar arquivos sensíveis no disco e salvá-los no banco de dados.
2. Pediu para ajustar a integração com o Google Agenda para incluir a agenda dos clientes caso configurada e marcados no evento.
3. Ao testar, o agendamento não apareceu nem na agenda do funcionário/veterinário nem na do cliente.
4. Solicitou:
   - Verificar se estamos salvando dados da integração no disco ou no banco.
   - Verificar se quebrou devido à questão dos convidados do evento.
   - Gerar uma camada de depuração se necessário.

---

## 2. Investigação & Diagnóstico

### 2.1. Onde os dados da integração estão sendo salvos? (Disco vs Banco)
- **Credenciais da Service Account (`google_service_account_json`)**:
  - Salvas na tabela `ConfiguracoesEmissor`, coluna `google_service_account_json` (tipo `LONGTEXT`).
  - Conteúdo criptografado com chave simétrica via `EncryptionHelper::encrypt()` ao fazer upload.
  - Ao executar `GoogleCalendarHelper`, o JSON é carregado diretamente do banco e descriptografado em memória via `EncryptionHelper::decrypt()`.
  - **Conclusão**: **Nenhum arquivo sensível ou credencial é salvo no disco**. Tudo está no banco e protegido por criptografia.
- **IDs das Agendas (`google_calendar_id`)**:
  - Salvo na tabela `Veterinarios` (para o profissional).
  - Salvo na tabela `Clientes` (para o cliente/tutor).
- **IDs dos Eventos (`google_event_id`)**:
  - Salvo na tabela `Agendamentos`.

---

### 2.2. Por que a integração quebrou após adicionar os convidados (`attendees`)?

#### Causa Raiz:
A autenticação do Dinovatech com a Google Calendar API v3 é feita através de uma **Google Cloud Service Account** (Conta de Serviço).

1. **Restrição Arquitetural do Google**:
   - Contas de Serviço do Google Cloud **NÃO possuem permissão para convidar participantes (`attendees`)** em eventos a menos que possuam **Domain-Wide Delegation (DWD)** configurada em uma conta corporativa Google Workspace com impersonation de um usuário do domínio.
   - Em contas pessoais comuns `@gmail.com` ou Service Accounts padrão, ao incluir a propriedade `'attendees' => [['email' => ...]]` no JSON da requisição `POST` ou `PATCH` para a API do Google Calendar (`/calendars/{calendarId}/events`), o Google **rejeita sumariamente** a chamada com **HTTP 403 Forbidden**:
     > *"Service accounts cannot invite attendees without domain-wide delegation of authority."*

2. **Impacto no Sistema**:
   - No `GoogleCalendarHelper.php`, a chamada disparava uma exceção Guzzle HTTP 403.
   - O método `createEvent()` capturava a exceção silenciosamente em um `catch (Exception $e)`, registrava apenas no `error_log` do PHP e retornava `null`.
   - Como retornou `null`, o `google_event_id` não era gravado no banco de dados e **nenhum evento era criado**, impedindo o agendamento de aparecer na agenda do funcionário/veterinário e também na do cliente.

---

## 3. Solução Arquitetural

### 3.1. Sincronização Direta Multi-Calendário (Sem dependência de `attendees`)
Como a Service Account do sistema tem permissão direta de escrita nos calendários que foram compartilhados com ela (tanto do veterinário/profissional quanto do cliente):
1. **Remover o envio de `attendees`** que disparava o erro 403.
2. **Adicionar suporte a sincronização direta na agenda do Cliente**:
   - Ao criar/atualizar/excluir um agendamento:
     a) Se o **Profissional** tem `google_calendar_id`, cria/atualiza/exclui o evento no calendário do profissional e armazena o ID em `google_event_id`.
     b) Se o **Cliente** tem `google_calendar_id`, cria/atualiza/exclui o evento no calendário do cliente e armazena o ID em `google_event_id_cliente`.
   - Adicionar migration para o campo `google_event_id_cliente` na tabela `Agendamentos`.

### 3.2. Camada de Depuração & Diagnóstico Robusta
1. **Tabela de Logs (`GoogleSyncLogs`)**:
   - Criação da tabela `GoogleSyncLogs` com campos: `id_log`, `data_hora`, `id_agendamento`, `calendar_id`, `tipo_operacao` (create, update, delete, test), `status` ('sucesso', 'erro', 'aviso'), `http_code`, `mensagem`, `payload_resumo`.
2. **Método de Log no `GoogleCalendarHelper`**:
   - Gravação automática de cada requisição e de qualquer exceção retornada pela API do Google com a mensagem exata em português amigável.
3. **Mecanismo de Teste de Diagnóstico (`testConnection`)**:
   - Método no `GoogleCalendarHelper` que verifica:
     1) Se o JSON está configurado no banco e é decodificável.
     2) Se a autenticação OAuth2 da Service Account obtém token válido.
     3) Se o `calendar_id` informado responde e tem permissão de escrita/leitura.
4. **Endpoint e Interface de Diagnóstico**:
   - Adicionar endpoint em `modules/Agenda/api.php` (`action=test_google_sync` e `action=get_google_logs`) para testar instantaneamente a integração e exibir histórico de logs com mensagens autoexplicativas.
