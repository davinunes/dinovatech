# Manual dos Contribuintes-Sistema Nacional NFS-e

**Guia para utilização das API´s do Emissor Público Nacional – Emissão por Decisão Administrativa ou Judicial**

***

## HISTÓRICO DE VERSÕES

| Versão | Data | Alterações da Versão |
| --- | --- | --- |
| 1.0 | 26/01/2026 | Versão inicial |

## Resumo do Documento

**Descrição:** Este documento contempla informações sobre a utilização das API´s relacionadas com a Emissão por Decisão Administrativa ou Judicial

## Destinação Atores envolvidos na emissão do documento fiscal: emitente, intermediário e tomador do serviço.

***

**1. Emissão por Decisão Administrativa ou Judicial** A emissão da Nota Fiscal de Serviço Eletrônica (NFS-e) por decisão administrativa ou judicial utiliza um fluxo específico criado na plataforma para atender a determinações judiciais ou administrativas que exigem o desvio das regras padrão de validação do documento fiscal. Esta funcionalidade, também conhecida como “*bypass*”, visa atender às determinações estabelecidas por tais decisões, sendo importante ressaltar que o contribuinte deve primeiramente solicitar ao Município que cadastre no sistema a referida decisão, incluindo o número do processo administrativo ou judicial que a determinou, e autorize o contribuinte a utilizar esse fluxo específico de emissão. Somente após isso, esse contribuinte estará apto a emitir o documento em conformidade com a decisão. Este fluxo específico possibilita a emissão dos documentos sem a necessidade de validação completa das regras de negócio aplicáveis ao processo padrão de emissão, conforme estabelecido no ANEXO\_I-SEFIN\_ADN-DPS\_NFSe-SNNFSe na planilha RN\_DPS\_NFS-e coluna “L”. Note que validações mínimas, tais como dígitos verificadores de CPF e CNPJ, ainda são aplicáveis, para garantir a integridade do documento fiscal e das bases de dados. No caso de emissão via web, as orientações constam no “Guia do Emissor Público Nacional Web da NFS-e”. Para emissão via API, o contribuinte deve seguir as instruções aqui presentes. No fluxo regular, o emissor do documento fiscal envia à Sefin nacional um arquivo em formato XML, chamado Declaração de Prestação de Serviço (DPS), contendo os dados básicos da operação. Esses dados são, então, validados e submetidos às regras de negócio do sistema, que agrega ao documento os dados parametrizados pelos Municípios e pelo CGNFS-e, tais como alíquotas, locais de incidência do ISSQN, além dos valores calculados pela plataforma (nativamente ou a partir de suas integrações, como aquela com a Calculadora de Tributos). Caso tudo esteja conforme as regras, a nota é gerada. No fluxo de emissão por decisão administrativa ou judicial, o mecanismo é diferente. Neste caso, o emissor deve emitir a NFS-e completa (DPS + campos que normalmente são gerados no fluxo regular). Dessa forma, o arquivo emitido deve conter **todos** os campos obrigatórios da NFS- e, e não somente os dados básicos da DPS do fluxo regular. A plataforma não gerará os campos calculados, nem determinará o local de incidência ou alíquota do ISSQN. Todas as informações são de responsabilidade do contribuinte, ainda que contrariem as regras de negócio da plataforma. Assim, o sistema nacional dispõe de bastante flexibilidade para comportar qualquer tipo de decisão administrativa ou judicial. Note, porém, que, neste caso, a responsabilidade pelas informações é inteiramente do contribuinte, que deve se ater ao que foi autorizado pela decisão, estando sujeito à fiscalização posterior para constatar se não extrapolou o que foi determinado pela decisão.

## 1.1 API NFS-e com indicativo de decisão judicial

## 1.1.1 Descrição

Esta API possui um serviço que recepciona o arquivo XML que representa a NFS-e de decisão administrativa / judicial.

***

## 1.1.2 Método

***a) POST - /decisao-judicial/nfse*** Emissão síncrona de NFS-e de decisão administrativa / judicial. Por meio de um método
POST a API recepciona uma NFS-e emitida pelo contribuinte e enviada pelo solicitante do serviço. A API realiza validações mínimas de negócio sobre o arquivo recebido, rejeitando (se a validação não passar em ao menos uma das regras de negócio definidas para a NFS-e) ou autorizando/recepcionando a NFS-e. O sistema retorna para o solicitante a mensagem de erro com o motivo da rejeição da validação, se for o caso, ou o arquivo XML da NFS-e autorizada.

