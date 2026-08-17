# Walkthrough - Módulo de Banho e Tosa (DinoVet)

Data: 2026-08-17
Status: Concluído

## 1. Resumo da Entrega
Implementação completa do módulo de **Banho e Tosa / Estética Animal** exclusivo para o Modo Vet (`AppHelper::isVetMode()`), contemplando serviços parametrizados, ficha estética do pet, pacotes/combos com controle de saldo e recorrência, agenda inteligente desacoplada com cálculo de tempo por porte e sincronização Google Calendar com convidados, além de esteira operacional Kanban com Modo TV e notificações via WhatsApp e Gmail.

## 2. Arquivos Modificados e Criados

### Migrações SQL
- `database/migrations/20260817_0001_create_banho_tosa_schema.sql` [NEW]

### Serviços e Pets
- `dinovatech/servico_form.php` [MODIFY] (duracao_minutos, disponivel_clinica, disponivel_banho, icone_servico, imagem_url)
- `dinovatech/servicos.php` [MODIFY] (badges de módulos, ícones, duração)
- `dinovatech/modules/Vet/pet_form.php` [MODIFY] (porte P/M/G/GG, tipo_pelagem, tags de preferências de banho)
- `dinovatech/modules/Vet/pet_detalhes.php` [MODIFY] (exibição de porte, pelagem e card de preferências)

### Pacotes & Combos
- `dinovatech/modules/Vet/pacotes.php` [NEW] (catálogo de pacotes e modal de vínculo ao tutor)
- `dinovatech/modules/Vet/pacote_form.php` [NEW] (criação dinâmica de combos e cálculo de valores)

### Agenda & Sincronização
- `dinovatech/modules/Vet/banho_agenda.php` [NEW] (grade FullCalendar com cálculo inteligente de duração por porte e detecção de créditos de pacotes)
- `dinovatech/modules/Agenda/api.php` [MODIFY] (suporte a tipo_agenda 'banho_tosa', id_cliente_pacote, auto-enqueue na esteira e convidados no Google Calendar)

### Linha de Produção & Notificações
- `dinovatech/modules/Vet/banho_producao.php` [NEW] (Kanban operacional, check-in fotográfico opcional, modo TV com auto-polling de 15s e botões WhatsApp/Gmail)
- `dinovatech/config_fiscal.php` [MODIFY] (flag de configuração `banho_checkin_foto_ativo`)

### Dashboard & Prontuário do Cliente
- `dinovatech/cliente_detalhes.php` [MODIFY] (card com barras de progresso de saldos de pacotes)
- `dinovatech/dashboard.php` [MODIFY] (widget ao vivo da esteira de banho e contador de pacotes ativos)
- `dinovatech/components/sidebar.php` [MODIFY] (links de Linha de Produção, Agenda Banho/Tosa e Pacotes & Combos)
- `dinovatech/app.php` [MODIFY] (ações AJAX de pacotes, saldos, consumo, esteira, fotos e e-mail)
