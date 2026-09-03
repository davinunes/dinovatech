<?php
namespace Dinovatech\Modules\Fiscal\Tests;

require_once __DIR__ . '/../bootstrap.php';

use Dinovatech\Modules\Fiscal\Formatters\DpsIdGenerator;

class DpsIdGeneratorTest
{
    public function testGenerateDpsIdCnpj(): bool
    {
        $id = DpsIdGenerator::generateDpsId('5300108', '61.733.714/0001-01', '1', 105);
        
        // Formato esperado:
        // DPS (3) + 5300108 (7) + 1 (1) + 61733714000101 (14) + 00001 (5) + 000000000000105 (15) = 45 chars
        $expected = "DPS530010816173371400010100001000000000000105";
        
        $passed = ($id === $expected && strlen($id) === 45);
        echo $passed ? "[PASS] testGenerateDpsIdCnpj\n" : "[FAIL] testGenerateDpsIdCnpj (Obtido: $id)\n";
        return $passed;
    }

    public function testGenerateDpsIdCpf(): bool
    {
        $id = DpsIdGenerator::generateDpsId('5300108', '016.911.281-04', '1', 1);
        
        // Formato esperado:
        // DPS (3) + 5300108 (7) + 2 (1) + 00001691128104 (14) + 00001 (5) + 000000000000001 (15) = 45 chars
        $expected = "DPS530010820000169112810400001000000000000001";
        
        $passed = ($id === $expected && strlen($id) === 45);
        echo $passed ? "[PASS] testGenerateDpsIdCpf\n" : "[FAIL] testGenerateDpsIdCpf (Obtido: $id)\n";
        return $passed;
    }

    public function testGeneratePedidoEventoId(): bool
    {
        $chave = "53260961733714000101000000000000100000000000000001"; // 50 chars
        $id = DpsIdGenerator::generatePedidoEventoId($chave, '101103');
        
        // Formato esperado: "PRE" + 50 chars da chave + "101103" = 59 chars
        $expected = "PRE" . $chave . "101103";
        
        $passed = ($id === $expected && strlen($id) === 59);
        echo $passed ? "[PASS] testGeneratePedidoEventoId\n" : "[FAIL] testGeneratePedidoEventoId (Obtido: $id)\n";
        return $passed;
    }

    public function run(): bool
    {
        echo "=== Executando Testes de DpsIdGenerator ===\n";
        $r1 = $this->testGenerateDpsIdCnpj();
        $r2 = $this->testGenerateDpsIdCpf();
        $r3 = $this->testGeneratePedidoEventoId();
        return $r1 && $r2 && $r3;
    }
}

// Execução direta via CLI se chamado
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    $t = new DpsIdGeneratorTest();
    $ok = $t->run();
    exit($ok ? 0 : 1);
}
