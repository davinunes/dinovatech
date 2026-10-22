# Walkthrough: Arquitetura SaaS Multi-Tenant (Container-per-Tenant) no K3s com Ingress Caddy

- **Data**: 2026-10-22
- **Escopo**: Transformação arquitetural do Dinovatech para suportar o modelo SaaS onde cada cliente novo recebe um Pod/container dedicado no cluster K3s, com banco de dados dedicado, certificados TLS sob demanda e atualização contínua zero-downtime.

---

## 1. Topologia da Infraestrutura

```
                                  [ INTERNET ]
                                        |
               (Portas 80 / 443 - HTTP/3, HTTP/2, HTTPS On-Demand)
                                        |
                                        v
                    +---------------------------------------+
                    |           CADDY INGRESS               |
                    |      (Namespace: ingress-caddy)       |
                    |  - On-Demand TLS (Let's Encrypt)      |
                    |  - Roteador dinâmico *.dinovatech...  |
                    +-------------------+-------------------+
                                        |
         +------------------------------+------------------------------+
         | (reverse_proxy interno)                                     |
         v                                                             v
+-------------------------------+                             +-------------------------------+
|     TENANT ALFA               |                             |     TENANT BETA               |
| Namespace: tenant-clinica-alfa|                             | Namespace: tenant-pet-beta    |
| - Service: app-clinica-alfa:80|                             | - Service: app-pet-beta:80    |
| - Pod PHP Dinovatech (Replica)|                             | - Pod PHP Dinovatech (Replica)|
| - Secret: tenant-env          |                             | - Secret: tenant-env          |
|   (DB_NAME=dino_alfa)         |                             |   (DB_NAME=dino_beta)         |
+---------------+---------------+                             +---------------+---------------+
                |                                                             |
                +------------------------------+------------------------------+
                                               |
                                               v
                                +-------------------------------+
                                |     SHARED SERVICES           |
                                | Namespace: shared-services    |
                                | - WeasyPrint REST (PDFs)      |
                                |   Porta 9080 (ClusterIP)      |
                                +-------------------------------+
                                               |
                                               v
                                +-------------------------------+
                                |  SERVIDOR MARIADB DEDICADO    |
                                |  - Host/IP configurável       |
                                |  - dino_alfa / dino_beta      |
                                +-------------------------------+
```

---

## 2. Componentes Implementados

### 2.1. Caddy Ingress com On-Demand TLS ([devops/k8s/01-caddy-ingress.yaml](file:///e:/DEV/dinovatech/devops/k8s/01-caddy-ingress.yaml))
* **Desacoplamento**: Substituiu o ingress-nginx legado, assumindo as portas 80 e 443 via Service LoadBalancer (`svclb-caddy-service`).
* **TLS Sob Demanda**: Emite certificados SSL na hora para qualquer subdomínio `*.dinovatech.com.br` ou domínio customizado de cliente (CNAME) via Let's Encrypt / ZeroSSL.
* **Roteamento Upstream Dinâmico**:
  ```caddy
  @tenant {
      header_regexp host Host ^(?P<slug>[a-z0-9-]+)\.dinovatech\.com\.br$
  }
  handle @tenant {
      reverse_proxy app-{re.host.slug}.tenant-{re.host.slug}.svc.cluster.local:80 {
          header_up Host {http.request.host}
          header_up X-Real-IP {http.request.header.X-Forwarded-For}
          header_up X-Forwarded-Proto {http.request.scheme}
      }
  }
  ```

### 2.2. Microserviço WeasyPrint Centralizado ([devops/k8s/02-weasyprint-shared.yaml](file:///e:/DEV/dinovatech/devops/k8s/02-weasyprint-shared.yaml))
* Implantado no namespace `shared-services` ouvindo na porta 9080.
* Todas as instâncias SaaS consomem o endpoint interno compartilhado:
  `http://weasyprint.shared-services.svc.cluster.local:9080/convert/html`
* **Economia**: Evita subir uma réplica de WeasyPrint para cada cliente (~250 MB de RAM economizados por tenant).

