# Dinovatech ERP & Plataforma SaaS Multi-Tenant

Sistema integrado de gestão empresarial e operacional com suporte nativo a múltiplos segmentos (comercial, serviços e veterinário) e arquitetura de entrega **SaaS Multi-Tenant** via orquestração de containers no **Kubernetes (K3s)**.

---

## 🏛️ Visão Geral da Arquitetura

O **Dinovatech** opera em dois modelos complementares:
1. **Instância Central (Painel Master / ERP de Gestão)**: Responsável pelo controle de clientes, contratos recorrentes, emissão de cobranças (Pix/Cartão via InfinitePay e boletos), faturamento e catálogo de serviços.
2. **Plataforma SaaS Multi-Tenant (Container-per-Tenant)**: Cada cliente contratante recebe uma instância isolada da aplicação rodando em container dedicado no cluster K3s, com banco de dados dedicado, domínio com certificado SSL emitido sob demanda e microserviços compartilhados.

```
                          [ USUÁRIOS & CLIENTES ]
                                     |
               (Portas 80 / 443 - HTTPS com On-Demand TLS)
                                     |
                                     v
                 +---------------------------------------+
                 |             CADDY INGRESS             |
                 |      (Namespace: ingress-caddy)       |
                 |  - TLS On-Demand (Let's Encrypt)      |
                 |  - Roteador dinâmico *.dinovatech...  |
                 +-------------------+-------------------+
                                     |
     +-------------------------------+-------------------------------+
     | (upstream dinâmico regex)                                     |
     v                                                               v
+-------------------------------+               +-------------------------------+
|     TENANT CLÍNICA ALFA       |               |      TENANT PET BETA          |
| Namespace: tenant-clinica-alfa|               | Namespace: tenant-pet-beta    |
| - Pod PHP 8.2 Dinovatech      |               | - Pod PHP 8.2 Dinovatech      |
| - Service: app-clinica-alfa:80|               | - Service: app-pet-beta:80    |
| - Secret: tenant-env          |               | - Secret: tenant-env          |
|   (dino_clinica_alfa)         |               |   (dino_pet_beta)             |
+---------------+---------------+               +---------------+---------------+
                |                                               |
                +-----------------------+-----------------------+
                                        |
                                        v
                         +-------------------------------+
                         |     SHARED SERVICES           |
                         | Namespace: shared-services    |
                         | - WeasyPrint REST (PDFs:9080) |
                         +-------------------------------+
                                        |
                                        v
                         +-------------------------------+
                         |     MARIADB DEDICADO/OCI      |
                         | - 1 Database isolado p/ tenant|
                         +-------------------------------+
```

---

## 🚀 Principais Tecnologias

- **Backend**: PHP 7.4 / 8.2+ (Procedural e estruturado com PDO / MySQLi)
- **Frontend**: HTML5, CSS3, Tailwind CSS, JavaScript Vanilla
- **Banco de Dados**: MariaDB 10.x (Database-per-Tenant)
- **Orquestração de Containers**: Kubernetes leve (K3s `v1.36.5+k3s1` ARM64 na Oracle Cloud)
- **Ingress & Roteamento**: Caddy Ingress com suporte nativo a On-Demand TLS
- **Sidecars & Microserviços**: WeasyPrint REST para compilação server-side de relatórios e receitas em PDF
- **Integrações Financeiras & Fiscais**:
  - Pagamentos e Recorrência Pix: InfinitePay Webhooks & API
  - Emissão Fiscal: NFS-e Nacional / ISS-DF (Nota Control) com assinatura digital XMLDSig
  - Armazenamento em Nuvem: Oracle Object Storage (S3 compatível) via URLs pré-autenticadas (PAR)

---

## 📂 Estrutura de Diretórios

```
dinovatech/
├── .agents/                    # Regras, skills e documentação de workflows
├── database/                   # Scripts e migrações SQL versionadas
│   └── migrations/             # Migrações incrementais
├── devops/                     # Infraestrutura como Código (IaC) e Docker
│   ├── Dockerfile              # Imagem base PHP 8.2+ para os tenants
│   ├── docker-compose.yml      # Ambiente local/VPS com sidecars
│   └── k8s/                    # Manifestos Kubernetes (Caddy, WeasyPrint)
├── dinovatech/                 # Aplicação principal (ERP & Módulo SaaS)
│   ├── config.php              # Configurações globais e flags de ambiente
│   ├── app.php                 # Roteador central de requisições e ações AJAX
│   ├── saas_tenants.php        # Central de Gestão SaaS Multi-Tenant
│   ├── contratos.php           # Gestão de Contratos de Clientes
│   ├── servicos.php            # Catálogo de Serviços (Padrão e SaaS)
│   └── helpers/                # Classes auxiliares
│       ├── K3sClientHelper.php     # Cliente REST nativo para a API K3s
│       ├── SaasProvisionerHelper.php # Orquestrador de ciclo de vida dos Pods
│       └── PdfHelper.php           # Comunicação com o WeasyPrint
├── docs/                       # Documentação técnica detalhada
│   ├── planejamento-multi-tenant-container/ # Especificação e status da arquitetura SaaS
│   └── migracao/               # Documentação técnica da migração NFS-e Nacional
└── index.php                   # Ponto de entrada web
```

---

## ⚙️ Variáveis de Ambiente (.env)

Exemplo das variáveis necessárias para habilitar o módulo de orquestração SaaS:

```env
# Ativação do Módulo SaaS Master
SAAS_MASTER_ENABLED=true

# API REST do Cluster K3s
K3S_API_URL=https://137.131.208.19:6443
K3S_API_TOKEN=seu_bearer_token_admin_k3s
K3S_INSECURE_SKIP_TLS=true
K3S_DEFAULT_IMAGE=dinovatech:latest

# Host Padrão do MariaDB para novos Tenants
SAAS_DEFAULT_DB_HOST=137.131.208.19
```

---

## 📚 Documentação Adicional

- [Documentação Detalhada da Arquitetura SaaS Multi-Tenant](docs/planejamento-multi-tenant-container/README.md)
- [Walkthrough de Implementação K3s & Caddy](.agents/planos_e_workflows/walkthroughs/2026-10-22_arquitetura_saas_k3s_caddy.md)
- [Especificação de Infraestrutura OCI & K3s](.agents/skills/infra_dinovatech/SKILL.md)
- [Padrões de DevOps & Containers](.agents/skills/devops_dinovatech/SKILL.md)