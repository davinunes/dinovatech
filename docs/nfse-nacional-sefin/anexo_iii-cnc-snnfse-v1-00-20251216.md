## RN_RECEPCAO_LEIAUTE_CNC

| # | REGRAS DE NEGÓCIO |  | APLIC. | EFEITO | CÓD. ERRO | MSG. ERRO | NOTAS EXPLICATIVAS |
| --- | --- | --- | --- | --- | --- | --- | --- |
| 1 | Validação do Certificado de Transmissão |  | - | - | - | - | - |
| 2 | Certificado de Transmissor Inválido:<br>- Certificado de Transmissor inexistente na mensagem<br>- Versão difere "3"<br>- Se informado o Basic Constraint deve ser true (não pode ser Certificado de AC)<br>- KeyUsage não define "Autenticação Cliente" |  | Obrig. | Rej. | E3316 | Certificado de Transmissão Inválido | - |
| 3 | Validade do Certificado (data início e data fim) |  | Obrig. | Rej. | E3317 | Certificado de Transmissão expirado | - |
| 4 | Verifica a Cadeia de Certificação:<br>- Certificado da AC emissora não cadastrado na RFB<br>- Certificado de AC revogado<br>- Certificado não assinado pela AC emissora do Certificado |  | Obrig. | Rej. | E3321 | Certificado de Transmissão - Erro Cadeira de Certificação | - |
| 5 | LCR do Certificado de Transmissor<br>- Falta o endereço da LCR (CRL DistributionPoint)<br>- LCR indisponível<br>- LCR inválida |  | Obrig. | Rej. | E3319 | Certificado de Transmissão - Erro de acesso a LCR | - |
| 6 | Certificado do Transmissor revogado |  | Obrig. | Rej. | E3320 | Certificado de Transmissão revogado | - |
| 7 | Certificado Raiz difere da "ICP-Brasil" |  | Obrig. | Rej. | E3322 | Certificado de Transmissão difere da ICP - Brasil | - |
| 8 | Falta a extensão de CNPJ no Certificado (OtherName - OID=2.16.76.1.3.3) |  | Obrig. | Rej. | E3318 | Certificado de Transmissão sem CNPJ | - |
| 9 | Verificar se o CNPJ do município informado corresponde ao CNPJ de transmissão. |  | Obrig. | Rej. | E3338 | O CNPJ do município informado não corresponde ao CNPJ de transmissão. | - |
| 10 | Falha no esquema XML. |  | Obrig. | Rej. | E3344 | Falha no esquema XML. | - |

### Formula sources

```excel-formula
Cell: A2
Formula: =ROW(A1)
Cached value: 1
Display value: 1
Type: number
Number format: General
```

```excel-formula
Cell: A3
Formula: =ROW(A2)
Cached value: 2
Display value: 2
Type: number
Number format: General
```

```excel-formula
Cell: A4
Formula: =ROW(A3)
Cached value: 3
Display value: 3
Type: number
Number format: General
```

```excel-formula
Cell: A5
Formula: =ROW(A4)
Cached value: 4
Display value: 4
Type: number
Number format: General
```

```excel-formula
Cell: A6
Formula: =ROW(A5)
Cached value: 5
Display value: 5
Type: number
Number format: General
```

```excel-formula
Cell: A7
Formula: =ROW(A6)
Cached value: 6
Display value: 6
Type: number
Number format: General
```

```excel-formula
Cell: A8
Formula: =ROW(A7)
Cached value: 7
Display value: 7
Type: number
Number format: General
```

```excel-formula
Cell: A9
Formula: =ROW(A8)
Cached value: 8
Display value: 8
Type: number
Number format: General
```

```excel-formula
Cell: A10
Formula: =ROW(A9)
Cached value: 9
Display value: 9
Type: number
Number format: General
```

```excel-formula
Cell: A11
Formula: =ROW(A10)
Cached value: 10
Display value: 10
Type: number
Number format: General
```

