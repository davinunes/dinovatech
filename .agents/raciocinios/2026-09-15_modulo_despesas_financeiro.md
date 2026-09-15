# Raciocínio Analítico: Módulo de Despesas e Contas a Pagar

**Data**: 2026-09-15  
**Contexto**: Criação do módulo de despesas / contas a pagar no sistema Dinovatech, reformulação do menu financeiro expansível, gerenciamento de fornecedores e centros de custo embutidos na dashboard de despesas, ciclo de despesas (avulsa, parcelada e recorrente), upload de documentos via Oracle Object Storage, liquidação, balanço consolidado no dashboard e visualização de vencimentos na agenda geral.

---

## 1. Problema e Demanda do Usuário

O sistema Dinovatech já possui controle robusto de receitas (faturas a receber, emissão de NFS-e, integração com Banco Inter / InfinitePay, esteira de banho e tosa e prontuários veterinários), mas carecia do lado de **contas a pagar / despesas operacionais** da empresa.

Principais requisitos estabelecidos pelo usuário:
1. **Navegação limpa**: Criar menu expansível "Financeiro" contendo dois submenus:
   - Receitas (contas a receber pagas e em aberto)
   - Despesas (contas a pagar)
2. **Evitar poluição do menu lateral**: O acesso ao cadastro de Fornecedores e Centros de Custo não deve ocupar espaço no menu lateral; deve ficar na própria tela de despesas através de botões de ação rápida.
3. **Tipos de Despesa**:
   - **Avulsa**: lançamento pontual único.
   - **Parcelada**: quantidade de parcelas conhecida, com valor fixo ou passível de ajuste mês a mês.
   - **Recorrente**: conta contínua que se repete mensalmente por prazo indeterminado (ex: água, luz, aluguel, internet, contabilidade), com geração automática de competência ao iniciar o mês ou sob demanda.
4. **Fornecedores**: Razão Social, Nome Fantasia, CPF/CNPJ, Contatos e Observações adicionais.
5. **Centros de Custo**: categorização para agrupamento e filtragem contábil/gerencial.
6. **Uploads**: Anexação de arquivos (boletos, notas fiscais, faturas e comprovantes de pagamento).
7. **Liquidação**: Baixa de despesas com registro de data, valor pago e forma de pagamento.
8. **Dashboard Principal**: Incorporar o balanço financeiro consolidado do mês (Receitas vs Despesas = Saldo Operacional).
9. **Agenda Geral**: Possibilidade de visualizar os vencimentos de despesas diretamente no calendário de agendamentos.

---

## 2. Decisões Arquiteturais e Modelagem

### 2.1 Modelagem de Tabelas (MariaDB 10.x / PHP 7.4)
- **`Fornecedores`**: Tabela dedicada para armazenar credores da empresa, desacoplada de `Clientes` e `Veterinarios`.
- **`CentrosCusto`**: Tabela simples para classificação de despesas com códigos de cores para badges visuais.
- **`Despesas`**: Entidade principal que suporta os 3 modos de operação:
  - Para despesas avulsas: `tipo='avulsa'`, `parcela_atual=1`, `total_parcelas=1`.
  - Para despesas parceladas: cada parcela é gerada como um registro individual com `tipo='parcelada'`, mantendo vínculo com `id_despesa_pai` ou sequenciador `parcela_atual / total_parcelas`. Isso permite que o usuário altere o valor de uma parcela específica (ex: parcela com reajuste ou juros) sem quebrar as demais.
  - Para despesas recorrentes: existe um registro base modelo (`recorrencia_ativa=1`) que define o fornecedor, centro de custo, dia de vencimento e valor base; e cada mês gera uma despesa executável (`data_competencia = 'YYYY-MM'`), permitindo ajustar o valor real apurado daquele mês (ex: consumo de energia).
- **`DespesaArquivos` & `Arquivos`**: Reutilização direta da tabela `Arquivos` existente no Dinovatech e da integração cURL PUT com o Oracle Object Storage (OCI S3), garantindo conformidade com a infraestrutura documentada em `infra_dinovatech`.

### 2.2 Dashboard de Despesas (`despesas.php`)
- A dashboard conterá os botões de ação para abrir modais de **Fornecedores** e **Centros de Custo**, mantendo a sidebar compacta.
- Botão "Sincronizar Recorrências" garante que, ao navegar para um mês novo, o sistema gere com um único clique (ou automaticamente na carga da página) as contas recorrentes ativas.
- Cards informativos: A Pagar no Mês, Pago no Mês, Total em Atraso e Total Geral Previsto.

### 2.3 Balanço na Dashboard Principal (`dashboard.php`)
- A dashboard principal passará a calcular o balanço do mês:
  - `Receitas Liquidadas` - `Despesas Liquidadas` = **Saldo Realizado**.
  - Projeção: `Receitas Previstas` - `Despesas Previstas` = **Saldo Previsto**.

### 2.4 Agenda Geral (`modules/Agenda/api.php`)
- No método `get_events`, adicionar consulta opcional de despesas com vencimento no intervalo visível do calendário.
- As despesas aparecerão no calendário com visual diferenciado (ícone `💸` e cores vermelha para aberto ou verde para liquidado), permitindo que o gestor visualize prazos críticos de pagamento sem precisar alternar de tela.

---

## 3. Próximos Passos
1. Obter aprovação do usuário sobre o plano de implementação.
2. Executar a criação da migration SQL.
3. Desenvolver as rotas no backend `app.php`.
4. Criar as interfaces `despesas.php` e `receitas.php`.
5. Atualizar o menu lateral `sidebar.php`.
6. Conectar o balanço na `dashboard.php` e os eventos de vencimento em `modules/Agenda/api.php`.
