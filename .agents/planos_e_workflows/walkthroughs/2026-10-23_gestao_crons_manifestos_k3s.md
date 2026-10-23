# Walkthrough / Resumo de Entrega: Gestão de Agendamentos (CronJobs K3s) por Tenant

**Data**: 2026-10-23  
**Tópico**: Criação de estrutura relacional `saas_tenant_crons`, endpoints de manipulação e orquestração declarativa de recursos `batch/v1` `CronJob` no Kubernetes K3s para cada tenant SaaS.

---

## 1. O que foi Implementado

### 1.1 Migração de Banco de Dados (`saas_tenant_crons`)
- Arquivo: [20261023_0001_create_saas_tenant_crons.sql](file:///d:/dev/github/dinovatech/database/migrations/20261023_0001_create_saas_tenant_crons.sql)
- Cria a tabela relacional com chave estrangeira `id_tenant` vinculada a `saas_tenants`:
  - `nome_job`: Identificador do job no K3s (ex: `faturas-recorrentes`).
  - `descricao`: Descrição legível da tarefa.
  - `script_path`: Caminho relativo do script PHP a executar no container (`dinovatech/cron/gerar_faturas_recorrencias.php`).
  - `cron_expression`: Expressão crontab (`0 6 1 * *`).
  - `ativo`: Flag de controle para pausar/ativar (`1` ou `0`).
  - `ultimo_status`, `ultima_execucao`, `ultimo_log`.

### 1.2 Extensão do Cliente Kubernetes (`K3sClientHelper.php`)
- Arquivo: [K3sClientHelper.php](file:///d:/dev/github/dinovatech/dinovatech/helpers/K3sClientHelper.php)
- Adicionados métodos nativos para a API `batch/v1`:
  - `listCronJobs(string $namespace)`: Lista CronJobs ativos e seus horários de agendamento.
  - `getCronJob(string $namespace, string $cronJobName)`: Obtém detalhes de um CronJob específico.
  - `createOrUpdateCronJob(string $namespace, string $cronJobName, array $payload)`: Cria ou atualiza o manifesto com idempotência (`POST` com fallback para `PUT`).
  - `deleteCronJob(string $namespace, string $cronJobName)`: Deleta o CronJob do cluster.
  - `setCronJobSuspend(string $namespace, string $cronJobName, bool $suspend)`: Pausa (`suspend: true`) ou reativa (`suspend: false`) o CronJob via patch JSON.

### 1.3 Orquestrador de Ciclo de Vida (`SaasProvisionerHelper.php`)
- Arquivo: [SaasProvisionerHelper.php](file:///d:/dev/github/dinovatech/dinovatech/helpers/SaasProvisionerHelper.php)
- Adicionados métodos completos para gerenciar e reconciliar as rotinas:
  - `getTenantCrons(int $idTenant, $link)`: Cruza dados do banco local com o status ao vivo dos CronJobs no K3s.
  - `saveTenantCron(int $idTenant, array $data, $link)`: Salva no banco e dispara a criação/atualização do CronJob no K3s.
  - `deleteTenantCron(int $idCron, $link)`: Remove do banco e limpa o CronJob do cluster.
  - `toggleTenantCron(int $idCron, int $ativo, $link)`: Pausa/despausa no banco e no K3s.
  - `syncTenantCronJobs(int $idTenant, $link)`: Varre todos os crons do tenant e aplica os manifestos declarativos `batch/v1` com isolamento no namespace `tenant-{slug}`, injeção do secret `tenant-env` e recursos controlados (`requests: 30m / 48Mi`, `limits: 200m / 128Mi`).
- **Integração com Escala e Deploy**:
  - `provisionTenant()` agora executa automaticamente `syncTenantCronJobs()`.
  - `scaleTenant()` suspende todos os CronJobs quando o pod é pausado (`replicas = 0`) e os reativa quando o pod volta à ativa.

### 1.4 Endpoints AJAX no Backend (`dinovatech/app.php`)
- Arquivo: [app.php](file:///d:/dev/github/dinovatech/dinovatech/app.php)
- Ações adicionadas:
  - `saas_get_tenant_crons`
  - `saas_save_tenant_cron`
  - `saas_delete_tenant_cron`
  - `saas_toggle_tenant_cron`
  - `saas_sync_tenant_crons`

### 1.5 Interface na Central SaaS Master (`saas_tenants.php`)
- Arquivo: [saas_tenants.php](file:///d:/dev/github/dinovatech/dinovatech/saas_tenants.php)
- Adicionado botão de ação com ícone de relógio (`schedule`) em cada linha de tenant.
- Criado o modal moderno `modalCrons`:
  - **Botão Rápido Preset**: *"Carregar Preset de Faturas"* (preenche automaticamente `dinovatech/cron/gerar_faturas_recorrencias.php` e `0 6 1 * *`).
  - **Formulário customizável**: Permite informar qualquer caminho de script PHP e expressão cron, com botões de atalho (Mensal, Diário 06h, Diário 20h, De hora em hora, A cada 15 min).
  - **Tabela ao vivo**: Mostra nome, caminho, expressão cron, status de reconciliação no K3s, botão liga/desliga de ativação rápida, edição e exclusão.
  - **Reconciliação manual**: Botão *"Reconciliar K3s"* para forçar reenvio dos manifestos ao cluster a qualquer momento.

---

## 2. Como Testar / Utilizar
1. Acesse a Central SaaS em `saas_tenants.php`.
2. Na tabela de instâncias, clique no novo ícone âmbar de relógio (**Rotinas Agendadas**) na coluna de Ações Rápidas.
3. Clique em **"Carregar Preset de Faturas"** e em seguida em **"Salvar Rotina & Sincronizar K3s"**.
4. O K3s receberá o manifesto `CronJob` no namespace do cliente (`tenant-{slug}`). O badge mudará para *"Ativo no K3s"*.
