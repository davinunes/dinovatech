# Solicitação de Suporte - Erro 403 (Forbidden) em Homologação Oficial

**Assunto:** Erro 403 ao acessar ambiente de Apresentação/Homologação Oficial - Validação XML Concluída em Homologação Fictícia

Prezada equipe de Suporte ISSNET,

Informamos que **concluímos com sucesso** a validação do nosso protocolo de comunicação XML/SOAP utilizando o ambiente de **Homologação Fictícia** (`homologaabrasf`).

Conseguimos realizar a comunicação completa (envio de envelope, autenticação via Certificado A1, validação de Schema XSD e Assinatura Digital) sem erros de protocolo, obtendo respostas de negócio válidas (ex: retorno das notas no método `ConsultarNfseServicoPrestado` e processamento do método `GerarNfse`).

**Protocolo Validado:**
- **Versão do Cabeçalho:** 2.04
- **Assinatura:** Padrão ABRASF (URI="")
- **Segurança:** TLS 1.2
- **Formatação:** Envelope SOAP sem CDATA (Entidades HTML).

### O Problema Atual

Ao tentarmos apontar nossa aplicação, que já está funcional, para o ambiente de **Homologação Oficial** (Apresentação), recebemos um bloqueio imediato:

- **URL Alvo:** `https://www.issnetonline.com.br/apresentacao/df/webservicenfse204/nfse.asmx`
- **Erro:** `HTTP 403 Forbidden` (bloqueio por WAF/Cloudflare)

### Solicitação

Gostaríamos de saber quais são os requisitos para conexão com este ambiente de Apresentação:

1.  Existe necessidade de liberação prévia de IP (Whitelist)? Nosso IP de saída é: `[INSERIR SEU IP DO SERVIDOR]`
2.  É necessário algum Header HTTP específico ou User-Agent customizado?
3.  O endpoint de Apresentação está ativo e operante para recepção via WebService?

Como já validamos que nosso XML está 100% aderente ao padrão do sistema no ambiente fictício, entendemos que este erro 403 é puramente de infraestrutura/rede.

Aguardamos orientações para prosseguir com os testes de emissão com dados reais de nossa empresa.

Atenciosamente,

**Davi Nunes**
**Digital Inovation Tecnologia**
CNPJ: 61.733.714/0001-01
Inscrição Municipal: 0841147200111
