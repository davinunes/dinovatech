# Raciocínio Analítico - Arquitetura e Planejamento do Módulo Banho e Tosa

**Data**: 17/08/2026  
**Contexto**: Criação do novo módulo de Banho e Tosa no DinoVet.

## 1. Análise de Requisitos e Restrições
- **Modo de Operação**: O módulo deve ser visível e acessível exclusivamente quando `AppHelper::isVetMode()` for verdadeiro.
- **Parametrização de Serviços**: 
  - Os serviços precisam conter sinalizador de disponibilidade em Clínica vs Banho e Tosa.
  - Necessidade de campo para duração padrão (em minutos), essencial para cálculo automático dos intervalos de agendamento na agenda.
  - Adição de ícone ou imagem para exibição na vitrine e seleção no mobile.
- **Modelo de Pacotes (Combos)**:
  - Pacote não tem validade temporal mandatória, apenas saldo de serviços contratados.
  - Pode ou não ser recorrente. Se recorrente, gera registro na tabela `Recorrencias` para que o botão "Incorporar Recorrências" gere faturas periódicas e renove os saldos de serviços.
- **Separação de Agendas**:
  - Se um colaborador também atuar como veterinário, sua agenda de Banho & Tosa não pode se misturar nem gerar conflitos visuais com sua agenda clínica.
  - Solução: identificador `tipo_agenda` ('clinica' vs 'banho_tosa') na tabela `Agendamentos`.
- **Linha de Produção & Modo TV**:
  - Painel operacional em formato Kanban com cards dinâmicos dos pets nas etapas do banho/tosa.
  - Modo TV com auto-refresh/polling sem reload de tela e layout de alto contraste.
- **Central do Cliente (Mobile-First)**:
  - Identificação inteligente de créditos de pacotes disponíveis para o cliente.
  - Consumo transparente de crédito com fallback para valor avulso caso o saldo seja zero.
- **Dashboard Executiva**:
  - Monitoramento de pacotes ativos vs utilizados e histórico de consumo.

## 2. Decisões Arquiteturais e de Banco de Dados
- Criar modelo desacoplado de saldo de pacotes (`ClientePacotes`, `ClientePacoteSaldos`, `ClientePacoteConsumo`) para permitir auditoria precisa de qual pet usou qual serviço e em qual horário.
- Integrar com o motor de recorrências existente (`Recorrencias`) mantendo compatibilidade total com o módulo financeiro.