Um caso à parte ocorre quando a NFS-e enviada contém uma chave de acesso de uma NFS- e já gerada pelo sistema para ser substituída. A API verifica a existência desta NFS-e e, encontrada, realiza os mesmos procedimentos descritos anteriormente para validação e gera um *Evento de* *Cancelamento de NFS-e por Substituição*. Este evento é vinculado à NFS-e original, cancelando-a. Sendo autorizada a NFS-e substituta, é retornado ao solicitante o arquivo XML desse documento fiscal.

Para o processamento realizado sobre uma NFS-e a ser emitida no mecanismo “bypass”, devem ser compreendidos:

- Esquemas e Leiaute DPS e NFS-e;
- Regras de negócio aplicadas sobre a NFS-e para gerá-la no modo decisão administrativa / judicial; Esses itens estão disponíveis no ANEXO\_I-SEFIN\_ADN-DPS\_NFSe-SNNFSe deste manual e

  disponíveis no seção de documentação técnica do Portal da NFS-e.

São também necessários para o processamento realizado para a autorização da NFS-e por decisão administrativa / judicial as integrações com:

- Parametrizações do convênio do município emissor da NFS-e;
- Inclusão da decisão administrativa / judicial na plataforma pelo Município;
- Parametrizações que o município emissor da NFS-e realiza previamente no Sistema Nacional NFS-e que são utilizadas nas validações;
- Informações do cadastro nacional de contribuintes NFS-e, se for o caso;
- Informações das integrações com os cadastros CNPJ, CPF e Simples Nacional;

## 1.2 Campos a serem preenchidos no arquivo XML da NFS-E

Para emitir uma NFS-e pelo fluxo de decisão administrativa ou judicial, o contribuinte deve utilizar a API específica para este fim.

O que diferencia este fluxo especial são os campos necessários para a emissão do documento e seus respectivos conteúdos no arquivo XML da NFS-e. Como mencionado acima, além de todos os campos que seriam informados em uma DPS do fluxo normal, é necessário também enviar **todos** **os campos de preenchimento obrigatório** na NFS-e. Esses campos seriam gerados pela plataforma no fluxo regular, mas neste fluxo a responsabilidade pelo seu envio e preenchimento é do contribuinte.

***

Para isso, o contribuinte deve utilizar como referência os documentos ANEXO\_I-SEFIN\_ADN- DPS\_NFSe-SNNFSe e anexos de domínio: ANEXO\_A, ANEXO\_B e ANEXO\_C.

A seguir, listamos alguns campos que podem gerar dúvida quanto ao preenchimento:

| Campo | Valor | Descrição |
| --- | --- | --- |
| cStat | 102 | Indica que a NFS-e se refere a uma decisão administrativa ou judicial. Este valor é essencial para que a plataforma identifique o tipo de documento, facilitando o processamento adequado em fluxos específicos e evitando erros na validação do lote de RPS. |
| nNFSe | Gerado pelo sistema de forma sequencial no fluxo normal; controlado e preenchido pelo contribuinte de forma sequencial no fluxo de decisão administrativa ou judicial, sem coincidir com NFS-e existentes | Representa o número único da NFS-e. No fluxo operacional padrão, é gerado automaticamente pelo sistema para garantir unicidade. Em cenários de decisão administrativa ou judicial, o contribuinte assume o controle sequencial, o que é crucial para manter a integridade dos registros e evitar duplicidades. |
| ambGer | 2 | Especifica o ambiente de geração do documento, com o valor '2' indicando emissão pela Sefin nacional. Isso é importante para contextualizar a origem do documento na NFS-e Nacional, auxiliando na interoperabilidade entre sistemas municipais e nacionais em fluxos administrativos. |
| tpEmis | 1 | Define o tipo de emissão da NFS-e, onde '1' representa emissão direta utilizando o modelo da NFS-e Nacional. Esse campo é vital para determinar o procedimento de emissão, diferenciando da NFS-e emitida em leiaute próprio do município e transcrita para o padrão nacional. |
| procEmi | Opcional (não precisa ser preenchido) | Campo opcional relacionado ao processo de emissão. Sua ausência não impacta o processamento obrigatório, mas pode ser útil em cenários complexos para fornecer informações adicionais sobre o fluxo de emissão na NFS-e. |

