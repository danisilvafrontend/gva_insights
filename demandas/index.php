<?php
require_once '../includes/auth.php';
require_login();
require_once '../includes/db_connect.php';

$userId  = usuario_id();
$isAdmin = is_admin();

// Filtros
$filtroStatus      = $_GET['status']      ?? '';
$filtroCategoria   = $_GET['categoria']   ?? '';
$filtroMes         = $_GET['mes']         ?? '';
$filtroResponsavel = $_GET['responsavel'] ?? '';

$where  = [];
$params = [];
$types  = '';

if (!$isAdmin) {
    $where[]  = 'd.id_usuario = ?';
    $params[] = $userId;
    $types   .= 'i';
}

if ($filtroStatus !== '') {
    $where[]  = 'd.status = ?';
    $params[] = $filtroStatus;
    $types   .= 's';
}
if ($filtroCategoria !== '') {
    $where[]  = 'd.categoria LIKE ?';
    $params[] = $filtroCategoria . '%';
    $types   .= 's';
}
if ($filtroMes !== '') {
    $where[]  = 'd.mes = ?';
    $params[] = $filtroMes;
    $types   .= 's';
}
if ($isAdmin && $filtroResponsavel !== '') {
    $where[]  = 'd.id_usuario = ?';
    $params[] = $filtroResponsavel;
    $types   .= 'i';
}

$sqlWhere = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "SELECT d.*, u.nome AS responsavel_nome
        FROM demandas d
        JOIN usuarios u ON d.id_usuario = u.id
        $sqlWhere
        ORDER BY CASE WHEN d.deadline IS NULL OR d.deadline = '' THEN 1 ELSE 0 END ASC,
                 d.deadline ASC";

$stmt = $conn->prepare($sql);
if ($params) { $stmt->bind_param($types, ...$params); }
$stmt->execute();
$todasDemandas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$statusFinal = ['Done', 'Enviado', 'Publicado'];
$hoje = date('Y-m-d');

$demandas   = array_filter($todasDemandas, fn($d) => !in_array($d['status'], $statusFinal));
$concluidas = array_filter($todasDemandas, fn($d) =>  in_array($d['status'], $statusFinal));
$demandas   = array_values($demandas);
$concluidas = array_values($concluidas);

$subtarefasPorDemanda = [];
$todasSubtarefas      = [];
if (!empty($todasDemandas)) {
    $ids = implode(',', array_column($todasDemandas, 'id'));
    $subWhere = $isAdmin ? '' : "AND s.id_usuario = $userId";
    $sqlSub = "SELECT s.*, u.nome AS responsavel_nome
               FROM subtarefas s
               JOIN usuarios u ON s.id_usuario = u.id
               WHERE s.id_demanda IN ($ids) $subWhere
               ORDER BY s.created_at ASC";
    $resSub = $conn->query($sqlSub);
    while ($sub = $resSub->fetch_assoc()) {
        $subtarefasPorDemanda[(int)$sub['id_demanda']][] = $sub;
        $todasSubtarefas[] = $sub;
    }
}

$kpiWhere  = !$isAdmin ? "WHERE id_usuario = $userId" : '';
$kpiResult = $conn->query("SELECT status, COUNT(*) as total FROM demandas $kpiWhere GROUP BY status");
$kpis = ['total'=>0,'concluidas'=>0,'em_andamento'=>0,'pendente'=>0,'atrasado'=>0];
while ($k = $kpiResult->fetch_assoc()) {
    $kpis['total'] += (int)$k['total'];
    if (in_array($k['status'], $statusFinal))   $kpis['concluidas']   += (int)$k['total'];
    if ($k['status'] === 'Em andamento')         $kpis['em_andamento'] += (int)$k['total'];
    if ($k['status'] === 'Pendente')             $kpis['pendente']     += (int)$k['total'];
}
foreach ($todasSubtarefas as $s) {
    $kpis['total']++;
    if (in_array($s['status'], $statusFinal))  $kpis['concluidas']++;
    if ($s['status'] === 'Em andamento')        $kpis['em_andamento']++;
    if ($s['status'] === 'Pendente')            $kpis['pendente']++;
}
foreach ($todasDemandas as $d) {
    if ($d['deadline'] && $d['deadline'] < $hoje && !in_array($d['status'], $statusFinal))
        $kpis['atrasado']++;
}
foreach ($todasSubtarefas as $s) {
    if ($s['deadline'] && $s['deadline'] < $hoje && !in_array($s['status'], $statusFinal))
        $kpis['atrasado']++;
}

