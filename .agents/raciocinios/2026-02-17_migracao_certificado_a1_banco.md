# Raciocínio: Migração do Certificado Digital Fiscal A1 (.pfx) para Base64 no Banco de Dados

## Contexto e Diagnóstico
Após a migração dos certificados do Banco Inter para o banco de dados, avaliamos o armazenamento do Certificado Digital Fiscal A1 (`.pfx`). Embora o `.pfx` possua criptografia protegida por senha, mantê-lo em disco abre possibilidade de download indevido e tentativas de força bruta offline da senha. Decidiu-se migrar também o `.pfx` para o banco de dados.

## Decisões Técnicas
1. **OpenSSL em Memória**:
   - A função nativa `openssl_pkcs12_read($pfxContent, $certs, $pass)` do PHP consome diretamente o binário do certificado.
   - Portanto, a coluna `certificado_pfx_base64` na tabela `ConfiguracoesEmissor` permite realizar `base64_decode()` e validar/assinar notas fiscais diretamente na memória RAM, sem necessidade de arquivos temporários em disco.
2. **Retrocompatibilidade**:
   - Caso `certificado_pfx_base64` ainda não esteja preenchido no banco em algum ambiente, o sistema mantém fallback automático para ler o caminho legado em disco (`caminho_certificado`).
3. **Script de Migração Unificado**:
   - O script `scripts/migrar_certificados_inter_base64.php` foi expandido para detectar tanto os arquivos do Inter quanto o `.pfx` fiscal, persistir em Base64 no banco e opcionalmente remover os arquivos antigos do disco (`--delete-files`).

## Arquivos Afetados
- `database/migrations/20260217_0002_add_certificado_pfx_base64.sql`
- `scripts/migrar_certificados_inter_base64.php`
- `dinovatech/app.php` (Upload, `get_config_fiscal`, `gerar_nfse_fatura`, `consultar_nfse`)
- `nfse_test/api.php`
- `dinovatech/config_fiscal.php`
