---
name: infra_dinovatech
description: Especificações e particularidades da infraestrutura de produção do Dinovatech (Oracle Cloud Free Tier, Docker, MariaDB remoto, Storage S3/Oracle Object Storage e restrições de ambiente).
---

# Infraestrutura & Ambiente do Dinovatech

Este documento registra as especificações da infraestrutura remota do Dinovatech e as decisões arquiteturais associadas.

## 1. Servidor de Aplicação (Compute / VPS)
- **Provedor**: Oracle Cloud Infrastructure (OCI) - Free Tier.
- **Configuração de Hardware**: 1 vCPU, 1 GB RAM, 100 GB SSD.
- **Sistema Operacional**: Ubuntu Linux.
- **Gerenciamento de Containers**:
  - **Docker Engine** com **Portainer** para gestão gráfica.
  - **Servidor Web**: **Caddy** (Reverse Proxy & HTTPS automático).
  - **Container PHP**: Imagem moderna `ilunne/php8.3-x86-base` (substituindo a legada `ilunne/php7.4-mysqli`).

## 2. Banco de Dados (MariaDB Gerenciado)
- **Instância**: Instância separada MariaDB (Free Tier na Oracle Cloud).
- **Atenção ao Fuso Horário**:
  - A instância do MariaDB é remota e **não herda o relógio do Ubuntu da VPS**.
  - O fuso horário do MariaDB opera em **UTC (+00:00)** por padrão.
  - **Regra do Projeto**: A aplicação PHP **sempre** define explicitamente `SET time_zone = '-03:00'` na conexão (`DBConnect()` em `database.php`) para garantir que funções de data como `NOW()`, `CURDATE()` e `CURRENT_TIMESTAMP` operem no Horário Oficial de Brasília (GMT-3).

## 3. Armazenamento de Arquivos e Uploads (Oracle Object Storage / S3)
- **Mecanismo**: Oracle Object Storage via **Pre-Authenticated Request (PAR) / URL Pré-configurada** com validade estendida.
- **Upload**: Realizado via HTTP `PUT` com cURL direto para o endpoint preauth.
- **Arquitetura Stateless dos Pods K3s & Migração Orientada ao Banco**:
  - Os pods de clientes no Kubernetes são estritamente *stateless* (sem volumes persistentes para uploads).
  - Toda a mídia (logos, fotos de pets, fotos de banho, comprovantes de despesas e assinaturas) é enviada diretamente para o OCI.
  - Para migrar arquivos locais legados sem inflar o bucket com lixo acumulado no disco, a ferramenta `scripts/migrar_arquivos_locais_para_s3.php` e a interface `dinovatech/saas_db_tool.php` **varrem primeiro o banco de dados** (`NOT LIKE 'http%'`) e sobem apenas o que está em uso efetivo, ignorando arquivos órfãos em disco.
- **Limitação Conhecida**:
  - Por utilizar URL pré-autenticada genérica sem credenciais completas de IAM SDK, **não há API direta para exclusão física de objetos obsoletos** (ex: imagens antigas substituídas).
  - Políticas de retenção ou scripts de expiração devem ser considerados caso o bucket precise de limpeza periódica.

## 4. Serviço de Geração de PDFs (WeasyPrint REST)
- **Mecanismo**: Microserviço sidecar Docker isolado para compilação server-side de documentos e receitas em PDF.
- **Imagem & Container**: `weasyprint` (`ghcr.io/schweizerischebundesbahnen/weasyprint-service:latest`).
- **Rede Docker**: Conectado diretamente à rede `php_dinovatech_financeiro_default` compartilhada com o container `homepage-php`.
- **Comunicação**: O backend PHP interage via cURL HTTP através do endpoint `http://weasyprint:9080/convert/html` (encapsulado pela classe `dinovatech/helpers/PdfHelper.php`).
- **Controle de Recursos (Proteção da VPS 1GB RAM)**:
  - Limite rígido de memória definido em `350M` (reserva `80M`) no Compose.
