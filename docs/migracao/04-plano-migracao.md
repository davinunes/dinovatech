# 04 — Plano Estratégico de Migração em Etapas (ABRASF 2.04 para Padrão Nacional)

Este plano detalha o roteiro para desenvolver a nova integração da NFS-e Nacional no **Dinovatech** em paralelo com o legado, garantindo zero impacto na operação atual.

---

## FASE A — Análise e Diagnóstico
- **Objetivo:** Mapear toda a arquitetura atual (ABRASF 2.04), analisar o manual v1.01, WSDLs, schemas e modelos de XML do novo padrão nacional.
- **Arquivos Envolvidos:**
  - `docs/migracao/01-analise-integracao-atual.md`
  - `docs/migracao/02-analise-novo-padrao.md`
  - `docs/migracao/03-comparacao-abrasf-nacional.md`
  - `doc_issdf/novo_padrao_nacional/`
- **Dependências:** Acesso aos documentos e ao repositório local.
- **Testes Necessários:** Revisão documental cruzada dos campos e schemas.
- **Critérios de Conclusão:** Todas as especificações técnicas, diferenças de tags, endpoints e algoritmos de assinatura identificados e documentados.
- **Riscos:** Nenhum (somente leitura).
- **Status:** **CONCLUÍDA**.

---

## FASE B — Arquitetura e Abstração de Domínio (Contratos e DTOs)
- **Objetivo:** Criar a camada de abstração independente de fornecedor fiscal (`NfseProviderInterface`) e objetos de transferência de dados (DTOs), sem alterar o comportamento do sistema existente.
- **Arquivos Envolvidos:**
  - `dinovatech/modules/Fiscal/Contracts/NfseProviderInterface.php` [NOVO]
  - `dinovatech/modules/Fiscal/DTOs/NfseData.php` [NOVO]
  - `dinovatech/modules/Fiscal/DTOs/EmissionResult.php` [NOVO]
  - `dinovatech/modules/Fiscal/DTOs/QueryResult.php` [NOVO]
  - `dinovatech/modules/Fiscal/DTOs/CancellationResult.php` [NOVO]
  - `dinovatech/modules/Fiscal/DTOs/UrlResult.php` [NOVO]
  - `dinovatech/modules/Fiscal/Services/NfseService.php` [NOVO]
- **Dependências:** Nenhuma externa.
- **Testes Necessários:** Testes unitários para validação da integridade dos DTOs e fábrica de provedores.
- **Critérios de Conclusão:** Interface e DTOs definidos de forma pura, cobrindo emissão, consulta, cancelamento e visualização.
- **Riscos:** Baixo (código novo e isolado).

---

## FASE C — Encapsulamento do Provedor Legado (`LegacyAbrasfProvider`)
- **Objetivo:** Encapsular a lógica atual do ABRASF 2.04 dentro de uma classe que implementa `NfseProviderInterface`, sem modificar as funções originais nem quebrar a compatibilidade.
- **Arquivos Envolvidos:**
  - `dinovatech/modules/Fiscal/Providers/LegacyAbrasfProvider.php` [NOVO]
  - `dinovatech/app.php` [ADAPTAÇÃO NÃO-DISRUPTIVA]
- **Dependências:** `nfse_test/api.php`, `AppHelper`, `ConfiguracoesEmissor`.
- **Testes Necessários:** Teste de emissão e consulta no legado mantendo 100% de paridade com o comportamento anterior.
- **Critérios de Conclusão:** O sistema pode operar invocando `NfseService->emitir()` apontando para `legacy` com exato mesmo resultado.
- **Riscos:** Médio (garantir que variáveis globais ou temporárias não se percam).

---

## FASE D — Infraestrutura de Certificado A1 e Conexão TLS
- **Objetivo:** Criar componente isolado para manipulação de certificado digital ICP-Brasil e transporte seguro via mTLS para o novo endpoint.
- **Arquivos Envolvidos:**
  - `dinovatech/modules/Fiscal/Security/CertificateManager.php` [NOVO]
  - `dinovatech/modules/Fiscal/Http/SoapClient.php` [NOVO]
