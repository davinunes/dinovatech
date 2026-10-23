<?php

require_once __DIR__ . '/K3sClientHelper.php';

/**
 * Helper de Orquestração e Provisionamento de Tenants SaaS no K3s.
 * Conecta os Contratos do Dinovatech aos Namespaces e Pods do cluster Kubernetes.
 */
class SaasProvisionerHelper
{
    /**
     * Gera slug sanitizado a partir de uma string (ex: "Clínica Veterinária Alfa" -> "clinica-veterinaria-alfa")
     */
    public static function slugify(string $text): string
    {
        $text = iconv('UTF-8', 'ASCII//TRANSLIT', $text);
        $text = preg_replace('~[^-\w]+~', '', $text);
        $text = trim($text, '-');
        $text = preg_replace('~-+~', '-', $text);
        $text = strtolower($text);

        if (empty($text)) {
            $text = 'tenant-' . substr(md5(uniqid()), 0, 6);
        }

        return substr($text, 0, 40);
    }

    /**
     * Gera credenciais padrão sugeridas para um novo tenant
     */
    public static function generateTenantDefaults(string $clienteNome, ?string $dominioBase = null): array
    {
        $slug = self::slugify($clienteNome);
        $base = $dominioBase ?: 'dinovatech.com.br';
        $randomPass = bin2hex(random_bytes(8));
        $defaultHost = defined('SAAS_DEFAULT_DB_HOST') ? SAAS_DEFAULT_DB_HOST : (defined('DB_HOSTNAME') ? DB_HOSTNAME : '127.0.0.1');
        $randomMasterKey = bin2hex(random_bytes(32));

        return [
            'slug_instancia' => $slug,
            'subdominio' => "{$slug}.{$base}",
            'dominio_customizado' => '',
            'namespace_k8s' => "tenant-{$slug}",
            'db_name' => "dino_{$slug}",
            'db_user' => "usr_{$slug}",
            'db_password' => $randomPass,
            'db_host' => $defaultHost,
            'db_port' => 3306,
            'app_mode_vet' => 1,
            'app_master_key' => $randomMasterKey,
            'custom_envs' => "",
            'versao_imagem' => defined('K3S_DEFAULT_IMAGE') ? K3S_DEFAULT_IMAGE : 'dinovatech:latest',
            'limite_memoria' => '128Mi',
            'limite_cpu' => '250m'
        ];
    }

    /**
     * Busca dados do tenant vinculado a um contrato/recorrência
     */
    public static function getTenantByRecorrencia(int $idRecorrencia, $link): ?array
    {
        $idSafe = (int)$idRecorrencia;
        $q = "SELECT * FROM saas_tenants WHERE id_recorrencia = '$idSafe' LIMIT 1";
        $res = DBExecute($link, $q);
        if ($res && mysqli_num_rows($res) > 0) {
            return mysqli_fetch_assoc($res);
        }
        return null;
    }

