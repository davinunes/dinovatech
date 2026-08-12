# Raciocínio Analítico: Modal de Estatísticas e Evolução de Peso dos Pets

**Data:** 2026-08-12  
**Contexto:** Ao clicar no card "Meus Pets" na Dashboard da Área do Cliente (modo veterinário), exibir um modal com os cartões de status de cada pet do tutor, incluindo estatísticas de consultas, histórico de vacinas e gráfico de evolução do peso.

## 1. Análise dos Dados e Banco de Dados
- **Tabela `Pets`**: guarda o peso atual do animal (`peso`).
- **Tabela `Atendimentos`**: armazena os atendimentos com data (`data_atendimento`).
- **Evolução de Peso**:
  - Para registrar o histórico de pesagem a cada atendimento, adicionaremos a coluna `peso DECIMAL(5,2) DEFAULT NULL` na tabela `Atendimentos` via migration `20260812_0003_add_peso_to_atendimentos.sql`.
  - Atualizaremos `atendimento_form.php` para salvar o peso em `Atendimentos.peso` além de atualizar `Pets.peso`.
- **Condições de Exibição de Peso**:
  - **Mais de 1 registro com peso > 0**: Exibir gráfico de linha (Chart.js) mostrando a evolução do peso ao longo do tempo.
  - **Apenas 1 registro com peso > 0**: Exibir card resumido com a última pesagem (ex: `Última Pesagem: 5.4 kg em 10/08/2026`).
  - **Nenhum registro**: Exibir mensagem "Nenhuma pesagem registrada".

## 2. Componentes Frontend & Bibliotecas
- Incluir `Chart.js` via CDN em `cliente/index.php` para renderização responsiva dos gráficos de linha.
- Modal `#modalMeusPetsDetalhes`:
  - Lista de cards por pet.
  - Informações de espécie, raça, idade e gênero.
  - Estatísticas de consultas e vacinas.
  - Canvas dinâmico para renderizar o gráfico de peso por pet.

## 3. Benefícios
- Engajamento do tutor com a saúde do pet.
- Acompanhamento preciso do ganho/perda de peso do animal ao longo dos tratamentos médicos.
