<?php
require_once '../../includes/auth.php';
require_login();
can_manage_registros() || (http_response_code(403) && exit('Acesso negado.'));

require_once '../../includes/db_connect.php';
require_once '../teams_webhook.php';
mysqli_set_charset($conn, 'utf8mb4');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: nova_demanda.php');
    exit;
}

$isAdmin  = is_admin();
$userId   = usuario_id();

// ── Captura e sanitização ────────────────────────────────────────────────────
$id_usuario    = $isAdmin ? (int)($_POST['id_usuario'] ?? 0) : $userId;
$categoria     = trim($_POST['categoria']    ?? '');
$mes           = trim($_POST['mes']          ?? '');
$acao          = trim($_POST['acao']         ?? '');
$tarefa        = trim($_POST['tarefa']       ?? '');
$tipo_conteudo = trim($_POST['tipo_conteudo']?? '');
$deadline      = trim($_POST['deadline']     ?? '');
$prioridade    = trim($_POST['prioridade']   ?? 'Media');
$status        = trim($_POST['status']       ?? 'Pendente');
$parceiros     = trim($_POST['parceiros']    ?? '');
$link_externo  = trim($_POST['link_externo'] ?? '');
$detalhes      = trim($_POST['detalhes']     ?? '');

// ── Validações básicas ───────────────────────────────────────────────────────
$erros = [];
if ($id_usuario <= 0)  $erros[] = 'Responsável não identificado.';
if (empty($categoria)) $erros[] = 'Categoria é obrigatória.';
if (empty($tarefa))    $erros[] = 'Tarefa / Demanda é obrigatória.';

if ($erros) {
    header('Location: nova_demanda.php?erro=' . urlencode(implode(' | ', $erros)));
    exit;
}

// ── Normaliza deadline ───────────────────────────────────────────────────────
if (!empty($deadline)) {
    $dt       = DateTime::createFromFormat('d/m/Y', $deadline)
             ?: DateTime::createFromFormat('Y-m-d', $deadline);
    $deadline = $dt ? $dt->format('Y-m-d') : '';
}

// ── INSERT ───────────────────────────────────────────────────────────────────
$sql  = "INSERT INTO demandas
         (id_usuario, categoria, mes, acao, tarefa, tipo_conteudo,
          deadline, prioridade, status, parceiros, link_externo, detalhes)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param('isssssssssss',
    $id_usuario, $categoria, $mes, $acao, $tarefa, $tipo_conteudo,
    $deadline, $prioridade, $status, $parceiros, $link_externo, $detalhes
);

if (!$stmt->execute()) {
    $err = urlencode('Erro ao salvar demanda: ' . $conn->error);
    header("Location: nova_demanda.php?erro=$err");
    $stmt->close();
    exit;
}
$novoId = $conn->insert_id;
$stmt->close();

// ── Busca nome do responsável para webhook ───────────────────────────────────
$stmtU = $conn->prepare("SELECT nome FROM usuarios WHERE id = ?");
$stmtU->bind_param('i', $id_usuario);
$stmtU->execute();
$rowU            = $stmtU->get_result()->fetch_assoc();
$stmtU->close();
$nomeResponsavel = $rowU['nome'] ?? 'Responsável';

// ── Notificação Teams ────────────────────────────────────────────────────────
$dadosTeams = [
    'id_usuario'   => $id_usuario,
    'responsavel'  => $nomeResponsavel,
    'categoria'    => $categoria,
    'tarefa'       => $tarefa,
    'mes'          => $mes,
    'deadline'     => $deadline,
    'prioridade'   => $prioridade,
    'status'       => $status,
    'observacoes'  => $detalhes,
    'link_sistema' => 'https://insights.gvacompany.com/demandas/index.php',
];
notificarTeams($dadosTeams);
notificarTeamsChat($dadosTeams);

$conn->close();
header('Location: ../index.php?sucesso=1');
exit;
