# 02 — Análise Completa do Novo Padrão Nacional (Manual de Integração v1.01 e Modelos XML)

## 1. Visão Geral e Arquitetura do Novo Padrão

A nova integração do Distrito Federal baseia-se no **Padrão Nacional da NFS-e**, desenvolvido no âmbito do GT 01 da ABRASF em alinhamento com a Nota Control e a Receita Federal / Comitê Gestor da NFS-e Nacional.

O modelo substitui conceitualmente o padrão anterior (**ABRASF 2.04**) e adota:
- Transição do **RPS** (Recibo Provisório de Serviços) para a **DPS** (Declaração de Prestação de Serviços).
- Modelo de eventos fiscais para cancelamento e substituição da NFS-e.
- Preparação técnica para a **Reforma Tributária** com suporte aos novos tributos sobre o consumo (**IBS e CBS**), com regras de transição obrigatórias a partir de **01/10/2026**.

```
                           [ Sistema Contribuinte (Dinovatech) ]
                                            │
                                            ▼  mTLS (Certificado A1 ICP-Brasil)
                           [ SOAP 1.1 Envelope (Wrapped) ]
                               ├── nfseCabecMsg (versao="1.00" ou "1.01")
                               └── nfseDadosMsg (XML da operação)
                                            │
                                            ▼
                           [ Web Service NFS-e Padrão Nacional ]
                   - Homologação: https://nfse.issnetonline.com.br/nfse.asmx
                                  (ou /wsnfsenacional/homologacao/nfse.asmx)
                   - Produção:    https://nfse.fazenda.df.gov.br/nfse.asmx
```

---

## 2. Padrões Técnicos de Comunicação e Transporte

1. **Protocolo:** Web Services SOAP 1.1 aderentes ao padrão WS-I Basic Profile, estilo `Document/Literal wrapped`.
2. **Namespace Oficial:** `http://www.sped.fazenda.gov.br/nfse`
3. **Parâmetros da Chamada SOAP:** Cada método recebe dois parâmetros do tipo string:
   - `nfseCabecMsg`: XML do cabeçalho contendo a versão (`1.00` ou `1.01`).
   - `nfseDadosMsg`: XML do payload da operação solicitado.
4. **Certificação Digital e TLS:**
   - **Camada de Transporte (mTLS):** Conexão HTTPS com certificado ICP-Brasil (A1 ou A3), exigindo a extensão `Extended Key Usage` de **Autenticação Cliente**.
   - **Camada de Aplicação (Assinatura de Mensagens):** O certificado para assinatura deve conter o CNPJ do estabelecimento emitente ou da matriz (ou CPF no caso de pessoa física) com permissão de Assinatura Digital.
5. **Endpoints Identificados (WSDL):**
   - **Homologação:**
     - WSDL: `doc_issdf/novo_padrao_nacional/xsd-homol.xml`
     - Service Location: `https://nfse.issnetonline.com.br/wsnfsenacional/homologacao/nfse.asmx`
     - URL de Validação Web: `https://nfse.issnetonline.com.br/wsnfsenacional/homologacao/validarxml`
   - **Produção:**
     - WSDL: `doc_issdf/novo_padrao_nacional/xsd-prod.xml`
     - Service Location: `https://nfse.fazenda.df.gov.br/wsnfsenacional/nfse.asmx`

---

## 3. Padrão de Assinatura Digital (XMLDSig)