- **Tratamento de Imagens & CSS**:
  - `PdfHelper.php` converte automaticamente caminhos locais de imagens para **Data URI Base64** antes do envio ao container, tornando o documento autocontido.
  - Atributos legados de dimensões (`width`/`height` do editor TinyMCE) são normalizados em CSS inline (`!important`) para garantir proporções e fidelidade visual na impressão A4.

## 5. Cluster K3s Multi-Tenant (Control-Plane / Master ARM64)
- **Provedor**: Oracle Cloud Infrastructure (OCI) - Instância Ampere ARM64.
- **IP Público**: `137.131.208.19` (Hostname: `k3s-master-arm`).
- **Configuração de Hardware**:
  - **CPU**: 2 OCPUs / vCPUs ARM64 (`Neoverse-N1`).
  - **RAM**: 11 GiB de RAM disponível (12 GB provisionados).
  - **Disco**: 48 GB NVMe/SSD (`/dev/sda1` com apenas ~4.0 GB / 9% em uso).
- **Sistema Operacional**: Ubuntu 26.04 LTS (Kernel 7.0.0-1009-oracle aarch64).
- **Versão do K3s**: `v1.36.5+k3s1` (com containerd `2.3.4-k3s1.36`).
- **Configuração K3s (`/etc/rancher/k3s/config.yaml`)**:
  - `write-kubeconfig-mode: "644"`
  - `disable: [traefik]` (Traefik desativado para uso exclusivo do Caddy Ingress).
  - `kubelet-arg`: Reserva de sistema (`cpu=150m,memory=1000Mi`) e kube (`cpu=100m,memory=500Mi`) para estabilidade.
  - `tls-san`: `137.131.208.19` (permite gestão remota via API REST na porta 6443 sem bloqueios de hostname).
- **Portas e Conectividade Externa**:
  - **Porta 22 (SSH)**: Acesso administrativo via chave privada.
  - **Portas 80 (HTTP) e 443 (HTTPS)**: Roteadas diretamente para o **Caddy Ingress** via Service LoadBalancer (`svclb-caddy-service`).
  - **Porta 6443 (K3s REST API)**: Acessível para o backend do Dinovatech via Bearer Token (`dinovatech-saas-admin-token`).
- **Ingress Controller (Caddy com On-Demand TLS)**:
  - Namespace: `ingress-caddy`.
  - Manifestos: `devops/k8s/01-caddy-ingress.yaml`.
  - Certificados SSL emitidos automaticamente na hora (*on_demand*) via Let's Encrypt / ZeroSSL para qualquer subdomínio `*.dinovatech.com.br` ou domínio customizado via CNAME.
  - Roteamento upstream dinâmico para os pods de clientes: `reverse_proxy app-{re.host.slug}.tenant-{re.host.slug}.svc.cluster.local:80`.
- **Serviço Centralizado WeasyPrint**:
  - Namespace: `shared-services`.
  - Manifestos: `devops/k8s/02-weasyprint-shared.yaml`.
  - DNS interno do Cluster: `http://weasyprint.shared-services.svc.cluster.local:9080/convert/html`.
  - Compartilhado entre todas as instâncias SaaS para evitar desperdício de memória RAM.
- **Topologia de Banco de Dados Multi-Tenant**:
  - Arquitetura **Database-per-Tenant**: Todos os clientes compartilham o servidor MariaDB (remoto OCI ou dedicado), com 1 banco de dados isolado por cliente (`dino_{slug}`).
  - Credenciais e host configuráveis por tenant (`saas_tenants.db_host`, `saas_tenants.db_port`).

## 6. Regras de Desenvolvimento e Compilação
- Desenvolvemos e alteramos apenas o código localmente.
- Não executar compiladores pesados ou dependências no host a menos que solicitado.
- Scripts PHP e SQL devem manter compatibilidade com **PHP 7.4/8.2+** e **MariaDB 10.x**.

