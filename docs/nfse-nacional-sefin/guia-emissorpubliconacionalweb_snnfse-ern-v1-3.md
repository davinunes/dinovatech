# Sistema Nacional Nota Fiscal de Serviço

### Guia do Emissor Público Nacional Web da NFS-e

***

##### HISTÓRICO DE VERSÕES

| Versão | Data | Alterações da Versão |
| --- | --- | --- |
| 1.0 |  |  |
| 1.1 | 23/09/2025 | Inclusão dos itens 4.3, 4.3.1, 4.3.2, 4.3.3 e 4.3.4 referentes a Emissão de NFS-e por decisão administrativa/judicial. |
| 1.2 | 27/07/2026 | Adequação do guia aos novos campos de IBS/CBS. |
| 1.3 | 31/10/2026 | Inclusão do item 11. “Alternância de Perfis e Procurações” e atualização dos itens 1.1.3 e 10.6.3. |

##### Resumo do Documento

Este documento é um roteiro para auxiliar os emitentes da Nota Fiscal de Serviço eletrônica **Descrição:** – NFS-e a usarem o emissor web do sistema da NFS-e.

##### Destinação: Emitentes de NFS-e.

***

**SUMÁRIO**

**1. INTRODUÇÃO.................................................................................................................................................... 6**
1.1. ACESSO AO EMISSOR PÚBLICO NACIONAL DA NFS-E...............................................................................................7
*1.1.1. Acesso com Usuário/Senha................................................................................................................7*
*1.1.2. Acesso com Certificado Digital........................................................................................................15*
*1.1.3. Acesso com GOV.BR............................................................................................................................16*
**2. PAINEL PRINCIPAL.......................................................................................................................................... 17**
2.1. MENU.......................................................................................................................................................................18
2.2. PAINÉIS GERENCIAIS................................................................................................................................................18
**3. CONFIGURAÇÕES........................................................................................................................................... 18**
**4. EMITINDO A NOTA FISCAL............................................................................................................................ 20**
4.1.EMISSÃO DE NFS-E COMPLETA (TODOS CONTRIBUINTES)...........................................................................................21
*4.1.1. PASSO 1 – Pessoas..............................................................................................................................22*
*4.1.2. PASSO 2 – Serviço...............................................................................................................................44*
*4.1.3. PASSO 3 – Valores...............................................................................................................................67*
*4.1.4. PASSO 4 – Emitir NFS-e....................................................................................................................85*
*4.1.5. NFS-e Gerada com sucesso..............................................................................................................87*
4.2. EMISSÃO DE NFS-E SIMPLIFICADA (CONTRIBUINTES MEI)......................................................................................88
*4.2.1. Configuração do sistema para emissão da NFS-e simplificada..........................................89*
*4.2.2. Configurações no menu principal:.................................................................................................89*
*4.2.3. Criando serviços favoritos.................................................................................................................90*
*4.2.4. Emitir NFS-e...........................................................................................................................................91*
*4.2.5. NFS-e Gerada com sucesso..............................................................................................................97*
4.3. EMISSÃO POR DECISÃO ADMINISTRATIVA OU JUDICIAL.............................................................................................97
*4.3.1. Inclusão de NFS-e por decisão administrativa/judicial..........................................................97*
*4.3.2. Consulta de NFS-e emitida por decisão administrativa / judicial......................................99*
*4.3.3. Cancelar NFS-e emitidas por decisão administrativa / judicial ..........................................99*
*4.3.4. Substituir NFS-e emitida por decisão administrativa / judicial.........................................100*
**5. CONSULTAS NFS-E EMITIDAS.................................................................................................................... 101**
5.1. VISUALIZAR NFS-E E EVENTOS VINCULADOS........................................................................................................102
5.2. SUBSTITUIÇÃO DE NFS-E......................................................................................................................................103
5.3. CANCELAMENTO DE NFS-E E SOLICITAÇÃO DE CANCELAMENTO POR ANÁLISE FISCAL........................................104
5.4. DOWNLOAD XML..................................................................................................................................................107
5.5. DOWNLOAD DANFSE...........................................................................................................................................107
**6. CONSULTA NFS-E RECEBIDAS .................................................................................................................. 108**
6.1. VISUALIZAR NFS-E RECEBIDA................................................................................................................................109
6.2. DOWNLOAD XML..................................................................................................................................................109
6.3. DOWNLOAD DANFSE...........................................................................................................................................110
6.4. MANIFESTAÇÃO DE NFS-E....................................................................................................................................110
*6.4.1. Confirmar uma NFS-e.......................................................................................................................111*
*6.4.2. Rejeitar uma NFS-e...........................................................................................................................111*
**7. RASCUNHO DPS............................................................................................................................................ 113**
7.1. EDITAR DPS..........................................................................................................................................................114
7.2. EXCLUIR DPS........................................................................................................................................................114
**8. SERVIÇOS FAVORITOS................................................................................................................................ 115**
**9. CONSULTA PÚBLICA DE UMA NFS-E........................................................................................................ 116**
9.1. CONSULTA USANDO A CHAVE DE ACESSO DA NFS-E............................................................................................117
9.2. CONSULTA INFORMANDO OS DADOS DA DPS.......................................................................................................118

| 10. |  |  | CONTROLE DE ACESSO AO SISTEMA NACIONAL NFS-E – CONTRIBUINTES................................... 119 |
| --- | --- | --- | --- |
| 10.1. |  |  | GERENCIAMENTO DE PERFIS E NÍVEIS DE ACESSO.............................................................................................119 |
| 10.2. |  |  | CONTROLE DE PRIMEIRO ACESSO (EMITENTES / NÃO EMITENTES DE NFS-E)..................................................119 |
| 10.3. |  |  | REALIZAR PRIMEIRO ACESSO AO SISTEMA NACIONAL NFS-E............................................................................119 |
| 10.4. |  |  | RECUPERAR SENHA DO USUÁRIO.......................................................................................................................122 |
| 10.5. |  |  | CONFIRMAÇÃO DO CADASTRAMENTO PRESENCIAL AO USUÁRIO......................................................................124 |
| 10.6. | 10.6.1. 10.6.2. 10.6.3. |  | CONTROLE DE ACESSO AO SISTEMA NACIONAL NFS-E PARA O EMISSOR WEB...............................................124 Controle de Acesso por Usuário/Senha .....................................................................................124 Controle de Acesso por Certificado Digital...............................................................................124 Controle de Acesso ao Sistema Nacional NFS-e via GOV.BR............................................125 |
| 11. |  |  | ALTERNÂNCIA DE PERFIS E PROCURAÇÕES.......................................................................................... 126 |
| 11.1. | 11.1.1. 11.1.2. |  | TIPOS DE REPRESENTAÇÃO...............................................................................................................................126 PROCURAÇÃO ELETRÔNICA...........................................................................................................................126 INTEGRAÇÃO COM A BASE CNPJ..................................................................................................................127 |
| 11.2. |  |  | COMO ALTERNAR PERFIS NO EMISSOR WEB.....................................................................................................128 |

***

##### SIGLAS UTILIZADAS:

ABRASF-Associação Brasileira de Secretários e Dirigentes das Finanças dos Municípios das Capitais ATM-Administração Tributária Municipal ADN – Ambiente de Dados Nacional CNC – Cadastro Nacional de Contribuintes CNM-Confederação Nacional dos Municípios DNA – Documento Nacional de Arrecadação GMP-Gestor Municipal Principal DPS – Declaração da Prestação de Serviços Sefin Nacional – Secretaria de Finanças Nacional CGNFS-e-Comitê Gestor da NFS-e MAN – Módulo de Apuração Nacional NFS-e-Nota Fiscal de Serviço Eletrônica Nacional RFB-Receita Federal do Brasil SEBRAE-Serviço Brasileiro de Apoio às Micro e Pequenas Empresas SERPRO-Serviço Federal de Processamento de Dados

***

#### Emissor Público Nacional NFS-e

A emissão da NFS-e Nacional é feita através de um sistema público de emissão, disponível para acesso via Web, Mobile ou API.

- Emissão Web: sistema para gerenciamento por parte do contribuinte, onde ele poderá se cadastrar, emitir, consultar e cancelar as NFS-e emitidas.
- Emissão Mobile: permitirá a emissão de NFS-e simplificada, visando atender especificamente aos contribuintes dos municípios que utilizam a Sefin Nacional, comunicando-se somente com o Sistema Nacional da NFS-e.
- Emissão via API: funciona como uma Secretária de Finanças Municipal (SEFIN Nacional NFS-e) com objetivo de recepcionar e validar as Declarações de Prestação de Serviços (DPS) dos contribuintes de municípios conveniados ao SN NFS-e – sejam estas DPS enviadas diretamente para API ou emitidas nos emissores públicos nacionais Web e APP. O município que não possuir um ambiente computacional para validar as DPS de seus contribuintes poderá se conveniar ao sistema nacional para utilização da Sefin Nacional NFS-e pelos seus contribuintes de ISSQN (Imposto Sobre Serviços de Qualquer Natureza).

  Mais informações sobre os emissores encontram-se na <u></u>Portal da Nota Fiscal de Serviço <u></u>eletrônica.

## Emissor Público Nacional NFS-e – Web

# 1. Introdução

Este módulo consiste em um sistema para gerenciamento de NFS-e do contribuinte. Está basicamente estruturado nas funcionalidades para:

- Configuração do emissor;
- Emissão de NFS-e;
- Consultas NFS-e emitidas;
- Consultas NFS-e recebidas;
- Cancelamento de NFS-e;
- Substituição de NFS-e;
- Apuração Nacional – MAN (por ora, apenas em ambiente de Prod. Restrita);
- Registro de serviços mais realizados pelo emitente (favoritos); e
- Gravação de rascunhos. Além destas funcionalidades, o Emissor Público Nacional NFS-e – Web realiza diversas

  integrações com outras bases de dados: Tabelas de convênios dos Municípios (Painel Administrativo

| 1.1. | a) b) c) | CNPJ, CPF, Simples Nacional, TOM e Cadastro Nacional de Contribuinte (CNC NFS-e). O painel está disponível no endereço: Obs.: para Utilização do Módulo de Apuração Nacional Acesso ao Emissor Público Nacional da NFS-e. O contribuinte deverá acessar o O acesso ao Portal se dá de 3 formas: Acesso com usuário e senha; Acesso com certificado digital; Acesso via GOV.BR. | Nacional), Tabelas Parametrizadas pelos Municípios (Painel Administrativo Municipal), Cadastros <https://www.nfse.gov.br/EmissorNacional>. para maiores informações sobre o Módulo de Apuração Nacional-MAN, acesse: Guia. Portal de Gestão NFS-e-Contribuinte. |
| --- | --- | --- | --- |
|  | 1.1.1. | Acesso com Usuário/Senha CNPJ). Para isto deve-se clicar em: “Fazer primeiro acesso”. | Para realizar este acesso, é necessário, incialmente, fazer um cadastro para o emissor (CPF ou |

***

Na próxima tela serão solicitadas as informações conforme o tipo de contribuinte: Pessoa Física ou Jurídica.

a) Pessoa Física: Marcar a opção. O sistema abrirá os campos: “CPF” e “Data de nascimento”.
Preencher os dois campos e clicar em.

|  | Erro | Mensagem |
| --- | --- | --- |
|  | CPF não existente CPF informado para o emitente da NFS-e é inválido (verificar DV). | “Não foi possível recuperar informações do Contribuinte.” “Informe um CPF válido” |

***

A data de nascimento diferente da “A data de nascimento informada não informada no cadastro CPF. corresponde ao CPF informado.”

A tela seguinte solicitará o número do título de eleitor para o CPF informado. Preencher o campo e clicar em.

Caso o emissor tenha declarado imposto de renda pessoa física, será necessário informar os números dos recibos das declarações de renda dos dois últimos anos, se for o caso. Preencher os campos e clicar em.

Na próxima tela, deverá ser informado um e-mail e criada uma senha de acesso. Preencher todo os campos e clicar em.

***

Será enviado um “código de segurança” no e-mail cadastrado. Deve-se acessar o e-mail e verificar o “código de segurança” enviado. Informar o código no campo correspondente e clicar em.

A etapa para o primeiro acesso de Pessoa Física está finalizada.

b) Pessoa Jurídica: Marcar a opção. O sistema abrirá os campos: “CNPJ”, “CPF do responsável” e “Data
de nascimento”(do responsável). Preencher os três campos e clicar em.

***

|  | Erro | Mensagem |
| --- | --- | --- |
|  | CNPJ não existente CPF do responsável diferente do informado no Cadastro CNPJ A data de nascimento do responsável é diferente da cadastrada no cadastro CPF | “Não foi possível recuperar informações do Contribuinte.” “O CPF informado não corresponde ao CPF responsável pelo CNPJ informado.” “A data de nascimento informada não corresponde ao CPF informado.” |

A tela seguinte solicitará o número do título de eleitor do responsável pelo CNPJ informado. Preencher o campo e clicar em.

|  | Caso o responsável pelo CNPJ tenha declarado imposto de renda pessoa física, será necessário informar os números dos recibos das declarações de renda dos dois últimos anos, se for o caso. |
| --- | --- |
| Preencher os campos e clicar em | . Na próxima tela, deverá ser informado um e-mail e criada uma senha de acesso. Preencher |
| todo os campos e clicar em | . |

***

Será enviado um “código de segurança” no e-mail cadastrado. Deve-se acessar o e-mail e verificar o “código de segurança” enviado. Informar o código no campo correspondente e clicar em.

A etapa para o primeiro acesso de Pessoa Jurídica está finalizada.

Em ambos os casos, ao finalizar o cadastro de Usuário/Senha, será exibida a página inicial do Portal onde deve ser informado o usuário (CPF/CNPJ) e a senha previamente cadastrados. Preencher ambos os campos e clicar em.

***

1. Não foi possível criar seu usuário

***

### 1.1.2. Acesso com Certificado Digital

Outra forma de acesso é com o uso do certificado digital. Clicar sobre o ícone do certificado e o sistema abrirá a janela para selecionar o certificado.

Selecione o certificado a ser usado e informe a senha na janela seguinte.

**Obs.:** Certificados digitais do tipo A1, instalados diretamente no computador do usuário, não exigem senha ao utilizá-los.

***

### 1.1.3. Acesso com GOV.BR

Uma terceira forma de acesso é com o uso do serviço GOV.BR. Clicar sobre o ícone e o sistema abrirá a janela de login do GOV.BR. Realizar o login, conforme as orientações do site.

Três observações importantes em relação ao acesso a partir dessa integração:

1. Apenas as contas com selo de confiabilidade Prata ou Ouro do GOV.BR serão aceitas;
2. Nesse mecanismo, o usuário se logará com o seu CPF. Caso o usuário queira alternar o perfil para um CNPJ de sua responsabilidade ou representação, ele deve se utilizar do mecanismo de Alternância de Perfis descrito em tópico específico deste guia;
3. Para os Microempreendedores Individuais-MEI, há uma exceção em relação à observação “2)”, acima: por padrão, caso o CPF utilizado para o login via GOV.BR seja responsável legal de um CNPJ na condição de MEI, o usuário se logará no perfil desse CNPJ e não do seu CPF.

***

# 2. Painel Principal

Ao realizar o login por uma das formas descritas no item 1, o sistema exibirá o painel principal do módulo Emissor Público Nacional NFS-e. Este painel possui diversas consultas realizadas na base de dados do Emissor Web para o usuário que está acessando o sistema.

Estas informações consolidadas são apresentadas no painel principal em pequenos dashboards (painéis) de acordo com o perfil do usuário logado. Estas informações podem não ser apresentadas dependendo do usuário que estiver acessando o sistema, pois o objetivo principal desses dashboards é auxiliar a interação do usuário emitente de NFS-e com o Emissor Web. Assim, um usuário não emitente, por exemplo, não possui rascunhos ou últimas NFS-e emitidas para serem apresentadas.

***

O Painel Principal apresenta um menu com os módulos do sistema e o painel gerencial, tratados a seguir.

## 2.1.

## Menu

• • • • • • • •

## 2.2.

• • • •

# 3.

Página inicial; Tipos de emissão (completa, simplificada e por decisão administrativa/judicial); NFS-e emitidas; NFS-e recebidas; Rascunhos; Módulo de Apuração Nacional – MAN (por ora, apenas em ambiente de Prod. Restrita); Configurações; Serviços Favoritos;

## Painéis gerenciais

Rascunhos; Dados do usuário que acessou o Emissor Web; Últimas NFS-e emitidas; Painel para acesso rápido a algumas funcionalidades;

# Configurações

São apresentados, no Painel Principal, os painéis gerenciais:

