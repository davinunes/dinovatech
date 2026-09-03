---
name: nfse_legado_auditor
description: Procedimentos de auditoria de acoplamento do código legado ABRASF 2.04 e verificação de prontidão para remoção pós 01/10/2026.
---

# Skill: Auditoria de Desacoplamento e Remoção do Legado Fiscal

Esta skill serve para garantir que nenhuma dependência indevida da integração antiga (ABRASF 2.04 / `nfse_test`) seja introduzida na nova implementação nacional e orienta o processo de auditoria de código para o desligamento em 01/10/2026.

## 1. Princípio Fundamental de Arquitetura
A nova integração Padrão Nacional **JAMAIS** deve:
- Herdar de classes legadas ou importar `nfse_test/api.php`.
- Reutilizar funções antigas como `buildGerarNfseXml`, `assinarRoot` (versão hackeada de URI vazia) ou `sendSoap` antigo.
- Compartilhar DTOs com semântica de RPS.

## 2. Comandos de Auditoria de Código
Execute no terminal ripgrep/grep para verificar se há acoplamentos vazando do legado:

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

## 3. Checklist de Limpeza Final (Pós 01/10/2026)
Consulte `docs/migracao/05-remocao-legado.md` antes de remover os arquivos físicos e excluir os scripts obsoletos.
