# Walkthrough: Sincronização de Sequência de RPS e Auto-Recuperação na Emissão de NFS-e

Implementamos a funcionalidade de **Sincronização de RPS com o ISS DF** nas configurações fiscais e a **Blindagem/Auto-recuperação com Double-Check** durante a emissão de notas.

---

## 🛠️ O que foi implementado

### 1. Botão "Sincronizar RPS com ISS DF" ([config_fiscal.php](file:///e:/DEV/dinovatech/dinovatech/config_fiscal.php))
- Localizado na aba **Geral / Numeração de RPS**, permite com um único clique consultar a base do ISS DF.
- O backend (`app.php -> sincronizar_rps_iss`) combina:
  - Consulta ao método `ConsultarRpsDisponivel` (próximo RPS livre na prefeitura).
  - Consulta ao método `ConsultarNfseServicoPrestado` (notas emitidas recentemente com seus respectivos RPSs).
- Atualiza e preenche o campo `ultimo_rps_producao` automaticamente.

### 2. Auto-recuperação / Double-Check na Emissão ([app.php](file:///e:/DEV/dinovatech/dinovatech/app.php))
- Quando o sistema dispara a emissão de uma nota (`gerar_nfse`):
  - Se a resposta síncrona não contiver `<CompNfse>` (por timeout, instabilidade da prefeitura, erro genérico ou *"RPS já informado"*), o sistema executa um **double-check automático** imediato consultando o RPS enviado (`ConsultarNfsePorRps`).
  - Se a consulta confirmar que a nota foi autorizada no ISS, o sistema adota a nota como **Sucesso**, captura o número da NFS-e, código de verificação, link do PDF, atualiza a fatura e incrementa o sequencial de RPS, evitando falsos erros e descompassos futuros.
  - Se a consulta confirmar que a nota realmente não foi gerada, o erro é apresentado ao usuário normalmente.

---

## 🔍 Como Testar

1. **Sincronizar Sequência de RPS:**
   - Acesse **Configurações Fiscais** (`config_fiscal.php`).
   - No quadro *Numeração de RPS*, clique em **"Sincronizar RPS com ISS DF"**.
   - O sistema consultará a prefeitura e atualizará o campo para o último número já utilizado (ex: `60`).

2. **Emissão com Auto-Recuperação:**
   - Ao emitir uma NFS-e a partir de qualquer fatura, se a prefeitura demorar a responder ou retornar erro transitório, o sistema validará automaticamente se a nota foi gravada antes de emitir qualquer alerta de falha.
