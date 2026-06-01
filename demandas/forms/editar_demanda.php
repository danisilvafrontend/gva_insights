<?php
require_once '../../includes/auth.php';
require_login();
require_once '../../includes/db_connect.php';

$userId = usuario_id();
$nivel  = usuario_nivel();
$isAdmin = is_admin();
$podeGerenciar = can_manage_registros();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: ../index.php'); exit; }

// Nível 3 só pode editar suas próprias demandas
if (!$podeGerenciar) {
    $stmtCheck = $conn->prepare("SELECT id FROM demandas WHERE id = ? AND id_usuario = ?");
    $stmtCheck->bind_param('ii', $id, $userId);
    $stmtCheck->execute();
    $stmtCheck->store_result();
    if ($stmtCheck->num_rows === 0) {
        header('Location: ../index.php');
        exit;
    }
    $stmtCheck->close();
}

$stmtD = $conn->prepare("SELECT * FROM demandas WHERE id = ?");
$stmtD->bind_param('i', $id);
$stmtD->execute();
$res = $stmtD->get_result();
if (!$res || $res->num_rows === 0) { header('Location: ../index.php'); exit; }
$d = $res->fetch_assoc();
$stmtD->close();

$usuarios = [];
if ($isAdmin) {
    $resU = $conn->query("SELECT id, nome FROM usuarios ORDER BY nome");
    while ($u = $resU->fetch_assoc()) $usuarios[] = $u;
}

$categorias = [
    'Gestão & Planejamento','Videos Promo','Webinars',
    'News & Releases','Posts SoMe','Roadshow Presencial',
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
    <title>Editar Demanda #<?= $id ?> — GVA Insights</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/demandas.css">
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
            <div class="alert alert-<?= $flash['type'] ?> alert-flash alert-dismissible fade show">
                <?= $flash['msg'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <div class="d-flex align-items-center mb-4">
                <a href="../index.php" class="btn btn-outline-secondary btn-sm me-3"><i class="bi bi-arrow-left"></i></a>
                <div>
                    <h4 class="mb-0"><i class="bi bi-pencil-square me-2 text-warning"></i>Editar Demanda <span class="text-muted">#<?= $id ?></span></h4>
                    <small class="text-muted">Brasil DNA 2026</small>
                </div>
            </div>

            <div class="form-card">
                <form method="POST" action="update_demanda.php">
                    <input type="hidden" name="id" value="<?= $id ?>">

                    <div class="section-title">Identificação</div>
                    <div class="row g-3">

                        <?php if ($isAdmin): ?>
                        <div class="col-md-6">
                            <label class="form-label">Responsável</label>
                            <select name="id_usuario" class="form-select" required>
                                <?php foreach ($usuarios as $u): ?>
                                <option value="<?= $u['id'] ?>" <?= $d['id_usuario'] == $u['id'] ? 'selected' : '' ?>><?= htmlspecialchars($u['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php else: ?>
                        <input type="hidden" name="id_usuario" value="<?= $userId ?>">
                        <?php endif; ?>

                        <div class="col-md-6">
                            <label class="form-label">Categoria <span class="text-danger">*</span></label>
                            <select name="categoria" id="categoria" class="form-select" required>
                                <?php foreach ($categorias as $cat): ?>
                                <option value="<?= $cat ?>" <?= $d['categoria'] === $cat ? 'selected' : '' ?>><?= $cat ?></option>
                                <?php endforeach; ?>
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
                                <?php foreach (['Alta','Média','Baixa'] as $p): ?>
                                <option value="<?= $p ?>" <?= $d['prioridade'] === $p ? 'selected' : '' ?>><?= $p ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Tarefa / Demanda <span class="text-danger">*</span></label>
                            <textarea name="tarefa" class="form-control" rows="3" required><?= htmlspecialchars($d['tarefa']) ?></textarea>
                        </div>
                    </div>

                    <!-- Campos Gestão -->
                    <div id="fields-gestao" class="fields-gestao <?= in_array($d['categoria'], ['Gestão & Planejamento','Roadshow Presencial','Roadshow Virtual / Eventos Especiais']) ? '' : 'd-none' ?>">
                        <div class="section-title">Detalhes — Gestão / Roadshow</div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Ação</label>
                                <input type="text" name="acao" class="form-control" value="<?= htmlspecialchars($d['acao'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Parceiros Envolvidos</label>
                                <input type="text" name="parceiros" class="form-control" value="<?= htmlspecialchars($d['parceiros'] ?? '') ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Observações Importantes</label>
                                <textarea name="detalhes" class="form-control" rows="3"><?= htmlspecialchars($d['detalhes'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Campos Conteúdo -->
                    <div id="fields-conteudo" class="fields-conteudo <?= in_array($d['categoria'], ['Videos Promo','Webinars','News & Releases','Posts SoMe']) ? '' : 'd-none' ?>">
                        <div class="section-title">Detalhes — Conteúdo / Comunicação</div>
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
                                <label class="form-label">Parceiros Envolvidos</label>
                                <input type="text" name="parceiros" class="form-control" value="<?= htmlspecialchars($d['parceiros'] ?? '') ?>">
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
const CAT_GESTAO   = ['Gestão & Planejamento','Roadshow Presencial','Roadshow Virtual / Eventos Especiais'];
const CAT_CONTEUDO = ['Videos Promo','Webinars','News & Releases','Posts SoMe'];
document.getElementById('categoria').addEventListener('change', function() {
    const cat = this.value;
    document.getElementById('fields-gestao').classList.toggle('d-none',   !CAT_GESTAO.includes(cat));
    document.getElementById('fields-conteudo').classList.toggle('d-none', !CAT_CONTEUDO.includes(cat));
});
</script>
</body>
</html>
