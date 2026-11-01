## LEIAUTE_PAR

| # | CAMINHO NO XML | CAMPO | ELE | TIPO | OCOR. | TAM. | DESCRIÇÃO | NOTAS EXPLICATIVAS |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| 1 |  | PARAM | Raiz | - | - | - | TAG raiz | - |
| 2 | PARAM/ | versao | A | C | 1-1 | 1-4V2 | Versão do leiaute. | - |
| 3 | PARAM/ | infPARAM | G | - | 1-1 | - | Grupo de informações da manutenção do processamento | - |
| 4 | PARAM/infPARAM/ | Id | ID | C | 1-1 | 26 | "PAR" + Cód.Mun.(7) + Tipo de Ambiente(1) + idPar(14) | - |
| 5 | PARAM/infPARAM/ | cMun | E | N | 1-1 | 7 | Código IBGE do município do contribuinte. | Conforme AnexoA-Tabelas_ISO2Paises_MunIBGE_Rodovias-SNNFSe. |
| 6 | PARAM/infPARAM/ | CNPJ | E | N | 1-1 | 14 | CNPJ do Município. | - |
| 7 | PARAM/infPARAM/ | CPFAgTrib | E | N | 1-1 | 11 | CPF do agente da administração tributária municipal que enviou a manutenção da parametrização pelo município. | - |
| 8 | PARAM/infPARAM/ | idPar | E | N | 1-1 | 14 | Identificador do parâmetro a ser manutenido. | - |
| 9 | PARAM/infPARAM/ | tpAmb | E | N | 1-1 | 1 |  Tipo de ambiente:<br><br>1 - Produção; <br>2 - Homologação; | - |
| 10 | PARAM/infPARAM/ | dhEmi | E | N | 1-1 | - | Data da Emissão da manutenção da parametrização. | - |
| 11 | PARAM/infPARAM/ | verAplic | E | C | 1-1 | 1-20 | Versão do aplicativo que gerou a manuteção da parametrização. | - |
|  | PARAM/infPARAM/ | versao | A | C | 1-1 | 1-4V2 | Versão do leiaute. |  |
| 12 | PARAM/infPARAM/ | contribuintes | G | - | 1-1 | - | Grupo de informações da manutenção do processamento dos contribuinte | - |
|  | PARAM/infPARAM/contribuintes/ | infContrib | G | - | 1-1000 | - | Grupo de informações dos contribuintes |  |
| 14 | PARAM/infPARAM/InfContrib/contribuintes/ | CNPJ | CE | N | 1-1 | 14 | Número da inscrição federal (CNPJ) do contribuinte do município. | - |
| 16 | PARAM/infPARAM/InfContrib/contribuintes/ | CPF | CE | N | 1-1 | 11 | Número da inscrição federal (CPF) do contribuinte do município. | - |
| 17 | PARAM/infPARAM/InfContrib/contribuintes/ | cManut | E | C | 1-1 | 1 | Código da manutenção da parametrização a ser realizada:<br><br>0 - Finalizar Vigência;<br>1 - Iniciar Vigência; | - |
| 18 | PARAM/infPARAM/InfContrib/contribuintes/ | dtVig | E | D | 1-1 | - | Data informada para iniciar ou finalizar vigência de um registro na parametrização. | - |
| 19 | PARAM/infPARAM/InfContrib/contribuintes/ | idLeg | E | C | 1-1 | 14 | Identificador da legislação já cadastrada do munícipio. | - |
| 20 | PARAM/ | Signature | G | - | 1-1 | - | Assinatura XML da NFS-e Segundo o Padrão XML Digital Signature<br>Obrigatório quando for enviado para API.<br>Demais casos poderão ser opcionais a serem definidos em regra de validação. | - |

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
Cell: A14
Formula: =ROW(A12)
Cached value: 12
Display value: 12
Type: number
Number format: General
```

```excel-formula
Cell: A16
Formula: =ROW(A14)
Cached value: 14
Display value: 14
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

## RN_PAR

