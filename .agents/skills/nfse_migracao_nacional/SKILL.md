---
name: nfse_migracao_nacional
description: Guia e padrões para desenvolvimento, validação de XML, assinatura digital XMLDSig, integração SOAP (ISS-DF / Nota Control) e integração REST (Sefin Nacional / RFB) com o Novo Padrão Nacional da NFS-e.
---

# Skill: Migração e Integração NFS-e Padrão Nacional

Esta skill orienta o desenvolvimento, operação, diagnóstico e manutenção da integração com o **Padrão Nacional da NFS-e** no sistema Dinovatech, cobrindo o canal direto da Receita Federal (**Sefin Nacional via REST JSON**), o canal municipal conveniado (**SEFAZ-DF via SOAP**) e as rotinas de sincronização, importação de XML e diagnóstico de erros de rejeição.

---

## 1. Arquitetura Geral do Padrão Nacional e Canais Disponíveis

No Padrão Nacional da NFS-e (SNNFSe / SPED Fazenda), o documento gerado pelo emitente é sempre a **DPS (Declaração de Prestação de Serviço)** assinada com XMLDSig. Existem dois canais de transmissão disponíveis no Dinovatech:

### Canal Principal: Emissor Público Nacional RFB (REST JSON) - `provider = 'sefin_nacional'`
- **Papel:** Canal direto do governo federal (Receita Federal / SERPRO / Sefin Nacional). Usado para emissão direta independente de webservice de prefeitura.
- **Processamento:** API RESTful moderna com autenticação mútua TLS (mTLS) via certificado digital A1 ICP-Brasil. O payload JSON transporta a DPS assinada compactada em **GZip** e codificada em **Base64** (`dpsXmlGZipB64`).
- **Base URLs:**
  - Produção: `https://sefin.nfse.gov.br/SefinNacional`
  - Homologação (Produção Restrita): `https://sefin.producaorestrita.nfse.gov.br/API/SefinNacional`
- **Classes Implementadoras:**
  - `Dinovatech\Modules\Fiscal\Providers\SefinNacionalRestProvider`
  - `Dinovatech\Modules\Fiscal\Http\RestClient`
  - `Dinovatech\Modules\Fiscal\Security\CertificateManager`

### Canal Secundário: SEFAZ-DF / Nota Control (SOAP) - `provider = 'nacional'`
- **Papel:** Município conveniado ao Sistema Nacional. O contribuinte envia a DPS para o WebService do DF via envelope SOAP 1.1 Document/Literal.
- **Restrição Crucial (Erro EM110):** A partir de 01/11/2026, empresas optantes pelo Simples Nacional **não podem emitir por este canal**. Devem emitir obrigatoriamente pela API do Ambiente Nacional (`sefin_nacional`) ou pelo Emissor Nacional Web.
- **WSDLs / Endpoints:**
  - Produção: `https://nfse.fazenda.df.gov.br/wsnfsenacional/nfse.asmx`
  - Homologação: `https://nfse.issnetonline.com.br/wsnfsenacional/homologacao/nfse.asmx`
- **Classe Implementadora:** `Dinovatech\Modules\Fiscal\Providers\NacionalProvider`

---

## 2. Autoloading e Inicialização do Módulo Fiscal