    /**
     * Salva ou atualiza os metadados do tenant no banco de dados
     */
    public static function saveTenant(int $idRecorrencia, int $idCliente, array $data, $link): array
    {
        $existing = self::getTenantByRecorrencia($idRecorrencia, $link);

        $slug = mysqli_real_escape_string($link, trim($data['slug_instancia'] ?? ''));
        $subdominio = mysqli_real_escape_string($link, trim($data['subdominio'] ?? ''));
        $dominioCustom = mysqli_real_escape_string($link, trim($data['dominio_customizado'] ?? ''));
        $namespace = mysqli_real_escape_string($link, trim($data['namespace_k8s'] ?? "tenant-{$slug}"));
        $dbName = mysqli_real_escape_string($link, trim($data['db_name'] ?? ''));
        $dbUser = mysqli_real_escape_string($link, trim($data['db_user'] ?? ''));
        $dbPass = mysqli_real_escape_string($link, trim($data['db_password'] ?? ''));
        $dbHost = mysqli_real_escape_string($link, trim($data['db_host'] ?? ''));
        $dbPort = (int)($data['db_port'] ?? 3306);
        if ($dbPort <= 0) $dbPort = 3306;

        $appModeVet = isset($data['app_mode_vet']) ? (int)$data['app_mode_vet'] : 1;
        $appMasterKey = mysqli_real_escape_string($link, trim($data['app_master_key'] ?? ''));
        if (empty($appMasterKey)) {
            $appMasterKey = bin2hex(random_bytes(32));
        }
        $customEnvs = mysqli_real_escape_string($link, trim($data['custom_envs'] ?? ''));

        $imagem = mysqli_real_escape_string($link, trim($data['versao_imagem'] ?? 'dinovatech:latest'));
        $memoria = mysqli_real_escape_string($link, trim($data['limite_memoria'] ?? '128Mi'));
        $cpu = mysqli_real_escape_string($link, trim($data['limite_cpu'] ?? '250m'));

        if (empty($slug) || empty($subdominio) || empty($dbName)) {
            return ['success' => false, 'message' => 'Slug da instância, subdomínio e nome do banco são obrigatórios.'];
        }

        if ($existing) {
            $idTenant = (int)$existing['id_tenant'];
            $q = "UPDATE saas_tenants SET 
                    slug_instancia = '$slug',
                    subdominio = '$subdominio',
                    dominio_customizado = " . (!empty($dominioCustom) ? "'$dominioCustom'" : "NULL") . ",
                    namespace_k8s = '$namespace',
                    db_name = '$dbName',
                    db_user = '$dbUser',
                    db_password = '$dbPass',
                    db_host = " . (!empty($dbHost) ? "'$dbHost'" : "NULL") . ",
                    db_port = '$dbPort',
                    app_mode_vet = '$appModeVet',
                    app_master_key = '$appMasterKey',
                    custom_envs = " . (!empty($customEnvs) ? "'$customEnvs'" : "NULL") . ",
                    versao_imagem = '$imagem',
                    limite_memoria = '$memoria',
                    limite_cpu = '$cpu'
                  WHERE id_tenant = '$idTenant'";
            $res = DBExecute($link, $q);
            return [
                'success' => (bool)$res,
                'id_tenant' => $idTenant,
                'message' => $res ? 'Configurações SaaS salvas com sucesso.' : 'Erro ao atualizar dados SaaS.'
            ];
        } else {
            $q = "INSERT INTO saas_tenants 
                  (id_recorrencia, id_cliente, slug_instancia, subdominio, dominio_customizado, namespace_k8s, db_name, db_user, db_password, db_host, db_port, app_mode_vet, app_master_key, custom_envs, versao_imagem, limite_memoria, limite_cpu, status_pod)
                  VALUES 
                  ('$idRecorrencia', '$idCliente', '$slug', '$subdominio', " . (!empty($dominioCustom) ? "'$dominioCustom'" : "NULL") . ", '$namespace', '$dbName', '$dbUser', '$dbPass', " . (!empty($dbHost) ? "'$dbHost'" : "NULL") . ", '$dbPort', '$appModeVet', '$appMasterKey', " . (!empty($customEnvs) ? "'$customEnvs'" : "NULL") . ", '$imagem', '$memoria', '$cpu', 'PENDING')";
            $res = DBExecute($link, $q);
            return [
                'success' => (bool)$res,
                'id_tenant' => $res ? mysqli_insert_id($link) : null,
                'message' => $res ? 'Registro SaaS criado com sucesso.' : 'Erro ao inserir dados SaaS: ' . mysqli_error($link)
            ];
        }
    }

