<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'Não autorizado']);
    exit;
}
require_once '../../config/db_connect.php';
mysqli_set_charset($conn, 'utf8mb4');

header('Content-Type: application/json');

$id     = (int)($_POST['id']     ?? 0);
$status = trim($_POST['status']  ?? '');

$statusValidos = ['Done','Em andamento','Produzindo','Enviado','Publicado','Aguardando','Pendente','Atrasado'];

if (!$id || !in_array($status, $statusValidos)) {
    echo json_encode(['success' => false, 'msg' => 'Dados inválidos']);
    exit;
}

$userId   = (int)$_SESSION['usuario_id'];
$isPerfil = $_SESSION['usuario_perfil'] ?? 'user';
$isAdmin  = ($isPerfil === 'admin');

// Usuário comum só pode alterar suas próprias demandas
$sql = $isAdmin
    ? "UPDATE demandas SET status = ? WHERE id = ?"
    : "UPDATE demandas SET status = ? WHERE id = ? AND id_usuario = ?";

$stmt = $conn->prepare($sql);
if ($isAdmin) {
    $stmt->bind_param('si', $status, $id);
} else {
    $stmt->bind_param('sii', $status, $id, $userId);
}

$ok = $stmt->execute();
$stmt->close();

// Verifica se ficou atrasado (deadline < hoje e status != Done)
$atrasado = false;
$row = $conn->query("SELECT deadline FROM demandas WHERE id = $id")->fetch_assoc();
if ($row && !empty($row['deadline'])) {
    $atrasado = ($row['deadline'] < date('Y-m-d') && $status !== 'Done');
}

$conn->close();
echo json_encode(['success' => $ok, 'atrasado' => $atrasado]);
