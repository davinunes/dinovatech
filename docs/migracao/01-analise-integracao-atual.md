# 01 — Análise Completa da Integração Atual de NFS-e (ISS-DF / ABRASF 2.04)

## 1. Arquitetura Atual

A integração fiscal atual do **Dinovatech** com a Secretaria de Fazenda do DF (ISS.net / Nota Control) foi desenvolvida sob o padrão **ABRASF 2.04**.

Arquiteturalmente, o sistema não possui uma camada de abstração de provedores fiscais com interfaces. A lógica é híbrida entre chamadas procedurais legadas e helpers utilitários:

```
[ Usuário / UI: fatura_view.php ]
             │ (AJAX POST)
             ▼
[ Controlador Central: dinovatech/app.php ]
   ├── action: preview_nfse_data
   ├── action: gerar_nfse
   ├── action: sincronizar_rps_iss
   └── action: consultar_e_vincular_nfse
             │
             ├──► AppHelper::calculateNfseData() [Busca Fatura, Itens, Alíquota, Tomador]
             ├──► EncryptionHelper::decrypt()    [Decriptografa senha do PFX]
             │
             ▼
[ Biblioteca Fiscal Legada: nfse_test/api.php ]
   ├── buildGerarNfseXml($inputApi)
   ├── buildConsultarNfseRpsXml($inputApi)
   ├── buildConsultarUrlNfseXml($inputApi)
   ├── assinarRoot($xml, $certs, $uriRef, $variation)
   └── sendSoap($payload, $endpoint, $certs, $variation, $method)
             │ (SOAP 1.1 + cURL + mTLS A1)
             ▼
[ Web Service Legado ISS-DF ]
- Homologação: https://www.issnetonline.com.br/homologaabrasf/webservicenfse204/nfse.asmx
- Produção:    https://df.issnetonline.com.br/webservicenfse204/nfse.asmx
```

