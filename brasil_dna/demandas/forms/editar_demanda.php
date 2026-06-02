<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../../index.php');
    exit;
}

include '../../../includes/config.php';
include '../../../includes/db_connect.php';
mysqli_set_charset($conn, 'utf8mb4');

$userId  = (int)$_SESSION['user_id'];
$isAdmin = isset($_SESSION['perfil']) && $_SESSION['perfil'] === 'admin';
$id      = (int)($_GET['id'] ?? 0);

if (!$id) { header('Location: ../index.php'); exit; }

// Buscar tarefa — não-admin só pode editar as próprias
$whereOwn = $isAdmin ? '' : "AND id_usuario = $userId";
$t = $conn->query("SELECT * FROM bdna_tarefas WHERE id = $id $whereOwn")->fetch_assoc();
if (!$t) { header('Location: ../index.php?erro=nao_encontrado'); exit; }

// Parceiros já vinculados
$parcsVinc = [];
$rp = $conn->query("SELECT id_parceiro FROM bdna_tarefas_parceiros WHERE id_tarefa = $id");
while ($pp = $rp->fetch_assoc()) { $parcsVinc[] = (int)$pp['id_parceiro']; }

$categorias   = $conn->query('SELECT id, nome, cor_hex FROM bdna_categorias WHERE ativo=1 ORDER BY ordem');
$parceiros    = $conn->query('SELECT id, nome FROM bdna_parceiros WHERE ativo=1 ORDER BY nome');
$responsaveis = $isAdmin ? $conn->query('SELECT id, nome FROM usuarios WHERE ativo=1 ORDER BY nome') : null;
$meses        = ['Janeiro','Fevereiro','Marco','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro'];
$statusList   = ['Pendente','Em andamento','Produzindo','Aguardando','Enviado','Publicado','Done'];

$conn->close();

function sel($a, $b) { return $a == $b ? 'selected' : ''; }
function chk($arr, $v) { return in_array($v, $arr) ? 'checked' : ''; }
?>
<?php include '../../../pages/header.php'; ?>
<link rel="stylesheet" href="../../assets/brasil_dna.css">
<link rel="stylesheet" href="../assets/demandas.css">

