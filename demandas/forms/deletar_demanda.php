<?php
session_start();
if (!isset($_SESSION['usuario_id'])) { header('Location: ../../index.php'); exit; }
require_once '../../config/db_connect.php';

$userId  = $_SESSION['usuario_id'];
$isAdmin = ($_SESSION['usuario_perfil'] ?? 'user') === 'admin';
$id      = (int)($_GET['id'] ?? 0);

if (!$id) { header('Location: ../index.php'); exit; }

$sqlCheck = $isAdmin
    ? "SELECT id FROM demandas WHERE id = $id"
    : "SELECT id FROM demandas WHERE id = $id AND id_usuario = $userId";

$res = $conn->query($sqlCheck);
if (!$res || $res->num_rows === 0) {
    $_SESSION['flash'] = ['type'=>'danger','msg'=>'Demanda não encontrada ou sem permissão.'];
    header('Location: ../index.php'); exit;
}

// Deleta histórico primeiro (FK)
$conn->query("DELETE FROM demandas_historico WHERE id_demanda = $id");
$conn->query("DELETE FROM demandas WHERE id = $id");

$_SESSION['flash'] = ['type'=>'success','msg'=>'🗑️ Demanda excluída com sucesso.'];
header('Location: ../index.php'); exit;
