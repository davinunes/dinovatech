# Plano: Vinculação de Cliente no Modal do Extrato Inter via CPF/CNPJ

- **Data**: 2026-09-10
- **Status**: Em aprovação

## 1. Contexto e Motivação
No [dashboard.php](file:///e:/DEV/dinovatech/dinovatech/dashboard.php), o Modal do Extrato Inter renderiza a lista de transações bancárias. Nomes longos ou de condomínios/empresas frequentemente chegam truncados pelo Banco Inter ou pelo arranjo Pix. O cruzamento pelo CPF/CNPJ do pagador com a tabela de `Clientes` viabiliza:
- Identificação visual imediata do cliente cadastrado.
- Exibição da foto/avatar com link direto para [cliente_detalhes.php](file:///e:/DEV/dinovatech/dinovatech/cliente_detalhes.php).
- Exibição do nome oficial completo cadastrado.
- Busca no filtro do extrato pelo nome do cliente cadastrado.

## 2. Arquivos a serem alterados
1. [inter/endpoint.php](file:///e:/DEV/dinovatech/inter/endpoint.php): Na ação `consultar_extrato_completo`, implementar busca em lote por CPFs/CNPJs e anexar `cliente_vinculado` a cada transação.
2. [dinovatech/dashboard.php](file:///e:/DEV/dinovatech/dinovatech/dashboard.php): Atualizar a renderização da coluna "Pagador / Detalhes" (desktop e mobile) para exibir foto/avatar com link para detalhes e o nome oficial do cliente, além de integrar o filtro de busca ao nome do cliente vinculado.

## 3. Estratégia de Teste
- Validar extrato bancário com pagadores que possuam cadastro e pagadores desconhecidos.
- Testar clique no avatar/nome redirecionando para a página de detalhes em nova aba.
- Testar responsividade nos cards mobile e campo de busca rápida.
