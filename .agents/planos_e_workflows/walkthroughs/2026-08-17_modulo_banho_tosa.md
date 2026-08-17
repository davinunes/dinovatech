# Walkthrough - Módulo de Banho e Tosa & Ajustes Fiscais/Portal do Cliente

Data: 2026-08-17
Status: Concluído e Atualizado

## 1. Resumo dos Ajustes Realizados

### A. Correção de Requisições na Esteira Operacional
- **Problema**: `get_banho_producao_fila` retornava `"Requisição inválida (apenas POST permitido)"`.
- **Solução**: Atualizado `dinovatech/app.php` para aceitar requisições `GET` e `POST` no switch principal, permitindo que o auto-polling e consultas da esteira funcionem com 100% de estabilidade.

### B. Área do Cliente / Portal do Tutor (`cliente/index.php`)
- Adicionada aba exclusiva **"Banho & Tosa"** (quando em Modo Vet).
- **Acompanhamento em Tempo Real**: Banner ao vivo destacando a etapa atual do pet na esteira (Recepção, Banho & Hidratação, Secagem, Tosa, Pronto para Retirada).
- **Meus Pacotes & Saldos**: Cards com barras de progresso de créditos disponíveis vs utilizados por serviço do pacote.
- **Agendamento Online pelo Tutor**: Modal intuitivo para escolha do pet, serviço, detecção automática de créditos de pacotes e escolha de data/hora.

### C. Seletor de Ícones & Upload de Fotos no Oracle Cloud
- `dinovatech/modules/Vet/pacote_form.php` e `dinovatech/servico_form.php`:
  - **Modal de Ícones**: Grid com mais de 40 ícones do Material Icons categorizados e com busca instantânea.
  - **Upload de Fotos (Oracle Cloud Storage)**: Botão "Upar Foto" que envia a imagem via `PUT` para a URL pré-autenticada do Oracle Cloud (`api_oracle_url`), salvando a URL pública no campo e exibindo preview instantâneo.

### D. Separação Visual dos Parâmetros Fiscais (NFS-e)
- `dinovatech/config_fiscal.php`:
  - **Card 1**: Dados Gerais da Empresa (Razão Social, CNPJ, Telefone, Logo, Cadastro sem CPF, Tema Landing Page, Check-in Fotográfico de Banho).
  - **Card 2**: Módulo e Emissão de NFS-e em card independente com toggle de ativação. Os campos fiscais (Inscrição Municipal, Endereço Fiscal, Código IBGE, Parâmetros RPS) ficam recolhidos se a NFS-e não estiver ativa.
