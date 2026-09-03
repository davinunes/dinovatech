# 05 — Plano de Remoção e Descomissionamento do Legado (ABRASF 2.04)

Este documento estabelece o inventário completo e o procedimento de limpeza do código legado após a transição definitiva para o **Padrão Nacional da NFS-e**, prevista para o desligamento em **01/10/2026**.

---

## 1. Inventário de Componentes a Serem Removidos

### 1.1. Arquivos e Diretórios de Código Legado
| Arquivo / Pasta | Finalidade Original | Ação no Descomissionamento |
|---|---|---|
| `nfse_test/api.php` | Script monolítico com builders XML ABRASF, `assinarRoot` e `sendSoap` | **Remover arquivo completo** (ou manter apenas em histórico git). |
| `nfse_test/consultar_nfse.php` | Script procedural legado de consulta | **Remover arquivo**. |
| `nfse_test/commit-estavel-4-metodos.php` | Snapshot de backup de implementação anterior | **Remover arquivo**. |
| `nfse_test/api_append_consultar.php` | Script auxiliar de teste | **Remover arquivo**. |
| `nfse_test/index.php` | Interface de teste legada do ABRASF | **Remover ou arquivar**. |
| `nfse_test/dados enviados pelo suporte/` | Exemplos e payloads legados do suporte | **Remover pasta**. |
| `doc_issdf/padrao_antigo/` | Manuais e esquemas XSD ABRASF 2.04 | **Remover ou mover para pasta de arquivo histórico**. |
| `dinovatech/modules/Fiscal/Providers/LegacyAbrasfProvider.php` | Provedor legado que encapsulava o ABRASF | **Remover classe**. |

### 1.2. Endpoints e URLs Antigas (a serem eliminados de configs/código)
- `https://df.issnetonline.com.br/webservicenfse204/nfse.asmx` (Produção ABRASF 2.04)
- `https://www.issnetonline.com.br/homologaabrasf/webservicenfse204/nfse.asmx` (Homologação ABRASF 2.04)
- `https://www.issnetonline.com.br/apresentacao/df/webservicenfse204/nfse.asmx`
- Namespace: `http://www.abrasf.org.br/nfse.xsd`
- SOAPAction: `http://nfse.abrasf.org.br/*`

### 1.3. Banco de Dados: Colunas e Configurações Legadas
Na tabela `ConfiguracoesEmissor`:
- `ultimo_rps_homologacao` -> Pode ser descontinuada após migração da numeração para DPS.
- `ultimo_rps_producao` -> Pode ser descontinuada (substituída por `ultimo_dps_producao`).
- `serie_rps` -> Pode ser renomeada ou substituída por `serie_dps`.
- `nfse_provider` -> A feature flag de escolha de provedor pode ser removida (tornando o provedor nacional padrão fixo).

Na tabela `NfseEmissoes`:
- Manter o histórico físico das notas já emitidas no passado sob o padrão ABRASF para conformidade legal (guarda fiscal de 5 anos).
- Novas notas utilizarão apenas as colunas nacionais (`numero_dps`, `serie_dps`, `chave_nfse`).

---

## 2. Dependências Ocultas e Cuidados Especiais

1. **`AppHelper::calculateNfseData`:**
   - Atualmente possui referências a `item_lista_servico`, `aliquota_iss` e `iss_retido`.
   - Garantir que o helper não dependa mais de estruturas do ABRASF, fornecendo dados puros ao DTO de faturamento.
2. **`fatura_view.php`:**
   - A função `renderNfseItem` faz regex em `<Numero>` e exibe `RPS: $numero_rps / $serie_rps`.
   - No Padrão Nacional, a exibição deve priorizar `DPS: $numero_dps / $serie_dps` e a `Chave Nacional (50 dígitos)`.
3. **`ContaDevHelper.php`:**
   - Manipulação de nomes de arquivos como `nfse_$id_safe.xml`. O formato de armazenamento do XML continuará compatível, sem exigir alterações de infraestrutura de storage.
4. **`ver_nfse_xml.php`:**
   - Atua lendo `xml_retorno` da tabela `NfseEmissoes`. É agnóstico ao padrão (serve tanto para ABRASF quanto para Padrão Nacional).

---

## 3. Checklist de Auditoria Pós-Remoção (Scripts de Busca)

Antes de considerar a remoção 100% concluída, os seguintes comandos devem retornar **zero resultados** no código ativo da aplicação (`dinovatech/`):

```powershell
# 1. Busca por referências ao namespace ABRASF
rg -i "abrasf" dinovatech/

# 2. Busca por referências ao webservice antigo
rg -i "webservicenfse204" dinovatech/

# 3. Busca por inclusões da pasta legada nfse_test
rg -i "nfse_test" dinovatech/

# 4. Busca por chamadas de funções antigas
rg "buildGerarNfseXml" dinovatech/
rg "buildConsultarNfseRpsXml" dinovatech/
rg "buildConsultarUrlNfseXml" dinovatech/
rg "assinarRoot" dinovatech/
rg "sendSoap" dinovatech/

# 5. Busca por referências a RPS no código de envio novo
rg -i "numero_rps" dinovatech/modules/Fiscal/Providers/NacionalProvider.php
```

---

## 4. Procedimento Seguro de Remoção (Passo a Passo)

1. **Validação Temporal:** Certificar-se de que a data limite da SEFAZ-DF (01/10/2026) foi atingida e o ambiente antigo foi definitivamente desativado pelo fisco.
2. **Backup Pré-Limpeza:** Criar branch de salvaguarda `archive/legacy-abrasf-2026`.
3. **Desativação da Feature Flag:** Definir o `NacionalProvider` como única implementação no container de serviços.
4. **Remoção Física dos Arquivos:** Deletar a pasta `nfse_test/` e a classe `LegacyAbrasfProvider.php`.
5. **Limpeza do `app.php`:** Remover os blocos `require_once '../nfse_test/api.php'`.
6. **Execução do Checklist de Auditoria:** Rodar os comandos de busca acima para assegurar que nenhum ponto do sistema quebrou.
7. **Homologação Final:** Rodar suite de testes automatizados e emitir nota de teste para garantir a integridade total do sistema.
