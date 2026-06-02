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

$id     = (int)($_POST['id']     ?? 0);
$status = trim($_POST['status']  ?? '');

$statusValidos = ['Pendente','Em andamento','Produzindo','Aguardando','Done','Atrasado'];

if (!$id || !in_array($status, $statusValidos, true)) {
    echo json_encode(['success' => false, 'msg' => 'Dados inválidos']);
    exit;
}

$userId  = usuario_id();
$isAdmin = is_admin();

// Busca responsável da subtarefa
$stmtS = $conn->prepare("SELECT id_usuario, deadline FROM subtarefas WHERE id = ?");
$stmtS->bind_param('i', $id);
$stmtS->execute();
$row = $stmtS->get_result()->fetch_assoc();
$stmtS->close();

if (!$row) {
    echo json_encode(['success' => false, 'msg' => 'Subtarefa não encontrada']);
    exit;
}

// Admin atualiza qualquer; nível 2 atualiza somente as próprias
if (!$isAdmin && (int)$row['id_usuario'] !== $userId) {
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'Sem permissão para alterar esta subtarefa']);
    exit;
}

$stmt = $conn->prepare("UPDATE subtarefas SET status = ?, updated_at = NOW() WHERE id = ?");
$stmt->bind_param('si', $status, $id);
$ok = $stmt->execute();
$stmt->close();

// Verifica se ficou atrasado
$atrasado = false;
if (!empty($row['deadline'])) {
    $atrasado = ($row['deadline'] < date('Y-m-d') && $status !== 'Done');
}

$conn->close();
echo json_encode(['success' => $ok, 'atrasado' => $atrasado]);
