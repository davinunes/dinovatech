# Walkthrough / Resumo de Entrega: Assinaturas/Contratos no Dashboard do Cliente & Visualização de Documentos

**Data:** 2026-08-12  
**Funcionalidades:**
1. Exibição dos cards de **Minhas Assinaturas & Contratos** na Dashboard e em uma aba dedicada no Portal do Cliente (`cliente/index.php`).
2. Listagem dos documentos e termos anexados a cada contrato (`DocumentosEmitidos`).
3. Botão **"Visualizar"** em cada documento do contrato que abre a visualização em PDF/HTML em nova aba via `documento_view.php`.
4. Permissões de sessão atualizadas em `documento_view.php` e `receita_print.php` para aceitar a sessão do cliente (`$_SESSION['cliente_id']`) com validação de titularidade.

## Alterações Realizadas

1. **[app.php](file:///e:/DEV/dinovatech/dinovatech/app.php)**
   - Atualizado o endpoint `get_cliente_dashboard_data` para buscar todas as recorrências/contratos ativas do cliente e os documentos vinculados (`DocumentosEmitidos WHERE id_recorrencia = $idRec`).

2. **[documento_view.php](file:///e:/DEV/dinovatech/dinovatech/modules/Vet/documento_view.php)**
   - Atualizada a checagem de sessão para permitir `cliente_id` com validação de posse do documento (`$doc['id_cliente'] == $_SESSION['cliente_id']`).

3. **[cliente/index.php](file:///e:/DEV/dinovatech/cliente/index.php)**
   - Adicionada a aba **"Minhas Assinaturas"** na navegação principal.
   - Adicionado o bloco **"Minhas Assinaturas & Contratos"** no Dashboard.
   - Renderização dos cards com nome do serviço/plano, valor recorrente, vigência, badge de status e lista de documentos vinculados com botão **"Visualizar"**.

## Instruções de Teste e Validação

1. Acesse a Área do Cliente em `cliente/index.php`.
2. Na aba **Visão Geral** ou na aba **Minhas Assinaturas**, verifique a exibição dos cards de contratos.
3. Clique em **"Visualizar"** em qualquer documento/termo vinculado a uma assinatura.
4. O documento será aberto em uma nova aba com o layout formal.