| # | CAMINHO NO XML | CAMPO | REGRAS DE NEGÓCIO |  | APLIC. | EFEITO | CÓD. ERRO |  | NOTAS EXPLICATIVAS |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| 1 |  | PARAM | - |  | - | - | - | - | - |
| 2 | PARAM/ | versao | Prazo de aceitação da versão do leiaute CNC ultrapassado. |  | Obrig. | Rej. | E4000 | O prazo de aceitação da versão do leiaute expirou. | - |
| 3 | PARAM/ | infPARAM | - |  | - | - | - | - | - |
| 4 | PARAM/infPARAM/ | Id | Verificar se o código do município está de acordo com o campo cMun do leiaute PAR. |  | Obrig. | Rej. | E4001 | O código do município do id não está de acordo com a informação do campo cMun do leiaute. | - |
| 5 |  |  | Verificar se o tipo de ambiente está de acordo com o campo tpAmb do leiaute PAR. |  | Obrig. | Rej. | E4002 | O tipo de ambiente do id não está de acordo com a informação do campo tpAmb do leiaute. | - |
| 6 | PARAM/infPARAM/ | cMun | O código do município deve corresponder a um código de município válido, conforme tabela de municipios do IBGE. |  | Obrig. | Rej. | E4004 | O código do município informado não corresponde a um código de município válido, conforme tabela de municipios do IBGE.	 | Conforme AnexoA-Tabelas_ISO2Paises_MunIBGE_Rodovias-SNNFSe. |
| 7 |  |  | O código do município deve estar conveniado ao Sistema Nacional NFS-e e estar ativo. |  | Obrig. | Rej. | E4005 | O código do município informado está conveniado ao Sistema Nacional NFS-e, mas não está ativo. | - |
| 8 |  |  | O código do município deve estar associado ao CNPJ de transmissão e existir no cadastro de convênio municipal do sistema nacional. |  | Obrig. | Rej. | E4006 | O código do município não existe no cadastro de convênio municipal do sistema nacional. | - |
| 9 | PARAM/infPARAM/ | CNPJ | CNPJ informado é inválido (verificar DV). |  | Obrig. | Rej. | E4007 | CNPJ do agente tributário informado é inválido. |  |
| 10 |  |  | O CNPJ do município deve corresponder ao CNPJ de transmissão. |  | Obrig. | Rej. | E4008 | O CNPJ do município informado não corresponde ao CNPJ de transmissão. | - |
| 11 |  |  | O CNPJ do município e deve estar registrado no cadastro de convênio municipal do sistema nacional e estar ativo. |  | Obrig. | Rej. | E4009 | O CNPJ do município informado não existe no cadastro de convênio municipal do sistema nacional. | - |
| 12 |  |  | O CNPJ do município existe no cadastro de convênio municipal do sistema nacional, mas está suspenso. |  | Obrig. | Rej. | E4010 | O código do município informado existe, mas está suspenso no convênio municipal. | - |
| 13 |  |  | O CNPJ do município existe no cadastro de convênio municipal do sistema nacional, mas está inativo. |  | Obrig. | Rej. | E4011 | O código do município informado existe, mas não está ativo há mais de <número de dias> dias no cadastro de convênio municipal. | - |
| 14 | PARAM/infPARAM/ | CPFAgTrib | CPF informado é inválido (verificar DV). |  | Obrig. | Rej. | E4012 | CPF do agente tributário informado é inválido. | - |
| 15 | PARAM/infPARAM/ | idPar | Verificar se o identificador do parâmetro pertence ao município que está realizando a manutenção da parametrização.<br>(Verificar se os 7 primeiros digitos do identificador da paramentrização <br>correspondem ao código do município informado). |  | Obrig. | Rej. | E4013 | O identificador da parametrização (idPar) informado não pertencem ao município informado (cMun). | - |
| 16 |  |  | Verificar se o identificador do parâmetro pertence ao tipo de parâmetro manutenido. |  | Obrig. | Rej. | E4014 | O identificador da parametrização não encontrado. |  |
| 17 |  |  | Não pode haver manutenção em parametrizações que não estejam vigentes.<br>(Verificar se a parametarização manutenida possui data final de vigência). |  | Obrig. | Rej. | E4015 | A parametrização informada não está mais vigente. | - |
| 18 | PARAM/infPARAM/ | tpAmb | Ambiente informado diverge do ambiente de recebimento para o qual o município enviou a manutenção do parâmetro. |  | Obrig. | Rej. | E4017 | Ambiente informado diverge do ambiente de recebimento para o qual o município enviou a manutenção do parâmetro. | - |
| 19 | PARAM/infPARAM/ | dhEmi | A data de emissão da manutenção da parametrização deve ser igual à data de processamento <br>(data atual). |  | Obrig. | Rej. | E4016 | A data de emissão da manutenção da parametrização deve ser igual à data de processamento (data atual). | - |
| 20 | PARAM/infPARAM/ | verAplic | - |  | - | - | - | - | - |
|  | PARAM/infPARAM/ | contribuintes | - |  | - | - | - | - |  |
| 21 | PARAM/infPARAM/contribuintes/ | infContrib | - |  | - | - | - | - | - |
| 23 | PARAM/infPARAM/contribuintes/infContrib/ | CNPJ | CNPJ informado é inválido (verificar DV). |  | Obrig. | Rej. | E4018 | CNPJ do contribuinte informado é inválido. | - |
| 26 |  |  | Verificar se o CNPJ do contribuinte informado para inclusão em parametrização de Regime Especial Tributação, existe e está vigente em outra parametrização de Regime Especial de Tributação para o mesmo município. |  | Obrig. | Rej. | E4022 | Não é possível registrar o CNPJ informado pois o mesmo está vigente em outra parametrização para Regime Especial de Tributação. |  |
| 27 | PARAM/infPARAM/contribuintes/infContrib/ | CPF | CPF informado é inválido (verificar DV). |  | Obrig. | Rej. | E4019 | CPF do contribuinte informado é inválido. | - |
| 28 |  |  | Verificar se o CPF do contribuinte informado para inclusão em parametrização de Regime Especial Tributação, existe e está vigente em outra parametrização de Regime Especial de Tributação para o mesmo município. |  | Obrig. | Rej. | E4025 | Não é possível registrar o CPF informado pois o mesmo está vigente em outra parametrização para Regime Especial de Tributação. |  |
| 29 | PARAM/infPARAM/contribuintes/infContrib/ | cManut | - |  | - | - | - | - | - |
| 30 | PARAM/infPARAM/contribuintes/infContrib/ | dtVig | Verificar se a data de vigência informada é maior ou igual à data de início de vigência da parametrização manutenida. |  | Obrig. | Rej. | E4020 | A data de vigência informada é menor que a data de início de vigência da parametrização manutenida. | - |
| 31 |  |  | Quando a manuteção for para iniciar uma vigência de registro (cManut = 1) e existir o CNPJ informado na parametrização, verificar se já não existe registro deste CNPJ sem a data final de vigência. |  | Obrig. | Rej. | E4021 | Não é possível registar o CNPJ informado pois o mesmo está vigente nesta parametrização informada. | - |
| 30 |  |  | Quando a manuteção for para iniciar uma vigência de registro (cManut = 1) e existir o CNPJ informado na parametrização com a data final de vigência do registro, verificar se a data de início de vigência informada é maior que a data final de vigência do registro mais recente. |  | Obrig. | Rej. | E4023 | Não é possível registar o CNPJ informado pois a data de início de vigência informada é menor ou igual à data final da última vigência deste CNPJ,<informar data final da última vigência do CNPJ>.  | - |
| 31 |  |  | Quando a manuteção for para iniciar uma vigência de registro (cManut = 1) e existir o CPF informado na parametrização, verificar se já não existe registro deste CPF sem a data final de vigência. |  | Obrig. | Rej. | E4024 | Não é possível registar o CPF informado pois o mesmo está vigente nesta parametrização informada. | - |
| 32 |  |  | Quando a manuteção for para iniciar uma vigência de registro (cManut = 1) e existir o CPF informado na parametrização com a data final de vigência do registro, verificar se a data de início de vigência informada é maior que a data final de vigência do registro mais recente. |  | Obrig. | Rej. | E4026 | Não é possível registar o CPF informado pois a data de início de vigência informada é menor ou igual à data final da última vigência deste CPF ,<informar data final da última vigência do CPF >.  | - |
| 33 |  |  | Quando a manuteção for para finalizar uma vigência de registro (cManut = 0), verificar se existe registro deste CNPJ. |  | Obrig. | Rej. | E4027 | Não é possível registar o fim de vigência do CNPJ informado pois o mesmo não existe nesta parametrização informada. | - |
| 34 |  |  | Quando a manuteção for para finalizar uma vigência de registro (cManut = 0) e existir o CNPJ informado na parametrização, verificar se não existe registro deste CNPJ sem a data final de vigência. |  | Obrig. | Rej. | E4028 | Não é possível registar o fim de vigência do CNPJ informado pois o mesmo não está vigente na parametrização informada. | - |
| 35 |  |  | Quando a manuteção for para finalizar uma vigência de registro (cManut = 0) e existir o CNPJ informado na parametrização sem a data final de vigência, verificar se a data de fim de vigência informada é menor que a data de início de vigência do registro do CNPJ. |  | Obrig. | Rej. | E4029 | Não é possível registar o fim de vigência do CNPJ informado pois a data para fim de vigência informada é menor que a data de início de vigência encontrada para este CNPJ na parametrização informada <informar data inicial da última vigência do CNPJ>. | - |
| 36 |  |  | Quando a manuteção for para finalizar uma vigência de registro (cManut = 0), verificar se existe registro deste CPF. |  | Obrig. | Rej. | E4030 | Não é possível registar o fim de vigência do CPF informado pois o mesmo não existe nesta parametrização informada. | - |
| 37 |  |  | Quando a manuteção for para finalizar uma vigência de registro (cManut = 0) e existir o CPF informado na parametrização, verificar se não existe registro deste CPF sem a data final de vigência. |  | Obrig. | Rej. | E4031 | Não é possível registar o fim de vigência do CPF informado pois o mesmo já não está vigente na parametrização informada. | - |
| 38 |  |  | Quando a manuteção for para finalizar uma vigência de registro (cManut = 0) e existir o CPF informado na parametrização sem a data final de vigência, verificar se a data de fim de vigência informada é menor que a data de início de vigência do registro do CPF . |  | Obrig. | Rej. | E4032 | Não é possível registar o fim de vigência do CPF informado pois a data para fim de vigência informada é menor à data de início de vigência encontrada para este CPF  na parametrização informada <informar data inicial da última vigência do CPF >. | - |
| 39 | PARAM/infPARAM/contribuintes/infContrib/ | idLeg | Verificar se o identificador da legislação pertence ao município que está realizando a manutenção da parametrização.<br>(Verificar se os 7 primeiros dígitos do identificador da legislação <br>correspondem ao código do município informado). |  | Obrig. | Rej. | E4033 | O identificador da legislação (idLeg) informado não pertencem ao município informado (cMun). | - |
| 40 |  |  | Verificar se o identificador da legislação existe no município que está realizando a manutenção da parametrização. |  | Obrig. | Rej. | E4034 | O identificador da legislação (idLeg) informado não existe para o município informado (cMun). |  |
| 41 | PARAM/ | Signature | A assinatura da PARAM deve ser válida. |  | Obrig. | Rej. | E4046 | Arquivo enviado com erro na assinatura. | - |
| 42 |  |  | Verificar a validade do Certificado Digital da assinatura. |  | Obrig. | Rej. | E0534 | Certificado expirado. | - |
| 43 |  |  | Certificado Digital da assinatura fora do padrão estabelecido pelo projeto NFS-e:<br><br>- Versão diferente de 3;<br>- Se informado, Basic Constraint deve ser true (não pode ser Certificado de AC);<br>- KeyUsage não define 'Assinatura Digital' e 'Não Recusa';<br>- Falta a extensão de CNPJ (OtherName - OID=2.16.76.1.3.3) ou CPF (OtherName - OID=2.16.76.1.3.1);<br>- Certificado Raiz difere da 'ICP-Brasil'. |  | Obrig. | Rej. | E0528 | Certificado Digital fora do padrão estabelecido. | - |
| 44 |  |  | É obrigatória a existência da assinatura da PARAM quando for enviado para a API.<br> |  | Obrig. | Rej. | E0530 | A assinatura é obrigatória quando for enviado para a API. | - |
| 45 |  |  | A assinatura deve ser feita com o certificado digital do Municipio da PARAM. |  | Obrig. | Rej. | E0532 | A assinatura deve ser feita com o certificado digital do município da parametrização.<br> | - |

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
Cell: A23
Formula: =ROW(A21)
Cached value: 21
Display value: 21
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
Formula: =ROW(A26)
Cached value: 26
Display value: 26
Type: number
Number format: General
```

```excel-formula
Cell: A26
Formula: =ROW(A27)
Cached value: 27
Display value: 27
Type: number
Number format: General
```

```excel-formula
Cell: A27
Formula: =ROW(A28)
Cached value: 28
Display value: 28
Type: number
Number format: General
```

```excel-formula
Cell: A28
Formula: =ROW(A29)
Cached value: 29
Display value: 29
Type: number
Number format: General
```

```excel-formula
Cell: A29
Formula: =ROW(A30)
Cached value: 30
Display value: 30
Type: number
Number format: General
```

```excel-formula
Cell: A30
Formula: =ROW(A31)
Cached value: 31
Display value: 31
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
Cell: A32
Formula: =ROW(A31)
Cached value: 31
Display value: 31
Type: number
Number format: General
```

```excel-formula
Cell: A33
Formula: =ROW(A32)
Cached value: 32
Display value: 32
Type: number
Number format: General
```

```excel-formula
Cell: A34
Formula: =ROW(A33)
Cached value: 33
Display value: 33
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

