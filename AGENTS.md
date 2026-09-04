# Regras do Workspace Dinovatech (AGENTS.md)

## 1. Comandos Git de Somente Leitura (Read-Only)
Está **autorizado por padrão** a execução de qualquer comando `git` de somente leitura sem impacto de alteração no repositório:
- `git status`, `git status -s`
- `git diff`, `git diff --cached`, `git diff HEAD`
- `git log`, `git log -n <N>`, `git log --oneline`
- `git show`, `git branch`, `git check-ignore`, `git ls-files`, `git remote -v`

## 2. Comandos PowerShell e Inspeção de Arquivos
Está **autorizado por padrão** o uso do PowerShell para inspeção de arquivos e buscas no Windows quando necessário:
- `Get-Content` (ou `gc`)
- `Select-String` (ou `sls`)
- `Get-ChildItem` (ou `gci`)
- `Test-Path`
