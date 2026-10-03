# Raciocínio: Exclusão de Prontuário Clínico com Validação de Vínculos

- **Data**: 03/10/2026
- **Contexto**: Exclusão de prontuários clínicos para remoção de testes ou duplicatas (`atendimento_form.php`).
- **Problema Analisado**:
  - Usuários que criam atendimentos para testes ou por duplicidade precisavam de uma forma segura de apagar o prontuário.
  - Um atendimento clínico pode possuir registros filhos diretos (Receitas médicas, Itens de Receita, Anexos/Arquivos via upload e Documentos Emitidos baseados em modelos).
  - A exclusão acidental de atendimentos reais com dados clínicos importantes acarretaria perda irrecuperável. Por isso, foi requerida validação prévia de vínculos e um fluxo com segunda confirmação caso existam receitas, anexos ou documentos vinculados, utilizando modais.

- **Decisões de Design e Implementação**:
  1. **Disponibilidade do Botão**:
     - O botão "Excluir Prontuário" é renderizado apenas quando `$is_edit` é verdadeiro (atendimento já persistido no banco).
     - Localizado no cabeçalho fixo da aba "Prontuário", harmonizando perfeitamente com o botão "Salvar Prontuário".
  2. **Verificação Dinâmica de Vínculos**:
     - Implementada a action AJAX `verificar_vinculos_prontuario` no `app.php` para contabilizar em tempo real as receitas, anexos e documentos emitidos atrelados ao atendimento.
  3. **Fluxo em 2 Etapas com Modais**:
     - **Modal 1 (Confirmação Inicial)**: Pergunta básica de confirmação sobre a exclusão do prontuário.
     - Ao prosseguir, o sistema avalia os vínculos:
       - Se existirem 0 vínculos: executa a exclusão imediata e redireciona.
       - Se existirem 1 ou mais vínculos: exibe o **Modal 2 (Segunda Confirmação com Alerta Crítico)** com contadores visuais de receitas, anexos e documentos, solicitando confirmação final explícita.
  4. **Exclusão Segura no Backend**:
     - Action `excluir_prontuario`:
       1. Deleta registros de `DocumentosEmitidos` com aquele `id_atendimento`.
       2. Deleta vínculos em `AtendimentoArquivos` e limpa os arquivos correspondentes em `Arquivos`.
       3. Deleta registros de `ItensReceita` e `Receitas`.
       4. Remove o registro em `Atendimentos`.
       5. Retorna URL de redirecionamento para `pet_detalhes.php?id={id_pet}#historico`.
