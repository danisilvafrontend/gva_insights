<?php
require_once '../../includes/auth.php';
require_login();
require_once '../../includes/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ../index.php'); exit; }

$userId  = usuario_id();
$isAdmin = is_admin();

$id             = (int)($_POST['id'] ?? 0);
$categoria      = trim($_POST['categoria']      ?? '');
$mes            = trim($_POST['mes']            ?? '');
$acao           = trim($_POST['acao']           ?? '');
$tarefa         = trim($_POST['tarefa']         ?? '');
$deadline       = !empty($_POST['deadline'])        ? $_POST['deadline']        : null;
$detalhes       = trim($_POST['detalhes']       ?? '');
$tipoConteudo   = trim($_POST['tipo_conteudo']  ?? '');
$linkExterno    = trim($_POST['link_externo']   ?? '');
$status         = trim($_POST['status']         ?? 'Pendente');
$prioridade     = trim($_POST['prioridade']     ?? 'Media');
$dataPublicacao = !empty($_POST['data_publicacao']) ? $_POST['data_publicacao'] : null;

$empresas_envolvidas = array_map('intval', $_POST['empresas_envolvidas'] ?? []);
$clientes_envolvidos = array_map('intval', $_POST['clientes_envolvidos'] ?? []);

if (!$id) { header('Location: ../index.php'); exit; }

// ── Busca demanda (id_usuario + status) numa única query ─────────────
$stmtD = $conn->prepare("SELECT id_usuario, status FROM demandas WHERE id = ?");
$stmtD->bind_param('i', $id);
$stmtD->execute();
$rowD = $stmtD->get_result()->fetch_assoc();
$stmtD->close();

if (!$rowD) { header('Location: ../index.php'); exit; }

// ── Verifica permissão via auth.php ──────────────────────────────────
// Nível 1 (admin) e Nível 2 (operacional) podem editar qualquer demanda
if (!pode_editar_tarefa((int)$rowD['id_usuario'])) {
    $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Você não tem permissão para editar esta demanda.'];
    header('Location: ../index.php');
    exit;
}

// Somente admin pode trocar o responsável; demais mantêm o original do banco
$idUsuario = $isAdmin
    ? (int)($_POST['id_usuario'] ?? $rowD['id_usuario'])
    : (int)$rowD['id_usuario'];

$oldStatus = $rowD['status'] ?? '';

// ── Validações ────────────────────────────────────────────────────────
$categoriasValidas = [
    'Gestão & Planejamento › Cronograma',
    'Gestão & Planejamento › Locais',
    'Gestão & Planejamento › Metas (clientes, marcas, números de pessoas)',
    'Gestão & Planejamento › Parcerias',
    'Gestão & Planejamento › Controle e Follow Up',
    'Comunicação › Videos Promo',
    'Comunicação › Webinars',
    'Comunicação › Releases Brasil',
    'Comunicação › Releases EUA',
    'Comunicação › Newsletter',
    'Comunicação › Posts SoMe',
    'Comunicação › Plataforma',
    'Documentação › Contratos',
    'Documentação › Invoice',
    'Documentação › Acordos',
    'Documentação › Clientes',
    'Documentação › Parceiros',
    'Documentação › Fornecedores',
    'Organização e Execução › Roadshow Presencial',
    'Organização e Execução › Roadshow Virtual',
    'Organização e Execução › Eventos Especiais',
    'Organização e Execução › Agenda B2B',
    'Organização e Execução › Travel Arrangements',
    'Organização e Execução › Promoção e RSVP',
    'Relatórios › Template de Relatórios',
    'Relatórios › Atualização de Dados',
    'Relatórios › Entrega de Relatórios',
];

if (empty($tarefa)) {
    $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Tarefa não pode ser vazia.'];
    header('Location: editar_demanda.php?id=' . $id);
    exit;
}

if (!empty($categoria) && !in_array($categoria, $categoriasValidas)) {
    $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Categoria inválida.'];
    header('Location: editar_demanda.php?id=' . $id);
    exit;
}

// ── UPDATE principal ──────────────────────────────────────────────────
$sql = "UPDATE demandas SET
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
$stmt->bind_param('isssssssssssi',
    $idUsuario, $categoria, $mes, $acao, $tarefa,
    $deadline, $detalhes, $tipoConteudo, $linkExterno,
    $status, $prioridade, $dataPublicacao, $id
);

if ($stmt->execute()) {

    // Registra histórico se status mudou
    if ($oldStatus !== $status) {
        $stmtH = $conn->prepare("INSERT INTO demandas_historico (id_demanda, id_usuario, status_anterior, status_novo, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmtH->bind_param('iiss', $id, $userId, $oldStatus, $status);
        $stmtH->execute();
        $stmtH->close();
    }

    // ── Sincroniza empresas ───────────────────────────────────────────
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

    // ── Sincroniza clientes ───────────────────────────────────────────
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
$conn->close();
header('Location: ../index.php');
exit;
