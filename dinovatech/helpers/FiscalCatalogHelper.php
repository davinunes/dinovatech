<?php

class FiscalCatalogHelper
{
    private static ?array $catalogData = null;

    /**
     * Carrega a Ficha Cadastral da Empresa (CNAEs e alíquotas do CNPJ no ISS-DF).
     */
    public static function getCatalog(): array
    {
        if (self::$catalogData !== null) {
            return self::$catalogData;
        }

        $jsonPath = dirname(__DIR__) . '/data/fiscal_catalog.json';
        if (file_exists($jsonPath)) {
            $content = file_get_contents($jsonPath);
            $data = json_decode($content, true);
            if (is_array($data)) {
                self::$catalogData = $data;
                return self::$catalogData;
            }
        }

        self::$catalogData = [
            'cnaes' => [],
            'atividades_municipio' => []
        ];
        return self::$catalogData;
    }

    /**
     * Retorna a lista de CNAEs cadastrados na Ficha Cadastral da empresa.
     */
    public static function getCnaes(): array
    {
        $catalog = self::getCatalog();
        return $catalog['cnaes'] ?? [];
    }

    /**
     * Retorna a lista de Atividades Municipais ativas na Ficha Cadastral da empresa.
     */
    public static function getAtividades(): array
    {
        $catalog = self::getCatalog();
        return $catalog['atividades_municipio'] ?? [];
    }

    /**
     * Busca uma atividade na Ficha Cadastral pelo código de tributação municipal ou item da LC 116.
     */
    public static function getAtividadeByCodigo(string $codigo): ?array
    {
        $atividades = self::getAtividades();
        $codigoLimpo = trim($codigo);

        foreach ($atividades as $ativ) {
            if ($ativ['codigo_tributacao'] === $codigoLimpo || $ativ['item_lc116'] === $codigoLimpo || ($ativ['codigo_tributacao_nacional'] ?? '') === $codigoLimpo) {
                return $ativ;
            }
        }
        return null;
    }

    /**
     * Busca códigos de Tributação Nacional (cTribNac de 6 dígitos) com suporte a busca no DB TribRefTributacaoNacional.
     */
    public static function searchTributacaoNacional(string $termo = '', $link = null, int $limit = 30): array
    {
        $termoLimpo = trim($termo);
        $results = [];

        if ($link) {
            $safeTermo = mysqli_real_escape_string($link, $termoLimpo);
            $where = !empty($safeTermo) 
                ? "WHERE codigo_trib_nac LIKE '%{$safeTermo}%' OR item_lc116 LIKE '%{$safeTermo}%' OR descricao LIKE '%{$safeTermo}%'" 
                : "";
            
            $query = "SELECT codigo_trib_nac, item_lc116, descricao, aliquota_padrao FROM TribRefTributacaoNacional {$where} LIMIT {$limit}";
            $res = @DBExecute($link, $query);
            
            if ($res && mysqli_num_rows($res) > 0) {
                while ($row = mysqli_fetch_assoc($res)) {
                    $results[] = [
                        'codigo_trib_nac' => $row['codigo_trib_nac'],
                        'item_lc116' => $row['item_lc116'],
                        'descricao' => $row['descricao'],
                        'aliquota' => (float)$row['aliquota_padrao']
                    ];
                }
                return $results;
            }
        }

        // Fallback Ficha Cadastral
        $atividades = self::getAtividades();
        foreach ($atividades as $ativ) {
            if (empty($termoLimpo) || 
                stripos($ativ['codigo_tributacao_nacional'] ?? '', $termoLimpo) !== false || 
                stripos($ativ['item_lc116'] ?? '', $termoLimpo) !== false || 
                stripos($ativ['descricao'] ?? '', $termoLimpo) !== false ||
                stripos($ativ['nome_curto'] ?? '', $termoLimpo) !== false) {
                
                $results[] = [
                    'codigo_trib_nac' => $ativ['codigo_tributacao_nacional'] ?? '010701',
                    'item_lc116' => $ativ['item_lc116'],
                    'descricao' => $ativ['descricao'],
                    'aliquota' => (float)$ativ['aliquota']
                ];
            }
        }

        return array_slice($results, 0, $limit);
    }

    private static ?array $correlacaoData = null;

