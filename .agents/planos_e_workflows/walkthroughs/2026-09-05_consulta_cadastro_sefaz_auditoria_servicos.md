# Walkthrough: Consulta de Dados Cadastrais SEFAZ-DF & Auditoria Fiscal de Serviços

Implementação completa do método oficial `ConsultarDadosCadastrais` do Novo Padrão Nacional da NFS-e (SEFAZ-DF / Nota Control v1.01), integração nos serviços fiscais do Dinovatech e recurso de auditoria automática que confronta serviços cadastrados com as atividades autorizadas no DF.

---

## 1. O que foi feito

### 1.1. Camada de Integração Fiscal (`Dinovatech\Modules\Fiscal`)
- **[NEW] `CadastroResult.php`:** DTO completo com dados cadastrais oficiais da empresa (Razão Social, Nome Fantasia, CNPJ, IM, Endereço, Telefones, E-mail, Simples Nacional, MEI, NFS-e autorizada, Tributações permitidas e lista de atividades com alíquotas e vigências).
- **[NEW] `ConsultarDadosCadastraisXmlBuilder.php`:** Builder para o XML `<ConsultarDadosCadastraisEnvio>` validado no SoapUI.
- **[MODIFY] `NacionalResponseParser.php`:** Método `parseConsultaCadastro()` para interpretar a resposta SOAP, extrair os dados cadastrais da empresa e o rol de atividades com alíquotas (`pAliq`) e vigências (`DataInicial` / `DataFinal`).
- **[MODIFY] `NfseProviderInterface.php`:** Adicionado contrato para `consultarDadosCadastrais(?string $cnpj = null, ?string $im = null): CadastroResult`.
- **[MODIFY] `NacionalProvider.php`:** Implementação da chamada SOAP `ConsultarDadosCadastrais` via mTLS (Certificado A1) na versão `1.01` em produção.
- **[MODIFY] `LegacyAbrasfProvider.php`:** Stub de compatibilidade.

### 1.2. Motor de Auditoria Fiscal de Serviços
- **[MODIFY] `NfseService.php`:**
  - Método `consultarCadastroEAuditarServicos()`:
    1. Consulta os dados cadastrais diretamente na SEFAZ-DF via `NacionalProvider`;
    2. Compara os dados da empresa (`razao_social`, `endereco`) com `ConfiguracoesEmissor`;
    3. Percorre todos os itens ativos da tabela `Servicos` e correlaciona com as atividades fiscais do DF por `cTribMun`, `item_lista_servico` ou descrição;
    4. **Detecta Alíquota Divergente:** Compara a `aliquota_iss` cadastrada no sistema com a alíquota autorizada vigente no DF;
    5. **Detecta Atividades Expiradas:** Identifica se o serviço aponta para atividade cuja vigência encerrou no DF (ex: atividades antigas de 5% expiradas em 09/05/2026);
    6. **Detecta Atividades Não Autorizadas:** Avisa caso o item não conste no rol de atividades da empresa.

### 1.3. Backend AJAX
- **[MODIFY] `app.php`:**
  - Rota AJAX `action: 'auditar_cadastro_sefaz'`, que aciona `NfseService::consultarCadastroEAuditarServicos()` e devolve um JSON estruturado para a interface.

### 1.4. Painel de Configurações Fiscais
- **[MODIFY] `config_fiscal.php`:**
  - Banner destacado dentro da aba de Dados Fiscais com o botão **"Auditar Cadastro na SEFAZ"**;
  - Modal interativo `#modalAuditoriaSefaz`:
    - Exibe dados oficiais da SEFAZ-DF (`LD TECNOLOGIA DA INFORMACAO LTDA`, Endereço, Simples Nacional, IM, etc.) com botão para copiar/aplicar os dados nos campos do formulário se desejado;
    - Exibe banner de auditoria e tabela com cada serviço cadastrado, sua alíquota no sistema vs alíquota na SEFAZ-DF, status visual (Conforme, Alíquota Divergente, Atividade Expirada) e botão direto de "Editar";
    - Exibe a lista completa de atividades autorizadas pela SEFAZ-DF com suas alíquotas e status de vigência.

### 1.5. Laboratório Interativo de Testes
- **[MODIFY] `nfse_nacional_test/api.php`:**
  - Adicionada action `consultar_cadastro` executando a chamada direta ao WebService da SEFAZ-DF.
- **[MODIFY] `nfse_nacional_test/index.php`:**
  - Botão **"6. Consultar Cadastro SEFAZ"** na esteira de ações do Lab;
  - Formatação especial no painel de detalhes com todos os dados cadastrais da empresa e lista de atividades autorizadas.
