<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../../index.php');
    exit;
}

include '../../../includes/config.php';
include '../../../includes/db_connect.php';
mysqli_set_charset($conn, 'utf8mb4');

$userId  = (int)$_SESSION['user_id'];
$isAdmin = isset($_SESSION['perfil']) && $_SESSION['perfil'] === 'admin';
$id      = (int)($_GET['id'] ?? 0);

if (!$id) { header('Location: ../index.php'); exit; }

// Não-admin só pode excluir as próprias
$whereOwn = $isAdmin ? '' : "AND id_usuario = $userId";
$t = $conn->query("SELECT id FROM bdna_tarefas WHERE id = $id $whereOwn")->fetch_assoc();

if ($t) {
    $conn->query("DELETE FROM bdna_tarefas_parceiros WHERE id_tarefa = $id");
    $conn->query("DELETE FROM bdna_historico_status WHERE id_tarefa = $id");
    $conn->query("DELETE FROM bdna_tarefas WHERE id = $id");
}

$conn->close();
header('Location: ../index.php?ok=del');
exit;
