# Plano de Implementação: Atendimentos Recentes no Dashboard (Modo Clínico)

**Data:** 2026-08-12  
**Funcionalidade:** Adição da seção "Atendimentos Recentes" no Dashboard quando o Modo Clínico estiver ativo, com limite de 10 itens por página e paginação.

## Objetivos
1. Criar endpoint em `dinovatech/app.php` (`get_atendimentos_recentes`) para listar atendimentos paginados.
2. Renderizar condicionalmente a seção no `dinovatech/dashboard.php` se `AppHelper::isVetMode()` for verdadeiro.
3. Fornecer visualização em tabela (desktop) e cards (mobile) com botões funcionais de paginação AJAX.

## Componentes Envolvidos

### 1. `dinovatech/app.php`
- Adicionar `case 'get_atendimentos_recentes':`
- Receber `page` e `limit`.
- Fazer a query:
  ```sql
  SELECT a.id_atendimento, a.id_pet, a.data_atendimento, a.queixa_principal, a.diagnostico,
         p.nome as pet_nome, p.especie as pet_especie,
         c.nome as tutor_nome,
         v.nome as vet_nome
  FROM Atendimentos a
  LEFT JOIN Pets p ON a.id_pet = p.id_pet
  LEFT JOIN Clientes c ON p.id_cliente = c.id_cliente
  LEFT JOIN Veterinarios v ON a.id_vet = v.id_vet
  ORDER BY a.data_atendimento DESC, a.id_atendimento DESC
  LIMIT $offset, $limit
  ```
- Retornar o JSON formatado com os dados e meta de paginação (`page`, `total_pages`, `total`).

### 2. `dinovatech/dashboard.php`
- Inserir bloco HTML para "Atendimentos Recentes" envolto por `<?php if (AppHelper::isVetMode()): ?>`.
- Adicionar tabela desktop (`#tabelaAtendimentosRecentes`) e container mobile (`#cardsAtendimentosRecentes`).
- Adicionar barra de paginação (`#paginacaoAtendimentosRecentes`).
- Adicionar código JS `loadAtendimentosRecentes(page)` para realizar a requisição AJAX e atualizar o DOM.

## Plano de Teste / Verificação
- Verificar que quando `APP_MODE_VET=true`, a seção é exibida no Dashboard.
- Verificar que quando `APP_MODE_VET=false`, a seção não é exibida.
- Testar a navegação de páginas (Próximo, Anterior e números das páginas).
- Testar os links de ação redirecionando corretamente para a tela de atendimento/prontuário (`modules/Vet/atendimento_form.php?id=...&pet_id=...`).
