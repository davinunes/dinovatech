# Planejamento: Sistema de Clínica Veterinária (DinoVet)

## 1. Visão Geral
Transformar o sistema "Dinovatech" em **DinoVet**, um sistema de gestão para clínicas veterinárias.
A base administrativa (Clientes, Faturas, Serviços) é mantida.
Entidades adicionadas: Pets, Veterinários, Vacinas, Atendimentos, Documentos.

## 2. Roteiro de Implementação Detalhado

### FASE 0: Fundação & Rebranding
- [ ] **Migração de Banco de Dados**: Executar `migrate0.php` (Concluído).
- [ ] **UI Rebranding**: Alterar logotipos e títulos para "DinoVet".
- [ ] **Ajuste de Menu**: Agrupar itens de menu por contexto (Adm vs Clínico).

### FASE 1: Gestão de Pets (Os Pacientes)
Esta fase conecta os Clientes (Tutors) aos seus animais.

1.  **Tela: Listagem de Pets (`pets.php`)**
    -   *Objetivo*: Listar todos os pets cadastrados no sistema.
    -   *Colunas*: Nome, Espécie/Raça, Tutor (Link), Idade, Peso.
    -   *Filtros*: Busca por nome, espécie.

2.  **Tela: Detalhes do Pet (`pet_detalhes.php`)**
    -   *Objetivo*: O "Prontuário" central do animal.
    -   *Seções*:
        -   **Card Principal**: Foto (placeholder), Dados Básicos, Tutor.
        -   **Histórico Clínico**: Timeline de atendimentos (Placeholder).
        -   **Vacinas**: Cartão de vacinas (Placeholder).

3.  **Tela: Formulário de Pet (`pet_form.php`)**
    -   *Objetivo*: Cadastro e Edição.
    -   *Campos*: Nome, Tutor (Select2/Autocomplete), Espécie (Select), Raça, Sexo, Data Nasc, Cor/Pelagem, Chip, Obs.

4.  **Integração Cliente (`cliente_detalhes.php`)**
    -   *Ação*: Adicionar aba/bloco "Meus Pets" na tela de detalhes do tutor.

### FASE 2: Módulo de Vacinação
Controle de imunização e alertas.

1.  **Tela: Catálogo de Vacinas (`vacinas.php`)**
    -   *Objetivo*: CRUD de tipos de vacinas (ex: V10, Antirrábica).
    -   *Campos*: Nome, Descrição, Recorrência Padrão (dias).

2.  **Funcionalidade: Registro de Aplicação**
    -   *Local*: Dentro de `pet_detalhes.php` (Aba Vacinas).
    -   *Modal/Form*: Selecionar Vacina, Data Aplicação, Data Revacina (Auto-calculada), Lote, Vet Responsável.

3.  **Visualização: Carteira de Vacinação**
    -   *UI*: Tabela colorida (Verde=Em dia, Amarelo=Próxima, Vermelho=Vencida).

### FASE 3: Atendimento Clínico
O registro da consulta.

1.  **Tela: Painel do Veterinário (`vet_dashboard.php`)**
    -   *Objetivo*: Visão rápida para o Vet.
    -   *Widgets*: Próximas vacinas, Aniversariantes do dia.

2.  **Tela: Cadastro de Veterinários (`veterinarios.php`)**
    -   *Objetivo*: CRUD de staff clínico.
    -   *Vínculo*: Associar a um Usuário do sistema (para login).

3.  **Tela: Realizar Atendimento (`atendimento_form.php`)**
    -   *Fluxo*:
        -   Selecionar Pet (se não vier da tela de detalhes).
        -   **Anamnese**: Motivo da consulta, Histórico.
        -   **Exame Físico**: Peso, Temp, FC, FR, Mucosas, TPC.
        -   **Diagnóstico/Suspeita**.
        -   **Conduta/Tratamento**.
    -   *Salvar*: Gera registro na timeline do Pet.

### FASE 4: Documentos & Receituário
Geração de documentos para o tutor.

1.  **Funcionalidade: Gerador de Receita**
    -   *Local*: Dentro do Atendimento.
    -   *UI*: Editor de texto simples ou lista de medicamentos.
    -   *Ação*: "Imprimir Receita" (Gera PDF/Print view com cabeçalho da clínica).

---

## 3. Modelo de Dados (Schema Vet)
*Ver `migrate0.php` para definição SQL completa.*

- `Pets` (Lies to Clientes)
- `Veterinarios`
- `Vacinas`
- `CarteiraVacinas` (Links Pet + Vacina + Vet)
- `Atendimentos` (Links Pet + Vet)
- `DocumentosEmitidos` (Links Pet + content)
