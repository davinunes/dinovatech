# Manual dos Municípios Conveniados ao Sistema Nacional NFS-e

# Guia para utilização das API´s do CNC (Cadastro Nacional de Contribuintes)

![Figure](asset:sha256:bb61354480077844128b14a300fcc1339e6a1b9ff99d1640471ea7b0c1bc9929)

<!-- page 2 -->

## HISTÓRICO DE VERSÕES

| Versão | Data | Alterações da Versão |
| --- | --- | --- |
| 1.0 | 25/03/2025 | Versão inicial. |

## Resumo do Documento

## Descrição: Este documento apresenta os eventos e métodos relacionados com a API

## do Cadastro Nacional de Contribuintes (CNC) utilizado pelos municípios conveniados.

## Destinação Municípios conveniados.

<!-- page 3 -->

## Cadastro Nacional de Contribuintes (CNC) – API

O CNC NFS-e é um dos módulos do Sistema Nacional NFS-e e estará disponível para os municípios que efetivarem convênio com o Sistema Nacional da Nota Fiscal de Serviços Eletrônica – SN NFS-

e. O CNC NFs-e tem como objetivo ser o repositório das informações cadastrais que dizem respeito à situação tributária do contribuinte no âmbito municipal. A definição de uso das informações do CNC NFS-e dentro do Sistema Nacional NFS-e deverá ser informada quando o município realizar as parametrizações de seu convênio. Esta opção poderá ser alterada caso seja do interesse do município. Após a ativação do convênio pelo município, caso tenha optado pelo uso do CNC NFS-e, então todas as validações de emissões de NFS-e dos seus contribuintes serão validadas pelas informações que estiverem cadastradas no CNC deste município na data de competência da emissão da NFS-e. O CNC NFS-e será composto pelas bases cadastrais de todos os municípios que optarem pelo envio e manutenção das informações de seus contribuintes. A distribuição do cadastro CNC NFSe para os municípios também é uma das finalidades do CNC NFS-e e estará disponível para aqueles que optarem por, também, utilizar as informações do CNC NFS-e como a base de dados que é utilizada para validar as NFS-e de seus contribuintes. Outros municípios que aderirem ao sistema nacional utilizando os cadastros RFB como cadastro de validação das emissões de NFS-e de seus contribuintes em vez do CNC NFS-e poderão realizar consultas no CNC, mas não haverá distribuição das informações do CNC NFS-e, ou seja, as alterações realizadas por outros municípios sobre seus contribuintes poderão ser apenas consultadas, mas não distribuídas para aqueles municípios que optaram pelos cadastros RFB. O CNC NFS-e foi criado para possibilitar a disponibilização de um repositório centralizado que viabilizasse a validação do emitente da NFS-e. O CNC NFS-e é operado no âmbito do Sistema Nacional NFS-e e mantido pelos municípios, sendo responsabilidade de cada um deles realizar a alimentação das informações com respeito aos seus respectivos contribuintes. O CNC NFS-e em sua versão atual trata contribuintes CPF e CNPJ desde que tenham Inscrição Municipal no próprio município.

## Definições do Sistema

- É importante ressaltar que todo o sistema está orientado para usar informações de uma tríade constituída por Código do Município, CNPJ/CPF e Inscrição Municipal (IM) de um estabelecimento, informados pelo município. Desta maneira, ao longo deste documento, todas referências a tríade “Município/CNPJ/IM” ou “Município/CPF/IM” devem ser levadas no contexto de que o par CNPJ/IM ou CPF/IM foi informado por um determinado Município.
- Note que o mesmo CNPJ/CPF pode ser informado por mais de um município e em um mesmo município, desde que tenha inscrições municipais diferentes.
- Está disponível um serviço que possibilitará o gerenciamento das informações permitindo a inclusão e alteração de informações e a exclusão lógica do contribuinte.
- O município será responsável pelo envio das informações respeitando as regras do layout para cada tipo de manutenção (inclusão, alteração, exclusão lógica).
- Será gerado histórico para cada movimentação enviada.
- Importante ressaltar que todo o Sistema Nacional da NFS-e leva em consideração a vigência das informações como por exemplo, alíquotas, benefícios, reduções, etc. Para as movimentações de alteração e exclusão lógica o mesmo conceito será utilizado.

