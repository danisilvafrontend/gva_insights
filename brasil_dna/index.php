<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

include '../includes/config.php';
include '../includes/db_connect.php';
mysqli_set_charset($conn, 'utf8mb4');

// ---------- KPIs ----------
$kpi = [];
$res = $conn->query("SELECT status, COUNT(*) AS total FROM bdna_tarefas GROUP BY status");
while ($row = $res->fetch_assoc()) {
    $kpi[$row['status']] = (int)$row['total'];
}
$totalGeral = array_sum($kpi);
$totalDone  = ($kpi['Done'] ?? 0) + ($kpi['Enviado'] ?? 0) + ($kpi['Publicado'] ?? 0);
$totalProg  = ($kpi['Em andamento'] ?? 0) + ($kpi['Produzindo'] ?? 0);
$totalAtras = $conn->query("SELECT COUNT(*) AS c FROM bdna_tarefas WHERE deadline < CURDATE() AND status NOT IN ('Done','Enviado','Publicado')")->fetch_assoc()['c'];

// ---------- Filtros ----------
$filtCat    = intval($_GET['cat']    ?? 0);
$filtStatus = $_GET['status']        ?? '';
$filtResp   = intval($_GET['resp']   ?? 0);
$filtMes    = $_GET['mes']           ?? '';
$filtPri    = $_GET['pri']           ?? '';

$where = ['1=1'];
if ($filtCat)    $where[] = "t.id_categoria = " . $filtCat;
if ($filtStatus) $where[] = "t.status = '" . $conn->real_escape_string($filtStatus) . "'";
if ($filtResp)   $where[] = "t.id_usuario = " . $filtResp;
if ($filtMes)    $where[] = "t.mes_referencia = '" . $conn->real_escape_string($filtMes) . "'";
if ($filtPri)    $where[] = "t.prioridade = '" . $conn->real_escape_string($filtPri) . "'";

$whereSQL = implode(' AND ', $where);

$sql = "SELECT t.*, u.nome AS responsavel, c.nome AS cat_nome, c.cor_hex
        FROM bdna_tarefas t
        INNER JOIN usuarios u ON t.id_usuario = u.id
        INNER JOIN bdna_categorias c ON t.id_categoria = c.id
        WHERE $whereSQL
        ORDER BY
          CASE t.prioridade WHEN 'Alta' THEN 1 WHEN 'Media' THEN 2 ELSE 3 END,
          t.deadline ASC, t.id DESC";

$tarefas = $conn->query($sql);

// Listas para filtros
$categorias = $conn->query('SELECT id, nome FROM bdna_categorias WHERE ativo=1 ORDER BY ordem');
$responsaveis = $conn->query('SELECT id, nome FROM usuarios WHERE ativo=1 ORDER BY nome');
$meses = ['Janeiro','Fevereiro','Marco','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro'];

$conn->close();
?>
<?php include '../pages/header.php'; ?>
<link rel="stylesheet" href="../brasil_dna/assets/brasil_dna.css">

