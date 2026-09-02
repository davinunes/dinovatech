# Raciocínio Analítico: Desativação de Serviços com Abas e Bloqueio por Vigência de Contrato

- **Data**: 2026-09-01
- **Objetivo**: Permitir a desativação de serviços no catálogo, organizando os dados entre as abas "Serviços Ativos" e "Serviços Desativados", e aplicando a regra de que serviços vinculados a contratos em vigência não podem ser desativados.

---

## 1. Análise do Problema e Regra de Negócio

1. **Catálogo de Serviços**:
   - Anteriormente, a tabela `Servicos` não possuía um campo explícito de ativação (`ativo`), e a página `servicos.php` listava todos os serviços sem distinção de status.
   - Para permitir desativar serviços sem quebrar integridade referencial com o histórico financeiro (faturas anteriores, relatórios e RPS), a exclusão física (`DELETE`) não é apropriada. O correto é o soft disable (`ativo = 0`).

2. **Regra de Contratos em Vigência**:
   - Um serviço não pode ser desativado se fizer parte de um contrato (`Recorrencias`) ativo cuja vigência ainda não terminou.
   - Na estrutura do sistema, contratos em vigência são caracterizados por:
     `R.id_servico = $id_servico AND (R.data_fim_cobranca IS NULL OR R.data_fim_cobranca >= CURDATE())`
   - Caso existam contratos nessa condição, o backend deve impedir a operação e avisar quais clientes e quantos contratos dependem daquele serviço.

3. **Experiência do Usuário (UX)**:
   - Divisão de `servicos.php` em abas **"Serviços Ativos"** e **"Serviços Desativados"** com contadores numéricos (badges).
   - Ação rápida de desativar (com confirmação) e reativar.
   - Em novos cadastros (ex: `contrato_form.php`, `servicos_prestados.php`, `pacote_form.php`), os dropdowns devem omitir serviços inativos para evitar novos vínculos indevidos.

---

## 2. Decisões Técnicas

1. **Migration idempotente**:
   - Criação de `database/migrations/20260901_0001_add_ativo_to_servicos.sql` com verificação prévia no `INFORMATION_SCHEMA.COLUMNS` e `INFORMATION_SCHEMA.STATISTICS` para compatibilidade com MariaDB 10.x.
2. **Backend centralizado em `app.php`**:
   - Nova ação `alterar_status_servico` / `toggle_status_servico` que valida tanto para chamadas rápidas via AJAX na listagem quanto em atualizações pelo formulário `editar_servico`.
3. **Filtros inteligentes em selects de contratos**:
   - Ao editar um contrato existente que já usava um serviço agora inativo, a query faz `WHERE (ativo = 1 OR ativo IS NULL OR id_servico = '$current_servico_id')`, evitando perda de dados no dropdown de edição.

---

## 3. Conclusão
A implementação foi finalizada com sucesso, preservando a retrocompatibilidade do banco e garantindo a regra de negócio solicitada.
