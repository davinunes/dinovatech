# Raciocínio Analítico: Migração NFS-e ISS-DF (ABRASF 2.04 para Padrão Nacional)

**Data:** 2026-09-03  
**Contexto:** Transição da Nota Fiscal de Serviços Eletrônica do Distrito Federal (ISS-DF) operando em ABRASF 2.04 para o Novo Padrão Nacional (Nota Control / SPED Fazenda).

---

## 1. Hipóteses e Diagnóstico Inicial

### 1.1. Onde reside a integração legada atual?
- **Descoberta:** Ao contrário de um módulo formal encapsulado com orientação a objetos, a integração com ABRASF 2.04 está concentrada em:
  - `dinovatech/app.php`: rotas/ações ajax (`case 'gerar_nfse'`, `case 'sincronizar_rps_iss'`, `case 'consultar_e_vincular_nfse'`, `case 'preview_nfse_data'`).
  - `dinovatech/helpers/AppHelper.php`: método estático `calculateNfseData($link, $id_fatura)` que faz o levantamento e validação dos dados fiscais da fatura/cliente/serviço.
  - `dinovatech/helpers/ContaDevHelper.php`: obtenção de XML/PDF e links pré-assinados (OCI Object Storage).
  - `dinovatech/ver_nfse_xml.php`: endpoint de download/visualização direta do XML armazenado no banco.
  - `dinovatech/fatura_view.php`: interface gráfica com botões de emissão, consulta e link de PDF/XML.
  - `nfse_test/api.php`: scripts de apoio procedural que foram importados via `require_once '../nfse_test/api.php'` em `app.php`. Contém `buildGerarNfseXml`, `assinarRoot`, `sendSoap`, `buildConsultarUrlNfseXml`, `buildConsultarNfseRpsXml`, `buildConsultarCadastralXml`, `buildConsultarRpsDisponivelXml`.
  - Tabelas de banco de dados: `NfseEmissoes` (histórico de emissões, xml_envio, xml_retorno, numero_nota, numero_rps, serie_rps, status, url_pdf) e `ConfiguracoesEmissor` (dados da empresa, certificados PFX, senhas encriptadas, último RPS homologação/produção, série RPS, regime).

### 1.2. O que o Novo Padrão Nacional exige (Manual v1.01)?
- **Substituição Conceitual:** O conceito de RPS (Recibo Provisório de Serviços) é substituído pela **DPS** (Declaração de Prestação de Serviços).
- **Protocolo de Transporte SOAP:**
  - WSDL: `http://www.sped.fazenda.gov.br/nfse` (definido nos arquivos `xsd-homol.xml` e `xsd-prod.xml`).
  - Formato: Document/Literal wrapped, com 2 parâmetros principais de entrada: `nfseCabecMsg` (XML de cabeçalho com versão 1.00 ou 1.01) e `nfseDadosMsg` (XML do payload da operação, escapado ou CDATA).
  - Métodos SOAP disponíveis:
    1. `GerarNfse` (Síncrono - gera NFS-e a partir de 1 DPS).
    2. `RecepcionarLoteDpsSincrono` (Síncrono - lote de DPS com retorno imediato).
    3. `RecepcionarLoteDps` (Assíncrono - retorna protocolo).
    4. `ConsultarLoteDps` (Síncrono - consulta status e notas do lote).
    5. `ConsultarNfseDps` (Síncrono - consulta NFS-e gerada por chave/série/número da DPS).
    6. `ConsultarNfseServicoPrestado` (Síncrono - notas por prestador/período/tomador).
    7. `ConsultarNfseServicoTomado` (Síncrono - serviços tomados).
    8. `ConsultarNfsePorFaixa` (Síncrono - faixa de notas).
    9. `CancelarNfse` (Síncrono - evento de cancelamento e101103).
    10. `ConsultarUrlNfse` (Síncrono - links de visualização, autenticidade e visualização nacional).
    11. `ConsultarDadosCadastrais` (Síncrono).
    12. `ConsultarDpsDisponivel` (Síncrono).
    13. `ValidarXml` (Síncrono - validação prévia de XML contra o XSD do fisco).
- **Assinatura Digital (XMLDSig):**
  - Canonicalização: C14N (`http://www.w3.org/TR/2001/REC-xml-c14n-20010315`).
  - Algoritmo de Assinatura: RSA com SHA-1 (`http://www.w3.org/2000/09/xmldsig#rsa-sha1`).
  - Referência (URI): A DPS possui um identificador de 45 caracteres (`Id="DPS..."`). A assinatura da DPS fica dentro de `<DPS>` e referencia especificamente `<infDPS Id="...">` via `URI="#DPS..."` (diferente do hack de `URI=""` do legado ABRASF).
  - No cancelamento (`pedRegEvento`), o Id é `PRE` + Chave NFS-e (50) + Tipo de Evento (6) -> `URI="#PRE..."`.
- **Certificados Digitais A1:**
  - TLS: Usado na conexão HTTPS/SOAP mTLS (autenticação de cliente).
  - XML: Usado para assinar a DPS e o Lote/Evento com a chave privada ICP-Brasil.
- **Visualização e PDF:**
  - O manual **NÃO** disponibiliza método que retorne arquivo PDF binário ou base64.
  - O método `ConsultarUrlNfse` retorna:
    - `<UrlVisualizacaoNfse>` (URL da prefeitura)
    - `<UrlVerificaAutenticidade>`
    - `<UrlVisualizacaoNfseNacional>` (Portal Nacional da NFS-e)
  - Portanto, a estratégia do Dinovatech deverá persistir a URL oficial de visualização e, se necessário para envio ao cliente final por WhatsApp/e-mail, manter geração de espelho/DANFSE via gerador interno (ou WeasyPrint já presente no docker-compose do projeto).

---

## 2. Decisões Arquiteturais e Coexistência

1. **Desacoplamento Completo:**
   - Criar uma pasta isolada `/dinovatech/modules/Fiscal/` (ou `/dinovatech/nfse/` conforme padrão do projeto):
     - `Contracts/NfseProviderInterface.php`
     - `DTOs/` (NfseData, EmissionResult, QueryResult, CancellationResult, UrlResult)
     - `Providers/LegacyAbrasfProvider.php` (encapsulando o código legado sem mexer em sua lógica)
     - `Providers/NacionalProvider.php` (nova implementação pura, com builders, assinatura e cliente SOAP próprios)
     - `Services/NfseService.php` (orquestrador que resolve o provider ativo via configuração).
2. **Coexistência via Configuração:**
   - Coluna ou parâmetro em banco (`ConfiguracoesEmissor.nfse_provider` com enum `legacy` | `nacional`) ou variável de ambiente / configuração do sistema.
   - Preservar o histórico: Notas já emitidas pelo padrão antigo mantêm seus dados e links intactos em `NfseEmissoes`.
   - Adicionar identificador de padrão/chave nacional nas tabelas para suportar os 50 dígitos da chave da NFS-e Nacional e os 45 dígitos do ID da DPS.
3. **Plano de Testes e Validação:**
   - Testes com fixtures de XML reais (extraídos dos modelos fornecidos pela Nota Control).
   - Validador de schema contra `schema_v101.xsd` e testes unitários de assinatura e digest.
