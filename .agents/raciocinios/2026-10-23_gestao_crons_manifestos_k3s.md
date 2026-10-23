# Raciocínio de Implementação: Gestão de Rotinas Agendadas via Manifestos K3s (CronJobs Multi-Tenant)

**Data**: 2026-10-23  
**Tópico**: Criação de estrutura relacional para agendamentos por tenant no painel da empresa, orquestração declarativa de CronJobs no cluster K3s e integração com a rotina de faturamento de contratos recorrentes.

---

## 1. Diagnóstico do Problema & Requisitos
- **Cenário Atual**:
  - O sistema possui atualmente a rotina `dinovatech/cron/gerar_faturas_recorrencias.php` para gerar faturas mensais baseadas em contratos recorrentes e agendar o Pix Automático (Banco Inter).
  - Na arquitetura de containers multi-tenant (K3s), cada cliente possui um namespace isolado (`tenant-{slug}`) e banco de dados dedicado (`dino_{slug}`).
  - O container principal roda `apache2-foreground` e não possui daemon cron interno (anti-pattern em containers).
- **Decisão Arquitetural do Usuário**:
  - Implementar uma tabela relacional vinculada a `saas_tenants`, permitindo cadastrar 1 ou mais rotinas agendadas por contrato.
  - Informar o caminho do script (`script_path`) e a periodicidade em formato cron (`cron_expression`).
  - O provisionador de pods (`SaasProvisionerHelper`) gerará/atualizará os manifestos nativos do Kubernetes (`batch/v1` `CronJob`) no namespace do cliente.
  - Como no momento a única rotina ativa é a de faturas recorrentes, ela será o preset padrão facilitador, mantendo o sistema aberto para futuros scripts agendados.

---

## 2. Modelagem Relacional (`saas_tenant_crons`)
- **Tabela**: `saas_tenant_crons`
  - `id_cron`: Chave primária autoincremento.
  - `id_tenant`: FK para `saas_tenants.id_tenant` com `ON DELETE CASCADE`.
  - `nome_job`: Slug do job no K3s (ex: `faturas-recorrentes`), alfanumérico com hífens.
  - `descricao`: Texto amigável (ex: "Geração de Faturas Mensais e Pix Automático").
  - `script_path`: Caminho relativo ao root do projeto (ex: `dinovatech/cron/gerar_faturas_recorrencias.php`).
  - `argumentos`: Argumentos adicionais CLI opcionais.
  - `cron_expression`: Expressão crontab padrão (ex: `0 6 1 * *`).
  - `ativo`: `1` (ativo) ou `0` (pausado).
  - `ultimo_status`: Status da última execução no K3s.
  - `ultima_execucao`: Timestamp da última execução.
  - `created_at` e `updated_at`.
- **Chave Única**: `uk_tenant_job (id_tenant, nome_job)` para evitar duplicação no mesmo namespace.

---

## 3. Orquestração no Kubernetes (K3s)
- **Recurso**: `batch/v1` -> `CronJob`.
- **Nome do Objeto**: `cron-{nome_job}` (ex: `cron-faturas-recorrentes`).
- **Namespace**: `tenant-{slug}`.
- **Configurações Cruciais**:
  - `concurrencyPolicy: Forbid`: Impede que uma nova execução seja disparada se a anterior ainda estiver rodando.
  - `successfulJobsHistoryLimit: 3`, `failedJobsHistoryLimit: 3`: Limita a retenção de pods terminados para economizar recursos no cluster.
  - `restartPolicy: OnFailure`: Reinicia o pod apenas se houver falha de execução.
  - `command: ["php", "/var/www/html/..." . $scriptPath]`.
  - `envFrom: [secretRef: {name: "tenant-env"}]`: Herda todas as credenciais do banco isolado e variáveis de ambiente do tenant.
  - `suspend: true/false`: Sincronizado com o status do tenant (se o tenant for suspenso, os crons são pausados automaticamente).

---

## 4. Integração no Painel da Empresa
- **Na Central SaaS (`saas_tenants.php`)**:
  - Modal interativo ou card de gestão de rotinas agendadas para o tenant.
  - Ações: Criar rotina (com preset de Faturas Recorrentes ou customizado), Pausar/Ativar, Editar periodicidade, Excluir e Sincronizar com K3s.
- **No Helper (`SaasProvisionerHelper.php`)**:
  - Métodos CRUD para `saas_tenant_crons`.
  - Método `syncTenantCronJobs(int $idTenant, $link)` integrado a `provisionTenant()` e `scaleTenant()`.
- **No Backend AJAX (`app.php`)**:
  - Handlers para salvar, excluir, alternar status e disparar sincronização manual dos crons.
