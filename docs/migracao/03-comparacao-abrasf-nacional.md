# 03 — Comparação Sistemática: ABRASF 2.04 × Novo Padrão Nacional

Esta matriz compara diretamente a integração atual do **Dinovatech** com o novo padrão da **NFS-e Nacional** (Nota Control / SPED Fazenda DF).

---

## 1. Tabela Comparativa de Itens Técnicos e Fiscais

| Item Analisado | Classificação | Implementação Atual (ABRASF 2.04) | Novo Padrão Nacional (Manual v1.01) | Impacto / Ação Necessária |
|---|---|---|---|---|
| **Certificado Digital TLS (Transporte)** | `[IGUAL]` | Certificado ICP-Brasil A1 (PKCS#12 `.pfx`), com chave privada em mTLS via cURL. | Certificado ICP-Brasil A1 ou A3 com extensão *Client Authentication*. | Totalmente compatível. O mesmo certificado A1 da empresa continuará sendo usado para o handshake HTTPS. |
| **Certificado de Assinatura Digital** | `[IGUAL]` | Chave privada do certificado A1 para assinatura digital do XML via `openssl_sign`. | Chave privada do certificado A1 ICP-Brasil com permissão de assinatura digital. | Totalmente compatível. |
| **Algoritmo de Assinatura** | `[IGUAL]` | RSA com SHA-1 (`http://www.w3.org/2000/09/xmldsig#rsa-sha1`). | RSA com SHA-1 (`http://www.w3.org/2000/09/xmldsig#rsa-sha1`). | Algoritmo mantido idêntico pelo manual. |
| **Canonicalização XML** | `[IGUAL]` | W3C C14N 20010315 (`http://www.w3.org/TR/2001/REC-xml-c14n-20010315`). | W3C C14N 20010315 (`http://www.w3.org/TR/2001/REC-xml-c14n-20010315`). | Mantida idêntica. |
| **Protocolo de Transporte / Estilo** | `[SEMELHANTE]` | SOAP 1.1 sobre HTTPS, `Document/Literal wrapped`. Headers HTTP com `SOAPAction`. | SOAP 1.1 sobre HTTPS, `Document/Literal wrapped`. Headers HTTP com `SOAPAction`. | O padrão SOAP permanece, mas o namespace e a ação específica mudam. |
| **Namespaces das Mensagens** | `[MUDOU]` | `xmlns="http://www.abrasf.org.br/nfse.xsd"` e `http://nfse.abrasf.org.br`. | `xmlns="http://www.sped.fazenda.gov.br/nfse"`. | O namespace nacional substitui completamente o ABRASF. |
| **Área do Cabeçalho SOAP (`nfseCabecMsg`)** | `[MUDOU]` | `<cabecalho versao="2.04" xmlns="http://www.abrasf.org.br/nfse.xsd"><versaoDados>2.04</versaoDados></cabecalho>` | `<cabecalho versao="1.00" xmlns="http://www.sped.fazenda.gov.br/nfse"><versaoDados>1.00</versaoDados></cabecalho>` (ou 1.01 com IBS/CBS) | Versão muda de `2.04` para `1.00` ou `1.01` com novo namespace. |
| **Documento Provisório Emitido** | `[MUDOU]` | **RPS** (Recibo Provisório de Serviços). Tag `<Rps>` com `<InfDeclaracaoPrestacaoServico>`. | **DPS** (Declaração de Prestação de Serviços). Tag `<DPS>` com `<infDPS>`. | Conceito e estrutura XML alterados completamente. |
| **Alvo da Assinatura Digital (URI de Referência)** | `[MUDOU]` | No legado, o sistema usava `URI=""` (assinando o elemento raiz `<Rps>` sem Id). | A assinatura referencia explicitamente o atributo Id da DPS: `URI="#DPS..."` (Id de 45 dígitos). | O gerador do novo padrão deve calcular o Digest do nó `<infDPS>` e assinar com referência explícita. |
| **Composição do Identificador do Documento** | `[MUDOU]` | `rps_id` gerado de forma simples (ex: `rps2001` ou sequencial local). | Identificador padronizado de **45 dígitos**: `"DPS" + CódMun(7) + TipoInscr(1) + Inscr(14) + Serie(5) + Num(15)`. | Deve ser construído novo helper/builder para gerar o Id oficial de 45 posições. |
| **Identificação da NFS-e Emitida** | `[MUDOU]` | Número simples da nota (até 15 dígitos) + Código de Verificação. | Chave de Acesso da NFS-e de **50 dígitos numéricos** (iniciada por `NFS` + 53 posições no XML). | O banco de dados precisará armazenar a chave nacional de 50 dígitos além do número da nota. |
| **Geração / Emissão da Nota** | `[SEMELHANTE]` | Chamada síncrona `GerarNfse` enviando XML no padrão ABRASF 2.04. | Chamada síncrona `GerarNfse` enviando XML `GerarNfseEnvio` com `<DPS>`. | Fluxo síncrono equivalente, porém payload e tags totalmente novos. |
| **Consulta por Documento Provisório** | `[SEMELHANTE]` | `ConsultarNfsePorRps` enviando número e série do RPS. | `ConsultarNfsePorDps` enviando número e série da DPS (`NumDPS`, `SerieDPS`). | Conceitualmente análogo, tags atualizadas para DPS. |
| **Cancelamento de NFS-e** | `[MUDOU]` | `<CancelarNfseEnvio>` com `<InfPedidoCancelamento>` (não estava integrado na UI do Dinovatech). | `<CancelarNfseEnvio>` via evento `pedRegEvento` (código `e101103` - Análise Fiscal de Cancelamento). | O cancelamento agora é tratado formalmente como registro de evento fiscal assinado digitalmente. |
| **Substituição de NFS-e** | `[MUDOU]` | `SubstituirNfseEnvio` (modelo ABRASF). | Declarada diretamente dentro da DPS através do grupo `<subst>` (`chSubstda`, `cMotivo`, `xMotivo`). | A substituição é nativa no envio da nova DPS. |
| **Codificação de Serviços** | `[MUDOU]` | Item Lista de Serviços LC 116 (ex: `01.07`) e Código Tributação Municipal. | Código de Tributação Nacional (`cTribNac` - 6 dígitos) + Código Municipal (`cTribMun`) + Código NBS (`cNBS` - 9 dígitos). | O catálogo de serviços precisa mapear o `cTribNac` de 6 posições e NBS. |
| **URLs dos Web Services** | `[MUDOU]` | `df.issnetonline.com.br/webservicenfse204/nfse.asmx` | Homologação: `nfse.issnetonline.com.br/nfse.asmx`<br>Produção: `nfse.fazenda.df.gov.br/nfse.asmx` | URLs de conexão completamente distintas. |
| **Visualização da Nota e Obtenção de PDF** | `[SEMELHANTE]` | `ConsultarUrlNfse` retornando `UrlVisualizacaoNfse`. Não retorna PDF binário. | `ConsultarUrlNfse` retornando `UrlVisualizacaoNfse`, `UrlVerificaAutenticidade` e `UrlVisualizacaoNfseNacional`. Não retorna PDF binário. | Continua sendo via URL web externa; agora inclui a URL nacional. |
| **Geração Nativa de PDF / DANFSE** | `[AINDA NÃO CONFIRMADO]` | Não fornecido pelo WebService legado da prefeitura. | Não fornecido pelo WebService da Nota Control / DF. | "Não determinado pela documentação analisada se a prefeitura fornecerá API REST direta de download de DANFSE em PDF". O Dinovatech deve manter sua estratégia de espelho interno/WeasyPrint ou link para o portal nacional. |
| **IBS / CBS (Reforma Tributária)** | `[NOVO]` | Inexistente no padrão ABRASF 2.04. | Grupo `<IBSCBS>` com alíquotas, CST, reduções, totalizadores e vinculação a pagamentos (`gPgtoVinc`). | Obrigatório a partir de 01/10/2026. Leiaute `versao="1.01"`. |
| **Serviço de Validação Prévia de XML** | `[NOVO]` | Inexistente na API antiga (apenas erro genérico retornado no envio). | Método `ValidarXml` no próprio Web Service e interface web de homologação. | Facilita validação de schemas antes do envio definitivo. |
| **Tratamento de Mensagens de Retorno** | `[SEMELHANTE]` | `<ListaMensagemRetorno><MensagemRetorno><Codigo><Mensagem>`. | `<ListaMensagemRetorno><MensagemRetorno><Codigo><Mensagem><Correcao>`. | Semelhante, mas o novo retorno acrescenta o campo explicativo `<Correcao>`. |
| **Campos de RPS no Banco de Dados** | `[REMOVIDO / SUBSTITUÍDO]` | `numero_rps`, `serie_rps`. | Substituídos por `numero_dps`, `serie_dps`, `id_dps` e `chave_nfse`. | Campos legados serão mantidos enquanto o legado existir e complementados pelas novas colunas. |

---

## 2. Detalhamento das Diferenças Estruturais de XML

### Exemplo de Envio no Padrão ABRASF 2.04 (Legado):
```xml
<GerarNfseEnvio xmlns="http://www.abrasf.org.br/nfse.xsd">
    <Rps>
        <InfDeclaracaoPrestacaoServico>
            <Rps>
                <IdentificacaoRps>
                    <Numero>105</Numero>
                    <Serie>8</Serie>
                    <Tipo>1</Tipo>
                </IdentificacaoRps>
                <DataEmissao>2026-09-03</DataEmissao>
                <Status>1</Status>
            </Rps>
            <Competencia>2026-09-03</Competencia>
            <Servico>
                <Valores>
                    <ValorServicos>100.00</ValorServicos>
                    <IssRetido>2</IssRetido>
                    <Aliquota>0.02</Aliquota>
                </Valores>
                <ItemListaServico>01.07</ItemListaServico>
                <Discriminacao>Suporte TI</Discriminacao>
                <CodigoMunicipio>5300108</CodigoMunicipio>
            </Servico>
            <Prestador>
                <CpfCnpj><Cnpj>61733714000101</Cnpj></CpfCnpj>
                <InscricaoMunicipal>0841147200111</InscricaoMunicipal>
            </Prestador>
            <TomadorServico>...</TomadorServico>
        </InfDeclaracaoPrestacaoServico>
        <Signature xmlns="http://www.w3.org/2000/09/xmldsig#">...</Signature>
    </Rps>
</GerarNfseEnvio>
```

### Exemplo de Envio no Novo Padrão Nacional (Manual v1.01):
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
            <toma>...</toma>
            <serv>
                <locPrest><cLocPrestacao>5300108</cLocPrestacao></locPrest>
                <cServ>
                    <cTribNac>010701</cTribNac>
                    <cTribMun>0107001</cTribMun>
                    <xDescServ>Suporte TI</xDescServ>
                    <cNBS>114032110</cNBS>
                </cServ>
            </serv>
            <valores>...</valores>
            <IBSCBS>...</IBSCBS>
        </infDPS>
        <Signature xmlns="http://www.w3.org/2000/09/xmldsig#">
            <!-- Reference URI="#DPS530010816173371400010100001000000000000001" -->
        </Signature>
    </DPS>
</GerarNfseEnvio>
```

---

## 3. Conclusão da Comparação

A discrepância é estrutural, conceitual e de schema. **Não é recomendável nem viável tentar adaptar as classes e funções legadas para "suportar" o novo padrão.** O caminho mais seguro, limpo e à prova de regressão é a coexistência paralela através de uma interface de abstração (`NfseProviderInterface`), mantendo a implementação ABRASF 2.04 isolada como um provedor legado (`LegacyAbrasfProvider`) e criando um provedor novo e desacoplado (`NacionalProvider`).
