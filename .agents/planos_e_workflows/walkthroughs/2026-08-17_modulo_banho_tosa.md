# Walkthrough - Módulo de Banho & Tosa: Colaboradores, Funções e Vagas Simultâneas

Data: 2026-08-17
Status: Concluído e Atualizado

## 1. Cadastro e Gestão de Colaboradores
- O menu lateral foi padronizado para **"Colaboradores"** ([dinovatech/components/sidebar.php](file:///e:/DEV/dinovatech/dinovatech/components/sidebar.php)).
- Criada migração `database/migrations/20260817_0004_add_funcao_to_colaboradores.sql` adicionando:
  - `funcao`: Veterinário(a), Banhista & Tosador(a), Recepção / Administrativo, Auxiliar Veterinário e Geral.
  - `realiza_banho` e `realiza_clinica`: flags para habilitar a atuação em cada módulo e agenda.
  - CRMV tornado opcional para funções que não exigem registro veterinário (ex: banhistas, recepção).
- Form de colaboradores ([dinovatech/modules/Vet/veterinario_form.php](file:///e:/DEV/dinovatech/dinovatech/modules/Vet/veterinario_form.php)) e listagem ([dinovatech/modules/Vet/veterinarios.php](file:///e:/DEV/dinovatech/dinovatech/modules/Vet/veterinarios.php)) atualizados com seleção de funções e badges informativos.

## 2. Configuração de Capacidade e Vagas Simultâneas de Banho & Tosa
- Criada migração `database/migrations/20260817_0005_add_banho_capacidade_to_config.sql` adicionando `banho_capacidade_simultanea` em `ConfiguracoesEmissor`.
- Na tela de configurações ([dinovatech/config_fiscal.php](file:///e:/DEV/dinovatech/dinovatech/config_fiscal.php)), a seção de **Estética & Banho** permite configurar o número de atendimentos simultâneos por horário (baseado no espaço físico, banheiras e mesas).
- O backend ([dinovatech/app.php](file:///e:/DEV/dinovatech/dinovatech/app.php)) utiliza esse parâmetro dinâmico para calcular os horários livres tanto na esteira quanto no autoatendimento do tutor.
