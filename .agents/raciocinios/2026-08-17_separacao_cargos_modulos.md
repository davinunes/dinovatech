# Raciocínio: Isolamento de Funções/Cargos e Módulos Veterinários

## Contexto
Ao acessar `/dinovatech/modules/Vet/veterinario_form.php`, os campos e opções de cargos (ex: Veterinário, Banhista & Tosador, CRMV, Habilitação em Banho e Atendimento Clínico) estavam sendo exibidos mesmo quando o sistema estava operando com o modo veterinário desativado (`APP_MODE_VET=false`).

## Análise e Causa Raiz
- O formulário e a listagem de colaboradores foram unificados para atender tanto o ecossistema DinoVet quanto instalações genéricas de prestação de serviços.
- No entanto, a interface de seleção de funções e as caixas de habilitação de módulos ainda continham valores estáticos voltados exclusivamente ao nicho veterinário e pet shop.
- Faltava condicionalizar:
  1. A lista de opções do `<select name="funcao">` com base em `AppHelper::isVetMode()`.
  2. O bloco de checkboxes de habilitação de módulos (Banho & Tosa e Atendimento Clínico).
  3. O bloco de input de CRMV e sua validação de obrigatoriedade no backend.
  4. O mapeamento visual de badges de cargos na listagem de colaboradores.
  5. Textos contextuais de assinatura digital e títulos da aplicação.

## Decisões Técnicas
- Criada lista de cargos de negócio geral quando `!AppHelper::isVetMode()`:
  - `administrativo`: Recepção / Administrativo
  - `atendente`: Atendimento / Comercial
  - `tecnico`: Técnico / Operacional
  - `gerente`: Gestão / Gerência
  - `geral`: Geral / Colaborador
- No modo veterinário (`AppHelper::isVetMode()`), mantida a listagem com Veterinário, Banhista/Tosador, Auxiliar Vet, Administrativo e Geral.
- Ocultados completamente os blocos de CRMV e de Habilitações de Módulos Clínicos/Banho quando `!isVetMode()`.
- No `POST`, garantido que `realiza_banho` e `realiza_clinica` sejam salvos como 0/inativos quando fora do modo vet.
