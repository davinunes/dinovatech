# Walkthrough - Backup BD com Download e Compactação

## Data
2026-08-12

## O que foi alterado
1. **[app.php](file:///e:/DEV/dinovatech/dinovatech/app.php)**:
   - Adicionada verificação de ação `download_backup` antes de forçar o cabeçalho JSON, configurando os cabeçalhos de download (`Content-Type`, `Content-Disposition: attachment; filename="backup_bd_YYYY-MM-DD_HHmmss.[ext]"`, `Content-Length`) e transmitindo o arquivo via `readfile()`.
   - Atualizada a ação `fazer_backup` para:
     - Manter a gravação dos arquivos `../estrutura.sql` e `../dados.sql` na raiz do servidor.
     - Detectar suporte a `ZipArchive` e empacotar a estrutura e os dados em um arquivo `.zip` (`../backup_bd.zip`).
     - Caso `ZipArchive` não esteja disponível, verificar suporte a `gzencode` e cabeçalho `gzip` do navegador para gerar um arquivo `.sql.gz` (`../backup_bd.sql.gz`).
     - Caso contrário, disponibilizar um arquivo `.sql` concatenado (`../backup_bd.sql`).
     - Retornar na resposta JSON o atributo `download_url: 'app.php?action=download_backup'`.

2. **[sidebar.php](file:///e:/DEV/dinovatech/dinovatech/components/sidebar.php)**:
   - Atualizada a função JavaScript `fazerBackup(e)` para que, assim que o backup for gerado no servidor, redirecione para `res.download_url` via `window.location.href`, iniciando o download do arquivo compactado diretamente no navegador do usuário.

## Como Validar no Servidor
1. Acesse o sistema e clique no botão **Backup BD** no menu lateral.
2. Confirme a geração do backup na caixa de diálogo.
3. Observe que o botão exibirá "Gerando..." e em seguida o navegador iniciará o download do arquivo (`backup_bd_YYYY-MM-DD_HHmmss.zip` ou `.sql.gz` / `.sql`).
4. Verifique no servidor que os arquivos `estrutura.sql` e `dados.sql` (e o arquivo compactado correspondente) continuam sendo atualizados na raiz do sistema.
