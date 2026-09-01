# Plano de Implementação: Importar e Vincular NFS-e Existente à Fatura

Permitir que o usuário importe ou vincule uma Nota Fiscal de Serviço (NFS-e) já existente na prefeitura/ISS DF diretamente a uma fatura no sistema, resolvendo descompassos de RPS e falhas síncronas de retorno.

## Contexto e Esclarecimento de Dúvida

> **Sobre o campo de Numeração no Sistema:**
> O campo de configuração do sistema (`ultimo_rps_producao`) deve conter o **ÚLTIMO NÚMERO JÁ UTILIZADO / EXISTENTE NO ISS**.
> - Se a última nota gerada no ISS possui **RPS 60** (NFS-e 53), configure o sistema com **60**.
> - Quando o sistema for emitir a próxima nota automaticamente, ele calculará `60 + 1 = 61`.

---

## Modos da Funcionalidade

A funcionalidade terá dois modos no mesmo modal:
1. **Consulta Automática na API do ISS DF**: Você informa apenas o *Número da NFS-e* (ou *Número do RPS*) e o sistema consulta a prefeitura, obtém o XML completo, Código de Verificação, Data e Link do PDF automaticamente.
2. **Vínculo Manual Direto (Fallback)**: Caso o WebService do ISS DF esteja offline ou instável, você poderá preencher manualmente o Número da NFS-e, Código de Verificação e RPS para marcar a fatura como concluída imediatamente.

---

## Arquivos e Modificações

### Backend (`dinovatech/app.php` & `nfse_test/api.php`)

#### `dinovatech/app.php`
- Criar a action `consultar_e_vincular_nfse`:
  - Recebe `id_fatura`, `tipo_busca` ('numero_nota' ou 'numero_rps'), `numero_busca`, `serie_rps`.
  - Dispara a consulta SOAP no ISS DF (`ConsultarNfseServicoPrestado` ou `ConsultarNfsePorRps`).
  - Faz o parse da resposta XML para extrair `<Numero>`, `<CodigoVerificacao>`, `<DataEmissao>`, `<IdentificacaoRps>`, `<ValoresNfse>`.
  - Dispara a busca complementar da URL do PDF (`ConsultarUrlNfse`).
  - Insere o registro em `NfseEmissoes` com `status = 'concluido'`.
  - Atualiza a fatura: `possui_nfse = 1` e `data_emissao_nfse = ...`.
  - Atualiza `ConfiguracoesEmissor.ultimo_rps_producao` se o RPS importado for maior que o atual.
- Criar a action `vincular_nfse_manual`:
  - Recebe `id_fatura`, `numero_nota`, `codigo_verificacao`, `numero_rps`, `serie_rps`, `url_pdf`.
  - Cria o registro em `NfseEmissoes` com `status = 'concluido'` e atualiza a fatura.

---

### Frontend (`dinovatech/fatura_view.php`)

#### `dinovatech/fatura_view.php`
- Adicionar botão **"Importar / Vincular NFS-e"** no card de Nota Fiscal quando a fatura não possuir nota ativa autorizada.
- Adicionar o Modal `modalImportarNfse`:
  - Aba 1: **Consultar na Prefeitura** (Campo para Nº da NFS-e ou Nº do RPS + Botão "Buscar e Vincular").
  - Aba 2: **Vínculo Manual** (Campos: Nº da NFS-e, Código de Verificação, RPS, Série, Link do PDF).
- Adicionar funções JavaScript para submissão AJAX e atualização visual da página.