Este módulo deve ser o primeiro a ser acessado. Ele contempla as funcionalidades de complemento das informações do emitente da NFS-e.

***

O e-mail e o telefone informados podem ser diferentes das informações cadastradas no município e/ou nas bases de dados da RFB. Por este motivo, para cada NFS-e emitida pelo Emissor Nacional Web, eles serão utilizados no grupo de informações da DPS conforme o tipo de emitente (Prestador/Fornecedor, Tomador/Adquirente ou Intermediário) e serão impressos no DANFSe somente quando forem ausentes nas fontes cadastrais oficiais.

| Campo | Descrição | Tipo | Ocorrência | Ação |
| --- | --- | --- | --- | --- |
| E-mail | Endereço do e-mail | Endereço de e- mail | Opcional | Informar um endereço de e- mail |
| Telefone | Telefone para Número contato |  | Opcional | Incluir um telefone |
| Opção atual no Informação sobre a Texto Simples Nacional Valor aproximado dos tributos – marcar a opção que melhor atende ao perfil do emitente. Para cada emissão de NFS-e, a opção selecionada abaixo influenciará no cálculo/exibição dos valores totais dos tributos Federais, Estaduais e Municipais. | opção atual no Simples Nacional do emitente. |  | Obrigatório | Este campo é recuperado dos cadastros RFB. |

***

A opção “Informar alíquota do Simples Nacional” será exibida somente para os optantes desse regime. Nesse caso, a ME/EPP informará a alíquota nominal a qual está sujeita nos termos do referido regime, conforme o art. 9º, do Decreto 8.264/2014, que regulamenta a Lei

12.741/2012. Se esta for a opção marcada, nenhum preenchimento será necessário.
Se esta for a opção marcada, deve-se preencher os percentuais “Federal”, “Estadual” e “Municipal”.

Se esta for a opção marcada, deve-se preencher a alíquota nominal do Simples Nacional aplicável ao emitente.

Após essa configuração, a emissão da nota fiscal de serviços eletrônica poderá ser feita.

# 4. Emitindo a Nota Fiscal

Para emitir a nota fiscal pelo Emissor Web, deve-se selecionar o ícone do menu marcado com o sinal de + na imagem abaixo e selecionar se deseja utilizar a Emissão Completa, Emissão Simplificada e Emissão por Decisão Administrativa/Judicial; ou, selecionar o mesmo ícone no painel de acesso rápido, que direcionará para a Emissão Completa. **A Emissão Simplificada é** **disponível apenas para o MEI.**

***

## 4.1.Emissão de NFS-e Completa (Todos Contribuintes)

A Emissão Completa é obrigatória para alguns tipos de prestação de serviço, como exportação e serviços cujo ISSQN é devido no local do tomador/adquirente.

Para emitir a nota fiscal de modo completo pelo portal web, basta clicar no ícone marcado na imagem abaixo e selecionar a opção “Emissão Completa”; ou no painel de “Acesso Rápido”:

Para preencher e emitir uma NFS-e serão consideradas regras de acordo com as opções de cada conjunto de informações solicitadas como Pessoas, Serviço, Valores e Emitir NFS-e.

Na tabela abaixo estão explicitadas a maioria das informações que poderão ser necessárias.

A obrigatoriedade dos campos, bem como as regras de negócio aplicadas para cada um deles, estão dispostas nas planilhas de leiaute DPS e RN DPS (<u></u>ANEXO\_I-SEFIN\_ADN-DPS\_NFSe-SNNFSe).

***

### 4.1.1. PASSO 1 – Pessoas

Nesta tela é feito o preenchimento dos dados dos participantes da NFS-e, quais sejam, emitente, prestador/fornecedor, tomador/adquirente e destinatário.

Além disso, informações gerais da DPS também são preenchidas nesta tela, tais como: data de competência, número e série da DPS, opção pelo preenchimento das informações de IBS/CBS e a informação de que a operação se trata de uma compra governamental.

A obrigatoriedade dos campos da tabela, bem como as regras de negócio aplicadas para cada um deles, estão dispostas na planilha de leiaute DPS e RN DPS descrita abaixo. Após o preenchimento dos campos, conforme indicado, clicar em.

