# Walkthrough / Guia de Implantação: Servidor RustDesk no K3s

- **Data de Entrega**: 2026-11-26
- **Status**: Concluído (Manifesto gerado e documentado)
- **Arquivo Criado**: [`devops/k8s/03-rustdesk-server.yaml`](file:///d:/dev/github/dinovatech/devops/k8s/03-rustdesk-server.yaml)

---

## 1. Resumo da Solução

Foi estruturado o manifesto unificado para executar o **RustDesk Server OSS** no cluster K3s da Oracle Cloud (ARM64), isolado no namespace `app-rustdesk`.

### Características da Arquitetura:
* **Armazenamento Seguro das Chaves**: Um `PersistentVolumeClaim` de 1 GiB (`local-path`) garante que as chaves criptográficas (`id_ed25519` e `id_ed25519.pub`) nunca se percam entre reinicializações.
* **Containers Co-localizados**: Os binários `hbbs` (descoberta/ID) e `hbbr` (relay) rodam juntos no mesmo pod compartilhando o volume das chaves.
* **Segurança Reforçada (`-k _`)**: O servidor rejeita conexões de terceiros que não possuam a chave pública do seu servidor.
* **Exposição de Portas**: Um Service do tipo `LoadBalancer` expõe as portas TCP (21115-21117) e UDP (21116).

---

## 2. Como Subir no Servidor Remoto (K3s)

No terminal da sua VPS OCI (`k3s-master-arm`):

```bash
# 1. Aplicar o manifesto
kubectl apply -f devops/k8s/03-rustdesk-server.yaml

# 2. Verificar se o pod subiu (deve aparecer 2/2 Running)
kubectl -n app-rustdesk get pods

# 3. Obter a Chave Pública do seu Servidor
kubectl -n app-rustdesk exec -it deploy/rustdesk-server -c hbbs -- cat /root/id_ed25519.pub
```

> Guarde a chave impressa pelo comando acima. Ela é o token de autenticação dos seus clientes.

---

## 3. Como Configurar o Cliente RustDesk

No seu computador ou celular onde instalou o cliente RustDesk:
1. Abra o RustDesk e vá em **Configurações (Settings) > Rede (Network)**.
2. Desmarque a opção de servidor público e preencha:
   * **Servidor ID (ID Server)**: `137.131.208.19` *(ou seu subdomínio apontado para ele)*
   * **Servidor Relay (Relay Server)**: `137.131.208.19`
   * **Servidor API**: *(deixe em branco)*
   * **Key**: Cole o conteúdo de `id_ed25519.pub` obtido no passo 2.
3. Clique em **Aplicar**. O status no rodapé do RustDesk mudará para verde: **"Pronto" (Ready)**.

### Macete para Windows (Instalação 1-Clique):
Você pode renomear o executável baixado do RustDesk para que ele já abra pré-configurado:
```text
rustdesk-host=137.131.208.19,key=SUA_CHAVE_AQUI.exe
```

---

## 4. (Opcional) Acompanhar pelo Painel do Dinovatech

Se quiser ver o pod listado com status verde, logs ao vivo e botão de restart na Central de Instâncias SaaS do Dinovatech (`saas_tenants.php`):
Basta rodar um insert simples no banco do Dinovatech:
```sql
INSERT INTO saas_tenants (
    id_cliente, id_recorrencia, nome_cliente, slug_instancia, 
    namespace_k8s, subdominio, db_name, db_user, 
    versao_imagem, status_pod, limite_memoria, limite_cpu
) VALUES (
    1, NULL, 'RustDesk Server Privado', 'rustdesk', 
    'app-rustdesk', 'remote.dinovatech.com.br', 'sqlite', 'root', 
    'rustdesk/rustdesk-server:latest', 'RUNNING', '384Mi', '500m'
);
```
Isso fará com que ele apareça na sua tabela do `saas_tenants.php` com monitoramento e terminal de logs ativo.
