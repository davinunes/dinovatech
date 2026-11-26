# Plano de Implementação: Servidor RustDesk OSS no K3s

- **Data**: 2026-11-26
- **Alvo**: Cluster K3s OCI ARM64 (`137.131.208.19`)
- **Objetivo**: Disponibilizar o manifesto Kubernetes pronto para execução e documentar os passos de subida e configuração dos clientes.

---

## 1. Arquivos Criados / Modificados
- `devops/k8s/03-rustdesk-server.yaml`: Manifesto unificado do Kubernetes.
- `.agents/raciocinios/2026-11-26_rustdesk_k3s_manifesto.md`: Raciocínio arquitetural.
- `.agents/planos_e_workflows/walkthroughs/2026-11-26_rustdesk_k3s_manifesto.md`: Resumo e instruções operacionais.

---

## 2. Itens do Manifesto
1. **Namespace**: `app-rustdesk`.
2. **PersistentVolumeClaim**: `rustdesk-pvc` (1 GiB, `local-path`).
3. **Deployment**: `rustdesk-server` (1 réplica, `strategy: Recreate`) com 2 containers:
   - `hbbs`: porta 21115 (TCP), 21116 (TCP/UDP), 21118 (TCP), chave forçada `-k _`, relay apontando para `137.131.208.19:21117`.
   - `hbbr`: porta 21117 (TCP), 21119 (TCP), validação `-k _`.
4. **Service**: `rustdesk-service` (`type: LoadBalancer`).

---

## 3. Plano de Teste e Validação
1. Aplicar o manifesto no cluster:
   ```bash
   kubectl apply -f devops/k8s/03-rustdesk-server.yaml
   ```
2. Validar pod rodando:
   ```bash
   kubectl -n app-rustdesk get pods -o wide
   ```
3. Extrair a chave pública:
   ```bash
   kubectl -n app-rustdesk exec -it deploy/rustdesk-server -c hbbs -- cat /root/id_ed25519.pub
   ```
4. Baixar o cliente RustDesk e configurar ID Server / Key para validar conexão verde ("Pronto").