### 2.3. Banco de Dados e Migrations
* [20261022_0001_add_saas_multi_tenant_schema.sql](file:///e:/DEV/dinovatech/database/migrations/20261022_0001_add_saas_multi_tenant_schema.sql):
  * Campos `tipo_servico`, `saas_default_image`, `saas_default_memory`, `saas_default_cpu` em `Servicos`.
  * Criação da tabela `saas_tenants` com índices por recorrência, cliente e status.
* [20261022_0002_add_db_host_to_saas_tenants.sql](file:///e:/DEV/dinovatech/database/migrations/20261022_0002_add_db_host_to_saas_tenants.sql):
  * Adiciona `db_host` e `db_port` para permitir que o tenant aponte para qualquer banco externo/remoto.
* [20261022_0003_add_env_fields_to_saas_tenants.sql](file:///e:/DEV/dinovatech/database/migrations/20261022_0003_add_env_fields_to_saas_tenants.sql):
  * Adiciona `app_mode_vet`, `app_master_key` e `custom_envs` para controle granular por cliente.

### 2.4. Orquestração e RollingUpdate ([SaasProvisionerHelper.php](file:///e:/DEV/dinovatech/dinovatech/helpers/SaasProvisionerHelper.php))
* **Zero-Downtime Nativo**:
  * `strategy: RollingUpdate` com `maxSurge: 1` e `maxUnavailable: 0`.
  * `readinessProbe` validando `/login.php:80`.
  * O novo pod sobe primeiro; o pod antigo só recebe `SIGTERM` quando o novo passar no teste de prontidão.
* **Recursos Elásticos**:
  * `requests`: mínimo garantido (`cpu: 50m`, `memory: 64Mi`).
  * `limits`: teto de pico (`cpu: 250m`, `memory: 128Mi` ou personalizado).
* **Gestão de Secret `tenant-env`**:
  * Injeção de credenciais de banco, modos operacionais, chaves mestras e parsing dinâmico de variáveis extras.

### 2.5. Central de Gestão SaaS ([saas_tenants.php](file:///e:/DEV/dinovatech/dinovatech/saas_tenants.php))
* **Métricas**: Total de Instâncias, Pods Running, Suspensos/Pendentes e MRR (Receita Recorrente Mensal contratada).
* **Ações Rápidas por Instância**:
  * 🚀 **Deploy / Provisionar**: Cria Namespace, Secret, Deployment e Service.
  * ⏸️ **Suspender / ▶️ Reativar**: Escala réplicas entre 0 e 1 (gestão de inadimplência).
  * 🔄 **Atualizar Versão**: Abre modal para digitar nova tag e dispara o RollingUpdate.
  * 📋 **Terminal de Logs**: Modal com streaming dos logs do pod em tempo real.
* **Diagnóstico do Cluster**: Seção retrátil para conferir a saúde do nó ARM64, latência da API REST do K3s e pods brutos.

### 2.6. Formulários de Negócio
* [servico_form.php](file:///e:/DEV/dinovatech/dinovatech/servico_form.php): Seletor de Modelo Operacional (Padrão vs SaaS) e template de container.
* [contrato_form.php](file:///e:/DEV/dinovatech/dinovatech/contrato_form.php): Geração automática de slug a partir do nome do cliente, badge de status do Pod, configuração de banco e ENVs customizadas.
* [contratos.php](file:///e:/DEV/dinovatech/dinovatech/contratos.php) e [servicos.php](file:///e:/DEV/dinovatech/dinovatech/servicos.php): Badges visuais indicando instâncias SaaS e status do Pod K3s.

---

## 3. Variáveis de Ambiente Necessárias (.env)

```env
# Ativação do Módulo SaaS Master
SAAS_MASTER_ENABLED=true

# API REST do Cluster K3s
K3S_API_URL=https://137.131.208.19:6443
K3S_API_TOKEN=eyJhbGciOiJSUzI1NiIsImtpZCI6IlNXTEJPLXVDMG1DLURjSkE1andfR0lmQU9BZkRsTUpXYVRrNzZpMWVlbGMifQ...
K3S_INSECURE_SKIP_TLS=true
K3S_DEFAULT_IMAGE=dinovatech:latest

# Host Padrão do MariaDB para novos Tenants (opcional)
SAAS_DEFAULT_DB_HOST=137.131.208.19
```

---

## 4. Próximos Passos Futuros (Backlog)

1. **Pipeline de Build da Imagem Base ARM64**:
   * Automatizar a compilação do `devops/Dockerfile` para arquitetura `linux/arm64` e publicação em um container registry (Docker Hub ou GitHub Container Registry).
2. **Execução Automatizada de Migrations no Banco do Tenant**:
   * Ao provisionar o tenant pela primeira vez, executar o runner `scripts/migrate.php` apontando para o banco isolado recém-criado (`dino_{slug}`) para criar o esquema inicial.
3. **Backup Automatizado de Bancos Multi-Tenant**:
   * Cron job centralizado para dump diário de todas as bases `dino_*` e envio para o Oracle Object Storage.
