# Manual dos Contribuintes-Sistema Nacional NFS-e

# Guia para utilização das API´s do ADN (Ambiente de Dados Nacional)

![Figure](asset:sha256:bb61354480077844128b14a300fcc1339e6a1b9ff99d1640471ea7b0c1bc9929)

<!-- page 2 -->

## HISTÓRICO DE VERSÕES

| Versão | Data | Alterações da Versão |
| --- | --- | --- |
| 1.0 | 12/02/2026 | Versão inicial |

## Resumo do Documento

**Descrição:** Este documento contempla informações sobre a utilização das API´s relacionadas com o compartilhamento no Ambiente de Dados Nacional

## (ADN).

## Destinação Atores envolvidos na emissão do documento fiscal: emitente, intermediário e tomador do serviço.

<!-- page 3 -->

**1. Utilização das API´s do ADN (Ambiente de Dados Nacional)**
**1.1. Descrição** As APIs descritas neste manual permitem que o contribuinte consulte documentos fiscais de serviços em que figure como emitente, tomador ou intermediário. Além disso, está disponível uma API para a consulta de eventos vinculados a um documento específico, mediante o uso da chave de acesso. As consultas da API de Distribuição dos Contribuintes agora podem ser realizadas utilizando um certificado cujo CNPJ tenha o mesmo CNPJ Raiz do contribuinte para o qual se deseja consultar. No caso da consulta por NSU, um novo parâmetro foi adicionado, para que possa ser possível informar um CNPJ de consulta diferente do CNPJ do certificado utilizado para estabelecer a conexão (é feita a validação do CNPJ Raiz nesse caso, entre o parâmetro informado e o CNPJ do certificado utilizado).
**1.1.1. Métodos *a) GET /DFe/{NSU}*** Retorna os documentos fiscais de serviço correspondente ao NSU informado. O solicitante informa um NSU e o sistema nacional retorna o DF-e associado.
***b) GET – /NFSe/{ChaveAcesso}/Eventos*** Retorna os Documentos Fiscais de Serviço do tipo Evento vinculados à chave de acesso informada. Consulta eventos por chave de acesso. O solicitante informa a chave de acesso de uma NFS-e e a API retorna todos os eventos vinculados à chave de acesso de NFS-e informada na consulta.
**1.2. Ambiente de produção restrita** Foi disponibilizada uma Swagger em ambiente de produção restrita destinada a testes por parte dos contribuintes. **Link ambiente de produção restrita:** <u>[https://adn.producaorestrita.nfse.gov.br/contribuintes/docs/index.html](https://adn.producaorestrita.nfse.gov.br/contribuintes/docs/index.html)</u>

Link: [https://adn.producaorestrita.nfse.gov.br/contribuintes/docs/index.html](https://adn.producaorestrita.nfse.gov.br/contribuintes/docs/index.html)