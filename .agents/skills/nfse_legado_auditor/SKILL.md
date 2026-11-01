---
name: nfse_legado_auditor
description: Procedimentos de auditoria de acoplamento do código legado ABRASF 2.04 e verificação de prontidão para remoção pós 01/11/2026.
---

# Skill: Auditoria de Desacoplamento e Remoção do Legado Fiscal

Esta skill serve para garantir que nenhuma dependência indevida da integração antiga (ABRASF 2.04 / `nfse_test`) seja mantida ou introduzida no Dinovatech. O prazo de transição estabelecido pela SEFAZ-DF e RFB expirou em **31/10/2026** (conforme erro `[EM110]`), tornando a API do Ambiente Nacional (`sefin_nacional`) o canal obrigatório e definitivo para Optantes pelo Simples Nacional.

---

## 1. Princípio Fundamental de Arquitetura

A integração Padrão Nacional **JAMAIS** deve:
- Herdar de classes legadas ou importar `nfse_test/api.php`.
- Reutilizar funções antigas como `buildGerarNfseXml`, `assinarRoot` (versão hackeada de URI vazia) ou `sendSoap` antigo.
- Compartilhar DTOs com semântica de RPS antigo.
- Utilizar endpoints SOAP municipais da Nota Control para competências posteriores a 31/10/2026.

---

## 2. Comandos de Auditoria de Código

Execute no terminal para verificar se há acoplamentos residuais do legado:

```bash
# 1. Checar se a nova pasta fiscal chama funções legadas
rg -i "buildGerarNfseXml" dinovatech/modules/Fiscal/
rg -i "assinarRoot" dinovatech/modules/Fiscal/
rg -i "sendSoap" dinovatech/modules/Fiscal/
rg -i "nfse_test" dinovatech/modules/Fiscal/

# 2. Checar se namespaces ABRASF estão no módulo novo
rg -i "abrasf" dinovatech/modules/Fiscal/

# 3. Checar se as ações de app.php usam a camada de abstração
rg -n "require_once '../nfse_test/api.php'" dinovatech/app.php
```

---

## 3. Relação de Arquivos Legados em Descontinuação

Os seguintes arquivos pertencem à pilha antiga ABRASF 2.04:
- `nfse_test/` (diretório raiz do antigo conector ISSNet / Nota Control)
- `dinovatech/modules/Fiscal/Providers/LegacyAbrasfProvider.php` (mantido apenas para consulta retroativa de notas emitidas antes de Nov/2026)
- Modelos de cancelamento via SOAP ABRASF em `dinovatech/app.php`

> [!NOTE]
> Para emissão regular e faturas correntes, o provider deve ser estritamente `sefin_nacional`.
