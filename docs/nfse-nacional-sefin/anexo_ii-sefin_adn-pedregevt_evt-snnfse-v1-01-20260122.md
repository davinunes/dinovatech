## TIPO EVENTOS DE NFSe

| # | EVENTOS DE NFS-e  |  | CÓDIGO<br>Categ./Autor/Amb./Seq. | GRUPOS<br>CATEGORIA DE EVENTOS |  | AUTOR DO <br>PEDIDO DE REGISTRO DE EVENTO | ASSINATURA DIGITAL OBRIGATÓRIA NO PEDREGEVT | AMBIENTE RECEPTOR DO <br>PEDIDO DE REGISTRO DE EVENTO |  | NFS-e PRECISA EXISTIR NO ADN? | EVENTO ÚNICO? | VISIBILIDADE |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| 1 | Cancelamento de NFS-e |  | 1 01 1 01 | NFS-e | 1 - Cancelamentos | Emite | Sim | 1 - Sistema que gerou a NFS-e<br>(Sistema próprio do município ou Sefin Nacional NFS-e) |  | Sim | Sim | EM / NE / CP / AT |
| 2 | Cancelamento de NFS-e por Substituição |  | 1 05 1 02 |  |  | MEmis | - | 1 - Sistema que gerou a NFS-e<br>(Sistema próprio do município ou Sefin Nacional NFS-e) |  | Sim | Sim | EM / NE / CP / AT |
| 3 | Solicitação de Análise Fiscal para Cancelamento de NFS-e |  | 1 01 1 03 |  |  | Emite | Sim | 1 - Sistema que gerou a NFS-e<br>(Sistema próprio do município ou Sefin Nacional NFS-e) |  | Sim | Sim | EM / NE / AT |
| 4 | Cancelamento de NFS-e Deferido por Análise Fiscal |  | 1 05 1 04 |  |  | MEmis | - | 1 - Sistema que gerou a NFS-e<br>(Sistema próprio do município ou Sefin Nacional NFS-e) |  | Sim | Sim | EM / NE / AT |
| 5 | Cancelamento de NFS-e Indeferido por Análise Fiscal |  | 1 05 1 05 |  |  | MEmis | - | 1 - Sistema que gerou a NFS-e<br>(Sistema próprio do município ou Sefin Nacional NFS-e) |  | Sim | Sim | EM / NE / AT |
| 6 | Manifestação de NFS-e | Confirmação do Prestador | 2 02 2 01 |  | 2 - Manifestações | Emite <br>(Prestador) | Não | 2 - ADN |  | Sim | Sim | EM / NE / CP / AT |
| 7 |  | Confirmação do Tomador | 2 03 2 02 |  |  | Emite<br>(Tomador) | Não |  |  | Sim | Sim | EM / NE / CP / AT |
| 8 |  | Confirmação do Intermediário | 2 04 2 03 |  |  | Emite<br>(Intermediário) | Não |  |  | Sim | Sim | EM / NE / CP / AT |
| 9 |  | Confirmação Tácita | 2 05 2 04 |  |  | MIncid  | - | 1 - Sistema que gerou a NFS-e<br>(Sistema próprio do município ou Sefin Nacional NFS-e)<br><br>2 - ADN |  | Sim | Sim | EM / NE / CP / AT |
| 10 |  | Rejeição do Prestador | 2 02 2 05 |  |  | Emite<br>(Prestador) | Não | 2 - ADN |  | Sim | Sim | EM / NE / CP / AT |
| 11 |  | Rejeição do Tomador | 2 03 2 06 |  |  | Emite<br>(Tomador) | Não |  |  | Sim | Sim | EM / NE / CP / AT |
| 12 |  | Rejeição do Intermediário | 2 04 2 07 |  |  | Emite<br>(Intermediário) | Não |  |  | Sim | Sim | EM / NE / CP / AT |
| 13 |  | Anulação da Rejeição | 2 05 2 08 |  |  | MIncid | - |  |  | Sim | Sim | EM / NE / CP / AT |
| 14 | Cancelamento de NFS-e por Ofício |  | 3 05 1 01 |  | 3 - Ofícios | MEmis | - | 1 - Sistema que gerou a NFS-e<br>(Sistema próprio do município ou Sefin Nacional NFS-e) |  | Sim | Sim | EM / NE / CP / AT |
| 15 | Bloqueio de NFS-e por Ofício para:<br><br>Ev. Cancelamento de NFS-e;<br>Ev. Cancelamento de NFS-e por Substituição;<br>Ev. Cancelamento de NFS-e Deferido por Análise Fiscal;<br>Ev. Cancelamento de NFS-e Indeferido por Análise Fiscal;<br>Ev. Cancelamento de NFS-e por Ofício; |  | 3 05 1 02 |  |  | MEmis | - | 1 - Sistema que gerou a NFS-e<br>(Sistema próprio do município ou Sefin Nacional NFS-e) |  | Sim | Não | EM / AT |
| 16 | Desbloqueio de NFS-e por Ofício para:<br><br>Ev. Cancelamento de NFS-e;<br>Ev. Cancelamento de NFS-e por Substituição;<br>Ev. Cancelamento de NFS-e Deferido por Análise Fiscal;<br>Ev. Cancelamento de NFS-e Indeferido por Análise Fiscal;<br>Ev. Cancelamento de NFS-e por Ofício; |  | 3 05 1 03 |  |  | MEmis | - | 1 - Sistema que gerou a NFS-e<br>(Sistema próprio do município ou Sefin Nacional NFS-e)	 |  | Sim | Não | EM / AT |
| CÓDIGO DO EVENTO:<br><br>O código identificador do evento é formado por 6 dígitos. <br>O 1º dígito identifica a categoria do evento;<br>Os 2º e 3º dígitos identificam o(s) autor(es) do(s) evento(s); <br>O 4º dígito identifica o ambiente receptor do evento e;<br>Os dois últimos dígitos, 5º e 6º, são números crescentes por categorias de eventos; |  |  | AUTOR DO PEDIDO DE REGISTRO DE EVENTO:<br><br>01 - Emite - Emitente da NFS-e;<br>02 - Prestador;<br>03 - Tomador;<br>04 - Intermediário;<br>05 - MEmis - Município Emissor;<br>06 - MIncid - Município de Incidência;<br>07 - Man - Módulo de Apuração Nacional;<br>08 - RespTrib - Responsável Tributário<br>56 - MEmis \| MInci <br>67 - MInci \| Man<br>99 - CGNFSe - Comitê Gestor da NFS-e |  |  | VISIBILIDADE: <br><br>EM - Emitente NFS-e;<br>NE - Não Emitente;<br>SP - Sujeito Passivo;<br>CP - Consulta Pública;<br>AT - Administração Tributária;*<br><br>* Município Emissor da NFS-e, Município(s) do(s) Não Emitente(s) da NFS-e, Município de Incidência do ISSQN e Município do local da prestação do serviço. Conforme regra de negócio a visibilidade poderá ser restrita a apenas algum(uns) desses municípios descritos que compõem a AT;<br><br>** Somente para NFS-e cujo cStat seja "102 - NFS-e de Decisão Judicial"; |  |  |  |  |  |  |

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

## RN EVENTOSxEVENTOS

|  |  |  |  | RECEPÇAO DE PEDIDOS DE REGISTROS DE EVENTOS NO SISTEMA NACIONAL NFS-e |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| # | PEDIDOS DE REGISTRO DOS EVENTOS DE NFS-e <br>RECEPCIONADOS NO SISTEMA AUTORIZADOR <br>APÓS UM EVENTO PRÉ-EXISTENTE <br>JÁ ESTAR RELACIONADO À UMA NFS-e |  |  | AO RECEBER OS POSSÍVEIS PEDIDOS DE REGISTROS DOS EVENTOS ABAIXO, O SISTEMA NACIONAL NFS-e PODE ACEITAR OU REJEITAR <br>O PEDIDO DE REGISTRO DE EVENTO RECEBIDO OU EVENTO COMPARTILHADO, CONFORME INDICADO PELOS SINAIS V (Permitir) OU X (Não é Permitido), RESPECTIVAMENTE |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |
|  |  |  |  | Cancelamento <br>de NFS-e | Cancelamento <br>de NFS-e<br>por Substituição | Solicitação de Análise Fiscal para Cancelamento de NFS-e | Cancelamento <br>de NFS-e Deferido <br>por Análise Fiscal | Cancelamento <br>de NFS-e Indeferido <br>por Análise Fiscal | Manifestação de NFS-e - <br>Confirmação do Prestador | Manifestação de NFS-e -<br>Confirmação do Tomador | Manifestação de NFS-e -<br>Confirmação do Intermediário | Manifestação de NFS-e -<br>Confirmação Tácita | Manifestação de NFS-e -<br>Rejeição do Prestador | Manifestação de NFS-e -<br>Rejeição do Tomador | Manifestação de NFS-e -<br>Rejeição do Intermediário | Manifestação de NFS-e<br>- Anulação da Rejeição | Cancelamento de NFS-e <br>por Ofício | Bloqueio de NFS-e por Ofício<br>(É permitido SOMENTE para eventos que ainda não tenham sido bloqueados ou que já foram desbloqueados) |  |  |  |  | Desbloqueio de NFS-e por Ofício<br>(É permitido SOMENTE se houver um bloqueio pendente e <br>se o desbloqueio corresponder exatamente ao identificador do bloqueio) |  |  |  |  |
|  | EVENTOS DE NFS-e PRÉ-EXISTENTES NO <br>SISTEMA AUTORIZADOR E <br>RELACIONADOS À NFS-e<br><br> |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  | Cancelamento de NFS-e | Cancelamento <br>de NFS-e por Substituição | Cancelamento <br>de NFS-e Deferido por Análise Fiscal | Cancelamento <br>de NFS-e Indeferido por Análise Fiscal | Cancelamento <br>de NFS-e por Ofício | Cancelamento <br>de NFS-e | Cancelamento <br>de NFS-e por Substituição | Cancelamento <br>de NFS-e Deferido por Análise Fiscal | Cancelamento <br>de NFS-e Indeferido por Análise Fiscal | Cancelamento <br>de NFS-e por Ofício |
| 1 |  | NENHUM EVENTO PRÉ-EXISTENTE |  | V | V | V | X | X | V | V | V | V | V | V | V | V | V | V | V | V | V | V | X | X | X | X | X |
| 2 |  | Cancelamento de NFS-e |  | X | X | X | X | X | X | X | X | X | X | X | X | X | X | X | X | X | X | X | X | X | X | X | X |
| 3 |  | Cancelamento de NFS-e por Substituição |  | X | X | X | X | X | X | X | X | X | X | X | X | X | X | X | X | X | X | X | X | X | X | X | X |
| 4 |  | Solicitação de Análise Fiscal para Cancelamento de NFS-e |  | X | X | X | V | V | V | V | V | V | V | V | V | V | X | V | V | V | V | V | V | V | V | V | V |
| 5 |  | Cancelamento de NFS-e Deferido por Análise Fiscal |  | X | X | X | X | X | X | X | X | X | X | X | X | X | X | X | X | X | X | X | X | X | X | X | X |
| 6 |  | Cancelamento de NFS-e Indeferido por Análise Fiscal |  | X | X | X | X | X | V | V | V | V | V | V | V | V | V | V | V | V | V | V | V | V | V | V | V |
| 7 |  | Manifestação de NFS-e - Confirmação do Prestador |  | X | V | X | X | X | X | X/V | X/V | X | V | X/V | X/V | V | V | V | V | V | V | V | V | V | V | V | V |
| 8 |  | Manifestação de NFS-e - Confirmação do Tomador |  | X | V | X | X | X | X/V | X | X/V | X | X/V | V | X/V | V | V | V | V | V | V | V | V | V | V | V | V |
| 9 |  | Manifestação de NFS-e - Confirmação do Intermediário |  | X | V | X | X | X | X/V | X/V | X | X | X/V | X/V | V | V | V | V | V | V | V | V | V | V | V | V | V |
| 10 |  | Manifestação de NFS-e - Confirmação Tácita |  | X | V | V | V | V | X | X | X | X | X | X | X | X | V | V | V | V | V | V | V | V | V | V | V |
| 11 |  | Manifestação de NFS-e - Rejeição do Prestador |  | V | V | V | V | V | V | X/V | X/V | X | X | X/V | X/V | V | V | V | V | V | V | V | V | V | V | V | V |
| 12 |  | Manifestação de NFS-e - Rejeição do Tomador |  | V | V | V | V | V | X/V | V | X/V | X | X/V | X | X/V | V | V | V | V | V | V | V | V | V | V | V | V |
| 13 |  | Manifestação de NFS-e - Rejeição do Intermediário |  | V | V | V | V | V | X/V | X/V | V | X | X/V | X/V | X | V | V | V | V | V | V | V | V | V | V | V | V |
| 14 |  | Manifestação de NFS-e - Anulação Rejeição |  | X | V | V | V | V | V | V | V | V | X/V | X/V | X/V | X | V | V | V | V | V | V | V | V | V | V | V |
| 15 |  | Cancelamento de NFS-e por Ofício |  | X | X | X | X | X | X | X | X | X | X | X | X | X | X | X | X | X | X | X | X | X | X | X | X |
| 16 |  | Bloqueio de NFS-e <br>por Ofício para: | Ev. de Cancelamento de NFS-e | X/V | V | V | V | V | V | V | V | V | V | V | V | V | V | X/V | V | V | V | V | V | V | V | V | V |
| 17 |  |  | Ev. de Cancelamento de NFS-e por Substituição | V | X/V | V | V | V | V | V | V | V | V | V | V | V | V | V | X/V | V | V | V | V | V | V | V | V |
| 18 |  |  | Ev. de Cancelamento de NFS-e Deferido por Análise Fiscal | V | V | V | X/V | V | V | V | V | V | V | V | V | V | V | V | V | X/V | V | V | V | V | V | V | V |
| 19 |  |  | Ev. de Cancelamento de NFS-e Indeferido por Análise Fiscal | V | V | V | V | X/V | V | V | V | V | V | V | V | V | V | V | V | V | X/V | V | V | V | V | V | V |
| 20 |  |  | Ev. de Cancelamento de NFS-e por Ofício | V | V | V | V | V | V | V | V | V | V | V | V | V | X/V | V | V | V | V | X/V | V | V | V | V | V |
| 21 |  | Desbloqueio de NFS-e <br>por Ofício de: | Ev. de Cancelamento de NFS-e | V | V | V | V | V | V | V | V | V | V | V | V | V | V | V | V | V | V | V | X | V | V | V | V |
| 22 |  |  | Ev. de Cancelamento de NFS-e por Substituição | V | V | V | V | V | V | V | V | V | V | V | V | V | V | V | V | V | V | V | V | X | V | V | V |
| 23 |  |  | Ev. de Cancelamento de NFS-e Deferido por Análise Fiscal | V | V | V | V | V | V | V | V | V | V | V | V | V | V | V | V | V | V | V | V | V | X | V | V |
| 24 |  |  | Ev. de Cancelamento de NFS-e Indeferido por Análise Fiscal | V | V | V | V | V | V | V | V | V | V | V | V | V | V | V | V | V | V | V | V | V | V | X | V |
| 25 |  |  | Ev. de Cancelamento de NFS-e por Ofício | V | V | V | V | V | V | V | V | V | V | V | V | V | V | V | V | V | V | V | V | V | V | V | X |