- **Dependências:** Extensões PHP `openssl` e `curl`.
- **Testes Necessários:** Handshake SSL/TLS contra o endpoint de homologação `https://nfse.issnetonline.com.br/nfse.asmx`.
- **Critérios de Conclusão:** Conexão HTTPS mTLS estabelecida com sucesso e resposta SOAP válida (mesmo que com Fault de payload vazio).
- **Riscos:** Rejeição de certificados ou incompatibilidade de cifras no cURL (já mitigada pelo padrão testado no Dinovatech).

---

## FASE E — Assinador Digital Nacional (XMLDSig)
- **Objetivo:** Implementar gerador de assinatura digital em conformidade estrita com o padrão da NFS-e Nacional: C14N, RSA-SHA1 e referência específica ao ID da DPS (`URI="#DPS..."`).
- **Arquivos Envolvidos:**
  - `dinovatech/modules/Fiscal/Security/XmlSigner.php` [NOVO]
  - `tests/Unit/XmlSignerTest.php` [NOVO]
- **Dependências:** `ext-dom`, `ext-openssl`.
- **Testes Necessários:**
  - Assinar XML fixture de exemplo.
  - Validar digest calculado contra o valor esperado.
  - Verificar presença correta da tag `<Signature>` filha de `<DPS>`.
- **Critérios de Conclusão:** XML gerado aceito pelo validador oficial (`ValidarXml`).
- **Riscos:** Divergência de canonicalização em namespaces herdados (tratado pelo padrão C14N).

---

## FASE F — Builder de DPS e Geração de XML
- **Objetivo:** Construir o gerador estruturado do XML `GerarNfseEnvio` e `DPS versao="1.01"`, mapeando dados de prestador, tomador, endereço, serviço, valores, alíquotas, código de tributação nacional e grupo `IBSCBS`.
- **Arquivos Envolvidos:**
  - `dinovatech/modules/Fiscal/Builders/DpsXmlBuilder.php` [NOVO]
  - `dinovatech/modules/Fiscal/Formatters/DpsIdGenerator.php` [NOVO] (gera os 45 dígitos oficiais)
  - `tests/Unit/DpsXmlBuilderTest.php` [NOVO]
- **Dependências:** DTO `NfseData`.
- **Testes Necessários:** Validação sintática e contra o XSD `schema_v101.xsd`.
- **Critérios de Conclusão:** XML gerado idêntico à especificação do manual e dos modelos fornecidos.
- **Riscos:** Campos condicionais obrigatórios faltando (ex: `cLocPrestacao`, `cTribNac`, `cNBS`).

---

## FASE G — Provedor Nacional: Emissão Síncrona (`GerarNfse`)
- **Objetivo:** Implementar o método de emissão direta no `NacionalProvider`, montando o envelope SOAP (`nfseCabecMsg` + `nfseDadosMsg`), transmitindo e interpretando o XML de retorno.
- **Arquivos Envolvidos:**
  - `dinovatech/modules/Fiscal/Providers/NacionalProvider.php` [NOVO]
  - `dinovatech/modules/Fiscal/Parsers/NacionalResponseParser.php` [NOVO]
- **Dependências:** `SoapClient`, `XmlSigner`, `DpsXmlBuilder`.
- **Testes Necessários:** Mock de resposta de sucesso e simulação de erros (`ListaMensagemRetorno`).
- **Critérios de Conclusão:** Extração correta do número da nota, chave nacional de 50 dígitos, protocolo e código de verificação.
- **Riscos:** Rejeição do lote ou falha de negócio tratada de forma transparente.

---

## FASE H — Consulta de NFS-e por DPS (`ConsultarNfsePorDps`)
- **Objetivo:** Implementar consulta por DPS para garantir auto-recuperação resiliente imediata em caso de timeout na emissão.
- **Arquivos Envolvidos:**
  - `dinovatech/modules/Fiscal/Builders/ConsultarDpsXmlBuilder.php` [NOVO]
  - `dinovatech/modules/Fiscal/Providers/NacionalProvider.php`
- **Dependências:** `SoapClient`, `NacionalResponseParser`.
- **Testes Necessários:** Teste de consulta com DPS existente e inexistente.
- **Critérios de Conclusão:** Confirmação da nota gerada mesmo se a resposta síncrona original falhar.
- **Riscos:** Baixo.

---

