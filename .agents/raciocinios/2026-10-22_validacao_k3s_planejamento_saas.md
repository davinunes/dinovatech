# Raciocínio: Validação da VPS K3s e Estratégia de Containerização SaaS

- **Data**: 2026-10-22
- **Contexto**: Acesso à nova VPS ARM64 da Oracle Cloud (`137.131.208.19`), validação da instalação do K3s e definição da arquitetura para transformar o Dinovatech em SaaS Multi-Tenant por containers.

---

## 1. Descobertas e Diagnóstico da VPS

1. **Hardware e SO**:
   - Hostname: `k3s-master-arm`.
   - Arquitetura: `aarch64` (ARM64 / Ampere Neoverse-N1, 2 OCPUs).
   - Memória: 11 GiB de RAM disponível (1.1 GB em uso, 7.3 GB totalmente livres).
   - Armazenamento: 48 GB NVMe com 44 GB livres (9% em uso).
   - SO: Ubuntu 26.04 LTS.

2. **Instalação do K3s**:
   - Versão: `v1.36.5+k3s1` rodando com serviço systemd ativo há 3+ dias.
   - Configuração `/etc/rancher/k3s/config.yaml`:
     - Traefik desabilitado (`disable: [traefik]`) de propósito.
     - `tls-san` inclui o IP público `137.131.208.19`.
     - `kubelet-arg` reserva memória/cpu para estabilidade.
   - Estado de Pods: `coredns`, `metrics-server` e `local-path-provisioner` 100% saudáveis.

3. **Conectividade de Rede e Firewall**:
   - Testes externos disparados via PowerShell no Windows confirmaram alcance da Internet:
     - Porta `80` (HTTP): Aberta e respondendo (`TcpTestSucceeded: True`).
     - Porta `443` (HTTPS): Aberta e respondendo (`TcpTestSucceeded: True`).
     - Porta `6443` (API K8s): Aberta e respondendo (`TcpTestSucceeded: True`).

---

## 2. Análise Crítica das Ideias Propostas

1. **Apenas 1 nó gerador de PDF (WeasyPrint Centralizado)**:
   - **Veredito**: Excelente. O WeasyPrint consome ~150 MB de RAM. Multiplicar isso por cada cliente seria um desperdício enorme de recursos. Um único Pod de WeasyPrint num namespace compartilhado (`shared-services`) consegue processar centenas de requisições concorrentes de PDFs com folga para dezenas de clientes.

2. **Caddy como Ingress (On-Demand TLS)**:
   - **Veredito**: A melhor escolha possível. O Traefik e Nginx exigem criar Ingress resources e cert-manager para cada domínio customizado de cliente. O Caddy, com a diretiva `on_demand_tls` e `ask http://...`, emite certificados Let's Encrypt automaticamente para qualquer domínio CNAME que o cliente apontar, no exato segundo da primeira requisição.

3. **API de Deploy Própria conectando no K3s (sem depender de GitHub Actions de início)**:
   - **Veredito**: Muito mais simples e imediato. O Dinovatech terá seu próprio `K3sClientHelper.php` que faz requisições HTTPS para a porta `6443` do cluster. Com isso, ao clicar em "Criar Instância" no painel SaaS do Dinovatech, o próprio sistema cria o banco, roda migrações e sobe o Pod.

4. **Módulo de Contratos/SaaS no Dinovatech**:
   - **Veredito**: Perfeito encaixe. O Dinovatech já possui módulos de Clientes, Contratos e Financeiro (com Pix InfinitePay). Bastará adicionar a camada de gerenciamento de Pods/Tenants vinculada aos contratos existentes.