<!-- page 4 -->

- Para o processamento das solicitações de movimentação de um contribuinte o CNC NFS-e irá considerar a chave única formada pelo código do município, o CNPJ/CPF e a IM do contribuinte informados.
- É de responsabilidade do município a definição da necessidade de envio das movimentações relativas aos seus contribuintes. O CNC NFS-e deverá fazer o processamento das solicitações tão logo as receba.
- O município poderá solicitar a qualquer momento cópia do CNC NFS-e. Para solicitar essa distribuição, o município poderá utilizar o NSU de cadastro ou NSU de movimento (ambos serão detalhados mais adiante). Poderá também utilizar outros filtros adicionais como CNPJ/CPF, Inscrição Municipal, código do município.

## Serviços de Manutenção e Distribuição do CNC NFS-e

### NSU do CNC NFS-e

O NSU Cadastro é uma identificação única para cada contribuinte gerada no momento de sua inclusão no CNC NFSe. Esta identificação fará parte do retorno da solicitação de movimentação das informações. Importante salientar que os registros recuperados a partir de um NSU de cadastro trará as informações atuais do(s) contribuinte(s) no CNC e não as informações iniciais do seu cadastro.

O NSU Movimento é uma identificação gerada para cada solicitação de movimentação de informações. Esta identificação fará parte do retorno da solicitação de movimentação de informações e é sequencial para toda movimentação do CNC NFSe, independentemente de qual seja o município solicitante. A cada movimentação de informações solicitadas por qualquer município para qualquer contribuinte o NSU de movimentação é acrescido.

### Manutenção do CNC NFS-e

O objetivo deste serviço é a manutenção do CNC NFS-e pelos municípios conveniados ao Sistema Nacional NFS-e.

Para a manutenção das bases das informações dos contribuintes do município o mesmo deverá solicitar a movimentação das informações pela API CNC método **POST**. Neste método serão enviadas as informações para inclusão ou alteração.

No processamento da movimentação solicitada será verificado se já existe informações registradas para o contribuinte no município, para a inscrição municipal informada. Caso ainda não exista informações para a chave informada será considerada uma movimentação de inclusão. Independentemente de ser uma movimentação de inclusão ou alteração deverão ser enviadas todas as informações do contribuinte. Na movimentação de alteração serão sobrepostas as informações enviadas sobre aquelas já registradas.

Para cada movimentação solicitada que altere as informações do contribuinte será gerado um histórico da situação anterior. Esse procedimento além de prover mais segurança e possibilidade de verificações posteriores atende ao requisito de vigência das informações, ou seja, na geração de uma NFS-e onde o município faça opção por utilizar CNC NFS-e, as informações serão recuperadas levando-se em consideração a vigências dessas informações e a data de competência da nota fiscal.

### Distribuição do CNC NFSe

Para o solicitar a distribuição do CNC NFSe o município deverá utilizar o método GET da API CNC.

O CNC NFSe poderá ser recuperado integralmente ou a partir de um NSU informado. Neste caso o município deverá utilizar o método Get/distribCNC. Este método irá recuperar os registros a partir do NSU de cadastro ou NSU de movimentação informado. Neste método poderão ser informados também filtros opcionais como CNPJ/CPF, IM ou código do município.

<!-- page 5 -->

Para recuperar registro específico do cadastro do município, deverá utilizar o método Get/cncUnico. Este método irá recuperar o registro identificado pelo NSU de cadastro ou pelo NSU de movimento informado. Neste método não haverá possiblidade de informar filtros adicionais já que o retorno é apenas um registro.

Como explanado no item anterior para a distribuição CNC NFSe, o município deverá utilizar o método GET da API CNC. A distribuição do cadastro poderá ser solicitada informando o NSU de cadastro, o NSU de movimento e filtros opcionais como CNPJ/CPF, IM ou código do município.

## API CNC

