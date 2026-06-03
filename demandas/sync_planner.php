<?php
/**
 * sync_planner.php
 * Endpoint chamado via fetch() para sincronizar uma demanda com o Microsoft Planner
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

$acao      = $_POST['acao']       ?? '';
$titulo    = $_POST['titulo']     ?? '';
$empresa   = $_POST['empresa']    ?? '';
$status    = $_POST['status']     ?? 'pendente';
$deadline  = $_POST['deadline']   ?? null;
$taskId    = $_POST['task_id']    ?? '';
$etag      = $_POST['etag']       ?? '';
$planId    = $_ENV['MS_PLANNER_PLAN_ID'] ?? '';

if (!$planId) {
    echo json_encode(['success' => false, 'error' => 'PLAN_ID não configurado no .env']);
    exit;
}

// Monta o título com a empresa: "[Empresa] Título da tarefa"
$tituloCompleto = $empresa ? "[{$empresa}] {$titulo}" : $titulo;

try {
    if ($acao === 'criar') {
        if (!$titulo) throw new RuntimeException('Título obrigatório.');

        $task = planner_criar_tarefa(
            $tituloCompleto,
            $planId,
            $deadline ?: null,
            null,
            $status
        );

        echo json_encode([
            'success'  => true,
            'task_id'  => $task['id'],
            'etag'     => $task['@odata.etag'] ?? '',
            'url'      => 'https://tasks.office.com/',
            'bucket'   => planner_bucket_por_status($status),
            'titulo'   => $tituloCompleto,
        ]);

    } elseif ($acao === 'mover') {
        // Muda a tarefa de bucket quando o status muda no GVA
        if (!$taskId) throw new RuntimeException('task_id obrigatório.');

        $result = planner_mover_bucket($taskId, $planId, $status, $etag);

        echo json_encode([
            'success'    => true,
            'http_code'  => $result['http_code'],
            'bucket'     => planner_bucket_por_status($status),
        ]);

    } elseif ($acao === 'concluir') {
        if (!$taskId || !$etag) throw new RuntimeException('task_id e etag obrigatórios.');

        // Marca 100% E move para bucket Concluído
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
