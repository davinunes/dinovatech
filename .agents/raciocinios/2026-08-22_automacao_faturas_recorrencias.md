# Raciocínio Diagnóstico e Arquitetura: Automação Diária de Faturas Recorrentes, Campo Dia de Vencimento e Data da NFSe

**Data**: 2026-08-22  
**Autor**: Antigravity Assistant  
**Status**: Análise & Arquitetura Atualizada  

---

## 1. Contexto & Novos Requisitos do Usuário

1. **Geração Individual de Faturas**:
   - Para clientes com múltiplos contratos, gera 1 fatura para cada contrato individualmente (1-to-1).
   - Execução diária pelo cron (00:05), gerando todas as faturas pendentes do mês de competência assim que o mês virar.
2. **Coluna `dia_vencimento` nos Contratos (`Recorrencias`)**:
   - Adicionar campo explícito de dia de vencimento (1 a 31) no formulário de criação/edição de contratos.
   - Permite que o vencimento seja independente da data de criação/início do contrato.
   - Fallback: se `dia_vencimento` não for preenchido (ex: contratos legados), utiliza o dia da `data_inicio_cobranca`.
3. **Coluna `data_emissao_nfse` nas Faturas (`Faturas`)**:
   - Adicionar coluna `data_emissao_nfse DATETIME DEFAULT NULL` na tabela `Faturas`.
   - Preenchida automaticamente quando a NFSe é gerada/concluída.
   - O `ContaDevHelper` passa a utilizar `Faturas.data_emissao_nfse` diretamente para compor o `issuedAt`, mantendo fallback via regex no XML e data de anexo para retrocompatibilidade.

---

## 2. Modelagem do Banco de Dados

### 2.1. Migration `20260822_0001_cron_recorrencias_and_faturas_fields.sql`
1. **Tabela `CronLogs`**:
   - `id_cron_log INT AUTO_INCREMENT PRIMARY KEY`
   - `data_execucao DATETIME DEFAULT CURRENT_TIMESTAMP`
   - `tipo_tarefa VARCHAR(50) NOT NULL` ('faturas_recorrencias')
   - `status ENUM('sucesso','erro','aviso') NOT NULL`
   - `faturas_geradas INT DEFAULT 0`
   - `valor_total_gerado DECIMAL(10,2) DEFAULT 0.00`
   - `detalhes_json LONGTEXT`
   - `origem VARCHAR(20) DEFAULT 'cron'` ('cron_cli', 'web', 'manual')
2. **Coluna `dia_vencimento` na tabela `Recorrencias`**:
   - `dia_vencimento TINYINT DEFAULT NULL COMMENT 'Dia preferencial de vencimento da fatura (1 a 31)'`
3. **Coluna `data_emissao_nfse` na tabela `Faturas`**:
   - `data_emissao_nfse DATETIME DEFAULT NULL COMMENT 'Data/hora de emissão da NFSe'`

---

## 3. Lógica de Negócio do Helper (`CronRecorrenciasHelper.php`)

```php
$dia = !empty($rec['dia_vencimento']) 
    ? (int)$rec['dia_vencimento'] 
    : (int)date('d', strtotime($rec['data_inicio_cobranca']));

$ultimoDiaMes = (int)date('t', strtotime("$ano-$mes-01"));
if ($dia > $ultimoDiaMes) {
    $dia = $ultimoDiaMes;
}
$dataVencimento = sprintf('%04d-%02d-%02d', $ano, $mes, $dia);
```

- Cada contrato gera 1 fatura com `data_emissao = date('Y-m-d')` e `data_vencimento = $dataVencimento`.
- Insere o item na tabela `ItensFatura` com os campos fiscais e valor do contrato.
- Atualiza `valor_total_fatura`.
- Atualiza `ultima_fatura_gerada_mes_ano = '$mesAno'`.
- Registra execução na tabela `CronLogs`.
