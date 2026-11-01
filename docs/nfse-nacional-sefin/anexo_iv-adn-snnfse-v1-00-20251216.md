## RN_RECEPCAO_LOTE_DF-e

| # | REGRAS DE NEGÓCIO |  | APLIC. | EFEITO | CÓD. ERRO | MSG. ERRO | NOTAS EXPLICATIVAS |
| --- | --- | --- | --- | --- | --- | --- | --- |
| 1 | Validação do Certificado de Transmissão |  | - | - | - | - | - |
| 2 | Certificado de Transmissor Inválido:<br>- Certificado de Transmissor inexistente na mensagem<br>- Versão difere "3"<br>- Se informado o Basic Constraint deve ser true (não pode ser Certificado de AC)<br>- KeyUsage não define "Autenticação Cliente" |  | Obrig. | Rej. | E1200 | Certificado de Transmissão Inválido | - |
| 3 | Validade do Certificado (data início e data fim) |  | Obrig. | Rej. | E1203 | Certificado de Transmissão expirado | - |
| 4 | Verifica a Cadeia de Certificação:<br>- Certificado da AC emissora não cadastrado na RFB<br>- Certificado de AC revogado<br>- Certificado não assinado pela AC emissora do Certificado |  | Obrig. | Rej. | E1205 | Certificado de Transmissão - Erro Cadeira de Certificação | - |
| 5 | LCR do Certificado de Transmissor<br>- Falta o endereço da LCR (CRL DistributionPoint)<br>- LCR indisponível<br>- LCR inválida |  | Obrig. | Rej. | E1206 | Certificado de Transmissão - Erro de acesso a LCR | - |
| 6 | Certificado do Transmissor revogado |  | Obrig. | Rej. | E1207 | Certificado de Transmissão revogado | - |
| 7 | Certificado Raiz difere da "ICP-Brasil" |  | Obrig. | Rej. | E1208 | Certificado de Transmissão difere da ICP - Brasil | - |
| 8 | Falta a extensão de CNPJ no Certificado (OtherName - OID=2.16.76.1.3.3) |  | Obrig. | Rej. | E1209 | Certificado de Transmissão sem CNPJ | - |
| 9 | CNPJ do certificado de transmissão do lote de DF-e não está autorizado a realizar a transmissão de DF-e pelo municipio conveniado. |  | Obrig. | Rej. | E1262 | CNPJ não autorizado a compartilhar DF-e pelo município conveniado ao Sistema Nacional NFS-e. | - |
| 10 | Certificado da assinatura do lote sem CNPJ. |  | Obrig. | Rej. | E1245 | Certificado da assinatura do lote sem CNPJ. | - |
| 11 | Certificado da assinatura do lote - Erro na cadeia de certificação. |  | Obrig. | Rej. | E1246 | Certificado da assinatura do lote - Erro na cadeia de certificação. | - |
| 12 | Validação Inicial da Mensagem na API |  | - | - | - | - | - |
| 13 | Verifica se o servidor de processamento está paralisado para manutenção |  | Obrig. | Rej. | E1217 | Serviço paralisado para manutenção | - |
| 14 | Número de documentos do lote maior do que o <<limite>. |  | Obrig. | Rej. | E1219 | Número de documentos maior do que o limite definido para a aplicação. | - |
| 15 | Validação da Área de Dados |  | - | - | - | - | - |
| 16 | Falha na descompactado da base 64. |  | Obrig. | Rej. | E1225 | Falha na decodificação da base 64 da área de dados | - |
| 17 | Estrutura descompactada mal formada. |  | Obrig. | Rej. | E1226 | Estrutura descompactada mal formada. | - |
| 18 | Validação dos Tipos de Documentos |  | - | - | - | - | - |
| 19 | Validar os tipos de DF-e tratados pelo Sistema Nacional NFS-e. |  | Obrig. | Rej. | E1242 | Tipo DF-e não tratado pelo Sistema Nacional NFS-e. | - |
| 20 | Uso de prefixo de namespace não permitido na área de dados descompactada. |  | Obrig. | Rej. | E1228 | Uso de prefixo de namespace não permitido na área de dados descompactada. | - |
| 21 | XML não está utilizando codificação UTF8. |  | Obrig. | Rej. | E1229 | XML não está utilizando codificação UTF8. | - |
| 22 | Falha no esquema XML do DF-e. |  | Obrig. | Rej. | E1235 | Falha no esquema XML do DF-e. | - |
| 23 | Assinatura difere do calculado |  | Obrig. | Rej. | E1250 | Assinatura difere do calculado | - |

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

