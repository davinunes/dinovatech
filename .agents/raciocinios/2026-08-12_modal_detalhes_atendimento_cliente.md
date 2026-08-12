# Raciocínio Analítico: Modal de Detalhes do Atendimento na Área do Cliente

**Data:** 2026-08-12  
**Contexto:** Exibição detalhada de um atendimento clínico para o cliente ao clicar no card de atendimento no Dashboard. O cliente poderá visualizar o prontuário completo (queixa, anamnese, exame físico, diagnóstico, tratamento), receitas emitidas e arquivos/anexos anexados.

## 1. Análise da Estrutura Atual
- **Dashboard do Cliente (`cliente/index.php`)**: Exibe os cards de atendimentos recentes no modo veterinário com informações resumidas (Pet, Data, Motivo e Veterinário).
- **Dados Necessários**:
  - `Atendimentos`: queixa_principal, anamnese, exame_fisico, diagnostico, conduta_tratamento, data_atendimento.
  - `Pets`: nome, especie, raca, peso.
  - `Veterinarios`: nome, crmv, uf_crmv.
  - `AtendimentoArquivos` + `Arquivos`: arquivos anexados.
  - `Receitas` + `ItensReceita`: prescrições e medicamentos emitidos.

## 2. Hipótese e Solução Proposta

### Backend (`dinovatech/app.php`)
- Criar a ação `get_atendimento_detalhes_cliente`:
  - Entrada: `id_atendimento`.
  - Validação de segurança: garantir que o `id_atendimento` pertence a um Pet do `id_cliente` logado na sessão (`$_SESSION['cliente_id']`).
  - Consultar dados do Atendimento, Pet e Veterinário.
  - Consultar arquivos vinculados (`AtendimentoArquivos` / `Arquivos`).
  - Consultar receitas vinculadas (`Receitas` / `ItensReceita`).
  - Retornar payload JSON completo e estruturado.

### Frontend (`cliente/index.php`)
- Adicionar evento de clique nos cards de atendimentos recentes do Dashboard.
- Criar Modal responsivo "Detalhes do Atendimento Clínico":
  - **Cabeçalho**: Dados do Pet, Data/Hora da consulta, Veterinário e CRMV.
  - **Bloco Prontuário**: Queixa Principal, Anamnese, Exame Físico, Diagnóstico e Conduta/Tratamento.
  - **Bloco Receitas**: Lista de receitas com medicamentos e botão para imprimir/visualizar (`modules/Vet/receita_print.php?id=...`).
  - **Bloco Anexos**: Exames/arquivos anexados com links para download/visualização direta.

## 3. Justificativa
- Dá total transparência ao tutor do pet sobre o histórico de saúde dos animais de forma segura (somente leitura).
- Centraliza receitas e exames diretamente na Área do Cliente sem necessidade de contato extra com a clínica.
