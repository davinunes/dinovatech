# Manual dos Municípios Conveniados ao Sistema Nacional NFS-e

# Guia para utilização das API´s do ADN (Ambiente de Dados Nacional)

***

## HISTÓRICO DE VERSÕES

| Versão | Data | Alterações da Versão |
| --- | --- | --- |
| 1.0 | 17/03/2025 | Versão inicial. |

## Resumo do Documento

**Descrição:** Este documento apresenta os eventos e métodos relacionados com as API´s utilizados no ADN pelos municípios conveniados.

## Destinação Municípios conveniados.

***

**1.1. API NFS-e**
**1.1.1. Descrição**

### Possui um serviço:

- Consulta de NFS-e pela chave de acesso;

  **1.1.2. Métodos**
  ***a) GET – /nfse/{chaveAcesso}***
  Consulta NFS-e pela chave de acesso. Através de um método GET a API obtém uma NFS-e a partir de uma chave de acesso como parâmetro na consulta realizada pelo solicitante.

**1.2. API DPS**
**1.2.1. Descrição** Serviço cujo objetivo principal é recuperar a chave de acesso da NFS-e a partir de uma consulta pelo identificador da DPS (Código IBGE do Município Emissor (7), Tipo de Inscrição (1), Inscrição Federal (14 - CPF completar com 000 à esquerda), Série DPS (5), Núm. DPS (15). No entanto, por questões de sigilo fiscal, a chave de acesso somente será informada somente se a identificação do usuário do certificado digital da conexão solicitante for um ator (Prestador, Tomador ou Intermediário) que consta na NFS-e gerada a partir da DPS consultada.
**1.2.2. Métodos** O processamento se dá em dois métodos distintos com objetivos separados, Get e Head. Realiza uma consulta ao banco de dados da Sefin Nacional NFS-e com os parâmetros da DPS informados, retornando a chave de acesso da NFS-e correspondente ao DPS consultado ou somente a informação de que a NFS-e referente à DPS consultada foi gerada, sem informar a chave de acesso.
***a) GET – /dps/{id}***
Recupera a chave de acesso da NFS-e a partir do identificador da DPS. O solicitante informa a identificação da DPS e a API retorna a chave de acesso da NFS-e correspondente. Somente podem ser consultados identificadores de DPS emitidos através Sefin Nacional NFS-e. e desde que a identificação do certificado digital da conexão solicitante corresponda a um dos atores informados na DPS da NFS-e (Prestador, Tomador ou Intermediário). Caso contrário a solicitação será negada.

***b) HEAD – /dps/{id}***
O método Head é uma consulta para informar se uma NFS-e foi gerada ou não a partir do identificador da DPS. Este método atende a qualquer usuário desde que realize a consulta com um certificado digital válido.

**1.3. API Eventos**
**1.3.1. Descrição** Recepciona os Pedidos de Registros de Eventos de NFS-e gerados e enviados para a API, realiza validações de negócio sobre estes pedidos e gera o Evento de NFS-e. O evento gerado é vinculado à NFS-e para a qual o pedido de registro de evento foi enviado. Os eventos de NFS-e implementados no Sistema Nacional NFS-e são:

| i. | Evento de Cancelamento de NFS-e; |
| --- | --- |
| ii. | Evento de Cancelamento por Substituição de NFS-e; |
| iii. | Solicitação de Análise Fiscal para Cancelamento de NFS-e; |
| iv. | Cancelamento de NFS-e Deferido por Análise Fiscal; |
| v. | Cancelamento de NFS-e Indeferido por Análise Fiscal; |
| vi. | Manifestação de NFS-e: |

- Confirmação do Prestador;
- Confirmação do Tomador;

***

- Confirmação do Intermediário;
- Confirmação Tácita;
- Rejeição do Prestador;
- Rejeição do Tomador;
- Rejeição do Intermediário;
- Anulação da Rejeição;

