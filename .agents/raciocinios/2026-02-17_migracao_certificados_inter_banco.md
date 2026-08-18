# Raciocínio: Migração dos Certificados Inter para Base64 no Banco

## Contexto e Diagnóstico
O usuário questionou a segurança dos certificados do Banco Inter salvos em disco na pasta `/certificado/inter/`, levantando a possibilidade de download indevido caso ficassem expostos via web server (Apache/Nginx) e da presença de um endpoint de debug público (`inter/debug_config.php`).

## Investigação
1. Identificamos que a tabela `ConfiguracoesEmissor` mantinha apenas strings de caminhos (`api_inter_cert_path`, `api_inter_key_path`, `api_inter_ca_path`) e os arquivos físicos eram gravados em `/certificado/inter/`.
2. Como o PHP cURL exige caminhos no sistema de arquivos para autenticação mTLS (`CURLOPT_SSLCERT`, `CURLOPT_SSLKEY`, `CURLOPT_CAINFO`), desenhamos um padrão de arquivos efêmeros em memória/temp:
   - Os dados são mantidos em Base64 no banco de dados (`api_inter_cert_base64`, `api_inter_key_base64`, `api_inter_ca_base64`).
   - Ao executar chamadas cURL (`inter/config.php`), o PHP gera arquivos temporários via `tempnam(sys_get_temp_dir(), ...)` com permissões restritas (0600) e registra a exclusão com `register_shutdown_function()`.
   - Nenhum arquivo sensível permanece em diretório público do web server.

## Implementação Efetuada
1. **Migration SQL**: `database/migrations/20260217_0001_add_inter_certs_base64.sql`.
2. **Script de Migração**: `scripts/migrar_certificados_inter_base64.php` para ler os arquivos já salvos em disco no servidor, gravar no banco e opcionalmente apagar os arquivos físicos (`--delete-files`).
3. **Backend & Endpoints**: `inter/config.php` atualizado com o gerador temporário e fallback.
4. **Remoção de Debug**: `inter/debug_config.php` excluído.
5. **Painel de Configuração**: `dinovatech/app.php` e `dinovatech/config_fiscal.php` atualizados para upload direto em Base64 e proteção de chaves privadas no JSON de resposta.
