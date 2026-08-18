# Walkthrough: Armazenamento de Certificados do Banco Inter no Banco de Dados (Base64)

Implementamos a migração completa do armazenamento de certificados e chaves privadas do Banco Inter, transferindo-os do sistema de arquivos exposto para colunas Base64 na tabela `ConfiguracoesEmissor`, gerando arquivos temporários efêmeros apenas durante o ciclo das requisições cURL e eliminando o arquivo público de debug.

---

## 🛠️ Modificações Realizadas

### 1. Migração de Banco de Dados
- **`database/migrations/20260217_0001_add_inter_certs_base64.sql`**:
  - Adiciona as colunas `api_inter_cert_base64`, `api_inter_key_base64` e `api_inter_ca_base64` como `LONGTEXT` na tabela `ConfiguracoesEmissor`.

### 2. Script de Migração de Dados Existentes
- **`scripts/migrar_certificados_inter_base64.php`**:
  - Script executável via CLI ou navegador que lê os certificados que estão atualmente no disco (a partir dos caminhos registrados no banco), converte para Base64 e atualiza o banco de dados.
  - Suporta a flag `--delete-files` para remover com segurança os arquivos físicos antigos do disco após a importação.

### 3. Backend e Consumo cURL Seguro
- **`inter/config.php`**:
  - Adicionada a função `inter_get_temp_cert_file()`.
  - Quando requisitado pelos endpoints de PIX (`inter/endpoint.php`, `inter/api.php`), decodifica o Base64 em arquivo temporário no diretório temp do SO (com permissão `0600`).
  - Utiliza `register_shutdown_function()` para garantir a exclusão automática de todos os arquivos temporários no encerramento do script PHP.
  - Mantém fallback transparente para os caminhos legados enquanto a migração não for executada.

### 4. Remoção do Endpoint de Debug
- **`inter/debug_config.php`**:
  - Arquivo excluído com sucesso do repositório.

### 5. Painel Administrativo & Upload
- **`dinovatech/app.php`**:
  - No formulário de configurações fiscais (`salvar_configuracao_fiscal`), os uploads de `.crt` e `.key` são codificados em Base64 e salvos direto no banco, sem gravar cópias no diretório `/certificado/inter/`.
  - No `get_config_fiscal`, envia flags booleanas de presença (`has_inter_crt`, `has_inter_key`, `has_inter_ca`) e remove dados sensíveis (chaves/senhas) do retorno JSON.
- **`dinovatech/config_fiscal.php`**:
  - Exibe feedback amigável no painel (*"Salvo no banco de dados"*).