| vii. | Cancelamento de NFS-e por Ofício; |  |  |
| --- | --- | --- | --- |
| viii. | Bloqueio de NFS-e por Ofício de: | • • • • • | Cancelamento de NFS-e; Cancelamento de NFS-e por Substituição; Cancelamento de NFS-e Deferido por Análise Fiscal; Cancelamento de NFS-e Indeferido por Análise Fiscal; Cancelamento de NFS-e por Ofício; |
| ix. | Desbloqueio de NFS-e por Ofício de: |  |  |

- Cancelamento de NFS-e;
- Cancelamento de NFS-e por Substituição;
- Cancelamento de NFS-e Deferido por Análise Fiscal;
- Cancelamento de NFS-e Indeferido por Análise Fiscal;
- Cancelamento de NFS-e por Ofício;

  Segue descrição do objetivo de cada um dos Eventos de NFS-e implementados no Sistema Nacional NFS-e cujo emitente é o município.

***a) Cancelamento de NFS-e Deferido por Análise Fiscal***
Após a análise da solicitação de cancelamento da nota fiscal, a administração municipal poderá deferir a solicitação emitindo um Evento de Cancelamento de NFS-e Deferido por Análise Fiscal. Este evento tem o mesmo efeito de um cancelamento de NFS-e para o sistema, cancelando a NFS- e, conforme a descrição do Evento de Cancelamento de NFS-e.

***b) Cancelamento de NFS-e Indeferido por Análise Fiscal***
Após a análise da solicitação de cancelamento da nota fiscal, a administração municipal poderá indeferir a solicitação emitindo um Evento de Cancelamento de NFS-e Indeferido por Análise Fiscal. Este evento indefere a solicitação de cancelamento de NFS-e para o sistema, não cancelando a NFS-e.

***c) Manifestação de NFS-e – Anulação da Rejeição***
Evento enviado pela administração tributária emissora da NFS-e que anula os efeitos da manifestação de rejeição da NFS-e encaminhada previamente pelo prestador, tomador ou intermediário. Quando um tomador encaminha um evento de rejeição da NFS-e, o prestador pode fazer prova de que o serviço realmente foi prestado e que a NFS-e é idônea. Caso a administração tributária municipal reconheça o pleito do prestador, ela poderá encaminhar o evento de anulação da rejeição.

***d) Cancelamento de NFS-e por Ofício***
Cancelamento efetuado pela administração tributária mesmo sem a solicitação do contribuinte.

- O efeito sistêmico desse evento é o mesmo do “Evento de Cancelamento de NFS-e”;
- Somente o município emissor da NFS-e pode cancelar de ofício;
- O cancelamento de ofício poderá ser realizado mesmo que a nota tenha evento de manifestação de confirmação da NFS-e.

  ***e) Bloqueio de NFS-e por Ofício***
  Evento pelo qual a administração tributária do município emissor da NFS-e indica quais outros eventos de NFS-e devem ser rejeitados pelo sistema por estar a nota, momentaneamente, bloqueada para recepcionar tais eventos.

Em um evento de bloqueio pode ser bloqueado apenas um evento dentre os da lista abaixo:

- Cancelamento de NFS-e;

***

- Cancelamento de NFS-e por Substituição;
- Cancelamento de NFS-e Deferido por Análise Fiscal;
- Cancelamento de NFS-e Indeferido por Análise Fiscal;
- Cancelamento de NFS-e por Ofício;

  O Evento de Bloqueio de NFS-e por Ofício não será aceito se a NFS-e já tiver um evento de bloqueio com o mesmo tipo de evento já bloqueado, ou seja, pendente de desbloqueio.