### Formula sources

```excel-formula
Cell: A5
Formula: =ROW(A1)
Cached value: 1
Display value: 1
Type: number
Number format: General
```

```excel-formula
Cell: A6
Formula: =ROW(A2)
Cached value: 2
Display value: 2
Type: number
Number format: General
```

```excel-formula
Cell: A7
Formula: =ROW(A3)
Cached value: 3
Display value: 3
Type: number
Number format: General
```

```excel-formula
Cell: A8
Formula: =ROW(A4)
Cached value: 4
Display value: 4
Type: number
Number format: General
```

```excel-formula
Cell: A9
Formula: =ROW(A5)
Cached value: 5
Display value: 5
Type: number
Number format: General
```

```excel-formula
Cell: A10
Formula: =ROW(A6)
Cached value: 6
Display value: 6
Type: number
Number format: General
```

```excel-formula
Cell: A11
Formula: =ROW(A7)
Cached value: 7
Display value: 7
Type: number
Number format: General
```

```excel-formula
Cell: A12
Formula: =ROW(A8)
Cached value: 8
Display value: 8
Type: number
Number format: General
```

```excel-formula
Cell: A13
Formula: =ROW(A9)
Cached value: 9
Display value: 9
Type: number
Number format: General
```

```excel-formula
Cell: A14
Formula: =ROW(A10)
Cached value: 10
Display value: 10
Type: number
Number format: General
```

```excel-formula
Cell: A15
Formula: =ROW(A11)
Cached value: 11
Display value: 11
Type: number
Number format: General
```

```excel-formula
Cell: A16
Formula: =ROW(A12)
Cached value: 12
Display value: 12
Type: number
Number format: General
```

```excel-formula
Cell: A17
Formula: =ROW(A13)
Cached value: 13
Display value: 13
Type: number
Number format: General
```

```excel-formula
Cell: A18
Formula: =ROW(A14)
Cached value: 14
Display value: 14
Type: number
Number format: General
```

```excel-formula
Cell: A19
Formula: =ROW(A15)
Cached value: 15
Display value: 15
Type: number
Number format: General
```

```excel-formula
Cell: A20
Formula: =ROW(A16)
Cached value: 16
Display value: 16
Type: number
Number format: General
```

```excel-formula
Cell: A21
Formula: =ROW(A17)
Cached value: 17
Display value: 17
Type: number
Number format: General
```

```excel-formula
Cell: A22
Formula: =ROW(A18)
Cached value: 18
Display value: 18
Type: number
Number format: General
```

```excel-formula
Cell: A23
Formula: =ROW(A19)
Cached value: 19
Display value: 19
Type: number
Number format: General
```

```excel-formula
Cell: A24
Formula: =ROW(A20)
Cached value: 20
Display value: 20
Type: number
Number format: General
```

```excel-formula
Cell: A25
Formula: =ROW(A21)
Cached value: 21
Display value: 21
Type: number
Number format: General
```

```excel-formula
Cell: A26
Formula: =ROW(A22)
Cached value: 22
Display value: 22
Type: number
Number format: General
```

```excel-formula
Cell: A27
Formula: =ROW(A23)
Cached value: 23
Display value: 23
Type: number
Number format: General
```

```excel-formula
Cell: A28
Formula: =ROW(A24)
Cached value: 24
Display value: 24
Type: number
Number format: General
```

```excel-formula
Cell: A29
Formula: =ROW(A25)
Cached value: 25
Display value: 25
Type: number
Number format: General
```

### Cell notes and hyperlinks

```excel-cell-metadata
Cell: K11
Comment 1 type: note
Comment 1 author: sheetjsghost
Comment 1 text: X: Se o tomador for o emitente;

V: Se o tomador for o não emitente;
Comments hidden: no
```

```excel-cell-metadata
Cell: L11
Comment 1 type: note
Comment 1 author: sheetjsghost
Comment 1 text: X: Se o intermediário for o emitente;

V: Se o intermediário for o não emitente;
Comments hidden: no
```

```excel-cell-metadata
Cell: O11
Comment 1 type: note
Comment 1 author: sheetjsghost
Comment 1 text: X: Se o tomador for o emitente;

V: Se o tomador for o não emitente;
Comments hidden: no
```

```excel-cell-metadata
Cell: P11
Comment 1 type: note
Comment 1 author: sheetjsghost
Comment 1 text: X: Se o intermediário for o emitente;

V: Se o intermediário for o não emitente;
Comments hidden: no
```

```excel-cell-metadata
Cell: J12
Comment 1 type: note
Comment 1 author: sheetjsghost
Comment 1 text: X: Se o prestador for o emitente;

V: Se o prestador for o não emitente;
Comments hidden: no
```

```excel-cell-metadata
Cell: L12
Comment 1 type: note
Comment 1 author: sheetjsghost
Comment 1 text: X: Se o intermediário for o emitente;

V: Se o intermediário for o não emitente;
Comments hidden: no
```

```excel-cell-metadata
Cell: N12
Comment 1 type: note
Comment 1 author: sheetjsghost
Comment 1 text: X: Se o prestador for o emitente;

V: Se o prestador for o não emitente;
Comments hidden: no
```

```excel-cell-metadata
Cell: P12
Comment 1 type: note
Comment 1 author: sheetjsghost
Comment 1 text: X: Se o intermediário for o emitente;

V: Se o intermediário for o não emitente;
Comments hidden: no
```

```excel-cell-metadata
Cell: J13
Comment 1 type: note
Comment 1 author: sheetjsghost
Comment 1 text: X: Se o prestador for o emitente;

V: Se o prestador for o não emitente;
Comments hidden: no
```

```excel-cell-metadata
Cell: K13
Comment 1 type: note
Comment 1 author: sheetjsghost
Comment 1 text: X: Se o tomador for o emitente;

V: Se o tomador for o não emitente;
Comments hidden: no
```

```excel-cell-metadata
Cell: N13
Comment 1 type: note
Comment 1 author: sheetjsghost
Comment 1 text: X: Se o prestador for o emitente;

V: Se o prestador for o não emitente;
Comments hidden: no
```

```excel-cell-metadata
Cell: O13
Comment 1 type: note
Comment 1 author: sheetjsghost
Comment 1 text: X: Se o tomador for o emitente;

V: Se o tomador for o não emitente;
Comments hidden: no
```

```excel-cell-metadata
Cell: K15
Comment 1 type: note
Comment 1 author: sheetjsghost
Comment 1 text: X: Se o tomador for o emitente;

V: Se o tomador for o não emitente;
Comments hidden: no
```

```excel-cell-metadata
Cell: L15
Comment 1 type: note
Comment 1 author: sheetjsghost
Comment 1 text: X: Se o intermediário for o emitente;

V: Se o intermediário for o não emitente;
Comments hidden: no
```

```excel-cell-metadata
Cell: O15
Comment 1 type: note
Comment 1 author: sheetjsghost
Comment 1 text: X: Se o tomador for o emitente;

V: Se o tomador for o não emitente;
Comments hidden: no
```

```excel-cell-metadata
Cell: P15
Comment 1 type: note
Comment 1 author: sheetjsghost
Comment 1 text: X: Se o intermediário for o emitente;

V: Se o intermediário for o não emitente;
Comments hidden: no
```

```excel-cell-metadata
Cell: J16
Comment 1 type: note
Comment 1 author: sheetjsghost
Comment 1 text: X: Se o prestador for o emitente;

V: Se o prestador for o não emitente;
Comments hidden: no
```

```excel-cell-metadata
Cell: L16
Comment 1 type: note
Comment 1 author: sheetjsghost
Comment 1 text: X: Se o intermediário for o emitente;

V: Se o intermediário for o não emitente;
Comments hidden: no
```

```excel-cell-metadata
Cell: N16
Comment 1 type: note
Comment 1 author: sheetjsghost
Comment 1 text: X: Se o prestador for o emitente;

V: Se o prestador for o não emitente;
Comments hidden: no
```

```excel-cell-metadata
Cell: P16
Comment 1 type: note
Comment 1 author: sheetjsghost
Comment 1 text: X: Se o intermediário for o emitente;

V: Se o intermediário for o não emitente;
Comments hidden: no
```

```excel-cell-metadata
Cell: J17
Comment 1 type: note
Comment 1 author: sheetjsghost
Comment 1 text: X: Se o prestador for o emitente;

V: Se o prestador for o não emitente;
Comments hidden: no
```

```excel-cell-metadata
Cell: K17
Comment 1 type: note
Comment 1 author: sheetjsghost
Comment 1 text: X: Se o tomador for o emitente;

V: Se o tomador for o não emitente;
Comments hidden: no
```

```excel-cell-metadata
Cell: N17
Comment 1 type: note
Comment 1 author: sheetjsghost
Comment 1 text: X: Se o prestador for o emitente;

V: Se o prestador for o não emitente;
Comments hidden: no
```

```excel-cell-metadata
Cell: O17
Comment 1 type: note
Comment 1 author: sheetjsghost
Comment 1 text: X: Se o tomador for o emitente;

V: Se o tomador for o não emitente;
Comments hidden: no
```

```excel-cell-metadata
Cell: N18
Comment 1 type: note
Comment 1 author: sheetjsghost
Comment 1 text: X: Se o Evento de Manif. de  Rejeição do não emitente prestador já foi anulada;

V: Se o Evento de Manif. de  Rejeição do não emitente prestador não foi anulada;
Comments hidden: no
```

```excel-cell-metadata
Cell: O18
Comment 1 type: note
Comment 1 author: sheetjsghost
Comment 1 text: X: Se o Evento de Manif. de  Rejeição do não emitente tomador já foi anulada;

V: Se o Evento de Manif. de  Rejeição do não emitente tomador não foi anulada;
Comments hidden: no
```

```excel-cell-metadata
Cell: P18
Comment 1 type: note
Comment 1 author: sheetjsghost
Comment 1 text: X: Se o Evento de Manif. de  Rejeição do não emitente intermediário já foi anulada;

V: Se o Evento de Manif. de  Rejeição do não emitente intermediário não foi anulada;
Comments hidden: no
```

```excel-cell-metadata
Cell: C20
Comment 1 type: note
Comment 1 author: sheetjsghost
Comment 1 text: Depende do motivo
	-Gustavo Jubé
ok
	-Adriano Guedes
----
Depende do motivo
	-Gustavo Jubé
ok
	-Adriano Guedes
Comments hidden: no
```

```excel-cell-metadata
Cell: E20
Comment 1 type: note
Comment 1 author: sheetjsghost
Comment 1 text: X: Se não houver um desbloqueio para este evento;

V: Se houver um desbloqueio para este evento;
Comments hidden: no
```

```excel-cell-metadata
Cell: S20
Comment 1 type: note
Comment 1 author: sheetjsghost
Comment 1 text: V: Se NFS-e identificada no Bloqueio que está chegando, já possuir um bloqueio anterior para o mesmo tipo de evento COM o respectivo desbloqueio;

X: Se NFS-e identificada no Bloqueio que está chegando, já possuir um bloqueio anterior para o mesmo tipo de evento SEM o respectivo desbloqueio;
Comments hidden: no
```

```excel-cell-metadata
Cell: F21
Comment 1 type: note
Comment 1 author: sheetjsghost
Comment 1 text: X: Se não houver um desbloqueio para este evento;

V: Se houver um desbloqueio para este evento;
Comments hidden: no
```

```excel-cell-metadata
Cell: T21
Comment 1 type: note
Comment 1 author: sheetjsghost
Comment 1 text: V: Se NFS-e identificada no Bloqueio que está chegando, já possuir um bloqueio anterior para o mesmo tipo de evento COM o respectivo desbloqueio;

X: Se NFS-e identificada no Bloqueio que está chegando, já possuir um bloqueio anterior para o mesmo tipo de evento SEM o respectivo desbloqueio;
Comments hidden: no
```

```excel-cell-metadata
Cell: H22
Comment 1 type: note
Comment 1 author: sheetjsghost
Comment 1 text: X: Se não houver um desbloqueio para este evento;

V: Se houver um desbloqueio para este evento;
Comments hidden: no
```

```excel-cell-metadata
Cell: U22
Comment 1 type: note
Comment 1 author: sheetjsghost
Comment 1 text: V: Se NFS-e identificada no Bloqueio que está chegando, já possuir um bloqueio anterior para o mesmo tipo de evento COM o respectivo desbloqueio;

X: Se NFS-e identificada no Bloqueio que está chegando, já possuir um bloqueio anterior para o mesmo tipo de evento SEM o respectivo desbloqueio;
Comments hidden: no
```

