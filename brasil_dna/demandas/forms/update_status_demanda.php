<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['ok' => false, 'msg' => 'Não autenticado']);
    exit;
}

include '../../../includes/config.php';
include '../../../includes/db_connect.php';
mysqli_set_charset($conn, 'utf8mb4');

$userId  = (int)$_SESSION['user_id'];
$isAdmin = isset($_SESSION['perfil']) && $_SESSION['perfil'] === 'admin';
$id      = (int)($_POST['id']     ?? 0);
$status  = trim($_POST['status']  ?? '');

$statusValidos = ['Pendente','Em andamento','Produzindo','Aguardando','Enviado','Publicado','Done'];

if (!$id || !in_array($status, $statusValidos)) {
    echo json_encode(['ok' => false, 'msg' => 'Dados inválidos']);
    exit;
}

// Não-admin só pode alterar as próprias
$whereOwn = $isAdmin ? '' : "AND id_usuario = $userId";
$atual = $conn->query("SELECT status FROM bdna_tarefas WHERE id = $id $whereOwn")->fetch_assoc();

if (!$atual) {
    echo json_encode(['ok' => false, 'msg' => 'Tarefa não encontrada ou sem permissão']);
    exit;
}

$statusEsc = $conn->real_escape_string($status);
$conn->query("UPDATE bdna_tarefas SET status = '$statusEsc', updated_at = NOW() WHERE id = $id");

// Registrar histórico
if ($atual['status'] !== $status) {
    $sAnt = $conn->real_escape_string($atual['status']);
    $conn->query("INSERT INTO bdna_historico_status (id_tarefa, id_usuario, status_anterior, status_novo, created_at)
        VALUES ($id, $userId, '$sAnt', '$statusEsc', NOW())");
}

$conn->close();
echo json_encode(['ok' => true, 'status' => $status]);
