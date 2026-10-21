# DevOps & Containers - Dinovatech

Este diretório contém a especificação dos contêineres e o pipeline de empacotamento da aplicação Dinovatech.

---

## 1. Arquitetura Atual e Resumo de Dependências

* **Servidor Web & Runtime**: Apache 2.4 + PHP 8.2 (com extensões `mysqli`, `soap`, `mbstring`, `gd`, `zip`, `bcmath`, `curl`).
* **SSH & Deploy no Host**: Pacotes `openssh-client`, `python3` e `python3-paramiko` instalados na imagem para permitir que a tela de atualização ([dinovatech/py/ssh.py](file:///e:/DEV/dinovatech/dinovatech/py/ssh.py)) execute comandos `git pull` na VPS host.
  > **Nota de Transição para Kubernetes**: Na migração para Kubernetes, o container deixará de executar comandos SSH na máquina host; a imagem será imutável e conterá o código ou usará pipelines CI/CD do cluster.
* **Geração de PDFs**: 
  * **NÃO** ocorre dentro do container do PHP (evitando instalar ferramentas pesadas de compilação gráfica ou WebKit/Chromium no runtime do PHP).
  * O PHP consome via HTTP `cURL` o microserviço sidecar **WeasyPrint REST** (`ghcr.io/schweizerischebundesbahnen/weasyprint-service:latest`), encapsulado por [PdfHelper.php](file:///e:/DEV/dinovatech/dinovatech/helpers/PdfHelper.php).

---

## 2. Como Fazer Build e Publicar no Docker Hub

Execute a partir deste diretório `devops/`:

```bash
# 1. Login no Docker Hub
docker login

# 2. Build da Imagem com tag para PHP 8.2
docker build -t seu_usuario_dockerhub/dinovatech:php8.2 -f Dockerfile .

# 3. Enviar para o repositório remoto
docker push seu_usuario_dockerhub/dinovatech:php8.2
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
