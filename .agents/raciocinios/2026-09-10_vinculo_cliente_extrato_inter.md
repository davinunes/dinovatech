# Raciocínio Analítico: Vínculo de Clientes no Extrato Inter

- **Data**: 2026-09-10
- **Contexto**: Exibição das transações bancárias da API do Banco Inter no modal do dashboard Dinovatech. Nomes de pagadores vindos da rede bancária/PIX costumam vir truncados ou muito similares (especialmente condomínios e clientes empresariais).
- **Objetivo**: Cruzar o CPF/CNPJ do pagador retornado na API com a base de clientes do Dinovatech para identificar o cliente e exibir foto/avatar com link para a página de detalhes, bem como o nome completo registrado no sistema.

## Investigação e Análise

1. **Endpoint de Origem das Transações**:
   - O modal em `dinovatech/dashboard.php` chama via AJAX `../inter/endpoint.php?action=consultar_extrato_completo`.
   - Esse endpoint invoca `consultarExtratoCompleto()` de `inter/api.php` e devolve um objeto JSON contendo o array `transacoes`.
   - Cada transação possui um bloco `detalhes` com `nomePagador`, `cpfCnpjPagador`, `txId`, `descricaoPix`, etc.

2. **Base de Dados de Clientes**:
   - Tabela `Clientes`: campos `id_cliente`, `nome`, `cpf_cnpj`, `foto_url`.
   - O documento `cpf_cnpj` pode conter pontuação em alguns registros antigos ou apenas dígitos numéricos nos cadastros recentes.
   - A página de detalhes do cliente é acessível via `cliente_detalhes.php?id=[id_cliente]`.

3. **Arquitetura da Solução**:
   - **Enriquecimento em lote no backend**: Realizar o matching no próprio endpoint `inter/endpoint.php` na ação `consultar_extrato_completo`. Coleta todos os CPFs/CNPJs das transações da página (até 100 itens), higieniza apenas com dígitos e executa uma única query otimizada `IN (...)` com limpeza de máscara (`REPLACE`).
   - Associa `$item->cliente_vinculado = [...]` em cada transação antes de retornar o JSON.
   - **Camada Visual no Frontend (`dashboard.php`)**:
     - Na função `renderizarExtratoTransacoes()`, se `t.cliente_vinculado` estiver presente, exibe a foto do cliente ou avatar com a inicial, tornando-o um link para `cliente_detalhes.php?id=...`.
     - Exibe o nome oficial do cliente com selo verificado, e caso o nome que veio do banco seja divergente ou truncado, mantém uma linha discreta de apoio.
     - Melhora o campo de busca `#filtroTextoExtrato` para permitir localizar transações pelo nome ou documento do cliente cadastrado.
