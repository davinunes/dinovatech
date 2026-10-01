# Raciocínio Analítico: Diagnóstico do Erro [EM062] e Correlação Fiscal Nacional (IBS/CBS)

## 1. Contexto e Problema Relatado
- **Erro:** `[EM062] O Cód. Tributação Nacional, NBS, Cód. Ind. Operação e Classificação Tributária precisam estar correlacionados. (Correção: O conjunto cTribNac, cNBS, cClassTrib e cIndOp não possuem correlação. Acesse https://www.notacontrol.com.br/download/nfse/Correlacao_TribNac_NBS_cClassTribIBSCBS_CSTIBSCBS_IndOp.xlsx.)`
- **Origem:** Rejeição do Web Service da Nota Control / SEFAZ-DF ao receber uma DPS Nacional em ambiente de produção (v1.01).
- **Entrada fornecida:** Arquivo `docs/notacontrol/correlacao.json` com o dump de dados oficiais da planilha da Nota Control.

## 2. Diagnóstico da Estrutura Atual no Dinovatech

### 2.1. Status dos Campos no Sistema
| Campo | Nome no XML | Tabela `Servicos` | `DpsData` (DTO) | `DpsXmlBuilder` | Formulário (`servico_form.php`) |
|---|---|---|---|---|---|
| Cód. Tributação Nacional | `<cTribNac>` | `codigo_tributacao_nacional` | `$codigoTributacaoNacional` | ✅ Sim (6 dígitos) | ✅ Sim |
| Código NBS | `<cNBS>` | `codigo_nbs` | `$codigoNbs` | ✅ Sim (9 dígitos) | ✅ Sim |
| CST IBS/CBS | `<CST>` | `cst_ibs_cbs` | `$cstIbsCbs` | ✅ Sim (3 dígitos) | ✅ Sim |
| Classificação Tributária | `<cClassTrib>` | `classificacao_trib_ibs_cbs` | `$classificacaoTribIbsCbs` | ✅ Sim (6 dígitos) | ✅ Sim |
| Indicador de Operação | `<cIndOp>` | `indicador_operacao` | `$indicadorOperacao` | ✅ Sim (6 dígitos) | ✅ Sim |

**Conclusão dos campos:** Todos os 5 campos fiscais já foram modelados e existem nas tabelas do banco de dados, DTOs e construtores de XML.

### 2.2. Causa Raiz da Rejeição [EM062]
1. **Divergência entre Tuplas Fiscais:** Os serviços cadastrados no banco continham combinações de valores (muitas vezes valores padrão como `cIndOp = 050101` ou `cClassTrib = 000000` ou NBS ausente/divergente) que não constam como uma combinação válida na matriz de correlação oficial da Reforma Tributária da SEFAZ/Nota Control.
2. **Fallback Genérico no XML Builder:** `DpsXmlBuilder` aplicava hardcodes como `'100301'` e `'000001'` caso o serviço tivesse `'050101'` ou `'000000'`, mas esses hardcodes só são válidos para alguns códigos específicos de serviço e não para todos.
3. **Falta de Validação Prévia (Pre-flight Check):** O sistema permitia tentar emitir a nota sem validar se os 5 campos fiscais formavam uma tupla válida na tabela de correlação, causando o erro em tempo de execução no Web Service.

## 3. Diretrizes da Solução
1. **Sincronização Completa da Matriz Oficial:** Importar/popular a tabela `TribRefCorrelacaoIbsCbs` com todos os registros oficiais do `correlacao.json` padronizando as máscaras (zeros à esquerda).
2. **Motor de Validação Fiscal (`FiscalValidator` / `FiscalCatalogHelper`):** Implementar validação estrita pré-emissão e autocompletar inteligente no frontend e backend.
3. **Auditoria de Conformidade em Serviços:** Destacar visualmente na lista de serviços quais itens estão prontos para emissão e quais necessitam de ajuste cadastral.
