# Plano de Implementação: Modal de Detalhes do Atendimento Clínico (Área do Cliente)

**Data:** 2026-08-12  
**Funcionalidade:** Exibição resumida dos atendimentos no Dashboard do Cliente com modal interativo para visualização completa de prontuário, receitas emitidas e anexos.

## Objetivos
1. Criar o endpoint `get_atendimento_detalhes_cliente` em `dinovatech/app.php`.
2. Adicionar o modal interativo em `cliente/index.php`.
3. Permitir que o cliente clique no card do atendimento para abrir o modal com histórico, receitas e arquivos anexados.

## Componentes Envolvidos

### 1. `dinovatech/app.php`
- Adicionar `case 'get_atendimento_detalhes_cliente':`
- Verificar se `id_atendimento` pertence a um Pet do cliente logado.
- Retornar:
  - Dados do Atendimento (queixa, anamnese, exame_fisico, diagnostico, conduta_tratamento).
  - Dados do Pet e Veterinário.
  - Arquivos anexados (`AtendimentoArquivos`).
  - Receitas e seus itens (`Receitas` e `ItensReceita`).

### 2. `cliente/index.php`
- Adicionar Modal `#modalDetalhesAtendimento` no HTML.
- Adicionar evento de clique nos cards de atendimento para invocar a função JS `abrirModalAtendimento(id_atendimento)`.
- Renderizar dinamicamente o histórico clínico, receitas (com link de impressão) e arquivos anexados.

## Plano de Teste
1. Acessar a Área do Cliente em Modo Veterinário.
2. Clicar em um card de Atendimento Clínico no Dashboard.
3. Verificar a abertura do Modal exibindo prontuário, receitas e anexos.
4. Testar o botão de impressão de receita e abertura de anexos.
