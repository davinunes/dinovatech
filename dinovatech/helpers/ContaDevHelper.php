<?php
// dinovatech/helpers/ContaDevHelper.php

require_once __DIR__ . '/EncryptionHelper.php';

class ContaDevHelper
{
    private static $baseUrl = 'https://api-app.conta-dev.com';

    /**
     * Registra log de auditoria na tabela config_contadev_logs.
     */
    public static function log($link, $id_fatura, $acao, $status, $mensagem, $payload_req = null, $payload_res = null)
    {
        if (!$link) return;

        $id_fatura_val = !empty($id_fatura) ? (int)$id_fatura : "NULL";
        $acao_safe = mysqli_real_escape_string($link, $acao);
        $status_safe = mysqli_real_escape_string($link, $status);
        $msg_safe = mysqli_real_escape_string($link, $mensagem);
        
        $req_safe = $payload_req !== null ? "'" . mysqli_real_escape_string($link, is_array($payload_req) ? json_encode($payload_req, JSON_UNESCAPED_UNICODE) : (string)$payload_req) . "'" : "NULL";
        $res_safe = $payload_res !== null ? "'" . mysqli_real_escape_string($link, is_array($payload_res) ? json_encode($payload_res, JSON_UNESCAPED_UNICODE) : (string)$payload_res) . "'" : "NULL";

        $sql = "INSERT INTO config_contadev_logs 
                (id_fatura, acao, status, mensagem, payload_requisicao, payload_resposta) 
                VALUES ($id_fatura_val, '$acao_safe', '$status_safe', '$msg_safe', $req_safe, $res_safe)";
        
        @DBExecute($link, $sql);
    }

    /**
     * Recupera as configurações da emissora.
     */
    public static function getConfig($link)
    {
        $res = DBExecute($link, "SELECT * FROM ConfiguracoesEmissor LIMIT 1");
        if ($res && mysqli_num_rows($res) > 0) {
            return mysqli_fetch_assoc($res);
        }
        return null;
    }

