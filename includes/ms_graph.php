<?php
/**
 * Microsoft Graph API — helper de autenticação e Planner
 * Carrega credenciais do arquivo .env na raiz do projeto
 */

function ms_load_env(): void {
    $envFile = dirname(__DIR__) . '/.env';
    if (!file_exists($envFile)) return;
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
        [$key, $value] = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value);
    }
}

ms_load_env();

/**
 * Mapeamento de status do GVA → nome do bucket no Planner
 */
function planner_bucket_por_status(string $status): string {
    return match (strtolower(trim($status))) {
        'pendente'              => '📋 Pendente',
        'em andamento'          => '🔄 Em andamento',
        'aguardando'            => '⏳ Aguardando',
        'aguardando cliente'    => '⏳ Aguardando',
        'produzindo'            => '🛠 Produzindo',
        'done', 'enviado',
        'publicado', 'concluído',
        'concluido'             => '✅ Concluído',
        default                 => '📋 Pendente',
    };
}

/**
 * Obtém token de acesso via Client Credentials (app-only)
 */
function ms_get_token(): string {
    $tenantId     = $_ENV['MS_TENANT_ID']     ?? '';
    $clientId     = $_ENV['MS_CLIENT_ID']     ?? '';
    $clientSecret = $_ENV['MS_CLIENT_SECRET'] ?? '';

    if (!$tenantId || !$clientId || !$clientSecret) {
        throw new RuntimeException('Credenciais Azure AD não configuradas no .env');
    }

    $url  = "https://login.microsoftonline.com/{$tenantId}/oauth2/v2.0/token";
    $body = http_build_query([
        'grant_type'    => 'client_credentials',
        'client_id'     => $clientId,
        'client_secret' => $clientSecret,
        'scope'         => 'https://graph.microsoft.com/.default',
    ]);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $body,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_TIMEOUT        => 15,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        throw new RuntimeException('Erro ao obter token Azure AD: ' . $response);
    }

    $data = json_decode($response, true);
    return $data['access_token'] ?? throw new RuntimeException('Token não encontrado na resposta.');
}

/**
 * Faz chamada à Graph API
 */
function ms_graph_request(string $method, string $endpoint, array $body = [], string $token = '', array $extraHeaders = []): array {
    if (!$token) $token = ms_get_token();

    $url = str_starts_with($endpoint, 'https') ? $endpoint : 'https://graph.microsoft.com/v1.0' . $endpoint;

    $ch = curl_init($url);
    $headers = array_merge([
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json',
    ], $extraHeaders);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => strtoupper($method),
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_TIMEOUT        => 20,
    ]);

    if (!empty($body)) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $decoded = json_decode($response, true) ?? [];

    if ($httpCode >= 400) {
        $msg = $decoded['error']['message'] ?? $response;
        throw new RuntimeException("Graph API [{$httpCode}]: {$msg}");
    }

    return array_merge($decoded, ['_http_code' => $httpCode]);
}

/**
 * Lista todos os buckets de um plano
 */
function planner_listar_buckets(string $planId, string $token = ''): array {
    if (!$token) $token = ms_get_token();
    $result = ms_graph_request('GET', "/planner/plans/{$planId}/buckets", [], $token);
    return $result['value'] ?? [];
}

/**
 * Cria um bucket no plano e retorna seu ID
 */
function planner_criar_bucket(string $nome, string $planId, string $token = ''): string {
    if (!$token) $token = ms_get_token();
    $result = ms_graph_request('POST', '/planner/buckets', [
        'name'      => $nome,
        'planId'    => $planId,
        'orderHint' => ' !',
    ], $token);
    return $result['id'] ?? throw new RuntimeException("Falha ao criar bucket '{$nome}'");
}

/**
 * Garante que todos os buckets de status existam no plano.
 * Retorna um array [ 'nome do bucket' => 'bucketId' ]
 */
