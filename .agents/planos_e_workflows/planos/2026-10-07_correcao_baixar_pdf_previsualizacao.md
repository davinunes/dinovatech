# Plano de Implementação: Correção do Botão Baixar PDF na Previsualização

## Objetivo
Garantir que o botão "Baixar PDF" funcione perfeitamente tanto ao visualizar documentos já salvos quanto ao previsualizar modelos de documentos não salvos em `dinovatech/modules/Vet/documento_print.php`.

## Arquivos Afetados
- `dinovatech/modules/Vet/documento_print.php`

## Alterações Propostas
1. Adicionar a função helper `renderHiddenInputsForPdf($data, $prefix = '')` em `documento_print.php`.
2. Substituir a tag `<a class="btn-pdf">` no grupo de botões superiores por um `<form method="POST" action="" target="_blank" style="display:inline;">` que:
   - Preserva todos os parâmetros recebidos (`$_GET` e `$_POST`), inclusive arrays como `overrides[...]`.
   - Adiciona `<input type="hidden" name="pdf" value="1">`.
   - Utiliza um `<button type="submit" class="btn-pdf" style="border:none; cursor:pointer;">` mantendo exatamente a mesma identidade visual e UX.

## Plano de Testes
1. Acessar o formulário de Atendimento Vet (`atendimento_form.php`).
2. Selecionar um Modelo de Documento e clicar em Visualizar/Previsualizar.
3. Na janela/aba de previsualização aberta, clicar em "Baixar PDF".
4. Verificar se o PDF é gerado e aberto via `PdfHelper::streamPdf` sem exibir a mensagem "Parametros invalidos.".
5. Repetir o teste via Contrato (`contrato_form.php`).
