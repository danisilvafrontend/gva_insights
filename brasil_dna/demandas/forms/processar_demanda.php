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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: nova_demanda.php');
    exit;
}

// Definir responsável
$idUsuario = $isAdmin && !empty($_POST['id_usuario'])
    ? (int)$_POST['id_usuario']
    : $userId;

// Sanitizar campos
$idCategoria    = (int)($_POST['id_categoria']   ?? 0);
$tarefa         = trim($conn->real_escape_string($_POST['tarefa']          ?? ''));
$mesReferencia  = trim($conn->real_escape_string($_POST['mes_referencia']  ?? ''));
$deadline       = trim($_POST['deadline'] ?? '');
$prioridade     = in_array($_POST['prioridade'] ?? '', ['Alta','Media','Baixa'])
                    ? $_POST['prioridade'] : 'Media';
$acao           = trim($conn->real_escape_string($_POST['acao']            ?? ''));
$temaConteudo   = trim($conn->real_escape_string($_POST['tema_conteudo']   ?? ''));
$dataPublicacao = trim($_POST['data_publicacao'] ?? '');
$linkExterno    = trim($conn->real_escape_string($_POST['link_externo']    ?? ''));
$detalhes       = trim($conn->real_escape_string($_POST['detalhes']        ?? ''));
$parceiros      = isset($_POST['parceiros']) && is_array($_POST['parceiros'])
                    ? array_map('intval', $_POST['parceiros']) : [];

// Validação mínima
if (!$idCategoria || !$tarefa) {
    header('Location: nova_demanda.php?erro=campos');
    exit;
}

// Formatar datas
$deadlineSQL       = $deadline       ? "'$deadline'"       : 'NULL';
$dataPubSQL        = $dataPublicacao ? "'$dataPublicacao'" : 'NULL';
$mesRefSQL         = $mesReferencia  ? "'$mesReferencia'"  : 'NULL';

// Inserir tarefa
$sqlInsert = "INSERT INTO bdna_tarefas
    (id_usuario, id_categoria, tarefa, mes_referencia, deadline, prioridade,
     acao, tema_conteudo, data_publicacao, link_externo, detalhes, status, created_at)
    VALUES
    ($idUsuario, $idCategoria, '$tarefa', $mesRefSQL, $deadlineSQL, '$prioridade',
     '$acao', '$temaConteudo', $dataPubSQL, '$linkExterno', '$detalhes', 'Pendente', NOW())";

if (!$conn->query($sqlInsert)) {
    error_log('Erro processar_demanda: ' . $conn->error);
    header('Location: nova_demanda.php?erro=db');
    exit;
}

$newId = (int)$conn->insert_id;

// Inserir parceiros vinculados
if ($newId && count($parceiros) > 0) {
    foreach ($parceiros as $pId) {
        if ($pId > 0) {
            $conn->query("INSERT INTO bdna_tarefas_parceiros (id_tarefa, id_parceiro) VALUES ($newId, $pId)");
        }
    }
}

// Registrar no histórico
$conn->query("INSERT INTO bdna_historico_status (id_tarefa, id_usuario, status_anterior, status_novo, observacao, created_at)
    VALUES ($newId, $userId, NULL, 'Pendente', 'Demanda criada', NOW())");

$conn->close();
header('Location: ../index.php?ok=1');
exit;
