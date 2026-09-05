# Raciocínio Analítico: Consulta de Dados Cadastrais SEFAZ-DF & Auditoria de Serviços

- **Data:** 05/09/2026
- **Contexto:** Validação do método `ConsultarDadosCadastrais` da NFS-e Nacional (ISS-DF / Nota Control v1.01), confronto com cadastro da empresa e implementação de auditoria preventiva de alíquotas de serviços.

---

## 1. Descobertas e Diagnóstico

1. **Operacionalidade do Endpoint:**
   - Ao contrário de `ConsultarUrlNfse` (que a ISSNet desativou retornando `L090`), o endpoint `ConsultarDadosCadastrais` está **100% ativo** e funcional no ambiente de produção da SEFAZ-DF.
   - O envelope SOAP 1.1 direto (`versaoDados="1.01"`, sem prólogo XML, sem CDATA) autenticado via mTLS (Certificado A1 da empresa) retorna a estrutura completa de `<Cadastro>`.

2. **Diagnóstico dos Dados Retornados:**
   - **Razão Social:** `LD TECNOLOGIA DA INFORMACAO LTDA` (anteriormente registrada no CNPJ como `DAVI NUNES DE FRANCA TECNOLOGIA...`, confirmando que a alteração contratual na Junta/SEFAZ foi processada com sucesso).
   - **Regime:** Optante pelo Simples Nacional desde 14/07/2025; não optante pelo MEI.
   - **Atividades e Alíquotas:**
     - Atividades de desenvolvimento, programação, consultoria, manutenção, treinamento e web (`101`, `102`, `103`, `104`, `105`, `106`, `107`, `108`, `802`) estão ativas com **alíquota de 2,00%**.
     - As atividades com alíquota de 5,00% antigas (`1011`, `1021`, `1061`, `1081`, etc.) tiveram sua vigência encerrada na SEFAZ-DF em **09/05/2026** (`<DataFinal>2026-05-09</DataFinal>`).
     - Apenas `1031` (hospedagem) e `1071` (suporte técnico) permanecem ativas com 5,00%.

3. **Demanda do Usuário:**
   - Adicionar botão nas Configurações Fiscais e no Laboratório de Testes;
   - Ao disparar a consulta via Configuração Fiscal, o sistema deve confrontar a base de dados oficial com a tabela de serviços cadastrados (`Servicos`) e alertar caso haja serviços com alíquotas incorretas, atividades vencidas ou não cadastradas.

---

## 2. Decisões Arquiteturais e Solução

1. **DTO e Builder dedicados:**
   - `CadastroResult.php` centraliza todos os campos de pessoa jurídica, flags de NFS-e, Simples Nacional e arrays de atividades vigentes e expiradas.
   - `ConsultarDadosCadastraisXmlBuilder.php` isola a montagem de `<ConsultarDadosCadastraisEnvio>`.

2. **Parser em `NacionalResponseParser`:**
   - Extrai de forma resiliente tanto mensagens de erro quanto os nós de `<Cadastro>` e múltiplas instâncias de `<Atividade>`.

3. **Motor de Auditoria em `NfseService`:**
   - Mapeia cada serviço ativo com as atividades oficiais através do código municipal (`codigo_tributacao_municipio`), item LC 116 (`item_lista_servico`) e código nacional.
   - Compara a alíquota cadastrada no Dinovatech (`aliquota_iss`) com o percentual exigido pelo Fisco (`pAliq`), alertando divergências e sugerindo a alíquota correta.
   - Alerta se a atividade tiver `<DataFinal>` no passado, prevenindo rejeições futuras na emissão.

4. **UX / UI Interativa:**
   - Em `config_fiscal.php`: modal elegante exibindo resumo da empresa, alertas de inconsistências com link direto para edição de cada serviço divergente, e botão opcional para atualizar os dados cadastrais locais com a base da SEFAZ.
   - Em `nfse_nacional_test`: botão dedicado e visualização formatada em texto e XML.
