# Walkthrough: Correção da Ordenação das Faturas na Central do Cliente

## 1. Contexto e Problema Relatado
Na Central do Cliente (`cliente/index.php`), a listagem do bloco **"Últimas Faturas"** estava exibindo as faturas mais antigas do histórico do cliente em vez das mais recentes.

## 2. Diagnóstico da Causa Raiz
Foram identificados dois pontos de divergência na ordenação:
1. **Backend (`dinovatech/app.php` - `get_cliente_dashboard_data`)**:
   - A query SQL `$qFaturas` utilizava `ORDER BY F.data_vencimento ASC` com o intuito de capturar a primeira fatura aberta para `$proximaFaturaPendente`.
   - Isso fazia com que o array `$faturasList` retornado na API trouxesse sempre as faturas mais antigas no início da coleção.
2. **Frontend (`cliente/index.php` - `renderFaturasBreveList`)**:
   - A ordenação local da lista rápida ordenava por status, desempatava por valor decrescente e ordenava a data de vencimento em ordem ascendente (`dateA - dateB`), pegando as 5 primeiras com `.slice(0, 5)`.
   - Resultado: se houvesse faturas antigas com datas menores, elas apareciam prioritariamente no card de "Últimas Faturas".

## 3. Alterações Realizadas

### 3.1. Backend: [dinovatech/app.php](file:///d:/dev/github/dinovatech/dinovatech/app.php)
- **Consulta `$qFaturas`**:
  - Alterada para `ORDER BY F.data_vencimento DESC, F.data_emissao DESC, F.id_fatura DESC`.
  - Agora o conjunto de faturas vem ordenado cronologicamente das mais recentes para as mais antigas.
- **Detecção de `$proximaFaturaPendente`**:
  - Em vez de pegar cegamente a primeira fatura do array, itera sobre as faturas não quitadas (`PENDENTE`, `PROCESSANDO`, `PARCIAL`, `ABERTO`) e seleciona a de menor data de vencimento (a fatura pendente com vencimento mais urgente/próximo).

### 3.2. Frontend: [cliente/index.php](file:///d:/dev/github/dinovatech/cliente/index.php)
- **Função `renderFaturasBreveList`**:
  - Ajustada para ordenar as faturas em ordem estritamente cronológica decrescente (`data_vencimento DESC, data_emissao DESC, id_fatura DESC`).
  - As 5 faturas renderizadas no card "Últimas Faturas" agora são verdadeiramente as mais recentes emitidas para o cliente.
- **Função `renderFaturasTabs`**:
  - Na aba **"Em Aberto"**: ordenação por vencimento mais próximo/urgente primeiro (`dateA - dateB`), facilitando para o cliente ver o que vence primeiro.
  - Na aba **"Histórico (Pagas)"**: ordenação mantida das mais recentes para as mais antigas (`dateB - dateA`).

## 4. Verificação e Conformidade
- Todos os arquivos mantêm o padrão de segurança e sanitização.
- Nenhuma dependência externa ou quebra de contrato na API JSON retornada pelo dashboard do cliente.
