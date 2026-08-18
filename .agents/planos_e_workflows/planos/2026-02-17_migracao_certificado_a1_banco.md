# Plano: Migração do Certificado Digital A1 (.pfx) para o Banco de Dados (Base64)

Armazenar o certificado digital fiscal A1 (`.pfx`) diretamente no banco de dados codificado em Base64 na tabela `ConfiguracoesEmissor`, eliminando completamente a dependência de arquivos `.pfx` no disco e garantindo que o OpenSSL processe o certificado diretamente da memória.

---

## 1. Migração de Banco de Dados
- **Arquivo:** `database/migrations/20260217_0002_add_certificado_pfx_base64.sql`
- **Coluna:** `certificado_pfx_base64` `LONGTEXT DEFAULT NULL`

---

## 2. Script de Migração
- **`scripts/migrar_certificados_inter_base64.php`**:
  - Migrar também o arquivo `.pfx` configurado em `caminho_certificado` para `certificado_pfx_base64`.
  - Suportar exclusão do `.pfx` com a flag `--delete-files`.

---

## 3. Backend e Emissão Fiscal
- **`dinovatech/app.php`**:
  - Upload direto para Base64 no banco.
  - Leitura em memória no `get_config_fiscal` para validação de validade.
  - Emissão e cancelamento de NFSe consumindo `$pfxContent` direto de `base64_decode()`.
- **`nfse_test/api.php`**:
  - Priorizar `certificado_pfx_base64`.

---

## 4. Interface Gráfica
- **`dinovatech/config_fiscal.php`**:
  - Exibir status "Salvo no banco de dados" para o certificado A1.