## ADN-GERACAO_NSU_DFe

| DISTRIBUIÇÃO DE DF-e PELO SISTEMA NACIONAL NFS-e  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| # | DF-e<br>\|<br>\\/ |  | ADMINISTRAÇÃO TRIBUTÁRIA |  |  |  |  |  |  |  |  |  |  |  |  | CGNFS-e | ATORES DA NFS-e |  |  |  |  |  |  |  |  |  |  |  |
|  |  |  | RFB | Município<br>Emissor<br>(cLocEmi) |  |  |  |  |  | Município do<br>Emitente<br>(P/T/I) | Município do<br>Não Emitente<br>(P/T/I)<br>(3) | Município da<br>Prestação<br>(3) | Município de<br>Incidência | Para os Municípios do:<br>Prestador, Tomador, Intermediário, Emissor da NFS-e e Local da Prestação |  | CGNFS-e | Emitente<br>(P/T/I) | Não Emitente<br>(P/T/I) |  |  |  |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |
|  | GERAÇÃO DE NSU -> |  | Geral | Recepção |  | Distribuição |  |  |  | Distribuição | Distribuição<br>(1) | Distribuição<br>(2) | Distribuição | MEI<br>(CSTAT = 107) |  | BYPASS<br>(CSTAT = 102) | Distribuição | Distribuição |  |  |  |  |  |  |  |  |  |  |
|  |  |  |  | DF-e<br>Emitido na<br>Sefin Nacional | DF-e<br>Emitido no Sistema Próprio do Município e<br>Compartilhado com ADN | Existente* e Ativo |  | Existente* <br>e <br>Não Ativo | Inexistente** |  |  |  |  | Município<br>inexistente ou<br>Existente<br>INATIVO no<br>SN NFS-e | Município<br>Existente diferente de INATIVO no<br>SN NFS-e |  |  |  |  |  |  |  |  |  |  |  |  |  |
|  |  |  |  |  |  | DF-e<br>Emitido na<br>Sefin Nacional | DF-e<br>Emitido no Sistema Próprio do Município e<br>Compartilhado com ADN |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |
| 1 | NFS-e |  | V | X | V | X | V | X | X | O NSU de distribuição do DF-e para o Município do Emitente<br>do DF-e já é contemplada  pois:<br><br>Município do<br>Emitente<br>(P/T/I)<br>é o<br>Município Emissor,<br>dependendo <br>do tipo de <br>emitente da NFS-e. | V | V | O NSU de distribuição do DF-e para o Município de Incidência<br>do ISSQN já é contemplada pois:<br><br>o<br>Município de<br>Incidência é:<br><br>ou<br>1) Município <br>do Prestador<br>ou<br>2) Município <br>do Tomador<br>ou<br>3) Município <br>da Prestação <br>do Serviço | V | X | V | V | V |  |  |  |  |  |  |  |  |  |  |
| 2 | Cancelamento de NFS-e |  | V | X | V | X | V | X | X |  | V | V |  | V | X | V | V | V |  |  |  |  |  |  |  |  |  |  |
| 3 | Cancelamento de NFS-e por Substituição |  | V | X | V | X | V | X | X |  | V | V |  | V | X | V | V | V |  |  |  |  |  |  |  |  |  |  |
| 4 | Solicitação de Análise Fiscal para Cancelamento de NFS-e |  | V | X | V | X | V | X | X |  | V | V |  | V | X | V | V | V |  |  |  |  |  |  |  |  |  |  |
| 5 | Cancelamento de NFS-e Deferido por Análise Fiscal |  | V | X | V | X | V | X | X |  | V | V |  | V | X | V | V | V |  |  |  |  |  |  |  |  |  |  |
| 6 | Cancelamento de NFS-e Indeferido por Análise Fiscal |  | V | X | V | X | V | X | X |  | V | V |  | V | X | V | V | V |  |  |  |  |  |  |  |  |  |  |
| 7 | Manifestação de NFS-e | Confirmação do Prestador | V | X | V | X | V | X | X |  | V | V |  | V | X | V | V | V |  |  |  |  |  |  |  |  |  |  |
| 8 |  | Confirmação do Tomador | V | X | V | X | V | X | X |  | V | V |  | V | X | V | V | V |  |  |  |  |  |  |  |  |  |  |
| 9 |  | Confirmação do Intermediário | V | X | V | X | V | X | X |  | V | V |  | V | X | V | V | V |  |  |  |  |  |  |  |  |  |  |
| 10 |  | Confirmação Tácita | V | X | V | X | V | X | X |  | V | V |  | V | X | V | V | V |  |  |  |  |  |  |  |  |  |  |
| 11 |  | Rejeição do Prestador | V | X | V | X | V | X | X |  | V | V |  | V | X | V | V | V |  |  |  |  |  |  |  |  |  |  |
| 12 |  | Rejeição do Tomador | V | X | V | X | V | X | X |  | V | V |  | V | X | V | V | V |  |  |  |  |  |  |  |  |  |  |
| 13 |  | Rejeição do Intermediário | V | X | V | X | V | X | X |  | V | V |  | V | X | V | V | V |  |  |  |  |  |  |  |  |  |  |
| 14 |  | Anulação da Rejeição | V | X | V | X | V | X | X |  | V | V |  | V | X | V | V | V |  |  |  |  |  |  |  |  |  |  |
| 15 | Cancelamento de NFS-e por Ofício |  | V | X | V | X | V | X | X |  | V | V |  | V | X | V | V | V |  |  |  |  |  |  |  |  |  |  |
| 16 | Bloqueio de NFS-e<br>por Ofício | Cancelamento de NFS-e | V | X | V | X | V | X | X |  | V | V |  | V | X | V | V | X |  |  |  |  |  |  |  |  |  |  |
| 17 |  | Cancelamento de NFS-e<br>por Substituição | V | X | V | X | V | X | X |  | V | V |  | V | X | V | V | X |  |  |  |  |  |  |  |  |  |  |
| 18 |  | Cancelamento de NFS-e<br>Deferido por Análise Fiscal | V | X | V | X | V | X | X |  | V | V |  | V | X | V | V | X |  |  |  |  |  |  |  |  |  |  |
| 19 |  | Cancelamento de NFS-e<br>Indeferido por Análise Fiscal | V | X | V | X | V | X | X |  | V | V |  | V | X | V | V | X |  |  |  |  |  |  |  |  |  |  |
| 20 |  | Cancelamento de NFS-e por Ofício | V | X | V | X | V | X | X |  | V | V |  | V | X | V | V | X |  |  |  |  |  |  |  |  |  |  |
| 21 | Desbloqueio de NFS-e por Ofício | Cancelamento de NFS-e | V | X | V | X | V | X | X |  | V | V |  | V | X | V | V | X |  |  |  |  |  |  |  |  |  |  |
| 22 |  | Cancelamento de NFS-e<br>por Substituição | V | X | V | X | V | X | X |  | V | V |  | V | X | V | V | X |  |  |  |  |  |  |  |  |  |  |
| 23 |  | Cancelamento de NFS-e<br>Deferido por Análise Fiscal | V | X | V | X | V | X | X |  | V | V |  | V | X | V | V | X |  |  |  |  |  |  |  |  |  |  |
| 24 |  | Cancelamento de NFS-e<br>Indeferido por Análise Fiscal | V | X | V | X | V | X | X |  | V | V |  | V | X | V | V | X |  |  |  |  |  |  |  |  |  |  |
| 25 |  | Cancelamento de NFS-e por Ofício | V | X | V | X | V | X | X |  | V | V |  | V | X | V | V | X |  |  |  |  |  |  |  |  |  |  |
|  |  |  | (1) |  Gerar NSU de Distribuição somente se o Município do Não Emitente for diferente do Município do Emitente. |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |
|  |  |  | (2) |  Gerar NSU de Distribuição somente se o Município da Prestação for diferente do Município do Emitente E do Município do Não Emitente. |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |
|  |  |  | (3) |  Os NSU são gerados para qualquer Município, ativos ou não ativos no Sistema Nacional NFS-e. Todos os DF-e podem ser solicitados na API de distribuição, exceto quando o município for inexistente ou existente inativo ou suspenso. |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |
|  |  |  | (4) |  Gerar NSU de Distribuição somente se o Município do Destinatário for diferente do Município do Tomador . |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |
|  |  |  | (5) |  Gerar NSU de Distribuição somente se o Destinatário for diferente do Tomador . |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |
|  |  |  | * | EXISTENTE no cadastro de municípios conveniados ao Sistema Nacional NFS-e |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |
|  |  |  | ** | INEXISTENTE no cadastro de municípios conveniados ao Sistema Nacional NFS-e |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |  |

