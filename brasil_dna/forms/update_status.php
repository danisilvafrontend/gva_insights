<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['ok' => false, 'msg' => 'Não autorizado']);
    exit;
}

include '../../includes/config.php';
include '../../includes/db_connect.php';
mysqli_set_charset($conn, 'utf8mb4');

$id          = intval($_POST['id'] ?? 0);
$status_novo = trim($_POST['status'] ?? '');
$user_id     = intval($_SESSION['user_id']);

$statusValidos = ['Pendente','Em andamento','Produzindo','Aguardando','Enviado','Publicado','Done'];

if (!$id || !in_array($status_novo, $statusValidos)) {
    echo json_encode(['ok' => false, 'msg' => 'Dados inválidos']);
    $conn->close();
    exit;
}

$anterior = $conn->query("SELECT status FROM bdna_tarefas WHERE id = $id")->fetch_assoc()['status'] ?? '';

$s   = $conn->real_escape_string($status_novo);
$sql = "UPDATE bdna_tarefas SET status = '$s' WHERE id = $id";

if ($conn->query($sql)) {
    // Registrar no histórico se mudou
    if ($anterior !== $status_novo) {
        $stmtH = $conn->prepare('INSERT INTO bdna_historico_status (id_tarefa, status_antes, status_depois, alterado_por) VALUES (?,?,?,?)');
        $stmtH->bind_param('issi', $id, $anterior, $status_novo, $user_id);
        $stmtH->execute();
        $stmtH->close();
    }
    echo json_encode(['ok' => true]);
} else {
    echo json_encode(['ok' => false, 'msg' => $conn->error]);
}

$conn->close();