## LEIAUTE_CNC

| # | CAMINHO NO XML | CAMPO | ELE | TIPO | OCOR. | TAM. | DESCRIÇÃO | NOTAS EXPLICATIVAS |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| 1 |  | CNC | Raiz | - | - | - | TAG raiz | - |
| 2 | CNC/ | versao | A | C | 1-1 | 1-4V2 | Versão do leiaute. | - |
| 3 | CNC/ | infCNC | G | - | 1-1 | - | Grupo de informações do Cadastro Nacional Complementar NFS-e | - |
| 4 | CNC/infCNC/ | Id | ID | C | 1-1 | 26 | "CNC" + Cód.Mun.(7) + Tipo de Ambiente(1) + IM(15) | - |
| 5 | CNC/infCNC/ | cMun | E | N | 1-1 | 7 | Código IBGE do município do contribuinte. | Conforme AnexoA-Tabelas_ISO2Paises_MunIBGE_Rodovias-SNNFSe. |
| 6 | CNC/infCNC/ | cnpjMun | E | N | 1-1 | 14 | CNPJ do Município. | - |
| 7 | CNC/infCNC/ | CPFAgTrib | E | N | 1-1 | 11 | CPF do agente da administração tributária municipal quer enviou o registro do contribuinte do município para o CNC. | - |
| 8 | CNC/infCNC/ | tpAmb | E | N | 1-1 | 1 |  Tipo de ambiente:<br><br>1 - Produção; <br>2 - Homologação; | - |
| 9 | CNC/infCNC/ | verAplic | E | C | 1-1 | 1-20 | Versão do aplicativo que gerou a Movimentação. | - |
| 10 | CNC/infCNC/ | infContrib | G | - | 1-1 | - | Grupo de informações do Contribuinte | - |
| 11 | CNC/infCNC/infContrib/ | CNPJ | CE | N | 1-1 | 14 | Número da inscrição federal (CNPJ) do contribuinte da NFS-e. | Um mesmo IDFed pode ser registrado mais de uma por um mesmo município desde que cada um destes registros realizados pelo município tenham IMs diferentes entre si.<br><br>Para um município, a chave para cadastro e manutenção do CNC é IDFederal (CNPJ/CPF) + Identificador Municipal (IM) (IDFed + IM são sempre obrigatórios). |
| 12 | CNC/infCNC/infContrib/ | CPF | CE | N | 1-1 | 11 | Número da inscrição federal (CPF) do contribuinte da NFS-e. |  |
| 13 | CNC/infCNC/infContrib/ | IM | E | C | 1-1 | 15 | Número do identificador municipal do contribuinte da NFS-e. |  |
| 14 | CNC/infCNC/infContrib/ | dIM | E | D | 1-1 | - | A data em que foi criada o indicador municipal do contribuinte AAAA-MM-DD.  | - |
| 15 | CNC/infCNC/infContrib/ | xFantasia | E | C | 0-1 | 150 | Nome fantasia do contribuinte. | - |
| 16 | CNC/infCNC/infContrib/ | ender | G | - | 0-1 | - | Grupo de informações do endereço do contribuinte do município | - |
| 17 | CNC/infCNC/infContrib/ender/ | CEP | E | N | 1-1 | 8 | Número do CEP do endereço do contribuinte.<br>(Informar os zeros não significativos) | - |
| 18 | CNC/infCNC/infContrib/ender/ | xLgr | E | C | 1-1 | 1-255 | Tipo e nome do logradouro da localização do endereço do contribuinte. | - |
| 19 | CNC/infCNC/infContrib/ender/ | nro | E | C | 1-1 | 1-60 | Número do imóvel do endereço do contribuinte. | - |
| 20 | CNC/infCNC/infContrib/ender/ | xCpl | E | C | 0-1 | 1-156 | Complemento do endereço do contribuinte. | - |
| 21 | CNC/infCNC/infContrib/ender/ | xBairro | E | C | 1-1 | 1-60 | Bairro do endereço do contribuinte. | - |
| 22 | CNC/infCNC/infContrib/ | fone | E | N | 0-1 | 9-20 | Número do telefone do contribuinte.<br>(Preencher com o Código DDD + número do telefone. <br>Nas operações com exterior é permitido informar o código do país + código da localidade + número do telefone) | - |
| 23 | CNC/infCNC/infContrib/ | email | E | C | 0-1 | 80 | E-mail do contribuinte. | - |
|  | CNC/infCNC/infContrib/ | dAutEmiss | E | D | 1-1 | - | Data inicial de autorização do contribuinte para emissão de NFS-e nos emissores públicos do Sistema Nacional NFS-e. | - |
| 24 | CNC/infCNC/infContrib/ | cStatEmiss | E | N | 1-1 | 1 | Status para emissão de NFS-e:<br><br>0 - Suspenso;<br>1 - Autorizado; | Por padrão todos os contribuintes com endereço em um município pelos cadastros RFB (CNPJ e CPF) estão habilitados a emitirem NFS-e pelos Emissores Públicos Nacionais.<br><br>Para desabilitar um contribuinte a emitir NFS-e pelo município, este deve registra o complemento no CNC (IDFed + IM + cStatEmiss = 0). |
| 26 | CNC/infCNC/infContrib/ | cSitCNC | E | N | 1-1 | 1 | Situação do registro no CNC NFS-e:<br><br>0 - Inativo;<br>1 - Ativo; | Exclusão lógica do registro no CNC. |
| 27 | CNC/infCNC/infContrib/ | xSitCadMun | E | C | 0-1 | 1-150 | Descrição Situação Cadastral no Município. | - |
| 28 | CNC/infCNC/infContrib/ | xMotivoSitCadMun | E | C | 0-1 | 1-255 | Motivo da Situação Cadastral no Município. | - |