A assinatura digital no novo padrão segue estritamente a especificação W3C XMLDSig:
- **Algoritmo de Canonicalização:** C14N 20010315 (`http://www.w3.org/TR/2001/REC-xml-c14n-20010315`).
- **Algoritmo de Assinatura:** RSA com SHA-1 (`http://www.w3.org/2000/09/xmldsig#rsa-sha1`).
- **Algoritmo de Digest:** SHA-1 (`http://www.w3.org/2000/09/xmldsig#sha1`).
- **Transforms:** Enveloped Signature + C14N.
- **Posição e Referência URI:**
  - Na **DPS**: o grupo `<infDPS>` possui um identificador único de **45 caracteres** (ex: `Id="DPS5300108..."`). A assinatura `<Signature>` é filha direta de `<DPS>` (ao lado de `<infDPS>`), e o atributo `Reference URI` deve apontar exatamente para o ID da DPS: `URI="#DPS5300108..."`.
  - No **Cancelamento**: o pedido `<infPedReg>` possui identificador único de **62 caracteres** (`Id="PRE..."`), e a assinatura `<Signature>` referencia `URI="#PRE..."`.
  - **Lote de DPS:** O lote pode ter sua própria assinatura na tag raiz (`<EnviarLoteDpsEnvio>` ou `<EnviarLoteDpsSincronoEnvio>`), mas cada DPS individual dentro do lote deve estar previamente assinada isoladamente.

### Estrutura do Identificador da DPS (45 caracteres):
```
"DPS" (3) + Cód. Município IBGE (7) + Tipo Inscrição Federal (1: 1=CNPJ, 2=CPF) + Inscrição Federal (14) + Série DPS (5) + Número DPS (15)
Exemplo: DPS530010816173371400010100001000000000000001
```

---

## 4. Tabela Completa de Operações do Web Service

| Operação | Método SOAP | Síncrono / Assíncrono | XML de Entrada | XML de Saída | Finalidade | Observações |
|---|---|---|---|---|---|---|
| **Geração de NFS-e** | `GerarNfse` | **Síncrono** | `GerarNfseEnvio` | `GerarNfseResposta` | Emissão direta de NFS-e a partir de 1 DPS | **MVP Principal**: Resposta imediata contendo a NFS-e gerada ou erros. |
| **Envio de Lote Síncrono** | `RecepcionarLoteDpsSincrono` | **Síncrono** | `EnviarLoteDpsSincronoEnvio` | `EnviarLoteDpsSincronoResposta` | Envio de lote com retorno imediato | Ideal para emissões múltiplas sem necessidade de pooling. |
| **Envio de Lote Assíncrono** | `RecepcionarLoteDps` | **Assíncrono** | `EnviarLoteDpsEnvio` | `EnviarLoteDpsResposta` | Recepção em fila para alto volume | Retorna apenas número de protocolo. Exige consulta posterior. |
| **Consulta Lote DPS** | `ConsultarLoteDps` | **Síncrono** | `ConsultarLoteDpsEnvio` | `ConsultarLoteDpsResposta` | Consulta o resultado do processamento do lote | Utilizado após `RecepcionarLoteDps` para obter as NFS-e emitidas. |
| **Consulta NFS-e por DPS** | `ConsultarNfsePorDps` | **Síncrono** | `ConsultarNfseDpsEnvio` | `ConsultarNfseDpsResposta` | Busca a NFS-e vinculada a uma DPS específica | **MVP Essencial**: Usado para double-check imediato e recuperação de notas. |
| **Consulta URL da NFS-e** | `ConsultarUrlNfse` | **Síncrono** | `ConsultarUrlNfseEnvio` | `ConsultarUrlNfseResposta` | Obtém links oficiais de visualização e autenticidade | **MVP Essencial**: Retorna link municipal e link nacional para o usuário. |
| **Cancelamento de NFS-e** | `CancelarNfse` | **Síncrono** | `CancelarNfseEnvio` | `CancelarNfseResposta` | Solicita cancelamento da nota via evento `e101103` | **MVP Essencial**: Evento oficial de cancelamento via webservice. |
| **Consulta Serviços Prestados** | `ConsultarNfseServicoPrestado` | **Síncrono** | `ConsultarNfseServicoPrestadoEnvio` | `ConsultarNfseServicoPrestadoResposta` | Consulta lista de notas emitidas por período/tomador | Paginação de 50 notas por página. Útil para conciliação. |
| **Consulta Serviços Tomados** | `ConsultarNfseServicoTomado` | **Síncrono** | `ConsultarNfseServicoTomadoEnvio` | `ConsultarNfseServicoTomadoResposta` | Consulta notas tomadas pelo contribuinte | Suporte e conferência de compras de serviços. |
| **Consulta por Faixa** | `ConsultarNfseFaixa` | **Síncrono** | `ConsultarNfseFaixaEnvio` | `ConsultarNfseFaixaResposta` | Consulta notas por intervalo de números | Suporte / auditoria interna. |
| **Consulta Dados Cadastrais** | `ConsultarDadosCadastrais` | **Síncrono** | `ConsultarDadosCadastraisEnvio` | `ConsultarDadosCadastraisResposta` | Obtém dados fiscais e regime cadastrado no município | Suporte / sincronização de cadastro fiscal. |
| **Consulta DPS Disponível** | `ConsultarDpsDisponivel` | **Síncrono** | `ConsultarDpsDisponivelEnvio` | `ConsultarDpsDisponivelResposta` | Consulta numerações de DPS disponíveis para emissão | Suporte operacional. |
| **Validação de XML** | `ValidarXml` | **Síncrono** | `ValidarXml` | `ValidarXmlResponse` | Valida previamente um XML contra os Schemas XSD | Ferramenta de teste e diagnóstico de schemas. |

