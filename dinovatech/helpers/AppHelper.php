<?php

class AppHelper
{
    public static function isVetMode()
    {
        // Verifica se a constante foi definida pelo config.php
        if (defined('APP_MODE_VET')) {
            return APP_MODE_VET === true || APP_MODE_VET === 'true' || APP_MODE_VET === 1 || APP_MODE_VET === '1';
        }

        // Fallback para verificar variável de ambiente diretamente
        $env = getenv('APP_MODE_VET');
        return $env === 'true' || $env === '1';
    }

    public static function getCompanyName()
    {
        $dbPath = dirname(__DIR__) . '/database.php';
        if (!file_exists($dbPath)) {
            $dbPath = dirname(__DIR__, 2) . '/database.php';
        }

        if (file_exists($dbPath)) {
            require_once $dbPath;
        }

        $link = DBConnect();
        if (!$link)
            return 'DinovaTech';

        $query = "SELECT nome_fantasia FROM ConfiguracoesEmissor LIMIT 1";
        $res = mysqli_query($link, $query);
        $name = 'DinovaTech';
        if ($res && $row = mysqli_fetch_assoc($res)) {
            if (!empty($row['nome_fantasia'])) {
                $name = $row['nome_fantasia'];
            }
        }
        DBClose($link);
        return $name;
    }
    public static function calculateNfseData($link, $id_fatura)
    {
        $id_fatura = mysqli_real_escape_string($link, $id_fatura);

        // 1. Fetch Config
        $resConf = mysqli_query($link, "SELECT * FROM ConfiguracoesEmissor LIMIT 1");
        $config = mysqli_fetch_assoc($resConf);
        if (!$config)
            return ['success' => false, 'message' => 'Configuração Fiscal não encontrada'];

        // 2. Fetch Fatura & Client
        $queryFat = "SELECT F.*, C.*, C.nome as nome_tomador, F.id_fatura as f_id FROM Faturas F JOIN Clientes C ON F.id_cliente=C.id_cliente WHERE F.id_fatura='$id_fatura'";
        $resFat = mysqli_query($link, $queryFat);
        $fatura = mysqli_fetch_assoc($resFat);
        if (!$fatura)
            return ['success' => false, 'message' => 'Fatura não encontrada'];

        // 3. Fetch Items
        $queryItems = "SELECT I.*, S.*, I.id_recorrencia as item_recorrencia_id FROM ItensFatura I JOIN Servicos S ON I.id_servico=S.id_servico WHERE I.id_fatura='$id_fatura'";
        $resItems = mysqli_query($link, $queryItems);

        $items = [];
        $totalServicos = 0.0;
        $taxSettings = null;
        $discriminacaoFinal = "";
        $firstItem = true;

        while ($row = mysqli_fetch_assoc($resItems)) {
            $items[] = $row;
            $totalServicos += ($row['quantidade'] * $row['valor_unitario']);

            if ($firstItem) {
                // Strategy: Recorrencia Fiscal > Servico Fiscal > Servico Nome
                $descItem = $row['descricao_fiscal'] ?? '';

                // Check Recorrencia Override
                if (!empty($row['item_recorrencia_id'])) {
                    $idRec = $row['item_recorrencia_id'];
                    $resRec = mysqli_query($link, "SELECT descricao_fiscal, codigo_cnae, codigo_nbs, codigo_tributacao_municipio, aliquota_iss, iss_retido FROM Recorrencias WHERE id_recorrencia = '$idRec'");
                    if ($resRec && mysqli_num_rows($resRec) > 0) {
                        $recRow = mysqli_fetch_assoc($resRec);
                        if (!empty($recRow['descricao_fiscal'])) {
                            $descItem = $recRow['descricao_fiscal'];
                        }
                        // Store recRow for Tax Settings Check below
                        $row['rec_override'] = $recRow;
                    }
                }

                if (empty($descItem)) {
                    $descItem = $row['nome_servico'];
                }

                $discriminacaoFinal = $descItem;
                $firstItem = false;
            }

            // Determine Tax Settings (Prioritize First Item)
            if (!$taxSettings) {
                $taxSettings = [
                    'codigo_cnae' => $row['codigo_cnae'],
                    'codigo_nbs' => $row['codigo_nbs'],
                    'item_lista_servico' => $row['item_lista_servico'],
                    'codigo_tributacao_municipio' => $row['codigo_tributacao_municipio'],
                    'aliquota_iss' => $row['aliquota_iss'],
                    'iss_retido' => $row['iss_retido']
                ];

                // Check Recurrence Override
                if (!empty($row['rec_override'])) {
                    $recRow = $row['rec_override'];
                    if (!empty($recRow['codigo_cnae']))
                        $taxSettings['codigo_cnae'] = $recRow['codigo_cnae'];
                    if (!empty($recRow['codigo_nbs']))
                        $taxSettings['codigo_nbs'] = $recRow['codigo_nbs'];
                    if (!empty($recRow['codigo_tributacao_municipio']))
                        $taxSettings['codigo_tributacao_municipio'] = $recRow['codigo_tributacao_municipio'];
                    if (!is_null($recRow['aliquota_iss']))
                        $taxSettings['aliquota_iss'] = $recRow['aliquota_iss'];
                    if (!is_null($recRow['iss_retido']))
                        $taxSettings['iss_retido'] = $recRow['iss_retido'];
                }
            }
        }

        if (empty($items))
            return ['success' => false, 'message' => 'Fatura sem itens'];

        // Append Footer
        $discriminacaoFinal .= "\nConforme documento auxiliar de cobranca numero " . $fatura['f_id'];

        // Validation Checks
        $validationErrors = [];
        $tomadorData = [
            'razao_social' => $fatura['nome_tomador'],
            'cpf_cnpj' => $fatura['cpf_cnpj'],
            'inscricao_municipal' => $fatura['inscricao_municipal'] ?? '',
            'endereco' => $fatura['endereco'],
            'numero' => $fatura['numero'] ?: 'S/N',
            'complemento' => $fatura['complemento'],
            'bairro' => $fatura['bairro'] ?: 'Centro',
            'cep' => $fatura['cep'],
            'uf' => $fatura['uf'],
            'codigo_municipio' => $fatura['codigo_municipio'] ?: '5300108',
            'email' => $fatura['email'],
            'telefone' => $fatura['telefone']
        ];

        if (empty($tomadorData['endereco']))
            $validationErrors[] = "Endereço";
        if (empty($tomadorData['numero']))
            $validationErrors[] = "Número";
        if (empty($tomadorData['bairro']))
            $validationErrors[] = "Bairro";
        if (empty($tomadorData['cep']))
            $validationErrors[] = "CEP";
        if (empty($tomadorData['uf']))
            $validationErrors[] = "UF";
        if (empty($tomadorData['codigo_municipio']))
            $validationErrors[] = "Município (IBGE)";

        return [
            'success' => true,
            'fatura' => $fatura,
            'config' => $config,
            'total_servicos' => $totalServicos,
            'tax_settings' => $taxSettings,
            'discriminacao' => $discriminacaoFinal,
            'tomador' => $tomadorData,
            'validation_errors' => $validationErrors,
            'ambiente' => ($config['ambiente_padrao'] === 'producao') ? 'producao' : 'homologacao'
        ];
    }

