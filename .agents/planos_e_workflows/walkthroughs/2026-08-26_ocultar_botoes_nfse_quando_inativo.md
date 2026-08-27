# Walkthrough - Ocultação de Botões de Nota Fiscal com Integração Inativa

**Data**: 26/08/2026  
**Objetivo**: Não exibir botões e prévias de geração de Nota Fiscal de Serviço (NFS-e) nas faturas quando a integração / módulo fiscal estiver desativado nas configurações do emissor.

---

## 🛠️ Alterações Realizadas

### 1. `dinovatech/fatura_view.php`
- Identificação da flag `$isFiscalAtivo` a partir da tabela `ConfiguracoesEmissor` (`modulo_fiscal_ativo == 1`).
- **Condicionamento da exibição**:
  - O bloco de NFS-e só é renderizado se o módulo fiscal estiver ativo OU se já existirem notas emitidas anteriormente para consulta/histórico.
  - O botão de **"Gerar NFS-e"** (`#btnGerarNfse`) e o card de prévia fiscal (`#nfsePreviewCard`) agora são restritos estritamente para quando `$isFiscalAtivo` estiver verdadeiro e a nota ainda não tiver sido autorizada.
  - O gatilho de carregamento via AJAX `loadNfsePreview()` no `$(document).ready` só é disparado se `$isFiscalAtivo && !$hasAuthorized`.

### 2. `dinovatech/helpers/AppHelper.php`
- No método `calculateNfseData()`, adicionada validação para verificar `modulo_fiscal_ativo`. Caso esteja desativado, o backend retorna mensagem informativa impedindo geração ou prévia indevida via chamadas diretas de API.

---

## 🧪 Validação
- Verificado que faturas não exibem o botão "Gerar NFS-e" e prévia quando `modulo_fiscal_ativo` está definido como 0 ou desmarcado em `ConfiguracoesEmissor`.
- Histórico de emissões concluídas (XML e PDF) continua acessível para faturas que já possuam NFS-e emitidas no passado.
