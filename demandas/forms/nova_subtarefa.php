<?php
require_once '../../includes/auth.php';
require_login();

// Somente admin pode criar subtarefas
if (!is_admin()) {
    $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Apenas administradores podem criar subtarefas.'];
    header('Location: ../index.php');
    exit;
}

require_once '../../includes/db_connect.php';

$idDemanda = (int)($_GET['id_demanda'] ?? 0);
if (!$idDemanda) { header('Location: ../index.php'); exit; }

// Busca demanda pai
$stmtD = $conn->prepare("SELECT id, tarefa FROM demandas WHERE id = ?");
$stmtD->bind_param('i', $idDemanda);
$stmtD->execute();
$demanda = $stmtD->get_result()->fetch_assoc();
$stmtD->close();
if (!$demanda) { header('Location: ../index.php'); exit; }

// Lista usuários para selecionar responsável
$usuarios = [];
$resU = $conn->query("SELECT id, nome FROM usuarios ORDER BY nome");
while ($u = $resU->fetch_assoc()) $usuarios[] = $u;

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nova Subtarefa</title>
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
          <h4 class="mb-0"><i class="bi bi-diagram-3 me-2 text-primary"></i>Nova Subtarefa</h4>
          <small class="text-muted">Demanda pai: <strong><?= htmlspecialchars($demanda['tarefa']) ?></strong></small>
        </div>
      </div>

      <div class="form-card">
        <form method="POST" action="processar_subtarefa.php">
          <input type="hidden" name="id_demanda" value="<?= $idDemanda ?>">

          <div class="section-title">Dados da Subtarefa</div>
          <div class="row g-3">

            <div class="col-md-6">
              <label class="form-label">Responsável <span class="text-danger">*</span></label>
              <select name="id_usuario" class="form-select" required>
                <option value="">Selecione...</option>
                <?php foreach ($usuarios as $u): ?>
                <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['nome']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-md-3">
              <label class="form-label">Deadline</label>
              <input type="date" name="deadline" class="form-control">
            </div>

            <div class="col-md-3">
              <label class="form-label">Prioridade</label>
              <select name="prioridade" class="form-select">
                <option value="Alta">Alta</option>
                <option value="Media" selected>Média</option>
                <option value="Baixa">Baixa</option>
              </select>
            </div>

            <div class="col-12">
              <label class="form-label">Título da Subtarefa <span class="text-danger">*</span></label>
              <input type="text" name="titulo" class="form-control" placeholder="Ex: Criar layout página Treinamentos" required>
            </div>

            <div class="col-12">
              <label class="form-label">Descrição / Observações</label>
              <textarea name="descricao" class="form-control" rows="4" placeholder="Detalhe o que precisa ser feito..."></textarea>
            </div>

          </div>

          <div class="d-flex justify-content-end gap-2 mt-4">
            <a href="../index.php" class="btn btn-outline-secondary">Cancelar</a>
            <button type="submit" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Criar Subtarefa</button>
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
