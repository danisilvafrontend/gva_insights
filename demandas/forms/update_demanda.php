<?php
require_once '../../includes/auth.php';
require_login();
require_once '../../includes/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ../index.php'); exit; }

$userId        = usuario_id();
$nivel         = usuario_nivel();
$isAdmin       = is_admin();
$podeGerenciar = can_manage_registros();

$id              = (int)($_POST['id'] ?? 0);
$idUsuario       = (int)($_POST['id_usuario'] ?? $userId);
$categoria       = trim($_POST['categoria']      ?? '');
$mes             = trim($_POST['mes']            ?? '');
$acao            = trim($_POST['acao']           ?? '');
$tarefa          = trim($_POST['tarefa']         ?? '');
$deadline        = !empty($_POST['deadline'])  ? $_POST['deadline']        : null;
$parceiros       = trim($_POST['parceiros']      ?? '');
$detalhes        = trim($_POST['detalhes']       ?? '');
$tipoConteudo    = trim($_POST['tipo_conteudo']  ?? '');
$linkExterno     = trim($_POST['link_externo']   ?? '');
$status          = trim($_POST['status']         ?? 'Pendente');
$prioridade      = trim($_POST['prioridade']     ?? 'Média');
$dataPublicacao  = !empty($_POST['data_publicacao']) ? $_POST['data_publicacao'] : null;

// ── Verifica permissão: nível 3 só pode atualizar suas próprias demandas ─────
if (!$podeGerenciar) {
    $stmtCheck = $conn->prepare("SELECT id, status FROM demandas WHERE id = ? AND id_usuario = ?");
    $stmtCheck->bind_param('ii', $id, $userId);
    $stmtCheck->execute();
    $stmtCheck->store_result();
    if ($stmtCheck->num_rows === 0) {
        header('Location: ../index.php');
        exit;
    }
    $stmtCheck->close();
    // Nível 3 não pode alterar o responsável
    $idUsuario = $userId;
} else {
    $stmtCheck = $conn->prepare("SELECT id, status FROM demandas WHERE id = ?");
    $stmtCheck->bind_param('i', $id);
    $stmtCheck->execute();
    $stmtCheck->store_result();
    if ($stmtCheck->num_rows === 0) { header('Location: ../index.php'); exit; }
    $stmtCheck->close();
}

// Busca status anterior para histórico
$stmtOld = $conn->prepare("SELECT status FROM demandas WHERE id = ?");
$stmtOld->bind_param('i', $id);
$stmtOld->execute();
$oldData   = $stmtOld->get_result()->fetch_assoc();
$stmtOld->close();
$oldStatus = $oldData['status'] ?? '';

// ── UPDATE com prepared statement ────────────────────────────────────────────
$sql  = "UPDATE demandas SET
            id_usuario      = ?,
            categoria       = ?,
            mes             = ?,
            acao            = ?,
            tarefa          = ?,
            deadline        = ?,
            parceiros       = ?,
            detalhes        = ?,
            tipo_conteudo   = ?,
            link_externo    = ?,
            status          = ?,
            prioridade      = ?,
            data_publicacao = ?,
            updated_at      = NOW()
         WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('issssssssssssi',
    $idUsuario, $categoria, $mes, $acao, $tarefa,
    $deadline, $parceiros, $detalhes, $tipoConteudo,
    $linkExterno, $status, $prioridade, $dataPublicacao, $id
);

if ($stmt->execute()) {
    // Registra histórico se status mudou
    if ($oldStatus !== $status) {
        $stmtH = $conn->prepare("INSERT INTO demandas_historico (id_demanda, id_usuario, status_anterior, status_novo, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmtH->bind_param('iiss', $id, $userId, $oldStatus, $status);
        $stmtH->execute();
        $stmtH->close();
    }
    $_SESSION['flash'] = ['type' => 'success', 'msg' => '✅ Demanda atualizada com sucesso!'];
} else {
    $_SESSION['flash'] = ['type' => 'danger',  'msg' => 'Erro ao atualizar: ' . $conn->error];
}

$stmt->close();
header('Location: ../index.php');
exit;
