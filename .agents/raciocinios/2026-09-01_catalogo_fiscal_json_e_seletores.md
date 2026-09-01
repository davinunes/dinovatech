# Raciocínio: Catálogo Fiscal em JSON e Seletores Inteligentes

- **Objetivo:** Facilitar e blindar a parametrização fiscal dos serviços e contratos, evitando erros manuais de digitação de alíquota (ex: 2.01% vs 2.00%), códigos de tributação e CNAEs.
- **Origem dos Dados:** Ficha Cadastral Oficial da SEFIN DF / Sistema Nota Control da empresa LD TECNOLOGIA DA INFORMACAO LTDA (CNPJ 61.733.714/0001-01 / Inscrição Municipal 0841147200111).
- **Decisões Técnicas:**
  1. Criação do arquivo `dinovatech/data/fiscal_catalog.json` para facilitar a extensão em outras instâncias sem necessidade de alterar código SQL/PHP.
  2. Implementação do `FiscalCatalogHelper.php` para fornecer métodos de consulta e mapeamento.
  3. Atualização dos formulários de serviços (`servico_form.php`) e contratos (`contrato_form.php`) com seletores inteligentes e listeners JavaScript em tempo real.
