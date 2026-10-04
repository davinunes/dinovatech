# Raciocínio: Vínculo de Usuário a Colaborador e Auto-seleção em Formulários

**Data:** 04/10/2026  
**Contexto:** Preparação da arquitetura do sistema para acessos de colaboradores (especialmente no modo Vet).

---

## 1. Demanda e Objetivos
O usuário solicitou:
1. Preparar o sistema para ter acessos de colaboradores (principalmente modo vet).
2. Habilitar o modelo relacional para criar o vínculo entre um `Usuario` e um `Colaborador` (tabela `Veterinarios`).
3. Nos formulários em que se requisita informar o colaborador (especialmente Agenda e Prontuários/Atendimentos), se o usuário logado estiver vinculado a um colaborador, este deve vir pré-selecionado por padrão.

---

## 2. Análise do Modelo Atual de Dados e Sessão

### 2.1 Tabela `Usuarios` vs `Veterinarios`
- A tabela `Usuarios` gerencia as credenciais de login (`id_usuario`, `nome`, `email`, `senha`, `nivel_acesso`).
- A tabela `Veterinarios` gerencia o cadastro de colaboradores/profissionais (`id_vet`, `nome`, `funcao`, `crmv`, `uf_crmv`, `telefone`, `email`, etc.). Em modo Vet são veterinários, esteticistas, banhistas; em modo padrão, colaboradores gerais.
- A chave primária dos colaboradores é `id_vet`.
- Portanto, adicionamos uma coluna relacional `id_colaborador INT NULL DEFAULT NULL` na tabela `Usuarios`, com Foreign Key referenciando `Veterinarios(id_vet) ON DELETE SET NULL`.

### 2.2 Gestão de Sessão
- No login (`login_process.php` e auto-login por cookie em `login.php`), carregamos o `id_colaborador` para a sessão: `$_SESSION['id_colaborador']`.
- Em `AppHelper::getLoggedColaboradorId()`, fornecemos acesso direto e seguro ao ID do colaborador vinculado, com mecanismo resiliente de *lazy loading* (caso a sessão já estivesse ativa antes da execução do código).

---

## 3. Telas de Gestão do Vínculo
Para oferecer máxima usabilidade ao administrador, o vínculo é gerenciável em duas pontas:
1. **Gestão de Usuários (`config_fiscal.php` / `app.php`)**:
   - Tabela de usuários exibe a coluna "Colaborador Vinculado".
   - Modal de criação/edição de usuário ganha campo select para vincular a um colaborador cadastrado.
2. **Cadastro/Edição de Colaborador (`veterinario_form.php` / `veterinarios.php`)**:
   - Formulário do colaborador ganha campo select para vincular um usuário do sistema.
   - Lista de colaboradores exibe badge/ícone do usuário vinculado.

---

## 4. Auto-seleção nos Formulários
Identificamos todos os formulários operacionais onde o profissional/colaborador é selecionado:
1. **Prontuário / Novo Atendimento Clínico (`modules/Vet/atendimento_form.php`)**:
   - Se for um novo atendimento (`!$is_edit`), se não houver vet pré-definido por agendamento, `$id_veterinario` assume o `AppHelper::getLoggedColaboradorId()`. O `<select name="id_veterinario">` já o renderiza com `selected`.
2. **Novo Agendamento na Agenda Geral (`modules/Agenda/form_modal.php`)**:
   - Na função `openEventModal`, ao criar um agendamento novo, o campo `#eventVet` é inicializado com o `id_colaborador` do usuário logado (caso nenhum filtro de profissional esteja ativo no momento).
3. **Agendamento de Banho e Tosa (`modules/Vet/banho_agenda.php`)**:
   - Na função `novoAgendamentoBanho`, `#modal_id_vet` recebe o colaborador logado por padrão.
4. **Check-in / Entrada de Fila do Banho (`modules/Vet/banho_producao.php`)**:
   - Na função `openCheckinModal`, `#checkin_id_colaborador` recebe o colaborador logado por padrão.
5. **Nova Internação (`modules/Vet/internacoes.php` e `pet_detalhes.php`)**:
   - Ao abrir o modal de nova internação, `#ni_id_vet` e `#int_id_vet` assumem o colaborador logado por padrão.