**Ex1:** A administração tributária envia dois eventos de bloqueio para o “cancelamento de NFS-e” para uma NFS-e, ou seja, dois eventos de bloqueio para o mesmo tipo de evento (Evento de Cancelamento de NFS-e). O primeiro destes eventos bloqueia a NFS-e para que não seja cancelada (pelo Evento de Cancelamento de NFS-e). Se não houver um evento que desbloqueie esta nota para o seu cancelamento, o segundo evento de bloqueio enviado será rejeitado pois o primeiro está pendente de desbloqueio. A administração tributária deverá enviar o Evento de Desbloqueio de NFS-e por Ofício (que será explicado logo a seguir) para o Evento de Cancelamento de NFS-e. Assim a nota ficará desbloqueada para seu cancelamento (cancelamento este realizado pelo Evento de Cancelamento de NFS-e). Uma vez desbloqueada a NFS-e poderá ser bloqueada para cancelamento (para o mesmo Evento de Cancelamento de NFS-e). Dessa forma, havendo um desbloqueio para o primeiro evento de bloqueio, o segundo evento de bloqueio enviado pela administração tributária não seria rejeitado pelo sistema nacional.

Consequentemente, é permitido o envio de um evento de bloqueio para cada evento da lista acima mencionada, que antes não foram bloqueados.

**Ex2:** existe um evento de bloqueio para o Evento de Cancelamento de NFS-e. Em um momento posterior é enviado um novo evento de bloqueio para o Evento de Cancelamento de NFS-e Deferido por Análise Fiscal; em seguida é enviado um outro evento de bloqueio para o Evento de Cancelamento de NFS-e Indeferido por Análise Fiscal; posteriormente é enviado um outro evento de bloqueio para o Evento de Cancelamento de NFS-e por Ofício;

Ao final teríamos a NFS-e bloqueada para 4 eventos, bloqueio este realizado em momentos distintos, conforme explicitado no Ex2.

***f) Desbloqueio de NFS-e por Ofício***
Evento pelo qual a administração tributária do município emissor da NFS-e indica quais eventos de NFS-e devem ser desbloqueados pelo sistema, ou seja, para que haja um desbloqueio de algum evento é necessário que haja um evento de bloqueio anterior.

Em um evento de desbloqueio poderão ser desbloqueados os mesmos eventos possíveis de bloqueio, conforme lista abaixo:

- Cancelamento de NFS-e;
- Cancelamento de NFS-e por Substituição;
- Cancelamento de NFS-e Deferido por Análise Fiscal;
- Cancelamento de NFS-e Indeferido por Análise Fiscal;
- Cancelamento de NFS-e por Ofício;

  O Evento de Desbloqueio de NFS-e por Ofício deverá indicar qual o Evento de Bloqueio de NFS-e se refere para realizar o desbloqueio.

Aproveitando o Ex2 do item que trata do Bloqueio de NFS-e por Ofício, temos:

Ex2: A NFS-e está bloqueada para 4 Eventos: Cancelamento de NFS-e, Cancelamento de NFS-e Deferido por Análise Fiscal, Cancelamento de NFS-e Indeferido por Análise Fiscal e Evento de Cancelamento de NFS-e por Ofício. O Evento de Desbloqueio de Ofício desbloqueia um evento por vez. Assim, este evento deve informar o identificador do evento de bloqueio que deseja desbloquear. No caso deste exemplo, para que a nota fique totalmente desbloqueada a administração tributária deve enviar 4 eventos de desbloqueio, um para cada evento de bloqueio existente na NFS-e.

**1.3.2. Métodos** Para o processamento realizado sobre o Pedido de Registro de Evento devem ser compreendidos:

***

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

***

***c) GET – /nfse/{chaveAcesso}/eventos/{tipoEvento}***
Consulta eventos por chave de acesso e tipo. O solicitante informa a chave de acesso e o tipo de evento (código identificador do evento). A API retorna o(s) evento(s) do tipo especificado vinculado(s) à chave de acesso de NFS-e informada na consulta.

