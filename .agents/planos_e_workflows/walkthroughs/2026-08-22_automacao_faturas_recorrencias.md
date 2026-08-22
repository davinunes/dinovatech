# Walkthrough: Automação de Faturas Recorrentes, Campo Dia de Vencimento e Data da NFSe

**Data**: 2026-08-22  
**Autor**: Antigravity Assistant  
**Status**: Concluído / Pronto para Deploy  

---

## 1. O que foi Implementado

### 1.1. Banco de Dados & Migrations
- **Arquivo Criado**: `database/migrations/20260822_0001_cron_recorrencias_and_faturas_fields.sql`
  - **Tabela `CronLogs`**: Histórico detalhado de execuções das automações (`id_cron_log`, `data_execucao`, `tipo_tarefa`, `status`, `faturas_geradas`, `valor_total_gerado`, `detalhes_json`, `origem`).
  - **Coluna `dia_vencimento` em `Recorrencias`**: Armazena o dia do mês (1 a 31) em que a fatura deve vencer.
  - **Coluna `data_emissao_nfse` em `Faturas`**: Armazena a data/hora de autorização da NFS-e.

### 1.2. Helper de Negócio (`CronRecorrenciasHelper.php`)
- **Arquivo Criado**: `dinovatech/helpers/CronRecorrenciasHelper.php`
  - Varre os contratos ativos no mês de competência que ainda não geraram fatura (`ultima_fatura_gerada_mes_ano != MM/YYYY`).
  - **Geração 1-para-1**: Cria uma fatura separada para cada contrato individual.
  - **Cálculo de Vencimento**: Utiliza o `dia_vencimento` do contrato (ou o dia da data de início se não preenchido), com ajuste automático para o último dia do mês quando necessário.
  - Insere o item na tabela `ItensFatura` herdando os dados fiscais do contrato.
  - Atualiza `ultima_fatura_gerada_mes_ano` no contrato para garantir **idempotência total (sem faturas duplicadas)**.
  - Registra o log consolidado na tabela `CronLogs`.

### 1.3. Ponto de Entrada do Cron (`gerar_faturas_recorrencias.php`)
- **Arquivo Criado**: `dinovatech/cron/gerar_faturas_recorrencias.php`
  - Executável diretamente via terminal Linux (`php /var/www/html/dinovatech/cron/gerar_faturas_recorrencias.php`).
  - Suporta também chamada via WebCron autenticada por token de segurança.

### 1.4. Otimização da Integração ContaDev (`ContaDevHelper.php`)
- **Arquivo Modificado**: `dinovatech/helpers/ContaDevHelper.php`
  - O ContaDev agora consome `Faturas.data_emissao_nfse` diretamente, dispensando a leitura/regex do XML e acelerando a sincronização (mantendo fallback no XML para faturas antigas).

### 1.5. Atualização da API e Emissão de NFS-e (`app.php`)
- **Arquivo Modificado**: `dinovatech/app.php`
  - Suporte a `dia_vencimento` nas actions `criar_recorrencia` e `editar_recorrencia`.
  - Atualização automática de `data_emissao_nfse = NOW(), possui_nfse = 1` ao autorizar uma nota fiscal.
  - Nova action `executar_cron_recorrencias_manual` para disparo manual pelo painel.

### 1.6. Telas de Contratos (`contrato_form.php` e `contratos.php`)
- **Arquivos Modificados**:
  - `dinovatech/contrato_form.php`: Campo **"Dia do Vencimento"** (1 a 31) adicionado na criação e edição do contrato.
  - `dinovatech/contratos.php`:
    - Coluna **Vencimento** adicionada na listagem de contratos ativos ("Dia X").
    - Botão **"Gerar Faturas do Mês"** adicionado no topo para permitir disparo e validação instantânea com relatório de faturas criadas.

---

## 2. Como Configurar e Validar no Servidor

### 2.1. Executar a Migration no MariaDB
Execute o script SQL no banco:
```sql
-- database/migrations/20260822_0001_cron_recorrencias_and_faturas_fields.sql
```

### 2.2. Configurar o Crontab no Servidor (Ubuntu / Docker)
No terminal da VPS da Oracle Cloud, adicione a entrada no cron (`crontab -e`):
```bash
# Executa todos os dias às 00:05 da madrugada
5 0 * * * docker exec homepage-php php /var/www/html/dinovatech/cron/gerar_faturas_recorrencias.php >> /var/log/dinovatech_cron.log 2>&1
```

### 2.3. Teste Manual Imediato
1. Acesse o menu **Contratos / Recorrência**.
2. Clique no botão verde **"Gerar Faturas do Mês"**.
3. O sistema processará e exibirá a lista das faturas geradas com seus respectivos clientes, valores e datas de vencimento.
4. Se clicar novamente, ele informará que nenhuma fatura está pendente (garantindo que não haverá duplicidades).
