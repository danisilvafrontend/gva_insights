<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['usuario_id'])) { echo json_encode(['success'=>false]); exit; }
require_once '../../config/db_connect.php';

$userId  = $_SESSION['usuario_id'];
$isAdmin = ($_SESSION['usuario_perfil'] ?? 'user') === 'admin';
$id      = (int)($_POST['id'] ?? 0);
$status  = $conn->real_escape_string(trim($_POST['status'] ?? ''));

$allowed = ['Done','Em andamento','Produzindo','Enviado','Publicado','Aguardando','Pendente','Atrasado'];
if (!$id || !in_array($status, $allowed)) { echo json_encode(['success'=>false]); exit; }

$sqlCheck = $isAdmin
    ? "SELECT id, status, deadline FROM demandas WHERE id = $id"
    : "SELECT id, status, deadline FROM demandas WHERE id = $id AND id_usuario = $userId";

$res = $conn->query($sqlCheck);
if (!$res || $res->num_rows === 0) { echo json_encode(['success'=>false]); exit; }
$old = $res->fetch_assoc();

$conn->query("UPDATE demandas SET status = '$status', updated_at = NOW() WHERE id = $id");

if ($old['status'] !== $status) {
    $conn->query("INSERT INTO demandas_historico (id_demanda, id_usuario, status_anterior, status_novo, created_at) VALUES ($id, $userId, '{$old['status']}', '$status', NOW())");
}

$hoje = date('Y-m-d');
$atrasado = ($old['deadline'] && $old['deadline'] < $hoje && $status !== 'Done');

echo json_encode(['success'=>true, 'atrasado'=>$atrasado]);
