<?php
namespace Dinovatech\Modules\Fiscal\Tests;

require_once __DIR__ . '/../bootstrap.php';

use Dinovatech\Modules\Fiscal\Builders\DpsXmlBuilder;
use Dinovatech\Modules\Fiscal\DTOs\NfseData;
use Dinovatech\Modules\Fiscal\Formatters\DpsIdGenerator;
use DOMDocument;

class DpsXmlBuilderTest
{
    public function testBuildXmlStructure(): bool
    {
        $dto = new NfseData();
        $dto->idFatura = 123;
        $dto->ambiente = 'producao';
        $dto->serie = '1';
        $dto->numero = 1;
        $dto->dataCompetencia = '2026-09-03';
        $dto->valorServico = 150.00;
        $dto->aliquotaIss = 2.00;
        $dto->issRetido = false;

        $dto->prestadorCnpj = '61733714000101';
        $dto->prestadorInscricaoMunicipal = '0841147200111';
        $dto->prestadorRazaoSocial = 'Dinovatech Ltda';
        $dto->prestadorRegimeTributario = 'simples';
        $dto->prestadorOptanteSimples = true;
        $dto->prestadorMunicipioIbge = '5300108';

        $dto->tomadorCpfCnpj = '01691128104';
        $dto->tomadorTipoDocumento = 'CPF';
        $dto->tomadorRazaoSocial = 'Cliente Teste';
        $dto->tomadorLogradouro = 'Rua das Flores';
        $dto->tomadorNumero = '10';
        $dto->tomadorBairro = 'Asa Sul';
        $dto->tomadorMunicipioIbge = '5300108';
        $dto->tomadorUf = 'DF';
        $dto->tomadorCep = '70000000';

        $dto->discriminacao = 'Desenvolvimento de software personalizado';
        $dto->itemListaServico = '01.07';
        $dto->codigoTributacaoNacional = '010701';
        $dto->codigoTributacaoMunicipal = '0107001';
        $dto->codigoNbs = '114032110';
        $dto->municipioPrestacaoIbge = '5300108';

        $dpsId = DpsIdGenerator::generateDpsId('5300108', $dto->prestadorCnpj, $dto->serie, $dto->numero);
        $builder = new DpsXmlBuilder();
        $xml = $builder->build($dto, $dpsId);

        $hasRoot = strpos($xml, '<GerarNfseEnvio') !== false;
        $hasDps = strpos($xml, '<DPS versao="1.01">') !== false;
        $hasId = strpos($xml, "Id=\"{$dpsId}\"") !== false;
        $hasPrest = strpos($xml, '<prest>') !== false && strpos($xml, '<CNPJ>61733714000101</CNPJ>') !== false;
        $hasToma = strpos($xml, '<toma>') !== false && strpos($xml, '<CPF>01691128104</CPF>') !== false;
        $hasServ = strpos($xml, '<cTribNac>010701</cTribNac>') !== false;
        $hasIbscbs = strpos($xml, '<IBSCBS>') !== false;

        $passed = ($hasRoot && $hasDps && $hasId && $hasPrest && $hasToma && $hasServ && $hasIbscbs);

        echo $passed ? "[PASS] testBuildXmlStructure\n" : "[FAIL] testBuildXmlStructure\n";
        return $passed;
    }

    public function testBuildDpsXmlStructure(): bool
    {
        $dto = new NfseData();
        $dto->idFatura = 123;
        $dto->ambiente = 'producao';
        $dto->serie = '1';
        $dto->numero = 1;
        $dto->dataCompetencia = '2026-11-01';
        $dto->valorServico = 150.00;
        $dto->aliquotaIss = 2.00;
        $dto->prestadorCnpj = '61733714000101';
        $dto->prestadorInscricaoMunicipal = '0841147200111';
        $dto->prestadorRazaoSocial = 'Dinovatech Ltda';
        $dto->prestadorOptanteSimples = true;
        $dto->prestadorMunicipioIbge = '5300108';

        $dpsId = DpsIdGenerator::generateDpsId('5300108', $dto->prestadorCnpj, $dto->serie, $dto->numero);
        $builder = new DpsXmlBuilder();
        $xml = $builder->buildDps($dto, $dpsId);

        $hasNoGerarNfse = (strpos($xml, '<GerarNfseEnvio') === false);
        $hasDpsRoot = (strpos($xml, '<DPS versao="1.01" xmlns="http://www.sped.fazenda.gov.br/nfse">') !== false);
        $hasClosingDps = str_ends_with(trim($xml), '</DPS>');
        $hasNoCTribMunForBrasilia = (strpos($xml, '<cTribMun') === false);
        $hasNoImForBrasilia = (strpos($xml, '<IM>') === false);

        $passed = ($hasNoGerarNfse && $hasDpsRoot && $hasClosingDps && $hasNoCTribMunForBrasilia && $hasNoImForBrasilia);
        echo $passed ? "[PASS] testBuildDpsXmlStructure\n" : "[FAIL] testBuildDpsXmlStructure\n";
        return $passed;
    }

    public function run(): bool
    {
        echo "=== Executando Testes de DpsXmlBuilder ===\n";
        $ok1 = $this->testBuildXmlStructure();
        $ok2 = $this->testBuildDpsXmlStructure();
        return $ok1 && $ok2;
    }
}

if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    $t = new DpsXmlBuilderTest();
    $ok = $t->run();
    exit($ok ? 0 : 1);
}
