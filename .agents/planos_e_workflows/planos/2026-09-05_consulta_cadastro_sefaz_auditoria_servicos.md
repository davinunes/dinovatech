# Plano de Implementação: Consulta de Dados Cadastrais SEFAZ-DF & Auditoria Fiscal de Serviços

Implementação do endpoint oficial `ConsultarDadosCadastrais` do Novo Padrão Nacional da NFS-e (SEFAZ-DF / Nota Control v1.01), integração nos módulos do sistema, e recurso de auditoria automática que confronta os serviços cadastrados no Dinovatech com as atividades e alíquotas fiscais ativas da empresa no DF.

---

## 1. Contexto e Motivação

O teste bem-sucedido no SoapUI confirmou que o endpoint `ConsultarDadosCadastrais` está **100% operacional** em ambiente de produção da SEFAZ-DF, retornando:
- Dados cadastrais oficiais da empresa (Razão Social atualizada `LD TECNOLOGIA DA INFORMACAO LTDA`, Endereço, Telefones, E-mail fiscal, Status Ativo, Enquadramento no Simples Nacional e MEI);
- Rol completo de atividades tributárias municipais (`cTribMun`), alíquotas vigentes (`pAliq` de 2,00% e 5,00%), com datas de início e fim de vigência;
- Tributações de ISSQN permitidas (`1` - Tributável, `4` - Não incidência).

Ao integrar essa consulta no Dinovatech, ganhamos:
1. **Validação e Sincronização Cadastral:** O formulário de Configurações Fiscais pode confrontar ou sincronizar automaticamente os dados cadastrais da empresa com a base oficial da SEFAZ-DF;
2. **Auditoria Preventiva de Serviços:** O sistema pode verificar cada item cadastrado na tabela `Servicos` e alertar imediatamente se houver divergência de alíquota (ex.: serviço configurado a 5% quando a atividade ativa no DF exige 2%, ou atividade com vigência encerrada na SEFAZ).
3. **Ferramenta de Diagnóstico no Lab:** No laboratório `nfse_nacional_test`, a consulta pode ser disparada diretamente para inspeção dos envelopes SOAP e das atividades.

---

## 2. Componentes e Arquivos a Serem Modificados/Criados

```
dinovatech/
├── modules/Fiscal/
│   ├── Builders/
│   │   └── [NEW] ConsultarDadosCadastraisXmlBuilder.php
│   ├── DTOs/
│   │   └── [NEW] CadastroResult.php
│   ├── Parsers/
│   │   └── [MODIFY] NacionalResponseParser.php (adicionar parseConsultaCadastro)
│   ├── Contracts/
│   │   └── [MODIFY] NfseProviderInterface.php (adicionar consultarDadosCadastrais)
│   ├── Providers/
│   │   ├── [MODIFY] NacionalProvider.php (implementar consultarDadosCadastrais)
│   │   └── [MODIFY] LegacyAbrasfProvider.php (método stub de fallback)
│   └── Services/
│       └── [MODIFY] NfseService.php (método consultarCadastroEAuditarServicos)
├── app.php (novo case AJAX 'auditar_cadastro_sefaz')
├── config_fiscal.php (botão de consulta/auditoria + modal de resultados)
└── nfse_nacional_test/
    ├── api.php (nova action 'consultar_cadastro')
    └── index.php (novo botão e card de exibição das atividades)
```

---

## 3. Detalhamento Técnico das Etapas

### Etapa 1: DTO e XML Builder (`Dinovatech\Modules\Fiscal`)
- **`ConsultarDadosCadastraisXmlBuilder.php`:**
  - Monta o XML enxuto conforme testado:
    ```xml
    <ConsultarDadosCadastraisEnvio xmlns="http://www.sped.fazenda.gov.br/nfse" xmlns:ns2="http://www.w3.org/2000/09/xmldsig#">
        <Prestador>
            <CNPJ>{cnpj}</CNPJ>
            <IM>{im}</IM>
        </Prestador>
    </ConsultarDadosCadastraisEnvio>
    ```
- **`CadastroResult.php`:**
  - DTO estruturado contendo:
    - `success`, `message`, `erros`
    - `cnpj`, `im`, `statusCadastro`, `razaoSocial`, `nomeFantasia`
    - `endereco` (logradouro, bairro, cMun, uf, cep)
    - `telefone`, `email`
    - `optanteSimples`, `optanteMei`, `emiteNfse`
    - `tributacoesPermitidas` (array de inteiros)
    - `atividades` (array de arrays contendo `codigo`, `descricao`, `aliquota`, `ativa`, `dataInicial`, `dataFinal`)
    - `atividadesVigentes` (filtro rápido apenas das que não expiraram)

