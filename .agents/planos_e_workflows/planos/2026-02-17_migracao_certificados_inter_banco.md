# Plano: Migração do Armazenamento de Certificados do Banco Inter para o Banco de Dados (Base64)

Armazenar os arquivos de certificado (.crt), chave privada (.key) e cadeia CA (.crt) da integração do Banco Inter diretamente no banco de dados codificados em Base64 na tabela `ConfiguracoesEmissor`, eliminando a necessidade de expor arquivos sensíveis em diretórios públicos no servidor web e removendo a página de debug pública `inter/debug_config.php`.

---

## 1. Banco de Dados / Migrations

- **Arquivo:** `database/migrations/20260217_0001_add_inter_certs_base64.sql`
- **Colunas:**
  - `api_inter_cert_base64` `LONGTEXT DEFAULT NULL`
  - `api_inter_key_base64` `LONGTEXT DEFAULT NULL`
  - `api_inter_ca_base64` `LONGTEXT DEFAULT NULL`

---

## 2. Backend & Gerenciamento de Certificados

- **`inter/config.php`**:
  - Recupera as strings em Base64 (`api_inter_cert_base64`, `api_inter_key_base64`, `api_inter_ca_base64`) do registro `ConfiguracoesEmissor`.
  - Se existirem, decodifica (`base64_decode`) e grava em arquivos temporários em local seguro do SO (via `sys_get_temp_dir()`) com permissões restritas.
  - Registra função de limpeza (`register_shutdown_function`) para excluir os arquivos temporários automaticamente ao término da requisição.
  - Fornece esses paths temporários para `$sslCertFile`, `$sslKeyFile`, `$caInfoFile` consumidos pelo cURL em `inter/api.php` e `inter/endpoint.php`.
  - Mantém fallback para os caminhos antigos caso a Base64 ainda não tenha sido enviada.
- **`inter/debug_config.php`**:
  - Excluir o arquivo para eliminar a exposição pública.

---

## 3. Painel Administrativo & Upload

- **`dinovatech/app.php`**:
  - No endpoint `salvar_configuracao_fiscal`:
    - Ao receber `arquivo_inter_crt`, `arquivo_inter_key` ou `arquivo_inter_ca`, lê o conteúdo via `file_get_contents()` do arquivo temporário de upload, converte para `base64_encode()` e salva diretamente no banco (`api_inter_cert_base64`, etc.).
    - Remove a gravação em disco dentro da pasta pública `/certificado/inter/`.
  - No endpoint `get_config_fiscal`:
    - Retorna flags de status (`has_inter_crt`, `has_inter_key`, `has_inter_ca`) para indicar visualmente se os certificados já estão cadastrados, sem expor chaves privadas para o cliente.
- **`dinovatech/config_fiscal.php`**:
  - Atualizar a interface de configuração para exibir status amigável ("✅ Salvo no banco") em vez do path do arquivo.