### Formula sources

```excel-formula
Cell: A2
Formula: =ROW(A1)
Cached value: 1
Display value: 1
Type: number
Number format: General
```

```excel-formula
Cell: A3
Formula: =ROW(A2)
Cached value: 2
Display value: 2
Type: number
Number format: General
```

```excel-formula
Cell: A4
Formula: =ROW(A3)
Cached value: 3
Display value: 3
Type: number
Number format: General
```

```excel-formula
Cell: A5
Formula: =ROW(A4)
Cached value: 4
Display value: 4
Type: number
Number format: General
```

```excel-formula
Cell: A6
Formula: =ROW(A5)
Cached value: 5
Display value: 5
Type: number
Number format: General
```

```excel-formula
Cell: A7
Formula: =ROW(A6)
Cached value: 6
Display value: 6
Type: number
Number format: General
```

```excel-formula
Cell: A8
Formula: =ROW(A7)
Cached value: 7
Display value: 7
Type: number
Number format: General
```

```excel-formula
Cell: A9
Formula: =ROW(A8)
Cached value: 8
Display value: 8
Type: number
Number format: General
```

```excel-formula
Cell: A10
Formula: =ROW(A9)
Cached value: 9
Display value: 9
Type: number
Number format: General
```

```excel-formula
Cell: A11
Formula: =ROW(A10)
Cached value: 10
Display value: 10
Type: number
Number format: General
```

```excel-formula
Cell: A12
Formula: =ROW(A11)
Cached value: 11
Display value: 11
Type: number
Number format: General
```

```excel-formula
Cell: A13
Formula: =ROW(A12)
Cached value: 12
Display value: 12
Type: number
Number format: General
```

```excel-formula
Cell: A14
Formula: =ROW(A13)
Cached value: 13
Display value: 13
Type: number
Number format: General
```

```excel-formula
Cell: A15
Formula: =ROW(A14)
Cached value: 14
Display value: 14
Type: number
Number format: General
```