```excel-formula
Cell: A38
Formula: =ROW(A37)
Cached value: 37
Display value: 37
Type: number
Number format: General
```

```excel-formula
Cell: A39
Formula: =ROW(A38)
Cached value: 38
Display value: 38
Type: number
Number format: General
```

```excel-formula
Cell: A40
Formula: =ROW(A39)
Cached value: 39
Display value: 39
Type: number
Number format: General
```

```excel-formula
Cell: A41
Formula: =ROW(A40)
Cached value: 40
Display value: 40
Type: number
Number format: General
```

```excel-formula
Cell: A42
Formula: =ROW(A41)
Cached value: 41
Display value: 41
Type: number
Number format: General
```

```excel-formula
Cell: A43
Formula: =ROW(A42)
Cached value: 42
Display value: 42
Type: number
Number format: General
```

```excel-formula
Cell: A44
Formula: =ROW(A43)
Cached value: 43
Display value: 43
Type: number
Number format: General
```

```excel-formula
Cell: A45
Formula: =ROW(A44)
Cached value: 44
Display value: 44
Type: number
Number format: General
```

```excel-formula
Cell: A46
Formula: =ROW(A45)
Cached value: 45
Display value: 45
Type: number
Number format: General
```

## Workbook images and drawings

Named range semantics

{
  "name": "_xlnm._FilterDatabase",
  "expression": "LEIAUTE_PAR!$A$1:$I$19",
  "scope": "worksheet",
  "sheet": "LEIAUTE_PAR",
  "hidden": true
}

Named range semantics

{
  "name": "_xlnm._FilterDatabase",
  "expression": "RN_PAR!$A$1:$J$40",
  "scope": "worksheet",
  "sheet": "RN_PAR",
  "hidden": true
}