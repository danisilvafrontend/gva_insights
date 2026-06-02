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
$id      = (int)($_POST['id'] ?? 0);

if (!$id || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

// Verificar posse (não-admin só edita as próprias)
$whereOwn = $isAdmin ? '' : "AND id_usuario = $userId";
$atual = $conn->query("SELECT status FROM bdna_tarefas WHERE id = $id $whereOwn")->fetch_assoc();
if (!$atual) { header('Location: ../index.php?erro=nao_encontrado'); exit; }

// Sanitizar
$idCategoria    = (int)($_POST['id_categoria']   ?? 0);
$idUsuarioUpd   = $isAdmin && !empty($_POST['id_usuario']) ? (int)$_POST['id_usuario'] : $userId;
$tarefa         = trim($conn->real_escape_string($_POST['tarefa']          ?? ''));
$mesReferencia  = trim($conn->real_escape_string($_POST['mes_referencia']  ?? ''));
$deadline       = trim($_POST['deadline'] ?? '');
$prioridade     = in_array($_POST['prioridade'] ?? '', ['Alta','Media','Baixa']) ? $_POST['prioridade'] : 'Media';
$statusNovo     = in_array($_POST['status'] ?? '', ['Pendente','Em andamento','Produzindo','Aguardando','Enviado','Publicado','Done'])
                    ? $_POST['status'] : 'Pendente';
$acao           = trim($conn->real_escape_string($_POST['acao']            ?? ''));
$temaConteudo   = trim($conn->real_escape_string($_POST['tema_conteudo']   ?? ''));
$dataPublicacao = trim($_POST['data_publicacao'] ?? '');
$linkExterno    = trim($conn->real_escape_string($_POST['link_externo']    ?? ''));
$detalhes       = trim($conn->real_escape_string($_POST['detalhes']        ?? ''));
$parceiros      = isset($_POST['parceiros']) && is_array($_POST['parceiros'])
                    ? array_map('intval', $_POST['parceiros']) : [];

$deadlineSQL    = $deadline       ? "'$deadline'"       : 'NULL';
$dataPubSQL     = $dataPublicacao ? "'$dataPublicacao'" : 'NULL';
$mesRefSQL      = $mesReferencia  ? "'$mesReferencia'"  : 'NULL';

$sqlUpd = "UPDATE bdna_tarefas SET
    id_usuario      = $idUsuarioUpd,
    id_categoria    = $idCategoria,
    tarefa          = '$tarefa',
    mes_referencia  = $mesRefSQL,
    deadline        = $deadlineSQL,
    prioridade      = '$prioridade',
    status          = '$statusNovo',
    acao            = '$acao',
    tema_conteudo   = '$temaConteudo',
    data_publicacao = $dataPubSQL,
    link_externo    = '$linkExterno',
    detalhes        = '$detalhes',
    updated_at      = NOW()
    WHERE id = $id";

if (!$conn->query($sqlUpd)) {
    error_log('Erro update_demanda: ' . $conn->error);
    header('Location: editar_demanda.php?id=' . $id . '&erro=db');
    exit;
}

// Histórico de status (se mudou)
if ($atual['status'] !== $statusNovo) {
    $sAnt = $conn->real_escape_string($atual['status']);
    $conn->query("INSERT INTO bdna_historico_status (id_tarefa, id_usuario, status_anterior, status_novo, created_at)
        VALUES ($id, $userId, '$sAnt', '$statusNovo', NOW())");
}

// Atualizar parceiros: remover todos e reinserir
$conn->query("DELETE FROM bdna_tarefas_parceiros WHERE id_tarefa = $id");
foreach ($parceiros as $pId) {
    if ($pId > 0) {
        $conn->query("INSERT INTO bdna_tarefas_parceiros (id_tarefa, id_parceiro) VALUES ($id, $pId)");
    }
}

$conn->close();
header('Location: ../index.php?ok=edit');
exit;