| Campo | Descrição | Tipo | Ocorrênci a | Ação |
| --- | --- | --- | --- | --- |
| INFORMAÇÕES GERAIS |  |  |  |  |
| Preencher as Permite informações IBS/CBS? A partir de 03 de agosto de 2026, passará a ser exigido o preenchimento das informações relativas ao IBS e à CBS, observadas as respectivas regras de validação. Dessa forma, não será permitida a emissão de documentos fiscais eletrônicos, pelas empresas sujeitas ao regime regular, sem o preenchimento dos campos correspondentes ao IBS e à CBS. Às empresas optantes do regime do Simples Nacional, o início da obrigatoriedade do preenchimento das informações relativas ao IBS e à CBS se dará para os fatos geradores ocorridos a partir de 01 de janeiro de 2027. | ao CheckBox emitente indicar se prencherá ou não as informações relativas ao IBS e à CBS |  | Obrigatório | Selecionar uma das 2 opções para o emitente: o Sim o Não |
| Data de Competência | Data em que o Data serviço foi prestado |  | Obrigatório | Incluir a data da competência ou clicar no ícone do calendário e navegar até a data definida. |
| DPS legislação tributária municipal. Comitê Gestor da NFS-e). Nota Fiscal de Serviço eletrônica | lote, para processamento e geração das respectivas NFS-e. Mais informações sobre DPS podem ser consultadas diretamente no site. | (Declaração de Prestação de Serviço) é um documento de posse e responsabilidade do contribuinte, que deverá ser gerado manualmente ou por alguma aplicação adquirida, possuindo uma numeração sequencial crescente e devendo ser convertido em NFS-e no prazo estipulado pela Este documento atende também àqueles contribuintes que, porventura, não dispõem de infraestrutura de conectividade em tempo integral, podendo gerar os documentos e enviá-los, em Para que as DPS possam fazer parte de um lote a ser enviado para geração das NFS-e correspondentes, é necessário que o contribuinte possua uma aplicação instalada em seus computadores (desenvolvida particularmente seguindo as especificações disponibilizadas pelo |  | O que é DPS? — Portal da |
| ou | O número e a série Checkbox da DPS são campos obrigatórios de controle da DPS que ajudam a identificar unicamente uma DPS. Em geral, campos são gerados automaticamente pelo sistema para cada emitente de NFS-e. No entanto, existem situações em que o emitente ainda necessita |  | Opcional | Clicar se a opção for informar a DPS. |
| Série | Informação sobre Numérico a série da DPS. |  | Obrigatório | Incluir a série da DPS. Deve ser um número entre 80.000 e 89.999, de acordo com a regra “Faixas de utilização da série da DPS”, descrita no ANEXO\\_I-SEFIN\\_ADN- DPS\\_NFSe-SNNFSe-v1.01- 20260209 |
| Número da DPS | Número da DPS | Numérico | Obrigatório | Incluir o número da DPS |
| EMITENTE DA NFS-E-Podem ser informadas 3 opções de emitente: Prestador/Fornecedor, Tomador/Adquirente ou Intermediário. A depender da opção feita, os campos para preenchimento vão sendo ajustados. Na atual versão, a NFS-e somente poderá ser emitida pelo Prestador/Fornecedor de Serviço. As emissões de NFS-e por Tomador/Adquirente e Intermediário ainda não estão disponíveis. A funcionalidade será disponibilizada em uma versão futura. |  |  |  |  |
| CPF/CNPJ | Informação do NI Numérico (CPF/CNPJ) do emitente |  | Obrigatório | Este campo é recuperado dos cadastros RFB ou CNC conforme convênio do município |
| Nome | Informação sobre Texto o nome ou Razão Social do emitente |  | Obrigatório | Este campo é recuperado dos cadastros RFB ou CNC conforme convênio do município |
| Município | Informação sobre Combo o nome do município do emitente |  | Obrigatório | Este campo é recuperado dos cadastros RFB ou CNC conforme convênio do município. O município do emitente da NFS-e será preenchido automaticamente, e corresponde ao código do município emissor (cLocEmi) informado na NFS-e. De acordo com as regras de negócio. |
| Indicador Municipal | Informação do Combo indicador |  | Obrigatório | Caso haja, este campo é recuperado do cadastro CNC, |
| Opção no Simples Informa Nacional Regime de Apuração dos Tributos no Simples Nacional (para optantes do Simples Nacional) A depender da opção realizada neste campo, os campos da Tributação Municipal (ISSQN) serão ou não habilitados para preenchimento. | se o Texto contribuinte emitente é ou não optante pelo Simples Nacional Informação se o Combo regime de apuração dos tributos federais e municipal serão realizados pelo SN ou pela NFS-e |  | De acordo Este campo é recuperado dos com as cadastros RFB. regras de negócio De acordo Selecionar com as apuração regras de federais negócio | o regime de dos tributos e municipal, conforme o enquadramento do emitente. |
| Telefone | Informação do Número número do telefone do emitente. |  | Opcional | Este campo é recuperado dos cadastros RFB ou CNC conforme convênio do município. Quando ausente nos cadastros oficiais, este campo é recuperado das informações complementares do emitente, preenchidas no menu Configurações |
| E-mail | Informação do Endereço endereço do e- | de e-mail | Opcional | Este campo é recuperado dos cadastros RFB ou CNC |
| CEP | Informação do Número número do CEP |  | Obrigatório | Este campo é recuperado dos cadastros RFB ou CNC conforme convênio do município |
| Logradouro | Informação do Texto nome do logradouro |  | Obrigatório | Este campo é recuperado dos cadastros RFB ou CNC conforme convênio do município |
| Número | Informação do Texto número do logradouro |  | Obrigatório | Este campo é recuperado dos cadastros RFB ou CNC conforme convênio do município |
| Complemento | Preencher com o Texto complemento do logradouro |  | Opcional | - |
| Bairro COMPRAS GOVERNAMENTAIS – Deve-se informar se a prestação/fornecimento de serviço se trata de uma compra governamental. Para emissões com destaque de IBS/CBS, as informações referentes a compras governamentais devem ser preenchidas. Quando informado “Sim”, o endereço do Tomador/Adquirente será configurado para “Brasil” obrigatoriamente. | Preencher com o Texto nome do bairro |  | Obrigatório | Este campo é recuperado dos cadastros RFB ou CNC conforme convênio do município |
| Compras Governamentais | Campo para CheckBox informação de se a |  | Obrigatório | Selecionar uma das 2 opções para o emitente: |
| TOMADOR/ADQUIRENTE DO SERVIÇO-Podem ser informadas 3 opções relativas à localização do Tomador/Adquirente: Brasil ou Exterior ou Tomador/Adquirente não informado. A depender da opção feita, os campos para preenchimento vão sendo ajustados. Para determinados Códigos de Tributação Nacional a serem preenchidos no passo 2 (Serviço), o preenchimento do Tomador/Adquirente do serviço é obrigatório, pois define o Local de Incidência do ISSQN (Lei Complementar 116/2003). Verificar a planilha MUN.INCID\\_INFO.SERV. do AnexoI-LeiautesRN\\_DPS\\_NFSe-SNNFSe. Tomador/Adquirente não informado - Caso esta opção seja marcada, nenhum campo será exibido para preenchimento posterior. | Selecionando “Sim”, deve-se informar o tipo de ente governamental Opção para CheckBox informar onde está localizado o estabelecimento/d omicílio do Tomador/Adquiren te. | Combo | Obrigatório Obrigatório | Selecionar o ente governamental, dentre as opções: o União o Estado o Distrito Federal o Município Marcar uma das 3 opções disponíveis. o Brasil o Exterior o Tomador/Adquirente não informado o |

| Tomador/Adquirente | no Brasil – | disponibilizados referem-se a informações de uma pessoa física ou jurídica domiciliada no Brasil. |  | Caso esta opção seja marcada, os campos a serem |  |
| --- | --- | --- | --- | --- | --- |
| CPF/CNPJ | Informação CPF/CNPJ do tomador/adquiren te | do Numérico | Obrigatório | Incluir Pode-se dos | o CPF/CNPJ do tomador/adquirente. recuperar os dados últimos tomadores/ adquirentes clicando em |
| Indicador Municipal | Informação Indicador (Inscrição) Municipal do tomador/adquiren te | do Numérico | Obrigatório | (inscrição) haja. | Preencher com o indicador municipal do tomador/adquirente, caso |
| Nome/Razão Social | Informação nome ou Razão Social do tomador/adquiren te | do Texto | Obrigatório | cadastros | Este campo é recuperado dos RFB conforme convênio do município |
| Telefone | Informação número do telefone do tomador/adquiren te | do Número | Opcional |  | Incluir número do telefone do tomador/adquirente |

| E-mail | Informação do Endereço endereço do e- mail cadastrado como contato no município | de e-mail | Opcional | Incluir o endereço de e-mail do tomador/adquirente |
| --- | --- | --- | --- | --- |
| Endereço Tomador/Adquirente no Brasil Obs.: As informações dos campos a seguir são recuperadas dos cadastros RFB, quando o tomador/adquirente for um CNPJ, podendo-se alterá-las. |  |  |  |  |
| CEP | Informação do Número número do CEP |  | Obrigatório | Incluir o CEP do endereço |
| Município | Informação do Texto nome do município ao qual pertence o tomador no Brasil |  | Obrigatório | Este campo é recuperado da tabela TOM de acordo com o CEP informado |
| Bairro | Informação do Texto nome do bairro |  | Obrigatório | Este campo é recuperado da tabela TOM de acordo com o CEP informado |
| Logradouro | Informação do Texto nome do logradouro |  | Obrigatório | Este campo é recuperado da tabela TOM de acordo com o CEP informado |
| Número | Informação do Texto número do logradouro |  | Obrigatório | Incluir o número do logradouro |
| Complemento Tomador/Adquirente do Serviço no Exterior - Caso esta opção seja marcada, os campos a serem disponibilizados referem-se a informações específicas de outros países para uma pessoa | Informação do Texto complemento do logradouro |  | Opcional | Incluir o complemento do logradouro |
|  | Informação se CheckBox haverá ou não a informação do NIF (Número de Identificação Fiscal – número fornecido pelo órgão de administração tributária do exterior e que identifica pessoa física ou jurídica externa ao Brasil (equivale ao CPF ou CNPJ, identificando a pessoa física ou jurídica no exterior). Opção para o caso CheckBox de o NIF não ser informado |  | Obrigatório Obrigatório | Selecionar uma das 2 opções disponíveis: o Sim o Não Selecionar a opção “Não”. Nesse caso, deve-se informar o motivo da não informação do NIF. |
| NIF | Informação do Numérico número de identificação internacional |  | Obrigatório | Incluir o NIF do tomador/adquirente Pode-se recuperar o NIF informado dos últimos tomadores em |
| Nome/Razão Social | Nome ou Razão Texto Social do tomador/adquiren te |  | Obrigatório | Preencher com o Nome/Razão Social do tomador/adquirente |
| Telefone | Número do Número telefone do tomador/adquiren te |  | Opcional | Preencher com número do telefone do tomador/adquirente |
| E-mail Endereço do Tomador/Adquirente no Exterior | Endereço do e- mail cadastrado de e-mail como contato no município | Endereço | Opcional | Preencher com o endereço de e-mail do tomador/adquirente |
| Logradouro | Informação do Texto nome do logradouro no exterior |  | Obrigatório | Preencher com o logradouro no exterior |
| Número | Informação do Texto número do logradouro no exterior |  | Obrigatório | Preencher com o número do logradouro no exterior |
| Complemento | Informação do Texto complemento do logradouro no |  | Opcional | Preencher com o complemento do logradouro no exterior |
| Bairro | Informação do Texto nome do bairro no exterior |  | Obrigatório | Preencher com o bairro no exterior |
| Cidade | Informação do Texto nome da cidade no exterior |  | Obrigatório | Preencher com o nome da cidade no exterior |
| Código de Informação Endereçamento Postal | do Caracter código de endereçamento postal do endereço no exterior |  | Obrigatório | Preencher com o código de endereçamento postal do endereço no exterior |
| Estado, província ou Informação região | do Texto nome do Estado, província ou região no exterior |  | Obrigatório | Preencher com o nome do Estado, província ou região no exterior |
| País | Informação do Combo nome do País |  | Obrigatório | Selecionar um dos países da lista |
| DESTINATÁRIO DO SERVIÇO – Podem ser informadas 2 opções: “Sim”, para quando o Destinatário do Serviço é o próprio Tomador/Adquirente; ou “Não”, para quando o Destinatário do Serviço é uma pessoa física ou jurídica diversa do Tomador/Adquirente. A depender da opção feita, os campos a serem preenchidos serão disponibilizados na tela. Esta opção será habilitada quando selecionado “Sim” na informação geral “Preencher as informações IBS/CBS?” | Opção para o caso Combo de o Destinatário do serviço ser o próprio tomador/adquiren te informado |  | Obrigatório | Selecionar a opção “Sim”. Nesse caso, não há campos adicionais para preenchimento. |
| Destinatário no Brasil – Caso esta opção seja marcada, os campos a serem disponibilizados referem-se a informações de uma pessoa física ou jurídica domiciliada no Brasil. |  |  |  |  |
| CPF/CNPJ | Informação do Numérico CPF/CNPJ do destinatário |  | Obrigatório | Incluir o CPF/CNPJ do destinatário. Pode-se recuperar os dados dos últimos destinatários clicando em |
| Indicador Municipal | Informação do Numérico Indicador (Inscrição) Municipal do destinatário |  | Obrigatório | Preencher com o indicador (inscrição) municipal do destinatário |
| Nome/Razão Social | Informação do Texto nome ou Razão Social do destinatário |  | Obrigatório | Este campo é recuperado dos cadastros RFB ou CNC conforme convênio do município |
| Telefone | Informação do Número número do telefone do destinatário |  | Opcional | Incluir número do telefone do destinatário |
| E-mail | Informação do Endereço endereço do e- | de e-mail | Opcional | Incluir o endereço de e-mail do destinatário |
| Endereço do Destinatário no Brasil Obs.: As informações dos campos a seguir são recuperadas dos cadastros RFB, quando o destinatário for um CNPJ, podendo-se alterá-las. |  |  |  |  |
| CEP | Informação do Número número do CEP |  | Obrigatório | Incluir o CEP do endereço |
| Município | Informação do Texto nome do município ao qual pertence o destinatário no Brasil |  | Obrigatório | Este campo é recuperado da tabela TOM de acordo com o CEP informado |
| Bairro | Informação do Texto nome do bairro |  | Obrigatório | Este campo é recuperado da tabela TOM de acordo com o CEP informado |
| Logradouro | Informação do Texto nome do logradouro |  | Obrigatório | Este campo é recuperado da tabela TOM de acordo com o CEP informado |
| Número | Informação do Texto número do logradouro |  | Obrigatório | Incluir o número do logradouro |
| Complemento | Informação do Texto complemento do logradouro |  | Opcional | Incluir o complemento do logradouro |
| Destinatário do Serviço no Exterior - Caso esta opção seja marcada, os campos a serem disponibilizados referem-se a informações específicas de outros países para uma pessoa física ou jurídica domiciliada fora do Brasil. |  |  |  |  |
|  | Opção para o caso CheckBox de o NIF não ser informado Informação sobre Checkbox o motivo da não informação do NIF |  | Obrigatório Obrigatório | Selecionar a opção “Não”. Nesse caso, deve-se informar o motivo da não informação do NIF. Selecionar uma das duas opções: o Dispensado do NIF ou o Não exigência do NIF |
| Nome/Razão Social | Nome ou Razão Texto Social do destinatário |  | Obrigatório | Preencher com o Nome/Razão Social do destinatário |
| Telefone | Número do Número telefone do destinatário |  | Opcional | Preencher com número do telefone do destinatário |
| E-mail Endereço do Destinatário no Exterior | Endereço do e- mail cadastrado de e-mail como contato no município | Endereço | Opcional | Preencher com o endereço de e-mail do destinatário |
| Logradouro | Informação do Texto nome do logradouro no exterior |  | Obrigatório | Preencher com o logradouro no exterior |
| Número | Informação do Texto número do logradouro no exterior |  | Obrigatório | Preencher com o número do logradouro no exterior |
| Complemento | Informação do Texto complemento do logradouro no exterior |  | Opcional | Preencher com o complemento do logradouro no exterior |
| Bairro | Informação do Texto nome do bairro no exterior |  | Obrigatório | Preencher com o bairro no exterior |
| Cidade | Informação do Texto nome da cidade no exterior |  | Obrigatório | Preencher com o nome da cidade no exterior |
| Estado, província ou Informação região | do Texto nome do Estado, província ou região no exterior |  | Obrigatório | Preencher com o nome do Estado, província ou região no exterior |
| País INTERMEDIÁRIO DO SERVIÇO – Podem ser informadas 3 opções relativas à localização do Intermediário: Brasil, Exterior ou Intermediário não informado. A depender da opção feita, os campos a serem preenchidos serão disponibilizados na tela. Intermediário não informado – Caso esta opção seja marcada, nenhum campo será exibido para preenchimento posterior. Intermediário no Brasil – Caso esta opção seja marcada, os campos a serem disponibilizados referem-se a informações de uma pessoa física ou jurídica domiciliada no Brasil. | Informação do Combo nome do País Opção para CheckBox informar se há um intermediário e onde ele está localizado. |  | Obrigatório Obrigatório | Selecionar um dos países da lista Deve-se marcar uma das 3 opções. o Brasil o Exterior o Intermediário não informado |
| Indicador Municipal | Indicador (Inscrição) Municipal do intermediário | Numérico | Opcional | Preencher com o indicador (inscrição) municipal do intermediário |
| Nome/Razão Social | Nome ou Razão Texto Social do intermediário |  | Obrigatório | Este campo é recuperado dos cadastros RFB ou CNC conforme convênio do município |
| Telefone | Número do Número telefone do intermediário |  | Opcional | Preencher com número do telefone do intermediário |
| E-mail Endereço Intermediário no Brasil Obs.: As informações dos campos a seguir são recuperadas dos cadastros RFB, quando o intermediário for um CNPJ, podendo-se alterá-las. | Endereço do e- mail cadastrado de e-mail como contato no município | Endereço | Opcional | Preencher com o endereço de e-mail do intermediário |
| CEP | Informação do Número número do CEP |  | Obrigatório | Preencher com o CEP do endereço |
| Município | Informação do Texto nome do município ao qual pertence o |  | Obrigatório | Este campo é recuperado da tabela TOM de acordo com o CEP informado |
| Bairro | Informação do Texto nome do bairro |  | Obrigatório | Este campo é recuperado da tabela TOM de acordo com o CEP informado |
| Logradouro | Informação do Texto nome do logradouro |  | Obrigatório | Este campo é recuperado da tabela TOM de acordo com o CEP informado |
| Número | Informação do Texto número do logradouro |  | Obrigatório | Preencher com o número do logradouro |
| Complemento | Informação do Texto complemento do logradouro |  | Opcional | Preencher com o complemento do logradouro |
| Intermediário do Serviço no Exterior - Caso esta opção seja marcada, os campos a serem disponibilizados referem-se a informações específicas de outros países para uma pessoa física ou jurídica domiciliada fora do Brasil. | Opção se haverá CheckBox ou não a informação do NIF (Número de Identificação Fiscal – número fornecido pelo órgão de administração |  | Obrigatório | Selecionar uma das 2 opções disponíveis: o Sim o Não |
|  | Opção para o caso CheckBox de o NIF não ser informado Informação sobre Checkbox o motivo da não informação do NIF |  | Obrigatório Obrigatório | Selecionar a opção “Não”. Nesse caso, deve-se informar o motivo da não informação do NIF. Selecionar uma das duas opções: o Dispensado do NIF ou o Não exigência do NIF |
| NIF | Informação do Numérico número de identificação internacional |  | Obrigatório | Incluir o NIF do tomador/adquirente. Pode recuperar o NIF informado dos últimos Intermediários |
| Nome/Razão Social | Nome ou Razão Texto Social do intermediário |  | Obrigatório | Preencher com o Nome/Razão Social do intermediário |
| Telefone | Número do Número telefone do intermediário |  | Opcional | Preencher com número do telefone do intermediário |
| E-mail | Endereço do e- mail cadastrado de e-mail como contato no município | Endereço | Opcional | Preencher com o endereço de e-mail do intermediário |

***

| Endereço do Intermediário no Exterior |  |  |  |  |
| --- | --- | --- | --- | --- |
| Logradouro | Informação do Texto nome do logradouro no exterior. |  | Obrigatório | Preencher com o logradouro no exterior |
| Número | Informação do Texto número do logradouro no exterior |  | Obrigatório | Preencher com o número do logradouro no exterior |
| Complemento | Informação do Texto complemento do logradouro no exterior |  | Opcional | Preencher com o complemento do logradouro no exterior |
| Bairro | Informação do Texto nome do bairro no exterior |  | Obrigatório | Preencher com o bairro no exterior |
| Cidade | Informação do Texto nome da cidade no exterior |  | Obrigatório | Preencher com o nome da cidade no exterior |
| Código de Informação Endereçamento Postal | do Caracter código de endereçamento postal do endereço no exterior |  | Obrigatório | Preencher com o código de endereçamento postal do endereço no exterior |
| Estado, província ou Informação região | do Texto nome do Estado, província ou região no exterior |  | Obrigatório | Preencher com o nome do Estado, província ou região no exterior |
| País | Informação do Combo nome do País |  | Obrigatório | Selecionar um dos países da lista |

##### Após o preenchimento dos campos da etapa “Pessoas”, clicar em.

***

### 4.1.2. PASSO 2 – Serviço

Tela para preenchimento do(s) serviço(s) prestado(s) pela pessoa física ou jurídica emitente da NFS-e.

A obrigatoriedade dos campos da tabela, bem como as regras de negócio aplicadas para cada um deles, estão dispostas na planilha de leiaute DPS e RN DPS descrita abaixo. Após o preenchimento dos campos, clicar em.

| Campo | Descrição | Tipo | Ocorrência | Ação |
| --- | --- | --- | --- | --- |
| LOCAL DO FORNECIMENTO/PRESTAÇÃO DO SERVIÇO |  |  |  |  |
| País | Informação sobre o Combo país onde ocorreu a conclusão do serviço prestado |  | Obrigatório | Selecionar um dos países da lista constantes da TAB.PAIS\\_ISO2 do Anexo\\_A- Municipio\\_IBGE- Paises\\_ISO2-V1.00- Snnfse-20251210. |
| Município SERVIÇO/FORNECIMENTO PRESTADO – Alguns campos relativos ao serviço/fornecimento prestado somente serão disponibilizados dependendo do Código de Tributação Nacional informado. Caso o Código de Tributação Nacional seja um dos casos de preenchimento obrigatório do Local da Prestação do serviço, deve-se informar o Tomador/Adquirente do serviço e o sistema exibirá a seguinte mensagem: Informando “Sim”, o sistema exibirá uma tela para inclusão dos dados do Tomador/Adquirente, a ser preenchido conforme explicado no passo anterior (Pessoas): | Informação sobre o Combo município onde ocorreu a conclusão do serviço prestado, quando o serviço for prestado no Brasil. |  | Obrigatório | Caso o país informado para a prestação do serviço seja “Brasil”, selecionar o município (digite pelo menos 3 caracteres para realizar a busca), onde ocorreu a conclusão do serviço prestado. |
| Código Complementar Informação sobre o Combo do Município (Caso o Código município tenha criado Complementar códigos complementares) | Municipal do serviço prestado. |  | Obrigatório | Selecionar o código complementar dentre as opções listadas. |
| O serviço prestado é um Informação caso de: imunidade, serviço exportação de serviço um ou não incidência do imunidade, ISSQN? Vários campos do terceiro passo (Valores) serão preenchidos a depender das opções feitas neste campo. Se a resposta for “Não”, nenhuma informação adicional será necessária. Se a resposta for “sim”, outros campos abrirão para preenchimento. | se o Combo prestado é caso de: exportação de serviço ou não incidência do ISSQN? |  | Obrigatório | Marcar uma das opções: o Não o Sim |
|  | Informação se é um Combo caso de “Imunidade” de tributação do ISSQN sobre o serviço prestado. |  | Obrigatório | Selecionar “Imunidade” caso seja a opção para não tributação do ISSQN sobre o serviço prestado. |
| Selecionar uma das opções do Tipo de Imunidade para não incidência de ISSQN. | Informação se é um Combo caso de “Exportação de Serviço” de tributação do ISSQN sobre o serviço prestado. |  | Obrigatório | Selecionar “Exportação de Serviço” caso seja a opção para não tributação do ISSQN sobre o serviço prestado. |
| O sistema exibirá a seguinte mensagem no painel: | Informação do país Combo em que se verificou o resultado do serviço |  | Obrigatório | Selecionar um dos países da lista constantes da |
| IMPORTANTE: Se o “Código de Tributação Nacional” informado não for o 99.01.01 ou algum cadastrado no respectivo município como não incidência, o sistema não permitirá esta seleção e exibirá a seguinte mensagem: | Informação se é um Combo caso de “Não incidência” de tributação do ISSQN sobre o serviço prestado. Esta opção somente é possível se no campo “Código de Tributação Nacional” for informado o código 99.01.01 - Serviço sem incidência de ISSQN e ICMS ou para códigos de serviço configurados como não incidência de ISSQN no painel municipal do respectivo município. Nome do Município Texto de incidência do |  | Obrigatório Obrigatório | Preenchimento automático quando no campo “Código de Tributação Nacional” for informado o código 99.01.01 - Serviço sem incidência de ISSQN e ICMS ou para códigos de serviço configurados como não incidência de ISSQN no painel municipal do respectivo município. O Sistema Nacional NFS-e identifica a |

***

| imposto. |  |  | localidade |  | de |
| --- | --- | --- | --- | --- | --- |
| Este | campo | será | incidência | do | ISSQN |
| exibido a depender |  |  | conforme |  | regras |
| do Tributação Nacional”. | “Código | de | estabelecidas 116/03. |  | na LC |
| Por | exemplo: |  | Existem exceções em |  |  |
| 17.05.01 |  | – | que | o | Sistema |
| Fornecimento mão-de-obra, |  | de | Nacional identifica a localidade | NFS-e | não |
| mesmo | em caráter |  | de | incidência | nem |
| temporário, inclusive |  |  | haverá | destaque | do |
| de empregados trabalhadores, |  | ou | ISSQN na emissão da NFS-e: |  |  |
| avulsos temporários, |  | ou | 1) imunidade não existe | Em caso | de |
| contratados |  | pelo | localidade |  | de |
| prestador/ |  |  | incidência | para | o |
| fornecedor |  | de | ISSQN; 2) | Em caso | de |

serviço. exportação de serviço não existe localidade de incidência para o ISSQN;

3. Em caso de serviços sem a incidência de ISSQN (cTribNac igual a 990101), não existe

| localidade |  | de |
| --- | --- | --- |
| incidência ISSQN; | para | o |
| operação | tributável |  |

4. caso de

Em

deve existir localidade de incidência para o ISSQN. os

| Inclusive | para |
| --- | --- |
| serviços para os subitens 03.04 e 22.01, se informado | prestados |
| um | município |

***

específico no local de prestação de serviço, conforme TAB.MUN\_IBGE. As exceções para não

| existir | município | de |
| --- | --- | --- |
| incidência nas NFS-e de serviços | do | ISSQN |
| prestados | para | os |
| subitens 22.01, | 03.04 | e |
| respectivamente, definidos abaixo: |  | são |
| a) Locação, arrendamento, direito | caso sublocação, | de |
| de |  | ou |
| permissão compartilhado ou não, de ferrovia, postes, cabos, dutos e condutos de qualquer natureza | de rodovia, (subitem | uso, |
| 03.04 serviço | da lista do Sistema | de |

Em

passagem

Nacional NFS-e), se o local de prestação de serviço informado na DPS corresponder à

