<?php
require_once '../../includes/auth.php';

if (!usuario_logado()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'Não autorizado']);
    exit;
}

require_once '../../includes/db_connect.php';
mysqli_set_charset($conn, 'utf8mb4');

header('Content-Type: application/json');

$id     = (int)($_POST['id']    ?? 0);
$status = trim($_POST['status'] ?? '');

$statusValidos = ['Done','Em andamento','Produzindo','Enviado','Publicado','Aguardando','Pendente','Atrasado'];

if (!$id || !in_array($status, $statusValidos, true)) {
    echo json_encode(['success' => false, 'msg' => 'Dados inválidos']);
    exit;
}

$userId = usuario_id();

// Busca o id_usuario responsável pela demanda
$stmtOwner = $conn->prepare("SELECT id_usuario FROM demandas WHERE id = ?");
$stmtOwner->bind_param('i', $id);
$stmtOwner->execute();
$owner = $stmtOwner->get_result()->fetch_assoc();
$stmtOwner->close();

if (!$owner) {
    echo json_encode(['success' => false, 'msg' => 'Demanda não encontrada']);
    exit;
}

// Verifica permissão usando a função centralizada do auth.php
if (!pode_editar_tarefa((int)$owner['id_usuario'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'Sem permissão para alterar esta demanda']);
    exit;
}

$stmt = $conn->prepare("UPDATE demandas SET status = ? WHERE id = ?");
$stmt->bind_param('si', $status, $id);
$ok = $stmt->execute();
$stmt->close();

// Verifica se ficou atrasado
$atrasado = false;
$row = $conn->query("SELECT deadline FROM demandas WHERE id = $id")->fetch_assoc();
if ($row && !empty($row['deadline'])) {
    $atrasado = ($row['deadline'] < date('Y-m-d') && $status !== 'Done');
}

$conn->close();
echo json_encode(['success' => $ok, 'atrasado' => $atrasado]);
