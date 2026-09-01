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

    public static function checkRememberLogin()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['usuario_id']) && !empty($_COOKIE['dinovatech_remember'])) {
            $parts = explode(':', $_COOKIE['dinovatech_remember'], 2);
            if (count($parts) === 2) {
                $userId = (int) $parts[0];
                $tokenHash = $parts[1];

                $dbPath = dirname(__DIR__) . '/database.php';
                if (!file_exists($dbPath)) {
                    $dbPath = dirname(__DIR__, 2) . '/database.php';
                }
                if (file_exists($dbPath)) {
                    require_once $dbPath;
                }

                $configPath = dirname(__DIR__) . '/config.php';
                if (file_exists($configPath)) {
                    require_once $configPath;
                }

                $link = DBConnect();
                if ($link) {
                    $userIdSafe = mysqli_real_escape_string($link, $userId);
                    $res = DBExecute($link, "SELECT id_usuario, nome, email, nivel_acesso FROM Usuarios WHERE id_usuario = '$userIdSafe' LIMIT 1");
                    if ($res && mysqli_num_rows($res) === 1) {
                        $user = mysqli_fetch_assoc($res);
                        $masterKey = defined('APP_MASTER_KEY') && !empty(APP_MASTER_KEY) ? APP_MASTER_KEY : 'dinovatech_secret_key';
                        $expectedHash = hash_hmac('sha256', $user['id_usuario'] . $user['email'], $masterKey);
                        if (hash_equals($expectedHash, $tokenHash)) {
                            $_SESSION['usuario_id'] = $user['id_usuario'];
                            $_SESSION['usuario_nome'] = $user['nome'];
                            $_SESSION['usuario_email'] = $user['email'];
                            $_SESSION['nivel_acesso'] = $user['nivel_acesso'];
                            DBClose($link);
                            return true;
                        }
                    }
                    DBClose($link);
                }
            }
        }
        return isset($_SESSION['usuario_id']);
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

    public static function getCompanyLogo()
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
            return null;

        $query = "SELECT logo_url FROM ConfiguracoesEmissor LIMIT 1";
        $res = mysqli_query($link, $query);
        $logo = null;
        if ($res && $row = mysqli_fetch_assoc($res)) {
            if (!empty($row['logo_url'])) {
                $logo = $row['logo_url'];
            }
        }
        DBClose($link);
        return $logo;
    }
    public static function calculateNfseData($link, $id_fatura)
    {
        $id_fatura = mysqli_real_escape_string($link, $id_fatura);

        // 1. Fetch Config
        $resConf = mysqli_query($link, "SELECT * FROM ConfiguracoesEmissor LIMIT 1");
        $config = mysqli_fetch_assoc($resConf);
        if (!$config)
            return ['success' => false, 'message' => 'Configuração Fiscal não encontrada'];

        if (isset($config['modulo_fiscal_ativo']) && (int) $config['modulo_fiscal_ativo'] !== 1) {
            return ['success' => false, 'message' => 'Módulo de emissão de Nota Fiscal está desativado nas configurações.'];
        }

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

            // Determine Tax Settings (Prioritize Concluded NFS-e Snapshot, then First Item / Recurrence)
            if (!$taxSettings) {
                $queryNfseEmissao = "SELECT * FROM NfseEmissoes WHERE id_fatura='$id_fatura' AND (status='concluido' OR status='processando') ORDER BY id_emissao DESC LIMIT 1";
                $resNfseEmissao = mysqli_query($link, $queryNfseEmissao);
                $nfseEmissaoRow = ($resNfseEmissao && mysqli_num_rows($resNfseEmissao) > 0) ? mysqli_fetch_assoc($resNfseEmissao) : null;

                if ($nfseEmissaoRow) {
                    $taxSettings = [
                        'codigo_cnae' => $row['codigo_cnae'],
                        'codigo_nbs' => $row['codigo_nbs'],
                        'item_lista_servico' => $nfseEmissaoRow['item_lista_servico'] ?: $row['item_lista_servico'],
                        'codigo_tributacao_municipio' => $row['codigo_tributacao_municipio'],
                        'aliquota_iss' => $nfseEmissaoRow['aliquota_iss'],
                        'iss_retido' => $nfseEmissaoRow['iss_retido']
                    ];
                } else {
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
        
        // --- NOVO: VALIDAÇÃO LC116 ---
        if (empty($taxSettings['item_lista_servico'])) {
            $validationErrors[] = "Item da Lista de Serviço (LC116) - Verifique o Cadastro do Serviço";
        } elseif (preg_match('/^\d\./', $taxSettings['item_lista_servico'])) {
            $validationErrors[] = "Formato LC116 Inválido (use zero à esquerda: '0' + '{$taxSettings['item_lista_servico']}')";
        }
        
        // --- NOVO: VALIDAÇÃO CNAE ---
        if (empty($taxSettings['codigo_cnae'])) {
            $validationErrors[] = "Código CNAE - Verifique o Cadastro do Serviço";
        }

        return [
            'success' => true,
            'fatura' => $fatura,
            'config' => $config,
            'total_servicos' => $totalServicos,
            'tomador' => $tomadorData,
            'tax_settings' => $taxSettings,
            'discriminacao' => $discriminacaoFinal,
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
        $queryFatura = "SELECT desconto_valor, desconto_tipo, status FROM Faturas WHERE id_fatura='$id_fatura'";
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
        // Check if there is an existing NFS-e emission snapshot (historical lock)
        $queryNfseLock = "SELECT aliquota_iss, iss_retido FROM NfseEmissoes WHERE id_fatura='$id_fatura' AND (status='concluido' OR status='processando') ORDER BY id_emissao DESC LIMIT 1";
        $resNfseLock = mysqli_query($link, $queryNfseLock);
        $nfseLock = ($resNfseLock && mysqli_num_rows($resNfseLock) > 0) ? mysqli_fetch_assoc($resNfseLock) : null;

        if ($nfseLock) {
            $aliquota = (float)$nfseLock['aliquota_iss'];
            $issRetido = (string)$nfseLock['iss_retido'];
        } else {
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

    public static function getCidadePorCodigo($codigo)
    {
        if (empty($codigo))
            return null;

        // 1. Check Session Cache
        if (isset($_SESSION['ibge_cache'][$codigo])) {
            return $_SESSION['ibge_cache'][$codigo];
        }

        // 2. Call API
        $url = "https://servicodados.ibge.gov.br/api/v1/localidades/municipios/{$codigo}";

        // Use curl for better reliability
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3); // Fast timeout
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && $response) {
            $data = json_decode($response, true);
            if (isset($data['nome'])) {
                $cidade = $data['nome'];
                $uf = $data['microrregiao']['mesorregiao']['UF']['sigla'] ?? '';

                $resultado = $cidade . ($uf ? ' - ' . $uf : '');

                // 3. Cache
                if (!isset($_SESSION['ibge_cache'])) {
                    $_SESSION['ibge_cache'] = [];
                }
                $_SESSION['ibge_cache'][$codigo] = $resultado;

                return $resultado;
            }
        }

        return null;
    }
}
