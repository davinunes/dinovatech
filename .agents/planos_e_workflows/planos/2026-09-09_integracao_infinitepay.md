# Plano de Implementação: Integração com InfinitePay (Checkout & PIX/Cartão)

## 1. Visão Geral
Este plano descreve a arquitetura, estrutura de banco de dados, fluxo de interface (UI/UX) e backend para integração do sistema Dinovatech com a **API de Checkout da InfinitePay**.

A integração permitirá a geração automatizada de links de checkout para faturas, possibilitando o pagamento facilitado via Cartão de Crédito e PIX com retorno via Webhook e Redirecionamento.

---

## 2. Configurações na Interface (`config_fiscal.php` - Aba Integrações)

### 2.1. Novo Card de Integração InfinitePay
- **Logo Oficial**: SVG fornecido (`https://cdn.prod.website-files.com/65c1399ac999a342139b5069/65c1399ac999a342139b5434_logo_brlc_preto.svg`).
- **Título**: InfinitePay (Checkout de Pagamento)
- **Toggle Principal (Ativo / Inativo)**:
  - Input `infinitepay_ativo` (checkbox/switch).
  - Quando desativado: O conteúdo interno do card colapsa (`hidden` via JavaScript/CSS).
- **Campo Handle (InfiniteTag)**:
  - Input `infinitepay_handle` (text).
  - **Filtro Automático JS/PHP**: Remove o caractere `$` do início e espaços em branco (ex: `$dinovatech` -> `dinovatech`).
- **Toggles de Personalização do Payload**:
  - `infinitepay_usar_webhook` (Switch): Habilita o envio de `webhook_url` para recebimento de notificações de pagamento em tempo real. Exibe a URL pública do webhook.
  - `infinitepay_usar_redirect` (Switch): Habilita o envio de `redirect_url` para redirecionar o cliente de volta à fatura pós-pagamento. Exibe a URL de sucesso.
  - `infinitepay_detalhar_itens` (Switch): Define se o payload enviará a lista detalhada item a item (`itens`) da fatura ou apenas 1 item resumido `"Pagamento da Fatura #XX"`.
  - `infinitepay_enviar_cliente` (Switch): Envia dados cadastrais do comprador (`customer`: nome, email, telefone).
  - `infinitepay_enviar_endereco` (Switch): Envia o objeto de endereço de entrega do cliente (`address`: CEP, rua, bairro, número, complemento).
- **Nota Informativa**:
  - Alerta indicando que o repasse de taxas aos clientes (se aplicável) é gerenciado diretamente no aplicativo mobile da InfinitePay.

---

## 3. Alterações no Banco de Dados (`ConfiguracoesEmissor` & `Pagamentos`)

### 3.1. Novas Colunas em `ConfiguracoesEmissor`
Adicionadas via `ALTER TABLE` condicional no backend `app.php` / helper:
- `infinitepay_ativo` (TINYINT DEFAULT 0)
- `infinitepay_handle` (VARCHAR(100) DEFAULT NULL)
- `infinitepay_usar_webhook` (TINYINT DEFAULT 1)
- `infinitepay_usar_redirect` (TINYINT DEFAULT 1)
- `infinitepay_detalhar_itens` (TINYINT DEFAULT 1)
- `infinitepay_enviar_cliente` (TINYINT DEFAULT 1)
- `infinitepay_enviar_endereco` (TINYINT DEFAULT 1)

---

## 4. Backend e Comunicação com a API InfinitePay

### 4.1. Helper / Serviço (`helpers/InfinitePayHelper.php`)
- **Método `gerarLinkCheckout($link, $id_fatura)`**:
  - Valida se `infinitepay_ativo == 1` e se `infinitepay_handle` está preenchido.
  - Converte valores para centavos (ex: R$ 10,00 -> 1000).
  - Monta a estrutura do JSON baseada nas preferências ativadas:
    - `handle`: string limpa.
    - `order_nsu`: `"fatura#$id_fatura"`.
    - `items`: array detalhado de itens ou resumo da fatura.
    - `redirect_url`: (se ativo) URL da fatura com parâmetro de retorno.
    - `webhook_url`: (se ativo) URL pública do webhook do sistema.
    - `customer`: (se ativo) dados do comprador.
    - `address`: (se ativo) dados do endereço.
  - Efetua chamada cURL `POST https://api.checkout.infinitepay.io/links` com `Content-Type: application/json`.
  - Trata o retorno e recupera a URL do link de checkout.

### 4.2. Webhook / Callback Handler (`webhook_infinitepay.php` ou `app.php?action=webhook_infinitepay`)
- Recebe os dados POST enviados pela InfinitePay ao aprovar um pagamento.
- Valida o `order_nsu` para identificar a fatura correspondente.
- Registra o pagamento na tabela `Pagamentos` (`status_pagamento = 'Confirmado'`, forma de pagamento `'InfinitePay'`, adicionando `transaction_nsu` e `receipt_url`).
- Responde com HTTP 200 OK em menos de 1 segundo.

---

## 5. Fluxo de Pagamento na Interface do Cliente (`fatura_view.php`)

1. **Exibição do Botão**:
   - Caso `infinitepay_ativo == 1`, `handle` esteja configurado e o saldo devedor seja `> 0`, exibe o card/botão **"Pagar com InfinitePay"** na fatura.
2. **Modal de Instruções**:
   - Ao clicar no botão, abre um modal explicativo (`modalInfinitePay`):
     - Texto: *"Você será redirecionado para o ambiente seguro da InfinitePay para concluir o pagamento via PIX ou Cartão de Crédito."*
     - Botão de Ação Inicial: **"Gerar Link de Checkout"**.
3. **Requisição AJAX e Substituição**:
   - Ao clicar em "Gerar Link de Checkout", executa requisição AJAX para `app.php?action=gerar_checkout_infinitepay`.
   - Enquanto gera: exibe indicador visual de carregamento (spinner).
   - Após a resposta da API: substitui o botão no modal (ou na tela) pelo link definitivo **"Pagar com InfinitePay"** com o atributo `href` apontando para o checkout da InfinitePay (`target="_blank"`).

---

## 6. Plano de Testes e Validação
- **Teste de UI**: Toggle de ativação do card colapsando/expandindo opções; máscara/filtro no campo handle removendo o `$`.
- **Teste de Salvamento**: Salvar as configurações em `config_fiscal.php` e recarregar a página para verificar a persistência.
- **Teste de Geração de Link**: Gerar checkout de uma fatura de teste e verificar a estrutura do payload JSON montado.
- **Teste de Redirecionamento / Webhook**: Simular recebimento do webhook e verificação do status da fatura.
