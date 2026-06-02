<?php
require_once '../../includes/auth.php';
require_login();
require_once '../../includes/db_connect.php';

$userId  = usuario_id();
$isAdmin = is_admin();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: ../index.php'); exit; }

// Busca a demanda para validar permissão
$stmtD = $conn->prepare("SELECT * FROM demandas WHERE id = ?");
$stmtD->bind_param('i', $id);
$stmtD->execute();
$res = $stmtD->get_result();
if (!$res || $res->num_rows === 0) { header('Location: ../index.php'); exit; }
$d = $res->fetch_assoc();
$stmtD->close();

// Verifica permissão usando a função centralizada do auth.php
if (!pode_editar_tarefa((int)$d['id_usuario'])) {
    $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Você não tem permissão para editar esta demanda.'];
    header('Location: ../index.php');
    exit;
}

// Usuários (somente admin pode trocar o responsável)
$usuarios = [];
if ($isAdmin) {
    $resU = $conn->query("SELECT id, nome FROM usuarios ORDER BY nome");
    while ($u = $resU->fetch_assoc()) $usuarios[] = $u;
}

// Empresas e Clientes
$empresas = [];
$resEmp = $conn->query("SELECT id, empresa FROM empresas ORDER BY empresa ASC");
while ($e = $resEmp->fetch_assoc()) $empresas[] = $e;

$clientes = [];
$resCli = $conn->query("SELECT id, company FROM clientes ORDER BY company ASC");
while ($c = $resCli->fetch_assoc()) $clientes[] = $c;

// IDs já vinculados (para pré-marcar chips)
$empSelecionados = [];
$resEmpSel = $conn->prepare("SELECT id_empresa FROM demandas_empresas WHERE id_demanda = ?");
$resEmpSel->bind_param('i', $id);
$resEmpSel->execute();
$rowsEmp = $resEmpSel->get_result();
while ($row = $rowsEmp->fetch_assoc()) $empSelecionados[] = (int)$row['id_empresa'];
$resEmpSel->close();

$cliSelecionados = [];
$resCliSel = $conn->prepare("SELECT id_cliente FROM demandas_clientes WHERE id_demanda = ?");
$resCliSel->bind_param('i', $id);
$resCliSel->execute();
$rowsCli = $resCliSel->get_result();
while ($row = $rowsCli->fetch_assoc()) $cliSelecionados[] = (int)$row['id_cliente'];
$resCliSel->close();

// Categorias com subcategorias (espelhadas no checklist Brasil DNA 2026)
$categorias = [
    'Gestão & Planejamento' => [
        'Cronograma',
        'Locais',
        'Metas (clientes, marcas, números de pessoas)',
        'Parcerias',
        'Controle e Follow Up',
    ],
    'Comunicação' => [
        'Videos Promo',
        'Webinars',
        'Releases Brasil',
        'Releases EUA',
        'Newsletter',
        'Posts SoMe',
        'Plataforma',
    ],
    'Documentação' => [
        'Contratos',
        'Invoice',
        'Acordos',
        'Clientes',
        'Parceiros',
        'Fornecedores',
    ],
    'Organização e Execução' => [
        'Roadshow Presencial',
        'Roadshow Virtual',
        'Eventos Especiais',
        'Agenda B2B',
        'Travel Arrangements',
        'Promoção e RSVP',
    ],
    'Relatórios' => [
        'Template de Relatórios',
        'Atualização de Dados',
        'Entrega de Relatórios',
    ],
];

