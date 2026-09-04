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

    /**
     * Retorna a sugestão de parâmetros de IBS/CBS para a Reforma Tributária.
     */
    public static function getCorrelacaoReforma(string $cTribNac, ?string $cNbs = null, $link = null): array
    {
        if ($link) {
            $safeTrib = mysqli_real_escape_string($link, preg_replace('/\D/', '', $cTribNac));
            $safeNbs = mysqli_real_escape_string($link, preg_replace('/\D/', '', $cNbs ?: ''));
            
            $whereNbs = !empty($safeNbs) ? "AND (codigo_nbs = '{$safeNbs}' OR codigo_nbs IS NULL)" : "";
            $query = "SELECT cst_ibs_cbs, classificacao_trib, indicador_operacao FROM TribRefCorrelacaoIbsCbs WHERE codigo_trib_nac = '{$safeTrib}' {$whereNbs} ORDER BY id ASC LIMIT 1";
            $res = @DBExecute($link, $query);
            
            if ($res && mysqli_num_rows($res) > 0) {
                $row = mysqli_fetch_assoc($res);
                return [
                    'cst_ibs_cbs' => $row['cst_ibs_cbs'] ?: '000',
                    'classificacao_trib_ibs_cbs' => $row['classificacao_trib'] ?: '000000',
                    'indicador_operacao' => $row['indicador_operacao'] ?: '050101'
                ];
            }
        }

        // Padrão Simples Nacional / Operação Tributável Regular
        return [
            'cst_ibs_cbs' => '000',
            'classificacao_trib_ibs_cbs' => '000000',
            'indicador_operacao' => '050101'
        ];
    }
}
