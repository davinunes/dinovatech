# Walkthrough: Armazenamento do Certificado Digital A1 (.pfx) no Banco de Dados (Base64)

Concluímos a migração do armazenamento do Certificado Digital Fiscal A1 (`.pfx`) para o banco de dados na coluna `certificado_pfx_base64` da tabela `ConfiguracoesEmissor`. O processamento com OpenSSL e a assinatura de NFSe agora ocorrem 100% em memória.

---

## 🛠️ Alterações Efetuadas

### 1. Migração SQL
- **`database/migrations/20260217_0002_add_certificado_pfx_base64.sql`**:
  - Adiciona a coluna `certificado_pfx_base64` (`LONGTEXT`) na tabela `ConfiguracoesEmissor`.

### 2. Script de Migração Unificado
- **`scripts/migrar_certificados_inter_base64.php`**:
  - Atualizado para migrar tanto os certificados do Banco Inter quanto o Certificado A1 (`.pfx`).
  - Lê o arquivo `.pfx` em disco, converte para Base64 e salva no banco de dados.
  - Ao passar a flag `--delete-files`, remove com segurança os arquivos físicos antigos (`.pfx`, `.crt`, `.key`) do disco.

### 3. Backend e Emissão Fiscal
- **`dinovatech/app.php`**:
  - **Upload (`salvar_configuracao_fiscal`):** Valida a senha do `.pfx` com OpenSSL e salva a string Base64 diretamente no banco de dados, sem salvar arquivos na pasta `/certificado/`.
  - **Validação de Vencimento (`get_config_fiscal`):** Decodifica `certificado_pfx_base64` em memória para extrair a data de expiração e dias restantes. Remove o Base64 do retorno JSON.
  - **Geração e Consulta de NFSe (`gerar_nfse_fatura` e `consultar_nfse`):** Carrega `$pfxContent` diretamente de `base64_decode($config['certificado_pfx_base64'])` para assinar o XML e enviar ao webservice via SOAP.
- **`nfse_test/api.php`**:
  - Atualizado para carregar o binário diretamente de `certificado_pfx_base64`.

### 4. Interface do Painel
- **`dinovatech/config_fiscal.php`**:
  - Exibe o status *"Salvo no banco de dados"* com os indicadores de validade do certificado A1.
