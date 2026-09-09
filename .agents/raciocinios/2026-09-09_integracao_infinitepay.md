# Raciocínio de Arquitetura: Integração InfinitePay

## Contexto e Requisitos
O usuário solicitou a elaboração do plano de integração com a InfinitePay.
A documentação da API (`infinitepay/docs/api.md`) especifica o endpoint `POST https://api.checkout.infinitepay.io/links` para criação de checkout, com payload aceitando:
- `handle` (obrigatório, sem o `$`)
- `items` (array de itens com `quantity`, `price` em centavos, `description`)
- `order_nsu` (identificador da fatura local)
- `redirect_url` (URL para retorno após pagamento)
- `webhook_url` (URL de notificação de pagamento)
- `customer` (objeto opcional de comprador)
- `address` (objeto opcional de endereço)

## Decisões de Design
1. **Configuração Unificada em `ConfiguracoesEmissor`**:
   - A tabela `ConfiguracoesEmissor` centraliza todas as integrações da empresa (Inter, Oracle, Google Gmail/Calendar, ContaDev).
   - Manteremos a consistência adicionando as colunas `infinitepay_*` nessa mesma tabela.

2. **Sanitização do Handle**:
   - Tanto no frontend (JS `oninput` / `onblur`) quanto no backend (`trim` / `ltrim(..., '$')`), vamos garantir a remoção automática do prefixo `$` caso inserido pelo usuário.

3. **Flexibilidade do Payload**:
   - Toggles independentes na tela de configurações para controlar o envio de webhook, redirect, itens detalhados vs resumidos, dados do cliente e endereço.

4. **UX do Cliente na Fatura**:
   - Modal informativo pré-redirecionamento com geração assíncrona (AJAX) do link para evitar links expirados antes da intenção real de pagamento.
