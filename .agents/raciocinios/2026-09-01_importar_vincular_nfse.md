# Raciocínio: Diagnóstico de Erro na Emissão de NFS-e e Criação do Recurso de Importar/Vincular Nota

## 1. Problema e Diagnóstico Inicial
- **Sintoma 1:** Rejeição do ISS DF com a mensagem `"RPS já informado.(Numero RPS: 59)"`.
- **Causa Raiz 1:** O RPS 59 já havia sido enviado anteriormente e gerou registro na base da prefeitura.
- **Sintoma 2:** O usuário ajustou a configuração do último RPS para 59 e depois 60, mas ao emitir no sistema retornou `"Ocorreu um erro no processamento do arquivo."` (para o RPS 61), enquanto no portal do ISS DF a nota anterior (RPS 60 / NFS-e 53) já constava como emitida.
- **Causa Raiz 2:** A resposta da chamada síncrona `GerarNfse` falhou ou foi inconclusiva para o sistema cliente, fazendo com que o sistema marcasse como erro e não incrementasse o `ultimo_rps_producao`. O sistema não dispunha de um mecanismo para importar ou consultar retroativamente notas já existentes no ISS DF.

## 2. Decisão de Projeto
- Foi criada a funcionalidade de **Importar / Vincular NFS-e Existente** com duas abordagens integradas:
  1. **Modo Automático:** Consulta via SOAP (`ConsultarNfseServicoPrestado` ou `ConsultarNfsePorRps`) usando o certificado A1 da empresa. Extrai XML, código de verificação, valores e link do PDF (`ConsultarUrlNfse`).
  2. **Modo Manual (Contingência):** Formulário direto para preenchimento manual de dados da NFS-e para contornar instabilidades no WebService do ISS DF.
- Sincronização do sequencial: Se o RPS importado for maior que o salvo no banco, o sistema atualiza `ConfiguracoesEmissor.ultimo_rps_producao` para evitar novos conflitos na próxima emissão.

## 3. Arquivos Modificados
- `dinovatech/app.php`: Adicionadas as actions `consultar_e_vincular_nfse` e `vincular_nfse_manual`.
- `dinovatech/fatura_view.php`: Adicionado botão no card fiscal, modal com tabs (`#modalImportarNfse`) e scripts AJAX.