### Componentes Envolvidos:
1. **Frontend / Apresentação:**
   - [fatura_view.php](file:///e:/DEV/dinovatech/dinovatech/fatura_view.php): Renderiza bloco de NFS-e com histórico de tentativas, botões de ação ("Emitir NFS-e", "Buscar PDF", "XML Assinado"), modais de vinculação manual e mensagens de erro extraídas do XML.
   - [config_fiscal.php](file:///e:/DEV/dinovatech/dinovatech/config_fiscal.php): Gerenciamento das configurações da empresa, certificado digital A1 (.pfx em base64 ou caminho em disco), senha criptografada, ambiente (homologação/produção), série e último RPS emitido.
   - [ver_nfse_xml.php](file:///e:/DEV/dinovatech/dinovatech/ver_nfse_xml.php): Rota pública/autenticada para visualizar ou fazer download direto do XML da nota armazenado no banco de dados.
2. **Controlador / Endpoints de Ação:**
   - [app.php](file:///e:/DEV/dinovatech/dinovatech/app.php): Concentra as ações disparadas via AJAX:
     - `preview_nfse_data` (L3073)
     - `gerar_nfse` (L3092)
     - `sincronizar_rps_iss` (L3354)
     - `consultar_e_vincular_nfse` (L3725)
3. **Regras de Negócio e Helpers:**
   - [AppHelper.php](file:///e:/DEV/dinovatech/dinovatech/helpers/AppHelper.php): `calculateNfseData` busca fatura, cliente tomador, endereço, serviço prestado, discriminação, retenção de ISS e alíquota, validando campos obrigatórios antes do envio.
   - [EncryptionHelper.php](file:///e:/DEV/dinovatech/dinovatech/helpers/EncryptionHelper.php): Criptografia e decriptografia reversível da senha do certificado PFX em banco.
   - [ContaDevHelper.php](file:///e:/DEV/dinovatech/dinovatech/helpers/ContaDevHelper.php): Armazenamento/obtenção de espelhos PDF e XMLs para o storage OCI (Oracle Cloud Infrastructure S3).
4. **Camada de Integração Fiscal Procedural:**
   - [nfse_test/api.php](file:///e:/DEV/dinovatech/nfse_test/api.php): Contém as funções de montagem do XML ABRASF 2.04, canonicalização C14N, assinatura SHA1 e transporte SOAP/cURL.

---

## 2. Fluxo de Emissão Atual

1. **Gatilho:** O operador clica no botão "Emitir NFS-e" na tela da Fatura (`fatura_view.php`), enviando `action=gerar_nfse` e `id_fatura` para `app.php`.
2. **Montagem dos Dados:** `AppHelper::calculateNfseData($link, $id_fatura)`:
   - Valida se a fatura já possui nota concluída.
   - Lê as configurações do emissor da tabela `ConfiguracoesEmissor`.
   - Recupera os dados do tomador (CPF/CNPJ, Razão Social, Endereço completo, CEP, etc.).
   - Determina o próximo número sequencial de RPS:
     - Em produção: `ultimo_rps_producao + 1`
     - Em homologação: `ultimo_rps_homologacao + 1`
   - Formata os valores, alíquota de ISS e discriminação de serviço.
3. **Geração do XML:** `buildGerarNfseXml($inputApi)` em `nfse_test/api.php`:
   - Monta o bloco `<InfDeclaracaoPrestacaoServico Id="rps...">`.
   - Envolve o bloco na tag raiz `<Rps xmlns="http://www.abrasf.org.br/nfse.xsd">`.
4. **Carga do Certificado A1:**
   - Lê o arquivo PFX do banco (coluna `certificado_pfx_base64`) ou do disco (`caminho_certificado`).
   - Decriptografa a senha com `EncryptionHelper::decrypt()`.
   - Extrai chave privada e certificado com `openssl_pkcs12_read()`.
5. **Assinatura Digital (Hack Legado):**
   - No padrão legado ABRASF do ISS-DF, a prefeitura aceitava o formato apelidado de `support_combo`:
     - O atributo `Id` é removido da tag raiz.
     - A referência da assinatura é vazia: `URI=""`.
     - A tag `<Signature>` é anexada ao elemento `<Rps>`.
6. **Envio SOAP:**
   - O payload assinado é envolvido em `<GerarNfseEnvio xmlns="http://www.abrasf.org.br/nfse.xsd">`.
   - É criado o Envelope SOAP 1.1:
     ```xml
     <soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
       <soap:Body>
         <GerarNfse xmlns="http://nfse.abrasf.org.br">
           <nfseCabecMsg>&lt;cabecalho versao="2.04" ... /&gt;</nfseCabecMsg>
           <nfseDadosMsg>&lt;GerarNfseEnvio ... /&gt;</nfseDadosMsg>
         </GerarNfse>
       </soap:Body>
     </soap:Envelope>
     ```
   - O cURL envia requisição POST HTTPS com certificado A1 mTLS nos headers (`CURLOPT_SSLCERT`, `CURLOPT_SSLKEY`), `SOAPAction: "http://nfse.abrasf.org.br/GerarNfse"`.
7. **Tratamento de Resposta e Auto-Recuperação:**
   - Se a resposta contiver `<Numero>` e (`<CompNfse>` ou `<Nfse>`), a nota é considerada emitida com sucesso.
   - **Mecanismo de Resiliência / Double Check Imediato:** Se a prefeitura responder erro ou timeout, o sistema imediatamente executa uma consulta de RPS (`buildConsultarNfseRpsXml` / `ConsultarNfsePorRps`) para checar se a nota foi de fato gerada na retaguarda da prefeitura antes de acusar falha ao usuário.
   - Se confirmada a emissão, tenta consultar imediatamente o link de visualização via `buildConsultarUrlNfseXml`.
8. **Persistência:**
   - Salva registro em `NfseEmissoes`: `id_fatura`, `numero_rps`, `serie_rps`, `numero_nota`, `codigo_verificacao`, `ambiente`, `valor_servico`, `aliquota_iss`, `iss_retido`, `discriminacao`, `url_pdf`, `xml_envio`, `xml_retorno`, `status = 'concluido'`.
   - Incrementa o contador de RPS em `ConfiguracoesEmissor`.
   - Atualiza `Faturas` com `possui_nfse = 1` e `data_emissao_nfse = NOW()`.

---

## 3. Fluxo de Consulta Atual

O sistema atual implementa três mecanismos de consulta:

1. **Consulta por RPS (`ConsultarNfsePorRps`):**
   - XML de Envio: `<ConsultarNfseRpsEnvio xmlns="http://www.abrasf.org.br/nfse.xsd">` contendo `<IdentificacaoRps>` (Número, Série, Tipo) e `<Prestador>` (CNPJ, Inscrição Municipal).
   - Utilizado pelo double-check automático de emissão e pelo botão `sincronizar_rps_iss`.
2. **Consulta por Faixa / Notas Tomadas / Prestadas:**
   - Funções auxiliares presentes em `nfse_test/api.php` (`buildConsultarXml`), gerando `<ConsultarNfseServicoPrestadoEnvio>`.
3. **Consulta e Vinculação Manual (`case 'consultar_e_vincular_nfse'`):**
   - Permite que o operador informe um número de nota ou RPS para puxar da prefeitura os dados de uma nota já emitida diretamente no portal do ISS.net e vinculá-la à fatura do Dinovatech.

---

## 4. Fluxo de Cancelamento Atual

- **Situação Atual:**
  - Em `dinovatech/app.php` e no painel principal, **NÃO** existe uma ação ativa de cancelamento de NFS-e via Web Service integrada à UI de fatura.
  - A tabela `NfseEmissoes` suporta o status enum `'cancelado'`, mas o cancelamento no legado era feito manualmente pelo operador no portal do ISS.net ou através de scripts de teste avulsos (`CancelarNfseEnvio.xml` em `doc_issdf/padrao_antigo`).
  - No padrão antigo ABRASF 2.04, o cancelamento operava via mensagem `<CancelarNfseEnvio>` contendo `<Pedido>` com `<InfPedidoCancelamento>` assinado.

---

## 5. Fluxo de Obtenção da Nota e PDF Atual

- O WebService do ISS-DF (ABRASF 2.04) **não retorna o binário de PDF** da nota fiscal na emissão nem na consulta.
- Ele oferece o método específico: **`ConsultarUrlNfse`** (`http://nfse.abrasf.org.br/ConsultarUrlNfse`).
- XML de Envio: `<ConsultarUrlNfseEnvio>` contendo CNPJ, Inscrição Municipal e Número da Nota (ou dados do RPS).
- Retorno da Prefeitura: `<ConsultarUrlNfseResposta>` contendo a tag `<UrlVisualizacaoNfse>` ou `<Url>` (ex: `https://df.issnetonline.com.br/nfse/visualizar/XYZ...`).
- O Dinovatech armazena esse link na coluna `NfseEmissoes.url_pdf`.
- Na interface (`fatura_view.php`), o link abre essa URL oficial em uma nova aba do navegador.
- No `ContaDevHelper.php`, para o portal do cliente, o sistema verifica se existe arquivo PDF no OCI Object Storage ou gera link pré-assinado.

---

## 6. Certificado e Autenticação

1. **Tipo de Certificado:** Certificado Digital A1 no formato PKCS#12 (`.pfx`).
2. **Armazenamento:**
   - Preferencialmente gravado em Base64 na coluna `ConfiguracoesEmissor.certificado_pfx_base64`.
   - Fallback de compatibilidade via caminho em disco na coluna `ConfiguracoesEmissor.caminho_certificado`.
3. **Proteção da Senha:** A senha do certificado é armazenada criptografada no banco (via `EncryptionHelper::encrypt`) e decriptografada em tempo de execução via `openssl_decrypt` (AES-256-CBC).
4. **Extração:** É feito `openssl_pkcs12_read($pfxContent, $certs, $password)` para extrair:
   - `$certs['cert']`: Certificado X.509 público (PEM).
   - `$certs['pkey']`: Chave privada RSA (PEM).
5. **Autenticação em Transporte (TLS/mTLS):**
   - Como o cURL do PHP não consome chave privada diretamente da memória para conexão SSL de cliente sem arquivos temporários, o sistema grava temporariamente os certificados em `/tmp` via `tempnam(sys_get_temp_dir(), 'cert')` e `tempnam(sys_get_temp_dir(), 'key')`, passando para:
     - `CURLOPT_SSLCERT`
     - `CURLOPT_SSLKEY`
   - Após a execução do cURL, os arquivos temporários são deletados com `@unlink()`.

---

## 7. Assinatura Digital

No código legado (`assinarRoot` em `nfse_test/api.php`):
- **Padrão:** Subconjunto XMLDSig (`http://www.w3.org/2000/09/xmldsig#`).
- **Algoritmo de Digest:** SHA-1 (`http://www.w3.org/2000/09/xmldsig#sha1`).
- **Algoritmo de Assinatura:** RSA-SHA1 (`http://www.w3.org/2000/09/xmldsig#rsa-sha1`).
- **Canonicalização:** W3C C14N 20010315 (`http://www.w3.org/TR/2001/REC-xml-c14n-20010315`).
- **Transforms:** Enveloped Signature + Canonicalization.
- **Variação Utilizada (`support_combo`):**
  - URI de referência: vazia (`URI=""`).
  - O atributo `Id` do elemento alvo é limpo.
  - O elemento `<Signature>` não utiliza prefixo de namespace (usa `xmlns="http://www.w3.org/2000/09/xmldsig#"` diretamente).
  - A tag `<X509Certificate>` recebe a chave pública limpa (sem cabeçalhos PEM).

---

## 8. Estruturas XML Utilizadas (Legado ABRASF 2.04)

- Namespace Principal: `xmlns="http://www.abrasf.org.br/nfse.xsd"`
- Envelope SOAP:
  - Header: `http://nfse.abrasf.org.br` -> `<nfseCabecMsg>` com `<cabecalho versao="2.04"><versaoDados>2.04</versaoDados></cabecalho>`
  - Body: `<nfseDadosMsg>` contendo o XML do método (com caracteres escapados via `htmlspecialchars(..., ENT_XML1)`).
- Operações utilizadas:
  - `GerarNfse`: `<GerarNfseEnvio><Rps><InfDeclaracaoPrestacaoServico>...</InfDeclaracaoPrestacaoServico><Signature>...</Signature></Rps></GerarNfseEnvio>`
  - `ConsultarNfsePorRps`: `<ConsultarNfseRpsEnvio><Pedido><IdentificacaoRps>...</IdentificacaoRps><Prestador>...</Prestador></Pedido><Signature>...</Signature></ConsultarNfseRpsEnvio>`
  - `ConsultarUrlNfse`: `<ConsultarUrlNfseEnvio><Pedido><Prestador>...</Prestador><NumeroNfse>...</NumeroNfse></Pedido><Signature>...</Signature></ConsultarUrlNfseEnvio>`

---

## 9. Persistência de Dados

### Tabela `NfseEmissoes`:
- `id_emissao` (PK)
- `id_fatura` (FK para `Faturas`)
- `data_emissao` (DATETIME)
- `ambiente` (`homologacao` ou `producao`)
- `valor_servico`, `aliquota_iss`, `iss_retido`, `item_lista_servico`, `discriminacao`
- `numero_rps` (INT)
- `serie_rps` (VARCHAR(5))
- `numero_nota` (VARCHAR(20) - número da nota fiscal municipal)
- `codigo_verificacao` (VARCHAR(50))
- `url_pdf` (TEXT - URL de visualização da nota no portal do ISS)
- `url_xml` (TEXT)
- `status` (`pendente`, `processando`, `concluido`, `erro`, `cancelado`)
- `mensagem_erro` (TEXT)
- `xml_envio` (LONGTEXT - payload completo assinado enviado)
- `xml_retorno` (LONGTEXT - resposta SOAP completa recebida)

### Tabela `ConfiguracoesEmissor`:
- Identificação: `cnpj`, `inscricao_municipal`, `codigo_municipio` (5300108 = Brasília/DF), `razao_social`.
- Tributação: `regime_tributario`, `optante_simples`.
- Parâmetros NFS-e: `ambiente_padrao`, `serie_rps`, `ultimo_rps_homologacao`, `ultimo_rps_producao`.
- Segurança: `certificado_pfx_base64`, `caminho_certificado`, `senha_certificado`.

---

## 10. Dependências Internas

- `AppHelper::calculateNfseData`: Orquestra a montagem do snapshot de dados e regras tributárias (alíquota, retenção, discriminação) baseado na fatura e no serviço.
- `EncryptionHelper`: Criptografia da senha do PFX.
- `database.php`: Funções de conexão MySQLi (`DBConnect`, `DBExecute`, `DBClose`).
- `fatura_view.php`: Renderizador da interface web para faturas e botões de disparo de NFS-e.

---

## 11. Dependências Externas

- **Extensão PHP `openssl`:** Leitura de PKCS#12 (`openssl_pkcs12_read`) e assinatura criptográfica (`openssl_sign`).
- **Extensão PHP `curl`:** Transporte HTTP/HTTPS com suporte a mTLS (client certificates).
- **Extensão PHP `dom` / `libxml`:** Manipulação do DOM e Canonicalização XML C14N (`DOMDocument::C14N`).
- **Servidores SOAP do ISS-DF (Nota Control / ISS.net):**
  - `df.issnetonline.com.br`
  - `www.issnetonline.com.br`

---

## 12. Pontos que NÃO Podem ser Reutilizados pela Nova Integração

1. **Funções geradoras de XML de `nfse_test/api.php` (`buildGerarNfseXml`, etc.):** Totalmente incompatíveis com a estrutura do Padrão Nacional (troca de RPS por DPS, mudança drástica de tags, novo grupo de IBS/CBS, namespaces `http://www.sped.fazenda.gov.br/nfse`).
2. **Formato do Identificador e Assinatura com `URI=""` (`support_combo`):** O Padrão Nacional exige explicitamente identificador de 45 caracteres (`Id="DPS..."`) e URI de assinatura `#DPS...`, com regras estritas de canonicalização e inclusão do namespace nacional.
3. **Endpoints SOAP legados:** `df.issnetonline.com.br/webservicenfse204/nfse.asmx` deixará de ser o endpoint oficial do Padrão Nacional.
4. **Schemas e Namespaces ABRASF:** Namespace `http://www.abrasf.org.br/nfse.xsd` e cabeçalho `cabecalho versao="2.04"` não são mais aceitos.
5. **Campos exclusivos de RPS:** Nomenclatura e convenções antigas de RPS não existem na DPS do Padrão Nacional.

---

## 13. Pontos que Podem ser Reaproveitados (Componentes Genéricos)

1. **`EncryptionHelper`:** O mecanismo seguro de cifragem da senha do certificado digital A1 é completamente independente do padrão fiscal.
2. **Estrutura de carregamento e decodificação do PFX:** A rotina que lê `certificado_pfx_base64` do banco e invoca `openssl_pkcs12_read` é genérica e 100% aproveitável.
3. **Mecanismo de certificados temporários para mTLS no cURL:** A técnica de passar os arquivos PEM para `CURLOPT_SSLCERT` e `CURLOPT_SSLKEY` e fazer unlink no final funciona perfeitamente para qualquer webservice SOAP com autenticação por certificado de cliente.
4. **`AppHelper::calculateNfseData` (com adaptações):** A lógica de consolidar cliente tomador, endereço, dados da empresa emitente e valores de faturas é genérica de domínio, precisando apenas mapear os campos para o DTO do provedor nacional (ex: código de tributação nacional e NBS).
5. **Tabela `NfseEmissoes`:** A tabela atual armazena `id_fatura`, `xml_envio`, `xml_retorno`, `valor_servico`, `aliquota_iss`, `status` e `url_pdf`. Com a adição de colunas para suportar o padrão nacional (como `chave_nfse` de 50 dígitos e `id_dps` de 45 dígitos e `provider`), pode servir como base unificada para as duas integrações.
