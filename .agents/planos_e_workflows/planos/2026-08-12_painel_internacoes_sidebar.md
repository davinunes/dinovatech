# Plano de Implementação - Painel da Sala de Internação (`internacoes.php`) & Menu Lateral

Criar o menu **Internações** na sidebar (abaixo do menu **Pets**) e desenvolver uma página prática, responsiva e otimizada para tablets e dispositivos móveis (`dinovatech/modules/Vet/internacoes.php`), focada no trabalho diário do veterinário na sala de internação.

---

## User Review Required

> [!IMPORTANT]
> **Fluxo e Usabilidade Otimizada para Tablets/Smartphones**:
> 1. **Sidebar**: Novo item **Internações** com ícone `local_hospital` logo abaixo de **Pets** (exibido apenas no modo veterinário).
> 2. **Painel de Internações (`internacoes.php`)**:
>    - Exibe cards responsivos com as internações ativas ("Em Internação"), com contador em tempo real e filtros de status (`Em Internação`, `Alta Médica`, `Todos`).
>    - Campo de busca rápida por nome do pet, tutor ou veterinário.
>    - Botão **+ Nova Internação** com modal para selecionar o pet cadastrado e criar internação diretamente.
>    - Cada card prioriza o botão de **Ficha Eletrônica** (abertura imediata da ficha digital para checagens de horários de medicação e fluidoterapia) e traz o botão **Gerar PDF / Imprimir** à parte em aba separada.

---

## Open Questions

*(Nenhuma dúvida no momento).*

---

## Proposed Changes

### Sidebar Component

#### [MODIFY] [sidebar.php](file:///e:/DEV/dinovatech/dinovatech/components/sidebar.php)
- Adicionar o item **Internações** abaixo do link de **Pets** na seção de cadastros do modo veterinário (`AppHelper::isVetMode()`).

---

### Módulo Vet / Painel da Sala de Internação

#### [NEW] [internacoes.php](file:///e:/DEV/dinovatech/dinovatech/modules/Vet/internacoes.php)
- Página principal do painel de internações:
  - Header com contador de pacientes internados e ação de **Nova Internação**.
  - Filtros rápidos por status e campo de busca interativo.
  - Grid de cards responsivos (1 col no celular, 2-3 cols no tablet/desktop):
    - Nome do Pet destacado, espécie, raça, idade e peso.
    - Nome do Tutor + link direto para WhatsApp.
    - Veterinário Responsável + Data/Hora de entrada.
    - Bloco com a Suspeita Clínica / Diagnóstico.
    - Ação principal de alta visibilidade: **Ficha Eletrônica** (abre modal para checagens e lançamento de medicações no tablet).
    - Ação secundária: **Gerar PDF / Imprimir** (`internacao_print.php?id=X`).
    - Atalho para o **Prontuário** do pet e opção de **Dar Alta**.
  - Inclusão das modais de **Nova Internação**, **Edição** e **Ficha Digital** com a lógica AJAX já validada.

---

## Verification Plan

### Manual Verification
1. **Verificar Sidebar**:
   - Abrir o sistema no navegador/tablet e confirmar que o item **Internações** aparece logo abaixo de **Pets**.
2. **Acessar o Painel de Internações**:
   - Navegar para `dinovatech/modules/Vet/internacoes.php`.
   - Verificar a exibição dos cards de internação em andamento.
3. **Testar Responsividade em Tablet/Celular**:
   - Simular visualização em tablet e celular no navegador.
   - Confirmar que os botões de **Ficha Eletrônica** e **Imprimir PDF** são fáceis de tocar.
4. **Testar Lançamento Rápido na Ficha Eletrônica**:
   - Clicar em "Ficha Eletrônica" em um card de paciente internado.
   - Realizar checagens de horários de medicação e salvar a fluidoterapia.
   - Clicar em "Gerar PDF" e validar se a folha de impressão A4 contém as alterações atualizadas.