    /**
     * Provisiona a infraestrutura do tenant no cluster K3s (Namespace, Secret, Deployment e Service)
     */
    public static function provisionTenant(int $idTenant, $link): array
    {
        $idSafe = (int)$idTenant;
        $q = "SELECT * FROM saas_tenants WHERE id_tenant = '$idSafe' LIMIT 1";
        $res = DBExecute($link, $q);
        if (!$res || mysqli_num_rows($res) === 0) {
            return ['success' => false, 'message' => 'Tenant não encontrado.'];
        }
        $t = mysqli_fetch_assoc($res);

        $k3s = new K3sClientHelper();
        $health = $k3s->checkHealth();
        if (!$health['healthy']) {
            return ['success' => false, 'message' => 'Cluster K3s indisponível: ' . ($health['error'] ?? 'Sem conexão')];
        }

        $namespace = $t['namespace_k8s'];
        $appName = "app-{$t['slug_instancia']}";

        // 1. Cria o Namespace no Kubernetes (se ainda não existir)
        $k3s->createNamespace($namespace);

        // 2. Cria ou atualiza Secret com as variáveis de ambiente do tenant
        $dbHostFinal = !empty($t['db_host']) ? $t['db_host'] : (defined('SAAS_DEFAULT_DB_HOST') ? SAAS_DEFAULT_DB_HOST : (defined('DB_HOSTNAME') ? DB_HOSTNAME : '127.0.0.1'));
        $dbPortFinal = !empty($t['db_port']) ? (string)$t['db_port'] : '3306';
        $appModeVet = (!isset($t['app_mode_vet']) || (int)$t['app_mode_vet'] === 1) ? 'true' : 'false';
        $appMasterKey = !empty($t['app_master_key']) ? $t['app_master_key'] : bin2hex(random_bytes(32));

        $stringData = [
            'DB_HOSTNAME' => $dbHostFinal,
            'DB_PORT' => $dbPortFinal,
            'DB_DATABASE' => $t['db_name'],
            'DB_USERNAME' => $t['db_user'],
            'DB_PASSWORD' => $t['db_password'],
            'DB_CHARSET' => 'UTF8',
            'APP_MODE_VET' => $appModeVet,
            'APP_MASTER_KEY' => $appMasterKey,
            'TZ' => 'America/Sao_Paulo',
            'WEASYPRINT_URL' => 'http://weasyprint.shared-services.svc.cluster.local:9080/convert/html'
        ];

        // Parse de variáveis de ambiente customizadas (formato CHAVE=VALOR por linha)
        if (!empty($t['custom_envs'])) {
            $lines = explode("\n", str_replace("\r", "", $t['custom_envs']));
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line) || strpos($line, '#') === 0) continue;
                $parts = explode('=', $line, 2);
                if (count($parts) === 2) {
                    $k = trim($parts[0]);
                    $v = trim($parts[1]);
                    if (!empty($k)) {
                        $stringData[$k] = $v;
                    }
                }
            }
        }

        $secretPayload = [
            'apiVersion' => 'v1',
            'kind' => 'Secret',
            'metadata' => [
                'name' => 'tenant-env',
                'namespace' => $namespace
            ],
            'type' => 'Opaque',
            'stringData' => $stringData
        ];
        $secRes = $k3s->request('POST', "/api/v1/namespaces/{$namespace}/secrets", $secretPayload);
        if (!$secRes['success'] && ($secRes['http_code'] ?? 0) === 409) {
            $k3s->request('PUT', "/api/v1/namespaces/{$namespace}/secrets/tenant-env", $secretPayload);
        }

        // 3. Cria ou atualiza Deployment com RollingUpdate Zero-Downtime
        $deploymentPayload = [
            'apiVersion' => 'apps/v1',
            'kind' => 'Deployment',
            'metadata' => [
                'name' => $appName,
                'namespace' => $namespace,
                'labels' => [
                    'app' => $appName,
                    'tenant' => $t['slug_instancia']
                ]
            ],
            'spec' => [
                'replicas' => 1,
                'strategy' => [
                    'type' => 'RollingUpdate',
                    'rollingUpdate' => [
                        'maxSurge' => 1,
                        'maxUnavailable' => 0
                    ]
                ],
                'selector' => [
                    'matchLabels' => [
                        'app' => $appName
                    ]
                ],
                'template' => [
                    'metadata' => [
                        'labels' => [
                            'app' => $appName,
                            'tenant' => $t['slug_instancia']
                        ]
                    ],
                    'spec' => [
                        'containers' => [
                            [
                                'name' => 'php-app',
                                'image' => $t['versao_imagem'],
                                'imagePullPolicy' => 'IfNotPresent',
                                'ports' => [
                                    ['containerPort' => 80]
                                ],
                                'envFrom' => [
                                    ['secretRef' => ['name' => 'tenant-env']]
                                ],
                                'resources' => [
                                    'requests' => [
                                        'cpu' => '50m',
                                        'memory' => '64Mi'
                                    ],
                                    'limits' => [
                                        'cpu' => $t['limite_cpu'],
                                        'memory' => $t['limite_memoria']
                                    ]
                                ],
                                'readinessProbe' => [
                                    'httpGet' => [
                                        'path' => '/dinovatech/login.php',
                                        'port' => 80
                                    ],
                                    'initialDelaySeconds' => 4,
                                    'periodSeconds' => 5,
                                    'timeoutSeconds' => 2,
                                    'failureThreshold' => 3
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
        $depRes = $k3s->request('POST', "/apis/apps/v1/namespaces/{$namespace}/deployments", $deploymentPayload);

        // Se o Deployment já existir (409 Conflict), aplica atualização via PATCH (RollingUpdate automático)
        if (!$depRes['success'] && ($depRes['http_code'] ?? 0) === 409) {
            $patchPayload = [
                'spec' => [
                    'template' => [
                        'spec' => [
                            'containers' => [
                                [
                                    'name' => 'php-app',
                                    'image' => $t['versao_imagem'],
                                    'resources' => [
                                        'requests' => [
                                            'cpu' => '50m',
                                            'memory' => '64Mi'
                                        ],
                                        'limits' => [
                                            'cpu' => $t['limite_cpu'],
                                            'memory' => $t['limite_memoria']
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ];
            $depRes = $k3s->request('PATCH', "/apis/apps/v1/namespaces/{$namespace}/deployments/{$appName}", $patchPayload, 'application/strategic-merge-patch+json');
        }

        // 4. Cria ou atualiza Service ClusterIP
        $svcPayload = [
            'apiVersion' => 'v1',
            'kind' => 'Service',
            'metadata' => [
                'name' => $appName,
                'namespace' => $namespace
            ],
            'spec' => [
                'type' => 'ClusterIP',
                'selector' => [
                    'app' => $appName
                ],
                'ports' => [
                    [
                        'name' => 'http',
                        'port' => 80,
                        'targetPort' => 80
                    ]
                ]
            ]
        ];
        $svcRes = $k3s->request('POST', "/api/v1/namespaces/{$namespace}/services", $svcPayload);

        // 5. Atualiza o status no banco
        $status = $depRes['success'] ? 'RUNNING' : 'ERROR';
        $erro = $depRes['success'] ? 'NULL' : "'" . mysqli_real_escape_string($link, $depRes['error'] ?? 'Erro desconhecido') . "'";
        DBExecute($link, "UPDATE saas_tenants SET status_pod = '$status', ultimo_erro = $erro WHERE id_tenant = '$idSafe'");

        // 6. Sincroniza CronJobs configurados para o tenant no K3s
        if ($depRes['success']) {
            self::syncTenantCronJobs($idSafe, $link);
        }

        return [
            'success' => $depRes['success'],
            'message' => $depRes['success'] ? 'Instância provisionada/atualizada com sucesso no K3s (RollingUpdate ativo)!' : 'Falha ao criar Pod: ' . ($depRes['error'] ?? ''),
            'data' => $depRes['data'] ?? null
        ];
    }

    /**
     * Atualiza a tag da imagem Docker do tenant disparando RollingUpdate Zero-Downtime
     */
    public static function updateTenantImage(int $idTenant, string $newImage, $link): array
    {
        $idSafe = (int)$idTenant;
        $newImageSafe = trim($newImage);
        if (empty($newImageSafe)) {
            return ['success' => false, 'message' => 'Nova imagem não informada.'];
        }

        $q = "SELECT * FROM saas_tenants WHERE id_tenant = '$idSafe' LIMIT 1";
        $res = DBExecute($link, $q);
        if (!$res || mysqli_num_rows($res) === 0) {
            return ['success' => false, 'message' => 'Tenant não encontrado.'];
        }
        $t = mysqli_fetch_assoc($res);

        $k3s = new K3sClientHelper();
        $namespace = $t['namespace_k8s'];
        $appName = "app-{$t['slug_instancia']}";

        // Envia Strategic Merge Patch para o Kubernetes atualizar a imagem
        $patchPayload = [
            'spec' => [
                'template' => [
                    'spec' => [
                        'containers' => [
                            [
                                'name' => 'php-app',
                                'image' => $newImageSafe
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $patchRes = $k3s->request('PATCH', "/apis/apps/v1/namespaces/{$namespace}/deployments/{$appName}", $patchPayload, 'application/strategic-merge-patch+json');

        if ($patchRes['success']) {
            $imgEsc = mysqli_real_escape_string($link, $newImageSafe);
            DBExecute($link, "UPDATE saas_tenants SET versao_imagem = '$imgEsc', status_pod = 'RUNNING' WHERE id_tenant = '$idSafe'");
            return [
                'success' => true,
                'message' => "RollingUpdate iniciado com sucesso para a versão {$newImageSafe}! O K3s manterá o pod atual ativo até o novo estar pronto."
            ];
        }

        return [
            'success' => false,
            'message' => 'Falha ao atualizar imagem no cluster: ' . ($patchRes['error'] ?? '')
        ];
    }

    /**
     * Obtém os últimos logs do Pod do tenant
     */
    public static function getTenantLogs(int $idTenant, int $lines, $link): array
    {
        $idSafe = (int)$idTenant;
        $q = "SELECT * FROM saas_tenants WHERE id_tenant = '$idSafe' LIMIT 1";
        $res = DBExecute($link, $q);
        if (!$res || mysqli_num_rows($res) === 0) {
            return ['success' => false, 'message' => 'Tenant não encontrado.', 'logs' => ''];
        }
        $t = mysqli_fetch_assoc($res);

        $k3s = new K3sClientHelper();
        $namespace = $t['namespace_k8s'];

        $podsRes = $k3s->listPods($namespace);
        if (!$podsRes['success'] || empty($podsRes['pods'])) {
            return ['success' => false, 'message' => 'Nenhum pod encontrado para este tenant.', 'logs' => ''];
        }

        $podName = $podsRes['pods'][0]['name'];
        $logsRes = $k3s->getPodLogs($namespace, $podName, $lines);

        return [
            'success' => $logsRes['success'],
            'pod_name' => $podName,
            'namespace' => $namespace,
            'logs' => $logsRes['logs'] ?? ($logsRes['error'] ?? 'Sem logs disponíveis.')
        ];
    }

    /**
     * Altera o estado do pod no cluster (0 para suspender / 1 para reativar)
     */
    public static function scaleTenant(int $idTenant, int $replicas, $link): array
    {
        $idSafe = (int)$idTenant;
        $q = "SELECT * FROM saas_tenants WHERE id_tenant = '$idSafe' LIMIT 1";
        $res = DBExecute($link, $q);
        if (!$res || mysqli_num_rows($res) === 0) {
            return ['success' => false, 'message' => 'Tenant não encontrado.'];
        }
        $t = mysqli_fetch_assoc($res);

        $k3s = new K3sClientHelper();
        $appName = "app-{$t['slug_instancia']}";
        $scaleRes = $k3s->scaleDeployment($t['namespace_k8s'], $appName, $replicas);

        if ($scaleRes['success']) {
            $newStatus = ($replicas === 0) ? 'SUSPENDED' : 'RUNNING';
            DBExecute($link, "UPDATE saas_tenants SET status_pod = '$newStatus' WHERE id_tenant = '$idSafe'");

            // Suspende ou reativa os CronJobs do tenant no K3s
            $suspendJobs = ($replicas === 0);
            $qCrons = "SELECT nome_job FROM saas_tenant_crons WHERE id_tenant = '$idSafe' AND ativo = 1";
            $resCrons = DBExecute($link, $qCrons);
            if ($resCrons) {
                while ($c = mysqli_fetch_assoc($resCrons)) {
                    $k3s->setCronJobSuspend($t['namespace_k8s'], "cron-{$c['nome_job']}", $suspendJobs);
                }
            }

            return ['success' => true, 'message' => "Instância " . ($replicas === 0 ? 'suspensa' : 'reativada') . " com sucesso."];
        }

        return ['success' => false, 'message' => 'Erro ao alterar réplicas no cluster: ' . ($scaleRes['error'] ?? '')];
    }

    /**
     * Consulta os detalhes e status ao vivo do Tenant direto no K3s
     * (Versão real da imagem, prontidão das réplicas, pods ativos e eventos de rollout)
     */
    public static function getTenantLiveDetails(int $idTenant, $link): array
    {
        $idSafe = (int)$idTenant;
        $q = "SELECT T.*, C.nome AS nome_cliente 
              FROM saas_tenants T 
              LEFT JOIN Clientes C ON T.id_cliente = C.id_cliente 
              WHERE T.id_tenant = '$idSafe' LIMIT 1";
        $res = DBExecute($link, $q);
        if (!$res || mysqli_num_rows($res) === 0) {
            return ['success' => false, 'message' => 'Tenant não encontrado.'];
        }
        $t = mysqli_fetch_assoc($res);

        $k3s = new K3sClientHelper();
        $namespace = $t['namespace_k8s'];
        $appName = "app-{$t['slug_instancia']}";

        // 1. Consulta o Deployment no K3s
        $depRes = $k3s->getDeployment($namespace, $appName);
        $k8sImage = $depRes['success'] ? ($depRes['image'] ?? '') : '';
        $replicas = $depRes['success'] ? ($depRes['replicas'] ?? 0) : 0;
        $readyReplicas = $depRes['success'] ? ($depRes['ready_replicas'] ?? 0) : 0;
        $updatedReplicas = $depRes['success'] ? ($depRes['updated_replicas'] ?? 0) : 0;

        // 2. Consulta os Pods reais no Namespace
        $podsRes = $k3s->listPods($namespace);
        $pods = $podsRes['pods'] ?? [];

        // 3. Consulta Eventos recentes de Rollout / K3s
        $eventsRes = $k3s->getEvents($namespace, null, 15);
        $events = $eventsRes['events'] ?? [];

        // 4. Determina o estado real do Rollout
        $rolloutState = 'PENDING';
        $rolloutLabel = 'Pendente';
        if (!$depRes['success']) {
            $rolloutState = 'NOT_DEPLOYED';
            $rolloutLabel = 'Não Provisionado';
        } elseif ($replicas === 0) {
            $rolloutState = 'SUSPENDED';
            $rolloutLabel = 'Suspenso (0 Réplicas)';
        } elseif ($readyReplicas >= $replicas && $updatedReplicas >= $replicas) {
            $rolloutState = 'RUNNING';
            $rolloutLabel = "Ativo ({$readyReplicas}/{$replicas} Ready)";
        } elseif ($updatedReplicas < $replicas || $readyReplicas < $replicas) {
            $rolloutState = 'UPDATING';
            $rolloutLabel = 'Rollout em Andamento...';
        }

        // 5. Sincroniza status no banco se necessário
        $syncDbStatus = $t['status_pod'];
        if ($rolloutState === 'RUNNING' && $t['status_pod'] !== 'RUNNING') {
            $syncDbStatus = 'RUNNING';
            DBExecute($link, "UPDATE saas_tenants SET status_pod = 'RUNNING' WHERE id_tenant = '$idSafe'");
        } elseif ($rolloutState === 'SUSPENDED' && $t['status_pod'] !== 'SUSPENDED') {
            $syncDbStatus = 'SUSPENDED';
            DBExecute($link, "UPDATE saas_tenants SET status_pod = 'SUSPENDED' WHERE id_tenant = '$idSafe'");
        }

        return [
            'success' => true,
            'id_tenant' => $idSafe,
            'nome_cliente' => $t['nome_cliente'] ?? 'Cliente',
            'slug' => $t['slug_instancia'],
            'namespace' => $namespace,
            'subdominio' => $t['subdominio'],
            'db_image' => $t['versao_imagem'],
            'k8s_image' => $k8sImage ?: $t['versao_imagem'],
            'is_image_sync' => empty($k8sImage) || ($t['versao_imagem'] === $k8sImage),
            'replicas' => $replicas,
            'ready_replicas' => $readyReplicas,
            'updated_replicas' => $updatedReplicas,
            'rollout_state' => $rolloutState,
            'rollout_label' => $rolloutLabel,
            'status_pod' => $syncDbStatus,
            'pods' => $pods,
            'events' => $events
        ];
    }

    /**
     * Sincroniza o status de todos os tenants cadastrados contra o K3s de uma só vez
     */
    public static function syncAllTenants($link): array
    {
        $q = "SELECT id_tenant FROM saas_tenants ORDER BY id_tenant DESC";
        $res = DBExecute($link, $q);
        $results = [];

        if ($res) {
            while ($row = mysqli_fetch_assoc($res)) {
                $details = self::getTenantLiveDetails((int)$row['id_tenant'], $link);
                if ($details['success']) {
                    $results[] = $details;
                }
            }
        }

        return [
            'success' => true,
            'count' => count($results),
            'tenants' => $results
        ];
    }

    /**
     * Retorna a lista de rotinas agendadas (crons) vinculadas ao tenant,
     * combinando dados do banco de dados com o status ao vivo dos CronJobs no K3s.
     */
    public static function getTenantCrons(int $idTenant, $link): array
    {
        $idSafe = (int)$idTenant;
        $qT = "SELECT id_tenant, slug_instancia, namespace_k8s, status_pod FROM saas_tenants WHERE id_tenant = '$idSafe' LIMIT 1";
        $rT = DBExecute($link, $qT);
        if (!$rT || mysqli_num_rows($rT) === 0) {
            return ['success' => false, 'message' => 'Tenant não encontrado.', 'crons' => []];
        }
        $tenant = mysqli_fetch_assoc($rT);

        // Consulta K3s para saber quais CronJobs existem no cluster
        $k3s = new K3sClientHelper();
        $k8sCronsRes = $k3s->listCronJobs($tenant['namespace_k8s']);
        $k8sCrons = [];
        if ($k8sCronsRes['success']) {
            foreach ($k8sCronsRes['cronjobs'] as $kc) {
                $k8sCrons[$kc['name']] = $kc;
            }
        }

        // Busca registros da tabela relacional
        $hasTable = false;
        $chk = DBExecute($link, "SHOW TABLES LIKE 'saas_tenant_crons'");
        if ($chk && mysqli_num_rows($chk) > 0) {
            $hasTable = true;
        }

        $crons = [];
        if ($hasTable) {
            $qCrons = "SELECT * FROM saas_tenant_crons WHERE id_tenant = '$idSafe' ORDER BY id_cron ASC";
            $resCrons = DBExecute($link, $qCrons);
            if ($resCrons) {
                while ($c = mysqli_fetch_assoc($resCrons)) {
                    $jobK8sName = "cron-" . $c['nome_job'];
                    $k8sInfo = $k8sCrons[$jobK8sName] ?? null;

                    $c['k8s_job_name'] = $jobK8sName;
                    $c['k8s_exists'] = ($k8sInfo !== null);
                    $c['k8s_suspend'] = $k8sInfo['suspend'] ?? null;
                    $c['k8s_last_schedule'] = $k8sInfo['last_schedule_time'] ?? null;
                    $c['k8s_last_success'] = $k8sInfo['last_successful_time'] ?? null;

                    $crons[] = $c;
                }
            }
        }

        return [
            'success' => true,
            'tenant' => $tenant,
            'crons' => $crons,
            'k8s_online' => $k8sCronsRes['success']
        ];
    }

    /**
     * Salva ou atualiza uma rotina agendada (saas_tenant_crons) e sincroniza no K3s
     */
    public static function saveTenantCron(int $idTenant, array $data, $link): array
    {
        $idSafe = (int)$idTenant;
        $qT = "SELECT id_tenant, slug_instancia, namespace_k8s, versao_imagem, status_pod FROM saas_tenants WHERE id_tenant = '$idSafe' LIMIT 1";
        $rT = DBExecute($link, $qT);
        if (!$rT || mysqli_num_rows($rT) === 0) {
            return ['success' => false, 'message' => 'Tenant não encontrado.'];
        }

        $idCron = !empty($data['id_cron']) ? (int)$data['id_cron'] : 0;
        $nomeJob = self::slugify($data['nome_job'] ?? 'job-' . time());
        $descricao = mysqli_real_escape_string($link, trim($data['descricao'] ?? ''));
        $scriptPath = mysqli_real_escape_string($link, trim($data['script_path'] ?? ''));
        $argumentos = mysqli_real_escape_string($link, trim($data['argumentos'] ?? ''));
        $cronExpr = mysqli_real_escape_string($link, trim($data['cron_expression'] ?? '0 6 1 * *'));
        $ativo = isset($data['ativo']) ? (int)$data['ativo'] : 1;

        if (empty($nomeJob)) {
            return ['success' => false, 'message' => 'O identificador (nome) do job é obrigatório.'];
        }
        if (empty($scriptPath)) {
            return ['success' => false, 'message' => 'O caminho do script PHP é obrigatório.'];
        }
        if (empty($cronExpr)) {
            $cronExpr = '0 6 1 * *';
        }

        if ($idCron > 0) {
            $q = "UPDATE saas_tenant_crons SET 
                    nome_job = '$nomeJob',
                    descricao = '$descricao',
                    script_path = '$scriptPath',
                    argumentos = '$argumentos',
                    cron_expression = '$cronExpr',
                    ativo = '$ativo'
                  WHERE id_cron = '$idCron' AND id_tenant = '$idSafe'";
            $res = DBExecute($link, $q);
            $msg = $res ? 'Rotina agendada atualizada com sucesso.' : 'Erro ao atualizar rotina: ' . mysqli_error($link);
        } else {
            $q = "INSERT INTO saas_tenant_crons 
                    (id_tenant, nome_job, descricao, script_path, argumentos, cron_expression, ativo)
                  VALUES 
                    ('$idSafe', '$nomeJob', '$descricao', '$scriptPath', '$argumentos', '$cronExpr', '$ativo')
                  ON DUPLICATE KEY UPDATE 
                    descricao = VALUES(descricao),
                    script_path = VALUES(script_path),
                    argumentos = VALUES(argumentos),
                    cron_expression = VALUES(cron_expression),
                    ativo = VALUES(ativo)";
            $res = DBExecute($link, $q);
            $idCron = mysqli_insert_id($link) ?: $idCron;
            $msg = $res ? 'Rotina agendada cadastrada com sucesso.' : 'Erro ao cadastrar rotina: ' . mysqli_error($link);
        }

        if ($res) {
            // Sincroniza imediatamente o CronJob no K3s
            self::syncTenantCronJobs($idSafe, $link);
        }

        return [
            'success' => (bool)$res,
            'id_cron' => $idCron,
            'message' => $msg
        ];
    }

    /**
     * Exclui uma rotina agendada do banco e remove o CronJob correspondente no K3s
     */
    public static function deleteTenantCron(int $idCron, $link): array
    {
        $idSafe = (int)$idCron;
        $q = "SELECT C.*, T.namespace_k8s 
              FROM saas_tenant_crons C 
              JOIN saas_tenants T ON C.id_tenant = T.id_tenant 
              WHERE C.id_cron = '$idSafe' LIMIT 1";
        $r = DBExecute($link, $q);
        if (!$r || mysqli_num_rows($r) === 0) {
            return ['success' => false, 'message' => 'Rotina agendada não encontrada.'];
        }
        $cron = mysqli_fetch_assoc($r);

        // Remove o CronJob no cluster Kubernetes
        $k3s = new K3sClientHelper();
        $cronJobName = "cron-" . $cron['nome_job'];
        $k3s->deleteCronJob($cron['namespace_k8s'], $cronJobName);

        // Remove do banco
        $del = DBExecute($link, "DELETE FROM saas_tenant_crons WHERE id_cron = '$idSafe'");

        return [
            'success' => (bool)$del,
            'message' => $del ? 'Rotina agendada e CronJob no cluster removidos com sucesso.' : 'Erro ao excluir do banco.'
        ];
    }

    /**
     * Alterna o status ativo/pausado de uma rotina agendada
     */
    public static function toggleTenantCron(int $idCron, int $ativo, $link): array
    {
        $idSafe = (int)$idCron;
        $ativoSafe = $ativo ? 1 : 0;
        $q = "SELECT C.*, T.namespace_k8s 
              FROM saas_tenant_crons C 
              JOIN saas_tenants T ON C.id_tenant = T.id_tenant 
              WHERE C.id_cron = '$idSafe' LIMIT 1";
        $r = DBExecute($link, $q);
        if (!$r || mysqli_num_rows($r) === 0) {
            return ['success' => false, 'message' => 'Rotina não encontrada.'];
        }
        $cron = mysqli_fetch_assoc($r);

        DBExecute($link, "UPDATE saas_tenant_crons SET ativo = '$ativoSafe' WHERE id_cron = '$idSafe'");

        // Aplica o suspend no K3s
        $k3s = new K3sClientHelper();
        $cronJobName = "cron-" . $cron['nome_job'];
        if ($ativoSafe === 1) {
            // Re-sincroniza caso não exista no K3s ou tira o suspend
            self::syncTenantCronJobs((int)$cron['id_tenant'], $link);
        } else {
            $k3s->setCronJobSuspend($cron['namespace_k8s'], $cronJobName, true);
        }

        return [
            'success' => true,
            'message' => $ativoSafe ? 'Rotina agendada reativada com sucesso.' : 'Rotina agendada pausada com sucesso.'
        ];
    }

    /**
     * Sincroniza todos os CronJobs ativos de um tenant no Kubernetes K3s
     */
    public static function syncTenantCronJobs(int $idTenant, $link): array
    {
        $idSafe = (int)$idTenant;
        $qT = "SELECT id_tenant, slug_instancia, namespace_k8s, versao_imagem, status_pod FROM saas_tenants WHERE id_tenant = '$idSafe' LIMIT 1";
        $rT = DBExecute($link, $qT);
        if (!$rT || mysqli_num_rows($rT) === 0) {
            return ['success' => false, 'message' => 'Tenant não encontrado.'];
        }
        $t = mysqli_fetch_assoc($rT);

        $chk = DBExecute($link, "SHOW TABLES LIKE 'saas_tenant_crons'");
        if (!$chk || mysqli_num_rows($chk) === 0) {
            return ['success' => false, 'message' => 'Tabela saas_tenant_crons ainda não criada.'];
        }

        $k3s = new K3sClientHelper();
        $namespace = $t['namespace_k8s'];
        $isTenantSuspended = ($t['status_pod'] === 'SUSPENDED');

        $qCrons = "SELECT * FROM saas_tenant_crons WHERE id_tenant = '$idSafe'";
        $resCrons = DBExecute($link, $qCrons);

        $synced = 0;
        $errors = [];

        if ($resCrons) {
            while ($c = mysqli_fetch_assoc($resCrons)) {
                $cronJobName = "cron-" . $c['nome_job'];
                $isAtivo = ((int)$c['ativo'] === 1);

                // Se inativo, pausa no cluster
                if (!$isAtivo) {
                    $k3s->setCronJobSuspend($namespace, $cronJobName, true);
                    continue;
                }

                $scriptPath = ltrim($c['script_path'], '/');
                $cmd = ['php', "/var/www/html/{$scriptPath}"];
                if (!empty($c['argumentos'])) {
                    $argsList = preg_split('/\s+/', trim($c['argumentos']));
                    foreach ($argsList as $arg) {
                        if (!empty($arg)) $cmd[] = $arg;
                    }
                }

                $cronPayload = [
                    'apiVersion' => 'batch/v1',
                    'kind' => 'CronJob',
                    'metadata' => [
                        'name' => $cronJobName,
                        'namespace' => $namespace,
                        'labels' => [
                            'app' => "app-{$t['slug_instancia']}",
                            'tenant' => $t['slug_instancia'],
                            'job' => $c['nome_job']
                        ]
                    ],
                    'spec' => [
                        'schedule' => $c['cron_expression'],
                        'concurrencyPolicy' => 'Forbid',
                        'suspend' => ($isTenantSuspended || !$isAtivo),
                        'successfulJobsHistoryLimit' => 3,
                        'failedJobsHistoryLimit' => 3,
                        'jobTemplate' => [
                            'spec' => [
                                'template' => [
                                    'metadata' => [
                                        'labels' => [
                                            'app' => "app-{$t['slug_instancia']}",
                                            'tenant' => $t['slug_instancia'],
                                            'job' => $c['nome_job']
                                        ]
                                    ],
                                    'spec' => [
                                        'restartPolicy' => 'OnFailure',
                                        'containers' => [
                                            [
                                                'name' => 'cron-worker',
                                                'image' => $t['versao_imagem'],
                                                'imagePullPolicy' => 'IfNotPresent',
                                                'command' => $cmd,
                                                'envFrom' => [
                                                    ['secretRef' => ['name' => 'tenant-env']]
                                                ],
                                                'resources' => [
                                                    'requests' => [
                                                        'cpu' => '30m',
                                                        'memory' => '48Mi'
                                                    ],
                                                    'limits' => [
                                                        'cpu' => '200m',
                                                        'memory' => '128Mi'
                                                    ]
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ];

                $rK8s = $k3s->createOrUpdateCronJob($namespace, $cronJobName, $cronPayload);
                if ($rK8s['success']) {
                    $synced++;
                    DBExecute($link, "UPDATE saas_tenant_crons SET ultimo_status = 'SYNCED' WHERE id_cron = '{$c['id_cron']}'");
                } else {
                    $err = $rK8s['error'] ?? 'Falha ao sincronizar CronJob';
                    $errors[] = "Job {$c['nome_job']}: $err";
                    $errSafe = mysqli_real_escape_string($link, $err);
                    DBExecute($link, "UPDATE saas_tenant_crons SET ultimo_status = 'ERROR', ultimo_log = '$errSafe' WHERE id_cron = '{$c['id_cron']}'");
                }
            }
        }

        return [
            'success' => count($errors) === 0,
            'synced' => $synced,
            'errors' => $errors,
            'message' => count($errors) === 0 
                ? "Sincronizados {$synced} CronJob(s) com sucesso no K3s!" 
                : "Sincronizados {$synced} job(s), mas com erros: " . implode('; ', $errors)
        ];
    }
}