***d) GET – /nfse/{chaveAcesso}/eventos/{tipoEvento}/{numSeqEvento}***
Consulta eventos por chave de acesso, tipo e sequencial do evento. O solicitante informa a chave de acesso, o tipo de evento (código identificador do evento) e o número sequencial específico do tipo de evento desejado (caso o tipo de evento permita mais de um evento do mesmo tipo para a mesma nota, caso contrário o número sequencial deve ser igual a 1). A API retorna exatamente o evento com o número sequencial solicitado, do tipo especificado, vinculado à chave de acesso de NFS-e informada na consulta.

## Ambiente de Dados Nacional NFS-e – Compartilhamento e Distribuição de DF-e

O Ambiente de Dados Nacional NFS-e é o módulo do Sistema Nacional NFS-e que funciona como um repositório nacional de documentos fiscais eletrônicos – DF-e (NFS-e nacional e Eventos de NFS-e, Créditos, Débitos e Apuração).

O ADN fornece a API DF-e para recepcionar os documentos fiscais eletrônicos compartilhados entre os municípios conveniados ao SN NFS-e além de possibilitar a distribuição destes DF-e para aqueles municípios que ocupem algum papel de interesse pelo DF-e, conforme regras de distribuição que veremos mais adiante.

O ADN agiliza o compartilhamento de informações e obtenção da confirmação da entrega em modo síncrono, com garantia de entrega fim-a-fim entre as aplicações. O uso da Internet, a escalabilidade da solução e a centralização dos processos são fatores chaves para este modelo.

A modelagem do compartilhamento de DF-e através de NSU permite a implementação de um controle de sincronismo eficiente, identificando os documentos fiscais faltantes no ADN e possibilitando a recuperação de documentos faltantes do repositório local.

**1.3.3. Compartilhamento de DF-e para o ADN NFS-e** A Sefin Municipal, responsável pela autorização do documento fiscal, deve transmitir os documentos fiscais para o método de recepção de documentos fiscais da API DF-e existente no ADN. O DF-e transmitido ao ADN deve utilizar os leiautes padronizados nacionalmente para os diversos DF-e existentes no Sistema Nacional NFS-e (NFS-e e Eventos de NFS-e). Os leiautes XML estão disponíveis nos anexos deste manual. Caso o município conveniado ao SN NFS-e mantenha seu próprio ambiente informatizado de autorização de DF-e (NFS-e e Eventos de NFS-e), poderá transcrever as informações dos documentos fiscais, recepcionados e autorizados em seu ambiente, para o padrão nacional, assinando-os com o certificado digital do município e transmitir estes documentos fiscais assinados para o ADN, que irá recepcioná-los e internalizá-los. A recepção de DF-e permite aos municípios conveniados enviarem os documentos fiscais autorizados pelos seus próprios sistemas autorizadores, centralizando a comunicação de todos os envolvidos em um único ponto central, o ADN NFS-e.
**1.3.4. Distribuição de DF-e pelo ADN NFS-e** Esta funcionalidade do ADN poderá ser acessada pelo município que deseja receber os documentos fiscais em que o município consta na NFS-e como interessado (Município de incidência do ISSQN, Local da prestação do serviço e município do endereço dos estabelecimentos ou domicílio dos não emitentes da NFS-e). Os eventos relacionados a uma NFS-e possuem a mesma regra de distribuição para municípios das NFS-e. Esta arquitetura favorece a implantação do modelo ao não exigir que os municípios mantenham API de alta disponibilidade para recepcionar os documentos fiscais e traz simplicidade para

***

construção de aplicações clientes para consumir serviços oferecidos de forma centralizada.

Para realizar o controle e distribuição dos DF-e do ADN são criados os NSU (Números Sequenciais Únicos) abaixo:

