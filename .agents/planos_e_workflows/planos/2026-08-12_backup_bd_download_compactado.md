# Plano de Implementação - Backup BD com Download e Compactação

## Objetivo
Permitir que ao clicar em "Backup BD", além de salvar os arquivos SQL no disco do servidor, o sistema compacte os dados (em ZIP ou GZ, conforme suporte do ambiente/navegador) e inicie o download automático do arquivo no navegador do usuário.

## Componentes Afetados
- `dinovatech/app.php` (Backend - geração de backup no disco, criação do arquivo compactado e endpoint de download)
- `dinovatech/components/sidebar.php` (Frontend - função `fazerBackup` que dispara o AJAX e executa o download automático)

## Etapas da Implementação

### 1. Ajustes em `dinovatech/app.php`
- No `case 'fazer_backup'`:
  - Executar a geração dos arquivos `../estrutura.sql` e `../dados.sql` na raiz do sistema.
  - Verificar suporte a compactação:
    - Se `class_exists('ZipArchive')`, criar `../backup_bd.zip` contendo `estrutura.sql` e `dados.sql`.
    - Caso contrário, se `gzencode` existir e o cabeçalho `HTTP_ACCEPT_ENCODING` contiver `gzip`, gerar `../backup_bd.sql.gz`.
    - Caso contrário, usar o arquivo `.sql` não compactado.
  - Retornar na resposta JSON `success: true`, mensagem informativa e a URL de download `download_url: 'app.php?action=download_backup'`.
- Adicionar o `case 'download_backup'`:
  - Verificar permissão/sessão do usuário.
  - Detectar qual arquivo compactado/gerado existe (`../backup_bd.zip`, `../backup_bd.sql.gz` ou `../estrutura.sql` / `../dados.sql`).
  - Configurar cabeçalhos HTTP apropriados:
    - `Content-Type: application/zip` ou `application/x-gzip` ou `text/plain`.
    - `Content-Disposition: attachment; filename="backup_bd_YYYY-MM-DD_HHmmss.[ext]"`.
    - `Content-Length`.
  - Ler e enviar o conteúdo do arquivo com `readfile()`.

### 2. Ajustes em `dinovatech/components/sidebar.php`
- Atualizar a função `fazerBackup(e)`:
  - Exibir o feedback "Gerando..." no botão.
  - Receber a resposta do `$.post`.
  - Se for bem-sucedido, disparar `window.location.href = res.download_url` para o navegador realizar o download automaticamente.
  - Restaurar o estado do botão.

## Plano de Testes / Verificação
- Verificação do código via inspecção detalhada.
- Validação das rotas e headers HTTP configurados.
