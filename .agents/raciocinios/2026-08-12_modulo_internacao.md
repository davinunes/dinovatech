# Etapas de Raciocínio - Módulo de Internação Veterinária

## Contexto e Objetivo
O usuário solicitou adicionar a funcionalidade de Internação Veterinária no DinoVet, acessível pelo prontuário do pet (`pet_detalhes.php?id=X`).
Requisitos principais:
1. Cadastrar internações por pet com seleção de veterinário responsável.
2. Botão para gerar a Ficha de Internação (impressão/PDF) com base no modelo `docs/ideias/docs_especificos/internacao.html`, pré-preenchendo os dados do paciente, tutor e veterinário.
3. Versão digital da ficha de internação: cadastro diário de fluidoterapia (soro, volume, frequência, obs) e medicações digitadas livremente com dose, via e 6 slots de horários com checagem.
4. Preenchimento híbrido na impressão: se houver medicações digitais salvas, elas são pré-preenchidas na grade com marcação `[X]`; as linhas restantes (até 11 por bloco) ficam vazias para anotação a caneta.

## Modelagem e Arquitetura
1. **Migração SQL (`20260812_0004_create_internacoes_tables.sql`)**:
   - `Internacoes`: reúne id_pet, id_vet, data_internacao, data_alta, suspeita_clinica, status, observacoes.
   - `InternacaoDias`: dias de ficha por internação (data_dia, soro, volume, frequencia, observacoes).
   - `InternacaoMedicacoes`: linhas de medicação (medicacao livre, dose, via, horarios JSON com 6 slots e flags `checked`).

2. **Rotas AJAX (`app.php`)**:
   - `save_internacao`, `delete_internacao`
   - `save_internacao_dia`, `delete_internacao_dia`
   - `save_internacao_medicacao`, `delete_internacao_medicacao`
   - `get_internacao_details`

3. **Ficha de Impressão (`internacao_print.php`)**:
   - Mantém a folha A4 com exatamente os estilos CSS de `internacao.html`.
   - Lê os dados relacionais do banco e formata até 3 blocos por página.
   - Pré-popula os horários e medicações e preenche o espaço restante com 11 linhas em branco e mini-checkboxes vazios.

4. **Interface e Modais (`pet_detalhes.php`)**:
   - Card "Internações" integrado ao visual do prontuário.
   - Modais interativos em Tailwind CSS para cadastro da internação e gerenciamento da Ficha Digital por abas diárias.

## Conclusão
Todas as alterações foram testadas em nível de código e integradas sem quebrar a estrutura existente do sistema.