$meses = ['Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];

// Subcategorias de Comunicação que exibem campos extras
$catConteudo = [
    'Comunicação › Videos Promo',
    'Comunicação › Webinars',
    'Comunicação › Releases Brasil',
    'Comunicação › Releases EUA',
    'Comunicação › Newsletter',
    'Comunicação › Posts SoMe',
    'Comunicação › Plataforma',
];
$mostrarConteudo = in_array($d['categoria'], $catConteudo);

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Editar Demanda #<?= $id ?> — GVA Insights</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/demandas.css">
    <style>
        .chip-select-group {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            padding: 6px 0;
        }
        .chip-select-group input[type="checkbox"] { display: none; }
        .chip-select-group label {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 14px;
            border: 1px solid #ccc;
            border-radius: 999px;
            font-size: 0.83rem;
            cursor: pointer;
            background: #fff;
            color: #555;
            transition: all 0.15s ease;
            user-select: none;
            line-height: 1.4;
        }
        .chip-select-group label::before {
            content: '';
            display: inline-block;
            width: 13px;
            height: 13px;
            border: 1.5px solid #aaa;
            border-radius: 3px;
            background: #fff;
            flex-shrink: 0;
            transition: all 0.15s ease;
        }
        .chip-select-group input[type="checkbox"]:checked + label {
            background: #e8f0fe;
            border-color: #0d6efd;
            color: #0d6efd;
            font-weight: 500;
        }
        .chip-select-group input[type="checkbox"]:checked + label::before {
            background: #0d6efd;
            border-color: #0d6efd;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 12 10' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M1 5l3.5 3.5L11 1' stroke='white' stroke-width='2' fill='none' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: center;
            background-size: 9px;
        }
        .chip-label-section {
            font-size: 0.78rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: #6c757d;
            margin-bottom: 2px;
        }
        #categoria optgroup { font-weight: 700; color: #343a40; }
        #categoria option   { font-weight: 400; color: #495057; }
    </style>
</head>
<body>
<?php include '../../pages/header.php'; ?>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-2 sidebar">
            <?php include '../../pages/menu_lateral.php'; ?>
        </div>
        <div class="col-md-10 main-content py-4">

            <?php if ($flash): ?>
            <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
                <?= $flash['msg'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <div class="d-flex align-items-center mb-4">
                <a href="../index.php" class="btn btn-outline-secondary btn-sm me-3"><i class="bi bi-arrow-left"></i></a>
                <div>
                    <h4 class="mb-0"><i class="bi bi-pencil-square me-2 text-warning"></i>Editar Demanda <span class="text-muted">#<?= $id ?></span></h4>
                    <small class="text-muted">Atualização de demanda / tarefa</small>
                </div>
            </div>

            <div class="form-card">
                <form method="POST" action="update_demanda.php">
                    <input type="hidden" name="id" value="<?= $id ?>">

                    <div class="section-title">Identificação</div>
                    <div class="row g-3">

                        <!-- Responsável: admin pode trocar, operacional vê só o nome -->
                        <?php if ($isAdmin): ?>
                        <div class="col-md-6">
                            <label class="form-label">Responsável</label>
                            <select name="id_usuario" class="form-select" required>
                                <?php foreach ($usuarios as $u): ?>
                                <option value="<?= $u['id'] ?>" <?= $d['id_usuario'] == $u['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($u['nome']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php else: ?>
                        <input type="hidden" name="id_usuario" value="<?= $d['id_usuario'] ?>">
                        <?php endif; ?>

                        <div class="col-md-6">
                            <label class="form-label">Categoria <span class="text-danger">*</span></label>
                            <select name="categoria" id="categoria" class="form-select" required>
                                <?php foreach ($categorias as $grupo => $subs):
                                    if (empty($subs)):
                                        $val = $grupo;
                                ?>
                                        <option value="<?= htmlspecialchars($val) ?>" <?= $d['categoria'] === $val ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($grupo) ?>
                                        </option>
                                <?php
                                    else:
                                ?>
                                        <optgroup label="<?= htmlspecialchars($grupo) ?>">
                                        <?php foreach ($subs as $sub):
                                            $val = $grupo . ' › ' . $sub;
                                        ?>
                                            <option value="<?= htmlspecialchars($val) ?>" <?= $d['categoria'] === $val ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($sub) ?>
                                            </option>
                                        <?php endforeach; ?>
                                        </optgroup>
                                <?php
                                    endif;
                                endforeach;
                                ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Mês</label>
                            <select name="mes" class="form-select">
                                <option value="">Selecione...</option>
                                <?php foreach ($meses as $m): ?>
                                <option value="<?= $m ?>" <?= $d['mes'] === $m ? 'selected' : '' ?>><?= $m ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Deadline</label>
                            <input type="date" name="deadline" class="form-control" value="<?= htmlspecialchars($d['deadline'] ?? '') ?>">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Prioridade</label>
                            <select name="prioridade" class="form-select">
                                <?php foreach (['Alta','Media','Baixa'] as $p): ?>
                                <option value="<?= $p ?>" <?= $d['prioridade'] === $p ? 'selected' : '' ?>>
                                    <?= $p === 'Media' ? 'Média' : $p ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Tarefa / Demanda <span class="text-danger">*</span></label>
                            <textarea name="tarefa" class="form-control" rows="3" required><?= htmlspecialchars($d['tarefa']) ?></textarea>
                        </div>
                    </div>

                    <!-- Campos Comunicação (dinâmico) -->
                    <div id="fields-conteudo" class="<?= $mostrarConteudo ? '' : 'd-none' ?>">
                        <div class="section-title">Detalhes — Comunicação</div>
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label">Tema / Conteúdo</label>
                                <input type="text" name="tipo_conteudo" class="form-control" value="<?= htmlspecialchars($d['tipo_conteudo'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Data de Publicação / Envio</label>
                                <input type="date" name="data_publicacao" class="form-control" value="<?= htmlspecialchars($d['data_publicacao'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Link Externo</label>
                                <input type="url" name="link_externo" class="form-control" value="<?= htmlspecialchars($d['link_externo'] ?? '') ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Detalhes</label>
                                <textarea name="detalhes" class="form-control" rows="3"><?= htmlspecialchars($d['detalhes'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Campos demais categorias -->
                    <div id="fields-gestao" class="<?= $mostrarConteudo ? 'd-none' : '' ?>">
                        <div class="section-title">Detalhes</div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Ação / Observação</label>
                                <input type="text" name="acao" class="form-control" value="<?= htmlspecialchars($d['acao'] ?? '') ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Detalhes / Observações</label>
                                <textarea name="detalhes" class="form-control" rows="3"><?= htmlspecialchars($d['detalhes'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- EMPRESAS & CLIENTES -->
                    <div class="section-title mt-3">Empresas &amp; Clientes Envolvidos</div>
                    <div class="row g-3">
                        <div class="col-md-12">
                            <div class="chip-label-section mb-1"><i class="bi bi-building me-1"></i>Empresas Envolvidas</div>
                            <div class="chip-select-group">
                                <?php foreach ($empresas as $emp): ?>
                                    <input type="checkbox" name="empresas_envolvidas[]" id="emp_<?= $emp['id'] ?>" value="<?= $emp['id'] ?>"
                                        <?= in_array((int)$emp['id'], $empSelecionados) ? 'checked' : '' ?>>
                                    <label for="emp_<?= $emp['id'] ?>"><?= htmlspecialchars($emp['empresa']) ?></label>
                                <?php endforeach; ?>
                                <?php if (empty($empresas)): ?>
                                    <span class="text-muted small">Nenhuma empresa cadastrada.</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="chip-label-section mb-1"><i class="bi bi-person-badge me-1"></i>Clientes Envolvidos</div>
                            <div class="chip-select-group">
                                <?php foreach ($clientes as $cli): ?>
                                    <input type="checkbox" name="clientes_envolvidos[]" id="cli_<?= $cli['id'] ?>" value="<?= $cli['id'] ?>"
                                        <?= in_array((int)$cli['id'], $cliSelecionados) ? 'checked' : '' ?>>
                                    <label for="cli_<?= $cli['id'] ?>"><?= htmlspecialchars($cli['company']) ?></label>
                                <?php endforeach; ?>
                                <?php if (empty($clientes)): ?>
                                    <span class="text-muted small">Nenhum cliente cadastrado.</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Status -->
                    <div class="section-title">Status</div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <?php foreach (['Done','Em andamento','Produzindo','Enviado','Publicado','Aguardando','Pendente','Atrasado'] as $s): ?>
                                <option value="<?= $s ?>" <?= $d['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="../index.php" class="btn btn-outline-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-warning"><i class="bi bi-check-lg me-1"></i>Atualizar Demanda</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php include '../../pages/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const CAT_CONTEUDO = [
    'Comunicação › Videos Promo',
    'Comunicação › Webinars',
    'Comunicação › Releases Brasil',
    'Comunicação › Releases EUA',
    'Comunicação › Newsletter',
    'Comunicação › Posts SoMe',
    'Comunicação › Plataforma'
];
document.getElementById('categoria').addEventListener('change', function () {
    const cat = this.value;
    const isConteudo = CAT_CONTEUDO.includes(cat);
    document.getElementById('fields-conteudo').classList.toggle('d-none', !isConteudo);
    document.getElementById('fields-gestao').classList.toggle('d-none',   isConteudo);
});
</script>
</body>
</html>
