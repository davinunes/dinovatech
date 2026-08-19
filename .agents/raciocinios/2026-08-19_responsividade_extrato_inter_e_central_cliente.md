# Raciocínio: Redesenho Responsivo do Extrato Inter e Central do Cliente

## 1. Desafios Mobile Identificados
- **Extrato Banco Inter**: Em dispositivos móveis, tabelas padrão com mais de 3 colunas forçam overflow horizontal ou esmagamento de texto. A melhor prática em bancos digitais é adotar uma lista de cartões (timeline/feed) no mobile e tabela no desktop.
- **Central do Cliente**:
  - Modais com margens fixas e paddings pesados (`p-6`) consomem até 40% da largura útil em telas de 360px-400px.
  - As abas quebram em várias linhas poluindo o visual.
  - Gráficos do Chart.js necessitam de container de altura responsiva controlada para não esticar verticalmente.

## 2. Abordagem
- **Extrato**: Desenvolver renderizador duplo no JS e no HTML: `#extratoTabelaContainer` (desktop) e `#extratoMobileCardsContainer` (mobile), populados simultaneamente pela mesma lógica de filtragem e cálculo de totais.
- **Central do Cliente**:
  - Reduzir paddings em mobile para `p-3` a `p-4` e `p-6` em desktop (`p-4 sm:p-6`).
  - Implementar CSS `.no-scrollbar` para permitir rolagem horizontal suave em abas e filtros sem barra de rolagem cinza feia.
  - Repensar grid de KPIs para 2 colunas no mobile e 4 no desktop.
