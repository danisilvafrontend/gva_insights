<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_id'])) { header('Location: ../../index.php'); exit; }

include '../../includes/config.php';
include '../../includes/db_connect.php';
mysqli_set_charset($conn, 'utf8mb4');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

$id             = intval($_POST['id'] ?? 0);
$id_usuario     = intval($_POST['id_usuario'] ?? 0);
$id_categoria   = intval($_POST['id_categoria'] ?? 0);
$mes_referencia = $conn->real_escape_string(trim($_POST['mes_referencia'] ?? ''));
$acao           = $conn->real_escape_string(trim($_POST['acao'] ?? ''));
$tarefa         = $conn->real_escape_string(trim($_POST['tarefa'] ?? ''));
$tema_conteudo  = $conn->real_escape_string(trim($_POST['tema_conteudo'] ?? ''));
$detalhes_promocao = $conn->real_escape_string(trim($_POST['detalhes_promocao'] ?? ''));
$observacoes    = $conn->real_escape_string(trim($_POST['observacoes'] ?? ''));
$notes          = $conn->real_escape_string(trim($_POST['notes'] ?? ''));
$link_externo   = $conn->real_escape_string(trim($_POST['link_externo'] ?? ''));
$status_novo    = $conn->real_escape_string($_POST['status'] ?? 'Pendente');
$prioridade     = $conn->real_escape_string($_POST['prioridade'] ?? 'Media');
$user_id        = intval($_SESSION['user_id']);

if (!$id || !$tarefa || !$id_categoria || !$id_usuario) {
    echo "<script>alert('Campos obrigatórios não preenchidos.'); history.back();</script>";
    exit;
}

// Datas
$deadline  = 'NULL';
$data_acao = 'NULL';
if (!empty($_POST['deadline'])) {
    $d = DateTime::createFromFormat('Y-m-d', $_POST['deadline']);
    if ($d) $deadline = "'" . $d->format('Y-m-d') . "'";
}
if (!empty($_POST['data_acao'])) {
    $d = DateTime::createFromFormat('Y-m-d', $_POST['data_acao']);
    if ($d) $data_acao = "'" . $d->format('Y-m-d') . "'";
}

$conn->begin_transaction();
try {
    // Buscar status anterior para histórico
    $anterior = $conn->query("SELECT status FROM bdna_tarefas WHERE id = $id")->fetch_assoc()['status'] ?? '';

    $sql = "UPDATE bdna_tarefas SET
              id_usuario      = $id_usuario,
              id_categoria    = $id_categoria,
              mes_referencia  = '$mes_referencia',
              acao            = '$acao',
              tarefa          = '$tarefa',
              tema_conteudo   = '$tema_conteudo',
              detalhes_promocao = '$detalhes_promocao',
              observacoes     = '$observacoes',
              notes           = '$notes',
              link_externo    = '$link_externo',
              deadline        = $deadline,
              data_acao       = $data_acao,
              status          = '$status_novo',
              prioridade      = '$prioridade'
            WHERE id = $id";

    if (!$conn->query($sql)) throw new Exception($conn->error);

    // Atualizar parceiros
    $conn->query("DELETE FROM bdna_tarefas_parceiros WHERE id_tarefa = $id");
    if (!empty($_POST['parceiros']) && is_array($_POST['parceiros'])) {
        $stmtP = $conn->prepare('INSERT IGNORE INTO bdna_tarefas_parceiros (id_tarefa, id_parceiro) VALUES (?,?)');
        foreach ($_POST['parceiros'] as $pid) {
            $pid = intval($pid);
            $stmtP->bind_param('ii', $id, $pid);
            $stmtP->execute();
        }
        $stmtP->close();
    }

    // Histórico de status se mudou
    if ($anterior !== $status_novo) {
        $stmtH = $conn->prepare('INSERT INTO bdna_historico_status (id_tarefa, status_antes, status_depois, alterado_por) VALUES (?,?,?,?)');
        $stmtH->bind_param('issi', $id, $anterior, $status_novo, $user_id);
        $stmtH->execute();
        $stmtH->close();
    }

    $conn->commit();
    $conn->close();
    header('Location: ../index.php?ok=edit');
    exit;

} catch (Exception $e) {
    $conn->rollback();
    $conn->close();
    echo "<script>alert('Erro ao atualizar: " . addslashes($e->getMessage()) . "'); history.back();</script>";
}
