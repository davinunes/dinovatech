# Raciocínio Diagnóstico: Recurso de Contrato como Cortesia / Teste (Deploy SaaS sem Faturamento)

**Data**: 2026-10-23  
**Contexto**: Implementação da funcionalidade de marcar Contratos de clientes como Cortesia ou Teste (Trial), evitando a geração de faturas (cron e manual) e possibilitando o vínculo e deploy de instâncias SaaS dedicadas no K3s sem cobrança indevida.

---

## 1. Análise da Demanda e Problema
- O usuário precisa vincular um contrato de cliente para efetuar o deploy de instâncias SaaS no cluster Kubernetes (K3s).
- Atualmente, todo contrato em `Recorrencias` entra na rotina de faturamento mensal (`CronRecorrenciasHelper.php` e `app.php?action=incorporar_recorrencias_na_fatura`).
- A validação de criação/edição em `app.php` (`vincular_recorrencia` e `editar_recorrencia`) exige `valor_sugerido_recorrencia > 0`, rejeitando valores zerados.
- Não há discriminação de contratos de cortesia, testes ou parcerias na listagem (`contratos.php`) nem na central SaaS (`saas_tenants.php`), distorcendo métricas como MRR.

## 2. Decisão de Arquitetura e Modelagem de Dados
- Optou-se por criar uma coluna na tabela `Recorrencias`:
  - `tipo_cobranca` ENUM('COMERCIAL', 'CORTESIA', 'TESTE') NOT NULL DEFAULT 'COMERCIAL'
  - Opcionalmente `motivo_cortesia` VARCHAR(255) NULL para justificar a concessão da cortesia ou teste.
- Benefícios dessa abordagem:
  1. **Comercial**: Comportamento padrão de faturamento e métricas.
  2. **Cortesia**: 100% isento, sem faturas geradas, valor pode ser 0,00 ou valor de tabela de referência, mantendo a instância SaaS operacional.
  3. **Teste**: Permite definir período de teste (usando `data_fim_cobranca` ou campo de expiração do teste), sem gerar fatura durante a avaliação.

## 3. Componentes Afetados
1. **Banco de Dados**:
   - `database/migrations/20261023_0002_add_tipo_cobranca_to_recorrencias.sql`
2. **Helpers e Cron**:
   - `dinovatech/helpers/CronRecorrenciasHelper.php`: Filtrar `tipo_cobranca = 'COMERCIAL'`.
3. **Backend API**:
   - `dinovatech/app.php`:
     - `vincular_recorrencia`: Aceitar `tipo_cobranca` e permitir valor >= 0 se for Cortesia/Teste.
     - `editar_recorrencia`: Mesma lógica de validação e persistência.
     - `incorporar_recorrencias_na_fatura`: Não permitir incorporar contratos de Cortesia ou Teste.
4. **Interface de Contratos**:
   - `dinovatech/contrato_form.php`: Seletores visuais de modalidade (Comercial, Cortesia, Teste), regras de UI para valor zero ou sugerido, e desativação contextual do Pix Automático.
   - `dinovatech/contratos.php`: Badges visuais claros ("🎁 Cortesia", "🧪 Teste / Trial"), filtros rápidos e indicação de isenção.
5. **Central SaaS**:
   - `dinovatech/saas_tenants.php`: Cálculo do MRR ignorando cortesias e testes; badges informativos na tabela de tenants.

## 4. Segurança e Integridade
- Queries SQL preparadas / devidamente sanitizadas com `mysqli_real_escape_string`.
- Sanitização de campos com `htmlspecialchars`.
- Migração idempotente via `INFORMATION_SCHEMA.COLUMNS`.
