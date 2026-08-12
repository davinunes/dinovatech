# Plano de Implementação: Modal "Meus Pets" com Estatísticas e Gráfico de Evolução de Peso

**Data:** 2026-08-12  
**Funcionalidade:** Clique no card "Meus Pets" do Dashboard do Cliente para abrir modal com cards de status, estatísticas e gráfico de evolução do peso de cada pet.

## Componentes Envolvidos

### 1. Banco de Dados
- **[NEW] Migration SQL**: `database/migrations/20260812_0003_add_peso_to_atendimentos.sql`
  ```sql
  ALTER TABLE Atendimentos ADD COLUMN peso DECIMAL(5,2) DEFAULT NULL;
  ```

### 2. Form de Atendimentos
- **[MODIFY] [atendimento_form.php](file:///e:/DEV/dinovatech/dinovatech/modules/Vet/atendimento_form.php)**
  - Atualizar handler de inserção/edição para salvar `$peso` também na coluna `Atendimentos.peso`.

### 3. Backend (PHP/AJAX)
- **[MODIFY] [app.php](file:///e:/DEV/dinovatech/dinovatech/app.php)**
  - No endpoint `get_cliente_dashboard_data`, incluir no objeto dos pets:
    - Lista histórica de pesagens (`data_atendimento`, `peso`).
    - Total de consultas/atendimentos.
    - Último atendimento (data e queixa).
    - Status de vacinas.

### 4. Frontend (Portal do Cliente)
- **[MODIFY] [index.php](file:///e:/DEV/dinovatech/cliente/index.php)**
  - Adicionar biblioteca Chart.js via CDN.
  - Tornar o card "Meus Pets" no Dashboard clicável com efeito hover.
  - Adicionar o Modal HTML `#modalMeusPetsDetalhes`.
  - Implementar a renderização dos cards dos pets e a inicialização dinâmica de gráficos Chart.js para pets com mais de uma pesagem registrada.

## Plano de Testes
1. Executar a migration SQL `20260812_0003_add_peso_to_atendimentos.sql`.
2. Acessar a Área do Cliente em modo veterinário.
3. Clicar no card "Meus Pets".
4. Verificar se o modal abre e se os pets com múltiplas pesagens exibem o gráfico de evolução do peso, enquanto pets com uma pesagem exibem o badge de última pesagem.
