# Regras de Execução de Comandos (Git e PowerShell)

## 1. Comandos Git de Somente Leitura (Read-Only)
Está **autorizado por padrão** a execução de qualquer comando `git` de leitura sem efeito de escrita na árvore de trabalho ou no repositório.

Exemplos de comandos Git permitidos por padrão:
- `git status` / `git status -s`
- `git diff` / `git diff --cached` / `git diff HEAD`
- `git log` / `git log -n <N>` / `git log --oneline`
- `git show <commit/file>`
- `git branch` / `git branch -a`
- `git check-ignore` / `git ls-files`
- `git remote -v`

*Nota:* Comandos que alterem estado (`git commit`, `git push`, `git checkout`, `git reset`, `git rebase`, `git clean`) continuam devendo ser confirmados ou evitados sem autorização explícita.

## 2. Comandos PowerShell e Manipulação de Arquivos (Leitura e Escrita)
Está **autorizado por padrão** o uso de comandos e scripts PowerShell tanto para leitura, busca e diagnóstico quanto para escrita, criação e edição de arquivos do projeto no Windows:

### Leitura, Busca e Inspeção:
- `Get-Content` / `gc` (leitura de arquivos)
- `Select-String` / `sls` (busca de texto/padrões e regex)
- `Get-ChildItem` / `gci` / `dir` (listagem de diretórios)
- `Test-Path` (verificação de existência de arquivos/pastas)
- `Get-Command` / `Get-Process` (diagnósticos do ambiente)

### Escrita, Edição e Criação:
- `Set-Content` / `sc` e `Out-File` (escrita/substituição de arquivos)
- `Add-Content` / `ac` (anexar conteúdo)
- `New-Item` (criação de arquivos e pastas)
- `Copy-Item` / `Move-Item` (cópia e movimentação)

### Boas Práticas e Cuidados com Codificação (Encoding):
- No Windows PowerShell 5.1, o padrão do `Out-File` é UTF-16LE e do `Set-Content` pode ser ANSI (Windows-1252).
- **Sempre utilize `-Encoding utf8`** ao escrever arquivos via PowerShell para evitar quebras de acentuação e caracteres especiais em arquivos PHP/HTML/JS.
- Para inspeção de alterações após comandos de escrita, priorize `git diff` para validação rápida.
