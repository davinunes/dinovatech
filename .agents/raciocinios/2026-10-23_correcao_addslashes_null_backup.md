# Diagnóstico e Raciocínio: Deprecated addslashes(null) em app.php

- **Data**: 2026-10-23
- **Contexto**: Erro reportado pelo PHP 8.1+: `Deprecated: addslashes(): Passing null to parameter #1 ($string) of type string is deprecated in /var/www/html/dinovatech/app.php on line 1478`

## Análise
1. A rotina em `dinovatech/app.php` no endpoint `case 'fazer_backup'` itera sobre todas as tabelas e linhas do banco via `mysqli_fetch_row`.
2. Para cada coluna `$row[$j]`, chamava diretamente `addslashes($row[$j])`.
3. Quando uma coluna do banco possui valor `NULL`, no PHP 8.1+ passar `null` para `addslashes` dispara aviso de depreciação repetidas vezes para cada registro e campo nulo no banco.
4. Além de poluir a saída/resposta HTTP, a lógica anterior atribuía `$row[$j] = addslashes($row[$j])`, convertendo `null` em string vazia `""`, o que fazia a verificação subsequente gravar `""` no script de dump SQL ao invés da palavra-chave SQL `NULL`.

## Solução Implementada
- Adicionada verificação estrita `if ($row[$j] === null) { $dadosContent .= 'NULL'; }`.
- No bloco `else`, o valor é convertido explicitamente para string `addslashes((string)$row[$j])`, com escape de quebras de linha (`\r\n`, `\r`, `\n`) e delimitado por aspas `"$val"`.
- Isso elimina completamente o aviso deprecation do PHP 8+ e melhora a integridade do dump SQL de backup para campos nulos.
