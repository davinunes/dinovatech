# Plano de Implementação - Integração ContaDev-Contabilidade

Este documento detalha o planejamento técnico para a integração do sistema Dinovatech com a plataforma **ContaDev-Contabilidade**, permitindo o armazenamento de credenciais/tokens de autenticação e a importação direta de notas fiscais (PDF e XML) a partir da tela de visualização de faturas (`fatura_view.php`).

---

## 1. Mapeamento de Atributos e Estrutura de Banco de Dados

### 1.1 Tabela `ConfiguracoesEmissor` (Mapeamento sem alterações manuais ad-hoc)
Para evitar múltiplas alterações iterativas no banco, criaremos uma migration consolidada adicionando os atributos necessários para manter a sessão da ContaDev:
- `contadev_email` (VARCHAR 255): E-mail do usuário autenticado no ContaDev.
- `contadev_token` (TEXT): Token JWT de autenticação (`Bearer token`).
- `contadev_user_id` (VARCHAR 255): UUID do usuário no ContaDev (`user.id` / `platformUserId`).
- `contadev_cnpj_id` (VARCHAR 255): UUID da empresa cadastrada no ContaDev (`cnpjs[0].id`).
- `contadev_company_name` (VARCHAR 255): Razão social / Fantasia da empresa registrada no ContaDev.
- `contadev_user_name` (VARCHAR 255): Nome do usuário no ContaDev.
- `contadev_ativo` (TINYINT 1 DEFAULT 0): Flag indicando se a integração está ativa.

### 1.2 Tabela Relacional de Sincronização (`nf_contadev_sync`)
Tabela criada via migration para rastrear e validar notas fiscais importadas para o ContaDev:
- `id_sync` (INT AUTO_INCREMENT PRIMARY KEY)
- `id_fatura` (INT NOT NULL, FK `Faturas.id_fatura`)
- `contadev_nf_id` (VARCHAR 255 NULL): UUID retornado pelo ContaDev após importação.
- `external_id` (VARCHAR 100 NULL): ID externo atribuído pelo ContaDev.
- `tomador_id` (VARCHAR 255 NULL): UUID do tomador de serviço cadastrado no ContaDev.
- `pdf_s3_uri` (VARCHAR 500 NULL): URI de destino do arquivo PDF na S3 da ContaDev.
- `xml_s3_uri` (VARCHAR 500 NULL): URI de destino do arquivo XML na S3 da ContaDev.
- `valor` (DECIMAL 10,2 NULL): Valor total importado.
- `issued_at` (DATE NULL): Data da emissão da nota.
- `status_importacao` (VARCHAR 50 DEFAULT 'pendente'): `sucesso`, `erro`, `ja_importada`.
- `import_dedup_key` (VARCHAR 255 NULL): Chave para controle de desduplicação.
- `detalhes_resposta` (LONGTEXT NULL): Payload JSON completo de retorno do ContaDev ou mensagem de erro.
- `criado_em` (DATETIME DEFAULT CURRENT_TIMESTAMP)
- `atualizado_em` (DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP)

### 1.3 Tabela de Logs de Operações (`config_contadev_logs`)
Tabela para registro histórico e auditoria de todas as requisições à API da ContaDev:
- `id_log` (INT AUTO_INCREMENT PRIMARY KEY)
- `id_fatura` (INT NULL): ID da fatura vinculada (se aplicável).
- `acao` (VARCHAR 100): Ex: `login`, `get_me`, `check_tomador`, `create_tomador`, `get_presigned_url`, `upload_s3`, `import_nf`, `check_dedup`.
- `status` (ENUM('sucesso', 'erro', 'info'))
- `mensagem` (TEXT NULL)
- `payload_requisicao` (LONGTEXT NULL)
- `payload_resposta` (LONGTEXT NULL)
- `criado_em` (DATETIME DEFAULT CURRENT_TIMESTAMP)

---

## 2. Fluxo das Requisições da API ContaDev

### 2.1 Autenticação e Configuração (`config_fiscal.php`)
1. **Login**: `POST https://api-app.conta-dev.com/platform/login` (`email`, `password`).
2. **Dados do Perfil/Empresa**: `GET https://api-app.conta-dev.com/platform/me` (com `Authorization: Bearer <token>`).
   - Obtém `user.id`, `user.name`, e `cnpjs[0].id` (`registrationNumber` / `name`).
3. **Card na Aba Integrações**:
   - Se inativo: Formulário com E-mail e Senha + Botão "Conectar ContaDev".
   - Se ativo: Badge "Conectado", E-mail, Empresa vinculada, CNPJ ID e botão "Desconectar".

### 2.2 Verificação de Desduplicação (Pré-Importação)
Antes de enviar qualquer arquivo, a requisição fará uma verificação em duas etapas:
1. **Verificação Local**: Checa se `nf_contadev_sync` possui registro com `status_importacao = 'sucesso'` para a `id_fatura`.
2. **Verificação na API ContaDev (`GET /platform/nf`)**:
   - **Identificador Principal (Padrão na Descrição)**: Na importação, a descrição é gerada com o trecho padronizado `"Conforme documento auxiliar de cobranca numero {id_fatura}"`. Na listagem de notas da ContaDev, o sistema busca na propriedade `description` se contém `"numero {id_fatura}"`.
   - **Identificador Secundário (Nome do Arquivo na S3)**: Checa se a propriedade `xmlS3Uri` ou `issuedS3Uri` contém a referência do número da nota/fatura (ex: `nfse_{numero}.xml` ou `_{id_fatura}_`).
   - **Identificador de Fallback (Combinação Tríplice)**: Compara se `tomadorSnapshot.documento` (CPF/CNPJ limpo), `value` (valor total da fatura) e `issuedAt` (data da emissão YYYY-MM-DD) coincidem exatamente.
   - Se encontrada por qualquer um dos identificadores, vincula a nota existente salvando o `id` da ContaDev em `nf_contadev_sync` e notifica na tela que a nota já existia na plataforma.

