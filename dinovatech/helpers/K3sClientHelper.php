<?php

/**
 * Helper para integração direta com a API REST do Kubernetes / K3s (porta 6443).
 * Permite orquestração de pods, namespaces, escalabilidade e monitoramento
 * sem depender de binários locais do kubectl ou conexões SSH.
 */
class K3sClientHelper
{
    private $apiUrl;
    private $token;
    private $skipTls;
    private $timeout;

    public function __construct(?string $apiUrl = null, ?string $token = null, ?bool $skipTls = null, int $timeout = 10)
    {
        $this->apiUrl = rtrim($apiUrl ?? (defined('K3S_API_URL') ? K3S_API_URL : 'https://137.131.208.19:6443'), '/');
        $this->token = $token ?? (defined('K3S_API_TOKEN') ? K3S_API_TOKEN : '');
        $this->skipTls = $skipTls ?? (defined('K3S_INSECURE_SKIP_TLS') ? K3S_INSECURE_SKIP_TLS : true);
        $this->timeout = $timeout;
    }

    /**
     * Executa requisição HTTP cURL contra a API do K3s
     */
    public function request(string $method, string $path, $body = null, string $contentType = 'application/json'): array
    {
        $url = $this->apiUrl . '/' . ltrim($path, '/');
        $ch = curl_init($url);

        $headers = [
            'Authorization: Bearer ' . $this->token,
            'Content-Type: ' . $contentType,
            'Accept: application/json'
        ];

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);