```excel-formula
Cell: A16
Formula: =ROW(A15)
Cached value: 15
Display value: 15
Type: number
Number format: General
```

```excel-formula
Cell: A17
Formula: =ROW(A16)
Cached value: 16
Display value: 16
Type: number
Number format: General
```

```excel-formula
Cell: A18
Formula: =ROW(A17)
Cached value: 17
Display value: 17
Type: number
Number format: General
```

```excel-formula
Cell: A19
Formula: =ROW(A18)
Cached value: 18
Display value: 18
Type: number
Number format: General
```

```excel-formula
Cell: A20
Formula: =ROW(A19)
Cached value: 19
Display value: 19
Type: number
Number format: General
```

```excel-formula
Cell: A21
Formula: =ROW(A20)
Cached value: 20
Display value: 20
Type: number
Number format: General
```

```excel-formula
Cell: A22
Formula: =ROW(A21)
Cached value: 21
Display value: 21
Type: number
Number format: General
```

```excel-formula
Cell: A23
Formula: =ROW(A22)
Cached value: 22
Display value: 22
Type: number
Number format: General
```

```excel-formula
Cell: A24
Formula: =ROW(A23)
Cached value: 23
Display value: 23
Type: number
Number format: General
```

```excel-formula
Cell: A26
Formula: =ROW(A24)
Cached value: 24
Display value: 24
Type: number
Number format: General
```

```excel-formula
Cell: A27
Formula: =ROW(A26)
Cached value: 26
Display value: 26
Type: number
Number format: General
```

```excel-formula
Cell: A28
Formula: =ROW(A27)
Cached value: 27
Display value: 27
Type: number
Number format: General
```

```excel-formula
Cell: A29
Formula: =ROW(A28)
Cached value: 28
Display value: 28
Type: number
Number format: General
```

## RN_LEIAUTE_CNC