## FASE I — Cancelamento via Evento (`CancelarNfse` / `e101103`)
- **Objetivo:** Implementar cancelamento oficial por solicitação de análise fiscal com registro de evento assinado (`infPedReg Id="PRE..."`).
- **Arquivos Envolvidos:**
  - `dinovatech/modules/Fiscal/Builders/CancelarNfseXmlBuilder.php` [NOVO]
  - `dinovatech/modules/Fiscal/Providers/NacionalProvider.php`
- **Dependências:** `XmlSigner`, `SoapClient`.
- **Testes Necessários:** Testes unitários do XML de cancelamento e parsing do retorno de evento.
- **Critérios de Conclusão:** Cancelamento síncrono registrado no banco com status atualizado.
- **Riscos:** Prazos de homologação pela prefeitura do pedido de cancelamento extemporâneo.

---

## FASE J — Obtenção de URLs de Visualização (`ConsultarUrlNfse`)
- **Objetivo:** Obter URLs oficiais de visualização e autenticidade da NFS-e emitidas pelo novo padrão.
- **Arquivos Envolvidos:**
  - `dinovatech/modules/Fiscal/Builders/ConsultarUrlXmlBuilder.php` [NOVO]
  - `dinovatech/modules/Fiscal/Providers/NacionalProvider.php`
- **Dependências:** `SoapClient`.
- **Testes Necessários:** Teste de consulta de URL por número de nota e por DPS.
- **Critérios de Conclusão:** Persistência de `UrlVisualizacaoNfse` e `UrlVisualizacaoNfseNacional` na tabela `NfseEmissoes`.
- **Riscos:** Baixo.

---

## FASE K — Testes e Validação em Homologação
- **Objetivo:** Realizar emissão, consulta e cancelamento reais no ambiente de homologação do ISS-DF (`https://nfse.issnetonline.com.br/nfse.asmx`).
- **Arquivos Envolvidos:**
  - Scripts de teste em `tests/Integration/NacionalHomologacaoTest.php`
  - Painel de testes administrativo.
- **Dependências:** Certificado digital válido e liberação de acesso de homologação junto ao suporte da Nota Control.
- **Testes Necessários:**
  - Emissão síncrona de 1 DPS com sucesso.
  - Simulação de erro (ex: CNPJ inválido).
  - Consulta da nota recém-emitida.
  - Obtenção da URL de visualização.
  - Solicitação de cancelamento.
- **Critérios de Conclusão:** Ciclo de vida completo validado e aceito pelo webservice sem erros estruturais.
- **Riscos:** Necessidade de cadastro do emitente na base de testes do fisco municipal.

---

## FASE L — Produção Paralela e Alternância por Configuração
- **Objetivo:** Disponibilizar a configuração do provedor ativo no banco de dados (`ConfiguracoesEmissor.nfse_provider`), permitindo chavear instantaneamente entre `legacy` e `nacional` sem alteração de código.
- **Arquivos Envolvidos:**
  - `database/migrations/YYYYMMDD_add_nfse_provider_and_nacional_columns.sql` [NOVO]
  - `dinovatech/config_fiscal.php` [UI de seleção do provedor]
  - `dinovatech/app.php` [Uso do `NfseService`]
- **Dependências:** Fases B a K concluídas.
- **Testes Necessários:** Chaveamento da flag em ambiente de desenvolvimento/staging, testando emissão no legado e no novo.
- **Critérios de Conclusão:** O operador escolhe qual provedor emitirá a nota pelo painel com rollback garantido em 1 clique.
- **Riscos:** Baixo (o legado continua 100% preservado como fallback).

---

## FASE M — Desligamento e Limpeza do Legado (Pós 01/10/2026)
- **Objetivo:** Remover permanentemente o código legado ABRASF 2.04 após a desativação definitiva dos serviços antigos pela SEFAZ-DF.
- **Arquivos Envolvidos:** Detalhados no documento `docs/migracao/05-remocao-legado.md`.
- **Dependências:** Sistema rodando 100% no Padrão Nacional em produção por período contínuo sem incidentes.
- **Critérios de Conclusão:** Código-fonte limpo, sem resquícios de arquivos em `nfse_test/` e referências a ABRASF.
- **Riscos:** Mínimo se o isolamento proposto nas Fases B e C tiver sido rigorosamente respeitado.
