<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_id'])) { header('Location: ../../index.php'); exit; }

include '../../includes/config.php';
include '../../includes/db_connect.php';
mysqli_set_charset($conn, 'utf8mb4');

$id = intval($_GET['id'] ?? 0);
if (!$id) { header('Location: ../index.php'); exit; }

// ON DELETE CASCADE cuida de parceiros e histórico automaticamente
if ($conn->query("DELETE FROM bdna_tarefas WHERE id = $id")) {
    header('Location: ../index.php?ok=del');
} else {
    echo "<script>alert('Erro ao excluir: " . addslashes($conn->error) . "'); window.location.href='../index.php';</script>";
}

$conn->close();
