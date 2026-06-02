<?php
require_once '../../includes/auth.php';
require_login();
require_once '../../includes/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ../index.php'); exit; }

$userId        = usuario_id();
$nivel         = usuario_nivel();
$isAdmin       = is_admin();
$podeGerenciar = can_manage_registros();

$id             = (int)($_POST['id'] ?? 0);
$idUsuario      = (int)($_POST['id_usuario'] ?? $userId);
$categoria      = trim($_POST['categoria']      ?? '');
$mes            = trim($_POST['mes']            ?? '');
$acao           = trim($_POST['acao']           ?? '');
$tarefa         = trim($_POST['tarefa']         ?? '');
$deadline       = !empty($_POST['deadline'])       ? $_POST['deadline']        : null;
$detalhes       = trim($_POST['detalhes']       ?? '');
$tipoConteudo   = trim($_POST['tipo_conteudo']  ?? '');
$linkExterno    = trim($_POST['link_externo']   ?? '');
$status         = trim($_POST['status']         ?? 'Pendente');
$prioridade     = trim($_POST['prioridade']     ?? 'Média');
$dataPublicacao = !empty($_POST['data_publicacao']) ? $_POST['data_publicacao'] : null;

// Arrays de relação (IDs inteiros, pode vir vazio se nenhum chip marcado)
$empresas_envolvidas = array_map('intval', $_POST['empresas_envolvidas'] ?? []);
$clientes_envolvidos = array_map('intval', $_POST['clientes_envolvidos'] ?? []);

// ── Verifica permissão: nível 3 só pode atualizar suas próprias demandas ────────
if (!$podeGerenciar) {
    $stmtCheck = $conn->prepare("SELECT id, status FROM demandas WHERE id = ? AND id_usuario = ?");
    $stmtCheck->bind_param('ii', $id, $userId);
    $stmtCheck->execute();
    $stmtCheck->store_result();
    if ($stmtCheck->num_rows === 0) { header('Location: ../index.php'); exit; }
    $stmtCheck->close();
    $idUsuario = $userId; // nível 3 não pode trocar o responsável
} else {
    $stmtCheck = $conn->prepare("SELECT id FROM demandas WHERE id = ?");
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

// ── UPDATE principal (tabela demandas) ──────────────────────────────
$sql  = "UPDATE demandas SET
            id_usuario      = ?,
            categoria       = ?,
            mes             = ?,
            acao            = ?,
            tarefa          = ?,
            deadline        = ?,
            detalhes        = ?,
            tipo_conteudo   = ?,
            link_externo    = ?,
            status          = ?,
            prioridade      = ?,
            data_publicacao = ?,
            updated_at      = NOW()
         WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('issssssssssi',
    $idUsuario, $categoria, $mes, $acao, $tarefa,
    $deadline, $detalhes, $tipoConteudo,
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

    // ── Sincroniza empresas: apaga tudo e reinclui marcados ────────────────
    $conn->prepare("DELETE FROM demandas_empresas WHERE id_demanda = ?")->bind_param('i', $id) | null;
    $stmtDel = $conn->prepare("DELETE FROM demandas_empresas WHERE id_demanda = ?");
    $stmtDel->bind_param('i', $id);
    $stmtDel->execute();
    $stmtDel->close();

    if (!empty($empresas_envolvidas)) {
        $stmtEmp = $conn->prepare("INSERT IGNORE INTO demandas_empresas (id_demanda, id_empresa) VALUES (?, ?)");
        foreach ($empresas_envolvidas as $idEmpresa) {
            if ($idEmpresa > 0) {
                $stmtEmp->bind_param('ii', $id, $idEmpresa);
                $stmtEmp->execute();
            }
        }
        $stmtEmp->close();
    }

    // ── Sincroniza clientes: apaga tudo e reinclui marcados ────────────────
    $stmtDel2 = $conn->prepare("DELETE FROM demandas_clientes WHERE id_demanda = ?");
    $stmtDel2->bind_param('i', $id);
    $stmtDel2->execute();
    $stmtDel2->close();

    if (!empty($clientes_envolvidos)) {
        $stmtCli = $conn->prepare("INSERT IGNORE INTO demandas_clientes (id_demanda, id_cliente) VALUES (?, ?)");
        foreach ($clientes_envolvidos as $idCliente) {
            if ($idCliente > 0) {
                $stmtCli->bind_param('ii', $id, $idCliente);
                $stmtCli->execute();
            }
        }
        $stmtCli->close();
    }

    $_SESSION['flash'] = ['type' => 'success', 'msg' => '✅ Demanda atualizada com sucesso!'];

} else {
    $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Erro ao atualizar: ' . $conn->error];
}

$stmt->close();
header('Location: ../index.php');
exit;
