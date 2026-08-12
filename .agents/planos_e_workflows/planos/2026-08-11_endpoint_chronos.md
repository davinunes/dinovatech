# Plano de Implementação: Endpoint chronos.php (Tarefas Agendadas)

**Data**: 2026-08-11
**Objetivo**: Criar o endpoint `chronos.php` para centralizar execuções de tarefas agendadas (cron jobs chamados por n8n ou cURL externo).
**Primeira Tarefa**: Renovação/manutenção de sessão do token ContaDev fazendo GET no endpoint `/platform/me`.

## 1. Arquitetura do Endpoint chronos.php

- **Segurança**: Validação de chave secreta (`CHRONOS_KEY` no `.env` ou via fallback) enviada via parâmetro `key` ou cabeçalho HTTP `X-Chronos-Key`.
- **Extensibilidade**: Mapeamento de tarefas (`$registeredTasks`) permitindo registrar facilmente novas tarefas agendadas futuramente.
- **Formato de Saída**: Resposta em JSON padronizado informando sucesso, tempo de execução, timestamp e detalhamento por tarefa executada.

## 2. Renovação do Token ContaDev

- Método `ContaDevHelper::renewToken($link)`:
  1. Carrega o token criptografado da tabela `ConfiguracoesEmissor`.
  2. Descriptografa e envia requisição `GET https://api-app.conta-dev.com/platform/me`.
  3. Registra a execução no log de auditoria `config_contadev_logs`.
  4. Retorna se a sessão foi mantida ativa com sucesso ou se a renovação falhou.

## 3. Arquivos Envolvidos

- `dinovatech/helpers/ContaDevHelper.php`: Adição do método `renewToken($link)`.
- `dinovatech/chronos.php`: Endpoint centralizador de tarefas.
