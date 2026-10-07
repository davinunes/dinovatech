# Walkthrough / Resumo de Entrega: Correção do Botão Baixar PDF na Previsualização de Documentos

## Data
2026-10-07

## Resumo das Alterações
Foi corrigida a funcionalidade do botão **Baixar PDF** no arquivo `dinovatech/modules/Vet/documento_print.php` durante a previsualização de documentos.

### Arquivo Modificado
- [`dinovatech/modules/Vet/documento_print.php`](file:///d:/dev/github/dinovatech/dinovatech/modules/Vet/documento_print.php)

### O que foi alterado:
1. **Adicionada a função helper `renderHiddenInputsForPdf`**:
   - Mapeia recursivamente todas as chaves e valores presentes nas superglobais `$_GET` e `$_POST`.
   - Ignora parâmetros de controle interno como `pdf` e `PHPSESSID`.
   - Renderiza inputs ocultos (`<input type="hidden">`), suportando dados simples e arrays como `overrides[...]`.

2. **Substituição do botão `<a class="btn-pdf">` por um `<form>` POST**:
   - Substituiu o link estático por `<form method="POST" action="" target="_blank" style="display: inline;">`.
   - Adicionou `<input type="hidden" name="pdf" value="1">`.
   - Renderizou um botão `<button type="submit" class="btn-pdf">` que preserva exatamente a mesma aparência e comportamento para o usuário.

## Testes e Validação
1. **Visualização de documento já salvo** (`documento_view.php?id=X`):
   - Continua funcionando normalmente via GET.
2. **Previsualização de documento temporário/não salvo** (`documento_print.php` via POST):
   - O clique em "Baixar PDF" abre uma nova aba enviando via POST todos os dados previsualizados (`id_atendimento`, `id_recorrencia`, `id_modelo`, `titulo_custom`, `overrides`, etc.) acompanhados de `pdf=1`.
   - O documento é transmitido com sucesso via `PdfHelper::streamPdf`.
