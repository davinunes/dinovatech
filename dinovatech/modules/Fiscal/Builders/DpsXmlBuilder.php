<?php
namespace Dinovatech\Modules\Fiscal\Builders;

use Dinovatech\Modules\Fiscal\DTOs\NfseData;
use Dinovatech\Modules\Fiscal\Formatters\DpsIdGenerator;

class DpsXmlBuilder
{
    /**
     * Monta o XML da DPS para o método GerarNfse.
     *
     * Versão da DPS:
     *  - 1.00: sem o grupo IBS/CBS — compatível com todos os ambientes.
     *          Usar para homologação, onde o servidor pode ainda não suportar 1.01.
     *  - 1.01: com o grupo IBS/CBS — apenas quando o servidor aceitar (produção).
     *
     * @param NfseData $data
     * @param string $dpsId ID de 45 chars
     * @return string XML não-assinado
     */
    public function build(NfseData $data, string $dpsId): string
    {
        $tpAmb = ($data->ambiente === 'producao') ? '1' : '2';
        $dhEmi = date('Y-m-d\TH:i:sP');
        $verAplic = 'Dinovatech_1.0';
        $serie = $data->serie;
        $nDPS = $data->numero;
        $dCompet = $data->dataCompetencia ?: date('Y-m-d');
        $cLocEmi = $data->prestadorMunicipioIbge ?: '5300108';

        // Prestador
        $prestCnpj = preg_replace('/\D/', '', $data->prestadorCnpj);
        $prestIm = preg_replace('/\D/', '', $data->prestadorInscricaoMunicipal);
        $opSimpNac = $data->prestadorOptanteSimples ? '3' : '1'; // 3=Optante ME/EPP, 1=Não Optante

        // Tomador
        $tomaXml = '';
        if (!empty($data->tomadorCpfCnpj)) {
            $docClean = preg_replace('/\D/', '', $data->tomadorCpfCnpj);
            $docTag = (strlen($docClean) > 11) ? "<CNPJ>{$docClean}</CNPJ>" : "<CPF>{$docClean}</CPF>";
            $xNome = htmlspecialchars($this->sanitizeText($data->tomadorRazaoSocial), ENT_XML1, 'UTF-8');

            $endXml = '';
            if (!empty($data->tomadorLogradouro) && !empty($data->tomadorCep)) {
                $cMunToma = preg_replace('/\D/', '', $data->tomadorMunicipioIbge ?: '5300108');
                $cepToma = str_pad(preg_replace('/\D/', '', $data->tomadorCep), 8, '0', STR_PAD_LEFT);
                $xLgr = htmlspecialchars($this->sanitizeText($data->tomadorLogradouro), ENT_XML1, 'UTF-8');
                $nro = htmlspecialchars($this->sanitizeText($data->tomadorNumero ?: 'S/N'), ENT_XML1, 'UTF-8');
                $xBairro = htmlspecialchars($this->sanitizeText($data->tomadorBairro ?: 'Centro'), ENT_XML1, 'UTF-8');

                $endXml = "<end>
                    <endNac>
                        <cMun>{$cMunToma}</cMun>
                        <CEP>{$cepToma}</CEP>
                    </endNac>
                    <xLgr>{$xLgr}</xLgr>
                    <nro>{$nro}</nro>
                    <xBairro>{$xBairro}</xBairro>
                </end>";
            }

            $foneXml = '';
            if (!empty($data->tomadorTelefone)) {
                $foneClean = preg_replace('/\D/', '', $data->tomadorTelefone);
                if (strlen($foneClean) >= 6) {
                    $foneXml = "<fone>{$foneClean}</fone>";
                }
            }

            $emailXml = '';
            if (!empty($data->tomadorEmail) && filter_var($data->tomadorEmail, FILTER_VALIDATE_EMAIL)) {
                $emailXml = "<email>" . htmlspecialchars(trim($data->tomadorEmail), ENT_XML1, 'UTF-8') . "</email>";
            }

            $tomaXml = "<toma>
                {$docTag}
                <xNome>{$xNome}</xNome>
                {$endXml}
                {$foneXml}
                {$emailXml}
            </toma>";
        }

        // Serviço
        $cLocPrestacao = preg_replace('/\D/', '', $data->municipioPrestacaoIbge ?: '5300108');
        $cTribNac = preg_replace('/\D/', '', $data->codigoTributacaoNacional ?: '010701');
        $cTribNac = str_pad($cTribNac, 6, '0', STR_PAD_LEFT);

        $cTribMun = preg_replace('/\D/', '', $data->codigoTributacaoMunicipal ?: '0107001');
        $xDescServ = htmlspecialchars($this->sanitizeText($data->discriminacao), ENT_XML1, 'UTF-8');

        $nbsXml = '';
        if (!empty($data->codigoNbs)) {
            $nbsClean = preg_replace('/\D/', '', $data->codigoNbs);
            if (strlen($nbsClean) === 9) {
                $nbsXml = "<cNBS>{$nbsClean}</cNBS>";
            }
        }

        // Valores
        $vServ = number_format($data->valorServico, 2, '.', '');
        $tpRetISSQN = $data->issRetido ? '2' : '1'; // 1=Não retido, 2=Retido tomador
        $pAliq = number_format($data->aliquotaIss, 2, '.', '');
        $tribISSQN = (string)($data->tributacaoIssqn ?: 1);

        // Em homologação usamos v1.00 (sem grupo IBS/CBS); em produção usamos v1.01 (com IBS/CBS) conforme página 108 do manual.
        $usarIbsCbs = ($data->ambiente === 'producao');
        $versaoDps  = $usarIbsCbs ? '1.01' : '1.00';

        $ibsCbsXml = '';
        if ($usarIbsCbs) {
            $cIndOp    = (!empty($data->indicadorOperacao) && $data->indicadorOperacao !== '050101') ? $data->indicadorOperacao : '100301';
            $cstIbsCbs = $data->cstIbsCbs ?: '000';
            $classTrib = (!empty($data->classificacaoTribIbsCbs) && $data->classificacaoTribIbsCbs !== '000000') ? $data->classificacaoTribIbsCbs : '000001';
            $ibsCbsXml = "<IBSCBS>
                <finNFSe>0</finNFSe>
                <indFinal>0</indFinal>
                <cIndOp>{$cIndOp}</cIndOp>
                <indDest>0</indDest>
                <valores>
                    <trib>
                        <gIBSCBS>
                            <CST>{$cstIbsCbs}</CST>
                            <cClassTrib>{$classTrib}</cClassTrib>
                        </gIBSCBS>
                    </trib>
                </valores>
            </IBSCBS>";
        }

        // Regimes tributários adicionais do Simples Nacional
        $regApTribSNXml = ($opSimpNac === '3') ? "<regApTribSN>1</regApTribSN>" : "";

        $xml = <<<XML
<GerarNfseEnvio xmlns="http://www.sped.fazenda.gov.br/nfse">
    <DPS versao="{$versaoDps}">
        <infDPS Id="{$dpsId}">
            <tpAmb>{$tpAmb}</tpAmb>
            <dhEmi>{$dhEmi}</dhEmi>
            <verAplic>{$verAplic}</verAplic>
            <serie>{$serie}</serie>
            <nDPS>{$nDPS}</nDPS>
            <dCompet>{$dCompet}</dCompet>
            <tpEmit>1</tpEmit>
            <cLocEmi>{$cLocEmi}</cLocEmi>
            <prest>
                <CNPJ>{$prestCnpj}</CNPJ>
                <IM>{$prestIm}</IM>
                <regTrib>
                    <opSimpNac>{$opSimpNac}</opSimpNac>
                    {$regApTribSNXml}
                    <regEspTrib>0</regEspTrib>
                </regTrib>
            </prest>
            {$tomaXml}
            <serv>
                <locPrest>
                    <cLocPrestacao>{$cLocPrestacao}</cLocPrestacao>
                </locPrest>
                <cServ>
                    <cTribNac>{$cTribNac}</cTribNac>
                    <cTribMun>{$cTribMun}</cTribMun>
                    <xDescServ>{$xDescServ}</xDescServ>
                    {$nbsXml}
                </cServ>
            </serv>
            <valores>
                <vServPrest>
                    <vServ>{$vServ}</vServ>
                </vServPrest>
                <trib>
                    <tribMun>
                        <tribISSQN>{$tribISSQN}</tribISSQN>
                        <tpRetISSQN>{$tpRetISSQN}</tpRetISSQN>
                        <pAliq>{$pAliq}</pAliq>
                    </tribMun>
                </trib>
            </valores>
            {$ibsCbsXml}
        </infDPS>
    </DPS>
</GerarNfseEnvio>
XML;

        return trim($xml);
    }

    private function sanitizeText(string $str): string
    {
        // Remove caracteres de controle mantendo texto puro
        $str = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $str);
        return trim($str);
    }
}
