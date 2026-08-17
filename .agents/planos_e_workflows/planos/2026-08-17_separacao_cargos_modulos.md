# Plano de Implementação: Separação de Módulos (Cargos e Funções no Modo Veterinário vs Padrão)

Garantir que as funções/cargos, habilitações de agenda/módulos (Banho & Tosa, Atendimento Clínico) e campos exclusivos (como CRMV e termos clínicos) só apareçam quando o **Modo Veterinário** (`AppHelper::isVetMode()`) estiver ativo, evitando misturar os módulos do sistema.

---

## Modificações Propostas

### 1. Formulário de Colaborador (`veterinario_form.php`)
- **Funções / Cargos Condicionais**:
  - Modo Vet (`isVetMode() == true`):
    - `veterinario`: 🩺 Veterinário(a)
    - `banhista_tosador`: 🛁 Banhista & Tosador(a) / Estética
    - `auxiliar`: 🐾 Auxiliar Veterinário
    - `administrativo`: 📋 Recepção / Administrativo
    - `geral`: 👥 Geral / Multidisciplinar
  - Modo Padrão (`isVetMode() == false`):
    - `administrativo`: 📋 Recepção / Administrativo
    - `atendente`: 💼 Atendimento / Comercial
    - `tecnico`: 🛠️ Técnico / Operacional
    - `gerente`: 👔 Gestão / Gerência
    - `geral`: 👥 Geral / Colaborador
- **Habilitação nos Módulos e Agendas (Banho & Tosa / Clínico)**:
  - Exibir bloco de checkboxes somente se `isVetMode()` for ativo.
- **CRMV / UF CRMV**:
  - Exibir bloco de CRMV apenas se `isVetMode()` for ativo.
- **Assinatura e Textos de Interface**:
  - Textos adaptados para não mencionar termos médicos/receitas quando fora do modo vet.

### 2. Listagem de Colaboradores (`veterinarios.php`)
- Suporte a badges para novos cargos (`atendente`, `tecnico`, `gerente`).
- Ocultar badges de Banho/Clínico e CRMV quando `!isVetMode()`.

### 3. Modal de Agenda (`form_modal.php`)
- Ocultar link de "Iniciar Atendimento" para prontuário clínico quando `!isVetMode()`.
