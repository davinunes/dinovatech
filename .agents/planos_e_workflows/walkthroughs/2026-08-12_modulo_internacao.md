# Walkthrough - Módulo de Internação Veterinária

Adicionada a funcionalidade completa de **Internação Veterinária por Pet**, incluindo cadastro de internações, seleção de veterinário responsável, gestão da **Ficha Digital Diária** (fluidoterapia, medicações digitadas livremente com dose, via e horários com marcadores de checagem) e **Geração/Impressão da Ficha em PDF** conforme o modelo de referência `internacao.html`.

---

## Alterações Efetuadas

### 1. Banco de Dados / Migração
- **[NEW] [20260812_0004_create_internacoes_tables.sql](file:///e:/DEV/dinovatech/database/migrations/20260812_0004_create_internacoes_tables.sql)**:
  - Tabela `Internacoes`: armazena `id_pet`, `id_vet` (médico veterinário responsável), `data_internacao`, `data_alta`, `suspeita_clinica`, `status` (`internado`, `alta`, `obito`, `cancelado`) e `observacoes`.
  - Tabela `InternacaoDias`: armazena registros por dia (`data_dia`, `soro`, `volume`, `frequencia`, `observacoes`).
  - Tabela `InternacaoMedicacoes`: armazena as medicações de cada dia (`medicacao` com digitação livre, `dose`, `via`, `horarios` como JSON com os 6 slots e flags de checagem).

### 2. Backend / API
- **[MODIFY] [app.php](file:///e:/DEV/dinovatech/dinovatech/app.php)**:
  - Adicionadas as ações AJAX:
    - `save_internacao`: cria ou atualiza uma internação (cria automaticamente o Dia 1 ao cadastrar).
    - `delete_internacao`: exclui a internação e registros vinculados.
    - `save_internacao_dia` / `delete_internacao_dia`: gerencia os dias da internação e dados de fluidoterapia.
    - `save_internacao_medicacao` / `delete_internacao_medicacao`: insere/edita/remove medicações da ficha digital.
    - `get_internacao_details`: retorna os dados completos da internação, seus dias e medicações para renderização na Ficha Digital.

### 3. Ficha de Impressão / PDF
- **[NEW] [internacao_print.php](file:///e:/DEV/dinovatech/dinovatech/modules/Vet/internacao_print.php)**:
  - Implementação idêntica ao modelo fornecido em `internacao.html`.
  - Pré-preenchimento dos dados do Pet (Nome, Espécie, Raça, Cor, Idade, Peso, Suspeita Clínica), Tutor (Nome, Telefone), Internação (Data/Hora de entrada) e Veterinário Responsável (+ CRMV).
  - Pré-preenchimento dos blocos de medicação com dados digitais (Medicação, Dose, Via, 6 slots com horários e `[X]` caso aplicados).
  - Preenchimento automático de linhas vazias até totalizar 11 linhas por bloco (e blocos em branco se houver menos de 3 dias) para escrita manual a caneta.

### 4. Interface do Prontuário do Pet
- **[MODIFY] [pet_detalhes.php](file:///e:/DEV/dinovatech/dinovatech/modules/Vet/pet_detalhes.php)**:
  - Adicionado o bloco **Internações** no prontuário do paciente com badges coloridos de status (`Em Internação`, `Alta Médica`, `Óbito`, `Cancelado`).
  - Botão **Nova Internação** e modal com seleção do Veterinário Responsável, Data/Hora, Status, Suspeita Clínica e Observações.
  - Modal **Ficha Digital de Internação**:
    - Abas navegáveis por dia (`Dia 1`, `Dia 2`...) com botão de adicionar novos dias.
    - Formulário para registrar Soro, Volume, Frequência e Observações de fluidoterapia.
    - Tabela de medicações digitadas livremente com botão de checagem rápida para alternar horários aplicados.
    - Sub-modal para lançar/editar medicações com dose, via e os 6 horários.

---

## Validação e Instruções de Uso

1. **Rodar a Migração**:
   No ambiente do servidor remoto, executar o script de migração:
   ```bash
   php scripts/migrate.php
   ```
   ou executar o arquivo SQL `database/migrations/20260812_0004_create_internacoes_tables.sql`.

2. **Testar o Cadastro de Internação**:
   - Acesse o prontuário de um pet em `dinovatech/modules/Vet/pet_detalhes.php?id=X`.
   - Clique em **"Nova Internação"**, selecione o Veterinário Responsável, informe a suspeita clínica e salve.

3. **Testar a Ficha Digital**:
   - Na lista de internações, clique no botão **"Ficha Digital"**.
   - Alterne ou adicione dias, preencha os dados de fluidoterapia e lance medicações com texto livre (ex: *Zofran*, dose *1 ml*, via *IV*, horários *08:00*, *16:00*, *00:00*).
   - Marque os horários como "Aplicado" diretamente nos botões de slot.

4. **Gerar PDF / Ficha de Impressão**:
   - Clique em **"Imprimir Ficha"**.
   - Verifique que a página de impressão A4 possui os dados do paciente, tutor, veterinário e a grade de medicação preenchida com as medicações digitais lançadas e linhas em branco para anotação a caneta.
