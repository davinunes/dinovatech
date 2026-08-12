# Plano de Implementação - Módulo de Internação Veterinária

Adicionar funcionalidade de **Internação Veterinária** ao prontuário do pet (`pet_detalhes.php`), permitindo cadastrar internações com veterinário responsável, gerenciar a ficha digital de cada dia de internação (medicações digitadas livremente, dose, via, horários com marcadores de checagem, soro/fluidoterapia) e gerar a **Ficha de Internação para Impressão/PDF** idêntica ao modelo fornecido (`internacao.html`), com pré-preenchimento automático dos dados e campos vazios para preenchimento manual complementar.

---

## User Review Required

> [!IMPORTANT]
> **Fluxo de Preenchimento da Ficha Digital e Impressão**:
> 1. **Cadastro de Internação**: No prontuário do pet (`pet_detalhes.php?id=X`), haverá a nova seção **Internações**. Será possível abrir uma nova internação selecionando o Médico Veterinário Responsável, Data/Hora de entrada, Suspeita Clínica e Observações.
> 2. **Ficha Digital Diária**: Dentro da internação, o veterinário/equipe poderá adicionar/gerenciar dias de internação. Para cada dia, poderá cadastrar soro/fluidoterapia (Soro, Volume, Frequência, Obs) e a lista de medicações (digitadas livremente, dose, via e 6 horários com status de checagem `[x]`).
> 3. **Impressão / PDF (`internacao_print.php`)**: Ao clicar em "Imprimir Ficha", o sistema gera a página A4 exatamente nos moldes de `internacao.html`. Se a ficha digital tiver dados (dias/medicações), eles serão pré-preenchidos nos blocos de medicação. Se a ficha tiver menos de 11 medicações por dia ou menos de 3 blocos de dias, o sistema preencherá as linhas/blocos restantes em branco com a grade padrão para escrita à caneta!

---

## Open Questions

*(Nenhuma dúvida impeditiva no momento. Todas as diretrizes da especificação foram incorporadas).*

---

## Proposed Changes

### Database Migration

#### [NEW] [20260812_0004_create_internacoes_tables.sql](file:///e:/DEV/dinovatech/database/migrations/20260812_0004_create_internacoes_tables.sql)
- Criação da tabela `Internacoes`:
  - `id_internacao` (INT AUTO_INCREMENT PRIMARY KEY)
  - `id_pet` (INT NOT NULL, FK `Pets`)
  - `id_vet` (INT NULL, FK `Veterinarios`)
  - `data_internacao` (DATETIME NOT NULL)
  - `data_alta` (DATETIME NULL)
  - `suspeita_clinica` (TEXT NULL)
  - `status` (ENUM('internado', 'alta', 'obito', 'cancelado') DEFAULT 'internado')
  - `observacoes` (TEXT NULL)
  - `created_at` (TIMESTAMP DEFAULT CURRENT_TIMESTAMP)
- Criação da tabela `InternacaoDias`:
  - `id_dia` (INT AUTO_INCREMENT PRIMARY KEY)
  - `id_internacao` (INT NOT NULL, FK `Internacoes` ON DELETE CASCADE)
  - `data_dia` (DATE NOT NULL)
  - `soro` (VARCHAR(255) NULL)
  - `volume` (VARCHAR(100) NULL)
  - `frequencia` (VARCHAR(100) NULL)
  - `observacoes` (TEXT NULL)
- Criação da tabela `InternacaoMedicacoes`:
  - `id_medicacao` (INT AUTO_INCREMENT PRIMARY KEY)
  - `id_dia` (INT NOT NULL, FK `InternacaoDias` ON DELETE CASCADE)
  - `medicacao` (VARCHAR(255) NOT NULL)
  - `dose` (VARCHAR(100) NULL)
  - `via` (VARCHAR(100) NULL)
  - `horarios` (TEXT/JSON NULL) -> Armazena os 6 horários e seus status de check (ex: `[{"hora":"08:00","checked":1},{"hora":"12:00","checked":0},...]`)
  - `ordem` (INT DEFAULT 0)

---

### Backend (AJAX Operations)

