# Raciocínio: Correção do Redirecionamento de Sessão Expirada / Remember Me para Dashboard

- **Data**: 18/08/2026
- **Contexto**: Ao ficar inativo por algum tempo e clicar em "Painel Admin", o sistema abria a tela legada `/dinovatech/index.php` (com layout antigo e modais jQuery) em vez da `dashboard.php`.

## Investigação e Causa Raiz
1. Quando a sessão PHP expira por inatividade, `isset($_SESSION['usuario_id'])` torna-se `false` na Landing Page raiz (`/index.php` e componentes `landing_default.php` / `landing_vet.php`).
2. O botão de acesso na Landing Page então aponta para `./dinovatech/login.php`.
3. Ao carregar `dinovatech/login.php`, o script verifica se existe o cookie seguro de auto-login (`dinovatech_remember`).
4. Se o cookie existe e é válido, a sessão do usuário é restaurada no backend.
5. Logo em seguida, `login.php` executava:
   ```php
   if (isset($_SESSION['usuario_id'])) {
       header("Location: index.php");
       exit();
   }
   ```
6. O destino relativo `index.php` dentro do diretório `dinovatech/` apontava diretamente para `dinovatech/index.php` (o painel legado de faturas em jQuery), em vez do novo `dinovatech/dashboard.php`.
7. Adicionalmente, `login_process.php` redirecionava para `clientes.php` em vez de `dashboard.php`, e a landing page raiz não checava o cookie `dinovatech_remember`.

## Decisões Técnicas
1. Alterar `login.php` para redirecionar explicitamente para `dashboard.php`.
2. Alterar `login_process.php` para direcionar logins bem-sucedidos para `dashboard.php`.
3. Adicionar o método `AppHelper::checkRememberLogin()` no `AppHelper.php` e acioná-lo no `index.php` raiz para restaurar a sessão já na visualização da Landing Page caso o cookie esteja presente.
