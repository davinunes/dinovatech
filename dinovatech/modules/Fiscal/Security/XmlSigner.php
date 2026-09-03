<?php
namespace Dinovatech\Modules\Fiscal\Security;

use DOMDocument;
use DOMXPath;
use Exception;

/**
 * Assinador digital XMLDSig estritamente aderente ao Padrão Nacional da NFS-e.
 */
class XmlSigner
{
    private CertificateManager $certManager;

    public function __construct(CertificateManager $certManager)
    {
        $this->certManager = $certManager;
    }

    /**
     * Assina digitalmente um elemento identificado por Id dentro do XML.
     * 
     * @param string $xmlString O XML original contendo o elemento com o Id.
     * @param string $targetId O Id do elemento alvo a ser assinado (sem o caractere '#').
     * @return string O XML assinado com a tag <Signature> inserida no nó pai do elemento.
     */
    public function sign(string $xmlString, string $targetId): string
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = false;

        if (!@$dom->loadXML($xmlString)) {
            throw new Exception("Falha ao carregar XML para assinatura digital.");
        }

        $xpath = new DOMXPath($dom);
        // Localiza o elemento com o Id informado (independente de namespace)
        $nodeList = $xpath->query("//*[@Id='{$targetId}']");
        if ($nodeList->length === 0) {
            throw new Exception("Elemento com Id '{$targetId}' não foi encontrado no XML.");
        }

        /** @var \DOMElement $targetNode */
        $targetNode = $nodeList->item(0);

        // 1. Canonicalização C14N exclusiva do nó alvo para cálculo do Digest
        $canonicalizedTarget = $targetNode->C14N(false, false, null, null);
        $digestValue = base64_encode(sha1($canonicalizedTarget, true));

        // 2. Montagem do <SignedInfo>
        $uriRef = "#" . $targetId;
        $signedInfo = '<SignedInfo xmlns="http://www.w3.org/2000/09/xmldsig#">' .
            '<CanonicalizationMethod Algorithm="http://www.w3.org/TR/2001/REC-xml-c14n-20010315"/>' .
            '<SignatureMethod Algorithm="http://www.w3.org/2000/09/xmldsig#rsa-sha1"/>' .
            '<Reference URI="' . $uriRef . '">' .
            '<Transforms>' .
            '<Transform Algorithm="http://www.w3.org/2000/09/xmldsig#enveloped-signature"/>' .
            '<Transform Algorithm="http://www.w3.org/TR/2001/REC-xml-c14n-20010315"/>' .
            '</Transforms>' .
            '<DigestMethod Algorithm="http://www.w3.org/2000/09/xmldsig#sha1"/>' .
            '<DigestValue>' . $digestValue . '</DigestValue>' .
            '</Reference>' .
            '</SignedInfo>';

        // 3. Canonicalização do SignedInfo
        $domSignedInfo = new DOMDocument();
        $domSignedInfo->loadXML($signedInfo);
        $canonicalSignedInfo = $domSignedInfo->C14N(false, false, null, null);

        // 4. Assinatura criptográfica com a chave privada RSA
        $signatureBinary = '';
        $pkey = $this->certManager->getPrivateKey();
        if (!openssl_sign($canonicalSignedInfo, $signatureBinary, $pkey, OPENSSL_ALGO_SHA1)) {
            throw new Exception("Erro ao executar openssl_sign: " . openssl_error_string());
        }
        $signatureValue = base64_encode($signatureBinary);

        // 5. Obtenção do certificado X.509 limpo em Base64
        $cleanCert = $this->certManager->getCleanCertificate();

        // 6. Montagem da tag <Signature>
        $signatureXml = '<Signature xmlns="http://www.w3.org/2000/09/xmldsig#">' .
            $canonicalSignedInfo .
            '<SignatureValue>' . $signatureValue . '</SignatureValue>' .
            '<KeyInfo>' .
            '<X509Data>' .
            '<X509Certificate>' . $cleanCert . '</X509Certificate>' .
            '</X509Data>' .
            '</KeyInfo>' .
            '</Signature>';

        // 7. Anexação da assinatura no nó pai do elemento assinado (ex: dentro de <DPS> ou <pedRegEvento>)
        $signatureFragment = $dom->createDocumentFragment();
        $signatureFragment->appendXML($signatureXml);

        $parentNode = $targetNode->parentNode ?: $dom->documentElement;
        $parentNode->appendChild($signatureFragment);

        $finalXml = $dom->saveXML();

        // Remove declarações duplicadas ou indesejadas de XML se necessário
        $finalXml = preg_replace('/<\?xml.*?\?>/', '', $finalXml);
        return trim($finalXml);
    }
}