1. NSU Geral do ADN (por DF-e)-Um número sequencial por documento gerado/compartilhado com o ADN pelas Sefins Municipais e Nacional. O enfoque dele não é distribuição, somente controle pelo ADN.
2. NSU de Recepção/Backup (por Código Município) -É único por município emissor, retornado para o município no retorno da chamada síncrona;
3. NSU de Distribuição (por Código Município) - Utilizado para cada município poder recuperar os documentos nos quais ele NÃO seja o gerador do documento (não é o município emissor do DF-e), mas conste como interessado (Município de incidência do ISSQN, Local da prestação do serviço e município do endereço dos estabelecimentos ou domicílio dos não emitentes da NFS-e);
4. NSU de Distribuição para os atores da NFS-e (por CPF/CNPJ) - Utilizado para distribuir os documentos para cada CPF/CNPJ interessado nos DF-e (Prestador, Tomador e Intermediário).

   **1.4. API DF-e para Municípios**
   **1.4.1. Descrição** Possui quatro métodos, um que recepciona os DF-e transmitidos pelas Sefin Autorizadoras (dos municípios que mantém seu próprio ambiente autorizador de NFS-e e a Sefin Nacional NFS-e), e outros três que realizam a distribuição dos DF-e para os atores da NFS-e: Emitente e Não Emitente (podem estar configurados como Prestador, Tomador e Intermediário do serviço da NFS-e). Para obter os DF-e, a inscrição federal (CPF ou CNPJ) dos atores deve constar na NFS-e nos campos apropriados que identificam o Prestador, Tomador e Intermediário do serviço da NFS-e. Os eventos de NFS-e que referenciam esta NFS-e poderão ser distribuídos para estes atores. Os atores devem possuir um certificado digital de PF com seu CPF ou de PJ com seu CNPJ. O Ambiente de Dados Nacional gera um número sequencial único (NSU) para cada interessado nos documentos fiscais. Os documentos recuperados deverão conter uma sequência de numeração sem intervalos em sua base de dados.
   ***a) Recepção de um Lote de DF-e dos sistemas municipais***
   A aplicação cliente da API deve forma os lotes de DF-e em sequência cronológica dos documentos pois os mesmos, ao serem recepcionados pela API, serão processados em ordem para que possamos garantir que o ADN não esteja inconsistente em relação ao ciclo de vida dos DF-e.

Para entendermos a necessidade desse modelo vejamos, no Sistema Nacional NFS-e uma NFS-e substituída foi cancelada pelo processo de substituição. Então temos, uma primeira NFS-e que agora está cancelada por um Evento de Cancelamento por Substituição que foi gerado no momento da geração da segunda NFS-e, que é a nota substituta e é a NFS-e válida em relação ao mesmo fato gerador.

Se não realizarmos uma validação básica de sequenciamento da recepção destes documentos que estão relacionados poderíamos ter uma NFS-e substituta que foi compartilhada com o ADN sem que a nota substituída e o evento que a cancelou possam ter sido compartilhados com o ADN. Outra situação ainda pior poderia ser o compartilhamento da nota substituta e o evento que cancela a nota substituída sem que a própria nota substituída tenha sido compartilhada com o ADN, ou seja, o ADN teria um evento de compartilhamento que referencia uma nota que não existiria no ADN por falta de compartilhamento pelo município. E não haveria possibilidade de exigirmos do município este compartilhamento. Isso na modelagem padronizada nacionalmente configura uma inconsistência que não pode acontecer. Para evitarmos esses problemas facilmente e mantermos o ADN consistente, realizamos a validação por ordem cronológica dos documentos relacionados. Neste exemplo a primeira NFS-e entraria no ADN pois se trata de uma NFS-e emitida “originalmente”, ou seja, escriturando-o primeiramente o fato gerador ocorrido. Pelo leiaute

***

conseguimos diferenciá-la de uma NFS-e substituta pelo campo do leiaute chSubstda. Quando se trata desta primeira nota, emitida “originalmente” este campo não existe, pois, esta nota não referencia a chave de acesso de uma outra nota. Quando este campo está preenchido significa que estramos tratando de uma NFS-e substituta, ou seja, ela está substituindo uma outra nota existente, então a chave de acesso que preenche este campo referencia uma nota que já foi emitida e agora está cancelada, pois esta segunda nota passa a substituí-la.

