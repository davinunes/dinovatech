# Plano de Implementação: Migração PHP 8.2 e Blindagem de Segurança (CVEs)

- **Data**: 2026-10-21
- **Status**: Planejado / Em Andamento
- **Responsável**: Antigravity & Davi Nunes

---

## 1. Contexto & Motivação

Recentemente foram divulgadas vulnerabilidades críticas de Execução Remota de Código (RCE) no motor core do PHP (notadamente CVE-2026-109618 / CVE-2026-109619 - "PHP2Shell", além da já conhecida CVE-2024-4577 de injeção de argumentos no Windows). 
O ambiente atual de produção do Dinovatech opera sobre um container legado com **PHP 7.4** (imagem `ilunne/php7.4-mysqli`), versão que atingiu EOL (End of Life) e não recebe correções oficiais do PHP.

Para blindar o ambiente e preparar a futura transição para **Kubernetes**, este plano estabelece a modernização do runtime para **PHP 8.2+**, a criação de infraestrutura de containers no diretório `devops/` e a validação de compatibilidade de código.

---

## 2. Diagnóstico dos Mecanismos do Sistema

### 2.1. Geração de PDFs (Diagnóstico Confirmado)
* **Arquitetura**: O Dinovatech **NÃO** compila PDFs de forma pesada dentro do container do PHP (sem libs como wkhtmltopdf ou headless Chrome no runtime do PHP).
* **Mecanismos em Uso**:
  1. **WeasyPrint REST Sidecar**: Utiliza o container `ghcr.io/schweizerischebundesbahnen/weasyprint-service:latest` rodando na porta 9080 na mesma rede Docker. O PHP apenas envia HTML via `cURL` através do [PdfHelper.php](file:///e:/DEV/dinovatech/dinovatech/helpers/PdfHelper.php) e recebe os bytes prontos.
  2. **Extratos Bancários Inter**: Consumo direto da API externa do Banco Inter via cURL (retorna Base64).
  3. **DANFSE / Impressão de NFS-e**: Renderização de página HTML responsiva com acionamento nativo de impressão/PDF do navegador ou link para o portal nacional.
* **Impacto no Container PHP**: O container do PHP permanece enxuto e não precisa de dependências gráficas ou binários nativos pesados.

### 2.2. Mecanismo Atual de Deploy SSH
* A tela de deploy do Dinovatech dispara [dinovatech/py/ssh.py](file:///e:/DEV/dinovatech/dinovatech/py/ssh.py), que utiliza `python3` e a biblioteca `paramiko` para abrir conexão SSH com o host (`172.17.0.1` ou `localhost`) e rodar `git pull`.
* **Solução no Dockerfile atual**: Incluído `openssh-client`, `python3` e `python3-paramiko` para manter 100% de retrocompatibilidade com o fluxo atual.
* **Transição Kubernetes**: Na migração para K8s, esse padrão SSH no host será descontinuado em favor de imagens imutáveis construídas via pipeline CI/CD.

---

## 3. Plano de Adaptação de Código para PHP 8.x

### 3.1. [database.php](file:///e:/DEV/dinovatech/database.php) (Ajuste Crítico de MySQLi)
* **Mudança no PHP 8.1+**: O MySQLi ativa `MYSQLI_REPORT_STRICT` por padrão, disparando exceções fatais em falhas de query.
* **Ação**: Incluir `mysqli_report(MYSQLI_REPORT_OFF);` no início da função `DBConnect()`.

### 3.2. [dinovatech/app.php](file:///e:/DEV/dinovatech/dinovatech/app.php) (Exibição de Erros em Produção)
* **Mudança no PHP 8.1+**: Deprecations de passagem de `null` para funções internas (`htmlspecialchars`, `trim`, etc.) podem ser emitidas. Se `display_errors` estiver ativo, o texto do aviso é injetado na saída AJAX e quebra o `JSON.parse` no front-end.
* **Ação**: Assegurar que avisos de depreciação fiquem apenas no log (`display_errors = Off` no container e controle via `.env`).

---

## 4. Etapas de Execução

1. [x] **Criar diretório `devops/` com `Dockerfile`, `docker-compose.yml` e `README.md`**:
   - Container baseado em `php:8.2-apache`.
   - Extensões: `mysqli`, `pdo_mysql`, `mbstring`, `soap`, `gd`, `zip`, `bcmath`, `curl`, `xml`, `opcache`.
   - Suporte SSH/Python/Paramiko para retrocompatibilidade de deploy.
2. [x] **Criar Skill do Projeto**: Registrar as diretrizes operacionais e de container em `.agents/skills/devops_dinovatech/SKILL.md`.
3. [ ] **Ajustar linha de segurança no `database.php`**: Inserir `mysqli_report(MYSQLI_REPORT_OFF);`.
4. [ ] **Build e Publicação da Imagem no Docker Hub**:
   - Build da imagem no ambiente do desenvolvedor: `docker build -t <usuario>/dinovatech:php8.2 devops/`.
   - Push para o Docker Hub: `docker push <usuario>/dinovatech:php8.2`.
5. [ ] **Deploy na VPS (Portainer/Docker Compose)**:
   - Trocar imagem do serviço `homepage-php` para a nova tag `php8.2`.
   - Recriar o container mantendo o volume do repositório mapeado.
6. [ ] **Validação dos Módulos Principais**:
   - Teste de login e navegação.
   - Teste de emissão/consulta de NFS-e (Módulo Fiscal).
   - Teste de emissão de PDF de receita e documento veterinário via WeasyPrint.
   - Teste do botão de atualização Git (deploy SSH).