| localidade |  | geral |
| --- | --- | --- |
| definida | para | este |
| serviço conforme TAB.LOC.GERAL), | (0000000, |  |
| então | não | é |
| identificada |  | a |
| localidade |  | de |
| incidência ISSQN na NFS-e | para | o |
| (as |  | das |

informações

|  |  |  |  | localidades de incidência do ISSQN para o serviço 03.04 ocorrerão na solicitação de apuração enviada pelo contribuinte ao MAN); |
| --- | --- | --- | --- | --- |
| Data de Competência | Data da competência Data da incidência do ISSQN. |  | Obrigatório | Recupera a data de competência informada na tela inicial (Pessoas) |
| Item da NBS Informação sobre a Combo correspondente ao NBS - serviço/fornecimento prestado | Nomenclatura Brasileira de Serviços |  | Obrigatório | Selecionar a NBS correspondente ao serviço prestado |
| Código indicador da Informação sobre o Combo operação (Preenchimento obrigatório para emissões com destaque de IBS/CBS) | Código indicador da operação |  | Obrigatório | Selecionar o Código indicador da operação correspondente ao serviço prestado, conforme lista do AnexoVII- IndOp\\_IBSCBS |
| Descrição do Serviço | Descrição do serviço Texto prestado |  | Obrigatório | Incluir uma descrição detalhada do serviço prestado |
| INFORMAÇÕES DO IMÓVEL-Campos que serão exibidos quando o Serviço/Fornecimento prestado forem relacionados a imóveis – Itens 020101, 020201 e 020301 do Código indicador da operação. |  |  |  |  |
| Código do Cadastro Imobiliário Brasileiro CIB-Somente permitido se a opção do Local da Prestação do Serviço for “Brasil” | Código do Cadastro Texto Imobiliário Brasileiro |  | Obrigatório | Incluir o CIB |
| Endereço no Brasil-Somente permitido se a opção do Local da Prestação do Serviço for “Brasil” Obs.: O CEP a ser informado para endereço do imóvel deve pertencer ao município que foi informado como local da prestação do serviço. |  |  |  |  |
| CEP | Informação do Número número do CEP da obra. |  | Obrigatório | Incluir o CEP da obra |
| Município | Informação do nome Texto do município ao qual pertence a obra no Brasil |  | Obrigatório | O sistema recupera as informações de logradouro na tabela TOM com o CEP informado |
| Bairro | Informação do bairro | Texto | Obrigatório | O sistema recupera as informações de logradouro na tabela TOM com o CEP informado |
| Logradouro | Informação do nome Texto do logradouro. |  | Obrigatório | O sistema recupera as informações de logradouro na tabela |
| Número | Informação do Texto número do logradouro, |  | Obrigatório | Incluir o número do logradouro |
| Complemento | Informação do Texto complemento do logradouro. |  | Opcional | Incluir o complemento do logradouro |
| Endereço no Exterior-Somente permitido se a opção do Local da Prestação do Serviço não for “Brasil” |  |  |  |  |
| Bairro | Informação do bairro no exterior. | Texto | Obrigatório | Informar o bairro no exterior do local da obra. |
| Logradouro | Informação do nome Texto do logradouro no exterior. |  | Obrigatório | Informar o logradouro no exterior do local da obra. |
| Número | Informação do Texto número do logradouro |  | Obrigatório | Informar o número no exterior do local da obra. |
| Complemento | Informação do Texto complemento do logradouro no exterior. |  | Opcional | Informar o complemento no exterior do local da obra. |
| Código de Informação Endereçamento Postal | do Número número do Código de endereçamento Postal no exterior. |  | Obrigatório | Informar o código de endereçamento fiscal no exterior do local da obra. |
| Cidade | Informação da Texto cidade no exterior. |  | Obrigatório | Informar a cidade no exterior do local da obra. |
| Inscrição Imobiliária Fiscal INFORMAÇÕES PARA OBRA-Campos que serão exibidos quando o Serviço/Fornecimento prestado se referir a Obras-Subitens 07.02 e 07.05 do Código de Tributação Nacional. Deve-se marcar uma das opções de informação: | Código de inscrição Texto imobiliária fornecida pela Prefeitura |  | Obrigatório | Incluir a inscrição imobiliária fiscal ( código fornecido pela prefeitura para a identificação da obra ou para fins de recolhimento do IPTU). |
| Código da Obra-Somente permitido se a identificação da obra opção do Local da Prestação do Serviço for Brasil - Somente permitido se a opção do Local da Prestação do Serviço for “Brasil” | Número de Texto |  | Obrigatório | Incluir o código de obra |
| Código do Cadastro | Código do Cadastro Texto |  | Obrigatório | Incluir o CIB |
| Endereço no Brasil-Somente permitido se a opção do Local da Prestação do Serviço for “Brasil” Obs.: O CEP a ser informado para endereço do imóvel deve pertencer ao município que foi informado como local da prestação do serviço. |  |  |  |  |
| CEP | Informação do Número número do CEP da obra. |  | Obrigatório | Incluir o CEP da obra |
| Município | Informação do nome Texto do município ao qual pertence a obra no Brasil |  | Obrigatório | O sistema recupera as informações de logradouro na tabela TOM com o CEP informado |
| Bairro | Informação do bairro Texto |  | Obrigatório | O sistema recupera as informações de logradouro na tabela TOM com o CEP informado |
| Logradouro | Informação do nome Texto do logradouro. |  | Obrigatório | O sistema recupera as informações de logradouro na tabela TOM com o CEP |
| Número | Informação do Texto número do logradouro, |  | Obrigatório | Incluir o número do logradouro |
| Complemento | Informação do Texto complemento do logradouro. |  | Opcional | Incluir o complemento do logradouro |
| Endereço no Exterior-Somente permitido se a opção do Local da Prestação do Serviço não for “Brasil” |  |  |  |  |
| Bairro | Informação do bairro Texto no exterior. |  | Obrigatório | Informar o bairro no exterior do local da obra. |
| Logradouro | Informação do nome Texto do logradouro no exterior. |  | Obrigatório | Informar o logradouro no exterior do local da obra. |
| Número | Informação do Texto número do logradouro |  | Obrigatório | Informar o número no exterior do local da obra. |
| Complemento | Informação do Texto complemento do logradouro no exterior. |  | Opcional | Informar o complemento no exterior do local da obra. |
| Código de Informação Endereçamento Postal | do Número número do Código de endereçamento Postal no exterior. |  | Obrigatório | Informar o código de endereçamento fiscal no exterior do local da obra. |
| Estado, província ou Informação | do Texto |  | Obrigatório | Informar o no exterior |
| Cidade | Informação da Texto cidade no exterior. |  | Obrigatório | Informar a cidade no exterior do local da obra. |
| Inscrição Imobiliária Fiscal INFORMAÇÕES PARA ATIVIDADE DE EVENTO-Campos que serão exibidos quando o serviço prestado se refere a atividades de Eventos-Subitens do item 12 do Código de Tributação Nacional. | Código de inscrição Texto imobiliária fornecida pela Prefeitura |  | Obrigatório | Incluir a inscrição imobiliária fiscal ( código fornecido pela prefeitura para a identificação da obra ou para fins de recolhimento do IPTU). |
| Data Inicial | Data em que se Data iniciou a prestação do serviço |  | Obrigatório | Incluir a data de início do serviço |
| Data Final | Data em que se foi Data ou será concluída a prestação do serviço |  | Obrigatório | Incluir a data final do serviço |
| Descrição Seleção de uma das formas de informação sobre o serviço prestado: Identificador-Somente permitido se a opção do Local da Prestação do Serviço for “Brasil” | Descrição do serviço Texto prestado |  | Obrigatório | Incluir descrição do serviço |
| Endereço no Brasil-Somente permitido se a opção do Local da Prestação do Serviço for “Brasil” Obs.: O CEP a ser informado para endereço do imóvel deve pertencer ao município que foi informado como local da prestação do serviço. |  |  |  |  |
| CEP | Informação do Número número do CEP da obra. |  | Obrigatório | Incluir o CEP do local do evento. |
| Município | Informação do nome Texto do município ao qual pertence a obra no Brasil |  | Obrigatório | O sistema recupera as informações de logradouro na tabela TOM com o CEP informado |
| Bairro | Informação do bairro Texto |  | Obrigatório | O sistema recupera as informações de logradouro na tabela TOM com o CEP informado |
| Logradouro | Informação do nome Texto do logradouro. |  | Obrigatório | O sistema recupera as informações de logradouro na tabela TOM com o CEP |
| Número | Informação do Texto número do logradouro, |  | Obrigatório | Incluir o número do logradouro do local do evento. |
| Complemento | Informação do Texto complemento do logradouro. |  | Opcional | Incluir o complemento do logradouro do local do evento. |
| Endereço no Exterior-Somente permitido se a opção do Local da Prestação do Serviço não for “Brasil” |  |  |  |  |
| Bairro | Informação do bairro Texto no exterior. |  | Obrigatório | Informar o bairro no exterior do local do evento. |
| Logradouro | Informação do nome Texto do logradouro no exterior. |  | Obrigatório | Informar o logradouro no exterior do local do evento. |
| Número | Informação do Texto número do logradouro |  | Obrigatório | Informar o número no exterior do local do evento. |
| Complemento | Informação do Texto complemento do logradouro no exterior. |  | Opcional | Informar o complemento no exterior do local do evento. |
| Código de Informação Endereçamento Postal | do Número número do Código de endereçamento Postal no exterior. |  | Obrigatório | Informar o código de endereçamento fiscal no exterior do local do evento. |
| Estado, província ou Informação região | do Texto Estado, província ou |  | Obrigatório | Informar o no exterior Estado, província ou |
| Cidade INFORMAÇÕES PARA COMÉRCIO EXTERIOR – Quando o País da Prestação/Fornecimento do serviço é diferente de Brasil, algumas informações adicionais são requisitadas. Para a RFB, a prestação de serviço caracteriza-se como uma operação de comércio exterior quando existe ao menos um dos seguintes itens identificados/localizados no exterior: tomador/adquirente, intermediário ou local da prestação do serviço. Dessa forma, o emitente da DPS deverá prestar informações sobre esse tipo de transação, conforme apresentado a seguir: | Informação da Texto cidade no exterior. |  | Obrigatório | Informar a cidade no exterior do local do evento. |
| Modo de Prestação | Informação sobre o Combo modo de prestação do serviço, no âmbito do comércio exterior |  | Obrigatório | Selecionar uma das opções da lista disponibilizada: o Transfronteiriço o Consumo no Brasil o Movimento Temporário de Pessoas Físicas o Consumo no Exterior |
| Vínculo entre as partes no Informação sobre o Combo negócio | vínculo entre as partes no negócio |  | Obrigatório | Selecionar um dos vínculos do serviço prestado da lista disponibilizada. o Sem vínculo com o tomador/prestador o Controlada o Controladora o Coligada o Matriz o Outro vínculo o Desconhecido |
| Moeda | Identifica a moeda Código |  | Obrigatório | Informar o código da |
| Valor do Valor serviço/fornecimento em moeda estrangeira | do serviço Valor expresso na moeda estrangeira especificada |  | Obrigatório | Incluir o valor do serviço expresso na moeda estrangeira especificada |
| Mecanismo de Descreve apoio/fomento ao mecanismo Comércio Exterior utilizado apoio/fomento pelo prestador do Comércio serviço/fornecimento | o Combo de ao Exterior utilizado pelo prestador do serviço/fornecimento. |  | Obrigatório | Selecionar o mecanismo de apoio/fomento ao Comércio Exterior utilizado pelo prestador/fornecedor do serviço dentre as opções da lista, por exemplo: o Nenhum; o ACC – Adiantamento sobre Contrato de Câmbio – Redução a zero do IR e do IOF; o ACE – Adiantamento sobre Cambiais Entregues – Redução a Zero do IR e do IOF; o BNDES-Exim Pós- Embarque – Serviços; o BNDES-Exim Pré- Embarque – Serviços. o FGE – Fundo de Garantia à Exportação o PROEX – |
| Mecanismo de Mecanismo apoio/fomento ao apoio/fomento Comércio Exterior utilizado Comércio pelo tomador do utilizado serviço/fornecimento | de Combo ao Exterior pelo tomador do serviço/fornecimento |  | Obrigatório | Selecionar o mecanismo de apoio/fomento ao Comércio Exterior utilizado pelo tomador/adquirente do serviço/fornecimento dentre as opções da lista, por exemplo: o Nenhum; o Adm. Pública e Repr. Internacional; o Aluguéis e Arrend. Mercantil de máquinas, equip., embarc. E aeronaves; o Arrendamento mercantil de aeronave para empresa de transporte aéreo público; o Comissão a agentes externos na exportação. o Despesas de armazenagem, mov. e transporte de carga no exterior o Eventos FIFA (subsidiária) o Eventos FIFA |
| Existe vínculo da operação Informação se Existe Combo à movimentação vínculo da operação |  |  | Obrigatório | Selecionar a opção do vínculo dentre a lista |
| Se a opção acima for : “Vinculada – Declaração de Importação”, o contribuinte deve informar o número da declaração de Importação (DI/DSI/DA/DRI-E) averbado. |  |  |  |  |
| DI/DSI/DA/DRI-E averbado Se a opção acima for : “Vinculada – Declaração de Exportação”, o contribuinte deve informar o Registro de Exportação (RE) averbado. | Número da Texto Declaração de Importação (DI/DSI/DA/DRI-E) averbado |  | Obrigatório | Incluir Número da Declaração de Importação (DI/DSI/DA/DRI-E) averbado |
| RE averbado | Número do Registro Texto de Exportação (RE) averbado |  | Obrigatório | Incluir o Número do Registro de Exportação (RE) averbado |
| Deseja compartilhar a Informação sobre o Combo NFS-e que será gerada compartilhamento da desta DPS com o MDIC? TIPO DE OPERAÇÃO-Esse campo é exigido para os casos de compras governamentais ou algum dos serviços dispostos no art. 254 da LC 214/2025 ( subitens 25.05, 15.09, 17.12 e 10.05, da lista de serviços ). | NFS-e que será gerada a partir desta DPS com o MDIC. |  | Obrigatório | Selecionar uma das opções: o Sim o Não |
| Tipo de Operação | Informação sobre Combo Tipo de operação |  | Obrigatório | Selecionar uma das opções da lista: |
| Chave da NFS-e | Informação da Chave Valor de acesso da NFS-e com fornecimento ou recebimento do pagamento realizado (Opções 2 ou 3 do Tipo de Operação) |  | Obrigatório | Informar a chave de acesso da NFS-e que acobertou o fornecimento ou recebimento do pagamento realizado. Deve-se informar uma chave de acesso válida, com 50 caracteres alfanuméricos. Caso seja informada uma chave de acesso inválida, o sistema exibirá a seguinte mensagem |
| Número do documento Informação sobre o Texto de responsabilidade número técnica | do documento de responsabilidade técnica |  | Opcional | Incluir o número do documento de responsabilidade técnica. |
| Documento de Informação referência | sobre Texto documentos referenciados |  | Opcional | Incluir dados sobre o documento de responsabilidade técnica. |
| Informações Complementares | Descrição de Texto informações complementares |  | Opcional | Incluir informações complementares sobre o serviço. |
| Número do Pedido, Informação Ordem de Compra, número Ordem de Serviço ou ordem Projeto que autorize a etc. prestação do serviço/fornecimento em operações B2B | sobre Texto do pedido, de compra |  | Opcional | Incluir as referidas informações. Será habilitado o botão para identificação de itens a serem incluídos no pedido, ordem de compra etc. |
| Incluir Item | Identificação de itens Texto a serem incluídos no Pedido, Ordem de Compra, Ordem de Serviço ou Projeto |  | Obrigatório | Incluir os itens que compõem as referidas informações de pedidos, ordem de compra etc. |

***

que autorize a prestação do serviço/ fornecimento em operações B2B.

##### Após o preenchimento dos campos da etapa “Serviço”, clicar em.

### 4.1.3. PASSO 3 – Valores

Tela para preenchimento dos valores do(s) serviço(s) que originou a emissão da NFSe.

A obrigatoriedade dos campos dessa tela, bem como as regras de negócio aplicadas para cada um deles, estão na planilha de leiaute DPS e RN DPS descrita abaixo.

Preencher os campos conforme as regras descritas na tabela abaixo e clicar em “Avançar” .A obrigatoriedade dos campos da tela do passo, bem como as regras de negócio aplicadas para cada um deles, são dadas para cada campo das planilhas de leiaute DPS e RN DPS (ANEXO\_I- SEFIN\_ADN-DPS\_NFSe-SNNFSe) que corresponda ao campo na tela.

Alguns campos podem não estar editáveis e serão preenchidos de acordo com as configurações, do contribuinte e/ou serviço, do município em que o contribuinte está autorizado a emitir notas, bem como os indicadores municipais previamente cadastrados no Painel Municipal.

| Campo | Descrição | Tipo | Ocorrência | Ação |
| --- | --- | --- | --- | --- |
| VALORES DO FORNECIMENTO/SERVIÇO PRESTADO |  |  |  |  |
| Valor da Informação sobre o Valor operação/serviço prestado | valor total cobrado pelo serviço prestado |  | Obrigatório | Incluir o valor total da operação/serviço |
| Valor recebido pelo Informação sobre o Valor intermediário | valor pago ao intermediário, caso informado na etapa 1 (pessoas) |  | Opcional | Incluir valor recebido pelo intermediário (se houver). |
| Desconto | Informação sobre o Valor valor do desconto |  | Opcional | Incluir o valor do desconto |

|  |  | incondicionado | incondicionado (desconto oferecido sem que haja necessidade de que alguma condição seja cumprida para obtenção do desconto. Ele é concedido por vontade do prestador/ fornecedor sem qualquer imposição e reduzirá o valor do serviço prestado). | incondicionado. |
| --- | --- | --- | --- | --- |
| Desconto condicionado TRIBUTAÇÃO MUNICIPAL Alguns campos já estarão preenchidos a depender das opções feitas em etapas anteriores. Por exemplo: não é permitido ao emitente informar alguns campos se se tratar de uma situação de exportação de serviço, conforme a planilha "COMEX-EXPORTAÇÃO DE SERVIÇO". Da mesma forma, para os optantes pelo Simples Nacional, com apuração dos tributos pelo regime do Simples Nacional, alguns campos não serão habilitados para preenchimento e a seguinte mensagem será exibida: | Informação sobre o Valor valor do desconto condicionado (desconto oferecido desde que alguma condição seja cumprida para obtenção do desconto. Este depende de alguma condição posterior à emissão da NFS-e. A base de cálculo não é reduzida por este desconto). |  | Opcional | Incluir o valor do desconto condicionado. |

| • • • • | Operação tributável; Imunidade; Exportação de serviço; Não Incidência; | Tributação do ISSQN sobre o serviço prestado | Este campo será pré-preenchido dependendo das opções feitas nas telas anteriores: |  |  |  |  |
| --- | --- | --- | --- | --- | --- | --- | --- |
| Tributação sobre prestado | do o |  | ISSQN Informação de que a serviço operação é tributável. | Combo | Obrigatório | Preenchimento automático do tipo de exigibilidade do ISSQN seguindo conforme de campos das anteriores. | regras preenchimento telas |
| Regime Tributação | Especial | Suspensão da exigibilidade do ISSQN – | de Relaciona Regimes Especiais de Tributação definidos pelo Município do emitente. Somente disponível quando a operação for tributável. Se a opção for “Nenhum”, alguns campos serão disponibilizados para preenchimento; automaticamente. | os Combo A depender das opções das telas anteriores, alguns campos serão preenchidos | Obrigatório campos de informação sobre a exigibilidade do imposto. | Selecionar umas opções, por exemplo o Nenhum; o Ato cooperado; | das : |

| Se a operação for tributável, alguns campos deverão ser preenchidos. |  |  |  |  |
| --- | --- | --- | --- | --- |
| A exigibilidade do Informação sobre a recolhimento do ISSQN exigibilidade devido nesta operação recolhimento está suspensa? | do do ISSQN devido nesta operação está suspensa? | Combo | Obrigatório | Exibe os campos relativos à suspensão da exigibilidade: o Não o Sim |
| Tipo de suspensão | Informação sobre o Combo tipo de suspensão: Judicial ou Administrativa |  | Obrigatório | Selecionar o tipo da suspensão: o Exigibilidade suspensa por decisão judicial o Exigibilidade suspensa por processo administrativo. |
| Número do processo | Informação do Valor número da suspensão da exigibilidade |  | Obrigatório | Informar o número do processo da suspensão |
| Retenção do ISSQN – Informação se haverá ou não retenção de ISSQN. | Nos casos de exigibilidade suspensa não é possível informar retenção do ISSQN, o sistema exibirá uma mensagem de alerta. |  |  |  |
|  | Informação de que Combo “Sim” haverá retenção de ISSQN. |  | Obrigatório | Marcar “Sim”. Neste caso será aberto o campo “por quem o ISSQN será retido” |
| Opções de retenção do Informação ISSQN | se o Botão ISSQN: o Retido pelo Tomador o Retido pelo Intermediário | Radio | Obrigatório | Informar o tipo da retenção do ISSQN. a) Se a opção for “Retido pelo Tomador”, deve ter sido informado o tomador/adquirente de serviço na primeira tela (Pessoas). Se não foi informado, será mostrada a mensagem: Pode ser usado o botão “+Incluir Tomador”; b) Se a opção for “Retido pelo Intermediário”, deve ter sido informado o intermediário do serviço na primeira tela (Pessoas). Se não foi informado, será mostrada a mensagem : |
| ou | Incluir/Editar Tomador | Botão | Obrigatório | Informar os dados do tomador/adquirente, caso não tenham sido informados no passo pessoas; ou editá-los, caso tenham sido informados. |
| ou | Incluir/Editar Intermediário | Botão | Obrigatório | Informar os dados do intermediário, caso não tenham sido informados no passo pessoas; ou editá-los, caso tenham sido informados. |
| Para o prestador de serviço ME/EPP com apuração do ISSQN pelo Simples Nacional, é obrigatório informar alíquota do ISSQN quando há indicação de retenção do imposto. | Informação do valor Valor da alíquota de ISSQN para o prestador/ fornecedor optante pelo Simples Nacional |  | Obrigatório | Informe o valor da alíquota do ISSQN aplicável. |
| Benefício Municipal-Exibe os campos relativos ao benefício municipal | Informação se há Opção amparo de benefício Sim/ Não municipal na tributação. |  | Obrigatório | Informar: o “Não”: neste caso nenhum campo a mais será mostrado. |
|  | Informação de “Sim” Opção para amparo de Sim/ Não benefício municipal na tributação. Informação sobre a Combo identificação do BM |  | Obrigatório Obrigatório | Informar “Sim” para benefício fiscal. Alguns campos obrigatórios deverão ser preenchidos. Selecionar uma das opções configuradas pelo município concedente. Caso o BM cadastrado para o serviço/contribuinte seja do tipo “Redução de Base de Cálculo”, o emitente deverá informar o valor (percentual ou monetário) da redução. |
| Valor/Percentual para Informação sobre o Valor redução da BC | valor percentual/ monetário da redução do BM na BC |  | Obrigatório | Informar o percentual/ valor da redução da BC. |
| Informações para dedução/redução da base de cálculo do ISSQN? – Exibe os campos relativos a deduções/reduções da base de cálculo. |  |  |  |  |
| Caso seja selecionado a opção “Sim”, deve-se selecionar o tipo de dedução/redução: |  |  |  |  |
| Valor monetário | Informação sobre o Valor valor de Redução da BC O valor deve ser menor que o valor do serviço prestado. |  | Obrigatório | Informar o valor monetário a ser utilizado como redução da BC. |
| Valor percentual Caso o tipo de dedução/redução seja do tipo “Documentos”, selecionar para vincular os documentos: Em seguida, o painel exibirá a tela para inclusão das informações referentes ao documento vinculado à dedução. | O valor da Valor dedução/redução será calculado pelo percentual informado |  | Obrigatório | Informar o valor percentual a ser utilizado como redução da BC. |
|  | Informação do tipo Combo de documento a ser incluído |  | Obrigatório | Selecionar uma das opções da lista: o NF-e o NFS-e o Outras NFS-e o NF/NFS o Outros Documentos Fiscais o Outros Documentos Obs.: A depender do tipo de documento informado, o painel exibirá campos específicos com as informações a serem preenchidas. Abaixo, trataremos dos tipos NF-e e NFS-e. |
| Chave de Acesso da NF- e ou Chave de acesso chave de acesso da da NFS-e | Informação sobre a Valor NF-e ou NFS-e a ser vinculada à dedução |  | Obrigatório | Informar o número da chave de acesso do documento: Se NF-e deve conter 44 caracteres alfanuméricos; Se NFS-e deve conter 50 |
|  | Informação sobre a Combo identificação da dedução |  | Obrigatório | Selecionar uma das opções disponíveis, por exemplo : Materiais |
| Data da emissão | Informação sobre a Data data de emissão do documento |  | Obrigatório | Informar a data de emissão do documento |
| Valor dedutível | Informação sobre Valor valor dedutível |  | Obrigatório | Informar a parte do valor do documento apto a ser utilizado como dedução |
| Valor da dedução | Informação sobre o Valor valor da dedução |  | Obrigatório | Informar a parte do valor identificado como apto a reduzir a base de cálculo da NFS-e em emissão |
| Identificar fornecedor | Informação sobre o Checkbox fornecedor na operação do documento informado |  | Opcional | Informar os dados do fornecedor na operação do documento informado. Selecionar entre as opções: o Brasil o Exterior Preencher os campos exibidos, conforme obrigatoriedade e seguindo as orientações já indicadas no passo |
|  | Salvo exceções previstas na legislação, o ISSQN não pode ser objeto de redução de base de cálculo que resulte em carga tributária menor que a decorrente da aplicação da alíquota mínima de 2,0% do valor do serviço. O sistema exibirá a seguinte mensagem de alerta, para que, se for o caso, sejam feitas as correções devidas: |  |  |  |
| Após a inclusão, os documentos serão listados na tela, podendo-se editá-los ou excluí-los: | Opção de edição do Opção documento informado para dedução Opção de exclusão Opção do documento informado para dedução |  | Opcional Opcional | Selecionar “Editar dedução” e editar os campos necessários Selecionar “Excluir dedução” e confirmar a mensagem do sistema: |
| Alíquota | Informação do Valor percentual do imposto efetivo aplicado ao valor da base de cálculo |  | Conforme Regra de percentual Negócio | Recuperado do valor informado na tela “Serviço” para o cálculo do ISSQN |
| BC ISSQN | Informação sobre a Valor base de cálculo do ISSQN. |  | Conforme Regra de do serviço reduzido o valor Negócio | Recuperado do valor total total das deduções e do valor do desconto incondicionado |
| TRIBUTAÇÃO FEDERAL PIS/COFINS – Os campos a serem preenchidos dependem da opção da “Situação Tributária do PIS/COFINS” selecionada. | Quando o emitente tem os tributos relacionados apurados pelo Simples Nacional, as informações de Tributação Federal já estarão preenchidas e não podem ser alteradas. |  |  |  |
| Situação Tributária do Informação sobre s Combo PIS/COFINS | tipo de situação tributária do PIS/COFINS |  | Opcional | Selecionar dentre os tipos de situação tributária da lista disponibilizada, por exemplo: o 00 – Nenhum o 01 – Operação Tributável com Alíquota Básica |
| BC PIS/COFINS | Total do valor da Valor base de cálculo para PIS/COFINS |  | Conforme Regra de Negócio | Incluir o valor informado |
| PIS-Alíquota | Valor do percentual a Valor ser aplicado | percentual | Conforme Regra de informado Negócio | Incluir percentual |
| COFINS-Alíquota | Valor do percentual a Valor ser aplicado | percentual | Conforme Regra de informado Negócio | Incluir percentual |
| COFINS - Débito Valor Apuração Própria | calculado do Valor imposto pela alíquota informada |  | Conforme Regra de automaticamente, Negócio | Calculado conforme BC e Alíquota informadas. |
| Tipo retenção do PIS/COFINS/CSLL | Informação sobre o Combo tipo de retenção de PIS/COFINS e CSLL |  | Obrigatório | Informar o tipo de retenção de PIS/COFINS e CSLL, conforme lista disponibilizada, por exemplo : o 0 – PIS/COFINS/CSLL Não Retidos o 3 – PIS/COFINS/CSLL Retidos |
| Outros Tributos Federais |  |  |  |  |
| IRRF | Valor do IRRF retido, Valor conforme legislação própria |  | Opcional | Incluir o valor do IRRF, se aplicável |
| Contribuições Sociais - Retidas | Valor de PIS/COFINS Valor e CSLL retidos, conforme legislação própria |  | Opcional | Incluir o valor de PIS/COFINS e CSLL retidos, se aplicável |
| Contribuição Previdenciária-Retida | Valor da Contribuição Previdenciária Patronal retida, conforme legislação própria | Valor | Opcional | Incluir o valor da Contribuição Previdenciária Patronal, se aplicável |
| Código de Situação Informação sobre o Combo Tributária – CST | código de situação tributária aplicável ao serviço/ fornecimento |  | Obrigatório | Selecionar o CST aplicável ao serviço/fornecimento |
| Código de Classificação Informação sobre o Combo Tributária – cClassTrib | código de classificação aplicável ao serviço/fornecimento |  | Obrigatório | Selecionar o cClassTrib aplicável ao serviço/fornecimento. Obs.: a lista de cClassTrib exibida dependerá do CST selecionado previamente |
| Nesse grupo também poderão ser incluídos os documentos referenciados nos casos de reembolso, repasse e ressarcimento, a serem considerados na apuração da base de cálculo do IBS/CBS. Para incluir o documento, selecione. |  |  |  |  |
| Em seguida, o painel exibirá a tela para inclusão das informações referentes ao documento vinculado ao reembolso, repasse ou ressarcimento. |  |  |  |  |
|  | Informação do tipo Combo de documento a ser incluído |  | Obrigatório | Selecionar uma das opções da lista: o NF-e o NFS-e o CT-e o Outro Obs.: A depender do tipo de documento informado, o painel exibirá campos específicos com as informações a serem preenchidas. Abaixo, trataremos dos tipos NF-e e NFS-e. |
| Chave de Acesso da Informação sobre a Valor NFS-e ou Chave de chave de acesso da acesso da NF-e | NF-e ou NFS-e a ser vinculada ao reembolso, repasse ou ressarcimento |  | Obrigatório | Informar o número da chave de acesso do documento: Se NF-e deve conter 44 caracteres alfanuméricos; Se NFS-e deve conter 50 caracteres alfanuméricos; O sistema fará a busca do respectivo documento na |
|  | Informação sobre a Combo identificação do reembolso, repasse ou ressarcimento |  | Obrigatório | Selecionar uma das opções disponíveis, por exemplo : Repasse de remuneração por intermediação... |
| Data de Emissão | Informação sobre a Data data de emissão do documento |  | Obrigatório | Informar a data de emissão do documento |
| Data de Competência | Informação sobre a Data data de competência do documento |  | Obrigatório | Informar a data de competência do documento |
| Valor Reemb./ Repasse / Ressarc. | Informação sobre o Valor valor do reembolso, repasse ou ressarcimento |  | Obrigatório | Informar o valor do documento objeto de reembolso, repasse ou ressarcimento e que serão considerados na base de cálculo do IBS/CBS |
| Identificar fornecedor | Informação sobre o Checkbox fornecedor na operação do documento informado |  | Opcional | Informar os dados do fornecedor na operação do documento informado. Selecionar entre as opções: o Brasil o Exterior Preencher os campos exibidos, conforme obrigatoriedade e seguindo as orientações já indicadas no passo |
| Após a inclusão, os documentos serão listados na tela, podendo-se editá-los ou excluí-los: | Opção de edição do Opção documento informado para reembolso, repasse ou ressarcimento Opção de exclusão Opção do documento informado para reembolso, repasse ou ressarcimento |  | Opcional Opcional | Selecionar “Editar” e editar os campos necessários Selecionar “Excluir” e confirmar a mensagem do sistema: |
| VALOR APROXIMADO DOS TRIBUTOS As informações dos valores aproximados dos tributos podem ser pré-configuradas, agilizando a emissão da NFS-e. Para isto, acesse a opção "Configurações" no menu principal. Deve-se selecionar uma das opções e preencher os campos exibidos. A opção “Informar alíquota do Simples Nacional” será exibida somente para os optantes desse regime. Nesse caso, a ME/EPP informará a alíquota nominal a qual está sujeita nos termos do referido regime, conforme o art. 9º, do Decreto 8.264/2014, que regulamenta a Lei 12.741/2012. |  |  |  |  |
| Valores monetários totais para os tributos Federal, Estadual e Municipal |  |  |  |  |
| Federal | Informação sobre o Valor valor monetário total para os tributos Federais da NFS-e em relação ao valor |  | Obrigatório | Preencher com o valor monetário total para os tributos Federais da NFS-e em relação ao valor do serviço. |
| Estadual | Informação sobre o Valor valor monetário total para os tributos Estaduais da NFS-e em relação ao valor do serviço. |  | Obrigatório | Preencher com o valor monetário total para os tributos estaduais da NFS- e em relação ao valor do serviço. |
| Municipal | Informação sobre o Valor valor monetário total para o imposto municipal da NFS-e em relação ao valor do serviço. |  | Obrigatório | Preencher com o valor monetário total para o imposto municipal da NFS- e em relação ao valor do serviço. |
| Valores percentuais totais para os tributos Federal, Estadual e Municipal |  |  |  |  |
| Federal | Informação dos Valor valores percentuais totais para os tributos Federais. |  | Obrigatório | Informar o valor percentual total de impostos federais da NFS- e em relação ao valor total do serviço. |
| Estadual | Informação dos Valor valores percentuais totais para os tributos estaduais. |  | Obrigatório | Informar o valor percentual total de impostos estaduais da NFS-e em relação ao valor total do serviço. |
| Municipal | Informação dos Valor valores percentuais totais para o imposto municipal. |  | Obrigatório | Informar o valor percentual total do imposto municipal da NFS- e em relação ao valor total do serviço. |
| Valor da alíquota para o regime do Simples Nacional |  |  |  |  |

