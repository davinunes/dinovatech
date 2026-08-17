# Walkthrough - Módulo de Banho & Tosa: Extrato de Pacotes, Vínculo por Pet e Token Gmail

Data: 2026-08-17
Status: Concluído e Atualizado

## 1. Notificação por E-mail Condicional à Integração Gmail
- Em `app.php` (`get_banho_producao_fila`) e `banho_producao.php`, o sistema agora verifica se as credenciais do Gmail (`google_oauth_token` ou `google_oauth_email`) estão configuradas em `ConfiguracoesEmissor`.
- O botão **"E-mail"** no card de pet pronto para retirada na esteira só é exibido se a integração com o Gmail estiver ativa.

## 2. Vínculo de Pacote com Pet Específico (ou Compartilhado)
- **Migração Criada**: `database/migrations/20260817_0003_add_id_pet_to_cliente_pacotes.sql` adicionando a coluna `id_pet` em `ClientePacotes`.
- **Modal de Vínculo (`pacotes.php`)**: Ao selecionar o tutor, os pets cadastrados são carregados dinamicamente, permitindo escolher entre *"Compartilhado entre todos os pets"* ou vincular exclusivamente a um pet específico.
- **Consumo e Saldos**: Ao consultar saldos e efetuar agendamentos/check-ins, o sistema valida a permissão do pet caso o pacote seja exclusivo.

## 3. Extrato de Pacotes (Portal do Tutor & Painel do Clínico)
- **Backend (`get_extrato_pacote`)**: Retorna os detalhes do pacote, saldos de cada serviço com barra de progresso e todo o histórico detalhado de utilizações (`ClientePacoteConsumo`).
- **Portal do Tutor (`cliente/index.php`)**:
  - Cada card de pacote exibe o pet vinculado e o botão **"Ver Extrato Completo"**.
  - Modal com visual moderno e responsivo exibindo o histórico de utilizações com data/hora, serviço e pet atendido.
- **Painel do Clínico (`modules/Vet/pacotes.php`)**:
  - Seção com tabela de contratos ativos e botão **"Ver Extrato"** com modal interativo para conferência de saldo e consumo dos clientes.