    /**
     * Executa requisição HTTP via cURL para a API do ContaDev ou S3.
     */
    public static function makeRequest($url, $method = 'GET', $data = null, $token = null, $contentType = 'application/json')
    {
        $ch = curl_init();

        $headers = [
            'Accept: application/json, text/plain, */*',
            'Accept-Language: pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7',
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',
            'Origin: https://app.conta-dev.com',
            'Referer: https://app.conta-dev.com/'
        ];

        if ($contentType) {
            $headers[] = 'Content-Type: ' . $contentType;
        }

        if ($token) {
            $headers[] = 'Authorization: Bearer ' . $token;
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Compatibilidade remota

        if ($data !== null) {
            if (is_array($data) || is_object($data)) {
                $payload = json_encode($data, JSON_UNESCAPED_UNICODE);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            }
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        $json = null;
        if (!empty($response)) {
            $json = json_decode($response, true);
        }

        return [
            'status' => $httpCode,
            'body' => $response,
            'json' => $json,
            'error' => $curlError
        ];
    }

    /**
     * Realiza login no ContaDev, obtém token, dados de usuário e CNPJ ID.
     */
    public static function login($link, $email, $password)
    {
        if (empty($email) || empty($password)) {
            return ['success' => false, 'message' => 'E-mail e senha são obrigatórios.'];
        }

        $urlLogin = self::$baseUrl . '/platform/login';
        $payloadLogin = ['email' => $email, 'password' => $password];

        $resLogin = self::makeRequest($urlLogin, 'POST', $payloadLogin);

        if ($resLogin['status'] !== 200 || empty($resLogin['json']['token'])) {
            $errorMsg = $resLogin['json']['message'] ?? 'Falha na autenticação com a ContaDev.';
            if (is_array($errorMsg)) $errorMsg = implode(', ', $errorMsg);
            self::log($link, null, 'login', 'erro', "Erro login ($email): $errorMsg", $payloadLogin, $resLogin['body']);
            return ['success' => false, 'message' => "Credenciais inválidas: $errorMsg"];
        }

        $token = $resLogin['json']['token'];
        $user = $resLogin['json']['user'] ?? [];
        $userId = $user['id'] ?? '';
        $userName = $user['name'] ?? '';

        // Buscar detalhes do CNPJ em /platform/me
        $urlMe = self::$baseUrl . '/platform/me';
        $resMe = self::makeRequest($urlMe, 'GET', null, $token);

        $cnpjId = '';
        $companyName = '';

        if ($resMe['status'] === 200 && !empty($resMe['json']['cnpjs'][0])) {
            $cnpjObj = $resMe['json']['cnpjs'][0];
            $cnpjId = $cnpjObj['id'] ?? '';
            $companyName = $cnpjObj['fantasyName'] ?? $cnpjObj['name'] ?? '';
        }

        if (empty($cnpjId)) {
            // Fallback: tentar chamar /platform/cnpj
            $urlCnpj = self::$baseUrl . '/platform/cnpj';
            $resCnpj = self::makeRequest($urlCnpj, 'GET', null, $token);
            if ($resCnpj['status'] === 200 && !empty($resCnpj['json'][0]['id'])) {
                $cnpjId = $resCnpj['json'][0]['id'];
                $companyName = $resCnpj['json'][0]['fantasyName'] ?? $resCnpj['json'][0]['name'] ?? '';
            }
        }

        if (empty($cnpjId)) {
            self::log($link, null, 'login', 'erro', "CNPJ não encontrado na ContaDev para o usuário $email", null, $resMe['body']);
            return ['success' => false, 'message' => 'Nenhum CNPJ vinculado foi encontrado na sua conta ContaDev.'];
        }

        // Garante que a coluna contadev_password existe na tabela
        @DBExecute($link, "ALTER TABLE ConfiguracoesEmissor ADD COLUMN contadev_password TEXT DEFAULT NULL");

        // Salva na tabela ConfiguracoesEmissor
        $tokenEnc = EncryptionHelper::encrypt($token);
        $passwordEnc = EncryptionHelper::encrypt($password);
        $emailSafe = mysqli_real_escape_string($link, $email);
        $tokenEncSafe = mysqli_real_escape_string($link, $tokenEnc);
        $passwordEncSafe = mysqli_real_escape_string($link, $passwordEnc);
        $userIdSafe = mysqli_real_escape_string($link, $userId);
        $cnpjIdSafe = mysqli_real_escape_string($link, $cnpjId);
        $companyNameSafe = mysqli_real_escape_string($link, $companyName);
        $userNameSafe = mysqli_real_escape_string($link, $userName);

        $config = self::getConfig($link);

        if ($config) {
            $sql = "UPDATE ConfiguracoesEmissor SET 
                    contadev_email = '$emailSafe',
                    contadev_token = '$tokenEncSafe',
                    contadev_password = '$passwordEncSafe',
                    contadev_user_id = '$userIdSafe',
                    contadev_cnpj_id = '$cnpjIdSafe',
                    contadev_company_name = '$companyNameSafe',
                    contadev_user_name = '$userNameSafe',
                    contadev_ativo = 1
                    WHERE id_config = {$config['id_config']}";
        } else {
            $sql = "INSERT INTO ConfiguracoesEmissor 
                    (razao_social, cnpj, inscricao_municipal, contadev_email, contadev_token, contadev_password, contadev_user_id, contadev_cnpj_id, contadev_company_name, contadev_user_name, contadev_ativo)
                    VALUES ('Empresa', '00000000000000', '0', '$emailSafe', '$tokenEncSafe', '$passwordEncSafe', '$userIdSafe', '$cnpjIdSafe', '$companyNameSafe', '$userNameSafe', 1)";
        }

        if (DBExecute($link, $sql)) {
            self::log($link, null, 'login', 'sucesso', "Login realizado com sucesso para $email", null, ['user_id' => $userId, 'cnpj_id' => $cnpjId]);
            return [
                'success' => true,
                'message' => 'Integração com ContaDev conectada com sucesso!',
                'data' => [
                    'email' => $email,
                    'user_name' => $userName,
                    'company_name' => $companyName,
                    'cnpj_id' => $cnpjId
                ]
            ];
        } else {
            return ['success' => false, 'message' => 'Erro ao salvar configurações no banco de dados.'];
        }
    }

    /**
     * Obtém um token válido do ContaDev. Se o token atual estiver expirado ou ausente,
     * tenta renová-lo automaticamente realizando o re-login com as credenciais salvas.
     */
    public static function getValidToken($link)
    {
        $config = self::getConfig($link);
        if (!$config || empty($config['contadev_ativo'])) {
            return null;
        }

        $token = !empty($config['contadev_token']) ? EncryptionHelper::decrypt($config['contadev_token']) : null;

        // 1. Testa token atual no endpoint /platform/me
        if (!empty($token)) {
            $urlMe = self::$baseUrl . '/platform/me';
            $resMe = self::makeRequest($urlMe, 'GET', null, $token);
            if ($resMe['status'] === 200) {
                return $token;
            }
            self::log($link, null, 'auto_refresh', 'info', "Token ContaDev expirado ou inválido (HTTP {$resMe['status']}). Tentando re-login automático...");
        }

        // 2. Se token expirou ou não é válido, tenta re-login automático se e-mail e senha existirem
        if (!empty($config['contadev_email']) && !empty($config['contadev_password'])) {
            $password = EncryptionHelper::decrypt($config['contadev_password']);
            if (!empty($password)) {
                $loginRes = self::login($link, $config['contadev_email'], $password);
                if ($loginRes['success']) {
                    self::log($link, null, 'auto_refresh', 'sucesso', 'Token ContaDev renovado automaticamente com sucesso via credenciais salvas.');
                    $configUpdated = self::getConfig($link);
                    return !empty($configUpdated['contadev_token']) ? EncryptionHelper::decrypt($configUpdated['contadev_token']) : null;
                } else {
                    self::log($link, null, 'auto_refresh', 'erro', "Falha ao renovar token automaticamente: " . ($loginRes['message'] ?? ''));
                }
            }
        }

        // Se a re-autenticação falhar, inativa a integração no banco
        DBExecute($link, "UPDATE ConfiguracoesEmissor SET contadev_ativo = 0 WHERE id_config = {$config['id_config']}");
        return null;
    }

    /**
     * Retorna o status atual da conexão com a ContaDev.
     */
    public static function getAccountStatus($link)
    {
        $config = self::getConfig($link);
        if (!$config || empty($config['contadev_ativo'])) {
            return ['active' => false, 'message' => 'ContaDev não conectada.'];
        }

        $validToken = self::getValidToken($link);
        if ($validToken) {
            $config = self::getConfig($link);
            return [
                'active' => true,
                'email' => $config['contadev_email'],
                'user_name' => $config['contadev_user_name'],
                'company_name' => $config['contadev_company_name'],
                'cnpj_id' => $config['contadev_cnpj_id']
            ];
        } else {
            return ['active' => false, 'message' => 'Sessão expirada na ContaDev e falha na re-autenticação. Por favor, conecte-se novamente.'];
        }
    }

    /**
     * Desconecta a integração ContaDev.
     */
    public static function disconnect($link)
    {
        $config = self::getConfig($link);
        if ($config) {
            $sql = "UPDATE ConfiguracoesEmissor SET 
                    contadev_ativo = 0,
                    contadev_token = NULL,
                    contadev_password = NULL,
                    contadev_user_id = NULL,
                    contadev_cnpj_id = NULL,
                    contadev_email = NULL,
                    contadev_company_name = NULL,
                    contadev_user_name = NULL
                    WHERE id_config = {$config['id_config']}";
            DBExecute($link, $sql);
            self::log($link, null, 'disconnect', 'sucesso', 'Integração ContaDev desconectada pelo usuário.');
        }
        return ['success' => true, 'message' => 'Desconectado com sucesso!'];
    }

    /**
     * Verifica se a fatura já foi importada no ContaDev.
     */
    public static function checkInvoiceAlreadyImported($link, $id_fatura, $token, $cnpjId)
    {
        $id_safe = (int)$id_fatura;

        // 1. Checa registro local em nf_contadev_sync
        $resSync = DBExecute($link, "SELECT * FROM nf_contadev_sync WHERE id_fatura = '$id_safe' AND status_importacao = 'sucesso' LIMIT 1");
        if ($resSync && mysqli_num_rows($resSync) > 0) {
            $syncRow = mysqli_fetch_assoc($resSync);
            return [
                'already_imported' => true,
                'source' => 'local_sync',
                'sync' => $syncRow
            ];
        }

        // Busca dados da fatura e cliente local para comparar
        $qFatura = "SELECT F.*, C.cpf_cnpj FROM Faturas F JOIN Clientes C ON F.id_cliente = C.id_cliente WHERE F.id_fatura = '$id_safe'";
        $resFatura = DBExecute($link, $qFatura);
        if (!$resFatura || mysqli_num_rows($resFatura) == 0) {
            return ['already_imported' => false];
        }
        $fatura = mysqli_fetch_assoc($resFatura);
        $docClienteClean = preg_replace('/\D/', '', $fatura['cpf_cnpj'] ?? '');
        $valorTotal = (float)$fatura['valor_total_fatura'];
        $dataEmissao = date('Y-m-d', strtotime($fatura['data_emissao']));

        // 2. Consulta API ContaDev /platform/nf
        $urlNf = self::$baseUrl . '/platform/nf';
        $resNf = self::makeRequest($urlNf, 'GET', null, $token);

        if ($resNf['status'] === 200 && is_array($resNf['json'])) {
            foreach ($resNf['json'] as $nf) {
                $match = false;
                
                $desc = $nf['description'] ?? '';
                $xmlUri = $nf['xmlS3Uri'] ?? '';
                $pdfUri = $nf['issuedS3Uri'] ?? '';
                $nfValue = (float)($nf['value'] ?? 0);
                $nfDoc = preg_replace('/\D/', '', $nf['tomadorSnapshot']['documento'] ?? '');
                $nfDate = !empty($nf['issuedAt']) ? date('Y-m-d', strtotime($nf['issuedAt'])) : '';

                // Identificador 1: Menção ao número da fatura na descrição
                if (stripos($desc, "numero $id_safe") !== false || stripos($desc, "fatura $id_safe") !== false) {
                    $match = true;
                }
                // Identificador 2: Menção no URI do arquivo S3
                elseif (stripos($xmlUri, "nfse_$id_safe") !== false || stripos($xmlUri, "nfse-$id_safe") !== false || stripos($pdfUri, "nfse-$id_safe") !== false) {
                    $match = true;
                }
                // Identificador 3: Combinação Tríplice (Documento do Tomador + Valor + Data)
                elseif (!empty($docClienteClean) && $nfDoc === $docClienteClean && abs($nfValue - $valorTotal) < 0.01 && $nfDate === $dataEmissao) {
                    $match = true;
                }

                if ($match) {
                    // Vincula na tabela local de sync
                    $contadevNfId = mysqli_real_escape_string($link, $nf['id'] ?? '');
                    $extId = mysqli_real_escape_string($link, $nf['externalId'] ?? '');
                    $tomadorId = mysqli_real_escape_string($link, $nf['tomadorId'] ?? '');
                    $jsonDetails = mysqli_real_escape_string($link, json_encode($nf, JSON_UNESCAPED_UNICODE));

                    $qUpsert = "INSERT INTO nf_contadev_sync 
                                (id_fatura, contadev_nf_id, external_id, tomador_id, pdf_s3_uri, xml_s3_uri, valor, issued_at, status_importacao, detalhes_resposta)
                                VALUES ('$id_safe', '$contadevNfId', '$extId', '$tomadorId', '$pdfUri', '$xmlUri', '$valorTotal', '$dataEmissao', 'sucesso', '$jsonDetails')
                                ON DUPLICATE KEY UPDATE 
                                contadev_nf_id = VALUES(contadev_nf_id),
                                external_id = VALUES(external_id),
                                status_importacao = 'sucesso',
                                detalhes_resposta = VALUES(detalhes_resposta)";
                    
                    @DBExecute($link, $qUpsert);
                    self::log($link, $id_safe, 'check_dedup', 'info', "Nota já identificada na ContaDev (ID: $contadevNfId)", null, $nf);

                    return [
                        'already_imported' => true,
                        'source' => 'contadev_api',
                        'contadev_nf' => $nf
                    ];
                }
            }
        }

        return ['already_imported' => false];
    }

    /**
     * Busca ou cria um Tomador na ContaDev para o cliente local.
     */
    public static function getOrCreateTomador($link, $id_cliente, $token, $cnpjId)
    {
        $id_safe = (int)$id_cliente;
        $qClient = "SELECT * FROM Clientes WHERE id_cliente = '$id_safe'";
        $resClient = DBExecute($link, $qClient);

        if (!$resClient || mysqli_num_rows($resClient) == 0) {
            return ['success' => false, 'message' => 'Cliente não encontrado no sistema.'];
        }

        $cliente = mysqli_fetch_assoc($resClient);
        $docClean = preg_replace('/\D/', '', $cliente['cpf_cnpj'] ?? '');

        if (empty($docClean)) {
            return ['success' => false, 'message' => 'Cliente não possui CPF/CNPJ cadastrado.'];
        }

        // 1. Busca tomadores cadastrados na ContaDev
        $urlTomadores = self::$baseUrl . '/platform/tomadores?cnpjId=' . urlencode($cnpjId);
        $resTomadores = self::makeRequest($urlTomadores, 'GET', null, $token);

        if ($resTomadores['status'] === 200 && is_array($resTomadores['json'])) {
            foreach ($resTomadores['json'] as $t) {
                $tDoc = preg_replace('/\D/', '', $t['documento'] ?? '');
                if ($tDoc === $docClean) {
                    return ['success' => true, 'tomadorId' => $t['id']];
                }
            }
        }

        // 2. Não encontrado -> Cadastra novo tomador
        $tipo = (strlen($docClean) > 11) ? 'BR_PJ' : 'BR_PF';
        $razaoSocial = !empty($cliente['nome']) ? $cliente['nome'] : 'Cliente ' . $docClean;

        $urlCreate = self::$baseUrl . '/platform/tomadores';
        $payloadCreate = [
            'cnpjId' => $cnpjId,
            'tipo' => $tipo,
            'documento' => $docClean,
            'razaoSocial' => $razaoSocial
        ];

        $resCreate = self::makeRequest($urlCreate, 'POST', $payloadCreate, $token);

        if (($resCreate['status'] === 200 || $resCreate['status'] === 201) && !empty($resCreate['json']['id'])) {
            $tomadorId = $resCreate['json']['id'];
            self::log($link, null, 'create_tomador', 'sucesso', "Tomador cadastrado na ContaDev ($docClean)", $payloadCreate, $resCreate['body']);
            return ['success' => true, 'tomadorId' => $tomadorId];
        } else {
            $err = $resCreate['json']['message'] ?? 'Erro ao criar tomador na ContaDev.';
            if (is_array($err)) $err = implode(', ', $err);
            self::log($link, null, 'create_tomador', 'erro', "Erro cadastrar tomador ($docClean): $err", $payloadCreate, $resCreate['body']);
            return ['success' => false, 'message' => "Erro ao cadastrar tomador na ContaDev: $err"];
        }
    }

    /**
     * Solicita URL pré-assinada para upload S3 no ContaDev.
     */
    public static function getPresignedUrl($token, $fileName, $fileType)
    {
        $url = self::$baseUrl . '/platform/nf/import/pre-signed-url';
        $payload = [
            'fileName' => $fileName,
            'fileType' => $fileType
        ];

        $res = self::makeRequest($url, 'POST', $payload, $token);

        if (($res['status'] === 200 || $res['status'] === 201) && !empty($res['json']['url']) && !empty($res['json']['s3Uri'])) {
            return [
                'success' => true,
                'url' => $res['json']['url'],
                's3Uri' => $res['json']['s3Uri']
            ];
        }

        $err = $res['json']['message'] ?? 'Erro ao obter URL pré-assinada.';
        if (is_array($err)) $err = implode(', ', $err);
        return ['success' => false, 'message' => $err];
    }

    /**
     * Faz upload do arquivo para a URL pré-assinada da S3 via HTTP PUT.
     */
    public static function uploadFileToS3($presignedUrl, $content, $mimeType)
    {
        $res = self::makeRequest($presignedUrl, 'PUT', $content, null, $mimeType);
        return ($res['status'] === 200 || $res['status'] === 204);
    }

    /**
     * Obtém o conteúdo de um arquivo a partir de uma URL pública (HTTP/HTTPS) ou caminho local.
     */
    public static function getFileContent($urlOrPath)
    {
        if (empty($urlOrPath)) {
            return null;
        }

        // Se for URL HTTP/HTTPS
        if (preg_match('/^https?:\/\//i', $urlOrPath)) {
            $opts = [
                'http' => [
                    'method' => 'GET',
                    'timeout' => 20,
                    'header' => "User-Agent: DinovaTech-ContaDevSync/1.0\r\n"
                ],
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false
                ]
            ];
            $context = stream_context_create($opts);
            $content = @file_get_contents($urlOrPath, false, $context);

            if ($content !== false && !empty($content)) {
                return $content;
            }

            // Fallback via cURL se file_get_contents falhar
            if (function_exists('curl_init')) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $urlOrPath);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($ch, CURLOPT_TIMEOUT, 20);
                $content = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($httpCode === 200 && !empty($content)) {
                    return $content;
                }
            }

            return null;
        }

