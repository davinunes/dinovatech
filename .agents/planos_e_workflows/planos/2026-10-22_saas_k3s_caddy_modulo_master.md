# Plano de Implementação: Arquitetura SaaS K3s, Caddy Ingress e Módulo Master de Tenants

- **Data**: 2026-10-22
- **Status**: Planejado / Em Andamento
- **Responsável**: Antigravity & Davi Nunes
- **Cluster Alvo**: `k3s-master-arm` (OCI Ampere 2 OCPUs, 11 GB RAM, IP `137.131.208.19`)

---

## 1. Validação de Infraestrutura Realizada

A infraestrutura foi acessada via SSH dedicado e inspecionada com 100% de êxito:
* **Nó Master K3s**: `v1.36.5+k3s1` ativo e estável há 3+ dias.
* **Recursos**: 2 OCPUs ARM64 (`Neoverse-N1`), 11 GiB RAM (7.3 GiB livres), 48 GB NVMe (apenas 4 GB em uso).
* **Portas Abertas e Alcançáveis via Internet**:
  * `22` (SSH) — OK
  * `80` (HTTP) — OK (`TcpTestSucceeded: True`)
  * `443` (HTTPS) — OK (`TcpTestSucceeded: True`)
  * `6443` (Kubernetes API com `tls-san` para o IP público) — OK (`TcpTestSucceeded: True`)

---

## 2. Decisões Arquiteturais Alinhadas

### 2.1. Microserviço WeasyPrint Centralizado (Compartilhado)
* **Decisão**: Em vez de rodar 1 sidecar WeasyPrint para cada cliente (o que gastaria 150MB de RAM por tenant), rodaremos **1 único Deployment do WeasyPrint** no namespace `shared-services`.
* **Benefício**: Economia de mais de 80% da memória RAM do cluster. Todos os pods de clientes consomem o endpoint interno do cluster:
  `http://weasyprint.shared-services.svc.cluster.local:9080/convert/html`.

### 2.2. Ingress com Caddy (SSL Wildcard + On-Demand TLS para Domínios Próprios)
* **Decisão**: Substituir/complementar o `ingress-nginx` temporário pelo **Caddy**.
* **Motivo**: O Caddy automatiza certificados Let's Encrypt para subdomínios e possui **On-Demand TLS**. Quando um cliente apontar seu domínio próprio via CNAME (ex: `sistema.clinicavet.com.br`), o Caddy emite o certificado SSL no primeiro acesso em tempo real, consultando o endpoint de validação do Dinovatech (`ask http://.../api/check-domain`).

### 2.3. Pipeline e API de Deploy Própria no Dinovatech vs GitHub Actions
* **Decisão**: Criar a API de orquestração diretamente conectada ao K3s (usando a porta 6443 e ServiceAccount Token) acionada pelo **Módulo SaaS Master do Dinovatech**.
* **Fluxo**:
  1. A imagem base do Dinovatech é construída (multi-arch ARM64/AMD64) e versionada (ex: `dinovatech:v1.0.0`).
  2. Ao cadastrar um novo cliente no painel SaaS, a API interna:
     - Cria o banco `db_cliente_xxx` no MariaDB.
     - Executa o pacote de migrações iniciais.
     - Cria o Namespace `cliente-xxx`.
     - Injeta os Secrets (`.env` com dados do banco e credenciais específicas).
     - Dá `apply` no Deployment do pod PHP (consumindo apenas ~35 MB de RAM).
     - Roteia o Ingress no Caddy para o novo tenant.

---

## 3. Especificação do Módulo SaaS no Dinovatech (Painel Master)

### 3.1. Estrutura de Banco de Dados (`saas_tenants`)
Tabela proposta para armazenar os contratos SaaS e metadados de containers:

```sql
CREATE TABLE IF NOT EXISTS saas_tenants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    nome_instancia VARCHAR(60) NOT NULL UNIQUE,      -- ex: clinica-alfa
    subdominio VARCHAR(100) NOT NULL UNIQUE,         -- ex: alfa.dinovatech.com.br
    dominio_customizado VARCHAR(150) NULL,          -- ex: sistema.clinicavet.com.br
    db_name VARCHAR(60) NOT NULL,                    -- ex: dinovatech_alfa
    db_user VARCHAR(60) NOT NULL,
    db_password VARCHAR(100) NOT NULL,
    namespace_k8s VARCHAR(60) NOT NULL,             -- ex: tenant-alfa
    status_pod ENUM('running', 'stopped', 'suspended', 'error') DEFAULT 'stopped',
    versao_imagem VARCHAR(50) DEFAULT 'v1.0.0',
    limite_cpu VARCHAR(20) DEFAULT '250m',
    limite_memoria VARCHAR(20) DEFAULT '128Mi',
    bloqueio_inadimplencia TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX (cliente_id),
    INDEX (status_pod)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 3.2. Ações de Gestão no Painel
* **Provisionar Tenant**: Botão de criação em 1 clique (DB + Pod + Ingress).
* **Suspender / Reativar**: Dispara `scale replicas=0` ou `scale replicas=1` via API K8s.
* **Reiniciar Pod**: `rollout restart` para recarregar configurações ou cache.
* **Logs em Tempo Real**: Leitura dos logs do container PHP para suporte e diagnóstico.
* **Atualização em Lote**: Seletor para atualizar todos os tenants para uma nova tag de imagem.

---

## 4. Etapas de Execução

1. [x] **Diagnóstico da VPS K3s e registro na skill de infraestrutura**: Conectividade, recursos e portas 80/443/6443 validadas.
2. [ ] **Subir o microserviço centralizado WeasyPrint**: Criar manifesto K8s no namespace `shared-services` para compilação de PDFs.
3. [ ] **Configurar Caddy Ingress Controller**: Subir Caddy no cluster com suporte a On-Demand TLS.
4. [ ] **Construir e Publicar a Imagem Docker Base ARM64/Multi-arch**: Gerar imagem a partir de `devops/Dockerfile`.
5. [ ] **Criar Migration e Módulo SaaS no Dinovatech**:
   - Migration da tabela `saas_tenants`.
   - Classe helper `K3sClientHelper.php` para envio de comandos e manifestos à API 6443 do K3s.
   - Interface visual de gerenciamento de Tenants no Dinovatech.
6. [ ] **Testar Provisionamento do Primeiro Tenant Piloto**: Validação de ponta a ponta (Criação -> Pod no ar -> Acesso HTTPS).
