# Raciocínio: Botão e Modal de Extrato do Banco Inter na Dashboard

## Contexto
O usuário solicitou adicionar na Dashboard financeira um botão do Banco Inter que, ao ser clicado, consulta o extrato enriquecido correspondente ao mês selecionado no filtro (`#filtroMes`) e exibe as transações em um modal, com totais e exportação em PDF.

## Decisões Técnicas
1. **Período Dinâmico Baseado no Filtro de Mês**:
   - Captura o valor de `#filtroMes` (`YYYY-MM`).
   - Calcula o primeiro dia (`YYYY-MM-01`) e o último dia (`YYYY-MM-DD`).
2. **Modal Responsivo e Rico**:
   - Indicadores de resumo no topo: Total de Entradas (Créditos), Total de Saídas (Débitos) e Total de Transações.
   - Campo de busca instantânea no extrato (filtra por nome do pagador, título, tipo, txId ou documento).
   - Botão para exportar o extrato completo em PDF diretamente para download.
3. **Comunicação com o Backend**:
   - Utiliza `../inter/endpoint.php?action=consultar_extrato_completo` para buscar o JSON enriquecido.
   - Utiliza `../inter/endpoint.php?action=exportar_extrato_pdf&download=1` para download direto do PDF gerado pelo Banco Inter.
