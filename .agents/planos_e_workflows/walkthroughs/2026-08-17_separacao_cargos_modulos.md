# Walkthrough: Revisão e Isolamento de Módulos (Configurações, Serviços e Colaboradores)

Implementamos a segregação completa de campos, opções visuais e módulos clínicos/pet shop entre o **Modo Veterinário** e o **Modo Padrão (Empresarial)**.

---

## 1. Tela de Configurações (`config_fiscal.php`)
- **Módulo de Estética & Banho (DinoVet)**:
  - O bloco de configurações de capacidade simultânea e check-in fotográfico de banho e tosa foi condicionalizado com `AppHelper::isVetMode()`. Não aparece mais no modo empresarial padrão.
- **Seletor de Tema da Página Inicial**:
  - A opção *"Clínica Veterinária (Tema DinoVet)"* só é listada no dropdown se `AppHelper::isVetMode()` for `true`.
- **Títulos e Cabeçalhos**:
  - Ajustado o título para dinâmico com o nome da empresa e o cabeçalho para *"Configurações"* geral.

---

## 2. Cadastro e Formulário de Serviços (`servico_form.php`)
- **Módulos de Disponibilidade (Clínica e Banho & Tosa)**:
  - O bloco de checkboxes "Disponibilidade do Serviço" (Módulo Clínica / Consultas e Módulo Banho e Tosa) agora é exibido **apenas** quando `AppHelper::isVetMode()` for `true`.
- **Ícones Padrão e Catálogo de Ícones**:
  - O ícone padrão do serviço agora é `build` (chave/ferramenta) no modo empresarial e `pets` (pata) no modo veterinário.
  - O modal seletor de ícones Material Icons carrega paletas distintas:
    - **Modo Veterinário**: Ícones de pets, banho, clínica, vacinas, spa, etc.
    - **Modo Padrão**: Ícones de serviços, ferramentas, tecnologia, finanças, consultoria, suporte e logística.
- **Título da Página**:
  - Título dinâmico baseado no nome fantasia da empresa (`AppHelper::getCompanyName()`).

---

## 3. Listagem de Serviços (`servicos.php`)
- **Coluna "Módulos" Condicional**:
  - A coluna de "Módulos" (com tags de Clínica e Banho & Tosa) na tabela desktop e nos cards mobile só é renderizada se `AppHelper::isVetMode()` for `true`.
  - Em modo padrão, a tabela se ajusta de forma limpa com 4 colunas (Serviço, Duração, Valor Sugerido, Ações).
- **Ícones de Linha**: Fallback automático para `build` quando fora do modo veterinário.

---

## 4. Backend e Processamento (`app.php`)
- Nas ações `criar_servico` e `editar_servico`:
  - `disponivel_clinica` e `disponivel_banho` são desativados (0) se `!AppHelper::isVetMode()`.
  - `icone_servico` recebe fallback de acordo com o modo ativo.

---

## 5. Formulário e Listagem de Colaboradores (`veterinario_form.php` e `veterinarios.php`)
- Funções e cargos segmentados (Empresariais vs Clínicos/Pet).
- Campos de CRMV e habilitações de agenda/esteira ocultos quando `APP_MODE_VET=false`.
