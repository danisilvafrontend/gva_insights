<?php
require_once '../../includes/auth.php';
require_admin(); // somente admin pode excluir demandas

require_once '../../includes/db_connect.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: ../index.php'); exit; }

// Verifica se a demanda existe
$stmtCheck = $conn->prepare("SELECT id FROM demandas WHERE id = ?");
$stmtCheck->bind_param('i', $id);
$stmtCheck->execute();
$stmtCheck->store_result();

if ($stmtCheck->num_rows === 0) {
    $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Demanda não encontrada.'];
    header('Location: ../index.php');
    exit;
}
$stmtCheck->close();

// Remove registros relacionados (FKs) e depois a demanda
$stmtH = $conn->prepare("DELETE FROM demandas_historico WHERE id_demanda = ?");
$stmtH->bind_param('i', $id);
$stmtH->execute();
$stmtH->close();

$stmtE = $conn->prepare("DELETE FROM demandas_empresas WHERE id_demanda = ?");
$stmtE->bind_param('i', $id);
$stmtE->execute();
$stmtE->close();

$stmtC = $conn->prepare("DELETE FROM demandas_clientes WHERE id_demanda = ?");
$stmtC->bind_param('i', $id);
$stmtC->execute();
$stmtC->close();

$stmtD = $conn->prepare("DELETE FROM demandas WHERE id = ?");
$stmtD->bind_param('i', $id);
$stmtD->execute();
$stmtD->close();

$conn->close();

$_SESSION['flash'] = ['type' => 'success', 'msg' => '🗑️ Demanda excluída com sucesso.'];
header('Location: ../index.php');
exit;