| # | CAMINHO NO XML | CAMPO | REGRAS DE NEGÓCIO |  | APLIC. | EFEITO | CÓD. ERRO |  | NOTAS EXPLICATIVAS |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| 1 |  | CNC |  |  |  |  |  |  | - |
| 2 | CNC/ | versao | Prazo de aceitação da versão do leiaute CNC ultrapassado. |  | Obrig. | Rej. | E3329 | O prazo de aceitação da versão do leiaute da CNC expirou. | - |
| 3 | CNC/ | infCNC |  |  |  |  |  |  | - |
| 4 | CNC/infCNC/ | Id | Verificar se o código do município está de acordo com o campo cMun do leiaute CNC. |  | Obrig. | Rej. | E3341 | O código do município do id não está de acordo com a informação do campo cMun do leiaute. |  |
| 5 |  |  | Verificar se o tipo de ambiente está de acordo com o campo tpAmb do leiaute CNC. |  | Obrig. | Rej. | E3342 | O tipo de ambiente do id não está de acordo com a informação do campo tpAmb do leiaute. |  |
| 6 |  |  | Verificar se a inscrição municipal está de acordo com o campo IM do leiaute CNC. |  | Obrig. | Rej. | E3343 | A inscrição municipal do id não está de acordo com a informação do campo IM do leiaute. |  |
| 7 | CNC/infCNC/ | cMun | O código do município deve estar associado ao CNPJ de transmissão e existir no cadastro de convênio municipal do sistema nacional. |  | Obrig. | Rej. | E3336 | O código do município não existe no cadastro de convênio municipal do sistema nacional. | Conforme AnexoA-Tabelas_ISO2Paises_MunIBGE_Rodovias-SNNFSe. |
| 8 | CNC/infCNC/ | cnpjMun | O CNPJ do município deve corresponder ao CNPJ de transmissão. |  | Obrig. | Rej. | E3338 | O CNPJ do município informado não corresponde ao CNPJ de transmissão. |  |
| 9 |  |  | O CNPJ do município deve estar registrado no cadastro de convênio municipal do sistema nacional e estar "CADASTRADO", "PARAMETRIZADO" ou "ATIVO". |  | Obrig. | Rej. | E3333 | O CNPJ do município informado não existe no cadastro de convênio municipal do sistema nacional ou não está conveniado. |  |
| 10 |  |  | O CNPJ do município existe no cadastro de convênio municipal do sistema nacional, mas está suspenso. |  | Obrig. | Rej. | E3334 | O código do município informado existe, mas está suspenso no convênio municipal. |  |
| 11 |  |  | O CNPJ do município existe no cadastro de convênio municipal do sistema nacional, mas está inativo. |  | Obrig. | Rej. | E3335 | O código do município informado existe, mas não está ativo há mais de <número de dias> dias no cadastro de convênio municipal. |  |
| 12 | CNC/infCNC/ | CPFAgTrib | CPF informado é inválido (verificar DV). |  | Obrig. | Rej. | E3330 | CPF do agente tributário informado é inválido. | - |
|  |  |  | O CPF do Agente Tributário deve corresponder ao CPF de um Gestor Municipal ATIVO do município que está realizando o registro complementar no CNC NFS-e. |  | Obrig. | Rej. | E3353 | O CPF do Agente Tributário deve corresponder ao CPF de um Gestor Municipal ATIVO do município que está realizando o registro complementar no CNC NFS-e. |  |
| 13 | CNC/infCNC/ | tpAmb | Ambiente informado diverge do ambiente de recebimento para o qual o município enviou o movimento. |  | Obrig. | Rej. | E3331 | Ambiente informado diverge do ambiente de recebimento para o qual o município enviou o movimento CNC. | - |
| 15 | CNC/infCNC/ | verAplic | - |  | - | - | - | - | - |
| 16 | CNC/infCNC/ | infContrib | É obrigatório informar pelo menos um dado complementar do contribuinte para qualquer registro pelo município no CNC NFS-e. |  | Obrig. | Rej. | E3348 | Para qualquer registro complementar de um contribuinte o município deve informar IDFed + IM + pelo menos mais uma informação complementar | - |
| 17 |  |  | Não é permitido realizar movimentações em um registro de contribuinte do CNC NFS-e inativo (cSitCNC = 0). |  | Obrig. | Rej. | E3351 | Não é permitido realizar movimentações em um registro de contribuinte do CNC NFS-e inativo. |  |
| 18 | CNC/infCNC/infContrib/ | CNPJ | CNPJ informado é inválido (verificar DV). |  | Obrig. | Rej. | E3339 | CNPJ do contribuinte informado é inválido. | - |
| 19 | CNC/infCNC/infContrib/ | CPF | CPF informado é inválido (verificar DV). |  | Obrig. | Rej. | E3340 | CPF do contribuinte informado é inválido. | - |
| 20 | CNC/infCNC/infContrib/ | IM | - |  | - | - | - | - | - |
| 21 | CNC/infCNC/infContrib/ | dIM | Esta data deve ser posterior ou igual à data de inscrição do contribuinte nos cadastros RFB. |  | Obrig. | Rej. | 3356 | A data do indicador municipal deve ser posterior ou igual à data de inscrição do contribuinte nos cadastros RFB. | - |
| 22 | CNC/infCNC/infContrib/ | xFantasia | - |  | - | - | - | - | - |
| 23 | CNC/infCNC/infContrib/ | ender | Verificar se o município no endereço dos cadastros RFB (CNPJ/CPF) para o CNPJ ou CPF do contribuinte que se está complementando informações no CNC NFS-e, corresponde ao mesmo município (cMun) que está enviando este registro. <br>Se os municípios forem diferentes, então é obrigatório registrar o grupo de informações complementares de endereço (ender). |  | Obrig. | Rej. | E3352 | É obrigatório informar o grupo de informações complementares de endereço quando o município do endereço, nos cadastros RFB para o contribuinte informado, for diferente do município que está registrando informações complementares para o contribuinte no CNC NFS-e. |  |
| 24 | CNC/infCNC/infContrib/ender/ | CEP | O CEP informado deve existir e pertencer ao município correspondente ao código do município (cMun) que está enviando a informação do contribuinte. |  | Obrig. | Rej. | E3313 | O CEP informado para o endereço do contribuinte não existe ou não pertence ao município. Informe um CEP existente e que pertença ao município que está enviando a informação do contribuinte. | - |
| 25 | CNC/infCNC/infContrib/ender/ | xLgr | - |  | - | - | - | - | - |
| 26 | CNC/infCNC/infContrib/ender/ | nro | - |  | - | - | - | - | - |
| 27 | CNC/infCNC/infContrib/ender/ | xCpl | - |  | - | - | - | - | - |
| 28 | CNC/infCNC/infContrib/ender/ | xBairro | - |  | - | - | - | - | - |
| 29 | CNC/infCNC/infContrib/ | fone | - |  | - | - | - | - | - |
| 30 | CNC/infCNC/infContrib/ | email | Email deve ser informado conforme estrutura (conter @, ponto etc.). |  | Obrig. | Rej. | E3332 | Email informado no documento inválido. | - |
|  | CNC/infCNC/infContrib/ | dAutEmiss | A data de autorização de uso dos emissores públicos deve ser posterior ou igual à data do indicador municipal. O contribuinte poderá emitir novos documentos fiscais a partir desta data de autorização de uso dos emissores públicos ou a partir da data de início de vigência do convênio municipal, prevalecendo a maior data. |  | Obrig. | Rej. | E3354 | A data de autorização de uso dos emissores públicos deve ser posterior ou igual à data do indicador municipal. |  |
|  |  |  | A data de autorização de uso dos emissores públicos não pode ser alterada se for menor ou igual que a data atual da alteracão. |  | Obrig. | Rej. | E3355 | A data de autorização de uso dos emissores públicos não pode ser alterada se for menor ou igual que a data atual da alteracão. |  |
| 31 | CNC/infCNC/infContrib/ | cStatEmiss | - |  | - | - | - | - | - |
| 34 | CNC/infCNC/infContrib/ | cSitCNC | - |  | - | - | - | - | - |
| 35 | CNC/infCNC/infContrib/ | xSitCadMun | - |  | - | - | - | - | - |
| 36 | CNC/infCNC/infContrib/ | xMotivoSitCadMun | - |  | - | - | - | - |  |

