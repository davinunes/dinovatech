<?php
namespace Dinovatech\Modules\Fiscal\Builders;

use Dinovatech\Modules\Fiscal\DTOs\NfseData;
use Dinovatech\Modules\Fiscal\Formatters\DpsIdGenerator;

class DpsXmlBuilder
{
    /**
     * Monta o XML da DPS para o método GerarNfse (SOAP) ou para a API REST Sefin Nacional.
     *
     * Versão da DPS:
     *  - 1.00: sem o grupo IBS/CBS — compatível com todos os ambientes.
     *          Usar para homologação, onde o servidor pode ainda não suportar 1.01.
     *  - 1.01: com o grupo IBS/CBS — apenas quando o servidor aceitar (produção).
     *
     * @param NfseData $data
     * @param string $dpsId ID de 45 chars
     * @param bool $wrapGerarNfseEnvio Se true, envolve com <GerarNfseEnvio> (SOAP / SEFAZ-DF). Se false, gera raiz <DPS> (REST / Sefin Nacional).
     * @return string XML não-assinado
     */
    public function build(NfseData $data, string $dpsId, bool $wrapGerarNfseEnvio = true): string
    {
        // Tolerância de clock skew com Sefin Nacional / SEFAZ (evita rejeição E0008).
        // Se dhEmi estiver mesmo frações de segundo à frente do relógio do servidor fiscal, a nota é rejeitada.
        // Recua 120 segundos para garantir margem segura de processamento.
        if (!empty($data->dataHoraEmissao)) {
            $ts = strtotime($data->dataHoraEmissao);
            $dhEmi = ($ts && $ts > (time() - 60)) ? date('Y-m-d\TH:i:sP', time() - 120) : $data->dataHoraEmissao;
        } else {
            $dhEmi = date('Y-m-d\TH:i:sP', time() - 120);
        }
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

        $xDescServ = htmlspecialchars($this->sanitizeText($data->discriminacao), ENT_XML1, 'UTF-8');

        // cTribMun é opcional no Padrão Nacional (minOccurs=0 no XSD da DPS).
        // Em Brasília (5300108), o DF não administra desdobros municipais no ADN (Sefin Nacional rejeita com E0314 se informado).
        // Deve ser omitido para DF e só enviado em outros municípios se explicitamente informado e válido.
        $cTribMunXml = '';
        if (!empty($data->codigoTributacaoMunicipal) && $cLocPrestacao !== '5300108') {
            $cTribMunClean = preg_replace('/\D/', '', (string)$data->codigoTributacaoMunicipal);
            if (!empty($cTribMunClean) && $cTribMunClean !== '0107001') {
                $cTribMunXml = "\n                    <cTribMun>{$cTribMunClean}</cTribMun>";
            }
        }

        $nbsXml = '';
        if (!empty($data->codigoNbs)) {
            $nbsClean = preg_replace('/\D/', '', $data->codigoNbs);
            if (strlen($nbsClean) === 9) {
                $nbsXml = "\n                    <cNBS>{$nbsClean}</cNBS>";
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
            $cIndOpRaw = preg_replace('/\D/', '', $data->indicadorOperacao ?: '');
            $cIndOp    = (!empty($cIndOpRaw) && $cIndOpRaw !== '50101' && $cIndOpRaw !== '050101') ? str_pad($cIndOpRaw, 6, '0', STR_PAD_LEFT) : '100301';
            
            $cstRaw    = preg_replace('/\D/', '', $data->cstIbsCbs ?: '');
            $cstIbsCbs = !empty($cstRaw) ? str_pad($cstRaw, 3, '0', STR_PAD_LEFT) : '000';
            
            $classRaw  = preg_replace('/\D/', '', $data->classificacaoTribIbsCbs ?: '');
            $classTrib = (!empty($classRaw) && $classRaw !== '0' && $classRaw !== '000000') ? str_pad($classRaw, 6, '0', STR_PAD_LEFT) : '000001';
            
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

        // Totais aproximados dos tributos (obrigatório no schema v1.01)
        // Para ME/EPP (opSimpNac=3), a regra E0712 exige pTotTribSN, vTotTrib ou pTotTrib.
        $totTribXml = ($opSimpNac === '3')
            ? "<totTrib><pTotTribSN>{$pAliq}</pTotTribSN></totTrib>"
            : "<totTrib><indTotTrib>0</indTotTrib></totTrib>";

        $dpsRootTag = $wrapGerarNfseEnvio
            ? "<DPS versao=\"{$versaoDps}\">"
            : "<DPS versao=\"{$versaoDps}\" xmlns=\"http://www.sped.fazenda.gov.br/nfse\">";

        $dpsXml = <<<XML
    {$dpsRootTag}
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
                    <cTribNac>{$cTribNac}</cTribNac>{$cTribMunXml}
                    <xDescServ>{$xDescServ}</xDescServ>{$nbsXml}
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
                    {$totTribXml}
                </trib>
            </valores>
            {$ibsCbsXml}
        </infDPS>
    </DPS>
XML;

        if (!$wrapGerarNfseEnvio) {
            $out = trim($dpsXml);
        } else {
            $out = trim(<<<XML
<GerarNfseEnvio xmlns="http://www.sped.fazenda.gov.br/nfse">
{$dpsXml}
</GerarNfseEnvio>
XML
            );
        }

        if (!mb_check_encoding($out, 'UTF-8')) {
            $out = mb_convert_encoding($out, 'UTF-8', 'ISO-8859-1, Windows-1252, UTF-8');
        }

        return $out;
    }

    /**
     * Monta o XML puro da DPS com raiz <DPS> (exigido para a API REST da Sefin Nacional).
     *
     * @param NfseData $data
     * @param string $dpsId ID de 45 chars
     * @return string XML puro da DPS não-assinado
     */
    public function buildDps(NfseData $data, string $dpsId): string
    {
        return $this->build($data, $dpsId, false);
    }

    private function sanitizeText(string $str): string
    {
        // 1. Decodifica entidades HTML pré-existentes (ex: &#xED; -> í, &iacute; -> í)
        $str = html_entity_decode($str, ENT_QUOTES | ENT_XML1, 'UTF-8');

        // 2. Garante que a codificação da string seja rigorosamente UTF-8 válida
        if (!mb_check_encoding($str, 'UTF-8')) {
            $str = mb_convert_encoding($str, 'UTF-8', 'ISO-8859-1, Windows-1252, UTF-8');
        }

        // 3. Remove caracteres de controle mantendo texto puro e quebras de linha normais
        $str = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $str);

        return trim($str);
    }
}
