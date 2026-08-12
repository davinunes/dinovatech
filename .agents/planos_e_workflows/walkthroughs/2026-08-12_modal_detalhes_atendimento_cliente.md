# Walkthrough / Resumo de Entrega: Correção do Status de Contratos Vencidos no Portal do Cliente

**Data:** 2026-08-12  
**Funcionalidade:** Verificação dinâmica da data de encerramento da cobrança (`data_fim_cobranca`) para marcar contratos vencidos/expirados com o devido badge visual no Portal do Cliente (`cliente/index.php`).

## Alterações Realizadas

1. **[cliente/index.php](file:///e:/DEV/dinovatech/cliente/index.php)**
   - Atualizada a função `renderRecorrenciasList`: agora compara `data_fim_cobranca` com a data atual (`hojeStr`).
   - Se `data_fim_cobranca` for inferior à data de hoje, o contrato é rotulado como **"Vencido"** com badge vermelho (`bg-red-100 text-red-800`), e a data de término é destacada em vermelho.
   - Se o contrato for inativo/cancelado, exibe a etiqueta **"Cancelado"**.
   - Se estiver dentro da vigência ou sem data de término, permanece rotulado como **"Ativa"** (verde).

## Instruções de Teste e Validação

1. Acesse a Área do Cliente em `cliente/index.php`.
2. Na aba **Visão Geral** ou na seção **Minhas Assinaturas** (em **Meus Dados**), observe os cards de contrato.
3. Confirme que contratos cujo término da vigência já passou são exibidos com o status **"Vencido"** em vermelho em vez de "Ativa".
