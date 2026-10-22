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

        return [
            'slug_instancia' => $slug,
            'subdominio' => "{$slug}.{$base}",
            'dominio_customizado' => '',
            'namespace_k8s' => "tenant-{$slug}",
            'db_name' => "dino_{$slug}",
            'db_user' => "usr_{$slug}",
            'db_password' => $randomPass,
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
                  (id_recorrencia, id_cliente, slug_instancia, subdominio, dominio_customizado, namespace_k8s, db_name, db_user, db_password, versao_imagem, limite_memoria, limite_cpu, status_pod)
                  VALUES 
                  ('$idRecorrencia', '$idCliente', '$slug', '$subdominio', " . (!empty($dominioCustom) ? "'$dominioCustom'" : "NULL") . ", '$namespace', '$dbName', '$dbUser', '$dbPass', '$imagem', '$memoria', '$cpu', 'PENDING')";
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

        // 2. Cria Secret com as variáveis de ambiente do tenant
        $secretPayload = [
            'apiVersion' => 'v1',
            'kind' => 'Secret',
            'metadata' => [
                'name' => 'tenant-env',
                'namespace' => $namespace
            ],
            'type' => 'Opaque',
            'stringData' => [
                'DB_HOSTNAME' => defined('DB_HOSTNAME') ? DB_HOSTNAME : '127.0.0.1',
                'DB_DATABASE' => $t['db_name'],
                'DB_USERNAME' => $t['db_user'],
                'DB_PASSWORD' => $t['db_password'],
                'DB_CHARSET' => 'UTF8',
                'TZ' => 'America/Sao_Paulo',
                'WEASYPRINT_URL' => 'http://weasyprint.shared-services.svc.cluster.local:9080/convert/html',
                'APP_MODE_VET' => 'true'
            ]
        ];
        // 2. Cria ou atualiza Secret com as variáveis de ambiente do tenant
        $secretPayload = [
            'apiVersion' => 'v1',
            'kind' => 'Secret',
            'metadata' => [
                'name' => 'tenant-env',
                'namespace' => $namespace
            ],
            'type' => 'Opaque',
            'stringData' => [
                'DB_HOSTNAME' => defined('DB_HOSTNAME') ? DB_HOSTNAME : '127.0.0.1',
                'DB_DATABASE' => $t['db_name'],
                'DB_USERNAME' => $t['db_user'],
                'DB_PASSWORD' => $t['db_password'],
                'DB_CHARSET' => 'UTF8',
                'TZ' => 'America/Sao_Paulo',
                'WEASYPRINT_URL' => 'http://weasyprint.shared-services.svc.cluster.local:9080/convert/html',
                'APP_MODE_VET' => 'true'
            ]
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
                                        'path' => '/login.php',
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
            return ['success' => true, 'message' => "Instância " . ($replicas === 0 ? 'suspensa' : 'reativada') . " com sucesso."];
        }

        return ['success' => false, 'message' => 'Erro ao alterar réplicas no cluster: ' . ($scaleRes['error'] ?? '')];
    }
}