#### [MODIFY] [app.php](file:///e:/DEV/dinovatech/dinovatech/app.php)
- Implementar as seguintes ações AJAX (com autenticação e tratamento de erros):
  - `save_internacao`: cria ou atualiza uma internação (pet, vet, data, suspeita, status, obs).
  - `delete_internacao`: remove uma internação.
  - `save_internacao_dia`: salva/atualiza informações de um dia de internação (soro, volume, frequência, obs).
  - `delete_internacao_dia`: remove um dia de internação.
  - `save_internacao_medicacao`: insere/edita linha de medicação livre com dose, via e horários.
  - `delete_internacao_medicacao`: remove uma linha de medicação.
  - `toggle_internacao_horario`: alterna o status do check de um horário de medicação digital.

---

### Módulo Vet / Interface do Prontuário

#### [MODIFY] [pet_detalhes.php](file:///e:/DEV/dinovatech/dinovatech/modules/Vet/pet_detalhes.php)
- Adicionar o card/seção **Internações** no painel do pet.
- Exibir a lista de internações ativas e anteriores do paciente com badges de status, médico responsável e atalhos rápidos:
  - Botão **Ficha Digital / Medicações** (abre modal/painel expansível para gerenciar dias, soro e medicações livremente).
  - Botão **Imprimir Ficha (PDF/Print)**.
  - Botão **Editar / Dar Alta**.
- Adicionar modal de cadastro/edição de internação.
- Adicionar modal/painel de gestão da Ficha Digital Diária (com opção de adicionar medicações com dose, via, 6 slots de horários e botões de checagem).

#### [NEW] [internacao_print.php](file:///e:/DEV/dinovatech/dinovatech/modules/Vet/internacao_print.php)
- Implementar a página de impressão baseada exatamente no HTML/CSS de `docs/ideias/docs_especificos/internacao.html`.
- Pré-preencher automaticamente:
  - Cabeçalho: Nome do Pet, Espécie, Raça, Cor (se houver/obs), Idade, Peso, Suspeita Clínica, Proprietário, Fone, Data/Hora da Internação, Médico Veterinário Responsável + CRMV.
  - Blocos de Medicação:
    - Renderiza até 3 blocos por folha A4.
    - Se houver dias e medicações registrados na Ficha Digital, popula a Data, o nome da Medicação, Dose, Via, e os 6 slots de horários com seus marcadores (com checkbox marcado ou `(X)` e a hora exibida).
    - Completa o restante do bloco com linhas vazias até totalizar 11 linhas para preenchimento manual a caneta.
    - Se não houver medicações digitais suficientes para 3 blocos, os blocos restantes são renderizados 100% em branco (11 linhas vazias) para uso manual na clínica.

---

## Verification Plan

### Automated / Database Verification
1. Executar a migração SQL ou o script `php scripts/migrate.php` para validar a criação das tabelas `Internacoes`, `InternacaoDias` e `InternacaoMedicacoes`.

### Manual Verification
1. **Acessar o Prontuário de um Pet**:
   - Abrir `dinovatech/modules/Vet/pet_detalhes.php?id=X`.
   - Verificar a exibição do novo bloco de **Internações**.
2. **Cadastrar Nova Internação**:
   - Clicar em "Nova Internação", selecionar o veterinário responsável, data/hora e suspeita clínica. Salvar e conferir se a internação aparece na lista.
3. **Gerenciar Ficha Digital**:
   - Adicionar medicações com texto livre (ex: *Enrofloxacino*, dose *0.5ml*, via *IM*, horários *08:00 (checked), 20:00 (unchecked)*).
   - Definir os dados de fluidoterapia/soro.
4. **Gerar PDF / Ficha de Impressão**:
   - Clicar no botão "Imprimir Ficha".
   - Confirmar que o layout A4 é exatamente igual a `internacao.html`.
   - Confirmar que os dados do pet, tutor e veterinário estão preenchidos.
   - Confirmar que a medicação digital aparece no topo do bloco de medicação com as checagens ativas e que as linhas abaixo permanecem vazias para anotações à caneta.
