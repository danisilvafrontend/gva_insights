<?
error_reporting(E_ALL);
ini_set('display_errors', 1);
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

// ── Captura e saneamento ──────────────────────────────────────
$id_usuario    = $isAdmin ? (int)($_POST['id_usuario'] ?? 0) : $userId;
$categoria     = trim($_POST['categoria']     ?? '');
$mes           = trim($_POST['mes']           ?? '');
$acao          = trim($_POST['acao']          ?? '');
$tarefa        = trim($_POST['tarefa']        ?? '');
$tipo_conteudo = trim($_POST['tipo_conteudo'] ?? '');
$prioridade    = trim($_POST['prioridade']    ?? 'Media');
$status        = trim($_POST['status']        ?? 'Pendente');
$link_externo  = trim($_POST['link_externo']  ?? '');
$detalhes      = trim($_POST['detalhes']      ?? '');

// Arrays de relação (IDs inteiros)
$empresas_envolvidas = array_map('intval', $_POST['empresas_envolvidas'] ?? []);
$clientes_envolvidos = array_map('intval', $_POST['clientes_envolvidos'] ?? []);

// ── Normaliza deadline — string vazia ou inválida vira NULL ───────────
$deadlineRaw = trim($_POST['deadline'] ?? '');
$deadline    = null; // default NULL — campo DATE aceita NULL, não aceita ''
if (!empty($deadlineRaw)) {
    $dt = DateTime::createFromFormat('d/m/Y', $deadlineRaw)
       ?: DateTime::createFromFormat('Y-m-d', $deadlineRaw);
    $deadline = $dt ? $dt->format('Y-m-d') : null;
}

// ── Validações básicas ────────────────────────────────────
$erros = [];
if ($id_usuario <= 0)  $erros[] = 'Responsável não identificado.';
if (empty($categoria)) $erros[] = 'Categoria é obrigatória.';
if (empty($tarefa))    $erros[] = 'Tarefa / Demanda é obrigatória.';

if ($erros) {
    header('Location: nova_demanda.php?erro=' . urlencode(implode(' | ', $erros)));
    exit;
}

// ── INSERT principal (tabela demandas) ───────────────────────────
$sql  = "INSERT INTO demandas
         (id_usuario, categoria, mes, acao, tarefa, tipo_conteudo,
          deadline, prioridade, status, link_externo, detalhes)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param('issssssssss',
    $id_usuario, $categoria, $mes, $acao, $tarefa, $tipo_conteudo,
    $deadline, $prioridade, $status, $link_externo, $detalhes
);

if (!$stmt->execute()) {
    $err = urlencode('Erro ao salvar demanda: ' . $conn->error);
    header("Location: nova_demanda.php?erro=$err");
    $stmt->close();
    exit;
}
$novoId = $conn->insert_id;
$stmt->close();

// ── Salva empresas envolvidas ─────────────────────────────────
if (!empty($empresas_envolvidas)) {
    $stmtEmp = $conn->prepare("INSERT IGNORE INTO demandas_empresas (id_demanda, id_empresa) VALUES (?, ?)");
    foreach ($empresas_envolvidas as $id_Empresa) {
        if ($id_Empresa > 0) {
            $stmtEmp->bind_param('ii', $novo_Id, $id_Empresa);
            $stmtEmp->execute();
        }
    }
    $stmtEmp->close();
}

// ── Salva clientes envolvidos ────────────────────────────────
if (!empty($clientes_envolvidos)) {
    $stmtCli = $conn->prepare("INSERT IGNORE INTO demandas_clientes (id_demanda, id_cliente) VALUES (?, ?)");
    foreach ($clientes_envolvidos as $id_Cliente) {
        if ($id_Cliente > 0) {
            $stmtCli->bind_param('ii', $novoId, $id_Cliente);
            $stmtCli->execute();
        }
    }
    $stmtCli->close();
}

// ── Busca nome do responsável + nomes de empresas e clientes para o webhook ──
$stmtU = $conn->prepare("SELECT nome FROM usuarios WHERE id = ?");
$stmtU->bind_param('i', $id_usuario);
$stmtU->execute();
$rowU            = $stmtU->get_result()->fetch_assoc();
$stmtU->close();
$nomeResponsavel = $rowU['nome'] ?? 'Responsável';

$nomesEmpresas = '';
if (!empty($empresas_envolvidas)) {
    $placeholders = implode(',', array_fill(0, count($empresas_envolvidas), '?'));
    $types        = str_repeat('i', count($empresas_envolvidas));
    $stmtE2       = $conn->prepare("SELECT empresa FROM empresas WHERE id IN ($placeholders)");
    $stmtE2->bind_param($types, ...$empresas_envolvidas);
    $stmtE2->execute();
    $rowsE         = $stmtE2->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmtE2->close();
    $nomesEmpresas = implode(', ', array_column($rowsE, 'empresa'));
}

$nomesClientes = '';
if (!empty($clientes_envolvidos)) {
    $placeholders = implode(',', array_fill(0, count($clientes_envolvidos), '?'));
    $types        = str_repeat('i', count($clientes_envolvidos));
    $stmtC2       = $conn->prepare("SELECT company FROM clientes WHERE id IN ($placeholders)");
    $stmtC2->bind_param($types, ...$clientes_envolvidos);
    $stmtC2->execute();
    $rowsC         = $stmtC2->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmtC2->close();
    $nomesClientes = implode(', ', array_column($rowsC, 'company'));
}

// ── Notificação Teams ──────────────────────────────────────────
$dadosTeams = [
    'id_usuario'   => $id_usuario,
    'responsavel'  => $nomeResponsavel,
    'categoria'    => $categoria,
    'tarefa'       => $tarefa,
    'mes'          => $mes,
    'deadline'     => $deadline ?? '',
    'prioridade'   => $prioridade,
    'status'       => $status,
    'observacoes'  => $detalhes,
    'empresas'     => $nomesEmpresas,
    'clientes'     => $nomesClientes,
    'link_sistema' => 'https://insights.gvacompany.com/demandas/index.php',
];
notificarTeams($dadosTeams);
notificarTeamsChat($dadosTeams);

$conn->close();
header('Location: ../index.php?sucesso=1');
exit;
