---
name: nfse_migracao_nacional
description: Guia e padrões para desenvolvimento, validação de XML, assinatura digital XMLDSig, integração SOAP (ISS-DF / Nota Control) e integração REST (Sefin Nacional / RFB) com o Novo Padrão Nacional da NFS-e.
---

# Skill: Migração e Integração NFS-e Padrão Nacional

Esta skill orienta o desenvolvimento, operação e manutenção da integração com o **Padrão Nacional da NFS-e** no sistema Dinovatech, cobrindo tanto o canal municipal conveniado (**SEFAZ-DF via SOAP**) quanto o canal direto da Receita Federal (**Sefin Nacional via REST JSON**).

---

## 1. Arquitetura Geral do Padrão Nacional e Canais Disponíveis

No Padrão Nacional da NFS-e (SNNFSe / SPED Fazenda), o documento gerado pelo emitente é sempre a **DPS (Declaração de Prestação de Serviço)** assinada com XMLDSig. Existem dois canais de transmissão disponíveis no Dinovatech:

### Canal A: SEFAZ-DF / Nota Control (SOAP) - `provider = 'nacional'`
- **Papel:** Brasília/DF é um município conveniado ao Sistema Nacional. O contribuinte envia a DPS para o WebService do DF via envelope SOAP 1.1 Document/Literal.
- **Processamento:** A SEFAZ-DF autoriza a nota, gera a Chave de Acesso Nacional de 50 dígitos e replica automaticamente a NFS-e para o **ADN (Ambiente de Dados Nacional)**.
- **WSDLs / Endpoints:**
  - Produção: `https://nfse.fazenda.df.gov.br/wsnfsenacional/nfse.asmx`
  - Homologação: `https://nfse.issnetonline.com.br/wsnfsenacional/homologacao/nfse.asmx`
- **Classe Implementadora:** `Dinovatech\Modules\Fiscal\Providers\NacionalProvider`

### Canal B: Emissor Público Nacional RFB (REST JSON) - `provider = 'sefin_nacional'`
- **Papel:** Canal direto do governo federal (Receita Federal / SERPRO / Sefin Nacional). Usado para emissão direta independente da prefeitura.
- **Processamento:** API RESTful moderna com autenticação mútua TLS (mTLS) via certificado digital A1 ICP-Brasil. O payload JSON transporta a DPS assinada compactada em **GZip** e codificada em **Base64** (`dpsXmlGZipB64`).
- **Base URLs:**
  - Produção: `https://sefin.nfse.gov.br/SefinNacional`
  - Homologação (Produção Restrita): `https://sefin.producaorestrita.nfse.gov.br/API/SefinNacional`
- **Classe Implementadora:** `Dinovatech\Modules\Fiscal\Providers\SefinNacionalRestProvider` (com `Dinovatech\Modules\Fiscal\Http\RestClient`)

---

## 2. Regras de Numeração, Séries e Controle da DPS vs NFS-e

> [!IMPORTANT]
> **A DPS é de controle do seu ERP; a NFS-e é de controle do Fisco.**

1. **Série e Sequencial da DPS (`serie_dps`, `ultimo_dps_producao`):**
   - A DPS é gerada e numerada **internamente pelo Dinovatech**.
   - Você **NÃO precisa pedir ou obter autorização de numeração de DPS** no portal federal nem na SEFAZ. O emitente é responsável pelo sequencial incremental estrito por série.
   - No Padrão Nacional, as séries de DPS devem ser **obrigatoriamente numéricas (1 a 5 dígitos)** (ex: `1`, `2`, `900`). Letras (como `"RPS"` ou `"A"`) violam o tipo XSD `TSSerieDPS` e resultam em erro `[E160]`.
   - **Continuidade de numeração:**
     - Se mantiver a série atual (ex: série `1`), basta seguir o próximo número (`ultimo_dps_producao + 1`).
     - Se desejar separar canais ou reiniciar a contagem sem risco de colisão de ID, basta criar uma nova série numérica no painel (ex: série `2`) iniciando no número `1`.
2. **Número da NFS-e (`nNFSe`) e Chave de Acesso (50 dígitos):**
   - São gerados exclusivamente pelo autorizador (SEFAZ ou Sefin Nacional) no momento da autorização.
   - O Dinovatech armazena a chave e o número retornado em `NfseEmissoes.chave_nfse` e `NfseEmissoes.numero_nota`.

