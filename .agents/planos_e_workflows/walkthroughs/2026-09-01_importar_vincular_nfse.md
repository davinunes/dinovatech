# Walkthrough: Importar e Vincular NFS-e Existente à Fatura

Implementamos a funcionalidade de **Importar / Vincular NFS-e Existente** na tela de visualização da fatura ([fatura_view.php](file:///e:/DEV/dinovatech/dinovatech/fatura_view.php)), com suporte a consulta direta via WebService do ISS DF ou inserção manual (fallback).

---

## 🛠️ Alterações Efetuadas

### 1. Backend (`dinovatech/app.php`)
- Criada a action `consultar_e_vincular_nfse`:
  - Realiza a consulta SOAP autenticada por certificado digital via `ConsultarNfseServicoPrestado` (pelo Número da NFS-e) ou `ConsultarNfsePorRps` (pelo Número do RPS).
  - Extrai automaticamente os dados fiscais, XML assinado de retorno e o link de visualização do PDF (`ConsultarUrlNfse`).
  - Cria o registro em `NfseEmissoes` com `status = 'concluido'`.
  - Atualiza a fatura com `possui_nfse = 1` e a data de emissão.
  - Atualiza o sequencial `ultimo_rps_producao` caso o RPS vinculado seja superior ao salvo.
- Criada a action `vincular_nfse_manual`:
  - Permite informar o Número da Nota, Código de Verificação, RPS e link do PDF diretamente caso a prefeitura esteja instável/offline.

### 2. Frontend (`dinovatech/fatura_view.php`)
- Adicionado o botão **"Importar / Vincular NFS-e"** no painel lateral de Nota Fiscal.
- Adicionado o modal interativo `#modalImportarNfse` com duas abas:
  - **Aba 1 (Consultar no ISS DF):** Seleção entre Número da Nota ou RPS + Consulta direta na API da prefeitura.
  - **Aba 2 (Vínculo Manual):** Campos diretos para preenchimento de contingência.
- Implementado tratamento AJAX com feedback visual (toasts, alerts e loading states).

---

## 🔍 Como Testar

1. Acesse a fatura desejada (ex: `fatura_view.php?id=86`).
2. No card **Nota Fiscal (NFS-e)**, clique em **"Importar / Vincular NFS-e"**.
3. **Opção 1 (Consulta Automática):**
   - Selecione *Número da NFS-e* e informe `53` (ou selecione *Número do RPS* e informe `60`).
   - Clique em **"Consultar e Vincular"**. O sistema consultará o ISS DF, salvará a nota e recarregará a página já com os botões de XML e PDF ativos.
4. **Opção 2 (Manual):**
   - Caso o ISS esteja instável, mude para a aba *Vínculo Manual*, preencha os dados e clique em **"Salvar e Vincular"**.