    public static function calculateFaturaTotals($link, $id_fatura)
    {
        // 1. Fetch Items Sum
        $queryItems = "SELECT SUM(quantidade * valor_unitario) as total_servicos FROM ItensFatura WHERE id_fatura='$id_fatura'";
        $resItems = mysqli_query($link, $queryItems);
        $rowItems = mysqli_fetch_assoc($resItems);
        $totalServicos = $rowItems['total_servicos'] ?? 0.00;

        // 2. Fetch Invoice Discount Settings
        $queryFatura = "SELECT desconto_valor, desconto_tipo FROM Faturas WHERE id_fatura='$id_fatura'";
        $resFatura = mysqli_query($link, $queryFatura);
        $rowFatura = mysqli_fetch_assoc($resFatura);

        $descontoValor = 0.00;
        if ($rowFatura) {
            $descVal = (float) $rowFatura['desconto_valor'];
            $descTipo = $rowFatura['desconto_tipo']; // 'percentual' or 'fixo'

            if ($descVal > 0) {
                if ($descTipo === 'percentual') {
                    $descontoValor = ($totalServicos * ($descVal / 100));
                } else {
                    $descontoValor = $descVal;
                }
            }
        }

        // 3. Fetch Tax Settings relative to this Invoice
        // We need to check if there is ANY item with retention, or if we follow the dominant service.
        // Usually, Invoice = One Service. But if mixed, we should check each?
        // Current logic in calculateNfseData takes the FIRST item's settings. We shall do the same for consistency.

        $queryTax = "SELECT I.id_recorrencia, I.id_servico, S.aliquota_iss, S.iss_retido
                     FROM ItensFatura I 
                     JOIN Servicos S ON I.id_servico = S.id_servico 
                     WHERE I.id_fatura='$id_fatura' LIMIT 1";

        $resTax = mysqli_query($link, $queryTax);
        $taxData = mysqli_fetch_assoc($resTax);

        $aliquota = $taxData['aliquota_iss'] ?? 0;
        $issRetido = $taxData['iss_retido'] ?? '2'; // 2=Não
        $idRecorrencia = $taxData['id_recorrencia'] ?? null;

        // Check Override from Recurrence
        if ($idRecorrencia) {
            $queryRec = "SELECT iss_retido, aliquota_iss FROM Recorrencias WHERE id_recorrencia='$idRecorrencia'";
            $resRec = mysqli_query($link, $queryRec);
            $rec = mysqli_fetch_assoc($resRec);
            if ($rec) {
                if (!is_null($rec['iss_retido']))
                    $issRetido = $rec['iss_retido'];
                if (!is_null($rec['aliquota_iss']))
                    $aliquota = $rec['aliquota_iss'];
            }
        }

        $valorRetencao = 0.00;
        $detalhesRetencao = "";

        if ($issRetido == '1' && $aliquota > 0) {
            $valorRetencao = ($totalServicos * ($aliquota / 100));
            $detalhesRetencao = "ISS (" . number_format($aliquota, 2, ',', '.') . "%)";
        }

        // Final Calculation: Total Services - Retention - Discount
        $valorLiquido = $totalServicos - $valorRetencao - $descontoValor;
        if ($valorLiquido < 0)
            $valorLiquido = 0;

        return [
            'valor_servicos' => (float) $totalServicos,
            'iss_retido' => ($issRetido == '1'),
            'valor_retencao' => (float) $valorRetencao,
            'detalhes_retencao' => $detalhesRetencao,
            'desconto_aplicado' => (float) $descontoValor,
            'tipo_desconto' => $rowFatura['desconto_tipo'] ?? 'percentual',
            'valor_desconto_original' => (float) ($rowFatura['desconto_valor'] ?? 0),
            'valor_liquido' => (float) $valorLiquido
        ];
    }
}
