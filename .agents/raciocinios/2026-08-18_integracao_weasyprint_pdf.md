# Raciocínio: Avaliação e Integração do WeasyPrint para Geração de PDFs

- **Data**: 18/08/2026
- **Contexto**: Substituição da impressão no cliente (`window.print()`) por geração server-side de documentos e receitas em PDF através do WeasyPrint.

## 1. Avaliação Técnica & Infraestrutura
- O WeasyPrint é um motor baseado em Python/Cairo/Pango com suporte completo a CSS Paged Media (`@page`, quebra de páginas, cabeçalhos e rodapés repetidos por página).
- A infraestrutura de produção é Oracle Cloud Free Tier (1 vCPU, 1 GB RAM, Ubuntu, Docker/Portainer com Caddy e PHP 7.4).
- O container PHP atual (`ilunne/php7.4-mysqli`) não possui Python/GTK instalados.
- **Decisão Arquitetural**: Executar o WeasyPrint como um microserviço REST isolado via Docker (`ghcr.io/schweizerischebundesbahnen/weasyprint-service`), configurado com limite de memória (`350M`) no Compose para garantir a estabilidade do servidor de 1 GB de RAM.

## 2. Tratamento de Imagens e Recursos Locais
- Quando o PHP envia o HTML bruto via HTTP para o container WeasyPrint, caminhos relativos como `../../uploads/logo.png` não seriam acessíveis sem permissões de volume compartilhado ou roteamento de rede.
- **Solução Implementada**: No `PdfHelper`, um parser regex localiza todas as tags `<img>` e converte automaticamente caminhos locais para Data URIs Base64 (`data:image/...;base64,...`), tornando o documento 100% autocontido antes do envio ao WeasyPrint.

## 3. Integração no Código PHP
- Criação de `dinovatech/helpers/PdfHelper.php` com métodos para geração, streaming e captura de erros.
- Adaptação das telas de impressão (ex: `receita_print.php`, `documento_print.php`) suportando o parâmetro `?pdf=1` e botão "Baixar PDF".