### 2.3 Gestão de Tomadores (Clientes)
1. Chama `GET https://api-app.conta-dev.com/platform/tomadores?cnpjId={contadev_cnpj_id}`.
2. Compara o documento (`cpf_cnpj` sem pontuação) do cliente local com os tomadores existentes.
3. Caso exista, obtém o `tomadorId`.
4. Caso não exista, realiza `POST https://api-app.conta-dev.com/platform/tomadores`:
   ```json
   {
     "cnpjId": "<contadev_cnpj_id>",
     "tipo": "BR_PJ" ou "BR_PF",
     "documento": "<cpf_cnpj_somente_numeros>",
     "razaoSocial": "<nome_cliente>"
   }
   ```
   e armazena o `id` retornado como `tomadorId`.

### 2.4 Obtenção de URLs Pré-assinadas e Upload S3
1. **Solicitação URL PDF**: `POST https://api-app.conta-dev.com/platform/nf/import/pre-signed-url` com `{"fileName": "fatura-{id}.pdf", "fileType": "pdf"}`.
2. **Solicitação URL XML**: `POST https://api-app.conta-dev.com/platform/nf/import/pre-signed-url` com `{"fileName": "nfse-{id}.xml", "fileType": "xml"}`.
3. **Upload HTTP PUT**:
   - `PUT <url_s3_pdf>` com cabeçalho `Content-Type: application/pdf` contendo os bytes do PDF (primeiro anexo em `FaturaArquivos`).
   - `PUT <url_s3_xml>` com cabeçalho `Content-Type: text/xml` contendo o conteúdo XML assinado da NFS-e.

### 2.5 Efetivação da Importação
1. Chama `POST https://api-app.conta-dev.com/platform/nf/import`:
   ```json
   {
     "isForeign": false,
     "value": <valor_liquido_ou_total>,
     "cnpjId": "<contadev_cnpj_id>",
     "description": "<descricao_itens>\nConforme documento auxiliar de cobranca numero <id_fatura>",
     "tomadorId": "<tomador_id>",
     "issuedAt": "YYYY-MM-DD",
     "pdfS3Uri": "<pdfS3Uri>",
     "xmlS3Uri": "<xmlS3Uri>"
   }
   ```
2. Registra o retorno no `nf_contadev_sync` e gera o log em `config_contadev_logs`.
3. Retorna o status via JSON para a interface e exibe Toast de Sucesso/Erro no frontend.

---

## 3. Modificações de Interface (UI/UX)

### 3.1 Card em `dinovatech/config_fiscal.php`
- Localizado na aba **Integrações (API)**.
- Card moderno com padrão visual consistente (ícone de contabilidade/nuvem, campos limpos, senha mascarada).
- Botões de conexão, teste de status e desconexão.

### 3.2 Card Premium em `dinovatech/fatura_view.php`
- Posicionado na coluna lateral direita (Sidebar de Ações), **imediatamente antes do botão "Imprimir / PDF"**.
- Estilo: Card destacado com badge de status (Verde para "Sincronizado", Amarelo para "Pendente", Vermelho para "Erro").
- Conteúdo do Card:
  - Título: **ContaDev Contabilidade**
  - Informações de Feedback:
    - Status de Sincronização.
    - Data da última sincronização ou ID retornado na ContaDev.
  - Botão de Ação: `[ Importar no ContaDev ]` (com ícone de cloud_upload e feedback de carregamento durante a requisição).
- Feedback via Toast no topo da página em caso de erro da API ou sucesso na operação.

---

## 4. Arquivos a Criar e Modificar

1. `database/migrations/20260810_0001_create_contadev_tables_and_config.sql` **[NOVO]**
2. `dinovatech/helpers/ContaDevHelper.php` **[NOVO]**
3. `dinovatech/config_fiscal.php` **[MODIFICAR]**
4. `dinovatech/fatura_view.php` **[MODIFICAR]**
5. `dinovatech/app.php` **[MODIFICAR]**

---

## 5. Plano de Verificação

### Testes Manuais
1. **Configuração Fiscal**:
   - Testar login na ContaDev pelo painel de configurações com credenciais válidas/inválidas.
   - Confirmar gravação em `ConfiguracoesEmissor`.
   - Verificar persistência dos dados ao recarregar a página `config_fiscal.php`.
2. **Visualização de Fatura**:
   - Acessar `fatura_view.php?id={id_fatura}`.
   - Confirmar presença e renderização do Card Premium ContaDev acima do botão "Imprimir / PDF".
   - Clicar no botão "Importar no ContaDev":
     - Testar busca/criação de tomador.
     - Testar requisição de URLs pré-assinadas para PDF e XML.
     - Verificar gravação dos logs em `config_contadev_logs` e na tabela `nf_contadev_sync`.
     - Verificar mensagens de erro/toast caso o PDF ou XML não estejam presentes na fatura local.
