---
name: devops_dinovatech
description: Padrões de containerização, build Dockerfile, estratégias de deploy (SSH atual e transição para Kubernetes) e microserviços sidecars (WeasyPrint) do Dinovatech.
---

# DevOps, Containers & Runtime do Dinovatech

Esta skill documenta a arquitetura de contêineres, as diretrizes de empacotamento Docker, a geração de PDFs via sidecars e o roadmap de deploy do projeto Dinovatech.

---

## 1. Runtime da Aplicação (Container Principal)

* **Base**: Imagem baseada em `php:8.2-apache` (substituindo a legada `ilunne/php7.4-mysqli`).
* **Extensões PHP Obrigatórias**:
  * `mysqli` e `pdo_mysql` (Acesso a dados MariaDB).
  * `soap` e `xml` (Integração com webservices da NFS-e / SEFAZ).
  * `curl` e `openssl` (Comunicação com WeasyPrint, Banco Inter, InfinitePay e Google APIs).
  * `mbstring`, `gd`, `zip`, `bcmath`, `opcache`.
* **Configuração de Compatibilidade com PHP 8.x**:
  * Em `database.php`: Sempre manter `mysqli_report(MYSQLI_REPORT_OFF);` ativo na conexão para evitar exceções não capturadas em queries com retorno falso.
  * Em `app.php`: Em produção, manter `display_errors = Off` para que eventuais avisos de depreciação do PHP não quebrem endpoints com retorno JSON.

---

## 2. Mecanismo de Deploy Atual (SSH / Git Pull)

* **Funcionamento**:
  * O painel web possui uma aba de atualização que dispara `dinovatech/py/ssh.py`.
  * O script conecta via SSH no IP do host Docker (`172.17.0.1:22` ou `localhost`) autenticando com chave PEM privada e executa `git pull` na pasta mapeada.
* **Dependências do Container**:
  * O container do PHP inclui `openssh-client`, `python3` e a biblioteca `python3-paramiko`.
* **Transição Ativa para Kubernetes (K3s SaaS)**:
  * No cluster K3s (`137.131.208.19`), cada cliente roda como um Deployment independente em um namespace próprio (`tenant-{slug}`).
  * O código é empacotado na imagem Docker (ex: `dinovatech:latest` ou tags versionadas `dinovatech:vX.Y.Z`).
  * Atualizações de versão são feitas via **RollingUpdate Zero-Downtime** diretamente pelo painel SaaS (`dinovatech/saas_tenants.php`), enviando um `PATCH` para a API REST do K3s (sem SSH e sem kubectl local).

---

## 3. Geração de PDFs (Arquitetura Sidecar)

* **Importante**: O Dinovatech **NÃO** gera PDFs utilizando bibliotecas pesadas dentro do container do PHP (sem wkhtmltopdf, sem headless Chrome e sem Dompdf no runtime principal).
* **Estrutura VPS / Docker Compose**:
  * Microserviço sidecar: `ghcr.io/schweizerischebundesbahnen/weasyprint-service:latest`.
  * Porta interna: `9080` (endpoint HTTP REST `/convert/html`).
  * Rede: Conectado na mesma rede bridge (`php_dinovatech_financeiro_default`).
  * O PHP consome esse serviço através da classe auxiliar `dinovatech/helpers/PdfHelper.php` via `cURL POST`.
* **Estrutura Kubernetes (K3s Multi-Tenant)**:
  * Subido centralizado no namespace `shared-services` como Service ClusterIP:
    `http://weasyprint.shared-services.svc.cluster.local:9080/convert/html`
  * Todas as instâncias SaaS consomem esse endpoint compartilhado, economizando ~250MB de RAM por tenant.

---

## 4. Orquestração SaaS Multi-Tenant & Zero-Downtime (K3s)

* **Estratégia de RollingUpdate**:
  * Cada pod roda com `strategy: RollingUpdate` (`maxSurge: 1`, `maxUnavailable: 0`).
  * O K3s cria o novo pod da nova versão antes de desligar o antigo.
  * O pod só recebe tráfego após aprovação no `readinessProbe` (`HTTP GET /login.php:80`).
  * Só após o novo pod estar 100% `Ready`, o pod antigo recebe `SIGTERM` para encerramento suave.
* **Recursos Elásticos por Pod**:
  * `requests`: `cpu: 50m`, `memory: 64Mi` (mínimo garantido).
  * `limits`: `cpu: 250m` a `500m`, `memory: 128Mi` a `256Mi` (teto configurável no contrato).
* **Injeção de Configurações (Secret `tenant-env`)**:
  * Variáveis de banco (`DB_HOSTNAME`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`).
  * Modos operacionais (`APP_MODE_VET=true/false`).
  * Chave mestra criptográfica (`APP_MASTER_KEY`).
  * Variáveis extras customizadas (`custom_envs`).

---

## 5. Localização dos Artefatos de DevOps & IaC

* `devops/Dockerfile`: Especificação de build da imagem PHP 8.2+ para instâncias SaaS.
* `devops/docker-compose.yml`: Compose para orquestração local/VPS com o sidecar WeasyPrint.
* `devops/k8s/01-caddy-ingress.yaml`: Ingress Caddy (On-Demand TLS e roteamento dinâmico upstream).
* `devops/k8s/02-weasyprint-shared.yaml`: WeasyPrint compartilhado em `shared-services`.
* `database/migrations/20261022_0001_add_saas_multi_tenant_schema.sql`: Schema base da tabela `saas_tenants`.
* `database/migrations/20261022_0002_add_db_host_to_saas_tenants.sql`: Suporte a host e porta de banco dedicado.
* `database/migrations/20261022_0003_add_env_fields_to_saas_tenants.sql`: Suporte a ENVs e chaves por cliente.
* `dinovatech/helpers/K3sClientHelper.php`: Cliente REST cURL para API do Kubernetes.
* `dinovatech/helpers/SaasProvisionerHelper.php`: Orquestrador de ciclo de vida de tenants (Deploy, Scale, Update, Logs).

