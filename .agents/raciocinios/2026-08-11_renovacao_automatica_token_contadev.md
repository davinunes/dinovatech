# Raciocínio Analítico: Renovação Automática do Token ContaDev

**Data**: 2026-08-11
**Tópico**: Renovação automática transparente do token ContaDev via credenciais salvas

## 1. Problema Identificado
Os tokens de autenticação da plataforma ContaDev expiram com o tempo. Requisições enviadas com token expirado retornam HTTP status 401 com o payload:
```json
{"message":"Invalid or expired token"}
```

Apenas realizar requisições `GET /platform/me` periodicamente não garante a extensão do ciclo de vida do token.

## 2. Decisão de Arquitetura
1. **Abortar temporariamente o `chronos.php`**: focar na solução transparente dentro do próprio fluxo da aplicação.
2. **Criptografia e Armazenamento da Senha**: Armazenar o campo `contadev_password` criptografado através do `EncryptionHelper` na tabela `ConfiguracoesEmissor`.
3. **Mecanismo `getValidToken($link)`**:
   - Sempre que a aplicação precisa de um token, executa primeiramente a validação rápida em `GET /platform/me`.
   - Se o token responde HTTP 200, ele é utilizado normalmente.
   - Se o token responde HTTP 401 (ou falha na validação), o sistema recupera a senha e o e-mail salvos e dispara o re-login em `ContaDevHelper::login()`.
   - Um novo token é gerado, criptografado e salvo automaticamente no banco de dados.
   - Toda a operação é transparente para o usuário final e fica registrada nos logs de auditoria (`config_contadev_logs`).