| nDFSe | 0 | Preenchido com '0' para indicar ausência de número de Documento Fiscal Eletrônico gerado por ambiente gerador de DFe do município. |
| --- | --- | --- |
| dhProc | Mesmo valor do campo dhEmi | Data e hora do processamento, que deve coincidir com a data de emissão (dhEmi). Essa sincronização é importante para manter a temporalidade consistente nos registros, especialmente em fluxos judiciais onde a data pode influenciar prazos e prescrições fiscais. |
| cLocIncid | Código de 7 dígitos da localidade de incidência do ISSQN, conforme ANEXO\\_A- MUNICIPIO\\_IBGE- PAISES\\_ISO2-v1.00- SNNFSe-20251210 | Código do local de incidência do ISSQN, obtido do anexo IBGE. No fluxo de decisão administrativa / judicial, o contribuinte deve informar este dado em conformidade com a decisão. Caso esta decisão não se refira ao local de incidência do ISSQN, deve-se seguir as regras da LC 116/2003, com exceções para imunidade (sem incidência), exportação de serviços ou serviços sem ISSQN (cTribNac=990101), onde não há destaque do imposto. Fundamental para determinar competência tributária em disputas judiciais sobre alíquota e base de cálculo. |
| xLocPrestacao | Nome do município referente ao local da prestação do serviço, conforme ANEXO\\_A- MUNICIPIO\\_IBGE- PAISES\\_ISO2-v1.00- SNNFSe-20251210 (correspondente a cLocPrestacao da DPS) | Descrição textual do local de prestação, extraída do anexo IBGE, referente ao código do município informado no campo cLocPrestacao. |
| xLocEmi | Nome do município da localidade emissora, conforme ANEXO\\_A- MUNICIPIO\\_IBGE- PAISES\\_ISO2-v1.00- SNNFSe-20251210 (correspondente a cLocEmi da DPS) | Descrição textual do local de emissão da NFS-e, baseado no anexo IBGE, referente ao código do município informado no campo cLocEmi. |
| pAliq | Quando aplicável, é obrigatório na emissão por decisão administrativa/judicial: Valor da alíquota (%) do | Percentual da alíquota aplicável ao ISSQN. Obrigatório em NFS-e de decisões administrativas/judiciais (apesar de opcional na DPS padrão). No fluxo padrão, se o município está no Sistema Nacional, o valor é parametrizado pelo ente; no fluxo de bypass, deve ser informado pelo emitente, quando |

***

|  | ISSQN relativo ao município de incidência; | aplicável. O emitente deve verificar a alíquota aplicável no município de incidência do ISSQN. |
| --- | --- | --- |
| xTribNac | Descrição do código de tributação nacional do ISSQN referente ao serviço prestado, conforme disponibilizado na plataforma nacional | Descrição textual do código nacional para o ISSQN, baseada na lista disponível na plataforma da NFS-e nacional, referente ao código informado no campo cTribNac. |
| xTribMun | Descrição do código de tributação municipal do ISSQN, conforme definido pelo município de incidência | Descrição do código de tributação municipal para o ISSQN, específico do município de incidência, referente ao código informado no campo cTribMun. |
| verAplic | Versão da aplicação que gerou a NFS-e (à escolha do contribuinte) | Versão do aplicativo ou software usado para gerar a NFS-e, para controle do contribuinte. |
| versao | Versão do leiaute da NFS- e utilizada como referência (no nível raiz do XML) | Versão do schema ou leiaute da NFS-e adotado (ex.: no elemento NFSe/). Garante alinhamento à versão vigente, sendo crítico validar a conformidade técnica e evitar rejeições por obsolescência no processamento do documento fiscal. |
| id | Precedido de 'ID': Formação de 53 posições: 'NFS' + Cód.Mun. (7) + Amb.Ger. (1) + Tipo Inscr. Federal (1) + Inscr. Federal (14, CPF com zeros à esquerda) + nNFSe (13) + AnoMes Emis. (4) + Cód.Num. aleatório (9, gerado pelo contribuinte) + DV (1); montado conforme ANEXO\\_I-SEFIN\\_ADN- DPS\\_NFSe-SNNFSe | Identificador único da NFS-e (no elemento NFSe/infNFSe/id), precedido de 'ID' para fins de assinatura digital. Deve ser montado e informado pelo contribuinte no fluxo de decisão administrativa / judicial. Estrutura fixa garante unicidade e rastreabilidade, integrando dados do emitente, emissão e verificação. O código aleatório de 9 dígitos deve ser gerado pelo contribuinte. O dígito verificador deve ser calculado segundo o algoritmo do módulo 11. |

## 1.3 Consulta e cancelamento de NFS-e emitida por decisão administrativa / judicial

***

Após o envio e a geração da nota pela plataforma, a NFS-e de decisão administrativa / judicial torna-se uma NFS-e como outra qualquer. Para consultar ou cancelar este tipo de NFS-e, deve-se utilizar os mesmos procedimentos, APIs e Métodos utilizados nas notas emitidas pelo fluxo normal.

## 1.4 Ambiente de produção restrita

Foi disponibilizada uma Swagger em ambiente de produção restrita destinada a testes por parte dos municípios conveniados.

### Link ambiente de produção restrita:

<u></u><https: sefin.producaorestrita.nfse.gov.br="" API="" SefinNacional="" docs="" index=""></https:>