<?php
require_once '../../includes/auth.php';
require_login();
require_once '../../includes/db_connect.php';

$userId  = usuario_id();
$isAdmin = is_admin();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: ../index.php'); exit; }

// Busca subtarefa + título da demanda pai
$stmt = $conn->prepare(
    "SELECT s.*, d.tarefa AS demanda_titulo
     FROM subtarefas s
     INNER JOIN demandas d ON d.id = s.id_demanda
     WHERE s.id = ?"
);
$stmt->bind_param('i', $id);
$stmt->execute();
$s = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$s) { header('Location: ../index.php'); exit; }

// Permissão: admin edita tudo; nível 2 só edita as próprias
if (!$isAdmin && (int)$s['id_usuario'] !== $userId) {
    $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Você não tem permissão para editar esta subtarefa.'];
    header('Location: ../index.php');
    exit;
}

// Admin pode trocar responsável
$usuarios = [];
if ($isAdmin) {
    $resU = $conn->query("SELECT id, nome FROM usuarios ORDER BY nome");
    while ($u = $resU->fetch_assoc()) $usuarios[] = $u;
}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Editar Subtarefa #<?= $id ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/demandas.css">
</head>
<body>
<?php include '../../pages/header.php'; ?>
<div class="container-fluid">
  <div class="row">
    <div class="col-md-2 sidebar"><?php include '../../pages/menu_lateral.php'; ?></div>
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
          <h4 class="mb-0"><i class="bi bi-pencil-square me-2 text-warning"></i>Editar Subtarefa <span class="text-muted">#<?= $id ?></span></h4>
          <small class="text-muted">Demanda pai: <strong><?= htmlspecialchars($s['demanda_titulo']) ?></strong></small>
        </div>
      </div>

      <div class="form-card">
        <form method="POST" action="update_subtarefa.php">
          <input type="hidden" name="id" value="<?= $id ?>">

          <div class="section-title">Dados da Subtarefa</div>
          <div class="row g-3">

            <!-- Responsável: somente admin pode trocar -->
            <?php if ($isAdmin): ?>
            <div class="col-md-6">
              <label class="form-label">Responsável</label>
              <select name="id_usuario" class="form-select" required>
                <?php foreach ($usuarios as $u): ?>
                <option value="<?= $u['id'] ?>" <?= $s['id_usuario'] == $u['id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($u['nome']) ?>
                </option>
                <?php endforeach; ?>
              </select>
            </div>
            <?php else: ?>
            <input type="hidden" name="id_usuario" value="<?= $s['id_usuario'] ?>">
            <?php endif; ?>

            <div class="col-md-3">
              <label class="form-label">Deadline</label>
              <input type="date" name="deadline" class="form-control" value="<?= htmlspecialchars($s['deadline'] ?? '') ?>">
            </div>

            <div class="col-md-3">
              <label class="form-label">Prioridade</label>
              <select name="prioridade" class="form-select">
                <?php foreach (['Alta','Media','Baixa'] as $p): ?>
                <option value="<?= $p ?>" <?= $s['prioridade'] === $p ? 'selected' : '' ?>>
                  <?= $p === 'Media' ? 'Média' : $p ?>
                </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-12">
              <label class="form-label">Título <span class="text-danger">*</span></label>
              <input type="text" name="titulo" class="form-control" value="<?= htmlspecialchars($s['titulo']) ?>" required>
            </div>

            <div class="col-12">
              <label class="form-label">Descrição / Observações</label>
              <textarea name="descricao" class="form-control" rows="4"><?= htmlspecialchars($s['descricao'] ?? '') ?></textarea>
            </div>

            <div class="col-md-4">
              <label class="form-label">Status</label>
              <select name="status" class="form-select">
                <?php foreach (['Pendente','Em andamento','Produzindo','Aguardando','Done','Atrasado'] as $st): ?>
                <option value="<?= $st ?>" <?= $s['status'] === $st ? 'selected' : '' ?>><?= $st ?></option>
                <?php endforeach; ?>
              </select>
            </div>

          </div>

          <div class="d-flex justify-content-end gap-2 mt-4">
            <a href="../index.php" class="btn btn-outline-secondary">Cancelar</a>
            <button type="submit" class="btn btn-warning"><i class="bi bi-check-lg me-1"></i>Atualizar Subtarefa</button>
          </div>
        </form>
      </div>

    </div>
  </div>
</div>
<?php include '../../pages/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
