# Walkthrough: Integração com InfinitePay

## Alterações Efetuadas

1. **Helper de Backend (`dinovatech/helpers/InfinitePayHelper.php`)**:
   - Classe com métodos `cleanHandle()`, `formatPhoneE164()`, `getBaseUrl()` e `gerarLinkCheckout()`.
   - Monta o payload JSON completo para `POST https://api.checkout.infinitepay.io/links` respeitando as opções configuradas (webhook, redirect para a URL direta da fatura, itens detalhados vs resumidos, dados do cliente e endereço).

2. **Webhook Público (`dinovatech/webhook_infinitepay.php`)**:
   - Recebe dados de notificação da InfinitePay ao confirmar um pagamento.
   - Registra o pagamento em `Pagamentos` (`status_pagamento = 'Confirmado'`) e atualiza o status da fatura para `'Pago'` quando o saldo é quitado.
   - Responde com HTTP 200 OK em formato JSON.

3. **Gerenciamento no Backend (`dinovatech/app.php`)**:
   - Adicionada verificação e criação dinâmica das colunas em `ConfiguracoesEmissor`: `infinitepay_ativo`, `infinitepay_handle`, `infinitepay_usar_webhook`, `infinitepay_usar_redirect`, `infinitepay_detalhar_itens`, `infinitepay_enviar_cliente`, `infinitepay_enviar_endereco`.
   - Atualizada a query de salvamento de configurações (`save_config_fiscal`).
   - Adicionada a ação AJAX `gerar_checkout_infinitepay`.

4. **Painel de Configurações (`dinovatech/config_fiscal.php`)**:
   - Adicionado novo card para a InfinitePay na aba **Integrações**.
   - Incluído logo oficial SVG (`https://cdn.prod.website-files.com/..._logo_brlc_preto.svg`).
   - Toggle **Ativo / Inativo** que colapsa/expande a seção interna do card.
   - Input para **InfiniteTag / Handle** com filtro automático que remove o caractere `$` ao digitar ou perder foco.
   - Toggles independentes para customização da payload.
   - Nota explicativa sobre o repasse de taxas no app da InfinitePay.
   - Verificação cuidadosa de fechamento de tags HTML para garantir a integridade da tela.

5. **Interface de Fatura (`dinovatech/fatura_view.php`)**:
   - Exibição condicional do card **"Pagar com InfinitePay"** com o logo oficial quando a integração estiver ativa e houver saldo devedor.
   - Modal `modalInfinitePay` com informações de redirecionamento e detalhes da fatura.
   - Requisição AJAX assíncrona para gerar o link e substituição dinâmica pelo botão **"Pagar com InfinitePay"** (com link do checkout) e opção de copiar o link.

---

## Validação e Testes
- **UI / Configurações**: Verificado o correto colapso/expansão do card da InfinitePay e o filtro no campo handle (remoção de `$` ao digitar).
- **Fechamento de Tags**: Todas as tags HTML no card e na aba de integrações foram rigorosamente auditadas e fechadas.
- **Payload & Links**: O link de redirecionamento pós-pagamento foi configurado para direcionar diretamente para a fatura do cliente (`fatura_view.php?id=XX`).
