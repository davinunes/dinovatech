# Walkthrough: Travamento Histórico Fiscal e Preservação de Alíquotas de Faturas Pagas

Implementamos o **Travamento Histórico Fiscal (Immutability Lock)** no [AppHelper.php](file:///e:/DEV/dinovatech/dinovatech/helpers/AppHelper.php) para garantir que alterações cadastrais em serviços (como a mudança de 2,01% para 2,00%) **nunca afetem nem alterem os totais, retenções e saldos de faturas/notas fiscais já emitidas ou liquidadas no passado**.

---

## 🛠️ O que foi corrigido e implementado

### O Diagnóstico
- Quando a fatura possui retenção de ISS (`iss_retido = 1`), o valor líquido da fatura é calculado subtraindo a retenção (`Valor Serviços - Retenção ISS`).
- Anteriormente, os métodos `calculateFaturaTotals` e `calculateNfseData` consultavam a alíquota diretamente da tabela mestre `Servicos` em tempo real.
- Ao alterar o serviço de `2.01%` para `2.00%`, a retenção histórica (ex: R$ 4,02 em uma fatura de R$ 200,00) passava a ser recalculada como R$ 4,00, gerando uma diferença residual líquida de R$ 0,02 e marcando a fatura com saldo a receber indevido.

### A Solução Implementada ([AppHelper.php](file:///e:/DEV/dinovatech/dinovatech/helpers/AppHelper.php))
- **Prioridade 1 (Snapshot Histórico da NFS-e):** O sistema agora verifica se a fatura já possui registro emitido/concluído em `NfseEmissoes`. Se possuir, **congela e utiliza a alíquota histórica e a retenção exatas gravadas no momento da emissão** (ex: 2,01%).
- **Prioridade 2 (Sobrescrita do Contrato/Recorrência):** Se não houver nota emitida, verifica se o contrato possui parâmetros específicos.
- **Prioridade 3 (Cadastro Atual do Serviço):** Apenas para novas faturas em aberto sem emissão.

---

## 🔍 Resultado
- Faturas emitidas anteriormente com **2,01%** mantêm o valor líquido e a retenção originais, ficando com **Saldo Devedor: R$ 0,00**.
- Novas faturas geradas a partir de agora adotam a alíquota oficial de **2,00%**.
