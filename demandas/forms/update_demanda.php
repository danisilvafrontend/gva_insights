<?php
session_start();
if (!isset($_SESSION['usuario_id'])) { header('Location: ../../index.php'); exit; }
require_once '../../config/db_connect.php';

$userId   = $_SESSION['usuario_id'];
$isAdmin  = ($_SESSION['usuario_perfil'] ?? 'user') === 'admin';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ../index.php'); exit; }

$id           = (int)$_POST['id'];
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

// Verifica permissão
$checkSql = $isAdmin ? "SELECT id, status FROM demandas WHERE id = $id" : "SELECT id, status FROM demandas WHERE id = $id AND id_usuario = $userId";
$checkRes = $conn->query($checkSql);
if (!$checkRes || $checkRes->num_rows === 0) { header('Location: ../index.php'); exit; }
$oldData = $checkRes->fetch_assoc();
$oldStatus = $oldData['status'];

$deadlineVal = $deadline ? "'$deadline'" : 'NULL';
$dataPublicacaoVal = $dataPublicacao ? "'$dataPublicacao'" : 'NULL';

$sql = "UPDATE demandas SET
        id_usuario = $idUsuario,
        categoria = '$categoria',
        mes = '$mes',
        acao = '$acao',
        tarefa = '$tarefa',
        deadline = $deadlineVal,
        parceiros = '$parceiros',
        detalhes = '$detalhes',
        tipo_conteudo = '$tipoConteudo',
        link_externo = '$linkExterno',
        status = '$status',
        prioridade = '$prioridade',
        data_publicacao = $dataPublicacaoVal,
        updated_at = NOW()
        WHERE id = $id";

if ($conn->query($sql)) {
    // Histórico se status mudou
    if ($oldStatus !== $status) {
        $conn->query("INSERT INTO demandas_historico (id_demanda, id_usuario, status_anterior, status_novo, created_at) VALUES ($id, $userId, '$oldStatus', '$status', NOW())");
    }
    $_SESSION['flash'] = ['type'=>'success','msg'=>'✅ Demanda atualizada com sucesso!'];
} else {
    $_SESSION['flash'] = ['type'=>'danger','msg'=>'Erro ao atualizar: ' . $conn->error];
}
header('Location: ../index.php'); exit;
