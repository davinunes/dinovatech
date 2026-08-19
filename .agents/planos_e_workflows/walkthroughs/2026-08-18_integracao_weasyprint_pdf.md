# Walkthrough: Configuração e Integração do WeasyPrint para Geração de PDFs

## Resumo da Entrega
Implementação de suporte a geração server-side de PDFs de alta fidelidade com **WeasyPrint** para receitas, termos e documentos, fornecendo a definição Compose para o Portainer e a classe auxiliar de integração PHP.

---

## 1. Arquivos Criados e Modificados

### [NOVO] [`docker-compose.weasyprint.yml`](file:///e:/DEV/dinovatech/docker-compose.weasyprint.yml)
Definição de stack Docker para o Portainer rodando o serviço REST WeasyPrint com limites de recursos configurados para o ambiente Free Tier:
```yaml
version: '3.8'

services:
  weasyprint:
    image: ghcr.io/schweizerischebundesbahnen/weasyprint-service:latest
    container_name: weasyprint
    restart: unless-stopped
    init: true
    ports:
      - "9080:9080"
    deploy:
      resources:
        limits:
          memory: 350M
        reservations:
          memory: 80M
    environment:
      - TZ=America/Sao_Paulo
```

### [NOVO] [`dinovatech/helpers/PdfHelper.php`](file:///e:/DEV/dinovatech/dinovatech/helpers/PdfHelper.php)
Classe utilitária que:
- Converte imagens locais para **Data URI Base64** automaticamente.
- Envia o HTML via cURL para o endpoint `/convert/html` do WeasyPrint.
- Disponibiliza os métodos `generatePdf($html)` e `streamPdf($html, $filename, $inline)`.

### [MODIFICADO] [`dinovatech/modules/Vet/receita_print.php`](file:///e:/DEV/dinovatech/dinovatech/modules/Vet/receita_print.php)
- Adicionado botão **"Baixar PDF"** no topo da receita.
- Ao acessar com `?pdf=1`, o documento é processado e entregue como arquivo PDF binário.

### [MODIFICADO] [`dinovatech/modules/Vet/documento_print.php`](file:///e:/DEV/dinovatech/dinovatech/modules/Vet/documento_print.php)
- Adicionado grupo de ações ("Baixar PDF" e "Imprimir").
- Ao acessar com `?pdf=1`, gera o documento compilado diretamente pelo WeasyPrint.

---

## 2. Como Subir o Container no Portainer

1. Acesse o **Portainer** do seu servidor.
2. Vá em **Stacks** -> **Add Stack**.
3. Nomeie como `weasyprint`.
4. Cole o conteúdo do arquivo [`docker-compose.weasyprint.yml`](file:///e:/DEV/dinovatech/docker-compose.weasyprint.yml) no editor Web.
5. Clique em **Deploy the stack**.
6. Se o container do PHP estiver na mesma rede Docker do WeasyPrint, ele se comunicará diretamente via `http://weasyprint:9080/convert/html`. Caso estejam em redes separadas, o PHP acessará via `http://localhost:9080/convert/html` ou pela variável `WEASYPRINT_URL` no `.env`.
