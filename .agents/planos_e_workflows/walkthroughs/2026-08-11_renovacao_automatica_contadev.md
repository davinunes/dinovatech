# Walkthrough: Renovação Automática do Token ContaDev

## Alterações Realizadas

### Database / Migrations
- **[20260812_0001_add_contadev_password_config.sql](file:///e:/DEV/dinovatech/database/migrations/20260812_0001_add_contadev_password_config.sql)**: Criada migration para adicionar a coluna `contadev_password TEXT DEFAULT NULL` na tabela `ConfiguracoesEmissor`.

### Backend / Helpers
- **[ContaDevHelper.php](file:///e:/DEV/dinovatech/dinovatech/helpers/ContaDevHelper.php)**:
  - **Salvamento de Senha Criptografada**: O método `login()` passa a armazenar a senha criptografada (`contadev_password`) via `EncryptionHelper::encrypt()`.
  - **Validação e Renovação Transparente**: Criado o método `getValidToken($link)` que valida o token atual no ContaDev (endpoint `/platform/me`). Em caso de sessão expirada (HTTP 401), realiza o re-login automático com as credenciais salvas e atualiza o token no banco.
  - **Desconexão Segura**: O método `disconnect()` limpa o campo `contadev_password` juntamente com as demais credenciais da integração.
  - **Integração nas Operações**: Atualizados os métodos `getAccountStatus()` e `importInvoice()` para utilizar o token obtido via `getValidToken()`.

## Como Validar
1. **Novo Login**: Realize o login no ContaDev através da aba de integrações (`config_fiscal.php`). A senha será salva criptografada no banco.
2. **Auto-refresh**: Quando um token expirar, ao consultar o status ou importar uma fatura, o sistema efetuará o re-login em segundo plano, salvando o novo token sem requerer intervenção do usuário.
3. **Logs**: As operações de auto-refresh são gravadas na tabela de logs `config_contadev_logs` com a ação `auto_refresh`.
