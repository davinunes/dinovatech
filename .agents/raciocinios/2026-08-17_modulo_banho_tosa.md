# Raciocínio Analítico - Arquitetura e Planejamento do Módulo Banho e Tosa

**Data**: 17/08/2026  
**Contexto**: Criação do novo módulo de Banho e Tosa no DinoVet - Atualização com feedback do usuário.

## 1. Análise de Requisitos e Novos Recursos Incorporados
- **Ficha de Preferências do Pet**: Armazenamento fixo no cadastro do Pet (`preferencias_banho`, `porte`, `tipo_pelagem`), propagando tags operacionais visíveis no Kanban, no Modo TV e no agendamento.
- **Multiplicador de Tempo (Porte/Pelagem)**: Ajuste dinâmico de duração do serviço na grade de agendamento, evitando atrasos em cães de grande porte ou pelagem densa/longa.
- **Check-in Fotográfico Opcional**: Parametrização via `ConfiguracoesEmissor` (`banho_checkin_foto_ativo`) para permitir ou dispensar o registro com fotos no momento do recebimento do pet.
- **Notificações Multicanal (WhatsApp & Gmail)**:
  - Integração de mensageria rápida com WhatsApp (link direto pré-preenchido).
  - Envio de e-mail ao tutor via integração configurada do Gmail no sistema.
- **Google Calendar Sync com Convidado**:
  - Garantir que o agendamento de Banho & Tosa crie o evento na agenda do profissional e insira o tutor como participante (`attendees`) caso possua e-mail ou `google_calendar_id`.

## 2. Decisões Arquiteturais e de Banco de Dados
- Manter o desacoplamento das agendas clínicas e de banho e tosa via flag `tipo_agenda` em `Agendamentos`.
- Implementar modelo auditável de consumo de pacotes (`ClientePacoteSaldos`, `ClientePacoteConsumo`) e tabela de fotos do check-in vinculada à fila de produção (`BanhoCheckinFotos`).
