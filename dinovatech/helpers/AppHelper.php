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

    /**
     * Obtém o IP real do cliente/sonda, considerando instâncias atrás de Reverse Proxies SSL (Caddy, Nginx, Cloudflare).
     *
     * @return string IP validado do cliente ou fallback
     */
    public static function getClientIP(): string
    {
        $headers = [
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_X_REAL_IP',        // Caddy / Nginx / Traefik
            'HTTP_X_FORWARDED_FOR',  // Cabeçalho padrão de proxy
            'REMOTE_ADDR'            // Fallback para conexão direta
        ];

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ipList = $_SERVER[$header];
                
                // X-Forwarded-For pode conter múltiplos IPs (cliente, proxy1, proxy2)
                if (strpos($ipList, ',') !== false) {
                    $ips = explode(',', $ipList);
                    $ipList = trim($ips[0]);
                }
                
                $ip = trim($ipList);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
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
                    $res = DBExecute($link, "SELECT * FROM Usuarios WHERE id_usuario = '$userIdSafe' LIMIT 1");
                    if ($res && mysqli_num_rows($res) === 1) {
                        $user = mysqli_fetch_assoc($res);
                        $masterKey = defined('APP_MASTER_KEY') && !empty(APP_MASTER_KEY) ? APP_MASTER_KEY : 'dinovatech_secret_key';
                        $expectedHash = hash_hmac('sha256', $user['id_usuario'] . $user['email'], $masterKey);
                        if (hash_equals($expectedHash, $tokenHash)) {
                            $_SESSION['usuario_id'] = $user['id_usuario'];
                            $_SESSION['usuario_nome'] = $user['nome'];
                            $_SESSION['usuario_email'] = $user['email'];
                            $_SESSION['nivel_acesso'] = $user['nivel_acesso'];
                            $_SESSION['id_colaborador'] = !empty($user['id_colaborador']) ? (int) $user['id_colaborador'] : null;
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
                        'codigo_tributacao_nacional' => $row['codigo_tributacao_nacional'] ?? '',
                        'codigo_tributacao_municipio' => $row['codigo_tributacao_municipio'],
                        'aliquota_iss' => $nfseEmissaoRow['aliquota_iss'],
                        'tributacao_issqn' => (int)($row['tributacao_issqn'] ?? 1),
                        'iss_retido' => $nfseEmissaoRow['iss_retido'],
                        'cst_ibs_cbs' => $row['cst_ibs_cbs'] ?? '000',
                        'classificacao_trib_ibs_cbs' => $row['classificacao_trib_ibs_cbs'] ?? '000000',
                        'indicador_operacao' => $row['indicador_operacao'] ?? '050101'
                    ];
                } else {
                    $taxSettings = [
                        'codigo_cnae' => $row['codigo_cnae'],
                        'codigo_nbs' => $row['codigo_nbs'],
                        'item_lista_servico' => $row['item_lista_servico'],
                        'codigo_tributacao_nacional' => $row['codigo_tributacao_nacional'] ?? '',
                        'codigo_tributacao_municipio' => $row['codigo_tributacao_municipio'],
                        'aliquota_iss' => $row['aliquota_iss'],
                        'tributacao_issqn' => (int)($row['tributacao_issqn'] ?? 1),
                        'iss_retido' => $row['iss_retido'],
                        'cst_ibs_cbs' => $row['cst_ibs_cbs'] ?? '000',
                        'classificacao_trib_ibs_cbs' => $row['classificacao_trib_ibs_cbs'] ?? '000000',
                        'indicador_operacao' => $row['indicador_operacao'] ?? '050101'
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

        // --- VALIDAÇÃO DE CONFORMIDADE FISCAL (IBS/CBS - REFORMA TRIBUTÁRIA) ---
        require_once __DIR__ . '/FiscalCatalogHelper.php';
        $checkFiscal = FiscalCatalogHelper::validarCorrelacao(
            $taxSettings['codigo_tributacao_nacional'] ?? '',
            $taxSettings['codigo_nbs'] ?? '',
            $taxSettings['classificacao_trib_ibs_cbs'] ?? '',
            $taxSettings['indicador_operacao'] ?? '',
            $taxSettings['cst_ibs_cbs'] ?? '',
            $link
        );

        return [
            'success' => true,
            'fatura' => $fatura,
            'config' => $config,
            'total_servicos' => $totalServicos,
            'tomador' => $tomadorData,
            'tax_settings' => $taxSettings,
            'discriminacao' => $discriminacaoFinal,
            'validation_errors' => $validationErrors,
            'conformidade_fiscal' => $checkFiscal,
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

    public static function isInterApiActive()
    {
        $dbPath = dirname(__DIR__) . '/database.php';
        if (!file_exists($dbPath)) {
            $dbPath = dirname(__DIR__, 2) . '/database.php';
        }

        if (file_exists($dbPath)) {
            require_once $dbPath;
        }

        $link = DBConnect();
        if (!$link) {
            return false;
        }

        $query = "SELECT api_inter_client_id, api_inter_cert_base64, api_inter_cert_path, api_inter_ativo FROM ConfiguracoesEmissor LIMIT 1";
        $res = mysqli_query($link, $query);
        $active = false;
        if ($res && $row = mysqli_fetch_assoc($res)) {
            $isToggleAtivo = !isset($row['api_inter_ativo']) || (int)$row['api_inter_ativo'] === 1;
            $hasClientId = !empty(trim($row['api_inter_client_id'] ?? ''));
            $hasCert = !empty($row['api_inter_cert_base64']) || (!empty($row['api_inter_cert_path']) && file_exists(dirname(__DIR__, 2) . '/' . $row['api_inter_cert_path']));
            if ($isToggleAtivo && $hasClientId && $hasCert) {
                $active = true;
            }
        }
        DBClose($link);
        return $active;
    }

    public static function isInfinitePayActive()
    {
        $dbPath = dirname(__DIR__) . '/database.php';
        if (!file_exists($dbPath)) {
            $dbPath = dirname(__DIR__, 2) . '/database.php';
        }

        if (file_exists($dbPath)) {
            require_once $dbPath;
        }

        $link = DBConnect();
        if (!$link) {
            return false;
        }

        $query = "SELECT infinitepay_ativo, infinitepay_handle FROM ConfiguracoesEmissor LIMIT 1";
        $res = mysqli_query($link, $query);
        $active = false;
        if ($res && $row = mysqli_fetch_assoc($res)) {
            $isToggleAtivo = isset($row['infinitepay_ativo']) && (int)$row['infinitepay_ativo'] === 1;
            $hasHandle = !empty(trim($row['infinitepay_handle'] ?? ''));
            if ($isToggleAtivo && $hasHandle) {
                $active = true;
            }
        }
        DBClose($link);
        return $active;
    }

    public static function getLoggedColaboradorId()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['id_colaborador']) && !empty($_SESSION['usuario_id'])) {
            $dbPath = dirname(__DIR__) . '/database.php';
            if (!file_exists($dbPath)) {
                $dbPath = dirname(__DIR__, 2) . '/database.php';
            }
            if (file_exists($dbPath)) {
                require_once $dbPath;
            }

            $link = DBConnect();
            if ($link) {
                $uid = (int) $_SESSION['usuario_id'];
                $checkCol = DBExecute($link, "SHOW COLUMNS FROM Usuarios LIKE 'id_colaborador'");
                if ($checkCol && mysqli_num_rows($checkCol) > 0) {
                    $res = DBExecute($link, "SELECT id_colaborador FROM Usuarios WHERE id_usuario = $uid LIMIT 1");
                    if ($res && $row = mysqli_fetch_assoc($res)) {
                        $_SESSION['id_colaborador'] = !empty($row['id_colaborador']) ? (int) $row['id_colaborador'] : null;
                    }
                } else {
                    $_SESSION['id_colaborador'] = null;
                }
                DBClose($link);
            }
        }

        return !empty($_SESSION['id_colaborador']) ? (int) $_SESSION['id_colaborador'] : null;
    }

    public static function getLoggedColaborador()
    {
        $idColab = self::getLoggedColaboradorId();
        if (!$idColab) {
            return null;
        }

        $dbPath = dirname(__DIR__) . '/database.php';
        if (!file_exists($dbPath)) {
            $dbPath = dirname(__DIR__, 2) . '/database.php';
        }
        if (file_exists($dbPath)) {
            require_once $dbPath;
        }

        $link = DBConnect();
        if (!$link) {
            return null;
        }

        $res = DBExecute($link, "SELECT * FROM Veterinarios WHERE id_vet = $idColab LIMIT 1");
        $colab = ($res && mysqli_num_rows($res) > 0) ? mysqli_fetch_assoc($res) : null;
        DBClose($link);
        return $colab;
    }

    /**
     * Gera variáveis estruturadas de vacinas (V4, FeLV, Antirrábica, Tabela HTML e Parágrafo)
     * para preenchimento em modelos de documentos WYSIWYG.
     *
     * @param mysqli $link Conexão com o banco de dados
     * @param int|string|null $id_pet ID do Pet
     * @return array Mapa de variáveis e seus valores substituídos
     */
    public static function gerarVariaveisVacinas($link, $id_pet): array
    {
        $vars = [
            '{{tabela_vacinas}}' => '',
            '{{TABELA_VACINAS}}' => '',
            '{{paragrafo_vacinas}}' => '',
            '{{PARAGRAFO_VACINAS}}' => '',
            '{{vacinas_paragrafo}}' => '',
            '{{lista_vacinas_paragrafo}}' => '',

            // Variáveis individuais V4
            '{{vacina_v4_data}}' => '',
            '{{vacina_v4_proxima}}' => '',
            '{{vacina_v4_lote}}' => '',
            '{{VACINA_V4_DATA}}' => '',
            '{{VACINA_V4_PROXIMA}}' => '',
            '{{VACINA_V4_LOTE}}' => '',

            // Variáveis individuais FeLV
            '{{vacina_felv_data}}' => '',
            '{{vacina_felv_proxima}}' => '',
            '{{vacina_felv_lote}}' => '',
            '{{VACINA_FELV_DATA}}' => '',
            '{{VACINA_FELV_PROXIMA}}' => '',
            '{{VACINA_FELV_LOTE}}' => '',

            // Variáveis individuais Antirrábica
            '{{vacina_antirrabica_data}}' => '',
            '{{vacina_antirrabica_proxima}}' => '',
            '{{vacina_antirrabica_lote}}' => '',
            '{{VACINA_ANTIRRABICA_DATA}}' => '',
            '{{VACINA_ANTIRRABICA_PROXIMA}}' => '',
            '{{VACINA_ANTIRRABICA_LOTE}}' => '',
        ];

        if (!$link || !$id_pet) {
            return $vars;
        }

        $id_pet_safe = mysqli_real_escape_string($link, (string)$id_pet);

        $query = "SELECT cv.*, v.nome as nome_vacina
                  FROM CarteiraVacinas cv
                  JOIN Vacinas v ON cv.id_vacina = v.id_vacina
                  WHERE cv.id_pet = '$id_pet_safe'
                  ORDER BY cv.data_aplicacao DESC, cv.id_carteira DESC";

        $result = DBExecute($link, $query);

        if (!$result || mysqli_num_rows($result) === 0) {
            return $vars;
        }

        $vacinas = [];
        $vacinasMapeadas = [];

        while ($row = mysqli_fetch_assoc($result)) {
            $vacinas[] = $row;

            $nomeVacinaLower = strtolower(trim($row['nome_vacina'] ?? ''));

            $dataRealizada = (!empty($row['data_aplicacao']) && $row['data_aplicacao'] !== '0000-00-00')
                ? date('d/m/Y', strtotime($row['data_aplicacao']))
                : '';
            $proximaDose = (!empty($row['data_vencimento']) && $row['data_vencimento'] !== '0000-00-00')
                ? date('d/m/Y', strtotime($row['data_vencimento']))
                : '';
            $lote = trim($row['lote'] ?? '');

            // Mapeamento dinâmico para qualquer vacina (slug do nome da vacina)
            $slugVacina = trim(preg_replace('/[^a-z0-9]+/', '_', $nomeVacinaLower), '_');
            if (!empty($slugVacina) && !isset($vacinasMapeadas[$slugVacina])) {
                $vacinasMapeadas[$slugVacina] = true;
                $vars["{{vacina_{$slugVacina}_data}}"] = $dataRealizada;
                $vars["{{vacina_{$slugVacina}_proxima}}"] = $proximaDose;
                $vars["{{vacina_{$slugVacina}_lote}}"] = $lote;
                $vars["{{" . strtoupper("vacina_{$slugVacina}_data") . "}}"] = $dataRealizada;
                $vars["{{" . strtoupper("vacina_{$slugVacina}_proxima") . "}}"] = $proximaDose;
                $vars["{{" . strtoupper("vacina_{$slugVacina}_lote") . "}}"] = $lote;
            }

            // Mapeamentos específicos/curtos (pega o registro mais recente)
            if (!isset($vacinasMapeadas['v4']) && (strpos($nomeVacinaLower, 'v4') !== false || strpos($nomeVacinaLower, 'v-4') !== false)) {
                $vacinasMapeadas['v4'] = true;
                $vars['{{vacina_v4_data}}'] = $dataRealizada;
                $vars['{{vacina_v4_proxima}}'] = $proximaDose;
                $vars['{{vacina_v4_lote}}'] = $lote;
                $vars['{{VACINA_V4_DATA}}'] = $dataRealizada;
                $vars['{{VACINA_V4_PROXIMA}}'] = $proximaDose;
                $vars['{{VACINA_V4_LOTE}}'] = $lote;
            }

            // FeLV
            if (!isset($vacinasMapeadas['felv']) && strpos($nomeVacinaLower, 'felv') !== false) {
                $vacinasMapeadas['felv'] = true;
                $vars['{{vacina_felv_data}}'] = $dataRealizada;
                $vars['{{vacina_felv_proxima}}'] = $proximaDose;
                $vars['{{vacina_felv_lote}}'] = $lote;
                $vars['{{VACINA_FELV_DATA}}'] = $dataRealizada;
                $vars['{{VACINA_FELV_PROXIMA}}'] = $proximaDose;
                $vars['{{VACINA_FELV_LOTE}}'] = $lote;
            }

            // Antirrábica
            if (!isset($vacinasMapeadas['antirrabica']) && (strpos($nomeVacinaLower, 'antirrab') !== false || strpos($nomeVacinaLower, 'antirráb') !== false || strpos($nomeVacinaLower, 'raiva') !== false)) {
                $vacinasMapeadas['antirrabica'] = true;
                $vars['{{vacina_antirrabica_data}}'] = $dataRealizada;
                $vars['{{vacina_antirrabica_proxima}}'] = $proximaDose;
                $vars['{{vacina_antirrabica_lote}}'] = $lote;
                $vars['{{VACINA_ANTIRRABICA_DATA}}'] = $dataRealizada;
                $vars['{{VACINA_ANTIRRABICA_PROXIMA}}'] = $proximaDose;
                $vars['{{VACINA_ANTIRRABICA_LOTE}}'] = $lote;
            }
        }

        // 1. Bloco de Tabela Dinâmica (HTML)
        $tableRows = '';
        foreach ($vacinas as $v) {
            $nomeVac = htmlspecialchars($v['nome_vacina'] ?? '');
            $dtApp = (!empty($v['data_aplicacao']) && $v['data_aplicacao'] !== '0000-00-00')
                ? date('d/m/Y', strtotime($v['data_aplicacao']))
                : '-';
            $dtProx = (!empty($v['data_vencimento']) && $v['data_vencimento'] !== '0000-00-00')
                ? date('d/m/Y', strtotime($v['data_vencimento']))
                : '-';
            $loteVac = !empty(trim($v['lote'] ?? '')) ? htmlspecialchars(trim($v['lote'])) : '-';

            $tableRows .= "
            <tr>
                <td style=\"padding: 8px 12px; border: 1px solid #e5e7eb;\">{$nomeVac}</td>
                <td style=\"padding: 8px 12px; border: 1px solid #e5e7eb;\">{$dtApp}</td>
                <td style=\"padding: 8px 12px; border: 1px solid #e5e7eb;\">{$dtProx}</td>
                <td style=\"padding: 8px 12px; border: 1px solid #e5e7eb;\">{$loteVac}</td>
            </tr>";
        }

        $tabelaHtml = "
        <table style=\"width: 100%; border-collapse: collapse; margin: 10px 0; font-family: inherit; font-size: 14px;\">
            <thead>
                <tr style=\"background-color: #f3f4f6; color: #374151; font-weight: bold; text-align: left;\">
                    <th style=\"padding: 8px 12px; border: 1px solid #e5e7eb;\">Vacina</th>
                    <th style=\"padding: 8px 12px; border: 1px solid #e5e7eb;\">Data Realizada</th>
                    <th style=\"padding: 8px 12px; border: 1px solid #e5e7eb;\">Próxima Dose</th>
                    <th style=\"padding: 8px 12px; border: 1px solid #e5e7eb;\">Lote/Part.</th>
                </tr>
            </thead>
            <tbody>{$tableRows}
            </tbody>
        </table>";

        $vars['{{tabela_vacinas}}'] = $tabelaHtml;
        $vars['{{TABELA_VACINAS}}'] = $tabelaHtml;

        // 2. Formato de Parágrafo (Omitindo linhas de dados não preenchidos)
        $paragrafosList = [];
        foreach ($vacinas as $v) {
            $linhas = [];
            if (!empty($v['nome_vacina'])) {
                $linhas[] = "• <strong>Nome da Vacina:</strong> " . htmlspecialchars($v['nome_vacina']);
            }
            if (!empty($v['data_aplicacao']) && $v['data_aplicacao'] !== '0000-00-00') {
                $linhas[] = "• <strong>Data de Realização:</strong> " . date('d/m/Y', strtotime($v['data_aplicacao']));
            }
            if (!empty($v['data_vencimento']) && $v['data_vencimento'] !== '0000-00-00') {
                $linhas[] = "• <strong>Data da Próxima Dose:</strong> " . date('d/m/Y', strtotime($v['data_vencimento']));
            }
            if (!empty(trim($v['lote'] ?? ''))) {
                $linhas[] = "• <strong>Lote/Partícula (Part):</strong> " . htmlspecialchars(trim($v['lote']));
            }

            if (!empty($linhas)) {
                $paragrafosList[] = implode("<br>", $linhas);
            }
        }

        $paragrafoHtml = implode("<br><br>", $paragrafosList);

        $vars['{{paragrafo_vacinas}}'] = $paragrafoHtml;
        $vars['{{PARAGRAFO_VACINAS}}'] = $paragrafoHtml;
        $vars['{{vacinas_paragrafo}}'] = $paragrafoHtml;
        $vars['{{lista_vacinas_paragrafo}}'] = $paragrafoHtml;

        return $vars;
    }
}


