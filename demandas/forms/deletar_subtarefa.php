<?php
require_once '../../includes/auth.php';
require_login();

// Somente admin pode excluir subtarefas
if (!is_admin()) {
    $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Apenas administradores podem excluir subtarefas.'];
    header('Location: ../index.php');
    exit;
}

require_once '../../includes/db_connect.php';

$id        = (int)($_GET['id']         ?? 0);
$idDemanda = (int)($_GET['id_demanda'] ?? 0);

if (!$id) {
    header('Location: ../index.php');
    exit;
}

// Verifica se a subtarefa existe
$stmt = $conn->prepare("SELECT id FROM subtarefas WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Subtarefa não encontrada.'];
    header('Location: ../index.php');
    exit;
}
$stmt->close();

$stmtD = $conn->prepare("DELETE FROM subtarefas WHERE id = ?");
$stmtD->bind_param('i', $id);

if ($stmtD->execute()) {
    $_SESSION['flash'] = ['type' => 'success', 'msg' => '🗑️ Subtarefa excluída com sucesso.'];
} else {
    $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Erro ao excluir subtarefa: ' . $conn->error];
}

$stmtD->close();
$conn->close();
header('Location: ../index.php');
exit;
