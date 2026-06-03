<?php
/**
 * Microsoft Graph API — helper de autenticação, OAuth delegado e Planner
 * Usa fluxo Authorization Code com refresh_token salvo no .env
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

// ---------------------------------------------------------------------------
// OAuth Delegado — token de acesso via refresh_token
// ---------------------------------------------------------------------------

/**
 * Salva um valor no .env (cria ou atualiza a linha da chave)
 */
function ms_env_write(string $key, string $value): void {
    $envFile = dirname(__DIR__) . '/.env';
    $lines   = file_exists($envFile) ? file($envFile, FILE_IGNORE_NEW_LINES) : [];
    $found   = false;
    foreach ($lines as &$line) {
        if (str_starts_with(trim($line), $key . '=')) {
            $line  = $key . '=' . $value;
            $found = true;
            break;
        }
    }
    if (!$found) $lines[] = $key . '=' . $value;
    file_put_contents($envFile, implode("\n", $lines) . "\n");
}

/**
 * Troca authorization code por access_token + refresh_token
 * Chamado uma única vez pelo ms_callback.php
 */
function ms_exchange_code(string $code): array {
    $tenantId     = $_ENV['MS_TENANT_ID']     ?? '';
    $clientId     = $_ENV['MS_CLIENT_ID']     ?? '';
    $clientSecret = $_ENV['MS_CLIENT_SECRET'] ?? '';
    $redirectUri  = $_ENV['MS_REDIRECT_URI']  ?? '';

    $url  = "https://login.microsoftonline.com/{$tenantId}/oauth2/v2.0/token";
    $body = http_build_query([
        'grant_type'    => 'authorization_code',
        'client_id'     => $clientId,
        'client_secret' => $clientSecret,
        'redirect_uri'  => $redirectUri,
        'code'          => $code,
        'scope'         => 'offline_access Tasks.ReadWrite Group.ReadWrite.All',
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
    curl_close($ch);

    $data = json_decode($response, true) ?? [];

    if (!empty($data['refresh_token'])) {
        ms_env_write('MS_REFRESH_TOKEN', $data['refresh_token']);
        $_ENV['MS_REFRESH_TOKEN'] = $data['refresh_token'];
    }

    return $data;
}

/**
 * Obtém access_token usando o refresh_token salvo no .env
 * Renova automaticamente o refresh_token após cada uso
 */
function ms_get_token(): string {
    $tenantId     = $_ENV['MS_TENANT_ID']     ?? '';
    $clientId     = $_ENV['MS_CLIENT_ID']     ?? '';
    $clientSecret = $_ENV['MS_CLIENT_SECRET'] ?? '';
    $refreshToken = $_ENV['MS_REFRESH_TOKEN'] ?? '';

    if (!$refreshToken) {
        throw new RuntimeException(
            'MS_REFRESH_TOKEN não configurado. Acesse /ms_oauth_init.php para autorizar o aplicativo.'
        );
    }

    $url  = "https://login.microsoftonline.com/{$tenantId}/oauth2/v2.0/token";
    $body = http_build_query([
        'grant_type'    => 'refresh_token',
        'client_id'     => $clientId,
        'client_secret' => $clientSecret,
        'refresh_token' => $refreshToken,
        'scope'         => 'offline_access Tasks.ReadWrite Group.ReadWrite.All',
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
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $data = json_decode($response, true) ?? [];

    if ($httpCode !== 200 || empty($data['access_token'])) {
        $msg = $data['error_description'] ?? $response;
        throw new RuntimeException('Erro ao renovar token OAuth: ' . $msg);
    }

    // Salva o novo refresh_token (rotação automática)
    if (!empty($data['refresh_token'])) {
        ms_env_write('MS_REFRESH_TOKEN', $data['refresh_token']);
        $_ENV['MS_REFRESH_TOKEN'] = $data['refresh_token'];
    }

    return $data['access_token'];
}

// ---------------------------------------------------------------------------
// Graph API — requisição genérica
// ---------------------------------------------------------------------------

function ms_graph_request(string $method, string $endpoint, array $body = [], string $token = '', array $extraHeaders = []): array {
    if (!$token) $token = ms_get_token();

    $url = str_starts_with($endpoint, 'https') ? $endpoint : 'https://graph.microsoft.com/v1.0' . $endpoint;

    $headers = array_merge([
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json',
    ], $extraHeaders);

    $ch = curl_init($url);
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
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $decoded = json_decode($response, true) ?? [];

    if ($httpCode >= 400) {
        $msg = $decoded['error']['message'] ?? $response;
        throw new RuntimeException("Graph API [{$httpCode}]: {$msg}");
    }

    return array_merge($decoded, ['_http_code' => $httpCode]);
}

// ---------------------------------------------------------------------------
// Planner — Buckets
// ---------------------------------------------------------------------------

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

function planner_listar_buckets(string $planId, string $token = ''): array {
    if (!$token) $token = ms_get_token();
    $result = ms_graph_request('GET', "/planner/plans/{$planId}/buckets", [], $token);
    return $result['value'] ?? [];
}

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
 * Garante que todos os buckets de status existam. Retorna [ 'nome' => 'id' ]
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

    $existentes = planner_listar_buckets($planId, $token);
    $mapa = [];
    foreach ($existentes as $b) {
        $mapa[$b['name']] = $b['id'];
    }

    foreach ($nomesBuckets as $nome) {
        if (!isset($mapa[$nome])) {
            $mapa[$nome] = planner_criar_bucket($nome, $planId, $token);
        }
    }

    return $mapa;
}

// ---------------------------------------------------------------------------
// Planner — Tarefas
// ---------------------------------------------------------------------------

function planner_criar_tarefa(string $titulo, string $planId, ?string $deadline = null, ?string $assigneeId = null, string $status = 'pendente'): array {
    $token = ms_get_token();

    $buckets    = planner_garantir_buckets($planId, $token);
    $nomeBucket = planner_bucket_por_status($status);
    $bucketId   = $buckets[$nomeBucket] ?? null;

    $body = [
        'planId' => $planId,
        'title'  => $titulo,
    ];

    if ($bucketId)  $body['bucketId']    = $bucketId;
    if ($deadline)  $body['dueDateTime'] = date('Y-m-d\TH:i:s\Z', strtotime($deadline));
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

function planner_mover_bucket(string $taskId, string $planId, string $novoStatus, string $etag): array {
    $token = ms_get_token();

    $buckets    = planner_garantir_buckets($planId, $token);
    $nomeBucket = planner_bucket_por_status($novoStatus);
    $bucketId   = $buckets[$nomeBucket] ?? null;

    if (!$bucketId) throw new RuntimeException("Bucket não encontrado para o status: {$novoStatus}");

    // Busca etag atualizado se não fornecido
    if (!$etag) {
        $taskData = ms_graph_request('GET', "/planner/tasks/{$taskId}", [], $token);
        $etag     = $taskData['@odata.etag'] ?? '';
    }

    $ch = curl_init('https://graph.microsoft.com/v1.0/planner/tasks/' . $taskId);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => 'PATCH',
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
            'If-Match: ' . $etag,
        ],
        CURLOPT_POSTFIELDS => json_encode(['bucketId' => $bucketId]),
        CURLOPT_TIMEOUT    => 20,
    ]);
    $response = curl_exec($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['http_code' => $httpCode, 'response' => $response];
}

function planner_listar_tarefas(string $planId): array {
    $token  = ms_get_token();
    $result = ms_graph_request('GET', "/planner/plans/{$planId}/tasks", [], $token);
    return $result['value'] ?? [];
}

function planner_atualizar_tarefa(string $taskId, int $percentComplete, string $etag): array {
    $token = ms_get_token();
    $ch    = curl_init('https://graph.microsoft.com/v1.0/planner/tasks/' . $taskId);
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
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['http_code' => $httpCode, 'response' => $response];
}