## ADN-RN_DISTRIB_MUN_CONTRIB

| # | REGRAS DE NEGÓCIO |  | APLIC. | EFEITO | CÓD. ERRO | MSG. ERRO | OBSERVAÇÕES DE NEGÓCIO |
| --- | --- | --- | --- | --- | --- | --- | --- |
| 1 | Validação da Distribuição para NSU Geral |  | - | - | - | - | - |
| 2 | CNPJ do transmissor deve estar cadastrado para um Órgão no convênio do sistema nacional. |  | Obrig. | Rej. | E2301 | CNPJ não cadastrado. | - |
| 3 | Distriuição a partir de um NSU |  | - | - | - | - | - |
| 4 | Verificar se existem DF-e a partir do NSU informado pelo o Órgão solicitante. |  | Obrig. | Rej. | E2305 | Nenhum documento localizado – não existem documentos fiscais a partir do NSU informado. | - |
| 5 | Consulta por NSU |  | - | - | - | - | - |
| 6 | Verificar se existe DF-e para o NSU informado pelo Órgão solicitante. |  | Obrig. | Rej. | E2306 | Nenhum documento localizado – não existe documento fiscal para o NSU informado. | - |
| 7 | Validação da Distribuição para NSU do Municipio |  | - | - | - | - | - |
| 8 | Validações do Certificado da Transmissão. |  | Obrig. | Rej. | E2000 | Erro no Certificado de transmissão. | - |
| 9 | CNPJ do transmissor deve estar cadastrado para um Município no convênio municipal do sistema nacional. |  | Obrig. | Rej. | E2001 | CNPJ não cadastrado para nenhum Município conveniado. | - |
| 10 | O código do Município informado deve existir e estar com a situação CADASTRADO no cadastro de convênio municipal. |  | Obrig. | Rej. | E2003 | O código do município informado existe, mas está suspenso no convênio municipal. | - |
| 11 | O código do Município informado deve existir e estar inativo a menos de <<prazo para a inativação>> dias no cadastro de convênio municipal. |  | Obrig. | Rej. | E2004 | O código do município informado existe, mas não está ativo há mais de <<prazo para a inativação>> dias no cadastro de convênio municipal. | - |
| 12 | Distriuição a partir de um NSU |  | - | - | - | - | - |
| 13 | Verificar se existem DF-e a partir do NSU informado pelo o município solicitante. |  | Obrig. | Rej. | E2020 | Nenhum documento localizado – não existem documentos fiscais para o Município a partir do NSU informado. | - |
| 14 | Consulta por NSU |  | - | - | - | - | - |
| 15 | Verificar se existe DF-e para o NSU informado pelo municipio solicitante. |  | Obrig. | Rej. | E2040 | Nenhum documento localizado – não existe documento fiscal para o Município com o NSU informado. | - |
| 16 | Consulta por Chave de Acesso |  |  |  |  |  | - |
| 17 | Chave de acesso não localizada. |  | Obrig. | Rej. | E2060 | Nenhum documento localizado – não existe documentos fiscal para a chave de acesso informada. | - |
| 18 | Chave de acesso não referencia o Município solicitante. |  | Obrig. | Rej. | E2061 | Chave de acesso da NFS-e não referencia o Município solicitante e por isso não pode ser compartilhado. | - |
| 19 | Recuperação de Backup - Município Emissor |  | - | - | - | - | - |
| 20 | NSU (NSU de recepção) não localizado para o Município solicitante. |  | Obrig. | Rej. | E2070 | Nenhum documento localizado – não existem documentos fiscais recebidos para o Município a partir do NSU de recepção informado. | - |
|  | Validação da Distribuição para NSU dos Atores da NFS-e |  | - | - | - | - | - |
| 22 | Distriuição a partir de um NSU |  | - | - | - | - | - |
| 23 | Verificar se existem DF-e a partir do NSU informado pelo o contribuinte solicitante. |  | Obrig. | Rej. | E2215 | Nenhum documento localizado – não existem documentos fiscais para o Contribuinte a partir do NSU informado. | - |
| 24 | Consulta por NSU |  | - | - | - | - | - |
| 25 | Verificar se existe DF-e para o NSU informado pelo contribuinte solicitante. |  | Obrig. | Rej. | E2230 | Nenhum documento localizado – não existe documento fiscal para o Contribuinte com o NSU informado. | - |
| 26 | Consulta por Chave de Acesso |  | - | - | - | - | - |
| 27 | Chave de acesso não localizada. |  | Obrig. | Rej. | E2240 | Nenhum documento localizado – não existe documentos fiscal para a chave de acesso informada. | - |
| 28 | Chave de acesso não referencia o Contribuinte solicitante. |  | Obrig. | Rej. | E2241 | Chave de acesso da NFS-e não referencia o Contribuinte solicitante e por isso não pode ser compartilhado. | - |

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