|  | Alíquota Nacional | no |  | Simples Informação da Valor alíquota nominal do Simples Nacional | Obrigatório Informar alíquota ao emitente | o Simples Nacional aplicável | valor nominal | da do |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
|  | 4.1.4. SNNFSe 4.1.4.1. | nos passos anteriores. | Editar Pessoas necessárias e clicar em “Avançar”. | Após o preenchimento dos campos da etapa “Valores”, clicar em PASSO 4 – Emitir NFS-e prestadas antes de solicitar a emissão da NFS-e. Nacional NFS-e. Este serviço valida a DPS conforme regras do e retorna o XML da NFS-e gerada. O usuário deve conferir os dados das pessoas envolvidas na emissão do documento. | Será apresentado um resumo dos itens informados em cada um dos passos de preenchimento da NFS-e. Neste momento o usuário tem a possibilidade de retornar e alterar as informações Neste passo é importante que se revise a Declaração de Prestação de Serviço (DPS) e confira o cálculo prévio dos tributos devidos. Se necessário, é possível a alteração das informações prestadas O acionamento do comando “Emitir NFS-e” da tela envia o XML da DPS que foi formado pelo preenchimento das informações nos passos anteriores para o Serviço de Geração de NFS-e da Sefin alguma informação não esteja correta, deve clicar em “Editar Pessoas”, fazer as alterações | ANEXO\\_I-SEFIN\\_ADN-DPS\\_NFSe- | . | Caso |