```excel-cell-metadata
Cell: I23
Comment 1 type: note
Comment 1 author: sheetjsghost
Comment 1 text: X: Se não houver um desbloqueio para este evento;

V: Se houver um desbloqueio para este evento;
Comments hidden: no
```

```excel-cell-metadata
Cell: V23
Comment 1 type: note
Comment 1 author: sheetjsghost
Comment 1 text: V: Se NFS-e identificada no Bloqueio que está chegando, já possuir um bloqueio anterior para o mesmo tipo de evento COM o respectivo desbloqueio;

X: Se NFS-e identificada no Bloqueio que está chegando, já possuir um bloqueio anterior para o mesmo tipo de evento SEM o respectivo desbloqueio;
Comments hidden: no
```

```excel-cell-metadata
Cell: R24
Comment 1 type: note
Comment 1 author: sheetjsghost
Comment 1 text: X: Se não houver um desbloqueio para este evento;

V: Se houver um desbloqueio para este evento;
Comments hidden: no
```

```excel-cell-metadata
Cell: W24
Comment 1 type: note
Comment 1 author: sheetjsghost
Comment 1 text: V: Se NFS-e identificada no Bloqueio que está chegando, já possuir um bloqueio anterior para o mesmo tipo de evento COM o respectivo desbloqueio;

X: Se NFS-e identificada no Bloqueio que está chegando, já possuir um bloqueio anterior para o mesmo tipo de evento SEM o respectivo desbloqueio;
Comments hidden: no
```

## LEIAUTE EVENTO_PED.REG.EVENTO

