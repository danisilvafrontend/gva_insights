<?php
/**
 * sync_planner.php
 * Endpoint chamado via fetch() para sincronizar uma demanda com o Microsoft Planner
 *
 * Estratégia dual:
 *   1. Tenta Graph API direta (ms_graph.php) — requer MS_REFRESH_TOKEN no .env
 *   2. Fallback: Power Automate webhook autenticado via Client Credentials (Bearer token)
 *
 * POST params:
 *   - acao      : 'criar' | 'concluir' | 'mover'
 *   - demanda_id: ID da demanda no banco
 *   - titulo    : título da tarefa
 *   - empresa   : nome da empresa envolvida
 *   - status    : status atual da demanda
 *   - deadline  : data no formato Y-m-d (opcional)
 *   - task_id   : ID da tarefa no Planner (necessário para 'concluir' e 'mover')
 *   - etag      : eTag da tarefa no Planner (necessário para 'concluir' e 'mover')
 */

require_once '../includes/auth.php';
require_login();
require_once '../includes/ms_graph.php';

header('Content-Type: application/json');

$acao       = $_POST['acao']       ?? '';
$titulo     = $_POST['titulo']     ?? '';
$empresa    = $_POST['empresa']    ?? '';
$status     = $_POST['status']     ?? 'pendente';
$deadline   = $_POST['deadline']   ?? null;
$taskId     = $_POST['task_id']    ?? '';
$etag       = $_POST['etag']       ?? '';
$planId     = $_ENV['MS_PLANNER_PLAN_ID'] ?? '';
$webhookUrl = $_ENV['PLANNER_WEBHOOK_URL'] ?? '';

if (!$planId) {
    echo json_encode(['success' => false, 'error' => 'PLAN_ID não configurado no .env']);
    exit;
}

// Monta o título com a empresa: "[Empresa] Título da tarefa"
$tituloCompleto = $empresa ? "[{$empresa}] {$titulo}" : $titulo;

// ---------------------------------------------------------------------------
// Helper: obtém token via Client Credentials (sem refresh_token de usuário)
// ---------------------------------------------------------------------------
function ms_get_client_credentials_token(): string {
    $tenantId     = $_ENV['MS_TENANT_ID']     ?? '';
    $clientId     = $_ENV['MS_CLIENT_ID']     ?? '';
    $clientSecret = $_ENV['MS_CLIENT_SECRET'] ?? '';

    if (!$tenantId || !$clientId || !$clientSecret) {
        throw new RuntimeException('MS_TENANT_ID, MS_CLIENT_ID ou MS_CLIENT_SECRET não configurados no .env');
    }

    $url  = "https://login.microsoftonline.com/{$tenantId}/oauth2/v2.0/token";
    $body = http_build_query([
        'grant_type'    => 'client_credentials',
        'client_id'     => $clientId,
        'client_secret' => $clientSecret,
        'scope'         => 'https://api.powerplatform.com/.default',
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
        throw new RuntimeException('Erro ao obter token Client Credentials: ' . $msg);
    }

    return $data['access_token'];
}

// ---------------------------------------------------------------------------
// Helper: dispara o Power Automate webhook com Bearer token
// ---------------------------------------------------------------------------
function disparar_webhook_planner(string $titulo, string $deadline, string $status, string $webhookUrl): array {
    if (!$webhookUrl) {
        throw new RuntimeException('PLANNER_WEBHOOK_URL não configurado no .env');
    }

    $token   = ms_get_client_credentials_token();
    $payload = json_encode([
        'titulo'   => $titulo,
        'deadline' => $deadline ?: date('Y-m-d', strtotime('+7 days')),
        'status'   => $status,
    ]);

    $ch = curl_init($webhookUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token,
        ],
        CURLOPT_TIMEOUT        => 20,
    ]);
    $response = curl_exec($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode < 200 || $httpCode >= 300) {
        throw new RuntimeException("Webhook Power Automate falhou [{$httpCode}]: {$response}");
    }

    return ['via' => 'power_automate_webhook', 'http_code' => $httpCode];
}

// ---------------------------------------------------------------------------
// Processamento das ações
// ---------------------------------------------------------------------------
try {
    if ($acao === 'criar') {
        if (!$titulo) throw new RuntimeException('Título obrigatório.');

        $refreshToken = $_ENV['MS_REFRESH_TOKEN'] ?? '';

        if ($refreshToken) {
            // Caminho principal: Graph API direta
            $task = planner_criar_tarefa(
                $tituloCompleto,
                $planId,
                $deadline ?: null,
                null,
                $status
            );

            echo json_encode([
                'success' => true,
                'via'     => 'graph_api',
                'task_id' => $task['id'],
                'etag'    => $task['@odata.etag'] ?? '',
                'url'     => 'https://tasks.office.com/',
                'bucket'  => planner_bucket_por_status($status),
                'titulo'  => $tituloCompleto,
            ]);

        } else {
            // Fallback: Power Automate webhook com Client Credentials
            $result = disparar_webhook_planner($tituloCompleto, $deadline ?? '', $status, $webhookUrl);

            echo json_encode([
                'success' => true,
                'via'     => 'power_automate_webhook',
                'task_id' => null,
                'etag'    => '',
                'url'     => 'https://tasks.office.com/',
                'bucket'  => planner_bucket_por_status($status),
                'titulo'  => $tituloCompleto,
                'aviso'   => 'Tarefa criada via Power Automate. task_id não disponível neste modo.',
            ]);
        }

    } elseif ($acao === 'mover') {
        if (!$taskId) throw new RuntimeException('task_id obrigatório.');

        $result = planner_mover_bucket($taskId, $planId, $status, $etag);

        echo json_encode([
            'success'   => true,
            'http_code' => $result['http_code'],
            'bucket'    => planner_bucket_por_status($status),
        ]);

    } elseif ($acao === 'concluir') {
        if (!$taskId || !$etag) throw new RuntimeException('task_id e etag obrigatórios.');

        $result = planner_atualizar_tarefa($taskId, 100, $etag);
        planner_mover_bucket($taskId, $planId, 'concluido', '');

        echo json_encode([
            'success'   => true,
            'http_code' => $result['http_code'],
            'bucket'    => '✅ Concluído',
        ]);

    } else {
        throw new RuntimeException('Ação inválida.');
    }

} catch (RuntimeException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