<body class="bdna-page">
<div class="bdna-wrapper">
  <?php include '../pages/menu_lateral.php'; ?>

  <main class="bdna-main">

    <!-- ===== Cabeçalho ===== -->
    <div class="bdna-page-header">
      <h1 class="bdna-page-title">
        <span class="bdna-flag">🇧🇷</span>
        Brasil DNA 2026 — Controle de Tarefas
      </h1>
      <a href="forms/registrar_tarefa.php" class="bdna-btn bdna-btn-primary">
        <i class="bi bi-plus-lg"></i> Nova Tarefa
      </a>
    </div>

    <!-- ===== KPIs ===== -->
    <div class="bdna-kpi-row">
      <div class="bdna-kpi-card kpi-total">
        <div class="bdna-kpi-label">Total</div>
        <div class="bdna-kpi-value"><?= $totalGeral ?></div>
      </div>
      <div class="bdna-kpi-card kpi-done">
        <div class="bdna-kpi-label">Concluídas</div>
        <div class="bdna-kpi-value"><?= $totalDone ?></div>
      </div>
      <div class="bdna-kpi-card kpi-prog">
        <div class="bdna-kpi-label">Em progresso</div>
        <div class="bdna-kpi-value"><?= $totalProg ?></div>
      </div>
      <div class="bdna-kpi-card kpi-late">
        <div class="bdna-kpi-label">Atrasadas</div>
        <div class="bdna-kpi-value"><?= $totalAtras ?></div>
      </div>
      <?php if ($totalGeral > 0): ?>
      <div class="bdna-kpi-card" style="border-left-color:#8b5cf6">
        <div class="bdna-kpi-label">% Concluído</div>
        <div class="bdna-kpi-value" style="color:#8b5cf6">
          <?= round($totalDone / $totalGeral * 100) ?>%
        </div>
      </div>
      <?php endif; ?>
    </div>

    <!-- ===== Filtros ===== -->
    <div class="bdna-filters">
      <div class="filter-group">
        <label>Categoria</label>
        <select name="cat" onchange="applyFilters(this)" data-param="cat">
          <option value="">Todas</option>
          <?php
          mysqli_data_seek($categorias, 0);
          while ($c = $categorias->fetch_assoc()):
          ?>
          <option value="<?= $c['id'] ?>" <?= $filtCat == $c['id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($c['nome']) ?>
          </option>
          <?php endwhile; ?>
        </select>
      </div>

      <div class="filter-group">
        <label>Status</label>
        <select name="status" onchange="applyFilters(this)" data-param="status">
          <option value="">Todos</option>
          <?php foreach (['Pendente','Em andamento','Produzindo','Aguardando','Enviado','Publicado','Done'] as $s): ?>
          <option value="<?= $s ?>" <?= $filtStatus == $s ? 'selected' : '' ?>><?= $s ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="filter-group">
        <label>Responsável</label>
        <select name="resp" onchange="applyFilters(this)" data-param="resp">
          <option value="">Todos</option>
          <?php
          mysqli_data_seek($responsaveis, 0);
          while ($u = $responsaveis->fetch_assoc()):
          ?>
          <option value="<?= $u['id'] ?>" <?= $filtResp == $u['id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($u['nome']) ?>
          </option>
          <?php endwhile; ?>
        </select>
      </div>

      <div class="filter-group">
        <label>Mês</label>
        <select name="mes" onchange="applyFilters(this)" data-param="mes">
          <option value="">Todos</option>
          <?php foreach ($meses as $m): ?>
          <option value="<?= $m ?>" <?= $filtMes == $m ? 'selected' : '' ?>><?= $m ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="filter-group">
        <label>Prioridade</label>
        <select name="pri" onchange="applyFilters(this)" data-param="pri">
          <option value="">Todas</option>
          <option value="Alta"  <?= $filtPri=='Alta'  ? 'selected':'' ?>>Alta</option>
          <option value="Media" <?= $filtPri=='Media' ? 'selected':'' ?>>Média</option>
          <option value="Baixa" <?= $filtPri=='Baixa' ? 'selected':'' ?>>Baixa</option>
        </select>
      </div>

      <div class="filter-group" style="justify-content:flex-end">
        <a href="index.php" class="bdna-btn bdna-btn-outline" style="height:34px">
          <i class="bi bi-x-circle"></i> Limpar
        </a>
      </div>
    </div>

    <!-- ===== Tabela ===== -->
    <div class="bdna-table-wrap">
      <?php if ($tarefas && $tarefas->num_rows > 0): ?>
      <table class="bdna-table">
        <thead>
          <tr>
            <th>Tarefa</th>
            <th>Categoria</th>
            <th>Responsável</th>
            <th>Mês</th>
            <th>Deadline</th>
            <th>Prioridade</th>
            <th>Status</th>
            <th>Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($t = $tarefas->fetch_assoc()):
            $hoje = date('Y-m-d');
            $atrasado = $t['deadline'] && $t['deadline'] < $hoje && !in_array($t['status'], ['Done','Enviado','Publicado']);
            $dHoje = $t['deadline'] && $t['deadline'] == $hoje;
            $statusClass = 'status-' . strtolower(str_replace(' ', '_', $t['status']));
            $priClass = 'pri-' . strtolower($t['prioridade']);
            $iniciais = mb_strtoupper(mb_substr($t['responsavel'], 0, 1) . (strpos($t['responsavel'], ' ') !== false ? mb_substr(strrchr($t['responsavel'], ' '), 1, 1) : ''));
          ?>
          <tr>
            <td class="bdna-task-name">
              <?= htmlspecialchars($t['tarefa']) ?>
              <?php if ($t['acao']): ?>
                <small><?= htmlspecialchars($t['acao']) ?></small>
              <?php elseif ($t['tema_conteudo']): ?>
                <small><?= htmlspecialchars($t['tema_conteudo']) ?></small>
              <?php endif; ?>
            </td>
            <td>
              <span class="bdna-cat-dot" style="color:<?= htmlspecialchars($t['cor_hex']) ?>">
                <?= htmlspecialchars($t['cat_nome']) ?>
              </span>
            </td>
            <td>
              <div class="bdna-user-cell">
                <span class="bdna-avatar"><?= $iniciais ?></span>
                <?= htmlspecialchars(explode(' ', $t['responsavel'])[0]) ?>
              </div>
            </td>
            <td><?= htmlspecialchars($t['mes_referencia'] ?? '—') ?></td>
            <td>
              <?php if ($t['deadline']): ?>
                <span class="bdna-deadline <?= $atrasado ? 'atrasado' : ($dHoje ? 'hoje' : '') ?>">
                  <?= $atrasado ? '<i class="bi bi-exclamation-circle"></i> ' : '' ?>
                  <?= date('d/m/Y', strtotime($t['deadline'])) ?>
                </span>
              <?php else: ?>
                <span style="color:var(--bdna-gray)">—</span>
              <?php endif; ?>
            </td>
            <td><span class="bdna-pri <?= $priClass ?>"><?= $t['prioridade'] ?></span></td>
            <td>
              <select class="bdna-badge <?= $statusClass ?> bdna-status-select"
                      onchange="updateStatus(<?= $t['id'] ?>, this)"
                      title="Alterar status">
                <?php foreach (['Pendente','Em andamento','Produzindo','Aguardando','Enviado','Publicado','Done'] as $s): ?>
                  <option value="<?= $s ?>" <?= $t['status'] == $s ? 'selected' : '' ?>><?= $s ?></option>
                <?php endforeach; ?>
              </select>
            </td>
            <td>
              <div class="bdna-actions">
                <a href="forms/edit_tarefa.php?id=<?= $t['id'] ?>" class="bdna-btn-icon" title="Editar">
                  <i class="bi bi-pencil"></i>
                </a>
                <a href="forms/deletar_tarefa.php?id=<?= $t['id'] ?>" class="bdna-btn-icon del"
                   title="Excluir" onclick="return confirm('Excluir esta tarefa?')">
                  <i class="bi bi-trash"></i>
                </a>
              </div>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
      <?php else: ?>
      <div class="bdna-empty">
        <i class="bi bi-check2-square"></i>
        <p>Nenhuma tarefa encontrada.<br>Clique em <strong>Nova Tarefa</strong> para começar.</p>
      </div>
      <?php endif; ?>
    </div>

  </main>
</div>

<!-- Toast -->
<div class="bdna-toast" id="bdnaToast"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Aplicar filtros mantendo os outros params
function applyFilters(el) {
  const params = new URLSearchParams(window.location.search);
  const val = el.options[el.selectedIndex].value;
  const param = el.getAttribute('data-param');
  if (val) params.set(param, val); else params.delete(param);
  window.location.href = 'index.php?' + params.toString();
}

// Atualizar status via AJAX
function updateStatus(id, el) {
  const status = el.value;
  fetch('../brasil_dna/forms/update_status.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'id=' + id + '&status=' + encodeURIComponent(status)
  })
  .then(r => r.json())
  .then(data => {
    showToast(data.ok ? '✓ Status atualizado' : '✗ Erro ao atualizar', data.ok ? 'success' : 'error');
    if (data.ok) {
      // Atualizar classe do badge
      const cls = 'status-' + status.toLowerCase().replace(/ /g, '_');
      el.className = 'bdna-badge bdna-status-select ' + cls;
    }
  });
}

function showToast(msg, type = '') {
  const t = document.getElementById('bdnaToast');
  t.textContent = msg;
  t.className = 'bdna-toast show ' + type;
  setTimeout(() => t.className = 'bdna-toast', 2800);
}

// Feedback de URL
const urlP = new URLSearchParams(window.location.search);
if (urlP.get('ok') === '1')  showToast('✓ Tarefa salva com sucesso!', 'success');
if (urlP.get('ok') === 'del') showToast('✓ Tarefa excluída.', 'success');
if (urlP.get('ok') === 'edit') showToast('✓ Tarefa atualizada!', 'success');
</script>

<?php include '../pages/footer.php'; ?>
</body>
</html>