### Etapa 2: Parser e Provedor Nacional
- **`NacionalResponseParser::parseConsultaCadastro(string $xmlResponse): CadastroResult`:**
  - Extrai os nós de `<Cadastro>` e faz o loop pelas tags `<Atividade>`;
  - Identifica se a atividade está vigente checando se `<DataFinal>` existe e se é menor que a data atual.
- **`NacionalProvider::consultarDadosCadastrais(?string $cnpj = null, ?string $im = null): CadastroResult`:**
  - Executa a chamada SOAP `ConsultarDadosCadastrais` com mTLS (Certificado A1) e versão `1.01`.
- **`LegacyAbrasfProvider.php`:**
  - Implementa fallback compatível.

### Etapa 3: Serviço de Auditoria Fiscal de Serviços (`NfseService.php`)
- Método: `consultarCadastroEAuditarServicos()`:
  1. Chama `consultarDadosCadastrais()` na SEFAZ-DF;
  2. Busca todos os serviços da tabela `Servicos` (`SELECT * FROM Servicos WHERE ativo = 1`);
  3. Para cada serviço cadastrado, compara:
     - **Existência no cadastro fiscal:** Correlaciona `codigo_tributacao_municipio`, `item_lista_servico` (ex: `01.06` -> `106`) ou `codigo_tributacao_nacional` com as atividades autorizadas da empresa;
     - **Vigência:** Avisa se o serviço aponta para uma atividade cuja vigência encerrou (ex.: as atividades de 5% que expiraram em 09/05/2026);
     - **Alíquota ISS:** Confronta a `aliquota_iss` do serviço com o `pAliq` retornado pela SEFAZ; se divergir, gera alerta com a recomendação de correção;
     - **Enquadramento ISSQN:** Checa se o campo `tributacao_issqn` é suportado (ex: `1` ou `4`).
  4. Retorna a lista de divergências e o comparativo cadastral da empresa (Razão Social, Endereço).

### Etapa 4: Backend AJAX (`dinovatech/app.php`)
- Criar a rota AJAX `action: 'auditar_cadastro_sefaz'`:
  - Instancia `NfseService` e executa `consultarCadastroEAuditarServicos()`;
  - Retorna JSON estruturado com status, dados da empresa, alertas dos serviços e lista de atividades oficiais para a interface.

### Etapa 5: Interface nas Configurações Fiscais (`config_fiscal.php`)
- Adicionar botão com ícone destacado no topo do card fiscal:
  - **`🔍 Consultar / Auditar na SEFAZ-DF`**
- Modal/Painel interativo de exibição com:
  - **Card 1: Status Cadastral da Empresa** (Razão Social oficial, Status Ativo, Simples Nacional ativo);
  - **Card 2: Auditoria dos Serviços Cadastrados**:
    - Alerta verde se todos os serviços estiverem com alíquotas corretas e atividades vigentes;
    - Alerta vermelho/amarelo listando individualmente os serviços com problemas (ex: *"Serviço X está com alíquota 5,00%, mas a SEFAZ-DF exige 2,00%"*), incluindo link direto para editar o serviço;
  - **Card 3: Tabela das Atividades Autorizadas no DF** com badges de alíquotas (2% / 5%) e status de vigência.

### Etapa 6: Laboratório Interativo (`dinovatech/nfse_nacional_test/`)
- Adicionar botão **"Consultar Cadastro SEFAZ"** no painel de ações do Lab;
- Implementar `action=consultar_cadastro` no backend `api.php`;
- Exibir os envelopes de envio/retorno e a lista de atividades formatada no visualizador do laboratório.

---

## 4. Plano de Verificação

### Verificação Automatizada e Inspeção de Código
- Validar a correta sintaxe PHP de todos os novos arquivos e modificações.
- Garantir que a estrutura XML do `ConsultarDadosCadastraisEnvio` siga exatamente o padrão do SoapUI.

### Verificação Prática
1. **No Laboratório (`nfse_nacional_test`):**
   - Disparar a consulta cadastral com o certificado digital em produção e verificar se o retorno traz as 18 atividades e dados de `LD TECNOLOGIA DA INFORMACAO LTDA`.
2. **Nas Configurações Fiscais (`config_fiscal.php`):**
   - Clicar no novo botão de auditoria;
   - Validar se o modal abre exibindo os dados da empresa e a checagem dos serviços cadastrados;
   - Testar o comportamento com serviços em conformidade e simular serviço com alíquota divergente para validar o alerta.