        if ($this->skipTls) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }

        if ($body !== null) {
            $payload = is_string($body) ? $body : json_encode($body);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        }

        $response = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, defined('CURLINFO_HTTP_CODE') ? CURLINFO_HTTP_CODE : 2097170);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            return [
                'success' => false,
                'http_code' => 0,
                'error' => 'Falha na conexão cURL: ' . $curlError,
                'data' => null
            ];
        }

        $decoded = json_decode($response, true);

        return [
            'success' => ($httpCode >= 200 && $httpCode < 300),
            'http_code' => $httpCode,
            'error' => ($httpCode >= 400) ? ($decoded['message'] ?? 'Erro HTTP ' . $httpCode) : null,
            'data' => $decoded !== null ? $decoded : $response
        ];
    }

    /**
     * Valida conexão e saúde do cluster (livez / readyz)
     */
    public function checkHealth(): array
    {
        $start = microtime(true);
        $res = $this->request('GET', '/readyz');
        $latencyMs = round((microtime(true) - $start) * 1000, 2);

        $isOk = ($res['success'] && trim((string)$res['data']) === 'ok');

        return [
            'healthy' => $isOk,
            'http_code' => $res['http_code'],
            'latency_ms' => $latencyMs,
            'raw' => $res['data'],
            'error' => $res['error']
        ];
    }

    /**
     * Obtém informações e recursos de todos os nós do cluster
     */
    public function getNodes(): array
    {
        $res = $this->request('GET', '/api/v1/nodes');
        if (!$res['success']) {
            return $res;
        }

        $nodes = [];
        $items = $res['data']['items'] ?? [];

        foreach ($items as $item) {
            $metadata = $item['metadata'] ?? [];
            $status = $item['status'] ?? [];

            // Identifica condições do nó
            $isReady = false;
            foreach ($status['conditions'] ?? [] as $cond) {
                if (($cond['type'] ?? '') === 'Ready') {
                    $isReady = (($cond['status'] ?? '') === 'True');
                    break;
                }
            }

            $nodeInfo = $status['nodeInfo'] ?? [];
            $capacity = $status['capacity'] ?? [];
            $allocatable = $status['allocatable'] ?? [];

            $nodes[] = [
                'name' => $metadata['name'] ?? 'desconhecido',
                'ready' => $isReady,
                'os' => $nodeInfo['osImage'] ?? '',
                'architecture' => $nodeInfo['architecture'] ?? '',
                'kernel' => $nodeInfo['kernelVersion'] ?? '',
                'kubelet_version' => $nodeInfo['kubeletVersion'] ?? '',
                'cpu_capacity' => $capacity['cpu'] ?? '',
                'memory_capacity' => $capacity['memory'] ?? '',
                'cpu_allocatable' => $allocatable['cpu'] ?? '',
                'memory_allocatable' => $allocatable['memory'] ?? '',
            ];
        }

        return [
            'success' => true,
            'nodes' => $nodes
        ];
    }

    /**
     * Lista todos os namespaces cadastrados
     */
    public function listNamespaces(): array
    {
        $res = $this->request('GET', '/api/v1/namespaces');
        if (!$res['success']) {
            return $res;
        }

        $namespaces = [];
        foreach ($res['data']['items'] ?? [] as $item) {
            $namespaces[] = [
                'name' => $item['metadata']['name'] ?? '',
                'status' => $item['status']['phase'] ?? 'Active',
                'created_at' => $item['metadata']['creationTimestamp'] ?? ''
            ];
        }

        return [
            'success' => true,
            'namespaces' => $namespaces
        ];
    }

    /**
     * Cria um novo namespace para um tenant isolado
     */
    public function createNamespace(string $namespaceName): array
    {
        $payload = [
            'apiVersion' => 'v1',
            'kind' => 'Namespace',
            'metadata' => [
                'name' => $namespaceName,
                'labels' => [
                    'app.kubernetes.io/managed-by' => 'dinovatech-saas-master',
                    'tenant' => $namespaceName
                ]
            ]
        ];

        return $this->request('POST', '/api/v1/namespaces', $payload);
    }

    /**
     * Lista os pods de um namespace específico ou de todo o cluster
     */
    public function listPods(?string $namespace = null): array
    {
        $path = $namespace ? "/api/v1/namespaces/{$namespace}/pods" : '/api/v1/pods';
        $res = $this->request('GET', $path);

        if (!$res['success']) {
            return $res;
        }

        $pods = [];
        foreach ($res['data']['items'] ?? [] as $item) {
            $metadata = $item['metadata'] ?? [];
            $status = $item['status'] ?? [];
            $spec = $item['spec'] ?? [];

            // Extrai dados dos containers
            $containerStatuses = $status['containerStatuses'] ?? [];
            $firstContainerStatus = $containerStatuses[0] ?? [];
            $firstContainerSpec = $spec['containers'][0] ?? [];

            $image = $firstContainerStatus['image'] ?? ($firstContainerSpec['image'] ?? '');
            $isReady = !empty($firstContainerStatus['ready']);
            $restartCount = (int)($firstContainerStatus['restartCount'] ?? 0);

            // Calcula idade amigável
            $startTime = $status['startTime'] ?? ($metadata['creationTimestamp'] ?? '');
            $ageStr = '';
            if (!empty($startTime)) {
                $startTs = strtotime($startTime);
                if ($startTs) {
                    $diffSec = time() - $startTs;
                    if ($diffSec < 60) {
                        $ageStr = "{$diffSec}s";
                    } elseif ($diffSec < 3600) {
                        $ageStr = round($diffSec / 60) . "m";
                    } elseif ($diffSec < 86400) {
                        $ageStr = round($diffSec / 3600) . "h";
                    } else {
                        $ageStr = round($diffSec / 86400) . "d";
                    }
                }
            }

            $pods[] = [
                'name' => $metadata['name'] ?? '',
                'namespace' => $metadata['namespace'] ?? '',
                'phase' => $status['phase'] ?? 'Unknown',
                'pod_ip' => $status['podIP'] ?? '',
                'node_name' => $spec['nodeName'] ?? '',
                'start_time' => $startTime,
                'age' => $ageStr,
                'image' => $image,
                'ready' => $isReady,
                'restart_count' => $restartCount
            ];
        }

        return [
            'success' => true,
            'pods' => $pods
        ];
    }

    /**
     * Obtém detalhes de um Deployment no Kubernetes
     */
    public function getDeployment(string $namespace, string $deploymentName): array
    {
        $path = "/apis/apps/v1/namespaces/{$namespace}/deployments/{$deploymentName}";
        $res = $this->request('GET', $path);

        if (!$res['success']) {
            return $res;
        }

        $data = $res['data'] ?? [];
        $spec = $data['spec'] ?? [];
        $status = $data['status'] ?? [];

        $containers = $spec['template']['spec']['containers'] ?? [];
        $currentImage = $containers[0]['image'] ?? '';

        return [
            'success' => true,
            'name' => $deploymentName,
            'namespace' => $namespace,
            'image' => $currentImage,
            'replicas' => (int)($status['replicas'] ?? ($spec['replicas'] ?? 0)),
            'ready_replicas' => (int)($status['readyReplicas'] ?? 0),
            'updated_replicas' => (int)($status['updatedReplicas'] ?? 0),
            'available_replicas' => (int)($status['availableReplicas'] ?? 0),
            'conditions' => $status['conditions'] ?? [],
            'raw' => $data
        ];
    }

    /**
     * Obtém os eventos recentes de um namespace (ex: Pulling, Created, Started, Failed)
     */
    public function getEvents(string $namespace, ?string $objectName = null, int $limit = 20): array
    {
        $path = "/api/v1/namespaces/{$namespace}/events";
        $res = $this->request('GET', $path);

        if (!$res['success']) {
            return $res;
        }

        $items = $res['data']['items'] ?? [];
        $events = [];

        foreach ($items as $item) {
            $involved = $item['involvedObject'] ?? [];
            if ($objectName !== null && ($involved['name'] ?? '') !== $objectName && strpos($involved['name'] ?? '', $objectName) === false) {
                continue;
            }

            $events[] = [
                'type' => $item['type'] ?? 'Normal', // Normal ou Warning
                'reason' => $item['reason'] ?? '',
                'message' => $item['message'] ?? '',
                'object' => ($involved['kind'] ?? '') . '/' . ($involved['name'] ?? ''),
                'count' => (int)($item['count'] ?? 1),
                'first_timestamp' => $item['firstTimestamp'] ?? '',
                'last_timestamp' => $item['lastTimestamp'] ?? ($item['eventTime'] ?? ($item['metadata']['creationTimestamp'] ?? ''))
            ];
        }

        // Ordena pelos eventos mais recentes primeiro
        usort($events, function($a, $b) {
            return strtotime($b['last_timestamp']) <=> strtotime($a['last_timestamp']);
        });

        if ($limit > 0 && count($events) > $limit) {
            $events = array_slice($events, 0, $limit);
        }

        return [
            'success' => true,
            'events' => $events
        ];
    }

    /**
     * Altera a quantidade de réplicas de um Deployment
     * Usado para suspender (replicas = 0) ou reativar (replicas = 1) um tenant
     */
    public function scaleDeployment(string $namespace, string $deploymentName, int $replicas): array
    {
        $path = "/apis/apps/v1/namespaces/{$namespace}/deployments/{$deploymentName}/scale";
        $payload = [
            'spec' => [
                'replicas' => $replicas
            ]
        ];

        // Kubernetes scale subresource aceita merge patch JSON
        return $this->request('PATCH', $path, $payload, 'application/merge-patch+json');
    }

    /**
     * Dispara um rollout restart no deployment (recria pods de forma graciosa)
     */
    public function restartDeployment(string $namespace, string $deploymentName): array
    {
        $path = "/apis/apps/v1/namespaces/{$namespace}/deployments/{$deploymentName}";
        $payload = [
            'spec' => [
                'template' => [
                    'metadata' => [
                        'annotations' => [
                            'kubectl.kubernetes.io/restartedAt' => date('c')
                        ]
                    ]
                ]
            ]
        ];

        return $this->request('PATCH', $path, $payload, 'application/merge-patch+json');
    }

    /**
     * Obtém as últimas linhas de log de um Pod
     */
    public function getPodLogs(string $namespace, string $podName, int $lines = 100): array
    {
        $path = "/api/v1/namespaces/{$namespace}/pods/{$podName}/log?tailLines={$lines}";
        return $this->request('GET', $path);
    }

    /**
     * Lista todos os CronJobs (batch/v1) em um namespace
     */
    public function listCronJobs(string $namespace): array
    {
        $path = "/apis/batch/v1/namespaces/{$namespace}/cronjobs";
        $res = $this->request('GET', $path);
        if (!$res['success']) {
            return ['success' => false, 'cronjobs' => [], 'error' => $res['error']];
        }

        $items = $res['data']['items'] ?? [];
        $cronjobs = [];

        foreach ($items as $item) {
            $metadata = $item['metadata'] ?? [];
            $spec = $item['spec'] ?? [];
            $status = $item['status'] ?? [];

            $cronjobs[] = [
                'name' => $metadata['name'] ?? '',
                'namespace' => $metadata['namespace'] ?? $namespace,
                'schedule' => $spec['schedule'] ?? '',
                'suspend' => (bool)($spec['suspend'] ?? false),
                'last_schedule_time' => $status['lastScheduleTime'] ?? null,
                'last_successful_time' => $status['lastSuccessfulTime'] ?? null,
                'active_jobs' => count($status['active'] ?? [])
            ];
        }

        return [
            'success' => true,
            'cronjobs' => $cronjobs
        ];
    }

    /**
     * Obtém detalhes de um CronJob específico
     */
    public function getCronJob(string $namespace, string $cronJobName): array
    {
        $path = "/apis/batch/v1/namespaces/{$namespace}/cronjobs/{$cronJobName}";
        return $this->request('GET', $path);
    }

    /**
     * Cria ou atualiza um CronJob (batch/v1) no namespace
     */
    public function createOrUpdateCronJob(string $namespace, string $cronJobName, array $payload): array
    {
        $createPath = "/apis/batch/v1/namespaces/{$namespace}/cronjobs";
        $res = $this->request('POST', $createPath, $payload);

        // Se já existir (409 Conflict), atualiza via PUT ou PATCH
        if (!$res['success'] && ($res['http_code'] ?? 0) === 409) {
            $updatePath = "/apis/batch/v1/namespaces/{$namespace}/cronjobs/{$cronJobName}";
            $res = $this->request('PUT', $updatePath, $payload);
        }

        return $res;
    }

    /**
     * Remove um CronJob do namespace
     */
    public function deleteCronJob(string $namespace, string $cronJobName): array
    {
        $path = "/apis/batch/v1/namespaces/{$namespace}/cronjobs/{$cronJobName}";
        return $this->request('DELETE', $path);
    }

    /**
     * Pausa (suspend = true) ou reativa (suspend = false) um CronJob
     */
    public function setCronJobSuspend(string $namespace, string $cronJobName, bool $suspend): array
    {
        $path = "/apis/batch/v1/namespaces/{$namespace}/cronjobs/{$cronJobName}";
        $payload = [
            'spec' => [
                'suspend' => $suspend
            ]
        ];

        return $this->request('PATCH', $path, $payload, 'application/merge-patch+json');
    }
}