No compartilhamento com o ADN pelo município, o sistema realiza uma validação para verificar se a primeira nota já foi compartilhada, bem como o Evento de Cancelamento por Substituição que cancela a primeira nota. Caso o ADN verifique que estes dois documentos já foram compartilhados então a nota substituta é aceita no ADN, caso contrário é rejeitada. Nesta última situação o município deve compartilhar anteriormente à nota substituta, a NFS-e “original (nota substituída) e seu Evento de Cancelamento por Substituição, que por sua vez, também deve ser compartilhado somente após o compartilhamento da primeira nota, pois o evento de cancelamento por substituição está cancelando uma NFS-e, então esta nota deve primeiramente existir.

Ainda assim o ADN não tem como garantir que o município deixe de compartilhar algum documento, por exemplo, o município pode compartilhar a primeira nota, depois o evento de cancelamento por substituição e não compartilhar a nota substituta. Entretanto, neste último caso, fica evidente que o município está seguindo a sequência de negócio estabelecida nacionalmente. Todos os participantes do Sistema Nacional NFS-e são responsáveis por manter a consistência do ADN que, além de ser o repositório nacional de NFS-e, também é o ambiente de compartilhamento de documentos fiscais entre os entes, contribuintes e partes interessadas.

***b) Distribuição de Conjunto de DF-e a Partir do NSU Informado***
A aplicação cliente da API deve informar o último número sequencial único (ultNSU) que possui.

Caso o NSU informado seja menor que o primeiro NSU disponível para distribuição, a aplicação do ADN deverá fornecer os documentos a partir do primeiro disponível para consulta.

***c) Consulta DF-e Vinculado ao NSU Informado***
A consulta DF-e a partir de um NSU permite que o interessado nos documentos fiscais consulte de maneira pontual um NSU que foi identificado como faltante em sua base de dados.

A aplicação cliente da API deve informar o número sequencial único (NSU) identificado como faltante em sua base de dados.

***d) Consulta de NFS-e por Chave de Acesso Informada***
A consulta a partir de uma chave de acesso permite que o interessado na NFS-e consulte de maneira pontual uma chave de acesso e obtenha o documento relativo à esta chave.

A aplicação cliente da API deve informar uma chave de acesso válida para recuperar a NFS-e.

