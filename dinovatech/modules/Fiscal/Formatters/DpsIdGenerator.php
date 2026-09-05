<?php
namespace Dinovatech\Modules\Fiscal\Formatters;

/**
 * Gerador de identificadores oficiais padronizados pela NFS-e Nacional / DPS.
 */
class DpsIdGenerator
{
    /**
     * Gera o identificador oficial de 45 posições da DPS:
     * "DPS" (3) + Cód.Mun (7) + Tipo Inscr (1) + Inscr Federal (14) + Série (5) + Número (15)
     */
    public static function generateDpsId(string $codMunIbge, string $cpfCnpj, string $serie, int $numeroDps): string
    {
        $codMun = str_pad(substr(preg_replace('/\D/', '', $codMunIbge), 0, 7), 7, '0', STR_PAD_LEFT);
        $cleanDoc = preg_replace('/\D/', '', $cpfCnpj);
        
        $tipoInscr = (strlen($cleanDoc) > 11) ? '2' : '1'; // 2=CNPJ (14 dígitos), 1=CPF (11 dígitos)
        $inscrFederal = str_pad(substr($cleanDoc, 0, 14), 14, '0', STR_PAD_LEFT);
        
        $cleanSerie = preg_replace('/\D/', '', $serie);
        $seriePad = str_pad(substr($cleanSerie ?: '1', 0, 5), 5, '0', STR_PAD_LEFT);
        
        $numPad = str_pad((string)$numeroDps, 15, '0', STR_PAD_LEFT);

        return "DPS" . $codMun . $tipoInscr . $inscrFederal . $seriePad . $numPad;
    }

    /**
     * Gera o identificador oficial do Pedido de Registro de Evento (Cancelamento):
     * "PRE" (3) + Chave de Acesso da NFS-e (50) + Tipo do Evento (6)
     */
    public static function generatePedidoEventoId(string $chaveNfse, string $tipoEvento = '101103'): string
    {
        $cleanChave = preg_replace('/\D/', '', $chaveNfse);
        $cleanChave = str_pad(substr($cleanChave, 0, 50), 50, '0', STR_PAD_LEFT);
        $cleanTipo = str_pad(substr($tipoEvento, 0, 6), 6, '0', STR_PAD_LEFT);

        return "PRE" . $cleanChave . $cleanTipo;
    }
}
