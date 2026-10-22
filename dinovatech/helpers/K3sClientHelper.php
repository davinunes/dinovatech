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

            $pods[] = [
                'name' => $metadata['name'] ?? '',
                'namespace' => $metadata['namespace'] ?? '',
                'phase' => $status['phase'] ?? 'Unknown',
                'pod_ip' => $status['podIP'] ?? '',
                'node_name' => $item['spec']['nodeName'] ?? '',
                'start_time' => $status['startTime'] ?? ''
            ];
        }

        return [
            'success' => true,
            'pods' => $pods
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
}
