<?php
/**
 * sync_planner.php
 * Endpoint chamado via fetch() para sincronizar uma demanda com o Microsoft Planner
 *
 * POST params:
 *   - acao      : 'criar' | 'concluir'
 *   - demanda_id: ID da demanda no banco
 *   - titulo    : título da tarefa
 *   - deadline  : data no formato Y-m-d (opcional)
 *   - task_id   : ID da tarefa no Planner (necessário para 'concluir')
 *   - etag      : eTag da tarefa no Planner (necessário para 'concluir')
 */

require_once '../includes/auth.php';
require_login();
require_once '../includes/ms_graph.php';

header('Content-Type: application/json');

$acao      = $_POST['acao']       ?? '';
$titulo    = $_POST['titulo']     ?? '';
$deadline  = $_POST['deadline']   ?? null;
$taskId    = $_POST['task_id']    ?? '';
$etag      = $_POST['etag']       ?? '';
$planId    = $_ENV['MS_PLANNER_PLAN_ID'] ?? '';

if (!$planId) {
    echo json_encode(['success' => false, 'error' => 'PLAN_ID não configurado no .env']);
    exit;
}

try {
    if ($acao === 'criar') {
        if (!$titulo) throw new RuntimeException('Título obrigatório.');
        $task = planner_criar_tarefa($titulo, $planId, $deadline ?: null);
        echo json_encode([
            'success' => true,
            'task_id' => $task['id'],
            'etag'    => $task['@odata.etag'] ?? '',
            'url'     => "https://tasks.office.com/",
        ]);

    } elseif ($acao === 'concluir') {
        if (!$taskId || !$etag) throw new RuntimeException('task_id e etag obrigatórios.');
        $result = planner_atualizar_tarefa($taskId, 100, $etag);
        echo json_encode(['success' => true, 'http_code' => $result['http_code']]);

    } else {
        throw new RuntimeException('Ação inválida.');
    }

} catch (RuntimeException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
