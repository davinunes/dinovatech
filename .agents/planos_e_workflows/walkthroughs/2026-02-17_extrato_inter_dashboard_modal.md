# Walkthrough: Botão e Modal de Extrato do Banco Inter na Dashboard

Adicionamos na [dashboard.php](file:///e:/DEV/dinovatech/dinovatech/dashboard.php) o botão **Extrato Inter** no cabeçalho de filtros, permitindo visualizar em um modal todas as transações bancárias do mês selecionado com totais consolidados e opção de download do PDF oficial.

---

## 🛠️ O que foi adicionado

### 1. Botão no Cabeçalho de Filtros
- Botão laranja estilizado (**Extrato Inter**) ao lado do botão *Filtrar*.
- **Exibição Condicional:** O botão só é renderizado caso a integração com o Banco Inter esteja de fato configurada no banco de dados (`api_inter_client_id`, `api_inter_client_secret` e certificado cadastrado).
- Lê automaticamente o mês selecionado no filtro (`#filtroMes`) e monta o período de 1º dia até a data atual (ou último dia para meses anteriores).

### 2. Modal Completo de Extrato
- **Header:** Período formatado (*ex: 01/08/2026 até 31/08/2026*), botão de fechar e botão **Exportar PDF**.
- **Cards de Resumo no topo:**
  - 🟢 **Entradas / Créditos** (Soma total dos valores com `tipoOperacao = 'C'`)
  - 🔴 **Saídas / Débitos** (Soma total dos valores com `tipoOperacao = 'D'`)
  - ⚪ **Total de Transações**
- **Busca Rápida:** Campo de busca em tempo real para filtrar transações por nome do pagador, título, tipo de transação (PIX, TED, Boleto, etc.), documento ou `txId`.
- **Tabela de Transações Enriquecida:**
  - Data e hora da inclusão
  - Tipo de transação com badges coloridos (Crédito / Débito)
  - Título, descrição e número de documento
  - Detalhes do pagador (Nome, CPF/CNPJ, TxID, Descrição Pix)
  - Valor formatado em moeda brasileira (R$)

### 3. Integração com o Backend e Correções
- Chamada AJAX para `../inter/endpoint.php?action=consultar_extrato_completo`.
- Exportação em PDF com extração e decodificação automática do campo `pdf` em Base64 retornado pelo Inter no endpoint `../inter/endpoint.php?action=exportar_extrato_pdf&download=1`.
- Tratamento de data final (`dataFim`) no mês corrente para não ultrapassar a data atual, evitando rejeições da API do Inter.
- Disponibilização da função `escapeHtml` no escopo global da página (anteriormente restrita à condicional de modo clínico/vet).