***

4.1.4.2. Editar Serviço O usuário deve conferir os dados do serviço informado na emissão do documento. Caso alguma informação não esteja correta, deve clicar em “Editar Serviço”, fazer as alterações necessárias e clicar em “Avançar”.
4.1.4.3. Editar Valores Os valores serão recuperados conforme os dados preenchidos nas etapas anteriores; e o usuário deve conferir os valores retornados para a emissão do documento. Caso alguma informação não esteja correta, deve clicar em “Editar Tributação”, fazer as alterações necessárias e clicar em “Avançar”.

***

Para conferência, é exibida a tela dos valores da NFS-e:

### 4.1.5. NFS-e Gerada com sucesso

Após conferir todos os dados informados, o usuário deve clicar em. Será apresentado um resumo da NFS-e gerada, incluindo a chave de acesso à mesma.

***

Após a geração da NFS-e, o Emissor Web permite as seguintes ações:

- Baixar o XML da NFS-e;
- Baixar o DANFSe (Documento Auxiliar de Nota Fiscal de Serviço eletrônica-representação gráfica da NFS-e com informações resumidas da nota) em formato PDF;
- Visualizar NFS-e (visualização no navegador do emitente em formato web);
- NFS-e emitidas (direciona para a funcionalidade que exibe as NFS-e emitidas pelo usuário);
- Nova NFS-e (recomeça o preenchimento da DPS para a geração de uma nova NFS-e);

## 4.2. Emissão de NFS-e Simplificada (Contribuintes MEI)

Os contribuintes enquadrados como MEI podem emitir uma versão simplificada da NFS-e, ou seja, o preenchimento de um grupo menor de informações é suficiente para emitir a NFS-e.

Ao final do preenchimento o Emissor Público Nacional NFS-e emite uma NFS-e com o mesmo leiaute especificado no <u></u>ANEXO\_I-SEFIN\_ADN-DPS\_NFSe-SNNFSe. A maioria dos campos necessários e obrigatórios pelo leiaute já foram obtidos através do cadastro do contribuinte.

A obrigatoriedade dos campos, bem como as regras de negócio aplicadas para cada um deles, são dispostas nas planilhas de leiaute DPS e RN DPS (<u></u>ANEXO\_I-SEFIN\_ADN-DPS\_NFSe-SNNFSe).

***

### 4.2.1. Configuração do sistema para emissão da NFS-e simplificada.

Para a emissão da NFSs-e simplificada, é necessário realizar algumas configurações no sistema. Acesse as opções "Configurações" e "Serviços Favoritos" no menu superior, preencha as informações de "Valor aproximado dos Tributos" e crie os serviços favoritos, respectivamente.

Se o usuário acessar a “Emissão Simplificada” e não tiver feito as configurações previamente, o sistema exibirá a seguinte tela:

### 4.2.2. Configurações no menu principal:

##### O sistema exibirá a seguinte tela:

Informar o e-mail e telefone e marcar a opção para os cálculos/exibição dos valores totais dos tributos Federais, Estaduais e Municipais.

Clicar em e voltar para a emissão simplificada.

***

### 4.2.3. Criando serviços favoritos

O sistema exibirá a tela abaixo para inclusão de.

##### Preencher os campos:

1. Apelido (termo que ajudará o emissor, posteriormente, a identificar o serviço pré- configurado dentro do Sistema Nacional da NFS-e. Deve ter no máximo 50 caracteres).
2. Código de Tributação Nacional – selecionar dentre os códigos da lista;
3. Item da NBS correspondente ao serviço prestado. A Nomenclatura Brasileira de Serviços Intangíveis e Outras Operações que Produzam Variações no Patrimônio (NBS) é o classificador nacional para a identificação dos serviços e viabiliza adequadamente a elaboração fiscalização e avaliação de políticas públicas de forma integrada. Selecionar dentre os valores cadastrados.
4. Descrição do serviço.

   Após o preenchimento de todos os campos, clicar em.

| ainda, cadastrar um novo serviço favorito. | O serviço cadastrado será exibido na tela inicial, onde será possível editá-lo ou excluí-lo; ou, |
| --- | --- |
| 4.2.4. Emitir NFS-e Emissão Simplificada da NFS-e. | Após as configurações feitas acima, para os serviços cadastrados como “favoritos”, é possível a |
| Identificador do cliente (o tomador/adquirente | Será apresentado um formulário simplificado para preenchimento de três informações: do serviço), Serviço prestado (o Código de Tributação Nacional do serviço, mas que será apresentado na lista conforme apelido dos “Serviços Favoritos” previamente cadastrados) e Valor do serviço prestado. |
| emissão simplificada para contribuintes MEI. Caso o serviço prestado seja uma | Para quase todos os serviços prestados, estes campos serão suficientes para a obra ou atividade de evento, o sistema solicitará informações adicionais de preenchimento que identificarão a obra ou seu local de execução para obras; e datas inicial/final e o identificador ou seu local de execução no caso de atividade de evento. |

| Campo | Descrição | Tipo | Ocorrênci a | Ação |
| --- | --- | --- | --- | --- |
| CPF/CNPJ do Cliente | Informação sobre o Numérico CPF/CNPJ do tomador/adquirente |  | Obrigatório | Incluir o CPF/CNPJ do tomador/adquirente. Pode-se recuperar os dados dos últimos tomadores/ adquirentes clicando em |
| Nome/Razão Social do Informação sobre o Texto Cliente | Nome/Razão Social do Cliente |  | Obrigatório | Preenchimento automático pelo sistema com busca no cadastro CPF/CNPJ |
| Serviço Prestado | Informação sobre o Combo serviço prestado. A lista exibe os serviços favoritos previamente cadastrados. Obs: caso deseje-se adicionar algum serviço à lista, basta cadastrar “novo serviço” na funcionalidade de “Serviços Favoritos”, item 4.2.3. acima. |  | Obrigatório | Selecionar um serviço prestado da lista |
| Descrição do Serviço | Campo acionado se Texto clicar no editar (canto direito) do campo “Serviço Prestado”. O texto informado neste campo será utilizado somente na emissão da NFS-e |  | Opcional | Editar ou complementar o texto salvo na descrição do serviço quando cadastrado como “Serviço Favorito” |
| Valor do serviço Informação sobre o Valor prestado | valor total cobrado pelo serviço prestado, em moeda nacional. |  | Obrigatório | Incluir valor total do serviço, em reais. |
| Serviço – Informações para Obra (Subitens 7.02 e 7.05 ) Quando a emissão da NFS-e refere-se a serviço de obra, alguns campos adicionais devem ser informados. O primeiro deles é marcar a opção se vai ser informado o Código de obra ou Endereço no Brasil. Conforme a opção marcada, o sistema exibirá campos diferentes a serem preenchidos. |  |  |  |  |
| Opção: Código de Obra |  |  |  |  |
| Código da Obra | Informação sobre o Texto número de identificação da obra |  | Obrigatório | Incluir o código da obra |
| Opção: Endereço no Brasil |  |  |  |  |
| CEP | Informação do Numérico Código de endereçamento postal no Brasil |  | Obrigatório | Informar o CEP do local da obra. |
| Município | Informação do nome Texto do município ao qual pertence a obra no Brasil |  | Obrigatório | Recupera as informações de Município na tabela TOM com o CEP informado |
| Bairro | Informação do nome Texto do bairro |  | Obrigatório | Recupera as informações de logradouro na tabela TOM com o CEP informado |
| Logradouro | Informação do nome Texto do logradouro |  | Obrigatório | Recupera as informações de logradouro na tabela TOM com o CEP informado |
| Número | Informação do Texto número do logradouro |  | Obrigatório | Incluir o número do logradouro |
| Serviço – Informações para Atividades de Eventos (subitens do item 12 - Serviços de diversões, lazer, entretenimento e congêneres) Quando o serviço favorito cadastrado é um serviço constante dos subitens do item 12 do código de tributação nacional, serão exibidos alguns campos sobre o endereço do evento. |  |  |  |  |
| Data Inicial | Data em que se Data iniciou a prestação do serviço |  | Obrigatório | Incluir a data de início do serviço |
| Data Final Opção: Identificador | Data em que se foi Data ou será concluída a prestação do serviço |  | Obrigatório | Incluir a data final do serviço |
| Identificador do Evento | Código identificador Código da atividade do evento determinado pela Administração Tributária |  | Opcional | Incluir código de identificação da atividade do evento |
| Município onde o Informação do nome serviço foi prestado | município no qual a atividade de evento | Texto | Obrigatório | Selecionar um dos municípios disponibilizados na |

***

|  |  |  | ocorreu | lista |
| --- | --- | --- | --- | --- |
| Opção: Endereço no Brasil |  |  |  |  |
| CEP | Informação do Numérico Código de endereçamento postal no Brasil |  | Obrigatório | Informar o CEP do local do evento |
| Município | Informação do nome Texto do município ao qual pertence a obra no Brasil |  | Obrigatório | Recupera as informações de Município na tabela TOM com o CEP informado |
| Bairro | Informação do nome Texto do bairro |  | Obrigatório | Recupera as informações de logradouro na tabela TOM com o CEP informado |
| Logradouro | Informação do nome Texto do logradouro |  | Obrigatório | Recupera as informações de logradouro na tabela TOM com o CEP informado |
| Número | Informação do Texto número do logradouro |  | Obrigatório | Incluir o número do logradouro |
| Complemento | Informação do Texto complemento do logradouro |  | Opcional | Incluir o complemento do logradouro |

Após o preenchimento dos dados obrigatórios, conforme o serviço prestado, deve-se clicar no ícone.

***

### 4.2.5. NFS-e Gerada com sucesso

Será apresentado um resumo da NFS-e gerada, incluindo a chave de acesso à mesma.

Após a NFS-e gerada com sucesso o Emissor Web permite ações:

- Baixar o XML da NFS-e;
- Baixar o DANFSe (Documento Auxiliar de Nota Fiscal de Serviço eletrônica-representação gráfica da NFS-e com informações resumidas da nota) em formato PDF;
- Visualizar NFS-e (visualização no navegador do emitente em formato web);
- NFS-e emitidas (direciona para a funcionalidade que exibe as NFS-e emitidas pelo usuário);
- Nova NFS-e (recomeça o preenchimento da DPS para a geração de uma nova NFS-e);

## 4.3. Emissão por decisão administrativa ou judicial

A emissão de notas pelo fluxo de decisão administrativa ou judicial possibilita a geração dos documentos fiscais sem a necessidade de validação completa das regras de negócio aplicáveis ao processo padrão de emissão, conforme estabelecido no <u></u>ANEXO\_I-SEFIN\_ADN-DPS\_NFSe-SNNFSe, na planilha RN\_DPS\_NFS-e, coluna “L”.

Essa funcionalidade tem como objetivo atender às determinações estabelecidas por tais decisões, sendo importante ressaltar que o contribuinte deve estar devidamente vinculado a uma decisão reconhecida pelo município.

### 4.3.1. Inclusão de NFS-e por decisão administrativa/judicial

Para inclusão de uma nota referente a decisão administrativa ou judicial, deve-se clicar na opção “Emissão por decisão administrativa/judicial” existente no menu principal:

***

Caso o contribuinte não esteja vinculado a nenhuma decisão administrativa/judicial em nenhum município conveniado, será apresentada a tela a seguir:

Caso contrário, se o usuário estiver vinculado a qualquer decisão administrativa/judicial, será exibida a tela abaixo para inclusão das informações:

A prestação das informações segue o mesmo fluxo de uma emissão normal, com algumas particularidades:

##### No passo “Pessoas”:

- Informar a data de competência, observando o início de vigência do cadastro da decisão administrativa/judicial pelo município de emissão que autorizou o usuário a emitir uma NFS-e por decisão administrativa/judicial;

***

- Informar os demais campos da tela, atentando para a possibilidade de informar o mesmo CPF/CNPJ do emitente como tomador/adquirente, destinatário e intermediário do serviço.

##### Para o passo “Serviço”:

- Informar os campos obrigatórios na tela, atentando para a seleção do município de incidência, que neste caso não é recuperado automaticamente pelo sistema;
- Com relação ao “Código de Tributação Nacional“, serão exibidos apenas os serviços cadastrados pelo município de emissão como parte de uma decisão administrativa/judicial favorável ao emitente desta NFS-e.
- O preenchimento dos grupos obra/imóvel, atividade de evento e comércio exterior é opcional.

##### Finalmente, no passo “Valores”:

- É necessário informar todos os campos de valores, entre os quais: alíquota, base de cálculo e valor do ISSQN, tendo em vista que os cálculos automáticos não são realizados;
- Também é necessário informar os valores da NFS-e, p. ex. “Valor Líquido da NFS-e”, que no fluxo normal são calculados automaticamente.

  Após o preenchimento das informações nos passos anteriores, basta clicar em “Emitir NFS-e” para finalizar o processo.

### 4.3.2. Consulta de NFS-e emitida por decisão administrativa / judicial

Para identificar as NFS-e emitidas pelo fluxo de decisão administrativa/judicial, basta acessar a opção “NFS-e Emitidas” no menu principal e localizar as notas emitidas que possuem a identificação abaixo (ícone com detalhe azul -):

### 4.3.3. Cancelar NFS-e emitidas por decisão administrativa / judicial

Para cancelar uma nota emitida pelo fluxo de decisão administrativa/judicial, deve-se acessar a opção “NFS-e Emitidas” no menu principal e identificar a nota que deseja cancelar.

No menu lateral da nota desejada, selecionar a opção “Cancelar NFS-e” conforme a tela abaixo:

***

Preencher as informações da janela aberta e clicar em “Cancelar NFS-e”. A nota cancelada terá seu status alterado conforme a tela abaixo:

### 4.3.4. Substituir NFS-e emitida por decisão administrativa / judicial

Para substituir uma nota emitida por decisão administrativa/judicial, deve-se acessar a opção “NFS-e Emitidas” no menu principal e identificar a nota que deseja substituir.

No menu lateral, selecionar a opção “Substituir”:

O sistema verificará se a nota selecionada pode ser substituída por outra emitida tanto pelo fluxo padrão quanto pelo fluxo de decisão administrativa ou judicial. Caso essa substituição seja possível, será exibida uma janela para que o emitente escolha qual fluxo deseja utilizar:

***

Após selecionar o fluxo desejado e clicar em “Confirmar”, o sistema irá direcionar para o fluxo de emissão por substituição.

# 5. Consultas NFS-e Emitidas

Ao clicar no ícone “NFS-e emitidas”, o usuário consulta as NFS-e emitidas. Na tela serão exibidos alguns campos de cada uma das NFS-e (Emissão, Emitida para, Competência, Município Emissor, Preço Serviço (R$), Situação):

A partir da listagem das NFS-e emitidas o emitente pode filtrar as notas conforme filtros descritos no campo de pesquisa da aplicação. O sistema deve totalizar o quantitativo de registros que atendem aos critérios de buscas (Total NFS-e emitidas) e exibir na tela o campo totalizador das NFS-e emitidas, desde o início de utilização do sistema até a data presente de visualização da consulta pelo emitente. Para atender aos requisitos de performance e usabilidade do sistema o resultado da consulta é retornado por páginas na tela.

