# Planejamento: Sistema de Clínica Veterinária (DinoVET)

## 1. Visão Geral
Transformar o sistema atual (Gestão de Clientes e Faturas) em um **Sistema de Gestão Veterinária (ERP Vet)**.
A base atual (`Clientes`, `Faturas`, `Servicos`) será aproveitada como o módulo administrativo/financeiro. Novas entidades serão acopladas para o módulo clínico.

## 2. Entidades Principais

### A. Tutors (Clientes)
- **Status**: Já existe (`Clientes`).
- **Adaptação**: O "Cliente" passa a ser semanticamente o "Tutor".
- **Relacionamento**: Um Tutor possui N Pets.

### B. Pets (Pacientes)
- **Novo**: Sim.
- **Campos**: Nome, Espécie (Canino, Felino...), Raça, Sexo, Data Nascimento, Pelagem, Peso Atual, Chip ID, Obs.
- **Relacionamento**: Pertence a `Clientes`.

### C. Veterinários (Staff)
- **Novo**: Sim.
- **Campos**: Nome, CRMV, UF, Telefone, Usuário Vinculado (Login).
- **Função**: Responsável por atendimentos, vacinas e receitas.

### D. Vacinas & Imunização
- **Vacinas (Catálogo)**:
    - Nome (ex: V10, Raiva), Descrição, Período Recorrência (dias).
- **Carteira de Vacinas (Aplicação)**:
    - Pet, Vacina, Data Aplicação, Data Próxima (Calculada), Lote, Responsável (Vet).

### E. Prontuário Eletrônico (Atendimentos)
- **Conceito**: Histórico cronológico de saúde do Pet.
- **Entidade `Atendimentos`**:
    - Data/Hora, Vet Responsável, Motivo (Queixa), Anamnese, Exame Físico (Temp, FC, FR...), Diagnóstico, Tratamento.
- **Anexos**: Exames, Fotos.

### F. Documentos Clínicos
- **Funcionalidade**: Gerador de documentos.
- **Tipos**: Receitas, Atestados, Termos de Consentimento.
- **Estrutura**: Templates HTML pré-definidos que puxam dados do Pet/Tutor.

---

## 3. Modelo de Banco de Dados (Proposto)

```sql
-- TABELA: Pets
CREATE TABLE `Pets` (
  `id_pet` int NOT NULL AUTO_INCREMENT,
  `id_cliente` int NOT NULL, -- FK Tutor
  `nome` varchar(100) NOT NULL,
  `especie` varchar(50) NOT NULL, -- Cachorro, Gato, etc
  `raca` varchar(100),
  `sexo` char(1), -- M/F
  `data_nascimento` date,
  `peso` decimal(5,2), -- Peso atual em KG
  `chip_id` varchar(50),
  `obs` text,
  `data_cadastro` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_pet`),
  FOREIGN KEY (`id_cliente`) REFERENCES `Clientes`(`id_cliente`)
);

-- TABELA: Veterinarios
CREATE TABLE `Veterinarios` (
  `id_vet` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `crmv` varchar(20) NOT NULL,
  `uf_crmv` char(2) NOT NULL,
  `telefone` varchar(20),
  `email` varchar(100),
  PRIMARY KEY (`id_vet`)
);

-- TABELA: Vacinas (Catálogo)
CREATE TABLE `Vacinas` (
  `id_vacina` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `descricao` text,
  `recorrencia_dias` int DEFAULT 365, -- Ex: 365 para anual
  PRIMARY KEY (`id_vacina`)
);

-- TABELA: CarteiraVacinas (Aplicação)
CREATE TABLE `CarteiraVacinas` (
  `id_carteira` int NOT NULL AUTO_INCREMENT,
  `id_pet` int NOT NULL,
  `id_vacina` int NOT NULL,
  `id_vet` int, -- Quem aplicou
  `data_aplicacao` date NOT NULL,
  `data_vencimento` date, -- Calculado ou manual
  `lote` varchar(50),
  `observacao` text,
  PRIMARY KEY (`id_carteira`),
  FOREIGN KEY (`id_pet`) REFERENCES `Pets`(`id_pet`),
  FOREIGN KEY (`id_vacina`) REFERENCES `Vacinas`(`id_vacina`)
);

-- TABELA: Atendimentos (Prontuário)
CREATE TABLE `Atendimentos` (
  `id_atendimento` int NOT NULL AUTO_INCREMENT,
  `id_pet` int NOT NULL,
  `id_vet` int NOT NULL,
  `data_atendimento` datetime DEFAULT CURRENT_TIMESTAMP,
  `queixa_principal` text,
  `anamnese` text,
  `exame_fisico` text, -- Pode ser JSON ou texto livre
  `diagnostico` text,
  `conduta_tratamento` text,
  PRIMARY KEY (`id_atendimento`),
  FOREIGN KEY (`id_pet`) REFERENCES `Pets`(`id_pet`),
  FOREIGN KEY (`id_vet`) REFERENCES `Veterinarios`(`id_vet`)
);

-- TABELA: DocumentosEmitidos
CREATE TABLE `DocumentosEmitidos` (
  `id_documento` int NOT NULL AUTO_INCREMENT,
  `id_atendimento` int, -- Opcional, vinculado a consulta
  `id_pet` int NOT NULL,
  `tipo` varchar(50), -- RECEITA, ATESTADO, TERMO
  `conteudo` longtext, -- HTML gerado
  `data_emissao` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_documento`)
);
```

---

## 4. Plano de Implementação (Roadmap)

### Fase 1: Fundação
1.  **Migração DB**: Criar tabelas novas (`schema_vet.sql`).
2.  **Cadastro de Pets**:
    - Tela de Listagem de Pets (por Tutor).
    - Formulário de Criação/Edição de Pet.
    - Alterar "Detalhes do Cliente" para incluir aba "Meus Pets".

### Fase 2: Gestão Clínica Básica
1.  **Cadastro de Vacinas**: CRUD de tipos de vacina.
2.  **Carteira de Vacinação**: 
    - Visualização no Perfil do Pet.
    - Lógica de "Próxima Dose" (Alerta de Vencimento).
    - Registro de aplicação.

### Fase 3: Prontuário e Atendimento
1.  **Nova Consulta**: Tela para registrar atendimento.
2.  **Histórico**: Timeline do Pet com consultas e vacinas passadas.

### Fase 4: Documentos
1.  **Gerador de Receitas**: Editor simples ou campos estruturados que geram PDF/Print.
2.  **Impressão**: Layout de impressão com cabeçalho da clínica.

---

## 5. UI/UX - Sugestões
- **Dashboard Principal**: Além do financeiro, ter widgets "Atendimentos do Dia", "Vacinas Vencendo".
- **Busca Global**: Buscar por Tutor, Pet ou Telefone.
- **Design**: Manter o padrão limpo atual, talvez usar ícone de patinha 🐾 para diferenciar ações Vet.