        // Se for caminho de arquivo local
        $localPath = dirname(__DIR__, 2) . '/' . ltrim($urlOrPath, '/');
        if (file_exists($localPath)) {
            return @file_get_contents($localPath);
        }

        if (file_exists($urlOrPath)) {
            return @file_get_contents($urlOrPath);
        }

        return null;
    }

    /**
     * Orquestra a importação completa de uma fatura para o ContaDev.
     */
    public static function importInvoice($link, $id_fatura, $force = false)
    {
        $id_safe = (int)$id_fatura;

        // 1. Valida configuração do ContaDev e garante token válido (com auto-refresh se necessário)
        $token = self::getValidToken($link);
        if (!$token) {
            return ['success' => false, 'message' => 'Integração ContaDev inativa ou sessão expirada. Por favor, reconecte nas configurações.'];
        }

        $config = self::getConfig($link);
        $cnpjId = $config['contadev_cnpj_id'];

        // 2. Busca dados da Fatura
        $qFatura = "SELECT F.*, C.nome AS nome_cliente, C.cpf_cnpj 
                    FROM Faturas F JOIN Clientes C ON F.id_cliente = C.id_cliente 
                    WHERE F.id_fatura = '$id_safe'";
        $resFatura = DBExecute($link, $qFatura);
        if (!$resFatura || mysqli_num_rows($resFatura) == 0) {
            return ['success' => false, 'message' => 'Fatura não encontrada.'];
        }
        $fatura = mysqli_fetch_assoc($resFatura);

        // 3. Checa desduplicação antes de tudo (se não for forçado)
        $checkDedup = self::checkInvoiceAlreadyImported($link, $id_safe, $token, $cnpjId);
        if (!$force && $checkDedup['already_imported']) {
            return [
                'success' => true,
                'already_imported' => true,
                'message' => 'Esta nota já consta como importada na plataforma ContaDev!'
            ];
        }

        // 4. Busca XML (Emissão NFS-e autorizada ou anexo)
        $xmlContent = null;
        $nfseDataEmissao = null;
        $qNfse = "SELECT xml_retorno, data_emissao FROM NfseEmissoes WHERE id_fatura = '$id_safe' AND status = 'concluido' ORDER BY id_emissao DESC LIMIT 1";
        $resNfse = DBExecute($link, $qNfse);
        if ($resNfse && mysqli_num_rows($resNfse) > 0) {
            $rowNfse = mysqli_fetch_assoc($resNfse);
            $xmlContent = $rowNfse['xml_retorno'];
            $nfseDataEmissao = $rowNfse['data_emissao'] ?? null;
        }

        // Se não encontrou na tabela NfseEmissoes, busca anexo .xml na fatura
        if (empty($xmlContent)) {
            $qAnexoXml = "SELECT A.* FROM Arquivos A 
                          JOIN FaturaArquivos FA ON A.id_arquivo = FA.id_arquivo 
                          WHERE FA.id_fatura = '$id_safe' 
                          AND (A.nome_original LIKE '%.xml' OR A.url_publica LIKE '%.xml' OR A.tipo_mime LIKE '%xml%')
                          ORDER BY FA.id_vinculo ASC";
            $resAnexoXml = DBExecute($link, $qAnexoXml);
            if ($resAnexoXml && mysqli_num_rows($resAnexoXml) > 0) {
                while ($anexoXml = mysqli_fetch_assoc($resAnexoXml)) {
                    $contentXml = self::getFileContent($anexoXml['url_publica']);
                    if (!empty($contentXml)) {
                        $xmlContent = $contentXml;
                        break;
                    }
                }
            }
        }

        // 5. Busca PDF (Anexos em FaturaArquivos)
        $pdfContent = null;
        $pdfFileName = "nfse-$id_safe.pdf";
        $anexoDataUpload = null;
        $qAnexo = "SELECT A.* FROM Arquivos A 
                   JOIN FaturaArquivos FA ON A.id_arquivo = FA.id_arquivo 
                   WHERE FA.id_fatura = '$id_safe' 
                   ORDER BY (CASE WHEN A.nome_original LIKE '%.pdf' OR A.url_publica LIKE '%.pdf' OR A.tipo_mime LIKE '%pdf%' THEN 1 ELSE 2 END), FA.id_vinculo ASC";
        $resAnexo = DBExecute($link, $qAnexo);
        if ($resAnexo && mysqli_num_rows($resAnexo) > 0) {
            while ($anexo = mysqli_fetch_assoc($resAnexo)) {
                $content = self::getFileContent($anexo['url_publica']);
                if (!empty($content)) {
                    $pdfContent = $content;
                    $pdfFileName = !empty($anexo['nome_original']) ? $anexo['nome_original'] : "fatura-$id_safe.pdf";
                    $anexoDataUpload = $anexo['data_upload'] ?? null;
                    break;
                }
            }
        }

        // Validação de requisitos de arquivos
        if (empty($xmlContent)) {
            return ['success' => false, 'message' => 'XML da NFS-e não foi localizado para esta fatura. É necessário ter a NFS-e concluída.'];
        }
        if (empty($pdfContent)) {
            return ['success' => false, 'message' => 'Nenhum anexo em PDF foi encontrado na fatura. Adicione o PDF da nota nos anexos da fatura antes de importar.'];
        }

        // Determina a data real de emissão da Nota Fiscal (issuedAt)
        $issuedAt = null;
        if (!empty($xmlContent)) {
            if (preg_match('/<(?:DataEmissao|dhEmi|DataEmissaoRps|dtEmissao)>([^<]+)<\//i', $xmlContent, $m)) {
                $ts = strtotime(trim($m[1]));
                if ($ts !== false && $ts > 0) {
                    $issuedAt = date('Y-m-d', $ts);
                }
            }
        }
        if (empty($issuedAt) && !empty($nfseDataEmissao)) {
            $ts = strtotime($nfseDataEmissao);
            if ($ts !== false && $ts > 0) {
                $issuedAt = date('Y-m-d', $ts);
            }
        }
        if (empty($issuedAt) && !empty($anexoDataUpload)) {
            $ts = strtotime($anexoDataUpload);
            if ($ts !== false && $ts > 0) {
                $issuedAt = date('Y-m-d', $ts);
            }
        }
        if (empty($issuedAt)) {
            $issuedAt = !empty($fatura['data_emissao']) ? date('Y-m-d', strtotime($fatura['data_emissao'])) : date('Y-m-d');
        }

        // 6. Obtém/Cria Tomador na ContaDev
        $tomadorRes = self::getOrCreateTomador($link, $fatura['id_cliente'], $token, $cnpjId);
        if (!$tomadorRes['success']) {
            return ['success' => false, 'message' => $tomadorRes['message']];
        }
        $tomadorId = $tomadorRes['tomadorId'];

        // 7. Requisita URLs Pré-assinadas S3 (PDF e XML)
        $preSignedPdf = self::getPresignedUrl($token, "nfse-$id_safe.pdf", 'pdf');
        if (!$preSignedPdf['success']) {
            return ['success' => false, 'message' => 'Erro ao obter pré-signed URL PDF: ' . $preSignedPdf['message']];
        }

        $preSignedXml = self::getPresignedUrl($token, "nfse_$id_safe.xml", 'xml');
        if (!$preSignedXml['success']) {
            return ['success' => false, 'message' => 'Erro ao obter pré-signed URL XML: ' . $preSignedXml['message']];
        }

        // 8. Upload para S3
        $upPdf = self::uploadFileToS3($preSignedPdf['url'], $pdfContent, 'application/pdf');
        if (!$upPdf) {
            return ['success' => false, 'message' => 'Falha no upload do arquivo PDF para a nuvem da ContaDev (S3).'];
        }

        $upXml = self::uploadFileToS3($preSignedXml['url'], $xmlContent, 'text/xml');
        if (!$upXml) {
            return ['success' => false, 'message' => 'Falha no upload do arquivo XML para a nuvem da ContaDev (S3).'];
        }

        // 9. Monta Descrição dos Serviços
        $qItems = "SELECT I.*, S.nome_servico FROM ItensFatura I JOIN Servicos S ON I.id_servico = S.id_servico WHERE I.id_fatura = '$id_safe'";
        $resItems = DBExecute($link, $qItems);
        $descLines = [];
        while ($item = mysqli_fetch_assoc($resItems)) {
            $descLines[] = $item['nome_servico'];
        }
        $descString = implode("\n", $descLines);
        if (empty($descString)) {
            $descString = "Prestação de Serviços Tecnologia da Informação";
        }
        $descFinal = $descString . "\nConforme documento auxiliar de cobranca numero " . $id_safe;

        // 10. Chamada de Importação Final `POST /platform/nf/import` ou `PUT /platform/nf/{id}`
        $urlImport = self::$baseUrl . '/platform/nf/import';
        $payloadImport = [
            'isForeign' => false,
            'value' => (float)$fatura['valor_total_fatura'],
            'cnpjId' => $cnpjId,
            'description' => $descFinal,
            'tomadorId' => $tomadorId,
            'issuedAt' => $issuedAt,
            'pdfS3Uri' => $preSignedPdf['s3Uri'],
            'xmlS3Uri' => $preSignedXml['s3Uri']
        ];

        $resImport = null;
        $existingNfId = $checkDedup['sync']['contadev_nf_id'] ?? $checkDedup['contadev_nf']['id'] ?? null;

        if ($force && !empty($existingNfId)) {
            // Tenta primeiramente PUT no endpoint do recurso existente se for re-sincronização
            $urlPut = self::$baseUrl . '/platform/nf/' . $existingNfId;
            $resImport = self::makeRequest($urlPut, 'PUT', $payloadImport, $token);

            // Se o PUT não for suportado pela API (retornando status != 200/201), tenta POST /platform/nf/import
            if ($resImport['status'] !== 200 && $resImport['status'] !== 201) {
                $resImport = self::makeRequest($urlImport, 'POST', $payloadImport, $token);
            }
        } else {
            $resImport = self::makeRequest($urlImport, 'POST', $payloadImport, $token);
        }

        if (($resImport['status'] === 200 || $resImport['status'] === 201) && !empty($resImport['json']['id'])) {
            $contadevNfId = mysqli_real_escape_string($link, $resImport['json']['id']);
            $externalId = mysqli_real_escape_string($link, $resImport['json']['externalId'] ?? '');
            $pdfUriSafe = mysqli_real_escape_string($link, $preSignedPdf['s3Uri']);
            $xmlUriSafe = mysqli_real_escape_string($link, $preSignedXml['s3Uri']);
            $valorVal = (float)$fatura['valor_total_fatura'];
            $dataEmissaoVal = mysqli_real_escape_string($link, $issuedAt);
            $resDetails = mysqli_real_escape_string($link, json_encode($resImport['json'], JSON_UNESCAPED_UNICODE));

            $qUpsert = "INSERT INTO nf_contadev_sync 
                        (id_fatura, contadev_nf_id, external_id, tomador_id, pdf_s3_uri, xml_s3_uri, valor, issued_at, status_importacao, detalhes_resposta)
                        VALUES ('$id_safe', '$contadevNfId', '$externalId', '$tomadorId', '$pdfUriSafe', '$xmlUriSafe', '$valorVal', '$dataEmissaoVal', 'sucesso', '$resDetails')
                        ON DUPLICATE KEY UPDATE 
                        contadev_nf_id = VALUES(contadev_nf_id),
                        external_id = VALUES(external_id),
                        status_importacao = 'sucesso',
                        detalhes_resposta = VALUES(detalhes_resposta)";
            
            @DBExecute($link, $qUpsert);
            self::log($link, $id_safe, 'import_nf', 'sucesso', "Fatura #$id_safe importada com sucesso no ContaDev (NF ID: $contadevNfId)", $payloadImport, $resImport['body']);

            return [
                'success' => true,
                'message' => 'Fatura importada no ContaDev com sucesso!',
                'data' => $resImport['json']
            ];
        } else {
            $err = $resImport['json']['message'] ?? 'Erro desconhecido ao importar nota no ContaDev.';
            if (is_array($err)) $err = implode(', ', $err);

            $resDetails = mysqli_real_escape_string($link, $resImport['body'] ?? '');
            $qUpsert = "INSERT INTO nf_contadev_sync 
                        (id_fatura, status_importacao, detalhes_resposta)
                        VALUES ('$id_safe', 'erro', '$resDetails')
                        ON DUPLICATE KEY UPDATE 
                        status_importacao = 'erro',
                        detalhes_resposta = VALUES(detalhes_resposta)";
            @DBExecute($link, $qUpsert);

            self::log($link, $id_safe, 'import_nf', 'erro', "Erro importação HTTP {$resImport['status']}: $err", $payloadImport, $resImport['body']);

            return [
                'success' => false,
                'message' => "Erro na API da ContaDev: $err"
            ];
        }
    }
}
