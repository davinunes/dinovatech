# DevOps & Containers - Dinovatech

Este diretório contém a especificação dos contêineres e o pipeline de empacotamento da aplicação Dinovatech.

---

## 1. Arquitetura Atual e Resumo de Dependências

* **Servidor Web & Runtime**: Apache 2.4 + PHP 8.3 (com extensões `mysqli`, `pdo_mysql`, `soap`, `xml`, `mbstring`, `gd`, `zip`, `bcmath`, `opcache`, `curl`).
* **SSH & Deploy no Host (Legado)**: Pacotes `openssh-client`, `python3`, `python3-pip` e `python3-paramiko` instalados na imagem para permitir que a tela de atualização ([dinovatech/py/ssh.py](file:///e:/DEV/dinovatech/dinovatech/py/ssh.py)) execute comandos `git pull` na VPS host.
  > **Nota de Transição para Kubernetes**: No cluster K3s, os pods são imutáveis e stateless; atualizações de versão são feitas via RollingUpdate Zero-Downtime direto pela API do K3s, sem dependência de SSH.
* **Geração de PDFs**: 
  * **NÃO** ocorre dentro do container do PHP (evitando instalar ferramentas pesadas de compilação gráfica ou WebKit/Chromium no runtime do PHP).
  * O PHP consome via HTTP `cURL` o microserviço sidecar **WeasyPrint REST** (`ghcr.io/schweizerischebundesbahnen/weasyprint-service:latest`), encapsulado por [PdfHelper.php](file:///e:/DEV/dinovatech/dinovatech/helpers/PdfHelper.php).

---

## 2. Como Fazer Build e Publicar no Docker Hub / Containerd

Execute a partir deste diretório `devops/`:

```bash
# 1. Login no Docker Hub
docker login

# 2. Build da Imagem Base x86_64 (para servidores/VPS convencionais)
docker buildx build -t ilunne/php8.3-x86-base:v1.0.0 -f Dockerfile .

# 3. Build da Imagem Base ARM64 (para K3s OCI Ampere A1)
docker buildx build --platform linux/arm64 -t ilunne/php8.3-arm-base:latest -f Dockerfile .

# 4. Build da Imagem Release com o código para o K3s
docker build -t ilunne/php8.3:v1.0.0 -f Dockerfile.release .

# 5. Enviar para o repositório remoto (se aplicável)
docker push ilunne/php8.3-x86-base:v1.0.0
docker push ilunne/php8.3-arm-base:latest
```

---

## 3. Atualizando o Container na VPS (Oracle Cloud / Portainer)

1. No **Portainer** ou terminal da VPS:
   * Altere a imagem do serviço `homepage-php` de `ilunne/php7.4-mysqli` para `seu_usuario_dockerhub/dinovatech:php8.2`.
   * Certifique-se de que o volume com a pasta mapeada do projeto `/caminho/na/vps/dinovatech` esteja apontando para `/var/www/html`.
   * Clique em **Recreate / Pull latest image**.
2. O sidecar `weasyprint` continua rodando na mesma rede Docker `php_dinovatech_financeiro_default`.

---

## 4. Variáveis de Ambiente Necessárias

| Variável | Descrição | Exemplo |
| :--- | :--- | :--- |
| `TZ` | Fuso horário da aplicação | `America/Sao_Paulo` |
| `DB_HOSTNAME` | Host do banco MariaDB | `10.0.0.x` ou IP remoto OCI |
| `DB_DATABASE` | Nome da base de dados | `dinovatech` |
| `DB_USERNAME` | Usuário do banco de dados | `app_user` |
| `DB_PASSWORD` | Senha do banco de dados | `********` |
| `APP_MASTER_KEY` | Chave de criptografia AES-256 | Chave de 32/64 chars hex |
| `WEASYPRINT_URL` | Endpoint do microserviço PDF | `http://weasyprint:9080/convert/html` |