Todas as classes do módulo fiscal residem sob a raiz de namespace `Dinovatech\Modules\Fiscal\`.

> [!CAUTION]
> **Autoloader Global Obrigatório:**
> O arquivo `dinovatech/modules/Fiscal/bootstrap.php` registra o autoloader PSR-4 e carrega os catálogos fiscais.
> Ele **DEVE** estar incluído obrigatoriamente no topo de `dinovatech/app.php` e `dinovatech/config.php`:
> ```php
> if (file_exists(__DIR__ . '/modules/Fiscal/bootstrap.php')) {
>     require_once __DIR__ . '/modules/Fiscal/bootstrap.php';
> }
> ```
> Omitir esta inclusão gera o erro fatal: `Class "Dinovatech\Modules\Fiscal\Services\NfseService" not found` em requisições assíncronas/AJAX.

---

## 3. Regras de Numeração, Séries e Controle da DPS vs NFS-e

> [!IMPORTANT]
> **A DPS é de controle do seu ERP; a NFS-e é de controle do Fisco.**

1. **Série e Sequencial da DPS (`serie_dps`, `ultimo_dps_producao`):**
   - A DPS é gerada e numerada **internamente pelo Dinovatech**.
   - Você **NÃO precisa pedir autorização prévia de numeração de DPS** na SEFAZ ou RFB. O emitente é responsável pelo sequencial incremental estrito por série.
   - Séries de DPS devem ser **obrigatoriamente numéricas (1 a 5 dígitos)** (ex: `1`, `15`, `900`). Letras (como `"RPS"` ou `"A"`) violam o tipo XSD `TSSerieDPS` e resultam no erro `[E160]`.
   - **Continuidade de numeração:**
     - Ao manter a série atual, segue o próximo número (`ultimo_dps_producao + 1`).
     - Para reiniciar contagem sem risco de colisão de ID, basta criar uma nova série numérica no painel (ex: série `15`) iniciando no número `1`.
2. **Número da NFS-e (`nNFSe`) e Chave de Acesso (50 dígitos):**
   - Gerados exclusivamente pelo autorizador no momento da autorização.
   - O Dinovatech armazena a chave e o número retornado em `NfseEmissoes.chave_nfse` e `NfseEmissoes.numero_nota`.

---

## 4. Regras Críticas de Assinatura Digital (XMLDSig)

O XML da DPS assinado é idêntico para emissão REST ou SOAP:
1. **Nunca use `URI=""`.**
2. O identificador da DPS (`Id="DPS..."`) possui exatamente **45 caracteres**:
   - `"DPS"` (3 chars) + `cLocEmi` (7 dígitos IBGE) + `tipoInscricao` (1 dígito: 2=CNPJ, 1=CPF) + `inscricaoFederal` (14 dígitos com zeros à esquerda) + `serie` (5 dígitos) + `nDPS` (15 dígitos).
3. A tag `<Signature>` fica no mesmo nível que `<infDPS>`, filha de `<DPS>`.
4. O elemento `Reference URI` deve apontar para o ID com `#`: `<Reference URI="#DPS5300108...">`.
5. Algoritmos:
   - Canonicalização: `http://www.w3.org/TR/2001/REC-xml-c14n-20010315`
   - Assinatura: `http://www.w3.org/2000/09/xmldsig#rsa-sha1`
   - Digest: `http://www.w3.org/2000/09/xmldsig#sha1`

---

## 5. Endpoints da API REST Sefin Nacional (`sefin_nacional`)

### 5.1. Emissão Síncrona (`POST /nfse`)
- **Headers:** `Content-Type: application/json; charset=utf-8`, `Accept: application/json`
- **Payload:** `{"dpsXmlGZipB64": "H4sIC..."}`
- **Retorno 201 (Sucesso):**
  Retorna `chaveAcesso`, `idDps`, `dataHoraProcessamento` e `nfseXmlGZipB64` contendo o XML completo da NFS-e autorizada.
- **Retorno 400 / 403 / 500 (Rejeição):**
  Array `erros` contendo objetos com `codigo`, `descricao` e `complemento`.

### 5.2. Consulta de Chave por DPS (`GET /dps/{id}`)
- Consulta o `dpsId` de 45 caracteres. Retorna a `chaveAcesso` caso a nota já tenha sido processada.

### 5.3. Consulta de NFS-e por Chave (`GET /nfse/{chaveAcesso}`)
- Retorna os dados completos da NFS-e e o `nfseXmlGZipB64`.
- Implementado em `SefinNacionalRestProvider::consultarPorChave()`.

### 5.4. Eventos e Cancelamento (`POST /nfse/{chaveAcesso}/eventos`)
- Carga útil: XML de `<pedRegEvento>` assinado, compactado em GZip e Base64 no campo `pedidoRegistroEventoXmlGZipB64`.
- Evento de cancelamento: `e101103` (Solicitação de Análise Fiscal para Cancelamento).

---

## 6. Catálogo de Erros Reais da SEFIN Nacional e Resoluções

