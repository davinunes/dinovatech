---
name: infra_dinovatech
description: Especificações e particularidades da infraestrutura de produção do Dinovatech (Oracle Cloud Free Tier, Docker, MariaDB remoto, Storage S3/Oracle Object Storage e restrições de ambiente).
---

# Infraestrutura & Ambiente do Dinovatech

Este documento registra as especificações da infraestrutura remota do Dinovatech e as decisões arquiteturais associadas.

## 1. Servidor de Aplicação (Compute / VPS)
- **Provedor**: Oracle Cloud Infrastructure (OCI) - Free Tier.
- **Configuração de Hardware**: 1 vCPU, 1 GB RAM, 100 GB SSD.
- **Sistema Operacional**: Ubuntu Linux.
- **Gerenciamento de Containers**:
  - **Docker Engine** com **Portainer** para gestão gráfica.
  - **Servidor Web**: **Caddy** (Reverse Proxy & HTTPS automático).
  - **Container PHP**: `ilunne/php7.4-mysqli`.

## 2. Banco de Dados (MariaDB Gerenciado)
- **Instância**: Instância separada MariaDB (Free Tier na Oracle Cloud).
- **Atenção ao Fuso Horário**:
  - A instância do MariaDB é remota e **não herda o relógio do Ubuntu da VPS**.
  - O fuso horário do MariaDB opera em **UTC (+00:00)** por padrão.
  - **Regra do Projeto**: A aplicação PHP **sempre** define explicitamente `SET time_zone = '-03:00'` na conexão (`DBConnect()` em `database.php`) para garantir que funções de data como `NOW()`, `CURDATE()` e `CURRENT_TIMESTAMP` operem no Horário Oficial de Brasília (GMT-3).

## 3. Armazenamento de Arquivos e Uploads (Oracle Object Storage / S3)
- **Mecanismo**: Oracle Object Storage via **Pre-Authenticated Request (PAR) / URL Pré-configurada** com validade estendida.
- **Upload**: Realizado via HTTP `PUT` com cURL direto para o endpoint preauth.
- **Limitação Conhecida**:
  - Por utilizar URL pré-autenticada genérica sem credenciais completas de IAM SDK, **não há API direta para exclusão física de objetos obsoletos** (ex: imagens antigas substituídas).
  - Políticas de retenção ou scripts de expiração devem ser considerados caso o bucket precise de limpeza periódica.

## 4. Serviço de Geração de PDFs (WeasyPrint REST)
- **Mecanismo**: Microserviço sidecar Docker isolado para compilação server-side de documentos e receitas em PDF.
- **Imagem & Container**: `weasyprint` (`ghcr.io/schweizerischebundesbahnen/weasyprint-service:latest`).
- **Rede Docker**: Conectado diretamente à rede `php_dinovatech_financeiro_default` compartilhada com o container `homepage-php`.
- **Comunicação**: O backend PHP interage via cURL HTTP através do endpoint `http://weasyprint:9080/convert/html` (encapsulado pela classe `dinovatech/helpers/PdfHelper.php`).
- **Controle de Recursos (Proteção da VPS 1GB RAM)**:
  - Limite rígido de memória definido em `350M` (reserva `80M`) no Compose.
- **Tratamento de Imagens & CSS**:
  - `PdfHelper.php` converte automaticamente caminhos locais de imagens para **Data URI Base64** antes do envio ao container, tornando o documento autocontido.
  - Atributos legados de dimensões (`width`/`height` do editor TinyMCE) são normalizados em CSS inline (`!important`) para garantir proporções e fidelidade visual na impressão A4.

## 5. Regras de Desenvolvimento e Compilação
- Desenvolvemos e alteramos apenas o código localmente.
- Não executar compiladores pesados ou dependências no host a menos que solicitado.
- Scripts PHP e SQL devem manter compatibilidade com **PHP 7.4** e **MariaDB 10.x**.