<body class="bdna-page">
<div class="bdna-wrapper">
  <?php include '../../../pages/menu_lateral.php'; ?>

  <main class="bdna-main">

    <div class="bdna-page-header">
      <h1 class="bdna-page-title">
        <span>✏️</span> Editar Demanda
        <small style="font-size:14px;font-weight:400;color:var(--bdna-text-muted)">#<?= $id ?></small>
      </h1>
      <a href="../index.php" class="bdna-btn bdna-btn-outline">
        <i class="bi bi-arrow-left"></i> Voltar
      </a>
    </div>

    <form method="POST" action="update_demanda.php">
      <input type="hidden" name="id" value="<?= $id ?>">

      <div class="bdna-form-card">
        <h5><i class="bi bi-pencil-square" style="color:var(--bdna-primary)"></i> Editar Demanda</h5>

        <?php if ($isAdmin && $responsaveis): ?>
        <div class="mb-3">
          <label class="bdna-label">Responsável</label>
          <select name="id_usuario" class="bdna-select">
            <?php while ($u = $responsaveis->fetch_assoc()): ?>
            <option value="<?= $u['id'] ?>" <?= sel($t['id_usuario'], $u['id']) ?>><?= htmlspecialchars($u['nome']) ?></option>
            <?php endwhile; ?>
          </select>
        </div>
        <?php endif; ?>

        <div class="mb-3">
          <label class="bdna-label">Categoria <span class="req">*</span></label>
          <select name="id_categoria" id="sltCategoria" class="bdna-select" required onchange="handleCategoria(this)">
            <?php mysqli_data_seek($categorias, 0); while ($c = $categorias->fetch_assoc()): ?>
            <option value="<?= $c['id'] ?>"
                    data-cor="<?= htmlspecialchars($c['cor_hex']) ?>"
                    data-nome="<?= htmlspecialchars($c['nome']) ?>"
                    <?= sel($t['id_categoria'], $c['id']) ?>>
              <?= htmlspecialchars($c['nome']) ?>
            </option>
            <?php endwhile; ?>
          </select>
        </div>

        <div class="mb-3">
          <label class="bdna-label">Descrição da Demanda <span class="req">*</span></label>
          <textarea name="tarefa" class="bdna-textarea" rows="3" required maxlength="500"><?= htmlspecialchars($t['tarefa']) ?></textarea>
        </div>

        <div class="bdna-form-row">
          <div>
            <label class="bdna-label">Mês de Referência</label>
            <select name="mes_referencia" class="bdna-select">
              <option value="">Selecionar...</option>
              <?php foreach ($meses as $m): ?>
              <option value="<?= $m ?>" <?= sel($t['mes_referencia'], $m) ?>><?= $m ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="bdna-label">Deadline</label>
            <input type="date" name="deadline" class="bdna-input" value="<?= htmlspecialchars($t['deadline'] ?? '') ?>">
          </div>
        </div>

        <div class="bdna-form-row mt-3">
          <div>
            <label class="bdna-label">Prioridade</label>
            <select name="prioridade" class="bdna-select">
              <?php foreach (['Alta','Media','Baixa'] as $p): ?>
              <option value="<?= $p ?>" <?= sel($t['prioridade'], $p) ?>><?= $p === 'Media' ? 'Média' : $p ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="bdna-label">Status</label>
            <select name="status" class="bdna-select">
              <?php foreach ($statusList as $s): ?>
              <option value="<?= $s ?>" <?= sel($t['status'], $s) ?>><?= $s ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <!-- Seção Gestão / Roadshow -->
        <div id="secGestao" class="demanda-section-condicional bdna-form-section">
          <div class="bdna-form-section-title"><i class="bi bi-diagram-3"></i> Detalhes — Gestão / Roadshow</div>
          <div class="bdna-form-row">
            <div>
              <label class="bdna-label">Ação</label>
              <input type="text" name="acao" class="bdna-input" value="<?= htmlspecialchars($t['acao'] ?? '') ?>" placeholder="Ex: Parcerias, Contratos...">
            </div>
            <div>
              <label class="bdna-label">Observações</label>
              <input type="text" name="detalhes" class="bdna-input" value="<?= htmlspecialchars($t['detalhes'] ?? '') ?>" placeholder="Observações...">
            </div>
          </div>
        </div>

        <!-- Seção Conteúdo -->
        <div id="secConteudo" class="demanda-section-condicional bdna-form-section">
          <div class="bdna-form-section-title"><i class="bi bi-camera-video"></i> Detalhes — Conteúdo / Publicação</div>
          <div class="bdna-form-row">
            <div>
              <label class="bdna-label">Tema / Conteúdo</label>
              <input type="text" name="tema_conteudo" class="bdna-input" value="<?= htmlspecialchars($t['tema_conteudo'] ?? '') ?>">
            </div>
            <div>
              <label class="bdna-label">Data de Publicação / Envio</label>
              <input type="date" name="data_publicacao" class="bdna-input" value="<?= htmlspecialchars($t['data_publicacao'] ?? '') ?>">
            </div>
          </div>
          <div class="mt-3">
            <label class="bdna-label">Link Externo</label>
            <input type="url" name="link_externo" class="bdna-input" value="<?= htmlspecialchars($t['link_externo'] ?? '') ?>" placeholder="https://...">
          </div>
          <div class="mt-3">
            <label class="bdna-label">Detalhes / Observações</label>
            <textarea name="detalhes" class="bdna-textarea" rows="2"><?= htmlspecialchars($t['detalhes'] ?? '') ?></textarea>
          </div>
        </div>

        <!-- Parceiros -->
        <?php if ($parceiros && $parceiros->num_rows > 0): ?>
        <div class="bdna-form-section">
          <div class="bdna-form-section-title"><i class="bi bi-people"></i> Parceiros Envolvidos</div>
          <div class="demanda-parceiros-wrap bdna-checkboxes">
            <?php while ($p = $parceiros->fetch_assoc()): ?>
            <label>
              <input type="checkbox" name="parceiros[]" value="<?= $p['id'] ?>" <?= chk($parcsVinc, (int)$p['id']) ?>>
              <span><?= htmlspecialchars($p['nome']) ?></span>
            </label>
            <?php endwhile; ?>
          </div>
        </div>
        <?php endif; ?>

        <div style="display:flex;gap:12px;margin-top:28px;padding-top:20px;border-top:1px solid var(--bdna-border);justify-content:flex-end">
          <a href="../index.php" class="bdna-btn bdna-btn-outline">Cancelar</a>
          <button type="submit" class="bdna-btn bdna-btn-primary">
            <i class="bi bi-check-lg"></i> Salvar Alterações
          </button>
        </div>

      </div>
    </form>

  </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const CAT_GESTAO   = [1, 6, 7];
const CAT_CONTEUDO = [2, 3, 4, 5];

function handleCategoria(sel) {
  const id = parseInt(sel.value);
  document.getElementById('secGestao').classList.toggle('ativo',   CAT_GESTAO.includes(id));
  document.getElementById('secConteudo').classList.toggle('ativo', CAT_CONTEUDO.includes(id));
}

// Disparar ao carregar para mostrar seção correta
document.addEventListener('DOMContentLoaded', () => {
  handleCategoria(document.getElementById('sltCategoria'));
});
</script>

<?php include '../../../pages/footer.php'; ?>
</body>
</html>
