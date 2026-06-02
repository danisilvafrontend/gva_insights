<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_id'])) { header('Location: ../../index.php'); exit; }

include '../../includes/config.php';
include '../../includes/db_connect.php';
include '../teams_webhook.php';
include '../enviar_email.php';
mysqli_set_charset($conn, 'utf8mb4');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: registrar_tarefa.php');
    exit;
}

// --- Sanitizar entradas ---
$id_usuario        = intval($_POST['id_usuario'] ?? 0);
$id_categoria      = intval($_POST['id_categoria'] ?? 0);
$mes_referencia    = $conn->real_escape_string(trim($_POST['mes_referencia'] ?? ''));
$acao              = $conn->real_escape_string(trim($_POST['acao'] ?? ''));
$tarefa            = $conn->real_escape_string(trim($_POST['tarefa'] ?? ''));
$tema_conteudo     = $conn->real_escape_string(trim($_POST['tema_conteudo'] ?? ''));
$detalhes_promocao = $conn->real_escape_string(trim($_POST['detalhes_promocao'] ?? ''));
$observacoes       = $conn->real_escape_string(trim($_POST['observacoes'] ?? ''));
$notes             = $conn->real_escape_string(trim($_POST['notes'] ?? ''));
$link_externo      = $conn->real_escape_string(trim($_POST['link_externo'] ?? ''));
$status            = $conn->real_escape_string($_POST['status'] ?? 'Pendente');
$prioridade        = $conn->real_escape_string($_POST['prioridade'] ?? 'Media');
$created_by        = intval($_SESSION['user_id']);

// Datas
$deadline     = null;
$deadline_raw = '';
if (!empty($_POST['deadline'])) {
    $d = DateTime::createFromFormat('Y-m-d', $_POST['deadline']);
    if ($d) {
        $deadline     = "'" . $d->format('Y-m-d') . "'";
        $deadline_raw = $d->format('Y-m-d');
    }
}

$data_acao = null;
if (!empty($_POST['data_acao'])) {
    $d = DateTime::createFromFormat('Y-m-d', $_POST['data_acao']);
    if ($d) $data_acao = "'" . $d->format('Y-m-d') . "'";
}

if (!$tarefa || !$id_categoria || !$id_usuario) {
    echo "<script>alert('Preencha os campos obrigat\u00f3rios.'); history.back();</script>";
    exit;
}

$conn->begin_transaction();

try {
    $sql = "INSERT INTO bdna_tarefas
              (id_usuario, id_categoria, mes_referencia, acao, tarefa, tema_conteudo,
               detalhes_promocao, observacoes, notes, link_externo,
               deadline, data_acao, status, prioridade, created_by)
            VALUES
              ($id_usuario, $id_categoria,
               '$mes_referencia', '$acao', '$tarefa', '$tema_conteudo',
               '$detalhes_promocao', '$observacoes', '$notes', '$link_externo',
               " . ($deadline ?? 'NULL') . ",
               " . ($data_acao ?? 'NULL') . ",
               '$status', '$prioridade', $created_by)";

    if (!$conn->query($sql)) throw new Exception($conn->error);
    $id_tarefa = $conn->insert_id;

    // Parceiros
    if (!empty($_POST['parceiros']) && is_array($_POST['parceiros'])) {
        $stmtP = $conn->prepare('INSERT IGNORE INTO bdna_tarefas_parceiros (id_tarefa, id_parceiro) VALUES (?, ?)');
        foreach ($_POST['parceiros'] as $pid) {
            $pid = intval($pid);
            $stmtP->bind_param('ii', $id_tarefa, $pid);
            $stmtP->execute();
        }
        $stmtP->close();
    }

    // Histórico
    $stmtH = $conn->prepare('INSERT INTO bdna_historico_status (id_tarefa, status_antes, status_depois, alterado_por) VALUES (?, NULL, ?, ?)');
    $stmtH->bind_param('isi', $id_tarefa, $status, $created_by);
    $stmtH->execute();
    $stmtH->close();

    $conn->commit();

    // ----- Buscar dados do responsável -----
    $nomeResp  = 'N/A';
    $nomeCat   = 'N/A';
    $emailResp = '';

    $rUser = $conn->query("SELECT nome, email FROM usuarios WHERE id = $id_usuario LIMIT 1");
    if ($rUser && $row = $rUser->fetch_assoc()) {
        $nomeResp  = $row['nome'];
        $emailResp = $row['email'];
    }

    $rCat = $conn->query("SELECT nome FROM bdna_categorias WHERE id = $id_categoria LIMIT 1");
    if ($rCat && $row = $rCat->fetch_assoc()) $nomeCat = $row['nome'];

    $dadosNotif = [
        'id_usuario'   => $id_usuario,          // ← necessario para notificarTeamsChat()
        'responsavel'  => $nomeResp,
        'email'        => $emailResp,
        'categoria'    => $nomeCat,
        'tarefa'       => stripslashes($tarefa),
        'mes'          => $mes_referencia,
        'deadline'     => $deadline_raw,
        'prioridade'   => stripslashes($prioridade),
        'status'       => stripslashes($status),
        'observacoes'  => stripslashes($observacoes),
        'link_sistema' => 'https://insights.gvacompany.com/brasil_dna/',
    ];

    // ----- Notificar canal do Teams (toda a equipe) -----
    notificarTeams($dadosNotif);

    // ----- Notificar chat privado do responsável no Teams -----
    notificarTeamsChat($dadosNotif);

    // ----- E-mail com convite .ics para o responsável -----
    enviarEmailTarefa($dadosNotif);

    $conn->close();
    header('Location: ../index.php?ok=1');
    exit;

} catch (Exception $e) {
    $conn->rollback();
    $conn->close();
    echo "<script>alert('Erro ao salvar: " . addslashes($e->getMessage()) . "'); history.back();</script>";
}