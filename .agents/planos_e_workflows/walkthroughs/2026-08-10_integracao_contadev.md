# Walkthrough - Integração ContaDev-Contabilidade

Foi implementada a integração completa com a plataforma **ContaDev-Contabilidade**, abrangendo desde o vínculo de credenciais e token até o envio e desduplicação de faturas (PDF + XML).

---

## 1. Alterações Realizadas

### 1. Banco de Dados e Migração
- **`database/migrations/20260810_0001_create_contadev_tables_and_config.sql`**:
  - Adicionou colunas de configuração em `ConfiguracoesEmissor` (`contadev_email`, `contadev_token`, `contadev_user_id`, `contadev_cnpj_id`, `contadev_company_name`, `contadev_user_name`, `contadev_ativo`).
  - Criou a tabela de sincronização relacional `nf_contadev_sync`.
  - Criou a tabela de histórico e auditoria de logs `config_contadev_logs`.

### 2. Backend & Helper HTTP
- **`dinovatech/helpers/ContaDevHelper.php`**:
  - `login()`: Executa autenticação via `POST /platform/login` e consulta CNPJ da empresa em `GET /platform/me`.
  - `getAccountStatus()`: Valida se a sessão e token continuam ativos.
  - `checkInvoiceAlreadyImported()`: Verifica se a fatura já foi importada usando 3 identificadores (texto `"numero {id_fatura}"` na descrição, nome do arquivo na S3 e combinação de CNPJ + Valor + Data).
  - `getOrCreateTomador()`: Consulta ou cadastra o cliente/tomador via `GET /platform/tomadores?cnpjId=...` e `POST /platform/tomadores`.
  - `getPresignedUrl()` & `uploadFileToS3()`: Obtém URLs pré-assinadas e realiza upload via HTTP PUT para a nuvem S3 da ContaDev.
  - `importInvoice()`: Executa a orquestração completa da importação e grava os registros de audit log em `config_contadev_logs` e sync em `nf_contadev_sync`.

### 3. Rotas AJAX
- **`dinovatech/app.php`**:
  - Incluiu `require_once __DIR__ . '/helpers/ContaDevHelper.php';`.
  - Adicionou os manipuladores AJAX: `contadev_login`, `contadev_status`, `contadev_disconnect`, `contadev_import_fatura` e `contadev_check_fatura`.

### 4. Interface de Usuário
- **`dinovatech/config_fiscal.php`**:
  - Adicionou o card **ContaDev-Contabilidade** na aba **Integrações (API)**.
  - Exibe formulário de e-mail/senha ou o status do vínculo ativo com botão de desconectar.
- **`dinovatech/fatura_view.php`**:
  - Adicionou o **Card Premium ContaDev Contabilidade** na barra lateral direita imediatamente acima do botão **Imprimir / PDF**.
  - Exibe o status da nota (Sincronizada / Não Importada) e botão interativo "Importar no ContaDev" com feedback via Toast.

---

## 2. Instruções para Validação

1. **Executar a Migração**:
   - No painel da Dinovatech (`config_fiscal.php` > aba "Atualizações") ou via console, execute o script de migração:
     `php scripts/migrate.php`
2. **Conectar a ContaDev**:
   - Acesse `config_fiscal.php` > **Integrações (API)**.
   - No card **ContaDev-Contabilidade**, informe o e-mail e senha do ContaDev e clique em **Conectar ContaDev**.
   - Confirme a exibição do status verde "ContaDev Conectado e Ativo" e as informações da empresa.
3. **Importar uma Fatura**:
   - Abra qualquer fatura com NFS-e concluída em `fatura_view.php?id={id_fatura}`.
   - Anexe um PDF na fatura caso ainda não exista um anexo em PDF.
   - Na barra lateral direita, localize o card premium **ContaDev Contabilidade**.
   - Clique em **Importar no ContaDev**.
   - Verifique o recebimento da mensagem Toast de sucesso e a atualização do badge para "Sincronizada".
