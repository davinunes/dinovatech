<?php
namespace Dinovatech\Modules\Fiscal\Tests;

require_once __DIR__ . '/../bootstrap.php';

use Dinovatech\Modules\Fiscal\Parsers\NacionalResponseParser;

class NacionalResponseParserTest
{
    public function testParseGerarNfseResposta(): bool
    {
        $fixturePath = __DIR__ . '/../../../../doc_issdf/novo_padrao_nacional/modelos-xml/GerarNfseResposta.xml';
        if (!file_exists($fixturePath)) {
            echo "[SKIP] Fixture GerarNfseResposta.xml não encontrada.\n";
            return true;
        }

        $xml = file_get_contents($fixturePath);
        $parser = new NacionalResponseParser();
        $result = $parser->parseEmissao($xml, '<xmlEnvio/>', 'DPS530010816173371400010100001000000000000001');

        $passed = ($result->isSuccess());
        echo $passed ? "[PASS] testParseGerarNfseResposta\n" : "[FAIL] testParseGerarNfseResposta\n";
        return $passed;
    }

    public function testParseConsultarUrlNfseResposta(): bool
    {
        $fixturePath = __DIR__ . '/../../../../doc_issdf/novo_padrao_nacional/modelos-xml/ConsultarUrlNfseResposta.xml';
        if (!file_exists($fixturePath)) {
            echo "[SKIP] Fixture ConsultarUrlNfseResposta.xml não encontrada.\n";
            return true;
        }

        $xml = file_get_contents($fixturePath);
        $parser = new NacionalResponseParser();
        $result = $parser->parseConsultarUrl($xml);

        $passed = ($result->success);
        echo $passed ? "[PASS] testParseConsultarUrlNfseResposta\n" : "[FAIL] testParseConsultarUrlNfseResposta\n";
        return $passed;
    }

    public function testParseErroRetorno(): bool
    {
        $xmlErro = '<ConsultarNfseResposta xmlns="http://www.sped.fazenda.gov.br/nfse">
            <ListaMensagemRetorno>
                <MensagemRetorno>
                    <Codigo>E001</Codigo>
                    <Mensagem>CNPJ do prestador nao localizado na base do ISS-DF.</Mensagem>
                    <Correcao>Verifique o cadastro tributario municipal.</Correcao>
                </MensagemRetorno>
            </ListaMensagemRetorno>
        </ConsultarNfseResposta>';

        $parser = new NacionalResponseParser();
        $result = $parser->parseEmissao($xmlErro, '<envio/>', 'DPS000');

        $passed = (!$result->isSuccess() && count($result->erros) > 0 && strpos($result->details, 'E001') !== false);
        echo $passed ? "[PASS] testParseErroRetorno\n" : "[FAIL] testParseErroRetorno\n";
        return $passed;
    }

    public function run(): bool
    {
        echo "=== Executando Testes de NacionalResponseParser ===\n";
        $r1 = $this->testParseGerarNfseResposta();
        $r2 = $this->testParseConsultarUrlNfseResposta();
        $r3 = $this->testParseErroRetorno();
        return $r1 && $r2 && $r3;
    }
}

if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    $t = new NacionalResponseParserTest();
    $ok = $t->run();
    exit($ok ? 0 : 1);
}
