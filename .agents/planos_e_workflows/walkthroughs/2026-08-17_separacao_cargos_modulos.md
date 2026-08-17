# Walkthrough: Separação de Módulos e Cargos de Colaboradores

Implementamos a segregação de cargos/funções, habilitações de agenda e campos específicos (como CRMV) de acordo com o status da flag de Modo Veterinário (`APP_MODE_VET`).

---

## Modificações Realizadas

### 1. Formulário de Colaborador (`veterinario_form.php`)
- **Seleção Dinâmica de Funções / Cargos**:
  - **Modo Veterinário Ativo**: Lista funções clínicas e de pet shop (Veterinário(a), Banhista & Tosador(a) / Estética, Auxiliar Veterinário, Recepção / Administrativo, Geral / Multidisciplinar).
  - **Modo Padrão (Não Veterinário)**: Lista funções empresariais gerais (Recepção / Administrativo, Atendimento / Comercial, Técnico / Operacional, Gestão / Gerência, Geral / Colaborador).
- **Isolamento de Habilitações de Módulos**:
  - O bloco de habilitação de módulos (Banho & Tosa e Atendimento Clínico) agora é renderizado **apenas** quando `AppHelper::isVetMode()` for `true`.
  - O processamento de POST ignora ou zera essas flags quando fora do modo vet.
- **Isolamento do Bloco CRMV**:
  - Bloco de CRMV e UF CRMV e sua validação de obrigatoriedade só entram em ação quando `AppHelper::isVetMode()` for `true`.
- **Textos e Nomenclaturas Contextuais**:
  - O título da página e as dicas de assinatura digital foram ajustados para refletir o contexto correto da empresa.

---

### 2. Listagem de Colaboradores (`veterinarios.php`)
- **Badges de Cargos Expandidos**: Mapeamento de estilos visuais e ícones para os novos cargos (`atendente`, `tecnico`, `gerente`, etc.).
- **Tags de Módulos e CRMV Condicionais**: As tags de "Banho & Tosa", "Clínico" e número de CRMV só aparecem nos cards de colaboradores se o Modo Veterinário estiver ativo.
- **Textos de Interface e Busca**: Placeholder de busca e descrição do topo adaptados para o modo em uso.

---

### 3. Modal de Agendamentos (`form_modal.php`)
- O botão de atalho "Iniciar Atendimento" (que leva para prontuário clínico) agora é condicionalizado com `AppHelper::isVetMode()`.