### Formula sources

```excel-formula
Cell: A2
Formula: =ROW(A1)
Cached value: 1
Display value: 1
Type: number
Number format: General
```

```excel-formula
Cell: A3
Formula: =ROW(A2)
Cached value: 2
Display value: 2
Type: number
Number format: General
```

```excel-formula
Cell: A4
Formula: =ROW(A3)
Cached value: 3
Display value: 3
Type: number
Number format: General
```

```excel-formula
Cell: A5
Formula: =ROW(A4)
Cached value: 4
Display value: 4
Type: number
Number format: General
```

```excel-formula
Cell: A6
Formula: =ROW(A5)
Cached value: 5
Display value: 5
Type: number
Number format: General
```

```excel-formula
Cell: A7
Formula: =ROW(A6)
Cached value: 6
Display value: 6
Type: number
Number format: General
```

```excel-formula
Cell: A8
Formula: =ROW(A7)
Cached value: 7
Display value: 7
Type: number
Number format: General
```

```excel-formula
Cell: A9
Formula: =ROW(A8)
Cached value: 8
Display value: 8
Type: number
Number format: General
```

```excel-formula
Cell: A10
Formula: =ROW(A9)
Cached value: 9
Display value: 9
Type: number
Number format: General
```

```excel-formula
Cell: A11
Formula: =ROW(A10)
Cached value: 10
Display value: 10
Type: number
Number format: General
```

