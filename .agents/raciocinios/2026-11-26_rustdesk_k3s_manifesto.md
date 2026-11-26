# Raciocínio Diagnóstico e Arquitetural: RustDesk Server no Cluster K3s ARM64

- **Data**: 2026-11-26
- **Ambiente**: OCI Ampere A1 (ARM64), 2 vCPUs, 11 GB RAM, K3s v1.36.5, Caddy Ingress, Ubuntu 26.04.
- **Solicitante**: Administrador Dinovatech.

---

## 1. Avaliação de Viabilidade e Utilidade ("Isso Presta?")
- **RustDesk OSS**: Projeto de alta maturidade em Rust, sem telemetria abusiva ou falsos positivos de "uso comercial" presentes no AnyDesk/TeamViewer.
- **Rendimento de Rede**: Em cenários onde ambas as pontas conseguem negociação STUN (hole punching), o tráfego de vídeo e controle é **P2P direto**, aliviando completamente o consumo de banda da VPS. Quando uma das pontas está em NAT simétrico (corporativo restrito), o tráfego é canalizado pelo `hbbr` local (latência nacional, sem sair do Brasil).
- **Consumo de Hardware**: Irrisório (~30MB a 60MB somando `hbbs` e `hbbr`), insignificante no nó de 11 GB de RAM.

---

## 2. Decisões Arquiteturais no Kubernetes / K3s

### 2.1 Por que Volume (PVC) e NÃO Banco Remoto (MySQL da Oracle)?
- O RustDesk Server OSS não possui suporte nativo a bancos MySQL ou Postgres (recurso restrito à versão Pro).
- Todo o estado do servidor OSS consiste em:
  1. `id_ed25519` (chave privada) e `id_ed25519.pub` (chave pública).
  2. Arquivos de banco SQLite leves (`db.sqlite3` / logs).
- **Crítico**: Se o pod reiniciar sem PVC, um novo par de chaves é gerado e todos os clientes perdem a conexão imediatamente.
- **Solução**: `PersistentVolumeClaim` de 1 GiB com `storageClassName: local-path`, montado em `/root` de ambos os containers.

### 2.2 Estrutura do Pod Unificado (hbbs + hbbr)
- Rodar `hbbs` e `hbbr` no mesmo Pod compartilha o volume de dados das chaves de forma direta, garantindo que o `hbbr` com `-k _` consiga ler `/root/id_ed25519.pub` gerado pelo `hbbs`.
- A estratégia de Deployment deve ser `type: Recreate` para evitar concorrência no volume e SQLite local durante eventuais reinicializações.

### 2.3 Roteamento de Portas e Firewall
- Como o RustDesk requer portas TCP (21115, 21116, 21117, 21118, 21119) e porta UDP crítica (21116), ele não passa pelo Ingress Caddy (HTTP/HTTPS 80/443).
- O Service `type: LoadBalancer` utiliza o Klipper LB nativo do K3s, ligando as portas diretamente no IP público do nó (`137.131.208.19`).
- O usuário confirmou que o firewall (Security List) da OCI já está liberado, eliminando o principal gargalo de bloqueio UDP.

---

## 3. Integração com Dinovatech (`saas_tenants.php`)
- O usuário optou pela abordagem de manifesto dedicado (`devops/k8s/03-rustdesk-server.yaml`).
- Caso deseje monitorar o pod via Dinovatech:
  - Pode-se registrar uma linha na tabela `saas_tenants` com `namespace_k8s = 'app-rustdesk'` e `slug_instancia = 'rustdesk'`.
  - Isso ativa os botões de status (Running), ver logs e restart na UI do Dinovatech sem poluir o fluxo de deploy dos tenants PHP.