| Código | Mensagem de Erro | Causa Raiz | Solução Aplicada no Dinovatech |
| :--- | :--- | :--- | :--- |
| **`EM110`** | *Não é permitido emitir NFSe através deste serviço na data de competência informada... Optante Simples Nacional até 31/10/2026.* | Tentativa de emissão de Simples Nacional via webservice municipal conveniado (Nota Control / SEFAZ-DF) com competência posterior a 31/10/2026. | Configurar `nfse_provider = 'sefin_nacional'` no painel fiscal para emitir direto pela API REST da Receita Federal. |
| **`E0120`** | *IM do prestador não deve ser informado, pois não existem informações complementares registradas no CNC NFS-e...* | Município de Brasília (`5300108`) não possui convênio de IM complementar cadastrado no CNC NFS-e. Enviar `<IM>` causa rejeição imediata. | No `DpsXmlBuilder.php`, omitir condicionalmente a tag `<IM>` quando `codigo_municipio === '5300108'` ou quando não houver exigência no CNC. |
| **`E0008`** | *A data de emissão da DPS não pode ser posterior à data do seu processamento.* | Fuso horário local ou relógio adiantado alguns segundos em relação ao relógio atômico do servidor da RFB. | No `DpsXmlBuilder.php`, usar `date('Y-m-d\TH:i:sP')` com fuso `America/Sao_Paulo` e margem segura (tolerância de até 30s se houver desvio NTP). |
| **`E1229`** | *Xml não está utilizando codificação UTF-8.* | Presença de caracteres ISO-8859-1, acentuação mal convertida ou BOM UTF-8 (`\xEF\xBB\xBF`). | Remover BOM e garantir sanitização UTF-8 pura antes da compactação e assinatura. |
| **`E0314`** | *O código de tributação municipal informado não existe ou não está administrado pelo município...* | Preenchimento indevido da tag `<cTribMun>` com código fictício que o município não administra. | Preencher estritamente `<cTribNac>` (6 dígitos, ex: `010501`). Se não houver correspondência municipal oficial, omitir `<cTribMun>`. |
| **`E1235`** | *Falha no esquema XML do DF-e.* | Tag fora de ordem no XSD versão 1.01 (ex: `tpAmb`, `dhEmi`, `verAplic`, `serie`, `nDPS`). | Seguir rigidamente a ordem sequencial estrita dos elementos estipulada pelo schema `DPS_v1.01.xsd`. |

---

## 7. Sincronização, Importação de XML e Vínculo de Notas

Para faturas cujo processamento foi realizado no portal do governo ou onde houve instabilidade de rede durante o retorno da emissão:

### 7.1. Sincronização Direta
- Na tela da fatura (`fatura_view.php`), o banner de sincronização oferece o botão **"Sincronizar DPS X"**.
- O sistema consulta a SEFIN Nacional pela DPS (`GET /dps/{id}`) ou pela chave, baixa o XML autorizado e vincula a fatura automaticamente.

### 7.2. Importação Direta de XML (`NfseService::importarXmlNfse`)
- No modal **Importar / Vincular NFS-e**, a aba **"Enviar XML"** aceita upload de arquivo `.xml` ou texto colado diretamente.
- O parser fiscal lê as tags:
  - `<chNFSe>` ou atributo `Id="NFS..."` -> Chave de Acesso (50 dígitos)
  - `<nNFSe>` ou `<nDFSe>` -> Número da Nota Fiscal
  - `<cVerif>` -> Código de Verificação
  - `<nDPS>` e `<serie>` -> DPS de origem
  - `<dhEmi>` / `<dhProc>` -> Data e hora de autorização
  - `<vServ>` -> Valor total do serviço
- Cria o registro na tabela `NfseEmissoes` com `status = 'concluido'` e atualiza `Faturas.possui_nfse = 1`.

---

## 8. Visualização e Download da DANFSE

URLs consolidadas do Padrão Nacional:
- **Visualizador Web da NFS-e Nacional:**
  `https://www.nfse.gov.br/EmissorNacional/Notas/Visualizar/Index/{chaveAcesso}`
- **Impressão Local DANFSE Dinovatech:**
  `dinovatech/ver_nfse_impressao.php?id={id_emissao}`