| # | CAMINHO NO XML | CAMPO | ELE | TIPO | OCOR. | TAM. | DESCRIÇÃO | OBSERVAÇÕES DE NEGÓCIO |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| 1 |  | evento | Raiz | - | - | - | TAG raiz | - |
| 2 | evento/ | versao | A | C | 1-1 | 1-4V2 | Versão do leiaute do evento. | - |
| 3 | evento/ | infEvento | G | - | 1-1 | - | Grupo de informações do pedido de registro do evento | - |
| 4 | evento/infEvento/ | id | ID | C | 1-1 | 62 | O identificador do evento é composto pela concatenação de campos que constam no leiaute.<br><br>A formação deste identificador considera o literal "EVT" associado a outras 59 posições numéricas, conforme descrito abaixo:<br><br>"EVT" + <br>id do Pedido de Registo de Evento (56) + <br>Número sequencial do evento (nSeqEvento) (3) | Para a formação do id do Evento, considerar o id do Pedido de Registro de Evento sem o literal "PRE". |
| 5 | evento/infEvento/ | verAplic | E | C | 1-1 | 1-20 | Versão do aplicativo que gerou o evento. | - |
| 6 | evento/infEvento/ | ambGer | E | D | 1-1 | - | Ambiente gerador do evento:<br><br>1 - Sistema próprio do município;<br>2 - Sefin Nacional NFS-e;<br>3 - ADN NFS-e; | - |
| 7 | evento/infEvento/ | nSeqEvento | E | N | 1-1 | 3 | Número sequencial do evento para o mesmo tipo de evento.<br><br>Para os eventos que ocorrem somente uma vez, como é o caso do cancelamento, o nSeqEvento = 001. <br><br>Para os eventos que possam existir mais de um evento do mesmo tipo o ambiente gerador deverá numerar de forma sequencial. | - |
| 8 | evento/infEvento/ | dhProc | E | D | 1-1 | - | Data/Hora do registro do evento.<br>Data e hora no formato UTC (Universal Coordinated Time):<br>AAAA-MM-DDThh:mm:ssTZD | - |
| 9 | evento/infEvento/ | nDFSe | E | N | 1-1 | 1-13 | Número sequencial do documento gerado por ambiente gerador de DFSe do município. | - |
| 10 | evento/infEvento/ | pedRegEvento | G | G | 1-1 | - | Leiaute do pedido de registro do evento gerado pelo autor do evento | - |
| 11 | evento/pedRegEvento/ | versao | A | C | 1-1 | 1-4V2 | Versão do leiaute do pedido de registro do evento. | - |
| 12 | evento/pedRegEvento/ | infPedReg | G | - | 1-1 | - | Parte Geral do Pedido de Registro de Evento<br>Grupo de informações do pedido de registro do evento | - |
| 13 | evento/pedRegEvento/infPedReg/ | id | ID | C | 1-1 | 59 | O identificador do pedido de registro de evento é composto pela concatenação de campos que constam no leiaute.<br><br>A formação deste identificador considera o literal "PRE" associado a outras 56 posições numéricas, conforme descrito abaixo:<br><br>"PRE" + <br>Chave de acesso da NFS-e (50) +<br>Código do evento (6) | - |
| 14 | evento/pedRegEvento/infPedReg/ | tpAmb | E | N | 1-1 | 1 |  Tipo de ambiente:<br><br>1 - Produção; <br>2 - Homologação; | - |
| 15 | evento/pedRegEvento/infPedReg/ | verAplic | E | C | 1-1 | 1-20 | Versão do aplicativo que gerou o pedido de registro de evento. | - |
| 16 | evento/pedRegEvento/infPedReg/ | dhEvento | E | D | 1-1 | - | Data e hora do evento no formato AAAA-MM-DDThh:mm:ssTZD (UTC - Universal Coordinated Time, onde TZD pode ser -02:00 (Fernando de Noronha), -03:00 (Brasília) ou -04:00 (Manaus), no horário de verão serão -01:00, -02:00 e -03:00. Ex.: 2010-08-19T13:00:15-03:00. | - |
| 17 | evento/pedRegEvento/infPedReg/ | CNPJAutor | CE | N | 1-1 | 14 | Número de inscrição federal (CNPJ) do autor do evento.<br><br>CNPJ do autor do evento (parte interessada ou pessoa que figure na NFS-e.<br>O autor do evento não é o procurador). | Em eventos onde o autor é o fisco, o campo deverá ser preenchido com o CNPJ da prefeitura. |
| 18 | evento/pedRegEvento/infPedReg/ | CPFAutor | CE | N | 1-1 | 11 | Número de inscrição federal (CPF) do autor do evento.<br><br>CPF do autor do evento (parte interessada ou pessoa que figure na NFS-e como prestador, tomador, intermediário. O autor do evento poderá ser o procurador). | Em eventos onde o autor é o fisco, este campo não poderá ser preenchido.  |
| 19 | evento/pedRegEvento/infPedReg/ | chNFSe | CE | N | 1-1 | 50 | Identificador da NFS-e à qual o evento será vinculado. | - |
| 20 | evento/pedRegEvento/infPedReg/ | e101101 | CG | - | 1-1 | - | Parte Específica do Pedido de Registro de Evento<br>Evento Cancelamento de NFS-e | - |
| 21 | evento/pedRegEvento/infPedReg/e101101/ | xDesc | E | C | 1-1 | 5-60 | Descrição do evento: <br>Cancelamento de NFS-e | - |
| 22 | evento/pedRegEvento/infPedReg/e101101/ | cMotivo | E | N | 1-1 | 1 | Código de justificativa de cancelamento:<br><br>1 - Erro na Emissão;<br>2 - Serviço não Prestado;<br>9 - Outros; | - |
| 23 | evento/pedRegEvento/infPedReg/e101101/ | xMotivo | E | C | 1-1 | 15-255 | Descrição para explicitar o motivo indicado neste evento. | - |
| 24 | evento/pedRegEvento/infPedReg/ | e105102 | CG | - | 1-1 | - | Parte Específica do Pedido de Registro de Evento<br>Cancelamento de NFS-e por Substituição | - |
| 25 | evento/pedRegEvento/infPedReg/e105102/ | xDesc | E | C | 1-1 | 5-60 | Descrição do evento: <br>Cancelamento de NFS-e por Substituição | - |
| 26 | evento/pedRegEvento/infPedReg/e105102/ | cMotivo | E | N | 1-1 | 2 | Código de justificativa de cancelamento substituição:<br><br>01 - Desenquadramento de NFS-e do Simples Nacional;<br>02 - Enquadramento de NFS-e no Simples Nacional;<br>03 - Inclusão Retroativa de Imunidade/Isenção para NFS-e;<br>04 - Exclusão Retroativa de Imunidade/Isenção para NFS-e;<br>05 - Rejeição de NFS-e pelo tomador ou pelo intermediário se responsável pelo recolhimento do tributo;<br>99 - Outros;<br><br>Obtido do campo da DPS "DPS/infDPS/subst/cMotivo". | - |
| 27 | evento/pedRegEvento/infPedReg/e105102/ | xMotivo | E | C | 0-1 | 15-255 | Descrição para explicitar o motivo indicado neste evento.<br><br>Obtido do campo da DPS "DPS/infDPS/subst/xMotivo". | - |
| 28 | evento/pedRegEvento/infPedReg/e105102/ | chSubstituta | E | N | 1-1 | 50 | Chave de Acesso da NFS-e substituta. | - |
| 29 | evento/pedRegEvento/infPedReg/ | e101103 | CG | - | 1-1 | - | Parte Específica do Pedido de Registro de Evento<br>Solicitação de Análise Fiscal para Cancelamento de NFS-e | - |
| 30 | evento/pedRegEvento/infPedReg/e101103/ | xDesc | E | C | 1-1 | 5-60 | Descrição do evento:<br>Solicitação de Análise Fiscal para Cancelamento de NFS-e | - |
| 31 | evento/pedRegEvento/infPedReg/e101103/ | cMotivo | E | N | 1-1 | 1 | Código do motivo da solicitação de análise fiscal para cancelamento de NFS-e:<br><br>1 - Erro na Emissão;<br>2 - Serviço não Prestado;<br>9 - Outros; | - |
| 32 | evento/pedRegEvento/infPedReg/e101103/ | xMotivo | E | C | 1-1 | 15-255 | Descrição para explicitar o motivo indicado neste evento. | - |
| 33 | evento/pedRegEvento/infPedReg/ | e105104 | CG | - | 1-1 | - | Parte Específica do Pedido de Registro de Evento<br>Cancelamento de NFS-e Deferido por Análise Fiscal | - |
| 34 | evento/pedRegEvento/infPedReg/e105104/ | xDesc | E | C | 1-1 | 5-60 | Descrição do evento:<br>Cancelamento de NFS-e Deferido por Análise Fiscal | - |
| 35 | evento/pedRegEvento/infPedReg/e105104/ | CPFAgTrib | E | N | 1-1 | 11 | CPF do agente da administração tributária municipal que efetuou o deferimento da solicitação de análise fiscal para cancelamento de NFS-e. | - |
| 36 | evento/pedRegEvento/infPedReg/e105104/ | nProcAdm | E | N | 0-1 | 1-30 | Número do processo administrativo municipal vinculado à solicitação de análise fiscal para cancelamento de NFS-e. | - |
| 37 | evento/pedRegEvento/infPedReg/e105104/ | cMotivo | E | N | 1-1 | 1 | Resposta da solicitação de análise fiscal para cancelamento de NFS-e:<br><br>1 - Cancelamento de NFS-e Deferido; | - |
| 38 | evento/pedRegEvento/infPedReg/e105104/ | xMotivo | E | C | 1-1 | 15-255 | Descrição para explicitar o motivo indicado neste evento. | - |
| 39 | evento/pedRegEvento/infPedReg/ | e105105 | CG | - | 1-1 | - | Parte Específica do Pedido de Registro de Evento<br>Cancelamento de NFS-e Indeferido por Análise Fiscal | - |
| 40 | evento/pedRegEvento/infPedReg/e105105/ | xDesc | E | C | 1-1 | 5-60 | Descrição do evento:<br>Cancelamento de NFS-e Indeferido por Análise Fiscal | - |
| 41 | evento/pedRegEvento/infPedReg/e105105/ | CPFAgTrib | E | N | 1-1 | 11 | CPF do agente da administração tributária municipal que efetuou o indeferimento da solicitação de análise fiscal para cancelamento de NFS-e. | - |
| 42 | evento/pedRegEvento/infPedReg/e105105/ | nProcAdm | E | N | 0-1 | 1-30 | Número do processo administrativo municipal vinculado à solicitação de análise fiscal para cancelamento de NFS-e. | - |
| 43 | evento/pedRegEvento/infPedReg/e105105/ | cMotivo | E | N | 1-1 | 1 | Resposta da solicitação de análise fiscal para cancelamento de NFS-e:<br><br>1 - Cancelamento de NFS-e Indeferido;<br>2 - Cancelamento de NFS-e Indeferido Sem Análise de Mérito; | - |
| 44 | evento/pedRegEvento/infPedReg/e105105/ | xMotivo | E | C | 1-1 | 15-255 | Descrição para explicitar o motivo indicado neste evento. | - |
| 45 | evento/pedRegEvento/infPedReg/ | e202201 | CG | - | 1-1 | - | Parte Específica do Evento<br>Manifestação de NFS-e - Confirmação do Prestador | - |
| 46 | evento/pedRegEvento/infPedReg/e202201/ | xDesc | E | C | 1-1 | 5-60 | Descrição do evento:<br>Manifestação de NFS-e - Confirmação do Prestador | - |
| 47 | evento/pedRegEvento/infPedReg/ | e203202 | CG | - | 1-1 | - | Parte Específica do Evento<br>Manifestação de NFS-e - Confirmação do Tomador | - |
| 48 | evento/pedRegEvento/infPedReg/e203202/ | xDesc | E | C | 1-1 | 5-60 | Descrição do evento:<br>Manifestação de NFS-e - Confirmação do Tomador | - |
| 49 | evento/pedRegEvento/infPedReg/ | e204203 | CG | - | 1-1 | - | Parte Específica do Evento<br>Manifestação de NFS-e - Confirmação do Intermediário | - |
| 50 | evento/pedRegEvento/infPedReg/e204203/ | xDesc | E | C | 1-1 | 5-60 | Descrição do evento:<br>Manifestação de NFS-e - Confirmação do Intermediário | - |
| 51 | evento/pedRegEvento/infPedReg/ | e205204 | CG | - | 1-1 | - | Parte Específica do Evento<br>Manifestação de NFS-e - Confirmação Tácita | - |
| 52 | evento/pedRegEvento/infPedReg/e205204/ | xDesc | E | C | 1-1 | 5-60 | Descrição do evento:<br>Manifestação de NFS-e - Confirmação Tácita | - |
| 53 | evento/pedRegEvento/infPedReg/ | e202205 | CG | - | 1-1 | - | Parte Específica do Evento<br>Manifestação de NFS-e - Rejeição do Prestador | - |
| 54 | evento/pedRegEvento/infPedReg/e202205/ | xDesc | E | C | 1-1 | 5-60 | Descrição do evento:<br>Manifestação de NFS-e - Rejeição do Prestador | - |
| 55 | evento/pedRegEvento/infPedReg/e202205/ | cMotivo | E | N | 1-1 | 1 | Motivo da Rejeição da NFS-e:<br><br>1 - NFS-e em duplicidade;<br>2 - NFS-e já emitida pelo tomador;<br>3 - Não ocorrência do fato gerador;<br>4 - Erro quanto a responsabilidade tributária;<br>5 - Erro quanto ao valor do serviço, valor das deduções ou serviço prestado ou data do fato gerador;<br>9 - Outros; | - |
| 56 | evento/pedRegEvento/infPedReg/e202205/ | xMotivo | E | C | 0-1 | 15-255 | Descrição para explicitar o motivo indicado neste evento. | - |
| 57 | evento/pedRegEvento/infPedReg/ | e203206 | CG | - | 1-1 | - | Parte Específica do Evento<br> Manifestação de NFS-e - Rejeição do Tomador | - |
| 58 | evento/pedRegEvento/infPedReg/e203206/ | xDesc | E | C | 1-1 | 5-60 | Descrição do evento:<br>Manifestação de NFS-e - Rejeição do Tomador | - |
| 59 | evento/pedRegEvento/infPedReg/e203206/ | cMotivo | E | N | 1-1 | 1 | Motivo da Rejeição da NFS-e:<br><br>1 - NFS-e em duplicidade;<br>2 - NFS-e já emitida pelo tomador;<br>3 - Não ocorrência do fato gerador;<br>4 - Erro quanto a responsabilidade tributária;<br>5 - Erro quanto ao valor do serviço, valor das deduções ou serviço prestado ou data do fato gerador;<br>9 - Outros; | - |
| 60 | evento/pedRegEvento/infPedReg/e203206/ | xMotivo | E | C | 0-1 | 15-255 | Descrição para explicitar o motivo indicado neste evento. | - |
| 61 | evento/pedRegEvento/infPedReg/ | e204207 | CG | - | 1-1 | - | Parte Específica do Evento<br>Manifestação de NFS-e - Rejeição do Intermediário | - |
| 62 | evento/pedRegEvento/infPedReg/e204207/ | xDesc | E | C | 1-1 | 5-60 | Descrição do evento:<br>Manifestação de NFS-e - Rejeição do Intermediário | - |
| 63 | evento/pedRegEvento/infPedReg/e204207/ | cMotivo | E | N | 1-1 | 1 | Motivo da Rejeição da NFS-e:<br><br>1 - NFS-e em duplicidade;<br>2 - NFS-e já emitida pelo tomador;<br>3 - Não ocorrência do fato gerador;<br>4 - Erro quanto a responsabilidade tributária;<br>5 - Erro quanto ao valor do serviço, valor das deduções ou serviço prestado ou data do fato gerador;<br>9 - Outros; | - |
| 64 | evento/pedRegEvento/infPedReg/e204207/ | xMotivo | E | C | 0-1 | 15-255 | Descrição para explicitar o motivo indicado neste evento. | - |
| 65 | evento/pedRegEvento/infPedReg/ | e205208 | CG | - | 1-1 | - | Parte Específica do Evento<br>Manifestação de NFS-e - Anulação da Rejeição | - |
| 66 | evento/pedRegEvento/infPedReg/e205208/ | xDesc | E | C | 1-1 | 5-60 | Descrição do evento:<br>Manifestação de NFS-e - Anulação da Rejeição | - |
| 67 | evento/pedRegEvento/infPedReg/e205208/ | CPFAgTrib | E | N | 1-1 | 11 | CPF do agente da administração tributária municipal que efetuou o anulação da manifestação de rejeição da NFS-e. |  |
| 68 | evento/pedRegEvento/infPedReg/e205208/ | idEvManifRej | E | C | 1-1 | 59 | Referência ao "id" do Evento de Manifestação de NFS-e - Rejeição, que originou o presente evento de anulação. | - |
| 69 | evento/pedRegEvento/infPedReg/e205208/ | xMotivo | E | C | 1-1 | 15-255 | Descrição para explicitar o motivo indicado neste evento. | - |
| 70 | evento/pedRegEvento/infPedReg/ | e305101 | CG | - | 1-1 | - | Parte Específica do Evento<br>Cancelamento de NFS-e por Ofício | - |
| 71 | evento/pedRegEvento/infPedReg/e305101/ | xDesc | E | C | 1-1 | 5-60 | Descrição do evento:<br>Cancelamento de NFS-e por Ofício | - |
| 72 | evento/pedRegEvento/infPedReg/e305101/ | CPFAgTrib | E | N | 1-1 | 11 | CPF do agente da administração tributária municipal que efetuou o cancelamento por ofício de NFS-e. | - |
| 73 | evento/pedRegEvento/infPedReg/e305101/ | nProcAdm | E | N | 1-1 | 30 | Número do processo administrativo municipal vinculado ao cancelamento de NFS-e por ofício. | - |
| 74 | evento/pedRegEvento/infPedReg/e305101/ | xProcAdm | E | C | 1-1 | 15-255 | Descrição para explicitar o motivo do processo administrativo municipal indicado neste evento. | - |
| 75 | evento/pedRegEvento/infPedReg/ | e305102 | CG | - | 1-1 | - | Parte Específica do Evento<br>Bloqueio de NFS-e por Ofício | - |
| 76 | evento/pedRegEvento/infPedReg/e305102/ | xDesc | E | C | 1-1 | 5-60 | Descrição do evento: <br>Bloqueio de NFS-e por Ofício | - |
| 77 | evento/pedRegEvento/infPedReg/e305102/ | CPFAgTrib | E | N | 1-1 | 11 | CPF do agente da administração tributária municipal que efetuou o bloqueio de NFS-e por ofício. | - |
| 78 | evento/pedRegEvento/infPedReg/e305102/ | codEvento | E | N | 1-1 | 7 | Eventos que podem ser escolhidos pelo município emissor para serem rejeitados após emissão e vinculação do evento de bloqueio por ofício em uma NFS-e:<br><br>e101101 - Cancelamento de NFS-e;<br>e105102 - Cancelamento de NFS-e por Substituição;<br>e105104 - Cancelamento de NFS-e Deferido por Análise Fiscal;<br>e105105 - Cancelamento de NFS-e Indeferido por Análise Fiscal;<br>e305101 - Cancelamento de NFS-e por Ofício; | Verificar em conjunto o código do Evento de Bloqueio  associado ao código do evento que está sendo bloqueado (valor do campo cEvtNFSe).<br><br>Em <tipo_evento_bloqueio_NFS-e> substituir pelo nome do evento correspondente ao código do campo cEvtNFSe.<br><br>e101101 - Cancelamento de NFS-e;<br>e105102 - Cancelamento de NFS-e por Substituição;<br>e105104 - Cancelamento de NFS-e Deferido por Análise Fiscal;<br>e105105 - Cancelamento de NFS-e Indeferido por Análise Fiscal;<br>e305101 - Cancelamento de NFS-e por Ofício; |
| 79 | evento/pedRegEvento/infPedReg/e305102/ | xMotivo | E | C | 1-1 | 15-255 | Descrição para explicitar o motivo indicado neste evento. | - |
| 80 | evento/pedRegEvento/infPedReg/ | e305103 | CG | - | 1-1 | - | Parte Específica do Evento<br>Desbloqueio de NFS-e por Ofício | - |
| 81 | evento/pedRegEvento/infPedReg/e305103/ | xDesc | E | C | 1-1 | 5-60 | Descrição do evento: <br>Desbloqueio de NFS-e por Ofício | - |
| 82 | evento/pedRegEvento/infPedReg/e305103/ | CPFAgTrib | E | N | 1-1 | 11 | CPF do agente da administração tributária municipal que efetuou o desbloqueio de NFS-e por ofício. | - |
| 83 | evento/pedRegEvento/infPedReg/e305103/ | idBloqOfic | E | C | 1-1 | 59 | Referência ao "id" do "Bloqueio de ofício" que originou o presente evento de desbloqueio. | - |
| 84 | evento/pedRegEvento/ | Signature | G | - | 0-1 | - | Assinatura do pedido de registro de evento segundo o Padrão XML Digital Signature. | - |
| 85 | evento/ | Signature | G | - | 1-1 | - | Assinatura do pedido de registro segundo o Padrão XML Digital Signature. | - |

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

```excel-formula
Cell: A47
Formula: =ROW(A46)
Cached value: 46
Display value: 46
Type: number
Number format: General
```

```excel-formula
Cell: A48
Formula: =ROW(A47)
Cached value: 47
Display value: 47
Type: number
Number format: General
```

```excel-formula
Cell: A49
Formula: =ROW(A48)
Cached value: 48
Display value: 48
Type: number
Number format: General
```

```excel-formula
Cell: A50
Formula: =ROW(A49)
Cached value: 49
Display value: 49
Type: number
Number format: General
```

```excel-formula
Cell: A51
Formula: =ROW(A50)
Cached value: 50
Display value: 50
Type: number
Number format: General
```

```excel-formula
Cell: A52
Formula: =ROW(A51)
Cached value: 51
Display value: 51
Type: number
Number format: General
```

```excel-formula
Cell: A53
Formula: =ROW(A52)
Cached value: 52
Display value: 52
Type: number
Number format: General
```

```excel-formula
Cell: A54
Formula: =ROW(A53)
Cached value: 53
Display value: 53
Type: number
Number format: General
```

```excel-formula
Cell: A55
Formula: =ROW(A54)
Cached value: 54
Display value: 54
Type: number
Number format: General
```

```excel-formula
Cell: A56
Formula: =ROW(A55)
Cached value: 55
Display value: 55
Type: number
Number format: General
```

```excel-formula
Cell: A57
Formula: =ROW(A56)
Cached value: 56
Display value: 56
Type: number
Number format: General
```

```excel-formula
Cell: A58
Formula: =ROW(A57)
Cached value: 57
Display value: 57
Type: number
Number format: General
```

```excel-formula
Cell: A59
Formula: =ROW(A58)
Cached value: 58
Display value: 58
Type: number
Number format: General
```

```excel-formula
Cell: A60
Formula: =ROW(A59)
Cached value: 59
Display value: 59
Type: number
Number format: General
```

```excel-formula
Cell: A61
Formula: =ROW(A60)
Cached value: 60
Display value: 60
Type: number
Number format: General
```

```excel-formula
Cell: A62
Formula: =ROW(A61)
Cached value: 61
Display value: 61
Type: number
Number format: General
```

```excel-formula
Cell: A63
Formula: =ROW(A62)
Cached value: 62
Display value: 62
Type: number
Number format: General
```

```excel-formula
Cell: A64
Formula: =ROW(A63)
Cached value: 63
Display value: 63
Type: number
Number format: General
```

```excel-formula
Cell: A65
Formula: =ROW(A64)
Cached value: 64
Display value: 64
Type: number
Number format: General
```

```excel-formula
Cell: A66
Formula: =ROW(A65)
Cached value: 65
Display value: 65
Type: number
Number format: General
```

```excel-formula
Cell: A67
Formula: =ROW(A66)
Cached value: 66
Display value: 66
Type: number
Number format: General
```

```excel-formula
Cell: A68
Formula: =ROW(A67)
Cached value: 67
Display value: 67
Type: number
Number format: General
```

```excel-formula
Cell: A69
Formula: =ROW(A68)
Cached value: 68
Display value: 68
Type: number
Number format: General
```

```excel-formula
Cell: A70
Formula: =ROW(A69)
Cached value: 69
Display value: 69
Type: number
Number format: General
```

```excel-formula
Cell: A71
Formula: =ROW(A70)
Cached value: 70
Display value: 70
Type: number
Number format: General
```

```excel-formula
Cell: A72
Formula: =ROW(A71)
Cached value: 71
Display value: 71
Type: number
Number format: General
```

```excel-formula
Cell: A73
Formula: =ROW(A72)
Cached value: 72
Display value: 72
Type: number
Number format: General
```

```excel-formula
Cell: A74
Formula: =ROW(A73)
Cached value: 73
Display value: 73
Type: number
Number format: General
```

```excel-formula
Cell: A75
Formula: =ROW(A74)
Cached value: 74
Display value: 74
Type: number
Number format: General
```

```excel-formula
Cell: A76
Formula: =ROW(A75)
Cached value: 75
Display value: 75
Type: number
Number format: General
```

```excel-formula
Cell: A77
Formula: =ROW(A76)
Cached value: 76
Display value: 76
Type: number
Number format: General
```

```excel-formula
Cell: A78
Formula: =ROW(A77)
Cached value: 77
Display value: 77
Type: number
Number format: General
```

```excel-formula
Cell: A79
Formula: =ROW(A78)
Cached value: 78
Display value: 78
Type: number
Number format: General
```

```excel-formula
Cell: A80
Formula: =ROW(A79)
Cached value: 79
Display value: 79
Type: number
Number format: General
```

```excel-formula
Cell: A81
Formula: =ROW(A80)
Cached value: 80
Display value: 80
Type: number
Number format: General
```

```excel-formula
Cell: A82
Formula: =ROW(A81)
Cached value: 81
Display value: 81
Type: number
Number format: General
```

```excel-formula
Cell: A83
Formula: =ROW(A82)
Cached value: 82
Display value: 82
Type: number
Number format: General
```

```excel-formula
Cell: A84
Formula: =ROW(A83)
Cached value: 83
Display value: 83
Type: number
Number format: General
```

```excel-formula
Cell: A85
Formula: =ROW(A84)
Cached value: 84
Display value: 84
Type: number
Number format: General
```

```excel-formula
Cell: A86
Formula: =ROW(A85)
Cached value: 85
Display value: 85
Type: number
Number format: General
```

## RN EVENTO_PED.REG.EVENTO

| # | CAMINHO NO XML | CAMPO | REGRAS DE NEGÓCIO PARA A NOTA FISCAL DE SERVIÇO ELETRÔNICA DO <br>SISTEMA NACIONAL NFS-e |  |  |  |  |  | NÍVEL<br>DA REGRA | EMISSORES PÚBLICOS NACIONAIS NFS-e<br>(SEFIN, WEB, APP) |  | ADN NFS-e |  | OBSERVAÇÕES DE NEGÓCIO |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
|  |  |  | Nível 1 - Regras de negócio para consistência do Leiaute NFS-e<br><br>Nível 2 - Regras de Negócio gerais para todos os municípios aderentes ao SN NFS-e<br><br>Nível 3 - Regras de Negócio específicas conforme legislação municipal do município parametrizada no SN NFS-e  |  |  |  |  |  |  | RNs EXECUTADAS NA RECEPÇÃO DE PEDREGEVT EVIADOS PELOS EMITENTES (V),<br>RNs EXECUTADAS NA GERAÇÃO DAS INFORMAÇÕES DE EVENTOS PELOS EMISSORES PÚBLICOS NACIONAIS (V) E<br>RNs NÃO EXECUTADAS (X) | EVENTOS EMITIDOS SOB CONDIÇÕES DE <br>DECISÃO JUDICIAL OU ADMINISTRATIVA (cStat = 102)<br>RNs EXECUTADAS NA GERAÇÃO DE EVENTOS PELOS<br>EMISSORES PÚBLICOS NACIONAIS (SEFIN, WEB, APP)<br>RNs EXECUTADAS (V)<br>RNs NÃO EXECUTADAS (X) | RNs EXECUTADAS <br>NA RECEPÇÃO DE EVENTOS COMPARTILHADOS <br>PELOS MUNICÍPIOS COM O <br>ADN NFS-e (V) <br>E<br>RNs NÃO EXECUTADAS (X) | EVENTOS EMITIDOS SOB CONDIÇÕES DE <br>DECISÃO JUDICIAL OU ADMINISTRATIVA (cStat = 102)<br>RNs EXECUTADAS NA GERAÇÃO DE EVENTOS PELOS<br>EMISSORES PÚBLICOS NACIONAIS (SEFIN, WEB, APP)<br>RNs EXECUTADAS (V)<br>RNs NÃO EXECUTADAS (X) |  |
|  |  |  | REGRAS DE NEGÓCIO |  | APLIC. | EFEITO | CÓD. ERRO | MSG. ERRO |  |  |  |  |  |  |
| 1 |  | evento | - |  | - | - | - | - | - | - | - | - | - | - |
| 2 | evento/ | versao | O prazo de aceitação da versão do leiaute do DF-e ultrapassado. |  | Obrig. | Rej. | E1260 | O prazo de aceitação da versão do leiaute do DF-e expirou. | 1 | V | V | V | V | - |
| 3 | evento/ | infEvento | - |  | - | - | - | - | - | - | - | - | - | - |
| 4 | evento/infEvento/ | id | Campo identificador do Evento (EVT) inválido.<br><br>Identificador do evento difere da concatenação dos campos correspondentes existente no leiaute evento.<br><br>A formação deste identificador considera o literal "EVT" associado a outras 59 posições numéricas, <br>conforme descrito abaixo:<br><br>"EVT" + <br>id do Pedido de Registo de Evento (56) + <br>Número sequencial do evento (nSeqEvento) (3) |  | Obrig. | Rej. | E1802 | Conteúdo do identificador informado no identificador do evento difere da concatenação dos campos correspondentes. | 1 | V | V | V | V | - |
| 5 |  |  | O id do evento compartilhado já existe no ADN. |  | Obrig. | Rej. | E1805 | Já existe um Evento com este identificador no ADN NFS-e. | 1 | X | X | V | V | - |
| 6 |  |  | O id do evento gerado já existe no ADN. |  | Obrig. | Rej. | E0802 | Já existe um documento fiscal eletrônico identificado com este id no Sistema Nacional NFS-e. |  | V | V | X | X | - |
| 7 | evento/infEvento/ | verAplic | - |  | - | - | - | - | - | - | - | - | - | - |
| 8 | evento/infEvento/ | ambGer | Verificar se o ambiente gerador da NFS-e está de acordo com a definição:<br>1- Sistema Próprio do Município, para as NFS-e compartilhadas pelo município para o ADN, ou<br>2 - Sefin Nacional NFS-e, para as NFS-e emitidas pela Sefin ou recepcionadas via API "Bypass". |  | Obrig. | Rej. | E1274 | O ambiente gerador da NFS-e não está de acordo com a definição 1 (Sistema Próprio do Município) ou 2 (Sefin Nacional). | 1 | X | X | V | V | - |
| 9 | evento/infEvento/ | nSeqEvento | - |  | - | - | - | - | - | - | - | - | - | - |
| 10 | evento/infEvento/ | dhProc | A data/hora do registro do evento deve ser anterior à data/hora do processamento do documento pelo Sistema Nacional NFS-e. |  | Obrig. | Rej. | E1278 | A data e hora do processamento (geração) do DF-e deve ser anterior ou igual à data da recepção pelo Sistema Nacional NFS-e. | 1 | V | V | V | V | - |
| 11 | evento/infEvento/ | nDFSe | - |  | - | - | - | - | - | - | - | - | - | - |
| 12 | evento/infEvento/ | pedRegEvento | - |  | - | - | - | - | - | - | - | - | - | - |
| 13 | evento/pedRegEvento/ | versao | Prazo de aceitação da versão do leiaute do pedido de registro de evento ultrapassado. |  | Obrig. | Rej. | E1825 | Prazo de aceitação da versão do leiaute do pedido de registro de evento expirou. | 1 | V | V | V | V | - |
| 14 | evento/pedRegEvento/ | infPedReg | - |  | - | - | - | - | - | - | - | - | - | - |
| 15 | evento/pedRegEvento/infPedReg/ | id | Campo identificador do Pedido de Registro de Evento (PRE) inválido.<br><br>Identificador do PRE difere da concatenação dos campos correspondentes existente no leiaute do PRE.<br><br>A formação deste identificador considera o literal "PRE" associado a outras 56 posições numéricas, conforme descrito abaixo:<br><br>"PRE" + <br>Chave de acesso da NFS-e (50) +<br>Código do evento (6) |  | Obrig. | Rej. | E1827 | Conteúdo do identificador informado no identificador do Pedido de Registro de Evento difere da concatenação dos campos correspondentes. | 1 | V | V | V | V | - |
| 16 |  |  | Não é permitido o compartilhamento dos seguintes eventos pelo município com o ADN são:<br>202201 - Manifestação de NFS-e de Confirmação do Prestador;<br>203202 - Manifestação de NFS-e de Confirmação do Tomador;<br>204203 - Manifestação de NFS-e de Confirmação do Intermediário;<br>202205 - Manifestação de NFS-e de Rejeição do Prestador;<br>203206 - Manifestação de NFS-e de Rejeição do Tomador;<br>204207 - Manifestação de NFS-e de Rejeição do Intermediário; |  | Obrig. | Rej. | E1818 | Não é permitido o compartilhamento pelo município com o ADN dos eventos de manifestação de NFS-e para confirmação ou rejeição pelos não emitentes de um NFS-e. Estes eventos devem ser emitidos pelos não emitentes da NFS-e diretamente no SEFIN NFS-e.<br> | 1 | X | X | V | V | - |
| 17 | evento/pedRegEvento/infPedReg/ | tpAmb | Tipo do ambiente informado difere do ambiente utilizado. |  | Obrig. | Rej. | E1845 | Ambiente informado diverge do ambiente de recebimento para o qual o emitente está enviando o evento. | 1 | V | V | V | V | - |
| 18 | evento/pedRegEvento/infPedReg/ | verAplic | - |  | - | - | - | - | - | - | - | - | - | - |
| 19 | evento/pedRegEvento/infPedReg/ | dhEvento | A data de emissão do pedido do registro do evento não pode ser posterior à data de recebimento do lote <br>em que o Evento está sendo recebido pelo Sistema Nacional NFS-e. |  | Obrig. | Rej. | E1843 | A data de emissão do pedido do registro do evento não pode ser posterior à data de recebimento pelo Sistema Nacional NFS-e. | 1 | X | V | V | V | - |
| 20 | evento/pedRegEvento/infPedReg/ | CNPJAutor | Se o autor do pedido de registro de evento for identificado por um CNPJ este deve corresponder ao mesmo CNPJ do certificado digital da assinatura, se informada no pedido. <br><br>Verificar apenas o CNPJ base. |  | Obrig. | Rej. | E0812 | O CNPJ do autor do pedido de registro de evento não corresponde à base do CNPJ informada no certificado digital da assinatura do pedido. | 1 | V | V | V | V | - |
| 21 |  |  | O CNPJ autor identificado no pedido de registro de evento deve corresponder ao CNPJ conforme o "AUTOR DO PEDIDO DE REGISTRO DE EVENTO" da planilha "Tipo Eventos". |  | Obrig. | Rej. | E0813 | O CNPJ autor informado no pedido de registro de evento não corresponde ao "AUTOR DO PEDIDO DE REGISTRO DE EVENTO" indicado na planilha "Tipo Eventos". | 1 | V | V | V | V | - |
| 22 | evento/pedRegEvento/infPedReg/ | CPFAutor | Se o autor do pedido de registro de evento for identificado por um CPF este deve corresponder ao mesmo CPF do certificado digital da assinatura, se informada no pedido. |  | Obrig. | Rej. | E0815 | O CPF do autor do pedido de registro de evento não corresponde ao CPF informado no certificado digital da assinatura do pedido. | 1 | V | V | V | V | - |
| 23 |  |  | O CPF autor identificado no pedido de registro de evento deve corresponder ao CPF conforme o "AUTOR DO PEDIDO DE REGISTRO DE EVENTO" da planilha "Tipo Eventos". |  | Obrig. | Rej. | E0816 | O CPF autor informado no pedido de registro de evento não corresponde ao "AUTOR DO PEDIDO DE REGISTRO DE EVENTO" indicado na planilha "Tipo Eventos". | 1 | V | V | V | V | - |
| 24 | evento/pedRegEvento/infPedReg/ | chNFSe | A NFS-e indicada não existe no ADN NFS-e. |  | Obrig. | Rej. | E1831 | O pedido de registro de evento não pode ser validado pois a NFS-e indicada não existe no Ambiente de Dados Nacional NFS-e. | 1 | V | V | V | V | - |
| 25 |  |  | Não pode ocorrer cancelamento de NFS-e fora do prazo limite para o cancelamento da NFS-e, conforme parametrização do município emissor da NFS-e. |  | Obrig. | Rej. | E0822 | O prazo para o cancelamento da NFS-e expirou, conforme parametrização do município emissor da NFS-e. | 3 | V | V | X | X | - |
| 26 |  |  | Não pode ocorrer cancelamento de NFS-e acima de valor permitido pelo município emissor da NFS-e, conforme parametrização do município emissor da NFS-e. |  | Obrig. | Rej. | E0823 | Valor da NFS-e a ser cancelada acima do permitido, conforme parametrização do município emissor da NFS-e. | 3 | V | V | X | X | - |
| 27 |  |  | Não pode ocorrer o cancelamento de NFS-e que não esteja identificado o tomador de serviço, conforme parametrização do município emissor da NFS-e. |  | Obrig. | Rej. | E0824 | NFS-e sem indentificação do tomador do serviço não pode ser cancelada, conforme parametrização do município emissor da NFS-e. | 3 | V | V | X | X | - |
| 28 |  |  | Não é permitido realizar um Evento de Cancelamento para NFS-e que possua Evento de Tributos Recolhidos vinculado, conforme parametrização do município de incidência do ISSQN da NFS-e. |  | Obrig. | Rej. | E0827 | Não é permitido realizar um Evento de Cancelamento para NFS-e que possua Evento de Tributos Recolhidos vinculado, conforme parametrização do município de incidência do ISSQN da NFS-e. | 3 | V | V | X | X | - |
| 29 |  |  | O pedido de registro deste evento de NFS-e deve ser enviado para o ambiente que gerou a NFS-e referenciada no pedido. |  | Obrig. | Rej. | E0831 | O pedido de registro deste evento de NFS-e deve ser enviado para o ambiente que gerou a NFS-e referenciada no pedido. | 1 | V | V | V | V | - |
| 30 |  |  | Somente é permitido um único evento do tipo Manifestação de NFS-e (Confirmação e Rejeição), se o autor do evento, informado nos campos CNPJAutor ou CPFAutor, corresponder aos possíveis não emitentes da NFS-e indicada pela chave de acesso. |  | Obrig. | Rej. | E1833 | Somente é permitido um único evento do tipo Manifestação de NFS-e (Confirmação e Rejeição), por não emitente da NFS-e indicada no evento. | 1 | V | V | V | V | - |
| 31 |  |  | Somente é permitido um único evento do tipo Manifestação de NFS-e (Anulação da Rejeição) para cada Evento de Manifestação de NFS-e - Rejeição, emitido por cada um dos possíveis não emitentes da NFS-e indicada pela chave de acesso. |  | Obrig. | Rej. | E1835 | Somente é permitido um único evento do tipo Manifestação de NFS-e (Anulação da Rejeição) para cada Evento de Manifestação de NFS-e - Rejeição, emitido por cada possível não emitentes da NFS-e indicada pela chave de acesso. | 1 | V | V | V | V | - |
| 32 | evento/pedRegEvento/infPedReg/ | e101101 | O Sistema Nacional NFS-e deve responder à recepção do EVENTO DE CANCELAMENTO NFS-e, conforme indicado nas linhas da respectiva coluna deste evento na planilha "RN EVENTOSxEVENTOS - Sistema Nacional NFS-e". |  | Obrig. | Rej. | E0840 | O Sistema Nacional NFS-e não pode recepecionar o EVENTO DE CANCELAMENTO DE NFS-e pois, o evento de <nome_evento_vinculado_a_NFS-e> já está vinculado à NFS-e indicada no evento enviado, impedindo sua recepção. | 1 | V | V | V | V | - |
| 33 | evento/pedRegEvento/infPedReg/e101101/ | xDesc | - |  | - | - | - | - | - | - | - | - | - | - |
| 34 | evento/pedRegEvento/infPedReg/e101101/ | cMotivo | - |  | - | - | - | - | - | - | - | - | - | - |
| 35 | evento/pedRegEvento/infPedReg/e101101/ | xMotivo | - |  | - | - | - | - | - | - | - | - | - | - |
| 36 | evento/pedRegEvento/infPedReg/ | e105102 | O Sistema Nacional NFS-e deve responder à recepção do EVENTO DE CANCELAMENTO DE NFS-e POR SUBSTITUIÇÃO, conforme indicado nas linhas da respectiva coluna deste evento na planilha "RN EVENTOSxEVENTOS - Sistema Nacional NFS-e". |  | Obrig. | Rej. | E0845 | O Sistema Nacional NFS-e não pode recepecionar o EVENTO DE CANCELAMENTO DE NFS-e POR SUBSTITUIÇÃO pois, o evento de <nome_evento_vinculado_a_NFS-e> já está vinculado à NFS-e indicada no evento enviado, impedindo sua recepção. | 1 | V | V | V | V | - |
| 37 | evento/pedRegEvento/infPedReg/e105102/ | xDesc | - |  | - | - | - | - | - | - | - | - | - | - |
| 38 | evento/pedRegEvento/infPedReg/e105102/ | cMotivo | - |  | - | - | - | - | - | - | - | - | - | - |
| 39 | evento/pedRegEvento/infPedReg/e105102/ | xMotivo | - |  | - | - | - | - | - | - | - | - | - | - |
| 40 | evento/pedRegEvento/infPedReg/e105102/ | chSubstituta | - |  | - | - | - | - | - | - | - | - | - | - |
| 41 | evento/pedRegEvento/infPedReg/ | e101103 | O Sistema Nacional NFS-e deve responder à recepção do EVENTO DE SOLICITAÇÃO DE ANÁLISE FISCAL PARA CANCELAMENTO DE NFS-e, conforme indicado nas linhas da respectiva coluna deste evento na planilha "RN EVENTOSxEVENTOS - Sistema Nacional NFS-e". |  | Obrig. | Rej. | E0848 | O Sistema Nacional NFS-e não pode recepecionar o EVENTO DE SOLICITAÇÃO DE ANÁLISE FISCAL PARA CANCELAMENTO DE NFS-e pois, o evento de <nome_evento_vinculado_a_NFS-e> já está vinculado à NFS-e indicada no evento enviado, impedindo sua recepção. | 1 | V | V | V | V | - |
| 42 | evento/pedRegEvento/infPedReg/e101103/ | xDesc | - |  | - | - | - | - | - | - | - | - | - | - |
| 43 | evento/pedRegEvento/infPedReg/e101103/ | cMotivo | - |  | - | - | - | - | - | - | - | - | - | - |
| 44 | evento/pedRegEvento/infPedReg/e101103/ | xMotivo | - |  | - | - | - | - | - | - | - | - | - | - |
| 45 | evento/pedRegEvento/infPedReg/ | e105104 | Não é permitida a recepção do deferimento de uma solicitação de cancelamento por análise fiscal sem que haja um Evento de Solicitação de Análise Fiscal para Cancelamento de NFS-e, que esteja pendente de deferimento ou indeferimento. |  | Obrig. | Rej. | E0853 | O Sistema Nacional NFS-e não pode recepecionar o EVENTO DE CANCELAMENTO DE NFS-e DEFERIDO POR ANÁLISE FISCAL pois não existe um EVENTO DE SOLICITAÇÃO DE ANÁLISE FISCAL PARA CANCELAMENTO DE NFS-e pendente para deferimento ou indeferimento. | 1 | V | V | V | V | - |
| 46 |  |  | O Sistema Nacional NFS-e deve responder à recepção do EVENTO DE CANCELAMENTO DE NFS-e DEFERIDO POR ANÁLISE FISCAL, conforme indicado nas linhas da respectiva coluna deste evento na planilha "RN EVENTOSxEVENTOS - Sistema Nacional NFS-e". |  | Obrig. | Rej. | E0852 | O Sistema Nacional NFS-e não pode recepecionar o EVENTO DE CANCELAMENTO DE NFS-e DEFERIDO POR ANÁLISE FISCAL pois, o evento de <nome_evento_vinculado_a_NFS-e> já está vinculado à NFS-e indicada no evento enviado, impedindo sua recepção. | 1 | V | V | V | V | - |
| 47 | evento/pedRegEvento/infPedReg/e105104/ | xDesc | - |  | - | - | - | - | - | - | - | - | - | - |
| 48 | evento/pedRegEvento/infPedReg/e105104/ | CPFAgTrib | - |  | - | - | - | - | - | - | - | - | - | - |
| 49 | evento/pedRegEvento/infPedReg/e105104/ | nProcAdm | - |  | - | - | - | - | - | - | - | - | - | - |
| 50 | evento/pedRegEvento/infPedReg/e105104/ | cMotivo | - |  | - | - | - | - | - | - | - | - | - | - |
| 51 | evento/pedRegEvento/infPedReg/e105104/ | xMotivo | - |  | - | - | - | - | - | - | - | - | - | - |
| 52 | evento/pedRegEvento/infPedReg/ | e105105 | Não é permitida a recepção do indeferimento de uma solicitação de cancelamento por análise fiscal sem que haja um Evento de Solicitação de Análise Fiscal para Cancelamento de NFS-e, que esteja pendente de deferimento ou indeferimento. |  | Obrig. | Rej. | E0856 | O Sistema Nacional NFS-e não pode recepecionar o EVENTO DE CANCELAMENTO DE NFS-e INDEFERIDO POR ANÁLISE FISCAL pois não existe um EVENTO DE SOLICITAÇÃO DE ANÁLISE FISCAL PARA CANCELAMENTO DE NFS-e pendente para deferimento ou indeferimento. | 1 | V | - | V | - | - |
| 53 |  |  | O Sistema Nacional NFS-e deve responder à recepção do EVENTO DE CANCELAMENTO DE NFS-E INDEFERIDO POR ANÁLISE FISCAL, conforme indicado nas linhas da respectiva coluna deste evento na planilha "RN EVENTOSxEVENTOS - Sistema Nacional NFS-e". |  | Obrig. | Rej. | E0855 | O Sistema Nacional NFS-e não pode recepecionar o EVENTO DE CANCELAMENTO DE NFS-E INDEFERIDO POR ANÁLISE FISCAL pois, o evento de <nome_evento_vinculado_a_NFS-e> já está vinculado à NFS-e indicada no evento enviado, impedindo sua recepção. | 1 | V | V | V | V | - |
| 54 | evento/pedRegEvento/infPedReg/e105105/ | xDesc | - |  | - | - | - | - | - | - | - | - | - | - |
| 55 | evento/pedRegEvento/infPedReg/e105105/ | CPFAgTrib | - |  | - | - | - | - | - | - | - | - | - | - |
| 56 | evento/pedRegEvento/infPedReg/e105105/ | nProcAdm | - |  | - | - | - | - | - | - | - | - | - | - |
| 57 | evento/pedRegEvento/infPedReg/e105105/ | cMotivo | - |  | - | - | - | - | - | - | - | - | - | - |
| 58 | evento/pedRegEvento/infPedReg/e105105/ | xMotivo | - |  | - | - | - | - | - | - | - | - | - | - |
| 59 | evento/pedRegEvento/infPedReg/ | e202201 | O Sistema Nacional NFS-e deve responder à recepção do EVENTO DE MANIFESTAÇÃO DE NFS-e - CONFIRMAÇÃO DO PRESTADOR, conforme indicado nas linhas da respectiva coluna deste evento na planilha "RN EVENTOSxEVENTOS - Sistema Nacional NFS-e". |  | Obrig. | Rej. | E0860 | O Sistema Nacional NFS-e não pode recepecionar o EVENTO DE MANIFESTAÇÃO DE NFS-e - CONFIRMAÇÃO DO PRESTADOR pois, o evento de <nome_evento_vinculado_a_NFS-e> já está vinculado à NFS-e indicada no evento enviado, impedindo sua recepção. | 1 | V | V | V | V | - |
| 60 | evento/pedRegEvento/infPedReg/e202201/ | xDesc | - |  | - | - | - | - | - | - | - | - | - | - |
| 61 | evento/pedRegEvento/infPedReg/ | e203202 | O Sistema Nacional NFS-e deve responder à recepção do EVENTO DE MANIFESTAÇÃO DE NFS-e - CONFIRMAÇÃO DO TOMADOR, conforme indicado nas linhas da respectiva coluna deste evento na planilha "RN EVENTOSxEVENTOS - Sistema Nacional NFS-e". |  | Obrig. | Rej. | E0861 | O Sistema Nacional NFS-e não pode recepecionar o EVENTO DE MANIFESTAÇÃO DE NFS-e - CONFIRMAÇÃO DO TOMADOR pois, o evento de <nome_evento_vinculado_a_NFS-e> já está vinculado à NFS-e indicada no evento enviado, impedindo sua recepção. | 1 | V | V | V | V | - |
| 62 | evento/pedRegEvento/infPedReg/e203202/ | xDesc | - |  | - | - | - | - | - | - | - | - | - | - |
| 63 | evento/pedRegEvento/infPedReg/ | e204203 | O Sistema Nacional NFS-e deve responder à recepção do EVENTO DE MANIFESTAÇÃO DE NFS-e - CONFIRMAÇÃO DO INTERMEDIÁRIO, conforme indicado nas linhas da respectiva coluna deste evento na planilha "RN EVENTOSxEVENTOS - Sistema Nacional NFS-e". |  | Obrig. | Rej. | E0862 | O Sistema Nacional NFS-e não pode recepecionar o EVENTO DE MANIFESTAÇÃO DE NFS-e - CONFIRMAÇÃO DO INTERMEDIÁRIO pois, o evento de <nome_evento_vinculado_a_NFS-e> já está vinculado à NFS-e indicada no evento enviado, impedindo sua recepção. | 1 | V | V | V | V | - |
| 64 | evento/pedRegEvento/infPedReg/e204203/ | xDesc | - |  | - | - | - | - | - | - | - | - | - | - |
| 65 | evento/pedRegEvento/infPedReg/ | e205204 | O Sistema Nacional NFS-e deve responder à recepção do EVENTO DE MANIFESTAÇÃO DE NFS-e - CONFIRMAÇÃO TÁCITA, conforme indicado nas linhas da respectiva coluna deste evento na planilha "RN EVENTOSxEVENTOS - Sistema Nacional NFS-e". |  | Obrig. | Rej. | E0863 | O Sistema Nacional NFS-e não pode recepecionar o EVENTO DE MANIFESTAÇÃO DE NFS-e - CONFIRMAÇÃO TÁCITA pois, o evento de <nome_evento_vinculado_a_NFS-e> já está vinculado à NFS-e indicada no evento enviado, impedindo sua recepção. | 1 | V | V | V | V | - |
| 66 | evento/pedRegEvento/infPedReg/e205204/ | xDesc | - |  | - | - | - | - | - | - | - | - | - | - |
| 67 | evento/pedRegEvento/infPedReg/e205204/ | CPFAgTrib | - |  | - | - | - | - | - | - | - | - | - | - |
| 68 | evento/pedRegEvento/infPedReg/ | e202205 | O Sistema Nacional NFS-e deve responder à recepção do EVENTO DE MANIFESTAÇÃO DE NFS-e - REJEIÇÃO DO PRESTADOR, conforme indicado nas linhas da respectiva coluna deste evento na planilha "RN EVENTOSxEVENTOS - Sistema Nacional NFS-e". |  | Obrig. | Rej. | E0864 | O Sistema Nacional NFS-e não pode recepecionar o EVENTO DE MANIFESTAÇÃO DE NFS-e - REJEIÇÃO DO PRESTADOR pois, o evento de <nome_evento_vinculado_a_NFS-e> já está vinculado à NFS-e indicada no evento enviado, impedindo sua recepção. | 1 | V | V | V | V | - |
| 69 | evento/pedRegEvento/infPedReg/e202205/ | xDesc | - |  | - | - | - | - | - | - | - | - | - | - |
| 70 | evento/pedRegEvento/infPedReg/e202205/ | cMotivo | - |  | - | - | - | - | - | - | - | - | - | - |
| 71 | evento/pedRegEvento/infPedReg/e202205/ | xMotivo | Se o campo cMotivo for igual a "9, - Outros", então a descrição do motivo tem preenchimento obrigatório. |  | Obrig. | Rej. | E1944 | A  descrição do motivo é obrigatória caso o tipo do motivo seja "9 - Outros". | 1 | V | V | V | V | - |
| 72 | evento/pedRegEvento/infPedReg/ | e203206 | O Sistema Nacional NFS-e deve responder à recepção do EVENTO DE MANIFESTAÇÃO DE NFS-e - REJEIÇÃO DO TOMADOR, conforme indicado nas linhas da respectiva coluna deste evento na planilha "RN EVENTOSxEVENTOS - Sistema Nacional NFS-e". |  | Obrig. | Rej. | E0866 | O Sistema Nacional NFS-e não pode recepecionar o EVENTO DE MANIFESTAÇÃO DE NFS-e - REJEIÇÃO DO TOMADOR pois, o evento de <nome_evento_vinculado_a_NFS-e> já está vinculado à NFS-e indicada no evento enviado, impedindo sua recepção. | 1 | V | V | V | V | - |
| 73 | evento/pedRegEvento/infPedReg/e203206/ | xDesc | - |  | - | - | - | - | - | - | - | - | - | - |
| 74 | evento/pedRegEvento/infPedReg/e203206/ | cMotivo | - |  | - | - | - | - | - | - | - | - | - | - |
| 75 | evento/pedRegEvento/infPedReg/e203206/ | xMotivo | Se o campo cMotivo for igual a "9, - Outros", então a descrição do motivo tem preenchimento obrigatório. |  | Obrig. | Rej. | E1949 | A  descrição do motivo é obrigatória caso o tipo do motivo seja "9 - Outros". | 1 | V | V | V | V | - |
| 76 | evento/pedRegEvento/infPedReg/ | e204207 | O Sistema Nacional NFS-e deve responder à recepção do EVENTO DE MANIFESTAÇÃO DE NFS-e - REJEIÇÃO DO INTERMEDIÁRIO, conforme indicado nas linhas da respectiva coluna deste evento na planilha "RN EVENTOSxEVENTOS - Sistema Nacional NFS-e". |  | Obrig. | Rej. | E0868 | O Sistema Nacional NFS-e não pode recepecionar o EVENTO DE MANIFESTAÇÃO DE NFS-e - REJEIÇÃO DO INTERMEDIÁRIO pois, o evento de <nome_evento_vinculado_a_NFS-e> já está vinculado à NFS-e indicada no evento enviado, impedindo sua recepção. | 1 | V | V | V | V | - |
| 77 | evento/pedRegEvento/infPedReg/e204207/ | xDesc | - |  | - | - | - | - | - | - | - | - | - | - |
| 78 | evento/pedRegEvento/infPedReg/e204207/ | cMotivo | - |  | - | - | - | - | - | - | - | - | - | - |
| 79 | evento/pedRegEvento/infPedReg/e204207/ | xMotivo | Se o campo cMotivo for igual a "9, - Outros", então a descrição do motivo tem preenchimento obrigatório. |  | Obrig. | Rej. | E1954 | A  descrição do motivo é obrigatória caso o tipo do motivo seja "9 - Outros". | - | V | V | V | V | - |
| 80 | evento/pedRegEvento/infPedReg/ | e205208 | O Sistema Nacional NFS-e deve responder à recepção do EVENTO DE MANIFESTAÇÃO DE NFS-e - ANULAÇÃO DA REJEIÇÃO, conforme indicado nas linhas da respectiva coluna deste evento na planilha "RN EVENTOSxEVENTOS - Sistema Nacional NFS-e". |  | Obrig. | Rej. | E0870 | O Sistema Nacional NFS-e não pode recepecionar o EVENTO DE MANIFESTAÇÃO DE NFS-e - ANULAÇÃO DA REJEIÇÃO pois, o evento de <nome_evento_vinculado_a_NFS-e> já está vinculado à NFS-e indicada no evento enviado, impedindo sua recepção. | 1 | V | V | V | V | - |
| 81 | evento/pedRegEvento/infPedReg/e205208/ | CPFAgTrib | - |  | - | - | - | - | - | - | - | - | - | - |
| 82 | evento/pedRegEvento/infPedReg/e205208/ | idEvManifRej | O identificador do Evento de Manifestação de NFS-e - Rejeição a ser anulado deve existir no Sistema Nacional NFS-e e corresponder a um identificador de evento de rejeição vinculada à NFS-e informada no evento. |  | Obrig. | Rej. | E1963 | O identificador do Evento de Manifestação de NFS-e - Rejeição a ser anulado deve existir no Sistema Nacional NFS-e e corresponder a um identificador de evento de rejeição vinculada à NFS-e informada no evento. | 1 | V | V | V | V | - |
| 83 | evento/pedRegEvento/infPedReg/e205208/ | xMotivo | - |  | - | - |  | - | - | - | - | - | - | - |
| 84 | evento/pedRegEvento/infPedReg/ | e305101 | O Sistema Nacional NFS-e deve responder à recepção do EVENTO DE CANCELAMENTO DE NFS-e POR OFÍCIO, conforme indicado nas linhas da respectiva coluna deste evento na planilha "RN EVENTOSxEVENTOS - Sistema Nacional NFS-e". |  | Obrig. | Rej. | E1960 | O Sistema Nacional NFS-e não pode recepecionar o EVENTO DE CANCELAMENTO DE NFS-e POR OFÍCIO pois, o evento de <nome_evento_vinculado_a_NFS-e> já está vinculado à NFS-e indicada no evento enviado, impedindo sua recepção. | 1 | V | V | V | V | - |
| 85 | evento/pedRegEvento/infPedReg/e305101/ | xDesc | - |  | - | - | - | - | - | - | - | - | - | - |
| 86 | evento/pedRegEvento/infPedReg/e305101/ | CPFAgTrib | - |  | - | - | - | - | - | - | - | - | - | - |
| 87 | evento/pedRegEvento/infPedReg/e305101/ | nProcAdm | - |  | - | - | - | - | - | - | - | - | - | - |
| 88 | evento/pedRegEvento/infPedReg/e305101/ | xProcAdm | - |  | - | - | - | - | - | - | - | - | - | - |
| 89 | evento/pedRegEvento/infPedReg/ | e305102 | O Sistema Nacional NFS-e deve responder à recepção do EVENTO DE BLOQUEIO DE NFS-e POR OFÍCIO, conforme indicado nas linhas da respectiva coluna deste evento na planilha "RN EVENTOSxEVENTOS - Sistema Nacional NFS-e". |  | Obrig. | Rej. | E1965 | O Sistema Nacional NFS-e não pode recepecionar o EVENTO DE BLOQUEIO DE NFS-e POR OFÍCIO pois, o evento de <nome_evento_vinculado_a_NFS-e> já está vinculado à NFS-e indicada no evento enviado, impedindo sua recepção. | 1 | V | V | V | V | - |
| 90 | evento/pedRegEvento/infPedReg/e305102/ | xDesc | - |  | - | - | - | - | - | - | - | - | - | - |
| 91 | evento/pedRegEvento/infPedReg/e305102/ | CPFAgTrib | - |  | - | - | - | - | - | - | - | - | - | - |
| 92 | evento/pedRegEvento/infPedReg/e305102/ | xMotivo | - |  | - | - | - | - | - | - | - | - | - | - |
| 93 | evento/pedRegEvento/infPedReg/e305102/ | codEvento | O Evento de Bloqueio de NFS-e para um tipo de evento, deve ser rejeitado se já houver um evento de bloqueio de NFS-e para o mesmo tipo do evento que não esteja desbloquedo.<br><br>Obs: Verificar se para cada evento de bloqueio de NFS-e para um tipo de evento existe o correspondente desbloqueio.<br><br>Se os possíveis eventos de bloqueios de NFS-e para um tipo de evento não estiverem todos pareados com os seus respectivos desbloqueios, então o eveto de bloqueio de NFS-e do mesmo tipo que está sendo recepcionado pelo Sistema Nacional NFS-e deverá ser rejeitado. |  | Obrig. | Rej. | E1967 | O sistema não pode recepcionar o Evento de Bloqueio de NFS-e por Ofício para <tipo_evento_bloqueio_NFS-e>, pois já existe o mesmo tipo de evento vinculado à NFS-e sem o Evento de Desbloqueio correpondente. | 1 | V | V | V | V | - |
| 94 | evento/pedRegEvento/infPedReg/ | e305103 | O Sistema Nacional NFS-e deve responder à recepção do EVENTO DE DESBLOQUEIO DE NFS-e POR OFÍCIO, conforme indicado nas linhas da respectiva coluna deste evento na planilha "RN EVENTOSxEVENTOS - Sistema Nacional NFS-e". |  | Obrig. | Rej. | E1970 | O Sistema Nacional NFS-e não pode recepecionar o EVENTO DE DESBLOQUEIO DE NFS-e POR OFÍCIO pois, o evento de <nome_evento_vinculado_a_NFS-e> já está vinculado à NFS-e indicada no evento enviado, impedindo sua recepção. | 1 | V | V | V | V | - |
| 95 | evento/pedRegEvento/infPedReg/e305103/ | xDesc | - |  | - | - | - | - | - | - | - | - | - | - |
| 96 | evento/pedRegEvento/infPedReg/e305103/ | CPFAgTrib | - |  | - | - | - | - | - | - | - | - | - | - |
| 97 | evento/pedRegEvento/infPedReg/e305103/ | idBloqOfic | O evento de Evento de Desbloqueio de NFS-e deve ser rejeitado se o identificador do bloqueio de oficio, indicado neste evento, não existir. |  | Obrig. | Rej. | E1976 | Não existe o identificador de bloqueio informado neste evento para desbloqueio. | 1 | V | V | V | V | - |
| 98 |  |  | O Evento de Desbloqueio de NFS-e deve ser rejeitado se o identificador do bloqueio de oficio, indicado neste evento, já tiver sido desbloqueado. |  | Obrig. | Rej. | E1978 | O sistema não pode recepcionar o Evento Desbloqueio de NFS-e por Ofício, pois o Evento de Bloqueio indicado já foi desbloqueado. | 1 | V | V | V | V | - |
| 99 | evento/pedRegEvento/ | Signature | A assinatura do Pedido de Registro de Evento deve ser válida. |  | Obrig. | Rej. | E1980 | Arquivo enviado com erro na assinatura. | 1 | V | V | X | V | - |
| 100 |  |  | Certificado Digital da assintura inválido:<br><br>- Validade do Certificado (data início e data fim);<br>- Verifica a Cadeia de Certificação;<br>- Certificado do Transmissor revogado;<br>- LCR indisponível ou inválida. |  | Obrig. | Rej. | E1983 | Certificado Digital da assinatura inválido. | 1 | V | V | X | V | - |
| 101 |  |  | Certificado Digital da assinatura fora do padrão estabelecido pelo projeto NFS-e:<br><br>- Versão diferente de 3;<br>- Se informado, Basic Constraint deve ser true (não pode ser Certificado de AC);<br>- KeyUsage não define 'Assinatura Digital' e 'Não Recusa';<br>- Falta a extensão de CNPJ (OtherName - OID=2.16.76.1.3.3) ou CPF (OtherName - OID=2.16.76.1.3.1);<br>- Certificado Raiz difere da 'ICP-Brasil'. |  | Obrig. | Rej. | E1986 | Certificado Digital fora do padrão estabelecido. | 1 | V | V | X | V | - |
| 102 |  |  | É obrigatória a existência da assinatura do Pedido de Registro de Evento quando for enviado para Web Service. |  | Obrig. | Rej. | E1989 | A assinatura é obrigatória quando for enviado para o Web Service. | 1 | V | V | X | V | - |
| 103 |  |  | A assinatura deve ser feita com o certificado digital do emitente do Pedido de Registro de Evento. |  | Obrig. | Rej. | E1991 | A assinatura deve ser feita com o certificado digital do emitente do Pedido de Registro de Evento.<br> | 1 | V | V | X | V | - |
| 104 | evento/ | Signature | A assinatura do Evento deve ser válida. |  | Obrig. | Rej. | E2020 | Arquivo enviado com erro na assinatura. | 1 | X | V | V | V | - |
| 105 |  |  | Certificado Digital da assintura inválido:<br><br>- Validade do Certificado (data início e data fim);<br>- Verifica a Cadeia de Certificação;<br>- Certificado do Transmissor revogado;<br>- LCR indisponível ou inválida. |  | Obrig. | Rej. | E2023 | Certificado Digital da assinatura inválido. | 1 | X | V | V | V | - |
| 106 |  |  | Certificado Digital da assinatura fora do padrão estabelecido pelo Sistema Nacional NFS-e:<br><br>- Versão diferente de 3;<br>- Se informado, Basic Constraint deve ser true (não pode ser Certificado de AC);<br>- KeyUsage não define 'Assinatura Digital' e 'Não Recusa';<br>- Falta a extensão de CNPJ (OtherName - OID=2.16.76.1.3.3) ou CPF (OtherName - OID=2.16.76.1.3.1);<br>- Certificado Raiz difere da 'ICP-Brasil'. |  | Obrig. | Rej. | E2026 | Certificado Digital fora do padrão estabelecido. | 1 | X | V | V | V | - |
| 107 |  |  | É obrigatória a existência da assinatura do Evento quando for enviado para Web Service. |  | Obrig. | Rej. | E2029 | A assinatura é obrigatória quando for enviado paraa API. | 1 | X | V | V | V | - |
| 108 |  |  | A assinatura deve ser feita com o certificado digital do município emissor do Evento. |  | Obrig. | Rej. | E2032 | A assinatura deve ser feita com o certificado digital do município emissor do Evento.<br> | 1 | X | V | V | V | - |

### Formula sources

```excel-formula
Cell: A4
Formula: =ROW(A1)
Cached value: 1
Display value: 1
Type: number
Number format: General
```

```excel-formula
Cell: A5
Formula: =ROW(A2)
Cached value: 2
Display value: 2
Type: number
Number format: General
```

```excel-formula
Cell: A6
Formula: =ROW(A3)
Cached value: 3
Display value: 3
Type: number
Number format: General
```

```excel-formula
Cell: A7
Formula: =ROW(A4)
Cached value: 4
Display value: 4
Type: number
Number format: General
```

```excel-formula
Cell: A8
Formula: =ROW(A5)
Cached value: 5
Display value: 5
Type: number
Number format: General
```

```excel-formula
Cell: A9
Formula: =ROW(A6)
Cached value: 6
Display value: 6
Type: number
Number format: General
```

```excel-formula
Cell: A10
Formula: =ROW(A7)
Cached value: 7
Display value: 7
Type: number
Number format: General
```

```excel-formula
Cell: A11
Formula: =ROW(A8)
Cached value: 8
Display value: 8
Type: number
Number format: General
```

```excel-formula
Cell: A12
Formula: =ROW(A9)
Cached value: 9
Display value: 9
Type: number
Number format: General
```

```excel-formula
Cell: A13
Formula: =ROW(A10)
Cached value: 10
Display value: 10
Type: number
Number format: General
```

```excel-formula
Cell: A14
Formula: =ROW(A11)
Cached value: 11
Display value: 11
Type: number
Number format: General
```

```excel-formula
Cell: A15
Formula: =ROW(A12)
Cached value: 12
Display value: 12
Type: number
Number format: General
```

```excel-formula
Cell: A16
Formula: =ROW(A13)
Cached value: 13
Display value: 13
Type: number
Number format: General
```

```excel-formula
Cell: A17
Formula: =ROW(A14)
Cached value: 14
Display value: 14
Type: number
Number format: General
```

```excel-formula
Cell: A18
Formula: =ROW(A15)
Cached value: 15
Display value: 15
Type: number
Number format: General
```

```excel-formula
Cell: A19
Formula: =ROW(A16)
Cached value: 16
Display value: 16
Type: number
Number format: General
```

```excel-formula
Cell: A20
Formula: =ROW(A17)
Cached value: 17
Display value: 17
Type: number
Number format: General
```

```excel-formula
Cell: A21
Formula: =ROW(A18)
Cached value: 18
Display value: 18
Type: number
Number format: General
```

```excel-formula
Cell: A22
Formula: =ROW(A19)
Cached value: 19
Display value: 19
Type: number
Number format: General
```

```excel-formula
Cell: A23
Formula: =ROW(A20)
Cached value: 20
Display value: 20
Type: number
Number format: General
```

```excel-formula
Cell: A24
Formula: =ROW(A21)
Cached value: 21
Display value: 21
Type: number
Number format: General
```

```excel-formula
Cell: A25
Formula: =ROW(A22)
Cached value: 22
Display value: 22
Type: number
Number format: General
```

```excel-formula
Cell: A26
Formula: =ROW(A23)
Cached value: 23
Display value: 23
Type: number
Number format: General
```

```excel-formula
Cell: A27
Formula: =ROW(A24)
Cached value: 24
Display value: 24
Type: number
Number format: General
```

```excel-formula
Cell: A28
Formula: =ROW(A25)
Cached value: 25
Display value: 25
Type: number
Number format: General
```

```excel-formula
Cell: A29
Formula: =ROW(A26)
Cached value: 26
Display value: 26
Type: number
Number format: General
```

```excel-formula
Cell: A30
Formula: =ROW(A27)
Cached value: 27
Display value: 27
Type: number
Number format: General
```

```excel-formula
Cell: A31
Formula: =ROW(A28)
Cached value: 28
Display value: 28
Type: number
Number format: General
```

```excel-formula
Cell: A32
Formula: =ROW(A29)
Cached value: 29
Display value: 29
Type: number
Number format: General
```

```excel-formula
Cell: A33
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
Formula: =ROW(A32)
Cached value: 32
Display value: 32
Type: number
Number format: General
```

```excel-formula
Cell: A36
Formula: =ROW(A33)
Cached value: 33
Display value: 33
Type: number
Number format: General
```

```excel-formula
Cell: A37
Formula: =ROW(A34)
Cached value: 34
Display value: 34
Type: number
Number format: General
```

```excel-formula
Cell: A38
Formula: =ROW(A35)
Cached value: 35
Display value: 35
Type: number
Number format: General
```

```excel-formula
Cell: A39
Formula: =ROW(A36)
Cached value: 36
Display value: 36
Type: number
Number format: General
```

```excel-formula
Cell: A40
Formula: =ROW(A37)
Cached value: 37
Display value: 37
Type: number
Number format: General
```

```excel-formula
Cell: A41
Formula: =ROW(A38)
Cached value: 38
Display value: 38
Type: number
Number format: General
```

```excel-formula
Cell: A42
Formula: =ROW(A39)
Cached value: 39
Display value: 39
Type: number
Number format: General
```

```excel-formula
Cell: A43
Formula: =ROW(A40)
Cached value: 40
Display value: 40
Type: number
Number format: General
```

```excel-formula
Cell: A44
Formula: =ROW(A41)
Cached value: 41
Display value: 41
Type: number
Number format: General
```

```excel-formula
Cell: A45
Formula: =ROW(A42)
Cached value: 42
Display value: 42
Type: number
Number format: General
```

```excel-formula
Cell: A46
Formula: =ROW(A43)
Cached value: 43
Display value: 43
Type: number
Number format: General
```

```excel-formula
Cell: A47
Formula: =ROW(A44)
Cached value: 44
Display value: 44
Type: number
Number format: General
```

```excel-formula
Cell: A48
Formula: =ROW(A45)
Cached value: 45
Display value: 45
Type: number
Number format: General
```

```excel-formula
Cell: A49
Formula: =ROW(A46)
Cached value: 46
Display value: 46
Type: number
Number format: General
```

```excel-formula
Cell: A50
Formula: =ROW(A47)
Cached value: 47
Display value: 47
Type: number
Number format: General
```

```excel-formula
Cell: A51
Formula: =ROW(A48)
Cached value: 48
Display value: 48
Type: number
Number format: General
```

```excel-formula
Cell: A52
Formula: =ROW(A49)
Cached value: 49
Display value: 49
Type: number
Number format: General
```

```excel-formula
Cell: A53
Formula: =ROW(A50)
Cached value: 50
Display value: 50
Type: number
Number format: General
```

```excel-formula
Cell: A54
Formula: =ROW(A51)
Cached value: 51
Display value: 51
Type: number
Number format: General
```

```excel-formula
Cell: A55
Formula: =ROW(A52)
Cached value: 52
Display value: 52
Type: number
Number format: General
```

```excel-formula
Cell: A56
Formula: =ROW(A53)
Cached value: 53
Display value: 53
Type: number
Number format: General
```

```excel-formula
Cell: A57
Formula: =ROW(A54)
Cached value: 54
Display value: 54
Type: number
Number format: General
```

```excel-formula
Cell: A58
Formula: =ROW(A55)
Cached value: 55
Display value: 55
Type: number
Number format: General
```

```excel-formula
Cell: A59
Formula: =ROW(A56)
Cached value: 56
Display value: 56
Type: number
Number format: General
```

```excel-formula
Cell: A60
Formula: =ROW(A57)
Cached value: 57
Display value: 57
Type: number
Number format: General
```

```excel-formula
Cell: A61
Formula: =ROW(A58)
Cached value: 58
Display value: 58
Type: number
Number format: General
```

```excel-formula
Cell: A62
Formula: =ROW(A59)
Cached value: 59
Display value: 59
Type: number
Number format: General
```

```excel-formula
Cell: A63
Formula: =ROW(A60)
Cached value: 60
Display value: 60
Type: number
Number format: General
```

```excel-formula
Cell: A64
Formula: =ROW(A61)
Cached value: 61
Display value: 61
Type: number
Number format: General
```

```excel-formula
Cell: A65
Formula: =ROW(A62)
Cached value: 62
Display value: 62
Type: number
Number format: General
```

```excel-formula
Cell: A66
Formula: =ROW(A63)
Cached value: 63
Display value: 63
Type: number
Number format: General
```

```excel-formula
Cell: A67
Formula: =ROW(A64)
Cached value: 64
Display value: 64
Type: number
Number format: General
```

```excel-formula
Cell: A68
Formula: =ROW(A65)
Cached value: 65
Display value: 65
Type: number
Number format: General
```

```excel-formula
Cell: A69
Formula: =ROW(A66)
Cached value: 66
Display value: 66
Type: number
Number format: General
```

```excel-formula
Cell: A70
Formula: =ROW(A67)
Cached value: 67
Display value: 67
Type: number
Number format: General
```

```excel-formula
Cell: A71
Formula: =ROW(A68)
Cached value: 68
Display value: 68
Type: number
Number format: General
```

```excel-formula
Cell: A72
Formula: =ROW(A69)
Cached value: 69
Display value: 69
Type: number
Number format: General
```

```excel-formula
Cell: A73
Formula: =ROW(A70)
Cached value: 70
Display value: 70
Type: number
Number format: General
```

```excel-formula
Cell: A74
Formula: =ROW(A71)
Cached value: 71
Display value: 71
Type: number
Number format: General
```

```excel-formula
Cell: A75
Formula: =ROW(A72)
Cached value: 72
Display value: 72
Type: number
Number format: General
```

```excel-formula
Cell: A76
Formula: =ROW(A73)
Cached value: 73
Display value: 73
Type: number
Number format: General
```

```excel-formula
Cell: A77
Formula: =ROW(A74)
Cached value: 74
Display value: 74
Type: number
Number format: General
```

```excel-formula
Cell: A78
Formula: =ROW(A75)
Cached value: 75
Display value: 75
Type: number
Number format: General
```

```excel-formula
Cell: A79
Formula: =ROW(A76)
Cached value: 76
Display value: 76
Type: number
Number format: General
```

```excel-formula
Cell: A80
Formula: =ROW(A77)
Cached value: 77
Display value: 77
Type: number
Number format: General
```

```excel-formula
Cell: A81
Formula: =ROW(A78)
Cached value: 78
Display value: 78
Type: number
Number format: General
```

```excel-formula
Cell: A82
Formula: =ROW(A79)
Cached value: 79
Display value: 79
Type: number
Number format: General
```

```excel-formula
Cell: A83
Formula: =ROW(A80)
Cached value: 80
Display value: 80
Type: number
Number format: General
```

```excel-formula
Cell: A84
Formula: =ROW(A81)
Cached value: 81
Display value: 81
Type: number
Number format: General
```

```excel-formula
Cell: A85
Formula: =ROW(A82)
Cached value: 82
Display value: 82
Type: number
Number format: General
```

```excel-formula
Cell: A86
Formula: =ROW(A83)
Cached value: 83
Display value: 83
Type: number
Number format: General
```

```excel-formula
Cell: A87
Formula: =ROW(A84)
Cached value: 84
Display value: 84
Type: number
Number format: General
```

```excel-formula
Cell: A88
Formula: =ROW(A85)
Cached value: 85
Display value: 85
Type: number
Number format: General
```

```excel-formula
Cell: A89
Formula: =ROW(A86)
Cached value: 86
Display value: 86
Type: number
Number format: General
```

```excel-formula
Cell: A90
Formula: =ROW(A87)
Cached value: 87
Display value: 87
Type: number
Number format: General
```

```excel-formula
Cell: A91
Formula: =ROW(A88)
Cached value: 88
Display value: 88
Type: number
Number format: General
```

```excel-formula
Cell: A92
Formula: =ROW(A89)
Cached value: 89
Display value: 89
Type: number
Number format: General
```

```excel-formula
Cell: A93
Formula: =ROW(A90)
Cached value: 90
Display value: 90
Type: number
Number format: General
```

```excel-formula
Cell: A94
Formula: =ROW(A91)
Cached value: 91
Display value: 91
Type: number
Number format: General
```

```excel-formula
Cell: A95
Formula: =ROW(A92)
Cached value: 92
Display value: 92
Type: number
Number format: General
```

```excel-formula
Cell: A96
Formula: =ROW(A93)
Cached value: 93
Display value: 93
Type: number
Number format: General
```

```excel-formula
Cell: A97
Formula: =ROW(A94)
Cached value: 94
Display value: 94
Type: number
Number format: General
```

```excel-formula
Cell: A98
Formula: =ROW(A95)
Cached value: 95
Display value: 95
Type: number
Number format: General
```

```excel-formula
Cell: A99
Formula: =ROW(A96)
Cached value: 96
Display value: 96
Type: number
Number format: General
```

```excel-formula
Cell: A100
Formula: =ROW(A97)
Cached value: 97
Display value: 97
Type: number
Number format: General
```

```excel-formula
Cell: A101
Formula: =ROW(A98)
Cached value: 98
Display value: 98
Type: number
Number format: General
```

```excel-formula
Cell: A102
Formula: =ROW(A99)
Cached value: 99
Display value: 99
Type: number
Number format: General
```

```excel-formula
Cell: A103
Formula: =ROW(A100)
Cached value: 100
Display value: 100
Type: number
Number format: General
```

```excel-formula
Cell: A104
Formula: =ROW(A101)
Cached value: 101
Display value: 101
Type: number
Number format: General
```

```excel-formula
Cell: A105
Formula: =ROW(A102)
Cached value: 102
Display value: 102
Type: number
Number format: General
```

```excel-formula
Cell: A106
Formula: =ROW(A103)
Cached value: 103
Display value: 103
Type: number
Number format: General
```

```excel-formula
Cell: A107
Formula: =ROW(A104)
Cached value: 104
Display value: 104
Type: number
Number format: General
```

```excel-formula
Cell: A108
Formula: =ROW(A105)
Cached value: 105
Display value: 105
Type: number
Number format: General
```

```excel-formula
Cell: A109
Formula: =ROW(A106)
Cached value: 106
Display value: 106
Type: number
Number format: General
```

```excel-formula
Cell: A110
Formula: =ROW(A107)
Cached value: 107
Display value: 107
Type: number
Number format: General
```

```excel-formula
Cell: A111
Formula: =ROW(A108)
Cached value: 108
Display value: 108
Type: number
Number format: General
```

## Workbook images and drawings