---

## 5. Classificação por Prioridade de Implementação

### 1. Escopo MVP (Imprescindível para Produção):
1. **`GerarNfse`:** Método principal de faturamento. Emite 1 DPS síncrona, retornando imediatamente a nota gerada.
2. **`ConsultarNfsePorDps`:** Indispensável para o mecanismo de auto-recuperação resiliente (se ocorrer queda de conexão ou timeout na emissão, consulta pelo número da DPS para confirmar se a nota foi gerada).
3. **`ConsultarUrlNfse`:** Indispensável para obter a URL oficial de visualização e autenticidade da NFS-e.
4. **`CancelarNfse`:** Evento oficial de cancelamento de NFS-e (evento `e101103`).

### 2. Segunda Etapa:
1. **`RecepcionarLoteDpsSincrono`:** Emissão de cobranças em lote (faturamento recorrente mensal).
2. **`ConsultarNfseServicoPrestado`:** Painel de conciliação fiscal e sincronização de notas do mês.
3. **`ConsultarDadosCadastrais`:** Validação automática dos parâmetros da empresa antes da emissão.

### 3. Suporte / Diagnóstico:
1. **`ValidarXml`:** Testes de conformidade e depuração de novos campos.
2. **`ConsultarDpsDisponivel`:** Checagem de lacunas na numeração.
3. **`RecepcionarLoteDps` / `ConsultarLoteDps`:** Somente se o volume de emissão simultânea exceder dezenas de notas por segundo.

---

## 6. Análise Estrutural dos XMLs de Exemplo

Ao inspecionar os arquivos XML em `doc_issdf/novo_padrao_nacional/modelos-xml`, destacam-se:

### 6.1. Cabeçalho SOAP (`nfseCabecMsg`)
Em todos os métodos, o cabeçalho possui formato padronizado:
```xml
<!-- Para emissão/consulta sem grupo IBS/CBS -->
<cabecalho versao="1.00" xmlns="http://www.sped.fazenda.gov.br/nfse">
    <versaoDados>1.00</versaoDados>
</cabecalho>

<!-- Para emissão informando o grupo IBS/CBS -->
<cabecalho versao="1.01" xmlns="http://www.sped.fazenda.gov.br/nfse">
    <versaoDados>1.01</versaoDados>
</cabecalho>
```

