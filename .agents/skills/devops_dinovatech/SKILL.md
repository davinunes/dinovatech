---
name: devops_dinovatech
description: Padrões de containerização, build Dockerfile, estratégias de deploy (SSH atual e transição para Kubernetes) e microserviços sidecars (WeasyPrint) do Dinovatech.
---

# DevOps, Containers & Runtime do Dinovatech

Esta skill documenta a arquitetura de contêineres, as diretrizes de empacotamento Docker, a geração de PDFs via sidecars e o roadmap de deploy do projeto Dinovatech.

---

## 1. Runtime da Aplicação (Container Principal)

* **Base**: Imagem baseada em `php:8.2-apache` (substituindo a legada `ilunne/php7.4-mysqli`).
* **Extensões PHP Obrigatórias**:
  * `mysqli` e `pdo_mysql` (Acesso a dados MariaDB).
  * `soap` e `xml` (Integração com webservices da NFS-e / SEFAZ).
  * `curl` e `openssl` (Comunicação com WeasyPrint, Banco Inter, InfinitePay e Google APIs).
  * `mbstring`, `gd`, `zip`, `bcmath`, `opcache`.
* **Configuração de Compatibilidade com PHP 8.x**:
  * Em `database.php`: Sempre manter `mysqli_report(MYSQLI_REPORT_OFF);` ativo na conexão para evitar exceções não capturadas em queries com retorno falso.
  * Em `app.php`: Em produção, manter `display_errors = Off` para que eventuais avisos de depreciação do PHP não quebrem endpoints com retorno JSON.

---

## 2. Mecanismo de Deploy Atual (SSH / Git Pull)

* **Funcionamento**:
  * O painel web possui uma aba de atualização que dispara `dinovatech/py/ssh.py`.
  * O script conecta via SSH no IP do host Docker (`172.17.0.1:22` ou `localhost`) autenticando com chave PEM privada e executa `git pull` na pasta mapeada.
* **Dependências do Container**:
  * O container do PHP inclui `openssh-client`, `python3` e a biblioteca `python3-paramiko`.
* **Futura Transição para Kubernetes**:
  * No Kubernetes, o conceito de SSH contra o host é descontinuado. O código será empacotado diretamente na imagem Docker (imutabilidade) ou injetado via pipeline CI/CD (GitHub Actions / GitLab CI) com `kubectl set image` ou Helm/ArgoCD.

---

## 3. Geração de PDFs (Arquitetura Sidecar)

* **Importante**: O Dinovatech **NÃO** gera PDFs utilizando bibliotecas pesadas dentro do container do PHP (sem wkhtmltopdf, sem headless Chrome e sem Dompdf no runtime principal).
* **Estrutura**:
  * Microserviço sidecar: `ghcr.io/schweizerischebundesbahnen/weasyprint-service:latest`.
  * Porta interna: `9080` (endpoint HTTP REST `/convert/html`).
  * Rede: Conectado na mesma rede bridge (`php_dinovatech_financeiro_default`).
  * O PHP consome esse serviço através da classe auxiliar `dinovatech/helpers/PdfHelper.php` via `cURL POST`.
  * Vantagem: Mantém o container do PHP leve, seguro e rápido para escalar.

---

## 4. Localização dos Artefatos de DevOps

* `devops/Dockerfile`: Especificação de build da imagem PHP 8.2+.
* `devops/docker-compose.yml`: Compose para orquestração local/VPS com o sidecar WeasyPrint.
* `devops/README.md`: Comandos para build, tagging e push no Docker Hub.
* `docker-compose.weasyprint.yml`: Compose isolado do microserviço WeasyPrint em produção.
