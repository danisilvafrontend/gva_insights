<?php
require_once '../../includes/auth.php';
require_nivel(1); // somente admin (nível 1) pode excluir demandas

require_once '../../includes/db_connect.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: ../index.php'); exit; }

$stmt = $conn->prepare("SELECT id FROM demandas WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Demanda não encontrada.'];
    header('Location: ../index.php');
    exit;
}
$stmt->close();

// Deleta histórico primeiro (FK) e depois a demanda
$conn->query("DELETE FROM demandas_historico WHERE id_demanda = $id");
$conn->query("DELETE FROM demandas WHERE id = $id");

$_SESSION['flash'] = ['type' => 'success', 'msg' => '🗑️ Demanda excluída com sucesso.'];
header('Location: ../index.php');
exit;
