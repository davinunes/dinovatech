# Walkthrough: Implementação Pix Automático Banco Inter (Jornada 4)

Este documento sintetiza as alterações implementadas para a integração da **Jornada 4 do Pix Automático (Banco Inter)** no sistema Dinovatech.

---

## 1. Visão Geral das Entregas

A **Jornada 4** permite unificar em um único QR Code:
1. O pagamento da fatura com vencimento no mês corrente (`CobV`).
2. A proposta de autorização do Débito Automático via Pix para as mensalidades seguintes (`Recorrência`).

Foram implementadas todas as 4 etapas da Jornada 4, webhooks para atualização assíncrona, sincronização ativa e passiva, rotinas automatizadas no cron diário (geração de `cobr` e higienização preventiva), cancelamentos no Inter e interfaces para Cliente e Admin.

---

## 2. Arquivos Modificados e Criados

### 2.1. Banco de Dados e Migração
- [`database/migrations/20260907_0001_create_pix_recorrencias_tables.sql`](file:///e:/DEV/dinovatech/database/migrations/20260907_0001_create_pix_recorrencias_tables.sql)
  - Tabela `PixRecorrencias` com rastreamento completo de `id_rec`, `id_recorrencia`, `id_cliente`, `id_fatura_origem`, `txid_origem`, `loc_id`, `emv_payload`, `status`, `valor_recorrente`, `data_inicio`, `data_aceite`, `data_cancelamento`, `motivo_rejeicao` e `payload_retorno`.
  - Enums de status: `CRIADA`, `PENDENTE`, `APROVADA`, `REJEITADA`, `CANCELADA`.

### 2.2. Integração Banco Inter (Core)
- [`inter/config.php`](file:///e:/DEV/dinovatech/inter/config.php)
  - Escopos OAuth atualizados: `rec.write rec.read cobv.write cobv.read cobr.write cobr.read webhook.write webhook.read`.
- [`inter/api.php`](file:///e:/DEV/dinovatech/inter/api.php)
  - `criarLocationRecorrencia()`: `POST /locrec`
  - `criarCobvComVencimento()`: `PUT /cobv/{txid}`
  - `criarRecorrenciaContrato()`: `POST /rec`
  - `consultarRecorrenciaJornada4()`: `GET /rec?idRec={idRec}&txid={txid}`
  - `consultarStatusRecorrencia()`: `GET /rec/{idRec}`
  - `criarCobrancaRecorrenteSubsequente()`: `POST /cobr`
  - `cancelarRecorrenciaContrato()`: `PATCH /rec/{idRec}` com `{"status": "CANCELADA"}`
  - `cancelarCobrancaIndividual()`: `PATCH /cobr/{txid}` com `{"status": "CANCELADA"}`
  - `configurarWebhookRecorrencia()` e `consultarWebhookRecorrencia()`
- [`inter/PixAutomaticoService.php`](file:///e:/DEV/dinovatech/inter/PixAutomaticoService.php)
  - Orquestrador com tratamento de idempotência, logs e transações.
- [`inter/endpoint.php`](file:///e:/DEV/dinovatech/inter/endpoint.php)
  - Endpoints REST internos para requisições AJAX do painel e da central do cliente.
- [`inter/webhook_rec.php`](file:///e:/DEV/dinovatech/inter/webhook_rec.php)
  - Listener seguro para callbacks de alteração de status do Inter.

### 2.3. Cron e Rotinas Automáticas
- [`dinovatech/helpers/CronRecorrenciasHelper.php`](file:///e:/DEV/dinovatech/dinovatech/helpers/CronRecorrenciasHelper.php)
  - Envio automático de cobranças recorrentes subsequentes (`POST /cobr`) quando contratos com Pix Automático aprovado geram faturas.
  - Higienização preventiva automática diária para cancelar no Inter contratos desativados localmente.

### 2.4. Central do Cliente
- [`dinovatech/app.php`](file:///e:/DEV/dinovatech/dinovatech/app.php)
  - `get_cliente_dashboard_data`: Join com `PixRecorrencias` para expor status da recorrência ativa.
- [`cliente/index.php`](file:///e:/DEV/dinovatech/cliente/index.php)
  - Badge visual `⚡ Débito Automático Pix Ativo` em contratos aprovados.
- [`cliente/fatura.php`](file:///e:/DEV/dinovatech/cliente/fatura.php)
  - Botão de destaque "⚡ Ativar Pix Automático".
  - Modal `#modalPixAutomatico` com visualização de QR Code, Copia e Cola, orientações passo a passo e duplo polling em tempo real.

### 2.5. Painel Administrativo
- [`dinovatech/fatura_view.php`](file:///e:/DEV/dinovatech/dinovatech/fatura_view.php)
  - Card lateral "Pix Automático (Jornada 4)" com informações de status, data de aceite, `idRec`, botão para gerar QR Code Jornada 4, consultar no Inter e cancelar cobrança.
- [`dinovatech/contrato_form.php`](file:///e:/DEV/dinovatech/dinovatech/contrato_form.php)
  - Aba "Pix Automático" com indicador de status no cabeçalho e botões para consultar status ou cancelar autorização de débito do contrato.

---

## 3. Guia de Validação e Testes

1. **Migração do Banco de Dados:**
   - Execute o script SQL em [`database/migrations/20260907_0001_create_pix_recorrencias_tables.sql`](file:///e:/DEV/dinovatech/database/migrations/20260907_0001_create_pix_recorrencias_tables.sql) no MariaDB.

2. **Fluxo da Jornada 4 na Central do Cliente:**
   - Acesse uma fatura em aberto de um contrato pelo portal do cliente (`cliente/fatura.php?id=...`).
   - Clique em **"⚡ Ativar Pix Automático"** -> o sistema executará os 4 passos no Inter e exibirá o QR Code combinado.
   - Pague o QR Code no app bancário e confirme a autorização de débito automático.

3. **Validação do Cron de Faturas Subsequentes:**
   - Na próxima geração diária pelo cron, verifique se a fatura do mês seguinte dispara `POST /cobr` e grava o `txid_cobr` correspondente.

4. **Cancelamento do Contrato:**
   - Ao cancelar um contrato em [`dinovatech/contrato_form.php`](file:///e:/DEV/dinovatech/dinovatech/contrato_form.php), clique na aba **Pix Automático** e selecione **"Cancelar Autorização de Débito"** para desvincular a recorrência no Banco Inter.
