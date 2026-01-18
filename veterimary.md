# Planejamento: Sistema de Clínica Veterinária (DinoVet)

## 1. Visão Geral
Transformar o sistema "Dinovatech" em **DinoVet**, um sistema de gestão para clínicas veterinárias.
A base administrativa (Clientes, Faturas, Serviços) é mantida.
Entidades adicionadas: Pets, Veterinários, Vacinas, Atendimentos, Documentos.

## 2. Roteiro de Implementação Detalhado

### FASE 0: Fundação & Rebranding [CONCLUÍDO]
- [x] **Migração de Banco de Dados**: Executar `migrate0.php`.
- [x] **UI Rebranding**: Alterar logotipos e títulos para "DinoVet".
- [x] **Ajuste de Menu**: Agrupar itens de menu por contexto (Adm vs Clínico).

### FASE 1: Gestão de Pets (Os Pacientes) [CONCLUÍDO]
Esta fase conecta os Clientes (Tutors) aos seus animais.

1.  **Tela: Listagem de Pets (`pets.php`)** [OK]
    -   *Objetivo*: Listar todos os pets cadastrados no sistema.
    -   *Colunas*: Nome, Espécie/Raça, Tutor (Link), Idade, Peso.
    -   *Filtros*: Busca por nome, espécie.

2.  **Tela: Detalhes do Pet (`pet_detalhes.php`)** [OK]
    -   *Objetivo*: O "Prontuário" central do animal.
    -   *Seções*:
        -   **Card Principal**: Foto, Dados Básicos, Tutor.
        -   **Histórico Clínico**: Timeline de atendimentos.
        -   **Vacinas**: Carteira de vacinas ativa.

3.  **Tela: Formulário de Pet (`pet_form.php`)** [OK]
    -   *Objetivo*: Cadastro e Edição.
    -   *Campos*: Nome, Tutor (Select2/Autocomplete), Espécie (Select), Raça, Sexo, Data Nasc, Cor/Pelagem, Chip, Obs.

4.  **Integração Cliente (`cliente_detalhes.php`)** [OK]
    -   *Ação*: Adicionar aba/bloco "Meus Pets" na tela de detalhes do tutor.

### FASE 2: Módulo de Vacinação [CONCLUÍDO]
Controle de imunização e alertas.

1.  **Tela: Catálogo de Vacinas (`vacinas.php`)** [OK]
    -   *Objetivo*: CRUD de tipos de vacinas (ex: V10, Antirrábica).
    -   *Campos*: Nome, Descrição, Recorrência Padrão (dias).
    -   **[NOVO] Ciclos/Protocolos**: Configuração de múltiplas doses (ex: Filhote 21 dias vs Reforço Anual).

2.  **Funcionalidade: Registro de Aplicação** [OK]
    -   *Local*: Dentro de `pet_detalhes.php` (Aba Vacinas).
    -   *Modal/Form*: Selecionar Vacina, Ciclo (se houver), Data Aplicação.
    -   *Cálculo*: Data de Revacina auto-calculada (via Ciclo ou Padrão) + Botões de ajuste rápido.

3.  **Visualização: Carteira de Vacinação** [OK]
    -   *UI*: Tabela com status colorido (Verde=Em dia, Amarelo=Próxima, Vermelho=Vencida).

### FASE 3: Atendimento Clínico [CONCLUÍDO]
O registro da consulta.

1.  **Tela: Cadastro de Veterinários (`veterinarios.php`)** [OK]
    -   *Objetivo*: CRUD de staff clínico.
    -   *Campos*: Nome, CRMV, UF, Contatos.

2.  **Tela: Realizar Atendimento (`atendimento_form.php`)** [OK]
    -   *Fluxo*:
        -   Selecionar Pet e Veterinário.
        -   **Anamnese**: Queixa Principal, Histórico.
        -   **Exame Físico**: Peso (atualiza cadastro), Campo aberto.
        -   **Diagnóstico/Suspeita**.
        -   **Conduta/Tratamento** (Receita simples).
    -   *Salvar*: Gera registro histórico no Pet e atualiza peso.

### FASE 4: Documentos & Receituário [PENDENTE]
Geração de documentos para o tutor.

1.  **Funcionalidade: Imprimir Atendimento/Receita**
    -   *Local*: Botão na linha do atendimento (Histórico).
    -   *UI*: Gerar PDF limpo com logo, dados do vet, dados do pet e a prescrição.

2.  **Funcionalidade: Atestados e Termos**
    -   *Objetivo*: Gerar documentos padrão (Atestado de Saúde, Termo de Cirurgia).

---

## 3. Modelo de Dados (Schema Vet)
*Ver `migrate0.php` e `migrate_vaccines.php` para definição SQL completa.*

- `Pets` (Lies to Clientes)
- `Veterinarios`
- `Vacinas` -> `VacinaCiclos`
- `CarteiraVacinas` (Links Pet + Vacina)
- `Atendimentos` (Links Pet + Vet)
- `DocumentosEmitidos` (Links Pet + content)
