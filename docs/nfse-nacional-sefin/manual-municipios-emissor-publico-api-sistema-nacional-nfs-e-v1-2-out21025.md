# Manual dos Municípios Conveniados ao Sistema Nacional NFS-e

# Gerenciamento Municipal no Sistema Nacional NFS-e – API

***

## HISTÓRICO DE VERSÕES

| Versão | Data Alterações da Versão |
| --- | --- |
| 1.0 | 17/03/2025 |

## Resumo do Documento

| Descrição: | Este documento contempla informações sobre a utilização das API´s |
| --- | --- |
|  | relacionadas com parâmetros municipais, DPS, API NFS-e e eventos. |
| Destinação | Municípios conveniados |

***

**1. Gerenciamento Municipal no Sistema Nacional NFS-e – API**

### Visão Geral

O gerenciamento municipal via API no Sistema Nacional NFS-e abrange um conjunto de funcionalidades básicas para que o município desenvolva clientes para comunicação com o as API disponíveis e possa integrar seus sistemas próprios de forma a automatizar o fluxo de informações com o sistema nacional. Abaixo seguem as especificações das APIs atualmente disponíveis para integração com o Sistema Nacional NFS-e.

**1.1. API Parâmetros Municipais**
**1.1.1. Descrição** Através deste serviço um usuário obtém as parametrizações de um município específico. De posse das informações dos parâmetros de um município o emitente pode preencher as informações necessárias da DPS para emissão e envio à Sefin Nacional NFS-e, que a validará para gerar a NFS-
e.
**1.1.2. Métodos**
***a) GET – /parametros\_municipais/{codigoMunicipio}/convenio***
Consulta os parâmetros do convênio de um município.

***b) GET – /parametros\_municipais/{ codigoMunicipio }/aliquotas***
Consulta as alíquotas, regimes especiais de tributação e deduções/reduções por subitens da lista de serviço.

***c) GET – /parametros\_municipais/{codigoMunicipio}/regimes\_especiais***
Consulta as retenções a que um contribuinte tem o dever de recolher para um município.

***d) GET – /parametros\_municipais/{codigoMunicipio}/retencoes***
Consulta os benefícios municipais a que um contribuinte tem direito em um município.

***e) GET – /parametros\_municipais/{codigoMunicipio}/beneficiomunicipal/ {nBM}***
Consulta os benefícios municipais a que um contribuinte tem direito em um município.

