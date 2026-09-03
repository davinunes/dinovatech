---
name: nfse_migracao_nacional
description: Guia e padrões para desenvolvimento, validação de XML, assinatura digital XMLDSig e integração SOAP com o Novo Padrão Nacional da NFS-e (ISS-DF / Nota Control).
---

# Skill: Migração NFS-e Padrão Nacional (ISS-DF)

Esta skill orienta o desenvolvimento e a manutenção da integração com o **Padrão Nacional da NFS-e** no sistema Dinovatech.

## 1. Referências Técnicas
- **Manual de Integração:** `doc_issdf/novo_padrao_nacional/Manual_integracao_v101.pdf` (Versão 1.01)
- **Modelos XML:** `doc_issdf/novo_padrao_nacional/modelos-xml/`
- **WSDLs Oficiais:**
  - Homologação: `doc_issdf/novo_padrao_nacional/xsd-homol.xml` (`https://nfse.issnetonline.com.br/nfse.asmx`)
  - Produção: `doc_issdf/novo_padrao_nacional/xsd-prod.xml` (`https://nfse.fazenda.df.gov.br/nfse.asmx`)
- **Namespace:** `http://www.sped.fazenda.gov.br/nfse`

## 2. Regras Críticas de Assinatura Digital (XMLDSig)
1. **Nunca use `URI=""` no novo padrão.**
2. O identificador da DPS (`Id="DPS..."`) possui exatamente **45 dígitos**:
   - `"DPS"` (3 chars) + `cLocEmi` (7 dígitos IBGE) + `tipoInscricao` (1 dígito: 1=CNPJ, 2=CPF) + `inscricaoFederal` (14 dígitos com zeros à esquerda) + `serie` (5 dígitos) + `nDPS` (15 dígitos).
3. A tag `<Signature>` fica no mesmo nível que `<infDPS>`, filha de `<DPS>`.
4. O elemento `Reference URI` deve apontar para o ID com `#`:
   - `<Reference URI="#DPS5300108...">`
5. Algoritmos:
   - Canonicalização: `http://www.w3.org/TR/2001/REC-xml-c14n-20010315`
   - Assinatura: `http://www.w3.org/2000/09/xmldsig#rsa-sha1`
   - Digest: `http://www.w3.org/2000/09/xmldsig#sha1`

## 3. Envelope SOAP e Cabeçalho
- **Envelope:** SOAP 1.1 estilo Document/Literal wrapped.
- **Dois parâmetros textuais:**
  - `nfseCabecMsg`:
    ```xml
    <cabecalho versao="1.01" xmlns="http://www.sped.fazenda.gov.br/nfse">
      <versaoDados>1.01</versaoDados>
    </cabecalho>
    ```
  - `nfseDadosMsg`: Payload XML do método escapado ou envolvido em CDATA.

## 4. Métodos do Web Service
- `GerarNfse`: Emissão síncrona de 1 DPS (método principal).
- `ConsultarNfsePorDps`: Consulta síncrona da nota pelo número/série da DPS (resiliência).
- `ConsultarUrlNfse`: Consulta links de visualização municipal e nacional.
- `CancelarNfse`: Solicitação de cancelamento via evento `e101103` (`infPedReg Id="PRE..."`).
