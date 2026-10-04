# Plano de Implementação: Vínculo de Usuário a Colaborador e Seleção Padrão em Formulários

**Data:** 04/10/2026  
**Status:** Em Implementação

---

## 1. Objetivos
- Criar a migration de banco de dados adicionando `id_colaborador` na tabela `Usuarios` (Foreign Key para `Veterinarios.id_vet`).
- Disponibilizar na sessão (`$_SESSION['id_colaborador']`) e via método estático `AppHelper::getLoggedColaboradorId()`.
- Habilitar gestão do vínculo no painel administrativo:
  - `config_fiscal.php` (Aba Usuários) & `app.php` (ações `get_usuarios` e `save_usuario`).
  - `modules/Vet/veterinario_form.php` (Edição de colaborador com vínculo a usuário).
- Implementar auto-seleção do colaborador vinculado nos formulários do sistema:
  - Prontuário / Atendimento Clínico (`modules/Vet/atendimento_form.php`).
  - Agenda Geral (`modules/Agenda/form_modal.php`).
  - Agendamento de Banho e Tosa (`modules/Vet/banho_agenda.php`).
  - Check-in de Banho na Esteira (`modules/Vet/banho_producao.php`).
  - Modal de Nova Internação (`modules/Vet/internacoes.php` e `modules/Vet/pet_detalhes.php`).

---

## 2. Arquivos Impactados
1. `database/migrations/20261004_0001_add_id_colaborador_to_usuarios.sql` (Novo)
2. `dinovatech/login_process.php`
3. `dinovatech/login.php`
4. `dinovatech/helpers/AppHelper.php`
5. `dinovatech/app.php`
6. `dinovatech/config_fiscal.php`
7. `dinovatech/modules/Vet/veterinario_form.php`
8. `dinovatech/modules/Vet/veterinarios.php`
9. `dinovatech/modules/Vet/atendimento_form.php`
10. `dinovatech/modules/Agenda/form_modal.php`
11. `dinovatech/modules/Vet/banho_agenda.php`
12. `dinovatech/modules/Vet/banho_producao.php`
13. `dinovatech/modules/Vet/internacoes.php`
14. `dinovatech/modules/Vet/pet_detalhes.php`

---

## 3. Etapas de Execução
1. **Migration SQL**:
   - Criação da migration idempotente com verificação via `INFORMATION_SCHEMA`.
2. **Sessão e Helpers**:
   - Carregamento de `id_colaborador` no login e fallback inteligente no `AppHelper`.
3. **Backend e API (`app.php`)**:
   - Ajustar `get_usuarios` para trazer `id_colaborador` e nome do colaborador.
   - Ajustar `save_usuario` para salvar `id_colaborador`.
4. **Interface de Configuração de Usuários (`config_fiscal.php`)**:
   - Carregar lista de colaboradores no modal de usuário e exibir na tabela.
5. **Interface de Colaboradores (`veterinario_form.php` e `veterinarios.php`)**:
   - Permitir vincular usuário ao editar colaborador e exibir na listagem.
6. **Formulários Operacionais**:
   - Pré-selecionar o colaborador logado nos selects de atendimento, agenda, banho e internação.
