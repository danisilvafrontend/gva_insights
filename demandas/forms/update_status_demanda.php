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

$userId        = usuario_id();
$podeGerenciar = can_manage_registros();

// Nível 3 só pode alterar status das suas próprias demandas
if ($podeGerenciar) {
    $sql  = "UPDATE demandas SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('si', $status, $id);
} else {
    $sql  = "UPDATE demandas SET status = ? WHERE id = ? AND id_usuario = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sii', $status, $id, $userId);
}

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