### 6.2. Estrutura da DPS em `GerarNfseEnvio.xml`
```xml
<GerarNfseEnvio xmlns="http://www.sped.fazenda.gov.br/nfse">
    <DPS versao="1.01">
        <infDPS Id="DPS530010816173371400010100001000000000000001">
            <tpAmb>2</tpAmb>
            <dhEmi>2026-09-03T18:00:00-03:00</dhEmi>
            <verAplic>Dinovatech_1.0</verAplic>
            <serie>1</serie>
            <nDPS>1</nDPS>
            <dCompet>2026-09-03</dCompet>
            <tpEmit>1</tpEmit>
            <cLocEmi>5300108</cLocEmi>
            <prest>
                <CNPJ>61733714000101</CNPJ>
                <IM>0841147200111</IM>
                <regTrib>
                    <opSimpNac>3</opSimpNac>
                    <regEspTrib>0</regEspTrib>
                </regTrib>
            </prest>
            <toma>
                <CNPJ>01691128000104</CNPJ>
                <xNome>Razao Social Tomador</xNome>
                <end>
                    <endNac>
                        <cMun>5300108</cMun>
                        <CEP>70000000</CEP>
                    </endNac>
                    <xLgr>Quadra Central</xLgr>
                    <nro>100</nro>
                    <xBairro>Asa Sul</xBairro>
                </end>
            </toma>
            <serv>
                <locPrest>
                    <cLocPrestacao>5300108</cLocPrestacao>
                </locPrest>
                <cServ>
                    <cTribNac>010701</cTribNac>
                    <cTribMun>0107001</cTribMun>
                    <xDescServ>Servicos de desenvolvimento de software e tecnologia.</xDescServ>
                    <cNBS>114032110</cNBS>
                </cServ>
            </serv>
            <valores>
                <vServPrest>
                    <vServ>150.00</vServ>
                </vServPrest>
                <trib>
                    <tribMun>
                        <tribISSQN>1</tribISSQN>
                        <tpRetISSQN>1</tpRetISSQN>
                        <pAliq>2.00</pAliq>
                    </tribMun>
                    <totTrib>
                        <indTotTrib>0</indTotTrib>
                    </totTrib>
                </trib>
            </valores>
        </infDPS>
        <Signature xmlns="http://www.w3.org/2000/09/xmldsig#">
            <!-- Assinatura digital da infDPS -->
        </Signature>
    </DPS>
</GerarNfseEnvio>
```

### 6.3. Estrutura do Retorno (`GerarNfseResposta.xml`)
O retorno utiliza estrutura de escolha (`choice`):
- Se sucesso: contém `<ListaNfse><CompNfse><Nfse versao="1.01"><infNFSe Id="NFS...">...</infNFSe><Signature>...</Signature></Nfse></CompNfse></ListaNfse>`
- Se erro: contém `<ListaMensagemRetorno><MensagemRetorno><Codigo>E001</Codigo><Mensagem>Descrição</Mensagem><Correcao>Instrução</Correcao></MensagemRetorno></ListaMensagemRetorno>`

---

## 7. A Questão do IBS / CBS (Reforma Tributária 2026)

O manual traz alterações fundamentais relativas à Emenda Constitucional 132/2023:
1. **Obrigatoriedade:**
   - Notas fiscais com competência **a partir de 01/10/2026** passam a ter o preenchimento do grupo `IBSCBS` obrigatório quando incidente.
   - Versão do leiaute: `versao="1.01"`.
2. **Grupos e Campos Chave:**
   - `finNFSe`: Finalidade da emissão (0 = NFS-e regular).
   - `cIndOp`: Código indicador da operação de fornecimento (ex: operações com bens/serviços).
   - `indDest`: Indicador se o destinatário é o tomador ou terceiro adquirente.
   - `valores/trib/gIBSCBS`: Contém `CST` (Situação tributária do IBS/CBS), `cClassTrib` (Classificação tributária).
   - `totCIBS`: Totalizadores calculando `vTotNF = vLiq (em 2026)` e `vTotNF = vLiq + vCBS + vIBSTot (a partir de 2027)`.
3. **Transação de Pagamento (`gPgtoVinc`):** Permite vincular o meio de pagamento (ex: `17` = PIX dinâmico, `03` = Cartão de Crédito) com o `CNPJReceb` e o `CNPJBasePSP` da instituição financeira ou adquirente.
