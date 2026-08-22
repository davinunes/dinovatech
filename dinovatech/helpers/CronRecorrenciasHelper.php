<?php
// dinovatech/helpers/CronRecorrenciasHelper.php

class CronRecorrenciasHelper
{
    /**
     * Processa a geração automática de faturas para todas as recorrências ativas do mês de competência.
     * Gera 1 fatura individual para cada contrato.
     *
     * @param string|null $mesAnoAlvo Formato "MM/YYYY" (ex: "08/2026"). Se nulo, usa o mês atual.
     * @param string $origem Origem da execução ('cron_cli', 'web', 'manual')
     * @return array Resumo da execução
     */
    public static function processarRecorrencias($mesAnoAlvo = null, $origem = 'cron_cli')
    {
        $link = DBConnect();
        if (!$link) {
            return [
                'success' => false,
                'message' => 'Falha ao conectar no banco de dados.',
                'faturas_geradas' => 0,
                'valor_total' => 0.00,
                'faturas' => [],
                'erros' => ['Erro de conexão com o banco de dados']
            ];
        }

        // Limpeza preventiva de eventuais faturas órfãs vazias criadas sem itens
        @DBExecute($link, "DELETE FROM Faturas WHERE id_fatura NOT IN (SELECT DISTINCT id_fatura FROM ItensFatura) AND DATE(data_emissao) = CURDATE() AND status = 'Em Aberto' AND (valor_total_fatura = 0 OR valor_total_fatura IS NULL)");

        if (empty($mesAnoAlvo)) {
            $mesAnoAlvo = date('m/Y');
        }

        // Decompõe mês e ano da competência
        $partes = explode('/', $mesAnoAlvo);
        $mes = isset($partes[0]) ? str_pad(trim($partes[0]), 2, '0', STR_PAD_LEFT) : date('m');
        $ano = isset($partes[1]) ? trim($partes[1]) : date('Y');
        $mesAnoSafe = "$mes/$ano";

        $primeiroDiaCompetencia = sprintf('%04d-%02d-01', $ano, $mes);
        $ultimoDiaCompetencia = date('Y-m-t', strtotime($primeiroDiaCompetencia));
        $maxDiasNoMes = (int) date('t', strtotime($primeiroDiaCompetencia));

        $dataEmissaoHoje = date('Y-m-d');

        // Busca todas as recorrências ativas que ainda não possuem flag preenchida para esta competência
        $query = "SELECT R.*, S.nome_servico, C.nome as nome_cliente 
                  FROM Recorrencias R
                  JOIN Servicos S ON R.id_servico = S.id_servico
                  JOIN Clientes C ON R.id_cliente = C.id_cliente
                  WHERE R.data_inicio_cobranca <= '$ultimoDiaCompetencia'
                    AND (R.data_fim_cobranca IS NULL OR R.data_fim_cobranca >= '$primeiroDiaCompetencia')
                    AND (R.ultima_fatura_gerada_mes_ano IS NULL OR R.ultima_fatura_gerada_mes_ano != '$mesAnoSafe')
                  ORDER BY R.id_cliente ASC, R.id_recorrencia ASC";

        $result = DBExecute($link, $query);

        $faturasCriadas = [];
        $faturasJaExistentesSincronizadas = 0;
        $erros = [];
        $totalValorGerado = 0.00;
        $totalFaturas = 0;

        if ($result && mysqli_num_rows($result) > 0) {
            while ($rec = mysqli_fetch_assoc($result)) {
                $idRec = (int) $rec['id_recorrencia'];
                $idCliente = (int) $rec['id_cliente'];
                $idServico = (int) $rec['id_servico'];
                $qtd = (int) ($rec['quantidade'] ?? 1);
                $vlrUnit = (float) ($rec['valor_sugerido_recorrencia'] ?? 0.00);
                $vlrTotalFatura = $qtd * $vlrUnit;

                // 1. Dupla camada de proteção contra duplicidade:
                // Checa se já existe fatura ativa neste mês/ano com este id_recorrencia vinculado
                $qCheckExistente = "SELECT F.id_fatura 
                                    FROM ItensFatura I
                                    JOIN Faturas F ON I.id_fatura = F.id_fatura
                                    WHERE I.id_recorrencia = $idRec
                                      AND (
                                        (MONTH(F.data_vencimento) = $mes AND YEAR(F.data_vencimento) = $ano)
                                        OR (MONTH(F.data_emissao) = $mes AND YEAR(F.data_emissao) = $ano)
                                      )
                                      AND F.status != 'Cancelada'
                                    LIMIT 1";
                $resExistente = DBExecute($link, $qCheckExistente);

                if ($resExistente && mysqli_num_rows($resExistente) > 0) {
                    // Já existe fatura manual/anterior gerada neste mês para este contrato.
                    // Atualiza a flag na recorrência para ficar consistente e não gera duplicada.
                    DBExecute($link, "UPDATE Recorrencias SET ultima_fatura_gerada_mes_ano = '$mesAnoSafe' WHERE id_recorrencia = $idRec");
                    $faturasJaExistentesSincronizadas++;
                    continue;
                }

                // 2. Calcula o dia de vencimento
                $diaVenc = !empty($rec['dia_vencimento']) ? (int) $rec['dia_vencimento'] : 0;
                if ($diaVenc <= 0 || $diaVenc > 31) {
                    // Fallback para o dia da data de início de cobrança
                    $diaVenc = (int) date('d', strtotime($rec['data_inicio_cobranca']));
                }

                // Ajusta dia caso o mês tenha menos dias (ex: dia 31 em fevereiro ou abril)
                if ($diaVenc > $maxDiasNoMes) {
                    $diaVenc = $maxDiasNoMes;
                }

                $dataVencimento = sprintf('%04d-%02d-%02d', $ano, $mes, $diaVenc);

                // 3. Cria a Fatura individual para o contrato
                $qFatura = "INSERT INTO Faturas (id_cliente, data_emissao, data_vencimento, valor_total_fatura, status, possui_nfse, desconto_valor, desconto_tipo, permitir_pagamento_parcial)
                            VALUES ($idCliente, '$dataEmissaoHoje', '$dataVencimento', $vlrTotalFatura, 'Em Aberto', 0, '0.00', 'percentual', '0')";

                $resFatura = DBExecute($link, $qFatura);
                if ($resFatura) {
                    $newFaturaId = mysqli_insert_id($link);

                    // 4. Monta a tag/descrição do item
                    $tag = !empty($rec['descricao_personalizada'])
                        ? $rec['descricao_personalizada']
                        : "Mensalidade - " . $rec['nome_servico'] . " (" . $mesAnoSafe . ")";
                    $tagSafe = mysqli_real_escape_string($link, $tag);

                    // 5. Insere o item na tabela ItensFatura
                    $qItem = "INSERT INTO ItensFatura (id_fatura, id_servico, quantidade, valor_unitario, tag, id_recorrencia)
                              VALUES ($newFaturaId, $idServico, $qtd, $vlrUnit, '$tagSafe', $idRec)";

                    $resItem = DBExecute($link, $qItem);

                    if ($resItem) {
                        // 6. Atualiza a flag na recorrência para evitar duplicidade
                        DBExecute($link, "UPDATE Recorrencias SET ultima_fatura_gerada_mes_ano = '$mesAnoSafe' WHERE id_recorrencia = $idRec");

                        $totalFaturas++;
                        $totalValorGerado += $vlrTotalFatura;

                        $faturasCriadas[] = [
                            'id_fatura' => $newFaturaId,
                            'id_recorrencia' => $idRec,
                            'id_cliente' => $idCliente,
                            'cliente_nome' => $rec['nome_cliente'],
                            'servico_nome' => $rec['nome_servico'],
                            'valor' => $vlrTotalFatura,
                            'vencimento' => $dataVencimento
                        ];
                    } else {
                        // Se falhou ao inserir o item, remove a fatura criada para não deixar fatura vazia
                        DBExecute($link, "DELETE FROM Faturas WHERE id_fatura = $newFaturaId");
                        $err = "Erro ao inserir item para Fatura ID $newFaturaId (Recorrência ID $idRec): " . mysqli_error($link);
                        error_log($err);
                        $erros[] = $err;
                    }
                } else {
                    $err = "Erro ao criar fatura para Recorrência ID $idRec (Cliente ID $idCliente): " . mysqli_error($link);
                    error_log($err);
                    $erros[] = $err;
                }
            }
        }

        // 7. Grava log na tabela CronLogs
        $statusLog = count($erros) > 0 ? ($totalFaturas > 0 ? 'aviso' : 'erro') : 'sucesso';
        $detalhesJson = json_encode([
            'competencia' => $mesAnoSafe,
            'faturas_criadas' => $faturasCriadas,
            'faturas_ja_existentes_sincronizadas' => $faturasJaExistentesSincronizadas,
            'erros' => $erros
        ], JSON_UNESCAPED_UNICODE);

        $detalhesSafe = mysqli_real_escape_string($link, $detalhesJson);
        $origemSafe = mysqli_real_escape_string($link, $origem);

        @DBExecute($link, "INSERT INTO CronLogs (data_execucao, tipo_tarefa, status, faturas_geradas, valor_total_gerado, detalhes_json, origem)
                          VALUES (NOW(), 'faturas_recorrencias', '$statusLog', $totalFaturas, $totalValorGerado, '$detalhesSafe', '$origemSafe')");

        DBClose($link);

        $msg = "";
        if ($totalFaturas > 0) {
            $msg = "Geração concluída: $totalFaturas fatura(s) gerada(s) totalizando R$ " . number_format($totalValorGerado, 2, ',', '.') . " para a competência $mesAnoSafe.";
        } elseif ($faturasJaExistentesSincronizadas > 0) {
            $msg = "Nenhuma fatura pendente para a competência $mesAnoSafe ($faturasJaExistentesSincronizadas contrato(s) já possuíam faturas geradas e foram sincronizados).";
        } else {
            $msg = "Nenhuma fatura pendente de geração para a competência $mesAnoSafe.";
        }

        return [
            'success' => count($erros) === 0,
            'competencia' => $mesAnoSafe,
            'faturas_geradas' => $totalFaturas,
            'faturas_ja_existentes_sincronizadas' => $faturasJaExistentesSincronizadas,
            'valor_total' => $totalValorGerado,
            'faturas' => $faturasCriadas,
            'erros' => $erros,
            'message' => $msg
        ];
    }
}
?>
