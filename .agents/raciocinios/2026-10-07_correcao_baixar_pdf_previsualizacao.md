# Raciocínio: Correção do Botão "Baixar PDF" na Previsualização de Documentos

## Data
2026-10-07

## Problema Relatado
No arquivo `dinovatech/modules/Vet/documento_print.php`, o botão **Baixar PDF** não funciona quando o documento é apenas previsualizado (antes ou no ato da geração sem abrir um documento já salvo). Ele só funciona quando visualizamos um documento já salvo no banco.

## Investigação e Causa Raiz
1. Ao visualizar um documento **já salvo** (`documento_view.php?id=X`), o acesso ocorre via requisição `GET` contendo a query string `?id=X`. O botão "Baixar PDF" era um elemento `<a>` com `href` que reaproveitava `$_SERVER['REQUEST_URI']` e anexava `&pdf=1`.
2. Ao **previsualizar** um documento não salvo a partir da tela de Atendimento (`atendimento_form.php`) ou de Contrato (`contrato_form.php`), a requisição para `documento_print.php` é realizada via **`POST`** utilizando um formulário HTML criado dinamicamente via JavaScript.
3. Como os parâmetros (`id_atendimento`, `id_recorrencia`, `id_modelo`, `titulo_custom`, `overrides`, etc.) foram enviados no corpo do `POST`, a `$_SERVER['REQUEST_URI']` da página de previsualização era apenas `/dinovatech/modules/Vet/documento_print.php` (sem query parameters).
4. Ao clicar no link `<a href="documento_print.php?pdf=1">`, o navegador disparava uma nova requisição `GET` para `documento_print.php?pdf=1`.
5. Nessa nova requisição `GET`, a superglobal `$_POST` vinha vazia. O script `documento_print.php` efetuava a validação `if ((!$id_atendimento && !$id_recorrencia) || !$id_modelo) { die("Parametros invalidos."); }` e interrompia a execução com "Parametros invalidos.", além de perder quaisquer dados de `overrides` ou `titulo_custom`.

## Solução Adotada
1. No arquivo `dinovatech/modules/Vet/documento_print.php`, substituir o elemento `<a>` do botão "Baixar PDF" por um `<form>` com `method="POST" action="" target="_blank" style="display:inline;">`.
2. Criar uma função auxiliar PHP `renderHiddenInputsForPdf` que lê recursivamente todos os parâmetros de entrada trazidos por `$_GET` e `$_POST` (reunidos via `array_merge($_GET, $_POST)`) e os gera como campos `<input type="hidden">`.
3. Injetar `<input type="hidden" name="pdf" value="1">` dentro desse formulário.
4. Dessa forma, quando o usuário clica em "Baixar PDF" na tela de previsualização (ou visualização via GET/POST), o formulário submete via POST para uma nova aba (`target="_blank"`) mantendo 100% dos parâmetros originais, permitindo que o `PdfHelper::streamPdf` processe o PDF perfeitamente.
