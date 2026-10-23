# Plano de Implementação: Gestão de Agendamentos (CronJobs K3s) por Tenant

**Data**: 2026-10-23  
**Objetivo**: Implementar a gestão de rotinas agendadas baseadas em caminho de script e periodicidade (crontab) na Central SaaS Master do Dinovatech, com orquestração declarativa de recursos `batch/v1` `CronJob` no Kubernetes K3s para cada tenant, tendo como preset padrão a rotina de faturamento de contratos recorrentes.

---

## Fases de Implementação

### Fase 1: Banco de Dados & Migração Relacional
- [x] Criar arquivo de migração `database/migrations/20261023_0001_create_saas_tenant_crons.sql`.
- Tabela `saas_tenant_crons` com campos:
  - `id_cron` (PK, INT AUTO_INCREMENT)
  - `id_tenant` (FK para `saas_tenants.id_tenant`)
  - `nome_job` (VARCHAR(60))
  - `descricao` (VARCHAR(150))
  - `script_path` (VARCHAR(255))
  - `cron_expression` (VARCHAR(50) DEFAULT '0 6 1 * *')
  - `ativo` (TINYINT(1) DEFAULT 1)
  - `ultimo_status` (VARCHAR(30))
  - `ultima_execucao` (DATETIME)
  - Timestamps (`created_at`, `updated_at`)
  - Chave única: `uk_tenant_job (id_tenant, nome_job)`

### Fase 2: Integração com o K3s (`K3sClientHelper.php`)
- [ ] Adicionar métodos específicos para manipulação de CronJobs na API do K3s:
  - `createOrUpdateCronJob(string $namespace, string $cronJobName, array $payload): array`
  - `deleteCronJob(string $namespace, string $cronJobName): array`
  - `listCronJobs(string $namespace): array`
  - `getCronJob(string $namespace, string $cronJobName): array`

### Fase 3: Orquestrador de Ciclo de Vida (`SaasProvisionerHelper.php`)
- [ ] Implementar métodos CRUD e de sincronização:
  - `getTenantCrons(int $idTenant, $link): array`
  - `saveTenantCron(int $idTenant, array $cronData, $link): array`
  - `deleteTenantCron(int $idCron, $link): array`
  - `toggleTenantCron(int $idCron, int $ativo, $link): array`
  - `syncTenantCronJobs(int $idTenant, $link): array`
- [ ] Integrar `syncTenantCronJobs` em `provisionTenant()` (para aplicar os agendamentos no deploy) e em `scaleTenant()` (para suspender/reativar agendamentos com `spec.suspend`).

### Fase 4: Endpoints AJAX no Backend (`dinovatech/app.php`)
- [ ] Criar actions dedicadas em `app.php`:
  - `saas_get_tenant_crons`: Retorna os crons cadastrados e status ao vivo no K3s.
  - `saas_save_tenant_cron`: Salva/edita uma rotina agendada e sincroniza com o K3s.
  - `saas_delete_tenant_cron`: Remove do banco e deleta o CronJob no cluster.
  - `saas_toggle_tenant_cron`: Ativa ou pausa a rotina no banco e no K3s.
  - `saas_sync_tenant_crons`: Força reconciliação de todas as rotinas do tenant no K3s.

### Fase 5: Interface do Usuário na Central SaaS (`dinovatech/saas_tenants.php`)
- [ ] Adicionar botão de "Rotinas Agendadas (Cron)" na tabela de cada tenant.
- [ ] Criar modal interativo moderno para listar, adicionar e pausar rotinas agendadas:
  - Preset rápido com 1 clique: *"Geração de Faturas Recorrentes"* (`dinovatech/cron/gerar_faturas_recorrencias.php` | `0 6 1 * *`).
  - Formulário para scripts customizados (Nome do job, Descrição, Script Path e Expressão Cron).
  - Tabela com status ao vivo, periodicidade legível (ex: "Todo dia 1º às 06:00") e botões de ação.