Outras ações podem ser realizadas para cada uma das notas listadas, acessando em cada uma das linhas:

***

- Visualizar NFS-e (visualização no navegador do emitente em formato web);
- Substituir NFS-e (recarrega as informações da DPS da NFS-e para que o emitente possa alterar informações de alguns campos possíveis e reenviar a DPS para gerar uma NFS-e substituta à NFS-e original);
- Cancelar NFS-e (realiza a ação para o cancelamento da NFS-e selecionada);
- Download do XML da NFS-e (baixa o arquivo XML da NFS-e);
- Download do DANFSe (Documento Auxiliar de Nota Fiscal de Serviço eletrônica- representação gráfica da NFS-e com informações resumidas da nota) em formato PDF; Se o usuário clicar em ,iniciará o preenchimento da DPS para a geração de uma

  nova NFS-e.

## 5.1. Visualizar NFS-e e Eventos Vinculados

Permite visualizar as informações da NFS-e, além da opção de download da mesma nos formatos HTML (Transformação do XML com XSLT), XML e PDF. A visualização retorna, além das informações da NFS-e, também dos eventos vinculados à NFS-e. Estes últimos, estão disponíveis somente no formato HTML (Transformação do XML com XSLT).

***

## 5.2. Substituição de NFS-e

O processo de substituição de uma NFS-e é o processo de emissão de uma DPS com alteração de uma ou mais informações de uma DPS já emitida com NFS-e gerada, com a informação da chave de acesso da NFS-e existente na nova DPS, para que o sistema entenda que este envio de DPS se trata de uma substituição de NFS-e e realize todas as validações necessárias para realizar a substituição da NFS-e original pela NFS-e substituta.

***

Este processo realiza o cancelamento da NFS-e original através da geração e vinculação de um Evento de Cancelamento por Substituição de NFS-e. O cancelamento por substituição de uma NFS-e segue as regras de validação para cancelamento de NFS-e conforme <u></u>ANEXO\_I-SEFIN\_ADN- <u></u>DPS\_NFSe-SNNFSe.

Todos estes documentos fiscais eletrônicos ficam vinculados mantendo a rastreabilidade entre eles. O processo de substituição pelo Emissor Web é realizado prestando as mesmas informações da emissão da DPS. Ao final é exibido um comparativo entre as informações da NFS-e original e da NFS-e substituta, **por exemplo:**

.

Os parâmetros para a substituição de uma NFS-e são cadastradas pelo município vinculado à emissão da NFS-e. Cada município define os seguintes parâmetros, e as dúvidas em relação a eles devem ser encaminhadas ao próprio município:

- Se existe um prazo máximo para a substituição de uma NFS-e;
- Qual é o prazo máximo (em dias) para substituição de uma NFS-e;
- Se é permitido substituir uma NFS-e onde os não-emitentes não foram identificados;
- Se é permitido alterar as informações dos não-emitentes na NFS-e substituta.

## 5.3. Cancelamento de NFS-e e Solicitação de Cancelamento por Análise Fiscal

O processo de cancelamento de uma NFS-e realiza o cancelamento da NFS-e original através da geração e vinculação de um Evento de Cancelamento de NFS-e. O cancelamento de uma NFS-e

***

segue as regras de validação para cancelamento de NFS-e dispostas no ANEXO\_I-SEFIN\_ADN- DPS\_NFSe-SNNFSe.

Todos estes documentos fiscais eletrônicos ficam vinculados, mantendo a rastreabilidade entre eles.

O processo de cancelamento pelo Emissor Web é realizado prestando as informações permitidas conforme campos abaixo. Este evento gera um arquivo XML, conforme leiautes dos eventos definidos no Anexo II, que é processado conforme regras de validação para ser armazenado em banco de dados e vinculado à NFS-e.

| Campo | Descrição | Tipo | Ocorrênci a | Ação |
| --- | --- | --- | --- | --- |
| Chave de acesso | Informação da chave Texto de acesso que identifica a NFS-e |  | Obrigatório | Recuperado pelo sistema |
| Detalhes da NFS-e |  |  |  |  |
| Número da DPS | Informação sobre o Numérico número da DPS |  | Conforme Regra de sistema Negócio | Recuperado pelo |
| Série da DPS | Informação da série Numérico da DPS |  | Conforme Regra de sistema Negócio | Recuperado pelo |
| Número da NFS-e | Informação sobre o Numérico Número da NFS-e |  | Conforme Regra de sistema Negócio | Recuperado pelo |
| Data da Geração | Informação da data da Data geração da NFS-e |  | Obrigatório | Recuperado pelo sistema |
| Valor do serviço | Informação sobre o Valor valor total cobrado pelo serviço prestado |  | Obrigatório | Recuperado pelo sistema |
| Município Gerador | Informação sobre o Texto município que gerou a NFS-e |  | Obrigatório | Recuperado pelo sistema |
| Emitido para | Informação sobre Texto |  | Obrigatório | Recuperado pelo |

***

|  |  |  | para quem a NFS-e foi emitida | sistema |
| --- | --- | --- | --- | --- |
| Serviço prestado | Informação do Texto código/Descrição do Serviço |  | Obrigatório | Recuperado pelo sistema |
| Motivo Cancelamento | Descrição do motivo Combo do cancelamento |  | Obrigatório | Incluir o motivo do cancelamento da NFS- e |
| Justificativa | Descrição da Texto justificativa para o cancelamento |  | Obrigatório | Incluir a justificativa para o cancelamento |

Caso o cancelamento de NFS-e não seja possível conforme as regras para cancelamento definidas pelo município vinculado à emissão da NFS-e, o sistema exibirá uma mensagem de alerta:

Se a resposta for “SIM”, ele permite que esta ação para cancelamento se torne um evento de Solicitação para Cancelamento de NFS-e por Análise Fiscal.

| 5.4. | Será exibida a tela abaixo, onde deverá ser selecionado o “Motivo do Cancelamento” e a “Justificativa”. Download XML |
| --- | --- |
| 5.5. | Para fazer o download do arquivo XML da NFS-e emitida, o emitente aciona o comando “Download XML” para manter uma cópia deste arquivo no seu ambiente computacional. O sistema baixará o modelo XML com os dados preenchidos da NFS-e. Download DANFSe Para fazer o download do arquivo PDF do DANFSe da NFS-e emitida, o emitente aciona o comando “Download DANFS-e” para manter uma cópia deste arquivo no seu ambiente computacional. O sistema baixará o modelo XML com os dados preenchidos da NFS-e. |

***

# 6. Consulta NFS-e Recebidas

Nesta página é possível realizar a consulta às NFS-e emitidas por outros usuários, onde o usuário emitente figura como tomador/adquirente ou intermediário. O usuário pode filtrar, de uma listagem disponibilizada as notas conforme filtros descritos no campo de pesquisa da aplicação.

***

O sistema vai totalizar o quantitativo de registros que atendem aos critérios de buscas (Total NFS-e recebidas) e exibir na tela o campo totalizador das NFS-e recebidas, desde o início de utilização do sistema até a data presente de visualização da consulta pelo usuário.

Outras ações podem ser realizadas para cada uma das notas listadas:

- Visualizar NFS-e (visualização no navegador do emitente em formato web);
- Baixar o XML da NFS-e (baixa o arquivo XML da NFS-e);
- Baixar o DANFSe (Documento Auxiliar de Nota Fiscal de Serviço eletrônica – representação gráfica da NFS-e com informações resumidas da nota) em formato PDF;
- Confirmar;
- Rejeitar.
- Iniciar uma nova emissão através do botão Nova NFS-e.

## 6.1. Visualizar NFS-e recebida

Apresentará a NFS-e emitida por outro usuário para o CPF/CNPJ do usuário emitente logado.

## 6.2. Download XML

Para fazer o download do arquivo XML da NFS-e emitida o, emitente aciona o comando “Download XML” para manter uma cópia deste arquivo no seu ambiente computacional. O sistema baixará o modelo XML com os dados preenchidos da NFS-e.

***

## 6.3. Download DANFSe

Para fazer o download do arquivo PDF do DANFSe da NFS-e emitida, o emitente aciona o comando “Download DANFS-e” para manter uma cópia deste arquivo no seu ambiente computacional. O sistema fará download de um PDF com o DANFSe da NFS-e emitida para o usuário logado.

## 6.4. Manifestação de NFS-e

O emitente da NFS-e logado no sistema poderá manifestar-se a respeito de uma NFS-e que foi emitida com sua identificação como tomador/adquirente de um serviço de uma NFS-e.

O Evento de Manifestação de NFS-e pode ser de Confirmação (onde o não emitente aceita e concorda com as informações registradas na nota) ou de Rejeição (onde o não emitente não aceita nem concorda com as informações registradas na nota).

Cada um dos não emitentes indicados na NFS-e pode manifestar-se em relação a uma nota. Portanto, estão previstos eventos de Confirmação e Rejeição de NFS-e emitidos cada um pelo Prestador/Fornecedor, Tomador/Adquirente ou Intermediário, a depender de quem seja o emitente da NFS-e.

Assim, **toda nota recebida por um usuário estará na área de Notas Recebidas do** **sistema e se poderá acionar os comandos para confirmar ou rejeitar aquela NFS-e**. Esta ação gera um arquivo XML, conforme leiaute definido no <u></u>ANEXO\_I-SEFIN\_ADN-DPS\_NFSe-SNNFSe e, que será processado, armazenado e vinculado à NFS-e correspondente.

***

### 6.4.1. Confirmar uma NFS-e

Ao consultar a área de Notas Recebidas, o usuário poderá acionar o comando “Confirmar” do menu disponibilizado caso reconheça a NFS-e emitida para ele como tomador/adquirente de serviço de um outro emitente. Esta ação gera um arquivo XML (evento de manifestação), conforme leiaute definido no <u></u>ANEXO\_I-SEFIN\_ADN-DPS\_NFSe-SNNFSe e, que será processado, armazenado e vincula à NFS-e correspondente.

Ao clicar em “Confirmar”, o sistema exibirá uma tela com um resumo da NFS-e emitida, conforme modelo abaixo:

Caso o usuário reconheça a NFS-e emitida para ele, deve clicar no botão.

### 6.4.2. Rejeitar uma NFS-e

Ao consultar a área de Notas Recebidas, o usuário poderá acionar o comando “Rejeitar” do menu disponibilizado caso reconheça a NFS-e emitida para ele como tomador/adquirente de serviço de um outro emitente. Esta ação gera um arquivo XML (evento de manifestação), conforme leiaute

***

definido no <u></u>ANEXO\_I-SEFIN\_ADN-DPS\_NFSe-SNNFSe, que será processado, armazenado e vinculado à NFS-e correspondente.

Ao clicar em “Rejeitar”, o sistema exibirá uma tela com um resumo da NFS-e emitida, conforme modelo abaixo:

Deve-se selecionador no campo “Motivo da Rejeição” uma das opções:

- NFS-e em duplicidade;

***

- NFS-e emitida pelo tomador;
- Não ocorrência do fato gerador;
- Erro quanto à responsabilidade tributária;
- Erro quanto ao valor do serviço, valor das deduções ou serviço prestado ou data do fato gerador;
- Outros. Deve-se preencher também o campo da “Justificativa”. Após o preenchimento deve-se clicar no botão.

# 7. Rascunho DPS

Esta funcionalidade permite que o emitente possa concluir o preenchimento e enviar a DPS para a geração da NFS-e em um momento posterior.

O principal uso desta funcionalidade é devido à perda de conexão do link de internet com o Emissor Web. O sistema “salva o rascunho” automaticamente a cada 5 segundos, contendo as informações da DPS que estão sendo preenchidas, para que o emitente possa retornar em outro momento e, do ponto onde parou, continuar o preenchimento conforme demonstrado na coluna passo da tela abaixo.

| Campo | Descrição | Tipo | Ocorrência | Ação |
| --- | --- | --- | --- | --- |
| Atualização | Informação da data da Data última atualização do rascunho |  | Obrigatório | Recuperado pelo sistema |
| Competência | Informação da data da Data competência da NFS-e |  | Obrigatório | Recuperado pelo sistema |
| Emitida para | Identificação sobre para quem foi emitida a NFS-e | Texto | Obrigatório | Recuperado pelo sistema |
| Valor | Informação sobre o Valor valor total da NFS-e emitida |  | Obrigatório | Recuperado pelo sistema |
| Passo | Identificação em qual Ícone passo de emissão a |  | Obrigatório | Recuperado pelo sistema |

***

|  |  |  |  | DPS está |
| --- | --- | --- | --- | --- |
| Ações no registro | Informação sobre as Ícone possíveis ações para o rascunho da DPS |  | Obrigatório | Direcionar para ação selecionada: • Editar • Excluir |

## 7.1. Editar DPS

A partir do registro do rascunho listado, o emitente pode continuar a edição da DPS acionando o comando “Editar”. **O sistema irá retomar o preenchimento da DPS do passo em que o** **rascunho está, p. ex.: serviço.**

A edição de uma DPS permite a alteração de todos os campos da DPS seguindo os mesmos passos para a emissão de NFS-e demonstradas neste documento de regras de negócio.

## 7.2. Excluir DPS

A partir do registro do rascunho listado, o emitente pode deletar fisicamente o rascunho acionando o comando “Excluir”.

***

# 8. Serviços Favoritos

Esta funcionalidade permite ao emitente gravar informações conforme tela abaixo para facilitar o preenchimento dos campos no passo 2 do Emissor Web. Estes registros servem também ao Emissor Móvel, que os utiliza para o preenchimento de informações na DPS simplificada.

Ao clicar o botão, o sistema exibirá a tela para o preenchimento dos seguintes campos:

1. Apelido (termo que ajudará o emissor, posteriormente, a identificar o serviço pré- configurado dentro do Sistema Nacional da NFS-e. Deve ter no máximo 50 caracteres).
2. Código de Tributação Nacional – selecionar dentre os códigos da lista;
3. Item da NBS correspondente ao serviço prestado. A Nomenclatura Brasileira de Serviços Intangíveis e Outras Operações que Produzam Variações no Patrimônio (NBS) é o classificador nacional para a identificação dos serviços e viabiliza adequadamente a elaboração fiscalização e avaliação de políticas públicas de forma integrada. Selecionar dentre os valores cadastrados.
4. Descrição do serviço.

   Após o preenchimento de todos os campos, clicar em.

O serviço cadastrado será exibido na tela inicial, onde será possível editá-lo ou excluí-lo; ou, ainda, cadastrar um novo serviço favorito.

***

| Campo | Descrição | Tipo | Ocorrência | Ação |
| --- | --- | --- | --- | --- |
| Municípios | Informação sobre o Texto município para o qual será criado o serviço favorito |  | Obrigatório | Recupera dos cadastros RFB ou CNC conforme convênio do município |
| Apelido | Termo para identificar Texto posteriormente o serviço pré- cadastrado no sistema da NFS-e |  | Obrigatório | Incluir apelido para o serviço |
| Código de Tributação Informação do Código Nacional | de Tributação Nacional | Combo | Obrigatório | Selecionar o código de tributação nacional aplicável ao serviço da lista |
| Item da NBS Informação correspondente ao Nomenclatura serviço selecionado | da Combo Brasileira de Serviços |  | Obrigatório | Selecionar a NBS aplicável ao serviço da lista |
| Descrição do serviço | Descrição do serviço Texto prestado |  | Obrigatório | Incluir a descrição do serviço prestado |

# 9. Consulta Pública de uma NFS-e

Com a unificação dos sistemas de NFS-e no Brasil, a chave de acesso nacional ajuda a padronizar o processo de emissão e controle de notas fiscais, eliminando as diferenças regionais que dificultavam a integração de sistemas entre municípios e empresas de diferentes localidades.

A consulta pública é disponibilizada a todos os envolvidos no processo fiscal (prestadores, tomadores e órgãos fiscalizadores) para que realizem consultas e validações do documento em

| 9.1. |  |  | exigido o preenchimento de alguns parâmetros. | plataformas digitais que tiverem os dados de emissão. A consulta a uma NFS-e é possível através do site | Consulta usando a chave de acesso da NFS-e | NFS-e | Consulta Pública de NFS-e | , onde será |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
|  | A • • • • • | conjunto de dígito documento. | chave de acesso da NFS-e A chave de acesso é composta por 50 Código do Município: Ambiente gerador: Nacional NFS-e. Tipo Inscrição Federal: Inscrição Federal: Número da NFSe: | nome do município através do código, acesse: | permitindo que os envolvidos no processo fiscal façam as suas consultas. Exemplo de uma Chave de Acesso e sua composição: 1-CPF, 2-CNPJ | serve como um “código de identificação” da nota fiscal, caracteres alfanuméricos, sendo que cada dígito representa uma informação sobre a nota fiscal e a empresa emitente do 31062001251235800000112230000000173023019580208160 Código do município do endereço do emitente. Códigos dos municípios IBGE Ambiente gerador da NFS-e: 1- Sistema Próprio do Município, 2- Sefin Se for CPF completar com 000 à esquerda. Número da NFS-e (Sequencial pelo emitente) | ou Para consultar o |  |

