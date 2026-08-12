# Raciocínio - Backup BD com Download no Navegador e Compactação

## Data
2026-08-12

## Contexto e Objetivo
O usuário solicitou que, ao clicar na opção "Backup BD" (no menu lateral do sistema), a geração do backup do banco de dados continue salvando os arquivos no servidor, porém passe também a "cuspir" (fornecer para download) o arquivo diretamente no navegador do usuário, utilizando compactação se o ambiente/navegador suportar.

## Análise da Implementação Atual
1. No arquivo `dinovatech/components/sidebar.php`, a função `fazerBackup(e)` realiza uma chamada AJAX `$.post('app.php', { action: 'fazer_backup' })`.
2. No backend `dinovatech/app.php`, o `case 'fazer_backup'` gera dois arquivos SQL na raiz do sistema: `../estrutura.sql` e `../dados.sql`.
3. Ao finalizar, o backend responde com um JSON contendo a mensagem de sucesso ou erro, e o JavaScript exibe um `alert()`.

## Solução Proposta
1. **Manutenção do salvamento no servidor**:
   - Manter a criação e atualização dos arquivos `../estrutura.sql` e `../dados.sql` na raiz do servidor.

2. **Compactação Inteligente**:
   - **Prioridade 1 (ZIP)**: Se a extensão PHP `ZipArchive` estiver disponível (`class_exists('ZipArchive')`), empacotar ambos os arquivos (`estrutura.sql` e `dados.sql`) em um arquivo ZIP (`../backup_bd.zip`), nomeado para download como `backup_bd_YYYY-MM-DD_HHmmss.zip`.
   - **Prioridade 2 (GZIP)**: Se `ZipArchive` não estiver presente, mas a extensão `zlib` / `gzencode` estiver disponível e o navegador indicar suporte a `gzip` no cabeçalho `Accept-Encoding`, concatenar a estrutura e os dados e compactar via `gzencode()`, nomeando o arquivo como `backup_bd_YYYY-MM-DD_HHmmss.sql.gz`.
   - **Fallback (SQL simples)**: Caso nenhuma compactação seja suportada, disponibilizar a concatenação simples dos arquivos SQL (`backup_bd_YYYY-MM-DD_HHmmss.sql`).

3. **Fluxo de Download no Navegador**:
   - Adicionar uma ação `download_backup` (ou resposta na própria chamada AJAX com URL de download) em `app.php`.
   - Na chamada AJAX original de `sidebar.php`, assim que o backup for gerado no servidor, o frontend recebe o resultado JSON com `download_url: 'app.php?action=download_backup'` e dispara `window.location.href = download_url`.
   - O browser executa o download sem redirecionar ou recarregar a página atual.