---

## 3. Regras Críticas de Assinatura Digital (XMLDSig)

O XML da DPS assinado é **100% idêntico** para ambos os canais:
1. **Nunca use `URI=""` no novo padrão.**
2. O identificador da DPS (`Id="DPS..."`) possui exatamente **45 dígitos**:
   - `"DPS"` (3 chars) + `cLocEmi` (7 dígitos IBGE) + `tipoInscricao` (1 dígito: 2=CNPJ, 1=CPF) + `inscricaoFederal` (14 dígitos com zeros à esquerda) + `serie` (5 dígitos) + `nDPS` (15 dígitos).
3. A tag `<Signature>` fica no mesmo nível que `<infDPS>`, filha de `<DPS>`.
4. O elemento `Reference URI` deve apontar para o ID com `#`: `<Reference URI="#DPS5300108...">`.
5. Algoritmos:
   - Canonicalização: `http://www.w3.org/TR/2001/REC-xml-c14n-20010315`
   - Assinatura: `http://www.w3.org/2000/09/xmldsig#rsa-sha1`
   - Digest: `http://www.w3.org/2000/09/xmldsig#sha1`

---

## 4. Endpoints e Métodos da API REST Sefin Nacional (`sefin_nacional`)

### 4.1. Emissão Síncrona (`POST /nfse`)
- **Headers:** `Content-Type: application/json; charset=utf-8`, `Accept: application/json`
- **Payload:**
  ```json
  {
    "dpsXmlGZipB64": "H4sIC..."
  }
  ```
- **Retorno 201 (Sucesso):**
  ```json
  {
    "tipoAmbiente": 1,
    "versaoAplicativo": "1.0",
    "dataHoraProcessamento": "2026-10-31T17:00:00",
    "idDps": "DPS53001082...",
    "chaveAcesso": "53261012345678000195000000000000000000000000000001",
    "nfseXmlGZipB64": "H4sIC..."
  }
  ```
  O XML da NFS-e é obtido com `gzdecode(base64_decode($json['nfseXmlGZipB64']))`.
- **Retorno 400 / 403 / 500 (Erro de negócio ou validação):**
  Retorna array `erros` contendo objetos com `codigo`, `descricao` e `complemento`.

### 4.2. Consulta de Chave por DPS (`GET /dps/{id}`)
- Consulta o `dpsId` de 45 caracteres.
- Se a nota foi emitida, retorna HTTP 200 com a `chaveAcesso`.
- Permite auto-recuperação transparente em caso de instabilidade ou timeout na emissão.

### 4.3. Consulta de NFS-e por Chave (`GET /nfse/{chaveAcesso}`)
- Retorna o XML completo da nota em `nfseXmlGZipB64`.

### 4.4. Eventos e Cancelamento (`POST /nfse/{chaveAcesso}/eventos`)
- Carga útil: XML do `<pedRegEvento>` assinado, compactado em GZip e Base64 no campo `pedidoRegistroEventoXmlGZipB64`.
- Evento de cancelamento: `e101103` (Solicitação de Análise Fiscal para Cancelamento).

---

## 5. Visualização e Download da DANFSE

URLs oficiais consolidadas do Padrão Nacional:
- **Visualizador Web da NFS-e Nacional:**
  `https://www.nfse.gov.br/EmissorNacional/Notas/Visualizar/Index/{chaveAcesso}`
- **API DANFSE (Ambiente de Dados Nacional):**
  `https://adn.nfse.gov.br/danfse/docs/index.html` (Produção)
  `https://adn.producaorestrita.nfse.gov.br/danfse/docs/index.html` (Homologação)
- **Visualizador Local Dinovatech:**
  `dinovatech/ver_nfse_impressao.php`

---

## 6. Configuração no Painel Dinovatech

No menu de Configurações Fiscais (`config_fiscal.php` / `ConfiguracoesEmissor`), o campo `nfse_provider` pode assumir:
- `'nacional'`: Padrão Nacional via SEFAZ-DF (SOAP)
- `'sefin_nacional'`: Padrão Nacional Direto via Sefin Nacional / RFB (REST)
- `'legacy'`: ABRASF 2.04 legado (em descontinuação)
