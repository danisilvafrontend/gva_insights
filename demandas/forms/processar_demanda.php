<?php
session_start();
if (!isset($_SESSION['usuario_id'])) { header('Location: ../../index.php'); exit; }
require_once '../../config/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ../index.php'); exit; }

$idUsuario    = (int)$_POST['id_usuario'];
$categoria    = $conn->real_escape_string(trim($_POST['categoria'] ?? ''));
$mes          = $conn->real_escape_string(trim($_POST['mes'] ?? ''));
$acao         = $conn->real_escape_string(trim($_POST['acao'] ?? ''));
$tarefa       = $conn->real_escape_string(trim($_POST['tarefa'] ?? ''));
$deadline     = !empty($_POST['deadline']) ? $conn->real_escape_string($_POST['deadline']) : null;
$parceiros    = $conn->real_escape_string(trim($_POST['parceiros'] ?? ''));
$detalhes     = $conn->real_escape_string(trim($_POST['detalhes'] ?? ''));
$tipoConteudo = $conn->real_escape_string(trim($_POST['tipo_conteudo'] ?? ''));
$linkExterno  = $conn->real_escape_string(trim($_POST['link_externo'] ?? ''));
$status       = $conn->real_escape_string(trim($_POST['status'] ?? 'Pendente'));
$prioridade   = $conn->real_escape_string(trim($_POST['prioridade'] ?? 'Média'));
$dataPublicacao = !empty($_POST['data_publicacao']) ? $conn->real_escape_string($_POST['data_publicacao']) : null;

if (!$idUsuario || !$categoria || !$tarefa) {
    $_SESSION['flash'] = ['type'=>'danger','msg'=>'Preencha os campos obrigatórios.'];
    header('Location: nova_demanda.php'); exit;
}

$deadlineVal = $deadline ? "'$deadline'" : 'NULL';
$dataPublicacaoVal = $dataPublicacao ? "'$dataPublicacao'" : 'NULL';

$sql = "INSERT INTO demandas
        (id_usuario, categoria, mes, acao, tarefa, deadline, parceiros, detalhes, tipo_conteudo, link_externo, status, prioridade, data_publicacao, created_at, updated_at)
        VALUES
        ($idUsuario, '$categoria', '$mes', '$acao', '$tarefa', $deadlineVal, '$parceiros', '$detalhes', '$tipoConteudo', '$linkExterno', '$status', '$prioridade', $dataPublicacaoVal, NOW(), NOW())";

if ($conn->query($sql)) {
    $newId = $conn->insert_id;
    // Registra histórico
    $conn->query("INSERT INTO demandas_historico (id_demanda, id_usuario, status_anterior, status_novo, created_at) VALUES ($newId, $idUsuario, '', '$status', NOW())");
    $_SESSION['flash'] = ['type'=>'success','msg'=>'✅ Demanda cadastrada com sucesso!'];
    header('Location: ../index.php'); exit;
} else {
    $_SESSION['flash'] = ['type'=>'danger','msg'=>'Erro ao salvar: ' . $conn->error];
    header('Location: nova_demanda.php'); exit;
}