```excel-formula
Cell: A12
Formula: =ROW(A11)
Cached value: 11
Display value: 11
Type: number
Number format: General
```

```excel-formula
Cell: A13
Formula: =ROW(A12)
Cached value: 12
Display value: 12
Type: number
Number format: General
```

```excel-formula
Cell: A15
Formula: =ROW(A13)
Cached value: 13
Display value: 13
Type: number
Number format: General
```

```excel-formula
Cell: A16
Formula: =ROW(A15)
Cached value: 15
Display value: 15
Type: number
Number format: General
```

```excel-formula
Cell: A17
Formula: =ROW(A16)
Cached value: 16
Display value: 16
Type: number
Number format: General
```

```excel-formula
Cell: A18
Formula: =ROW(A17)
Cached value: 17
Display value: 17
Type: number
Number format: General
```

```excel-formula
Cell: A19
Formula: =ROW(A18)
Cached value: 18
Display value: 18
Type: number
Number format: General
```

```excel-formula
Cell: A20
Formula: =ROW(A19)
Cached value: 19
Display value: 19
Type: number
Number format: General
```

```excel-formula
Cell: A21
Formula: =ROW(A20)
Cached value: 20
Display value: 20
Type: number
Number format: General
```

```excel-formula
Cell: A22
Formula: =ROW(A21)
Cached value: 21
Display value: 21
Type: number
Number format: General
```

```excel-formula
Cell: A23
Formula: =ROW(A22)
Cached value: 22
Display value: 22
Type: number
Number format: General
```

```excel-formula
Cell: A24
Formula: =ROW(A23)
Cached value: 23
Display value: 23
Type: number
Number format: General
```

```excel-formula
Cell: A25
Formula: =ROW(A24)
Cached value: 24
Display value: 24
Type: number
Number format: General
```

```excel-formula
Cell: A26
Formula: =ROW(A25)
Cached value: 25
Display value: 25
Type: number
Number format: General
```

```excel-formula
Cell: A27
Formula: =ROW(A26)
Cached value: 26
Display value: 26
Type: number
Number format: General
```

```excel-formula
Cell: A28
Formula: =ROW(A27)
Cached value: 27
Display value: 27
Type: number
Number format: General
```

```excel-formula
Cell: A29
Formula: =ROW(A28)
Cached value: 28
Display value: 28
Type: number
Number format: General
```

```excel-formula
Cell: A30
Formula: =ROW(A29)
Cached value: 29
Display value: 29
Type: number
Number format: General
```

```excel-formula
Cell: A31
Formula: =ROW(A30)
Cached value: 30
Display value: 30
Type: number
Number format: General
```

```excel-formula
Cell: A34
Formula: =ROW(A31)
Cached value: 31
Display value: 31
Type: number
Number format: General
```

```excel-formula
Cell: A35
Formula: =ROW(A34)
Cached value: 34
Display value: 34
Type: number
Number format: General
```

```excel-formula
Cell: A36
Formula: =ROW(A35)
Cached value: 35
Display value: 35
Type: number
Number format: General
```

```excel-formula
Cell: A37
Formula: =ROW(A36)
Cached value: 36
Display value: 36
Type: number
Number format: General
```

## Workbook images and drawings

Named range semantics

{
  "name": "_xlnm._FilterDatabase",
  "expression": "LEIAUTE_CNC!$A$1:$I$29",
  "scope": "worksheet",
  "sheet": "LEIAUTE_CNC",
  "hidden": true
}

Named range semantics

{
  "name": "_xlnm._FilterDatabase",
  "expression": "RN_LEIAUTE_CNC!$A$1:$J$36",
  "scope": "worksheet",
  "sheet": "RN_LEIAUTE_CNC",
  "hidden": true
}