<?php
require_once '../../includes/auth.php';
require_login();

if (!is_admin()) {
    $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Sem permissão para criar subtarefas.'];
    header('Location: ../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ../index.php'); exit; }

require_once '../../includes/db_connect.php';

$idDemanda  = (int)($_POST['id_demanda']  ?? 0);
$idUsuario  = (int)($_POST['id_usuario']  ?? 0);
$titulo     = trim($_POST['titulo']       ?? '');
$descricao  = trim($_POST['descricao']    ?? '');
$deadline   = !empty($_POST['deadline'])  ? $_POST['deadline'] : null;
$prioridade = trim($_POST['prioridade']   ?? 'Media');
$createdBy  = usuario_id();

if (!$idDemanda || !$idUsuario || empty($titulo)) {
    $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Preencha todos os campos obrigatórios.'];
    header('Location: nova_subtarefa.php?id_demanda=' . $idDemanda);
    exit;
}

// Verifica se a demanda existe
$stmtD = $conn->prepare("SELECT id FROM demandas WHERE id = ?");
$stmtD->bind_param('i', $idDemanda);
$stmtD->execute();
if (!$stmtD->get_result()->fetch_assoc()) {
    $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Demanda não encontrada.'];
    header('Location: ../index.php');
    exit;
}
$stmtD->close();

$stmt = $conn->prepare(
    "INSERT INTO subtarefas (id_demanda, id_usuario, titulo, descricao, deadline, prioridade, status, created_by, created_at)
     VALUES (?, ?, ?, ?, ?, ?, 'Pendente', ?, NOW())"
);
$stmt->bind_param('iissssi',
    $idDemanda, $idUsuario, $titulo, $descricao, $deadline, $prioridade, $createdBy
);

if ($stmt->execute()) {
    $_SESSION['flash'] = ['type' => 'success', 'msg' => '✅ Subtarefa criada com sucesso!'];
} else {
    $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Erro ao criar subtarefa: ' . $conn->error];
}

$stmt->close();
$conn->close();
header('Location: ../index.php');
exit;