**1.4.2. Métodos**
***a) POST /DFe/***
Os lotes devem ser gerados para serem compartilhados pelos sistemas municipais de forma que seja respeitada a ordem cronológica do processo de geração das transações (o cancelamento de NFS-e é sempre posterior à NFS-e a ser cancelada, sendo importante que o compartilhamento da NFS-e com o ADN seja realizado antes do compartilhamento do Evento de Cancelamento de NFS-

e). A criação do lote de documentos fiscais deve observar as seguintes premissas:

- Ordem crescente de número de documentos;
- O lote pode conter qualquer tipo de DF-e;
- Quantidade máxima de documentos fiscais do lote: 50 DF-e;
- Tamanho máximo do lote: 1 MB;

  O retorno da recepção de lotes irá conter o NSU (Número sequencial único) vinculado ao respectivo documento pelo Ambiente Nacional da NFS-e.

|  |  |  | A rejeição por falha de esquema XML será realizada documento a documento e não por lote. |
| --- | --- | --- | --- |
|  | b) • • • | Importante cronológica de | Este modelo possibilita a montagem de lote com qualquer tipo de DF-e; o DF-e existente no lote será identificado pelo conteúdo do atributo que define o tipo de evento. GET /DFe/{UltimoNSU} Obtém até 50 DFe a partir do último NSU informado. O solicitante utiliza como parâmetro para realizar a consulta o último NSU que é de seu conhecimento. O sistema nacional pesquisa até 50 DF-e que corresponderem aos próximos 50 NSU em sequência, a partir do último NSU repassado como parâmetro da consulta. O sistema nacional distribui estes DF-e encontrados e informa o último NSU da sequência encontrada para que o solicitante possa realizar nova consulta e repassar como parâmetro este último NSU informado pelo sistema nacional. A criação do lote de documentos deverá observar as seguintes regras: Ordem crescente de NSU; O lote poderá conter qualquer tipo de documento válido e seu respectivo NSU; Quantidade máxima de documentos no lote: 50 documentos; Documentos compartilhados pelo próprio município não estarão disponíveis para consulta. ressaltar que o processo de recepção e sincronização não será realizado em ordem emissão ou geração pelo sistema gerador de NFS-e (municipal ou Sefin Nacional), uma vez que a geração do NSU dos documentos será organizada por ordem cronológica de recepção pelo ADN NFS-e. |
| Obs |  |  | : Não confundir com a necessidade de envio dos DF-e, pelos municípios, em ordem cronológica dos documentos correlacionados. Com este serviço, o município conseguirá recuperar todos os documentos de seu interesse tão logo estes sejam recebidos pelo ADN NFS-e. É conveniente manter um controle do primeiro NSU válido para consulta. A resposta da API poderá |
| ser: | • • • momento. c) • • |  | Rejeição-com a devolução da mensagem com o motivo da falha informado; Nenhum documento localizado – não existe documentos fiscais para o CNPJ/CPF informado; Documento localizado – com a devolução dos documentos fiscais encontrados; O interessado nos DF-e deverá aguardar um tempo mínimo de uma hora para efetuar uma nova solicitação de distribuição caso receba a indicação que não existem mais documentos a serem pesquisados na base de dados do ADN. Se o NSU informado (ultNSU) for igual ao maior NSU do Ambiente Nacional (maxNSU), então não existem mais documentos a serem pesquisados no GET /DFe/{NSU} O solicitante informa um NSU e o sistema nacional retorna o DF-e associado. Para a distribuição dos DF-e realizados pelo ADN devem ser compreendidos: Leiautes DPS e NFS-e; Leiautes Pedido de Registro de Evento e Evento de NFS-e; Estes itens estão disponíveis no AnexoI-LeiautesRN\\_DPS\\_NFSe-SNNFSe. São também necessárias a compreensão das regras para o processamento da recepção do lote de |
|  | DF-e. |  | Estas regras estão disponíveis no AnexoIV-LeiautesRN\\_ADN-SNNFSe. |

***

**1.5. API DANFSe**
**1.5.1. Descrição** Serviço que gera o arquivo PDF da NFS-e (DANFSe – Documento Auxiliar de Nota Fiscal de Serviço eletrônica) a partir de uma consulta pela chave de acesso da NFS-e.
**1.5.2. Métodos** Consulta ao banco de dados do ADN NFS-e para recuperar o XML da NFS-e solicitada e, a partir das informações contidas no documento NFS-e, gerar o PDF em leiaute específico. Será possível gerar o DANFSe de qualquer nota que conste no ADN independentemente se foram geradas pela Sefin Nacional ou pelo sistema próprio do município, desde que o documento XML gerado pelo sistema municipal tenha sido compartilhado com o ADN NFS-e.
***a) GET – /danfse/{chaveAcesso}***
Recupera o DANFSe de uma NFS-e a partir de sua chave de acesso. O solicitante informa a chave de acesso e a API retorna o PDF da NFS-e correspondente à chave de acesso informada na consulta. Podem ser consultados identificadores de NFS-e geradas através da Sefin Nacional NFS-e ou qualquer Sefin geradora de NFS-e (desde que as NFS-e tenham sido transcritas para o leiaute nacional e compartilhadas com o Ambiente de Dados Nacional NFS-e.).

**1.6. AMBIENTE DE PRODUÇÃO RESTRITA** Foi disponibilizado um ambiente destinado a realização de testes das API´s do ADN por parte dos municípios conveniados:

### Link para produção restrita Swagger

<u></u><https: adn.producaorestrita.nfse.gov.br="" municipios="" docs="" index.html=""></https:>