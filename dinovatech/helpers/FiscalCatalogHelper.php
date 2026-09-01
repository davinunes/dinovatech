<?php

class FiscalCatalogHelper
{
    private static ?array $catalogData = null;

    /**
     * Carrega os dados do catálogo fiscal JSON.
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
     * Retorna a lista de CNAEs cadastrados.
     */
    public static function getCnaes(): array
    {
        $catalog = self::getCatalog();
        return $catalog['cnaes'] ?? [];
    }

    /**
     * Retorna a lista de Atividades Municipais / Itens LC 116.
     */
    public static function getAtividades(): array
    {
        $catalog = self::getCatalog();
        return $catalog['atividades_municipio'] ?? [];
    }

    /**
     * Busca uma atividade municipal pelo código de tributação ou item da LC 116.
     */
    public static function getAtividadeByCodigo(string $codigo): ?array
    {
        $atividades = self::getAtividades();
        $codigoLimpo = trim($codigo);

        foreach ($atividades as $ativ) {
            if ($ativ['codigo_tributacao'] === $codigoLimpo || $ativ['item_lc116'] === $codigoLimpo) {
                return $ativ;
            }
        }
        return null;
    }
}
