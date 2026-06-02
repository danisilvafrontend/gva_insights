<?php
require_once '../../includes/auth.php';
require_login();
require_once '../../includes/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ../index.php'); exit; }

$userId  = usuario_id();
$isAdmin = is_admin();

$id         = (int)($_POST['id']          ?? 0);
$titulo     = trim($_POST['titulo']       ?? '');
$descricao  = trim($_POST['descricao']    ?? '');
$deadline   = !empty($_POST['deadline'])  ? $_POST['deadline'] : null;
$prioridade = trim($_POST['prioridade']   ?? 'Media');
$status     = trim($_POST['status']       ?? 'Pendente');

if (!$id || empty($titulo)) { header('Location: ../index.php'); exit; }

// Busca subtarefa para verificar permissão
$stmtS = $conn->prepare("SELECT id_usuario FROM subtarefas WHERE id = ?");
$stmtS->bind_param('i', $id);
$stmtS->execute();
$row = $stmtS->get_result()->fetch_assoc();
$stmtS->close();

if (!$row) { header('Location: ../index.php'); exit; }

// Admin edita qualquer subtarefa; nível 2 só edita as próprias
if (!$isAdmin && (int)$row['id_usuario'] !== $userId) {
    $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Você não tem permissão para editar esta subtarefa.'];
    header('Location: ../index.php');
    exit;
}

// Somente admin pode trocar responsável; outros mantêm o original
$idUsuario = $isAdmin
    ? (int)($_POST['id_usuario'] ?? $row['id_usuario'])
    : (int)$row['id_usuario'];

$statusValidos = ['Pendente','Em andamento','Produzindo','Aguardando','Done','Atrasado'];
if (!in_array($status, $statusValidos, true)) $status = 'Pendente';

$stmt = $conn->prepare(
    "UPDATE subtarefas
     SET id_usuario = ?, titulo = ?, descricao = ?, deadline = ?,
         prioridade = ?, status = ?, updated_at = NOW()
     WHERE id = ?"
);
$stmt->bind_param('isssssi',
    $idUsuario, $titulo, $descricao, $deadline, $prioridade, $status, $id
);

if ($stmt->execute()) {
    $_SESSION['flash'] = ['type' => 'success', 'msg' => '✅ Subtarefa atualizada com sucesso!'];
} else {
    $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Erro ao atualizar: ' . $conn->error];
}

$stmt->close();
$conn->close();
header('Location: ../index.php');
exit;