    /**
     * Carrega os dados indexados da Matriz de Correlação Oficial (IBS/CBS).
     */
    public static function getCorrelacoesData(): array
    {
        if (self::$correlacaoData !== null) {
            return self::$correlacaoData;
        }

        $jsonPath = dirname(__DIR__) . '/data/correlacao_ibscbs.json';
        if (file_exists($jsonPath)) {
            $content = file_get_contents($jsonPath);
            $data = json_decode($content, true);
            if (is_array($data)) {
                self::$correlacaoData = $data;
                return self::$correlacaoData;
            }
        }

        self::$correlacaoData = [];
        return self::$correlacaoData;
    }

    /**
     * Retorna a lista de NBS válidos para um determinado Código de Tributação Nacional (cTribNac).
     */
    public static function getNbsDisponiveisPorTribNac(string $cTribNac, $link = null): array
    {
        $cTribClean = str_pad(preg_replace('/\D/', '', $cTribNac), 6, '0', STR_PAD_LEFT);
        if (empty($cTribClean) || $cTribClean === '000000') {
            return [];
        }

        $lista = [];

        // 1. Tenta buscar no DB
        if ($link) {
            $safeTrib = mysqli_real_escape_string($link, $cTribClean);
            $query = "SELECT c.codigo_nbs, n.descricao as descricao_nbs, c.cst_ibs_cbs, c.classificacao_trib, c.indicador_operacao 
                      FROM TribRefCorrelacaoIbsCbs c
                      LEFT JOIN TribRefNbs n ON c.codigo_nbs = n.codigo_nbs
                      WHERE c.codigo_trib_nac = '{$safeTrib}'
                      ORDER BY c.codigo_nbs ASC";
            $res = @DBExecute($link, $query);
            if ($res && mysqli_num_rows($res) > 0) {
                while ($row = mysqli_fetch_assoc($res)) {
                    $lista[] = [
                        'codigo_nbs' => $row['codigo_nbs'],
                        'descricao_nbs' => $row['descricao_nbs'] ?: '',
                        'cst_ibs_cbs' => $row['cst_ibs_cbs'] ?: '000',
                        'classificacao_trib' => $row['classificacao_trib'] ?: '000001',
                        'indicador_operacao' => $row['indicador_operacao'] ?: '100301'
                    ];
                }
                return $lista;
            }
        }

        // 2. Fallback via JSON indexado
        $allData = self::getCorrelacoesData();
        if (isset($allData[$cTribClean]['correlacoes'])) {
            return $allData[$cTribClean]['correlacoes'];
        }

        return [];
    }