function planner_garantir_buckets(string $planId, string $token = ''): array {
    if (!$token) $token = ms_get_token();

    $nomesBuckets = [
        '📋 Pendente',
        '🔄 Em andamento',
        '⏳ Aguardando',
        '🛠 Produzindo',
        '✅ Concluído',
    ];

    // Busca buckets existentes
    $existentes = planner_listar_buckets($planId, $token);
    $mapa = [];
    foreach ($existentes as $b) {
        $mapa[$b['name']] = $b['id'];
    }

    // Cria os que faltam
    foreach ($nomesBuckets as $nome) {
        if (!isset($mapa[$nome])) {
            $mapa[$nome] = planner_criar_bucket($nome, $planId, $token);
        }
    }

    return $mapa;
}

/**
 * Cria uma tarefa no Planner dentro do bucket correto para o status
 */
function planner_criar_tarefa(string $titulo, string $planId, ?string $deadline = null, ?string $assigneeId = null, string $status = 'pendente'): array {
    $token = ms_get_token();

    // Garante que os buckets existem e pega o ID do bucket correto
    $buckets      = planner_garantir_buckets($planId, $token);
    $nomeBucket   = planner_bucket_por_status($status);
    $bucketId     = $buckets[$nomeBucket] ?? null;

    $body = [
        'planId' => $planId,
        'title'  => $titulo,
    ];

    if ($bucketId) {
        $body['bucketId'] = $bucketId;
    }

    if ($deadline) {
        $body['dueDateTime'] = date('Y-m-d\TH:i:s\Z', strtotime($deadline));
    }

    if ($assigneeId) {
        $body['assignments'] = [
            $assigneeId => [
                '@odata.type' => '#microsoft.graph.plannerAssignment',
                'orderHint'   => ' !',
            ]
        ];
    }

    return ms_graph_request('POST', '/planner/tasks', $body, $token);
}

/**
 * Move uma tarefa para o bucket correspondente ao novo status
 */
function planner_mover_bucket(string $taskId, string $planId, string $novoStatus, string $etag): array {
    $token = ms_get_token();

    $buckets    = planner_garantir_buckets($planId, $token);
    $nomeBucket = planner_bucket_por_status($novoStatus);
    $bucketId   = $buckets[$nomeBucket] ?? null;

    if (!$bucketId) {
        throw new RuntimeException("Bucket não encontrado para o status: {$novoStatus}");
    }

    // Busca etag atualizado da tarefa se não fornecido
    $etagAtual = $etag;
    if (!$etagAtual) {
        $taskData  = ms_graph_request('GET', "/planner/tasks/{$taskId}", [], $token);
        $etagAtual = $taskData['@odata.etag'] ?? '';
    }

    $ch = curl_init('https://graph.microsoft.com/v1.0/planner/tasks/' . $taskId);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => 'PATCH',
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
            'If-Match: ' . $etagAtual,
        ],
        CURLOPT_POSTFIELDS => json_encode(['bucketId' => $bucketId]),
        CURLOPT_TIMEOUT    => 20,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['http_code' => $httpCode, 'response' => $response];
}

/**
 * Lista tarefas de um plano
 */
function planner_listar_tarefas(string $planId): array {
    $token = ms_get_token();
    $result = ms_graph_request('GET', "/planner/plans/{$planId}/tasks", [], $token);
    return $result['value'] ?? [];
}

/**
 * Atualiza status/percentual de uma tarefa do Planner
 * percentComplete: 0 = não iniciada, 50 = em andamento, 100 = concluída
 */
function planner_atualizar_tarefa(string $taskId, int $percentComplete, string $etag): array {
    $token = ms_get_token();
    $ch = curl_init('https://graph.microsoft.com/v1.0/planner/tasks/' . $taskId);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => 'PATCH',
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
            'If-Match: ' . $etag,
        ],
        CURLOPT_POSTFIELDS => json_encode(['percentComplete' => $percentComplete]),
        CURLOPT_TIMEOUT    => 20,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['http_code' => $httpCode, 'response' => $response];
}
