# Banco Inter Integration Skills

## Overview
This integration handles PIX payments (generation, status check, and cancellation). It relies on mTLS authentication with certificates.

## Authentication & Configuration
- **Certificates**: 
    - Requires a `.crt` (cert) and `.key` (private key) pair.
    - Also requires the Inter CA chain (`ca.crt`).
    - Stored in `certificado/inter/` (managed via `config_fiscal.php`).
- **Credentials**:
    - `Client ID` and `Client Secret` are stored in `ConfiguracoesEmissor`.
    - `Client Secret` is encrypted in the database.
- **Environment**: Supports both Sandbox (Homologacao) and Production.

## Key Files
- `inter/endpoint.php`: Main entry point for frontend AJAX calls related to PIX.
- `inter/api.php`: Contains the low-level CURL wrapper and helper functions (`getInterAccessToken`, `newInstantPix`, `consultarPix`).

## Core Functions
### `getInterAccessToken(...)`
Required for most calls. Obtains an OAuth2 token using the client credentials and mTLS certs. Token is short-lived (usually 1 hour).

### `newInstantPix(...)`
Generates a dynamic QRCode (Pix Copia e Cola).
- Input: `devedor`, `valor`, `chavePix`, `infoAdicionais`.
- Returns: `txid`, `pixCopiaECola`, `calendario` (expiration).

### `consultarPix(...)`
Checks the status of a specific PIX by `txid`.
- Returns: Status (`CONCLUIDA`, `ATIVA`, `EXPIRADA`, etc.) and E2E ID if paid.

## Logic Flow
1. **Frontend**: Calls `endpoint.php?action=obter_ou_criar_pix_pagamento`.
2. **Backend**:
    - Checks for existing Pending payment for the invoice.
    - If valid and unexpired, returns existing QRCode.
    - If expired or amounts differ, generates a new one.
    - Saves `txid` and `qrcode` in `Pagamentos` table.
3. **Verification**:
    - Frontend button checks status via `endpoint.php?action=verificar_pagamento_pix&txid=...`.
    - If status is `CONCLUIDA`, updates `Pagamentos` to `Confirmado` and potentially settles the Invoice.

## Common Issues
- **Certificate Expiry**: Ensure `.crt` and `.key` are valid.
- **Scope**: Ensure the App on Inter Developer Portal has `pix.write` and `pix.read` scopes essential.
- **Webhook**: TODO (Currently relying on manual verification or polling).