**1.1. Descrição** Serviço para atender dois objetivos: a manutenção e a distribuição do CNC NFS-e. Para o serviço de distribuição do CNC NFSe será utilizada API que terá como identificador o NSU Cadastro ou NSU Movimento e poderá utilizar também filtros adicionais como CPF/CNPJ, código do município e/ou inscrição municipal.
**1.2. Métodos**
***a. POST – /CNC***
Método da API que recepciona uma movimentação enviada pelo município. A API realiza validações de negócio sobre a movimentação recebida, rejeitando (se a validação não passar em ao menos uma das regras de negócio definidas para o CNC) ou incluindo/alterando as informações do(s) contribuinte(s).

O sistema retorna para o município a mensagem de erro com o motivo da rejeição da validação ou o arquivo com o NSU Movimento para cada movimentação efetivada.

***b. GET – /cad/CNC/{nsu}***
Método que recupera o cadastro CNC NFSe pela chave de pesquisa. Caso a chave de pesquisa seja o NSU Cadastro os registros recuperados serão aqueles que tem as informações mais atualizadas.

***c. GET – /mov/CNC/{nsu}***
Caso a chave de pesquisa seja o NSU Movimento serão recuperados os registros de atualizações a partir do NSU informado.

Para o processamento realizado sobre o CNC devem ser compreendidos:

- Leiaute Cadastro Contribuintes NFSe;
- Regras de negócio Cadastro CNC NFS-e;
- Leiaute CNC NFS-e;
- Leiaute Movimentação CNC NFSe;
- Leiaute de distribuição do CNC NFSe.

Estes itens estão disponíveis no <u>AnexoV-LeiautesRN_CNC-SNNFSe</u>, anexo a este manual.

**1.3. AMBIENTE DE PRODUÇÃO RESTRITA** Foi disponibilizado um ambiente destinado a realização de testes da API do CNC por parte dos municípios conveniados:

### Link para produção restrita Swagger:

<u>[https://adn.producaorestrita.nfse.gov.br/municipios/docs/index.html](https://adn.producaorestrita.nfse.gov.br/municipios/docs/index.html)</u>

Link: [https://www.gov.br/nfse/pt-br/biblioteca/documentacao-tecnica](https://www.gov.br/nfse/pt-br/biblioteca/documentacao-tecnica)

Link: [https://nam10.safelinks.protection.outlook.com/?url=https%3A%2F%2Fadn.producaorestrita.nfse.gov.br%2Fmunicipios%2Fdocs%2Findex.html&data=05%7C02%7Crafael.silva%40rfb.gov.br%7Cd01cef5cacbe4bd23b5208de029c75d4%7C6f49aa43822a4c209670db7700bf1eb0%7C0%7C0%7C638951068178691058%7CUnknown%7CTWFpbGZsb3d8eyJFbXB0eU1hcGkiOnRydWUsIlYiOiIwLjAuMDAwMCIsIlAiOiJXaW4zMiIsIkFOIjoiTWFpbCIsIldUIjoyfQ%3D%3D%7C0%7C%7C%7C&sdata=8%2FiRJP8TYxkRXl%2FsRC4PAT9ozwWIHRZpgFpl0XRXgh4%3D&reserved=0](https://nam10.safelinks.protection.outlook.com/?url=https%3A%2F%2Fadn.producaorestrita.nfse.gov.br%2Fmunicipios%2Fdocs%2Findex.html&data=05%7C02%7Crafael.silva%40rfb.gov.br%7Cd01cef5cacbe4bd23b5208de029c75d4%7C6f49aa43822a4c209670db7700bf1eb0%7C0%7C0%7C638951068178691058%7CUnknown%7CTWFpbGZsb3d8eyJFbXB0eU1hcGkiOnRydWUsIlYiOiIwLjAuMDAwMCIsIlAiOiJXaW4zMiIsIkFOIjoiTWFpbCIsIldUIjoyfQ%3D%3D%7C0%7C%7C%7C&sdata=8%2FiRJP8TYxkRXl%2FsRC4PAT9ozwWIHRZpgFpl0XRXgh4%3D&reserved=0)