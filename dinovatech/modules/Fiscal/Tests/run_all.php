<?php
// dinovatech/modules/Fiscal/Tests/run_all.php

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/DpsIdGeneratorTest.php';
require_once __DIR__ . '/DpsXmlBuilderTest.php';
require_once __DIR__ . '/NacionalResponseParserTest.php';

use Dinovatech\Modules\Fiscal\Tests\DpsIdGeneratorTest;
use Dinovatech\Modules\Fiscal\Tests\DpsXmlBuilderTest;
use Dinovatech\Modules\Fiscal\Tests\NacionalResponseParserTest;

echo "====================================================\n";
echo "  BATERIA DE TESTES - NOVO PADRÃO NACIONAL (NFS-e)  \n";
echo "====================================================\n\n";

$t1 = new DpsIdGeneratorTest();
$r1 = $t1->run();
echo "\n";

$t2 = new DpsXmlBuilderTest();
$r2 = $t2->run();
echo "\n";

$t3 = new NacionalResponseParserTest();
$r3 = $t3->run();
echo "\n";

$allPassed = ($r1 && $r2 && $r3);

echo "====================================================\n";
if ($allPassed) {
    echo "  RESULTADO: TODOS OS TESTES PASSARAM COM SUCESSO!  \n";
} else {
    echo "  RESULTADO: FALHA EM UM OU MAIS TESTES.           \n";
}
echo "====================================================\n";

if (php_sapi_name() === 'cli') {
    exit($allPassed ? 0 : 1);
}
