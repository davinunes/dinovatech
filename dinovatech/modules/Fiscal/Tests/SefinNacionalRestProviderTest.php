<?php
namespace Dinovatech\Modules\Fiscal\Tests;

use Dinovatech\Modules\Fiscal\Providers\SefinNacionalRestProvider;
use Dinovatech\Modules\Fiscal\DTOs\NfseData;

class SefinNacionalRestProviderTest
{
    public function run(): bool
    {
        echo "Executando SefinNacionalRestProviderTest...\n";
        $passed = true;

        $config = [
            'id_config' => 1,
            'cnpj' => '12345678000195',
            'inscricao_municipal' => '123456',
            'codigo_municipio' => '5300108',
            'serie_dps' => '1',
            'ambiente_padrao' => 'homologacao'
        ];

        $provider = new SefinNacionalRestProvider($config);

        if ($provider->getProviderName() !== 'sefin_nacional') {
            echo " [FALHA] getProviderName() deveria retornar 'sefin_nacional', retornou: " . $provider->getProviderName() . "\n";
            $passed = false;
        } else {
            echo " [OK] getProviderName() retornou 'sefin_nacional'\n";
        }

        // Teste de consulta URL formato padrão federal
        $urlRes = $provider->consultarUrl('53261012345678000195000000000000000000000000000001');
        if ($urlRes->success && strpos($urlRes->urlVisualizacao, 'https://www.nfse.gov.br/EmissorNacional/Notas/Visualizar/Index/') !== false) {
            echo " [OK] consultarUrl() gerou link oficial do Portal Nacional\n";
        } else {
            echo " [FALHA] consultarUrl() link inválido ou falhou\n";
            $passed = false;
        }

        return $passed;
    }
}

