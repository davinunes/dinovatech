# Regras de Execução de Comandos (Somente Leitura e Inspeção)

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

## 2. Comandos PowerShell e Inspeção de Arquivos
Está **autorizado por padrão** o uso de scripts/comandos PowerShell para leitura, busca ou diagnósticos de arquivos no sistema operacional Windows (útil em outros projetos onde a leitura direta de arquivos via ferramentas nativas encontre dificuldades):

Exemplos de comandos PowerShell permitidos por padrão:
- `Get-Content` / `gc` (leitura de arquivos)
- `Select-String` / `sls` (busca de texto/padrões)
- `Get-ChildItem` / `gci` / `dir` (listagem de diretórios)
- `Test-Path` (verificação de existência de caminhos)
- `Get-Command` / `Get-Process` (diagnóstico de sistema/ferramentas)
