# Walkthrough: Integração K3s REST API e Ativação Controlada via .env

- **Data**: 2026-10-22
- **Objetivo**: Integrar o backend do Dinovatech diretamente à API REST do K3s (porta 6443) com ativação condicional por variável de ambiente (`SAAS_MASTER_ENABLED`).

---

## 1. O que foi Implementado

### 1.1. ServiceAccount e Bearer Token no Cluster K3s
* Criada a ServiceAccount `dinovatech-saas-admin` no namespace `kube-system` com permissão de `cluster-admin`.
* Gerado secret dedicado `dinovatech-saas-admin-token` do tipo `kubernetes.io/service-account-token` para autenticação de longa duração.

### 1.2. Configurações de Ambiente em [config.php](file:///e:/DEV/dinovatech/dinovatech/config.php)
* Constantes adicionadas:
  * `SAAS_MASTER_ENABLED`: Booleano (liga/desliga o módulo).
  * `K3S_API_URL`: URL da API do cluster (default `https://137.131.208.19:6443`).
  * `K3S_API_TOKEN`: Bearer token do cluster.
  * `K3S_INSECURE_SKIP_TLS`: Permite ignorar validação estrita de SSL em IP público.
  * `K3S_DEFAULT_IMAGE`: Imagem padrão para novos pods.
* Criado o template [.env.example](file:///e:/DEV/dinovatech/.env.example).

### 1.3. Helper de Conexão: [K3sClientHelper.php](file:///e:/DEV/dinovatech/dinovatech/helpers/K3sClientHelper.php)
* Comunicação via cURL nativo do PHP com os endpoints oficiais do Kubernetes:
  * `checkHealth()`: Bate em `/readyz` e mede latência em milissegundos.
  * `getNodes()`: Lista nós, arquitetura (ARM64) e memória/CPU alocáveis.
  * `listNamespaces()` e `createNamespace()`: Gestão de isolamento por cliente.
  * `listPods()`: Monitoramento de pods e estados (`Running`, `Pending`, etc.).
  * `scaleDeployment()`: Ajusta réplicas para 0 (suspensão) ou 1 (reativação).
  * `restartDeployment()`: Rollout restart gracioso.
  * `getPodLogs()`: Leitura de logs do container para suporte.

### 1.4. Tela de Gestão e Diagnóstico: [saas_tenants.php](file:///e:/DEV/dinovatech/dinovatech/saas_tenants.php)
* Protegida estritamente por `SAAS_MASTER_ENABLED`. Se desativada, retorna 403 e aviso amigável.
* Painel visual com status em tempo real da conexão, latência, especificações do nó `k3s-master-arm` e tabela de pods do cluster com botão de teste AJAX.
* Item adicionado no menu lateral ([sidebar.php](file:///e:/DEV/dinovatech/dinovatech/components/sidebar.php)), visível apenas quando o módulo está ativo.

---

## 2. Como Ativar no Ambiente de Playground

Para ativar a integração no seu ambiente de teste, adicione ao seu arquivo `.env`:

```env
SAAS_MASTER_ENABLED=true
K3S_API_URL=https://137.131.208.19:6443
K3S_API_TOKEN=eyJhbGciOiJSUzI1NiIsImtpZCI6IlNXTEJPLXVDMG1DLURjSkE1andfR0lmQU9BZkRsTUpXYVRrNzZpMWVlbGMifQ...
K3S_INSECURE_SKIP_TLS=true
```

*(No seu servidor de produção de 1GB, basta manter `SAAS_MASTER_ENABLED=false` ou omitir a linha, e o sistema continuará operando exatamente como sempre esteve, sem qualquer alteração).*