***f) POST – /parametros\_municipais/{codigoMunicipio}/beneficiomunicipal/*** ***{idManut}***
Realiza manutenção em um benefício municipal já existente em um município.

***g) POST – /parametros\_municipais/{codigoMunicipio}/retencoes/ {idManut}***
Realiza manutenção em uma retenção já existente em um município.

***h) POST – /parametros\_municipais/{codigoMunicipio}/regimes\_especiais/*** ***{idManut}***
Realiza manutenção em um regime especial de tributação já existente em um município.

**1.2. API NFS-e**
**1.2.1. Descrição**

### Possui um serviço:

- Consulta de NFS-e pela chave de acesso;

  **1.2.2. Métodos**
  ***a) GET – /nfse/{chaveAcesso}***

***

Consulta NFS-e pela chave de acesso. Através de um método GET a API obtém uma NFS-e a partir de uma chave de acesso como parâmetro na consulta realizada pelo solicitante.

**1.3. API DPS**
**1.3.1. Descrição** Serviço cujo objetivo principal é recuperar a chave de acesso da NFS-e a partir de uma consulta pelo identificador da DPS (Código IBGE do Município Emissor (7), Tipo de Inscrição (1), Inscrição Federal (14 - CPF completar com 000 à esquerda), Série DPS (5), Núm. DPS (15). No entanto, por questões de sigilo fiscal, a chave de acesso somente será informada somente se a identificação do usuário do certificado digital da conexão solicitante for um ator (Prestador, Tomador ou Intermediário) que consta na NFS-e gerada a partir da DPS consultada.
**1.3.2. Métodos** O processamento se dá em dois métodos distintos com objetivos separados, Get e Head. Realiza uma consulta ao banco de dados da Sefin Nacional NFS-e com os parâmetros da DPS informados, retornando a chave de acesso da NFS-e correspondente ao DPS consultado ou somente a informação de que a NFS-e referente à DPS consultada foi gerada, sem informar a chave de acesso.
***a) GET – /dps/{id}***
Recupera a chave de acesso da NFS-e a partir do identificador do DPS. O solicitante informa a identificação da DPS e a API retorna a chave de acesso da NFS-e correspondente. Somente podem ser consultados identificadores de DPS emitidos através Sefin Nacional NFS-e. e desde que a identificação do certificado digital da conexão solicitante corresponda a um dos atores informados na DPS da NFS-e (Prestador, Tomador ou Intermediário). Caso contrário a solicitação será negada.

***b) HEAD – /dps/{id}***
O método Head é uma consulta para informar se uma NFS-e foi gerada ou não a partir do identificador da DPS. Este método atende a qualquer usuário desde que realize a consulta com um certificado digital válido.

**1.4. API Eventos**
**1.4.1. Descrição** Recepciona os Pedidos de Registros de Eventos de NFS-e gerados e enviados para a API, realiza validações de negócio sobre estes pedidos e gera o Evento de NFS-e. O evento gerado é vinculado à NFS-e para a qual o pedido de registro de evento foi enviado.
**1.4.2. Métodos** Para o processamento realizado sobre o Pedido de Registro de Evento devem ser compreendidos:

- Esquemas relativos aos eventos reconhecidos pelo Sistema Nacional NFS-e;
- Leiaute do Pedido de Registro de Evento de NFS-e;
- Leiaute do Evento de NFS-e;
- Regras de negócio aplicadas sobre o pedido de registro de evento;
- Parametrizações que o município emissor da NFS-e realiza previamente para utilização nas validações sobre o Pedido de Registro de Evento de NFS-e;
- Estes itens e todas as regras e observações de negócio para todos os eventos de NFS-e do Sistema Nacional NFS-e estão anotadas e disponíveis nas planilhas do arquivo <u></u>AnexoII- <u></u>LeiautesRN\_Eventos-SNNFSe deste manual.

  O processo de transação completa de utilização dos eventos de NFS-e tem a seguinte sequência:

1. O solicitante envia o pedido de registro de evento;
2. O sistema valida, registra ou rejeita o pedido de registro de evento;
3. O sistema, caso aceite o pedido de registro de evento, gera o evento de NFS-e que ficará vinculado ao documento da nota, cuja chave de acesso da NFS-e estiver informada no pedido;
4. O sistema envia comunicação de aceite ou rejeição do pedido de registro de evento ao solicitante;

***

***a) POST – /nfse/{chaveAcesso}/eventos***
É um modelo genérico que permite o registro de eventos originados a partir de: Emitentes da NFS- e; Não Emitentes da NFS-e; Município Emissor; Município de Incidência e do Módulo de Apuração Nacional;

Um evento é o registro de um fato relacionado a uma NFS-e. Esse evento pode ou não modificar a situação do documento (por exemplo: cancelamento de NFS-e) ou simplesmente dar ciência sobre a confirmação da prestação de serviço pelo tomador (por exemplo: Manifestação de NFS-e - Confirmação).

O serviço para registro de eventos será disponibilizado pelas Sefins geradoras de NFS-e (Sefin Nacional e demais Sefins municipais que desejarem seguir o padrão nacional para NFS-e) através de um processamento síncrono na API Eventos. Assim como as NFS-e, os eventos são um tipo de documento fiscal eletrônico e serão compartilhados pelo ADN NFS-e conforme regras de visibilidade de documentos. As mensagens de comunicação com a API utilizarão o padrão JSON, já definido para o projeto NFS-e, enquanto o leiaute do DFS-e utiliza o padrão XML contendo a assinatura digital do emissor do evento. Para o registro do evento é necessário a existência da NFS-e na Sefin geradora de NFS-e, em conformidade com as regras de negócio estabelecidas para os eventos de NFS-e.

O modelo de mensagem do evento deverá ter um conjunto mínimo de informações comuns, a saber:

- Identificação do autor da mensagem;
- Identificação do evento;
- Identificação do NFS-e vinculado;
- Informações específicas do evento;
- Assinatura digital da mensagem;

  A API de eventos será única com a funcionalidade de tratar eventos de forma genérica para facilitar a criação de novos eventos sem a necessidade de criação de novos serviços e com poucas alterações na aplicação de Pedido de Registro de Eventos da Sefin geradora de NFS-e.

O leiaute da mensagem do Pedido de Registro de Evento seguirá o modelo, contendo uma parte genérica (comum a todos os tipos de evento) e uma parte específica onde será inserido o XML correspondente a cada tipo de evento.

- As regras de validação referentes às partes genérica e específica dos eventos estão descritas em tabela específica no <u></u>AnexoII-LeiautesRN\_Eventos-SNNFSe.

  ***b) GET – /nfse/{chaveAcesso}/eventos***
  Consulta eventos por chave de acesso. O solicitante informa a chave de acesso de uma NFS-e e a API retorna todos os eventos vinculados à chave de acesso de NFS-e informada na consulta.

***c) GET – /nfse/{chaveAcesso}/eventos/{tipoEvento}***
Consulta eventos por chave de acesso e tipo. O solicitante informa a chave de acesso e o tipo de evento (código identificador do evento). A API retorna o(s) evento(s) do tipo especificado vinculado(s) à chave de acesso de NFS-e informada na consulta.

***d) GET – /nfse/{chaveAcesso}/eventos/{tipoEvento}/{numSeqEvento}***
Consulta eventos por chave de acesso, tipo e sequencial do evento. O solicitante informa a chave de acesso, o tipo de evento (código identificador do evento) e o número sequencial específico do tipo de evento desejado (caso o tipo de evento permita mais de um evento do mesmo tipo para a mesma nota, caso contrário o número sequencial deve ser igual a 1). A API retorna exatamente o evento com o número sequencial solicitado, do tipo especificado, vinculado à chave de acesso de NFS-e informada na consulta.

***

**1.5. Ambiente de produção restrita** Foi disponibilizada uma Swagger em ambiente de produção restrita destinada a testes por parte dos municípios conveniados.

### Link ambiente de produção restrita:

<u></u><https: adn.producaorestrita.nfse.gov.br="" municipios="" docs="" index.html=""></https:>