    /**
     * Valida se a combinação (cTribNac, cNbs, cClassTrib, cIndOp, cst) é válida conforme a matriz oficial da Nota Control.
     */
    public static function validarCorrelacao(
        string $cTribNac,
        ?string $cNbs,
        ?string $cClassTrib,
        ?string $cIndOp,
        ?string $cst = null,
        $link = null
    ): array {
        $cTribClean = str_pad(preg_replace('/\D/', '', $cTribNac), 6, '0', STR_PAD_LEFT);
        $cNbsClean = !empty($cNbs) ? str_pad(preg_replace('/\D/', '', $cNbs), 9, '0', STR_PAD_LEFT) : '';
        $cClassClean = !empty($cClassTrib) ? str_pad(preg_replace('/\D/', '', $cClassTrib), 6, '0', STR_PAD_LEFT) : '000001';
        $cIndOpClean = !empty($cIndOp) ? str_pad(preg_replace('/\D/', '', $cIndOp), 6, '0', STR_PAD_LEFT) : '100301';
        $cstClean = !empty($cst) ? str_pad(preg_replace('/\D/', '', $cst), 3, '0', STR_PAD_LEFT) : '000';

        if (empty($cTribClean) || $cTribClean === '000000') {
            return [
                'valido' => false,
                'mensagem' => 'Código de Tributação Nacional (cTribNac) é obrigatório e deve ter 6 dígitos.',
                'sugestao' => null
            ];
        }

        // Obtém correlações válidas para esse cTribNac
        $opcoesValidas = self::getNbsDisponiveisPorTribNac($cTribClean, $link);

        if (empty($opcoesValidas)) {
            // Não há restrição conhecida ou código genérico
            return [
                'valido' => true,
                'mensagem' => 'Código nacional aceito sem restrição de matriz pré-definida.',
                'sugestao' => null
            ];
        }

        // Procura correspondência exata
        foreach ($opcoesValidas as $op) {
            $opNbs = str_pad($op['codigo_nbs'], 9, '0', STR_PAD_LEFT);
            $opClass = str_pad($op['classificacao_trib'], 6, '0', STR_PAD_LEFT);
            $opInd = str_pad($op['indicador_operacao'], 6, '0', STR_PAD_LEFT);
            $opCst = str_pad($op['cst_ibs_cbs'], 3, '0', STR_PAD_LEFT);

            $matchNbs = empty($cNbsClean) || ($opNbs === $cNbsClean);
            $matchClass = ($opClass === $cClassClean);
            $matchInd = ($opInd === $cIndOpClean);
            $matchCst = empty($cstClean) || ($opCst === $cstClean);

            if ($matchNbs && $matchClass && $matchInd && $matchCst) {
                return [
                    'valido' => true,
                    'mensagem' => 'Parâmetros fiscais 100% correlacionados com a matriz oficial.',
                    'sugestao' => $op
                ];
            }
        }

        // Sugestão padrão (primeira tupla compatível com o NBS ou primeira tupla do cTribNac)
        $sugestao = $opcoesValidas[0];
        if (!empty($cNbsClean)) {
            foreach ($opcoesValidas as $op) {
                if (str_pad($op['codigo_nbs'], 9, '0', STR_PAD_LEFT) === $cNbsClean) {
                    $sugestao = $op;
                    break;
                }
            }
        }

        return [
            'valido' => false,
            'mensagem' => "[EM062] Incompatibilidade fiscal: O conjunto cTribNac ({$cTribClean}), NBS ({$cNbsClean}), cClassTrib ({$cClassClean}) e cIndOp ({$cIndOpClean}) não possui correlação na matriz oficial da Reforma Tributária.",
            'sugestao' => $sugestao,
            'opcoes_validas' => $opcoesValidas
        ];
    }

    /**
     * Retorna a sugestão de parâmetros de IBS/CBS para a Reforma Tributária.
     */
    public static function getCorrelacaoReforma(string $cTribNac, ?string $cNbs = null, $link = null): array
    {
        $cTribClean = str_pad(preg_replace('/\D/', '', $cTribNac), 6, '0', STR_PAD_LEFT);
        $cNbsClean = !empty($cNbs) ? str_pad(preg_replace('/\D/', '', $cNbs), 9, '0', STR_PAD_LEFT) : '';

        if (!empty($cTribClean) && $cTribClean !== '000000') {
            $opcoes = self::getNbsDisponiveisPorTribNac($cTribClean, $link);
            if (!empty($opcoes)) {
                if (!empty($cNbsClean)) {
                    foreach ($opcoes as $op) {
                        if (str_pad($op['codigo_nbs'], 9, '0', STR_PAD_LEFT) === $cNbsClean) {
                            return [
                                'codigo_nbs' => $op['codigo_nbs'],
                                'cst_ibs_cbs' => $op['cst_ibs_cbs'] ?: '000',
                                'classificacao_trib_ibs_cbs' => $op['classificacao_trib'] ?: '000001',
                                'indicador_operacao' => $op['indicador_operacao'] ?: '100301'
                            ];
                        }
                    }
                }

                // Retorna a primeira válida como sugestão padrão
                $primeira = $opcoes[0];
                return [
                    'codigo_nbs' => $primeira['codigo_nbs'],
                    'cst_ibs_cbs' => $primeira['cst_ibs_cbs'] ?: '000',
                    'classificacao_trib_ibs_cbs' => $primeira['classificacao_trib'] ?: '000001',
                    'indicador_operacao' => $primeira['indicador_operacao'] ?: '100301'
                ];
            }
        }

        // Padrão Simples Nacional / Operação Tributável Regular
        return [
            'codigo_nbs' => $cNbsClean ?: '',
            'cst_ibs_cbs' => '000',
            'classificacao_trib_ibs_cbs' => '000001',
            'indicador_operacao' => '100301'
        ];
    }
}