***

- **Ano/Mês Emissão:** Ano e mês que a nota foi emitida.
- **Código Numérico:** Código numérico de 9 posições, aleatório, gerado automaticamente pelo sistema gerador da NFS-e.
- **Dígito Verificador:** Dígito verificador do código numérico. Deve-se preencher a chave de acesso, preencher o hCaptcha e clicar em.

## 9.2. Consulta informando os dados da DPS

A <u></u>Declaração de Prestação de Serviço (DPS) é um documento de posse e responsabilidade do contribuinte, que deverá ser gerado manualmente ou por alguma aplicação adquirida, possuindo uma numeração sequencial crescente e devendo ser convertido em NFS-e no prazo estipulado pela legislação tributária municipal.

Neste caso deve-se, obrigatoriamente, preencher os seguintes dados:

- CPF/CNPJ do Emitente;
- Série da DPS (a série da DPS na NFS-e nacional é um código sequencial utilizado para identificar a ordem de emissão das DPS, que são documentos preparatórios para a emissão da Nota Fiscal de Serviço eletrônica. A série pode ser numérica ou alfabética, dependendo da prefeitura ou do sistema utilizado. A série da DPS pode ser encontrada no próprio documento eletrônico ou PDF da NFS-e, geralmente em um campo específico com a nomenclatura "Série da DPS" ou similar. No arquivo XML da NFS-e, as informações da série também podem ser encontradas nas tags correspondentes.)
- Número da DPS;
- Município de emissão (selecionar dentre aqueles disponíveis na lista).

***

Após o preenchimento dos campos obrigatórios, preencher o hCaptcha e clicar em

# 10. Controle de Acesso ao Sistema Nacional NFS-e – Contribuintes

## 10.1. Gerenciamento de Perfis e Níveis de Acesso

Gerencia Níveis e Perfis de Acesso do Usuário (Emitentes / Não Emitentes de NFS-e)

Existem três níveis de acesso definidos para o Sistema Nacional NFS-e:

- Nível 1 - acesso por usuário/senha;
- Nível 2 - acesso por usuário/senha com validação e cadastro prévio presencial pelo município do emitente;
- Nível 3 - acesso por certificado digital;

## 10.2. Controle de Primeiro Acesso (Emitentes / Não Emitentes de NFS-e)

O controle de primeiro acesso dos perfis Emitentes e Não Emitentes de NFS-e é composto por processos que são descritos abaixo, desde a funcionalidade para primeiro acesso ao sistema até o controle de níveis de acesso a funcionalidades disponíveis. Segue abaixo a descrição deste gerenciamento que verifica o cadastramento do emitente de DF-e pelo município no Sistema Nacional NFS-e

## 10.3. Realizar primeiro acesso ao Sistema Nacional NFS-e

O Cadastramento de Login/Senha no Sistema Nacional da NFS-e será realizada pelo próprio usuário no Portal Nacional NFS-e.

Para utilizar esta funcionalidade o usuário deve possuir os números de recibos das Declarações do Imposto de renda Pessoa Física (DIRPF) dos últimos 2 anos. Durante o processo de cadastramento será solicitado os números dos recibos de entrega das Declarações do Imposto de renda Pessoa Física (DIRPF) dos exercícios referentes às declarações ativas (últimas entregues) das quais o CPF do contribuinte seja titular.

Se a geração de senha for para um CNPJ, os números de recibo das DIRPF que devem ser informados são os do CPF que seja responsável legal pelo CNPJ.

Em caso de existência de registro de declaração de somente um dos últimos exercícios, será obrigatório que o contribuinte informe o número do recibo referente à declaração ativa (última entrega) deste ano.

Caso o usuário não tenha nenhum recibo da DIRPF será utilizado o título de eleitor para o cadastramento do Login/Senha, obtido do cadastro CPF da RFB.

##### Caso não possua título de eleitor, não será possível criação de usuário/senha e o

***

##### usuário será instruído a comparecer presencialmente ao município em que seja emitente

**para ser cadastrado no sistema.** Outra opção sem a necessidade de comparecimento presencial poderá ser o acesso ao sistema por certificado digital.

Ao final, o processo de primeiro acesso realizado é registrado na tabela de log do sistema.

***

## 10.4. Recuperar senha do usuário

O processo para recuperação de senha, caso o usuário já tenha se cadastrado, segue praticamente o mesmo fluxo realizado no primeiro acesso, exceto pelo início do processo em que o usuário já poderá acionar a recuperação de senha desde o início, sem realizar o primeiro acesso pois este fluxo já foi realizado. Segue abaixo as telas para melhor entendimento.

***

Caso o CPF ou CNPJ digitados não sejam encontrados no Sistema Nacional NFS-e, o sistema apresenta a tela a seguir para que o usuário opte por realizar o login com um CPF/CNPJ reconhecido pelo sistema ou que possa realizar todo o processo de primeiro acesso, conforme telas do item “Realizar primeiro acesso ao Sistema Nacional NFS-e” deste documento.

Durante os processos de primeiro acesso e recuperação de senha de usuário, a existência dos CNPJ ou CPF informados pelo usuário, os números da última ou das duas últimas declarações de imposto de renda pessoa física e o título de eleitor do CPF informado ou do CPF responsável pelo CNPJ são verificados nos respectivos cadastros da RFB.

Caso o CPF ou CNPJ sejam encontrados, ou seja, o usuário já tenha realizado o primeiro acesso ao sistema, o Sistema Nacional NFS-e exibe os e-mails cadastrados vinculados ao CPF ou CNPJ para que o usuário possa escolher qual e-mail deseja que o sistema envie o link para redefinição de senha.

Ao final, o processo de recuperação de senha realizada é registrado na tabela de log do sistema.

***

## 10.5. Confirmação do Cadastramento Presencial ao Usuário

A realização do cadastro inicial de autorização para emissão de NFS-e, bem como a regularização da situação cadastral de emitentes de NFS-e serão realizadas no serviço de atendimento presencial do município do emitente

Mediante a apresentação e conferência da documentação necessária exigida pelo município, o Gestor Atendente Municipal realiza acesso à funcionalidade disponível no Painel Administrativo Municipal e realiza o cadastramento inicial ou a regularização do emitente diretamente no CNC NFS-e.

O emitente deverá cadastrar uma senha. No primeiro acesso, o Sistema Nacional NFS-e identificará o usuário e solicitará que sua senha seja alterada.

## 10.6. Controle de Acesso ao Sistema Nacional NFS-e para o Emissor Web

### 10.6.1. Controle de Acesso por Usuário/Senha

O acesso pelo usuário CNPJ ou CPF e pela senha gerada no Sistema Nacional da NFS-e disponibiliza as funcionalidades conforme definição dos níveis de acesso para o tipo de acesso realizado do Sistema Nacional da NFS-e, sem a necessidade de certificado digital.

Se o emitente estiver apenas cadastrado, mas não estiver habilitado, ou seja, estiver bloqueado para emissão, o sistema permitirá acesso à funcionalidade para emissão de NFS-e, mas indicará por mensagem ao emitente a sua situação de bloqueio no CNC.

Ao final, o controle de acesso por usuário/senha realizado é registrado na tabela de log do sistema.

### 10.6.2. Controle de Acesso por Certificado Digital

O acesso por certificado digital disponibiliza as funcionalidades de consultas e emissão de NFS- e do Sistema Nacional da NFS-e.

***

Ao final, o controle de acesso por certificado digital realizado é registrado na tabela de log do sistema.

### 10.6.3. Controle de Acesso ao Sistema Nacional NFS-e via GOV.BR

A integração com GOV.BR pode ser utilizada para autenticação e acesso ao Emissor Web da NFS-e. A partir da implantação, em ambiente de produção, da evolução que permite alternar perfis no Emissor Web (próximo tópico deste guia), o acesso pelo GOV.BR passou a ser possível para todos os contribuintes, não apenas para o MEI.

**a. Níveis de Acesso GOV.BR aceitos pelo Emissor Web** Serão aceitos os níveis prata e ouro do GOV.BR para acesso ao Emissor Web da NFS-e por
pessoa física. **O nível bronze foi considerado insuficiente para os padrões mínimos de** **segurança exigidos pelo sistema.**

Importante observar que a forma de acesso ao emissor Web via login e senha também é possível para todos os prestadores/fornecedores (para a criação da senha de acesso, o cadastramento, “Fazer primeiro acesso”, continuará sendo necessário), sendo o GOV.BR apenas mais uma opção de acesso a ser oferecida (sem a necessidade de cadastramento prévio no Emissor Web).

Não há qualquer restrição ao acesso em relação à forma utilizada pelo usuário (login e senha, certificado digital, etc.) desde que tenha os níveis prata ou ouro.

**b. Acesso ao GOV.BR** O acesso por meio do GOV.BR é vinculado à pessoa física (CPF). Assim, ao realizar o login, a

***

sessão será iniciada no perfil correspondente ao CPF utilizado na autenticação.

Na página inicial, na seção “Meus Dados”, serão apresentadas as informações cadastrais desse CPF. Nesse perfil, o usuário poderá consultar as notas fiscais recebidas e, caso esteja cadastrado como contribuinte no Cadastro Nacional de Contribuintes (CNC) de algum município, também poderá emitir documentos fiscais em seu próprio nome.

Após iniciar a sessão, caso deseje atuar em nome de uma pessoa jurídica, o usuário poderá alternar para o perfil de um CNPJ do qual seja responsável legal ou para o qual possua procuração eletrônica válida. Para isso, deverá utilizar a funcionalidade de alternância de perfis descrita no tópico seguinte.

**c. Exceção para o MEI** Caso o CPF utilizado no login via GOV.BR seja o responsável legal por um CNPJ enquadrado
como MEI, o acesso ao Emissor Web será realizado diretamente no perfil do respectivo CNPJ, e não no perfil da pessoa física (CPF). Trata-se de um comportamento específico para essa situação, distinto da regra geral descrita no item “b”.

Essa exceção foi mantida para preservar a experiência já conhecida pelos usuários MEI. Isso ocorre porque, até a disponibilização da funcionalidade de alternância de perfis, o acesso por meio do GOV.BR era exclusivo para MEIs. Assim, por razões de continuidade e previsibilidade, optou-se por manter o mesmo comportamento de acesso já adotado e esperado por esses usuários.

# 11. Alternância de Perfis e Procurações

Pelo Emissor Web, após o login (estabelecimento de sessão), também é possível alternar perfis a partir de integrações com a base CNPJ ou com o Procurações Eletrônicas.

## 11.1. Tipos de Representação

## 11.1.1. Procuração Eletrônica

A gestão de procurações eletrônicas relacionadas à NFS-e é realizada por meio do Portal de Serviços da Receita Federal, disponível em: <u></u><https://servicos.receitafederal.gov.br/>

Foi disponibilizado um serviço específico para a NFS-e, por meio do qual uma pessoa física (CPF) ou pessoa jurídica (CNPJ) pode conceder poderes a outra pessoa física ou jurídica para atuar em seu nome nos serviços da NFS-e.

Para cadastrar uma nova procuração, o usuário deve acessar o Portal de Serviços e selecionar a opção “Nova Autorização”, conforme imagem abaixo. Em seguida, deverá informar o CPF ou CNPJ do procurador (pessoa que receberá os poderes) e definir o período de vigência da autorização.

***

Após informar os dados do procurador e a vigência da procuração, o usuário deverá selecionar o serviço específico da NFS-e na relação de serviços disponíveis, conforme demonstrado na imagem a seguir. Concluída a seleção, basta confirmar e assinar eletronicamente a autorização.

Uma vez concedida a procuração, o procurador poderá atuar em nome do outorgante (pessoa que concedeu os poderes) nos limites da autorização concedida. Entre as funcionalidades disponíveis estão:

- Utilizar os serviços da NFS-e por meio das integrações via API (emissão e distribuição), conforme previsto na documentação técnica;
- Acessar o Emissor Web da NFS-e em nome do outorgante.

## 11.1.2. Integração com a Base CNPJ

O sistema permite a alternância entre perfis de acesso com base nos vínculos existentes no cadastro do CNPJ perante a Receita Federal do Brasil.

##### As seguintes situações são permitidas:

- **Responsável Legal para Pessoa Jurídica**: quando o CPF utilizado no acesso estiver cadastrado como Responsável Legal de uma empresa, o usuário poderá alternar sua sessão para o CNPJ dessa empresa, passando a operar em nome da pessoa jurídica;
- **Pessoa Jurídica para outra Pessoa Jurídica com a mesma raiz de CNPJ**: quando houver empresas vinculadas à mesma raiz de CNPJ (os 8 primeiros dígitos do CNPJ), o perfil de uma empresa poderá ser alternado para o perfil de outra empresa pertencente ao mesmo grupo empresarial, observadas as permissões e os vínculos cadastrais existentes.

***

Exemplo: Supondo que o usuário acesse o sistema com um CPF cadastrado como Responsável Legal da empresa 12.345.678/0001-90, ele poderá:

- Alternar do perfil da pessoa física (CPF) para o perfil da empresa

  12.345.678/0001-90;
* Caso existam outros estabelecimentos ou empresas com a mesma raiz de
  CNPJ, como 12.345.678/0002-70 ou 12.345.678/0003-50, poderá alternar entre esses perfis diretamente pelo sistema.

Importante: a disponibilidade dos perfis para alternância depende dos vínculos cadastrais existentes no cadastro do CNPJ e das permissões identificadas pelo sistema no momento do acesso.

## 11.2. Como Alternar Perfis no Emissor Web

Após realizar o login no Emissor Nacional da NFS-e por uma das formas de autenticação disponíveis (Login e Senha, Certificado Digital ou GOV.BR), as informações do usuário serão exibidas no canto superior direito da tela, conforme ilustrado abaixo (também foi incluído um *timer* regressivo com a informação do tempo restante da sessão que foi estabelecida).

Para acessar a funcionalidade de representação, clique na seta localizada à direita da identificação do usuário. Em seguida, selecione a opção "Procuração/Representação" no menu de perfil, conforme demonstrado na imagem abaixo.

Ao selecionar a opção "Procuração/Representação", será exibida a seguinte janela:

***

No campo destacado em vermelho, o procurador ou representante deverá informar o CPF ou CNPJ da pessoa física ou jurídica que deseja representar, observadas as regras de autorização descritas nos tópicos Procuração Eletrônica e Integração com a Base CNPJ. Após informar o CPF/CNPJ que deseja representar, clique no botão "Representar". O sistema também disponibiliza o quadro "Representações Recentes", destacado em verde na imagem acima. Nesse quadro são exibidas as representações anteriormente realizadas pelo usuário. Para acessar novamente uma dessas representações, basta clicar no ícone de seta localizado à direita do respectivo registro, sem a necessidade de informar novamente o CPF ou CNPJ. Caso o CPF ou CNPJ informado não possua vínculo de representação válido de acordo com as regras descritas nos tópicos Procuração Eletrônica e Integração com a Base CNPJ, o sistema exibirá uma mensagem de erro semelhante à apresentada abaixo:

***

Quando a representação for validada com sucesso, o sistema realizará automaticamente a alteração do perfil ativo. No canto superior direito da tela serão exibidos:

- o nome e o CPF ou CNPJ da pessoa física ou jurídica representada;
- a identificação do procurador ou representante que está realizando o acesso;
- um aviso em destaque informando que o usuário está atuando em nome de terceiros.

  A partir desse momento, o procurador ou representante poderá utilizar as funcionalidades disponíveis no Emissor Nacional da NFS-e em nome da pessoa física ou jurídica representada, observadas as autorizações e os limites definidos para a representação.

**OBSERVAÇÃO**: De forma análoga ao Emissor Web, também será possível acessar as APIs de Emissão (<https://sefin.nfse.gov.br/SefinNacional/docs/index#operation/NFSe_Post>) e de Distribuição (<https://adn.nfse.gov.br/contribuintes/docs/index.html#tag/DistribuicaoContribuinte>) com base no serviço NFS-e criado no Procurações Eletrônicas ou nas integrações com a base cadastral CNPJ. As validações se darão a partir do certificado digital utilizado nas conexões com as APIs e as assinaturas dos emitentes dos documentos.