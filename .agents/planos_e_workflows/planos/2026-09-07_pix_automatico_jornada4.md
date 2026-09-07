# Plano de Implementação: Pix Automático Banco Inter (Jornada 4 & Gestão de Ciclo de Vida)

**Data:** 2026-09-07  
**Status:** Aguardando Aprovação  

## 1. Objetivos
Implementar a integração com o Banco Inter para o fluxo da **Jornada 4 do Pix Automático**, incluindo:
- Geração da proposta unificada (Passos 1 a 4) com QR Code combinado;
- Verificação ativa (`GET /rec/{idRec}`) e passiva (Webhook `POST /inter/webhook_rec.php`);
- Geração mensal de cobranças recorrentes (`POST /cobr`) no Cron;
- Cancelamento de contrato de recorrência (`PATCH /rec/{idRec}`);
- Cancelamento de cobrança individual de fatura (`PATCH /cobr/{txid}`);
- Rotina preventiva de higienização de contratos cancelados no Cron diário;
- Badge e identificador de Pix Automático na listagem de contratos da Central do Cliente;
- Card dedicado e botões operacionais no Painel Admin (`fatura_view.php` e `contrato_form.php`).

## 2. Arquivos Envolvidos
- `database/migrations/20260907_0001_create_pix_recorrencias_tables.sql` [NOVO]
- `inter/api.php` [MODIFICAR]
- `inter/config.php` [MODIFICAR]
- `inter/endpoint.php` [MODIFICAR]
- `inter/PixAutomaticoService.php` [NOVO]
- `inter/webhook_rec.php` [NOVO]
- `dinovatech/helpers/CronRecorrenciasHelper.php` [MODIFICAR]
- `cliente/index.php` [MODIFICAR]
- `cliente/fatura.php` [MODIFICAR]
- `dinovatech/app.php` [MODIFICAR]
- `dinovatech/fatura_view.php` [MODIFICAR]
- `dinovatech/contrato_form.php` [MODIFICAR]
