# Raciocínio Diagnóstico e Arquitetural: RustDesk Server no Cluster K3s ARM64 e Automação Dinovatech

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

---

## 3. Automação via API do K3s e Migração no Dinovatech

### 3.1 Provisionamento Direto via API REST do K3s (Porta 6443)
- O Dinovatech já possui o `K3sClientHelper` conectado à API do K3s via token Bearer.
- Adicionamos o método `SaasProvisionerHelper::provisionRustDesk`:
  - Envia requisições HTTP REST para a API do K3s criando:
    1. Namespace `app-rustdesk`
    2. PVC `rustdesk-pvc` (1Gi, local-path)
    3. Deployment `rustdesk-server` (hbbs + hbbr)
    4. Service `rustdesk-service` (LoadBalancer TCP/UDP 21115-21119)
- Se o usuário clicar no botão roxo **"Deploy"** na linha do RustDesk dentro de `saas_tenants.php`, o Dinovatech dispara essa rotina nativamente.

### 3.2 Migração de Banco de Dados (`database/migrations/20261126_0001_register_rustdesk_service.sql`)
- Torna a coluna `id_recorrencia` na tabela `saas_tenants` opcional (`NULL`), viabilizando o registro de serviços de infraestrutura internos sem necessidade de um contrato de venda falso.
- Insere o registro em `saas_tenants` com `slug_instancia = 'rustdesk'`.

---

## 4. Garantia Mandatória de Isolamento do "Modo Vet" (`app_mode_vet`)

### Por que o Modo Veterinário NÃO é Afetado?
1. **Configuração por Tenant**: No Dinovatech, a flag `app_mode_vet` é armazenada individualmente para cada linha da tabela `saas_tenants`.
2. **Valor Estrito**: A migração e o script definem `app_mode_vet = 0` **estritamente na linha do RustDesk**, pois se trata de um utilitário de acesso remoto.
3. **Clínicas Existentes Intocadas**: Nenhum registro existente de clientes de clínicas veterinárias tem seu `app_mode_vet` (valor 1) alterado.
4. **Isolamento de Pods**: Os pods de clínicas PHP rodam com a variável `APP_MODE_VET=true` em seus respectivos secrets `tenant-env`. O pod do RustDesk roda a imagem oficial Rust (`rustdesk/rustdesk-server`), sem PHP, sem ler os segredos das clínicas e em um namespace completamente separado (`app-rustdesk`).
