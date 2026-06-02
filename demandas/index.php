<?php
require_once '../includes/auth.php';
require_login();
require_once '../includes/db_connect.php';

$userId          = usuario_id();
$userNome        = usuario_nome();
$nivel           = usuario_nivel();
$isAdmin         = ($nivel === 1);
$podeGerenciar   = ($nivel <= 2); // níveis 1 e 2 vêem todas as demandas

// Filtros
$filtroStatus      = $_GET['status']      ?? '';
$filtroCategoria   = $_GET['categoria']   ?? '';
$filtroMes         = $_GET['mes']         ?? '';
$filtroResponsavel = $_GET['responsavel'] ?? '';

$where  = [];
$params = [];
$types  = '';

// Nível 3 vê apenas as próprias demandas
if (!$podeGerenciar) {
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
    $where[]  = 'd.categoria = ?';
    $params[] = $filtroCategoria;
    $types   .= 's';
}
if ($filtroMes !== '') {
    $where[]  = 'd.mes = ?';
    $params[] = $filtroMes;
    $types   .= 's';
}
if ($podeGerenciar && $filtroResponsavel !== '') {
    $where[]  = 'd.id_usuario = ?';
    $params[] = $filtroResponsavel;
    $types   .= 'i';
}

$sqlWhere = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "SELECT d.*, u.nome AS responsavel_nome
        FROM demandas d
        JOIN usuarios u ON d.id_usuario = u.id
        $sqlWhere
        ORDER BY d.created_at DESC";

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result   = $stmt->get_result();
$demandas = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// KPIs
$kpiBase   = !$podeGerenciar ? "WHERE id_usuario = $userId" : '';
$kpiSql    = "SELECT status, COUNT(*) as total FROM demandas $kpiBase GROUP BY status";
$kpiResult = $conn->query($kpiSql);
$kpis      = ['total' => 0, 'Done' => 0, 'Em andamento' => 0, 'Pendente' => 0, 'Atrasado' => 0];
while ($k = $kpiResult->fetch_assoc()) {
    $kpis[$k['status']] = (int)$k['total'];
    $kpis['total']     += (int)$k['total'];
}

// Lista usuários para filtro (níveis 1 e 2)
$usuarios = [];
if ($podeGerenciar) {
    $resU = $conn->query("SELECT id, nome FROM usuarios ORDER BY nome");
    while ($u = $resU->fetch_assoc()) $usuarios[] = $u;
}

$categorias = [
    'Gestão & Planejamento',
    'Videos Promo',
    'Webinars',
    'News & Releases',
    'Posts SoMe',
    'Roadshow Presencial',
    'Roadshow Virtual / Eventos Especiais'
];
$meses = ['Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
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
</head>
<body>
<?php include '../pages/header.php'; ?>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-2 sidebar">
            <?php include '../pages/menu_lateral.php'; ?>
        </div>
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
                    <h4 class="mb-0"><i class="bi bi-kanban me-2 text-primary"></i>Controle de Demandas</h4>
                    <small class="text-muted">Brasil DNA 2026</small>
                </div>
                <?php if ($podeGerenciar): ?>
                <a href="forms/nova_demanda.php" class="btn btn-primary">
                    <i class="bi bi-plus-lg me-1"></i> Nova Demanda
                </a>
                <?php endif; ?>
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
                        <div class="kpi-value text-success"><?= $kpis['Done'] ?></div>
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="kpi-card border-warning">
                        <div class="kpi-label">Em Andamento</div>
                        <div class="kpi-value text-warning"><?= $kpis['Em andamento'] ?></div>
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="kpi-card border-secondary">
                        <div class="kpi-label">Pendentes</div>
                        <div class="kpi-value text-secondary"><?= $kpis['Pendente'] ?></div>
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="kpi-card border-danger">
                        <div class="kpi-label">Atrasadas</div>
                        <div class="kpi-value text-danger"><?= $kpis['Atrasado'] ?></div>
                    </div>
                </div>
                <?php if ($kpis['total'] > 0): ?>
                <div class="col-6 col-md-2">
                    <div class="kpi-card border-info">
                        <div class="kpi-label">% Concluído</div>
                        <div class="kpi-value text-info"><?= round(($kpis['Done'] / $kpis['total']) * 100) ?>%</div>
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
                        <?php foreach ($categorias as $cat): ?>
                        <option value="<?= $cat ?>" <?= $filtroCategoria === $cat ? 'selected' : '' ?>><?= $cat ?></option>
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
                <?php if ($podeGerenciar): ?>
                <div class="col-md-3">
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

            <!-- Tabela -->
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Responsável</th>
                            <th>Categoria</th>
                            <th>Mês</th>
                            <th>Tarefa / Demanda</th>
                            <th>Deadline</th>
                            <th>Prioridade</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($demandas)): ?>
                        <tr><td colspan="9" class="text-center text-muted py-4"><i class="bi bi-inbox fs-3 d-block mb-2"></i>Nenhuma demanda encontrada.</td></tr>
                    <?php else: ?>
                    <?php foreach ($demandas as $d): ?>
                        <?php
                        $hoje       = date('Y-m-d');
                        $atrasado   = ($d['deadline'] && $d['deadline'] < $hoje && $d['status'] !== 'Done');
                        $prioClass  = ['Alta' => 'danger', 'Média' => 'warning', 'Baixa' => 'secondary'][$d['prioridade']] ?? 'secondary';
                        $statusClass = [
                            'Done'         => 'success', 'Em andamento' => 'primary',
                            'Produzindo'   => 'info',    'Enviado'      => 'info',
                            'Publicado'    => 'success', 'Aguardando'   => 'warning',
                            'Pendente'     => 'secondary','Atrasado'    => 'danger'
                        ][$d['status']] ?? 'secondary';

                        // Nível 3 só pode editar suas próprias demandas
                        $podeEditar = $podeGerenciar || ($nivel === 3 && $d['id_usuario'] == $userId);
                        ?>
                        <tr class="<?= $atrasado ? 'table-danger' : '' ?>">
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
                                <?php if ($podeEditar): ?>
                                <a href="forms/editar_demanda.php?id=<?= $d['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                                <?php endif; ?>
                                <?php if ($isAdmin): ?>
                                <a href="forms/deletar_demanda.php?id=<?= $d['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Confirma exclusão?')"><i class="bi bi-trash"></i></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</div>
<?php include '../pages/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.querySelectorAll('.status-select').forEach(sel => {
    sel.addEventListener('change', function() {
        const id     = this.dataset.id;
        const status = this.value;
        fetch('forms/update_status_demanda.php', {
            method: 'POST',
            headers: {'Content-Type':'application/x-www-form-urlencoded'},
            body: `id=${id}&status=${encodeURIComponent(status)}`
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const row = this.closest('tr');
                row.classList.toggle('table-danger', data.atrasado);
            }
        });
    });
});
</script>
</body>
</html>