$usuarios = [];
if ($isAdmin) {
    $resU = $conn->query("SELECT id, nome FROM usuarios ORDER BY nome");
    while ($u = $resU->fetch_assoc()) $usuarios[] = $u;
}

$categorias = [
    'Gestão & Planejamento' => ['Cronograma','Locais','Metas (clientes, marcas, números de pessoas)'],
    'Comunicação'           => ['Releases Brasil','Releases EUA','Vídeos promocionais','Newsletter','Redes Sociais','Plataforma'],
    'Documentação'          => ['Contratos','Invoice','Acordos','Clientes','Parceiros','Fornecedores'],
    'Organização e Execução'=> ['Webinars','Roadshow Presencial','Roadshow Virtual','Eventos Especiais'],
    'Relatórios'            => [],
];

$meses = ['Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

// SVG inline do Microsoft Planner (sem dependência externa)
define('PLANNER_SVG', '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 32 32" aria-hidden="true"><rect width="32" height="32" rx="4" fill="#0078d4"/><rect x="4" y="8" width="11" height="7" rx="1.5" fill="#fff"/><rect x="17" y="8" width="11" height="7" rx="1.5" fill="#50e6ff"/><rect x="4" y="17" width="7" height="7" rx="1.5" fill="#50e6ff"/><rect x="13" y="17" width="7" height="7" rx="1.5" fill="#fff"/><rect x="22" y="17" width="6" height="7" rx="1.5" fill="#50e6ff"/></svg>');

function renderRows(array $list, array $subtarefasPorDemanda, array $statusFinal, string $hoje, bool $isAdmin, bool $podeEditarFn, int $userId): void {
    if (empty($list)) {
        echo '<tr><td colspan="11" class="text-center text-muted py-4"><i class="bi bi-inbox fs-3 d-block mb-2"></i>Nenhuma demanda encontrada.</td></tr>';
        return;
    }
    foreach ($list as $d) {
        $atrasado    = ($d['deadline'] && $d['deadline'] < $hoje && !in_array($d['status'], $statusFinal));
        $concluida   = in_array($d['status'], $statusFinal);
        $prioClass   = ['Alta'=>'danger','Media'=>'warning','Baixa'=>'secondary'][$d['prioridade']] ?? 'secondary';
        $statusClass = [
            'Done'=>'success','Em andamento'=>'primary','Produzindo'=>'info',
            'Enviado'=>'success','Publicado'=>'success','Aguardando'=>'warning',
            'Pendente'=>'secondary','Atrasado'=>'danger'
        ][$d['status']] ?? 'secondary';
        $podeEditar  = pode_editar_tarefa((int)$d['id_usuario']);
        $subs        = $subtarefasPorDemanda[(int)$d['id']] ?? [];
        $temSubs     = !empty($subs);
        $subRowId    = 'subrow-' . $d['id'];
        $trClass     = $atrasado ? 'table-danger' : '';
        $titulo = ($d['acao'] ? $d['acao'] . ': ' : '') . mb_strimwidth($d['tarefa'], 0, 80, '...');
        ?>
        <tr class="demanda-row <?= $trClass ?>" id="demanda-tr-<?= $d['id'] ?>" data-id="<?= $d['id'] ?>" data-concluida="<?= $concluida ? '1' : '0' ?>">
            <td class="text-center">
                <?php if ($temSubs): ?>
                <button class="toggle-sub-btn" data-target="<?= $subRowId ?>" title="Ver subtarefas">
                    <i class="bi bi-diagram-3 text-primary"></i>
                    <span class="badge bg-primary sub-count-badge"><?= count($subs) ?></span>
                </button>
                <?php elseif ($isAdmin): ?>
                <a href="forms/nova_subtarefa.php?id_demanda=<?= $d['id'] ?>" class="toggle-sub-btn" title="Adicionar subtarefa">
                    <i class="bi bi-plus-circle text-muted"></i>
                </a>
                <?php endif; ?>
            </td>
            <td><?= $d['id'] ?></td>
            <td><?= htmlspecialchars($d['responsavel_nome']) ?></td>
            <td><small><?= htmlspecialchars($d['categoria']) ?></small></td>
            <td><?= htmlspecialchars($d['mes'] ?? '—') ?></td>
            <td>
                <strong><?= htmlspecialchars($d['acao'] ? $d['acao'] . ': ' : '') ?></strong>
                <?= htmlspecialchars(mb_strimwidth($d['tarefa'], 0, 80, '...')) ?>
            </td>
            <td><?= $d['deadline'] ? date('d/m/Y', strtotime($d['deadline'])) : '—' ?></td>
            <td><span class="badge bg-<?= $prioClass ?>"><?= $d['prioridade'] ?></span></td>
            <td>
                <?php if ($podeEditar): ?>
                <select class="form-select form-select-sm status-select" data-id="<?= $d['id'] ?>" style="min-width:130px">
                    <?php foreach (['Done','Em andamento','Produzindo','Enviado','Publicado','Aguardando','Pendente','Atrasado'] as $s): ?>
                    <option value="<?= $s ?>" <?= $d['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                    <?php endforeach; ?>
                </select>
                <?php else: ?>
                <span class="badge bg-<?= $statusClass ?>"><?= $d['status'] ?></span>
                <?php endif; ?>
            </td>
            <td>
                <?php if ($isAdmin): ?>
                <button
                    class="btn btn-sm btn-planner planner-btn"
                    title="Enviar para o Planner"
                    data-id="<?= $d['id'] ?>"
                    data-titulo="<?= htmlspecialchars($titulo, ENT_QUOTES) ?>"
                    data-deadline="<?= $d['deadline'] ?? '' ?>">
                    <?= PLANNER_SVG ?> Planner
                </button>
                <?php endif; ?>
            </td>
            <td>
                <?php if ($podeEditar): ?>
                <a href="forms/editar_demanda.php?id=<?= $d['id'] ?>" class="btn btn-sm btn-outline-primary" title="Editar"><i class="bi bi-pencil"></i></a>
                <?php endif; ?>
                <?php if ($isAdmin): ?>
                <a href="forms/nova_subtarefa.php?id_demanda=<?= $d['id'] ?>" class="btn btn-sm btn-outline-success" title="Adicionar Subtarefa"><i class="bi bi-diagram-3"></i></a>
                <a href="forms/deletar_demanda.php?id=<?= $d['id'] ?>" class="btn btn-sm btn-outline-danger" title="Excluir" onclick="return confirm('Confirma exclusão?')"><i class="bi bi-trash"></i></a>
                <?php endif; ?>
            </td>
        </tr>
        <?php if ($temSubs): ?>
        <tr class="subtask-row" id="<?= $subRowId ?>">
            <td colspan="11">
                <div class="subtask-block">
                    <div class="subtask-header">
                        <span class="text-muted" style="font-size:.8rem;font-weight:600;text-transform:uppercase;letter-spacing:.04em">
                            <i class="bi bi-diagram-3 me-1"></i>Subtarefas (<?= count($subs) ?>)
                        </span>
                        <?php if ($isAdmin): ?>
                        <a href="forms/nova_subtarefa.php?id_demanda=<?= $d['id'] ?>" class="btn btn-outline-success btn-sm py-0 px-2" style="font-size:.75rem">
                            <i class="bi bi-plus-lg me-1"></i>Adicionar
                        </a>
                        <?php endif; ?>
                    </div>
                    <?php foreach ($subs as $sub):
                        $subAtrasado  = ($sub['deadline'] && $sub['deadline'] < $hoje && !in_array($sub['status'], $statusFinal));
                        $subConcluida = in_array($sub['status'], $statusFinal);
                        $subPrioClass = ['Alta'=>'danger','Media'=>'warning','Baixa'=>'secondary'][$sub['prioridade']] ?? 'secondary';
                        $subStatusClass = [
                            'Done'=>'success','Em andamento'=>'primary','Produzindo'=>'info',
                            'Enviado'=>'success','Publicado'=>'success','Aguardando'=>'warning',
                            'Pendente'=>'secondary','Atrasado'=>'danger'
                        ][$sub['status']] ?? 'secondary';
                        $podEditSub   = $isAdmin || (int)$sub['id_usuario'] === $userId;
                        $subItemClass = $subAtrasado ? 'border-danger' : ($subConcluida ? 'border-success bg-success bg-opacity-10' : '');
                    ?>
                    <div class="subtask-item <?= $subItemClass ?>">
                        <span class="subtask-titulo">
                            <?= $subAtrasado ? '<i class="bi bi-exclamation-triangle-fill text-danger me-1"></i>' : '' ?>
                            <?= htmlspecialchars($sub['titulo']) ?>
                        </span>
                        <span class="subtask-resp"><i class="bi bi-person me-1"></i><?= htmlspecialchars($sub['responsavel_nome']) ?></span>
                        <?php if ($sub['deadline']): ?>
                        <span class="subtask-dead"><i class="bi bi-calendar3 me-1"></i><?= date('d/m/Y', strtotime($sub['deadline'])) ?></span>
                        <?php endif; ?>
                        <span class="badge bg-<?= $subPrioClass ?>" style="font-size:.7rem"><?= $sub['prioridade'] ?></span>
                        <?php if ($podEditSub): ?>
                        <select class="form-select form-select-sm sub-status-select" data-id="<?= $sub['id'] ?>" style="min-width:120px;font-size:.8rem">
                            <?php foreach (['Pendente','Em andamento','Produzindo','Aguardando','Enviado','Publicado','Done','Atrasado'] as $ss): ?>
                            <option value="<?= $ss ?>" <?= $sub['status'] === $ss ? 'selected' : '' ?>><?= $ss ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php else: ?>
                        <span class="badge bg-<?= $subStatusClass ?>" style="font-size:.7rem"><?= $sub['status'] ?></span>
                        <?php endif; ?>
                        <?php if ($podEditSub): ?>
                        <a href="forms/editar_subtarefa.php?id=<?= $sub['id'] ?>" class="btn btn-sm btn-outline-primary py-0 px-2" title="Editar subtarefa" style="font-size:.75rem"><i class="bi bi-pencil"></i></a>
                        <?php endif; ?>
                        <?php if ($isAdmin): ?>
                        <a href="forms/deletar_subtarefa.php?id=<?= $sub['id'] ?>&id_demanda=<?= $d['id'] ?>" class="btn btn-sm btn-outline-danger py-0 px-2" title="Excluir subtarefa" onclick="return confirm('Excluir esta subtarefa?')" style="font-size:.75rem"><i class="bi bi-trash"></i></a>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </td>
        </tr>
        <?php endif;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Demandas — GVA Insights</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/demandas.css">
    <style>
        .subtask-row td { background: #f8f9ff; padding: 0 !important; }
        .subtask-block { padding: .75rem 1.25rem .75rem 2.5rem; border-top: 1px solid #dee2e6; }
        .subtask-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:.5rem; }
        .subtask-item {
            display:flex; align-items:center; gap:.6rem;
            padding:.45rem .75rem; border-radius:.4rem;
            background:#fff; border:1px solid #e3e6f0;
            margin-bottom:.35rem; flex-wrap:wrap;
        }
        .subtask-item:last-child { margin-bottom:0; }
        .subtask-titulo { flex:1; min-width:160px; font-size:.875rem; font-weight:500; }
        .subtask-resp, .subtask-dead { font-size:.78rem; color:#6c757d; white-space:nowrap; }
        .toggle-sub-btn { background:none; border:none; padding:0 4px; color:#6c757d; cursor:pointer; }
        .toggle-sub-btn:hover { color:#0d6efd; }
        .sub-count-badge { font-size:.7rem; }
        .subtask-row { display: none; }

        /* Botão Planner */
        .btn-planner {
            background: #fff;
            border: 1px solid #0078d4;
            color: #0078d4;
            font-size: .75rem;
            padding: .2rem .5rem;
            display: inline-flex;
            align-items: center;
            gap: .3rem;
            white-space: nowrap;
            transition: background .2s;
        }
        .btn-planner:hover { background: #e6f0fa; }
        .btn-planner.enviado {
            border-color: #198754;
            color: #198754;
            pointer-events: none;
        }
        .btn-planner.loading { opacity: .6; pointer-events: none; }

        /* Tabela de concluídas */
        .section-concluidas { margin-top: 2.5rem; }
        .section-concluidas .toggle-concluidas-btn {
            background: none; border: none; cursor: pointer;
            display: flex; align-items: center; gap: .5rem;
            font-size: 1rem; font-weight: 600; color: #198754;
            padding: .5rem 0;
        }
        .section-concluidas .toggle-concluidas-btn:hover { color: #146c43; }
        .section-concluidas .toggle-concluidas-btn .chevron { transition: transform .25s ease; }
        .section-concluidas .toggle-concluidas-btn.open .chevron { transform: rotate(180deg); }
        #table-concluidas-wrapper { display: none; }
        #table-concluidas-wrapper.open { display: block; }
        #tbody-concluidas tr { opacity: .85; }
        #empty-concluidas { display: none; }
        #empty-ativas { display: none; }
    </style>
</head>
<body>
<?php include '../pages/header.php'; ?>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-2 sidebar"><?php include '../pages/menu_lateral.php'; ?></div>
        <div class="col-md-10 main-content py-4">

            <?php if ($flash): ?>
            <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
                <?= $flash['msg'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <!-- Título -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="mb-0"><i class="bi bi-kanban me-2 text-primary"></i>Controle de Atividades Equipe GVA</h4>
                </div>
                <a href="forms/nova_demanda.php" class="btn btn-primary">
                    <i class="bi bi-plus-lg me-1"></i> Nova Demanda
                </a>
            </div>

            <!-- KPIs -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-2">
                    <div class="kpi-card border-primary">
                        <div class="kpi-label">Total</div>
                        <div class="kpi-value text-primary"><?= $kpis['total'] ?></div>
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="kpi-card border-success">
                        <div class="kpi-label">Concluídas</div>
                        <div class="kpi-value text-success"><?= $kpis['concluidas'] ?></div>
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="kpi-card border-warning">
                        <div class="kpi-label">Em Andamento</div>
                        <div class="kpi-value text-warning"><?= $kpis['em_andamento'] ?></div>
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="kpi-card border-secondary">
                        <div class="kpi-label">Pendentes</div>
                        <div class="kpi-value text-secondary"><?= $kpis['pendente'] ?></div>
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="kpi-card border-danger">
                        <div class="kpi-label">Atrasadas</div>
                        <div class="kpi-value text-danger"><?= $kpis['atrasado'] ?></div>
                    </div>
                </div>
                <?php if ($kpis['total'] > 0): ?>
                <div class="col-6 col-md-2">
                    <div class="kpi-card border-info">
                        <div class="kpi-label">% Concluído</div>
                        <div class="kpi-value text-info"><?= round(($kpis['concluidas'] / $kpis['total']) * 100) ?>%</div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Filtros -->
            <form method="GET" class="row g-2 mb-4 p-3 bg-light rounded border">
                <div class="col-md-2">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">Todos os Status</option>
                        <?php foreach (['Done','Em andamento','Produzindo','Enviado','Publicado','Aguardando','Pendente','Atrasado'] as $s): ?>
                        <option value="<?= $s ?>" <?= $filtroStatus === $s ? 'selected' : '' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="categoria" class="form-select form-select-sm">
                        <option value="">Todas as Categorias</option>
                        <?php foreach ($categorias as $cat => $subs): ?>
                        <optgroup label="<?= htmlspecialchars($cat) ?>">
                            <option value="<?= htmlspecialchars($cat) ?>" <?= $filtroCategoria === $cat ? 'selected' : '' ?>>└ Todas de <?= htmlspecialchars($cat) ?></option>
                            <?php foreach ($subs as $sub): $val = $cat . ' › ' . $sub; ?>
                            <option value="<?= htmlspecialchars($val) ?>" <?= $filtroCategoria === $val ? 'selected' : '' ?>>&nbsp;&nbsp;&nbsp;<?= htmlspecialchars($sub) ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="mes" class="form-select form-select-sm">
                        <option value="">Todos os Meses</option>
                        <?php foreach ($meses as $m): ?>
                        <option value="<?= $m ?>" <?= $filtroMes === $m ? 'selected' : '' ?>><?= $m ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if ($isAdmin): ?>
                <div class="col-md-2">
                    <select name="responsavel" class="form-select form-select-sm">
                        <option value="">Todos os Responsáveis</option>
                        <?php foreach ($usuarios as $u): ?>
                        <option value="<?= $u['id'] ?>" <?= $filtroResponsavel == $u['id'] ? 'selected' : '' ?>><?= htmlspecialchars($u['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-funnel me-1"></i>Filtrar</button>
                </div>
                <div class="col-md-1">
                    <a href="index.php" class="btn btn-outline-secondary btn-sm w-100">Limpar</a>
                </div>
            </form>

            <!-- ═══ TABELA ATIVAS ═══ -->
            <div class="d-flex align-items-center gap-2 mb-2">
                <h5 class="mb-0"><i class="bi bi-list-task me-1 text-primary"></i>Em andamento / Pendentes</h5>
                <span class="badge bg-primary" id="badge-ativas"><?= count($demandas) ?></span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle" id="table-ativas">
                    <thead class="table-dark">
                        <tr>
                            <th style="width:30px"></th>
                            <th>#</th>
                            <th>Responsável</th>
                            <th>Categoria</th>
                            <th>Mês</th>
                            <th>Tarefa / Demanda</th>
                            <th>Deadline</th>
                            <th>Prioridade</th>
                            <th>Status</th>
                            <th>Planner</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-ativas">
                        <tr id="empty-ativas" style="<?= empty($demandas) ? '' : 'display:none' ?>">
                            <td colspan="11" class="text-center text-muted py-4">
                                <i class="bi bi-check2-all fs-3 d-block mb-2 text-success"></i>Todas as demandas estão concluídas!
                            </td>
                        </tr>
                        <?php renderRows($demandas, $subtarefasPorDemanda, $statusFinal, $hoje, $isAdmin, 'pode_editar_tarefa', $userId); ?>
                    </tbody>
                </table>
            </div>

            <!-- ═══ TABELA CONCLUÍDAS ═══ -->
            <div class="section-concluidas">
                <button class="toggle-concluidas-btn" id="btn-toggle-concluidas">
                    <i class="bi bi-check-circle-fill text-success"></i>
                    Concluídas
                    <span class="badge bg-success" id="badge-concluidas"><?= count($concluidas) ?></span>
                    <i class="bi bi-chevron-down chevron"></i>
                </button>

                <div id="table-concluidas-wrapper">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle table-sm" id="table-concluidas">
                            <thead class="table-success">
                                <tr>
                                    <th style="width:30px"></th>
                                    <th>#</th>
                                    <th>Responsável</th>
                                    <th>Categoria</th>
                                    <th>Mês</th>
                                    <th>Tarefa / Demanda</th>
                                    <th>Deadline</th>
                                    <th>Prioridade</th>
                                    <th>Status</th>
                                    <th>Planner</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody id="tbody-concluidas">
                                <tr id="empty-concluidas" style="<?= empty($concluidas) ? '' : 'display:none' ?>">
                                    <td colspan="11" class="text-center text-muted py-3">
                                        <i class="bi bi-inbox me-1"></i>Nenhuma demanda concluída ainda.
                                    </td>
                                </tr>
                                <?php renderRows($concluidas, $subtarefasPorDemanda, $statusFinal, $hoje, $isAdmin, 'pode_editar_tarefa', $userId); ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Toast notificação Planner -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index:9999">
    <div id="toastPlanner" class="toast align-items-center text-white border-0" role="alert">
        <div class="d-flex">
            <div class="toast-body" id="toastPlannerMsg"></div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<?php include '../pages/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const STATUS_FINAL = ['Done', 'Enviado', 'Publicado'];
const PLANNER_BTN_DEFAULT = `<?= PLANNER_SVG ?> Planner`;

// ── Toggle tabela concluídas ──
const btnToggle   = document.getElementById('btn-toggle-concluidas');
const wrapperConc = document.getElementById('table-concluidas-wrapper');
btnToggle.addEventListener('click', () => {
    const open = wrapperConc.classList.toggle('open');
    btnToggle.classList.toggle('open', open);
});

// ── Toggle subtarefas ──
function bindToggleSub(btn) {
    btn.addEventListener('click', function () {
        const row = document.getElementById(this.dataset.target);
        if (!row) return;
        const icon    = this.querySelector('i');
        const visible = row.style.display === 'table-row';
        row.style.display = visible ? 'none' : 'table-row';
        icon.className = visible ? 'bi bi-diagram-3 text-primary' : 'bi bi-dash-circle text-danger';
        this.title    = visible ? 'Ver subtarefas' : 'Ocultar subtarefas';
    });
}
document.querySelectorAll('.toggle-sub-btn[data-target]').forEach(bindToggleSub);

// ── Toast helper ──
function showToast(msg, tipo = 'success') {
    const el    = document.getElementById('toastPlanner');
    const msgEl = document.getElementById('toastPlannerMsg');
    el.classList.remove('bg-success', 'bg-danger');
    el.classList.add('bg-' + tipo);
    msgEl.textContent = msg;
    bootstrap.Toast.getOrCreateInstance(el, { delay: 4000 }).show();
}

function updateBadges() {
    const nAtivas = document.querySelectorAll('#tbody-ativas tr.demanda-row').length;
    const nConc   = document.querySelectorAll('#tbody-concluidas tr.demanda-row').length;
    document.getElementById('badge-ativas').textContent     = nAtivas;
    document.getElementById('badge-concluidas').textContent = nConc;
    document.getElementById('empty-ativas').style.display    = nAtivas === 0 ? '' : 'none';
    document.getElementById('empty-concluidas').style.display = nConc  === 0 ? '' : 'none';
}

function moveDemandaRow(trRow, toConcluidas) {
    const subRowId  = trRow.querySelector('.toggle-sub-btn[data-target]')?.dataset.target;
    const subRow    = subRowId ? document.getElementById(subRowId) : null;
    const destTbody = toConcluidas
        ? document.getElementById('tbody-concluidas')
        : document.getElementById('tbody-ativas');
    if (subRow) subRow.style.display = 'none';
    trRow.style.transition = 'opacity .3s';
    trRow.style.opacity = '0';
    setTimeout(() => {
        destTbody.appendChild(trRow);
        if (subRow) destTbody.appendChild(subRow);
        trRow.style.opacity = '1';
        trRow.dataset.concluida = toConcluidas ? '1' : '0';
        if (toConcluidas && !wrapperConc.classList.contains('open')) {
            wrapperConc.classList.add('open');
            btnToggle.classList.add('open');
        }
        updateBadges();
    }, 300);
}

function bindStatusSelect(sel) {
    sel.addEventListener('change', function () {
        const id = this.dataset.id, status = this.value;
        fetch('forms/update_status_demanda.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `id=${id}&status=${encodeURIComponent(status)}`
        })
        .then(r => r.json())
        .then(data => {
            if (!data.success) return;
            const tr = this.closest('tr.demanda-row');
            const eraConcluida   = tr.dataset.concluida === '1';
            const agoraConcluida = STATUS_FINAL.includes(status);
            tr.classList.remove('table-danger');
            if (data.atrasado && !agoraConcluida) tr.classList.add('table-danger');
            if (!eraConcluida && agoraConcluida)  moveDemandaRow(tr, true);
            else if (eraConcluida && !agoraConcluida) moveDemandaRow(tr, false);
        });
    });
}
document.querySelectorAll('.status-select').forEach(bindStatusSelect);

document.querySelectorAll('.sub-status-select').forEach(sel => {
    sel.addEventListener('change', function () {
        const id = this.dataset.id, status = this.value;
        fetch('forms/update_status_subtarefa.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `id=${id}&status=${encodeURIComponent(status)}`
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const item = this.closest('.subtask-item');
                item.classList.remove('border-danger', 'border-success', 'bg-success', 'bg-opacity-10');
                if (STATUS_FINAL.includes(status)) item.classList.add('border-success', 'bg-success', 'bg-opacity-10');
                else if (data.atrasado)             item.classList.add('border-danger');
                const icone = item.querySelector('.bi-exclamation-triangle-fill');
                if (data.atrasado && !icone)
                    item.querySelector('.subtask-titulo').insertAdjacentHTML('afterbegin', '<i class="bi bi-exclamation-triangle-fill text-danger me-1"></i>');
                else if (!data.atrasado && icone) icone.remove();
            }
        });
    });
});

// ── Botão Planner ──
function bindPlannerBtn(btn) {
    btn.addEventListener('click', function () {
        if (this.classList.contains('enviado') || this.classList.contains('loading')) return;
        const titulo   = this.dataset.titulo;
        const deadline = this.dataset.deadline;
        const btnEl    = this;
        btnEl.classList.add('loading');
        btnEl.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Enviando...';

        fetch('sync_planner.php', {
            method: 'POST',
            body: new URLSearchParams({ acao: 'criar', titulo, deadline })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                btnEl.classList.remove('loading');
                btnEl.classList.add('enviado');
                btnEl.innerHTML = '<i class="bi bi-check-circle-fill"></i> Enviado!';
                showToast('✅ Tarefa criada no Planner com sucesso!', 'success');
            } else {
                btnEl.classList.remove('loading');
                btnEl.innerHTML = PLANNER_BTN_DEFAULT;
                showToast('❌ Erro: ' + (data.error ?? 'Falha ao enviar'), 'danger');
            }
        })
        .catch(() => {
            btnEl.classList.remove('loading');
            btnEl.innerHTML = PLANNER_BTN_DEFAULT;
            showToast('❌ Erro de conexão com o servidor', 'danger');
        });
    });
}
document.querySelectorAll('.planner-btn').forEach(bindPlannerBtn);

updateBadges();
</script>
</body>
</html>
