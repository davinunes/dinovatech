# NFS-e (ISS-DF) Integration Skills

## Overview
Integration with the ISS-DF (Brasília) SOAP API for Electronic Service Invoices. 
- **Endpoint**: `https://df.issnetonline.com.br/webservicenfse204/nfse.asmx` (Production).
- **Protocol**: SOAP 1.2 with digital signature (XMLDSig).
- **Authentication**: Client Certificate (`.pfx`) stored in `certificado/`.

## Key Components
### Data Preparation
`AppHelper::calculateNfseData($link, $id_fatura)`:
- Fetches Invoice, Client, and Items from DB.
- Validates mandatory fields (Address, CPF/CNPJ, Email).
- Determines Taxation Codes:
    - Checking `Recorrencias` overrides first.
    - Falling back to `Servicos` defaults.
- Returns structured array for XML generation.

### XML Generation & Signing
- `buildGerarNfseXml($data)`: Constructs the SOAP envelope and payload.
- `assinarRoot($xml, $certs, ...)`: Signs the `<Rps>` element using the PFX private key.

### Transmission
- `sendSoap(...)`: Sends the signed XML to the `GerarNfse` method.
- **Async Processing**: The API returns a generated NFS-e immediately or errors out. We store the returned XML and status.

## Status Workflow
- **Pending**: Not yet sent.
- **Processando**: Sent, awaiting result (rare for synchronous `Gerar`).
- **Concluido**: Successfully generated (`<Numero>` and `<CompNfse>` present in response).
- **Erro**: Any other response or SOAP Fault.

### Error Handling
- The Admin UI groups non-success statuses into a collapsible "Failures/Attempts" section.
- Errors are often due to:
    - Invalid CNAE/Service Code combination.
    - Client data mismatch (CPF/CNPJ).
    - Certificate expiry.
    - Rate limiting or temporary ISS instability ("Lote em processamento").

## Database Tables
- `NfseEmissoes`: Stores the history of attempts (XML sent/received, timestamps, status).
- `ConfiguracoesEmissor`: Stores the PFX path, password, and RPS sequence numbers.
