# Raciocínio: Correção da Tradução de Variáveis nos Modelos de Documentos Contratuais

**Data:** 2026-10-06  
**Tópico:** Variáveis de modelos de documentos não traduzidas no vínculo de contratos (`contrato_form.php` / `documento_print.php`)

---

## 1. Identificação da Causa Raiz

Ao analisar o modelo `modelo2.txt` criado pelo usuário e compará-lo com as variáveis suportadas no backend (`app.php` e `documento_print.php`), foram identificadas 4 causas principais para a falha na tradução das variáveis:

1. **Variáveis Ausentes e Nomes de Tags Diferentes (Falta de Aliases):**
   - O usuário utilizou as tags `{{RAZAO_SOCIAL_CLIENTE}}`, `{{CNPJ_CLIENTE}}`, `{{IE_CLIENTE}}` e `{{MESES_VIGENCIA}}`.
   - O sistema no PHP aceitava apenas `{{NOME_TUTOR}}` / `{{NOME_CLIENTE}}` e `{{CPF_TUTOR}}` / `{{CPF_CNPJ_CLIENTE}}`.
   - Tags como `{{RAZAO_SOCIAL_CLIENTE}}`, `{{CNPJ_CLIENTE}}`, `{{IE_CLIENTE}}` e `{{MESES_VIGENCIA}}` não existiam no array `$vars`, fazendo com que o `str_replace` do PHP as ignorasse e mantivesse a sintaxe em código no documento final.

2. **Projeção Incompleta de Campos de Clientes na Consulta de Recorrências (`SELECT` incompleto):**
   - Nas buscas por contrato/recorrência em `app.php` (`get_modelo_vars_preview` e `save_document_emitted`) e em `documento_print.php`, a SQL trazia apenas alguns campos de `Clientes` (`c.nome`, `c.cpf_cnpj`, `c.endereco`, `c.email`, `c.telefone`).
   - Campos essenciais como `c.inscricao_estadual`, `c.inscricao_municipal`, `c.numero`, `c.bairro`, `c.complemento`, `c.cep`, `c.uf` e `c.codigo_municipio` não eram selecionados.

3. **Formatação e Composição do Endereço Completo:**
   - A tag `{{ENDERECO_CLIENTE}}` retornava apenas o campo bruto de logradouro (`c.endereco`), sem incluir número, complemento, bairro, UF ou CEP.

4. **Tradução Incorreta de `{{DIA_VENCIMENTO}}`:**
   - O código calculava `{{DIA_VENCIMENTO}}` a partir de `data_inicio_cobranca` e ignorava a coluna específica `dia_vencimento` presente na tabela `Recorrencias`.

---

## 2. Plano de Ação e Solução

1. **Atualizar as Consultas SQL de Recorrência/Contrato:**
   - Incluir todos os campos da tabela `Clientes` (`c.*`) ao buscar os dados da recorrência.

2. **Expandir o Mapeamento `$vars` (Backend PHP):**
   - Adicionar aliases no mapa de substituição em `app.php` (`get_modelo_vars_preview`, `save_document_emitted`) e `modules/Vet/documento_print.php`:
     - `{{RAZAO_SOCIAL_CLIENTE}}` => Nome / Razão Social do cliente
     - `{{CNPJ_CLIENTE}}` / `{{CPF_CLIENTE}}` => CPF / CNPJ formatado do cliente
     - `{{IE_CLIENTE}}` / `{{INSCRICAO_ESTADUAL_CLIENTE}}` => Inscrição Estadual do cliente
     - `{{IM_CLIENTE}}` / `{{INSCRICAO_MUNICIPAL_CLIENTE}}` => Inscrição Municipal do cliente
     - `{{ENDERECO_CLIENTE}}` => Endereço completo formatado (Rua, nº, compl, bairro, UF, CEP)
     - `{{MESES_VIGENCIA}}` => Duração calculada do contrato em meses (padrão: 12 se não definida data fim)
     - `{{DIA_VENCIMENTO}}` => Utilizar `dia_vencimento` da recorrência como prioridade
     - `{{VALOR_CONTRATO}}` e `{{VALOR_RECORRENTE}}` => Valor formatado em R$

3. **Atualizar o Painel de Tags na Interface (`modelos_documentos.php`):**
   - Exibir as novas tags no painel de atalhos do editor de modelos para orientar a criação de futuros documentos.

---

## 3. Validação

- Verificar sintaxe dos arquivos modificados.
- Garantir compatibilidade tanto no módulo de Veterinária quanto no cadastro geral de contratos (`contrato_form.php`).
