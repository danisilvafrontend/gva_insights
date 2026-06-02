<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_id'])) { header('Location: ../../index.php'); exit; }

include '../../includes/config.php';
include '../../includes/db_connect.php';
mysqli_set_charset($conn, 'utf8mb4');

$id = intval($_GET['id'] ?? 0);
if (!$id) { header('Location: ../index.php'); exit; }

$tarefa = $conn->query("SELECT * FROM bdna_tarefas WHERE id = $id")->fetch_assoc();
if (!$tarefa) { header('Location: ../index.php'); exit; }

// Parceiros já vinculados
$parceirosVinculados = [];
$rp = $conn->query("SELECT id_parceiro FROM bdna_tarefas_parceiros WHERE id_tarefa = $id");
while ($rr = $rp->fetch_assoc()) $parceirosVinculados[] = $rr['id_parceiro'];

$categorias   = $conn->query('SELECT id, nome, cor_hex FROM bdna_categorias WHERE ativo=1 ORDER BY ordem');
$parceiros    = $conn->query('SELECT id, nome FROM bdna_parceiros WHERE ativo=1 ORDER BY nome');
$responsaveis = $conn->query('SELECT id, nome FROM usuarios WHERE ativo=1 ORDER BY nome');
$meses = ['Janeiro','Fevereiro','Marco','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro'];
$conn->close();
?>
<?php include '../../pages/header.php'; ?>
<link rel="stylesheet" href="../../brasil_dna/assets/brasil_dna.css">

<body class="bdna-page">
<div class="bdna-wrapper">
  <?php include '../../pages/menu_lateral.php'; ?>

  <main class="bdna-main">
    <div class="bdna-page-header">
      <h1 class="bdna-page-title">
        <span class="bdna-flag">🇧🇷</span> Editar Tarefa #<?= $id ?>
      </h1>
      <a href="../index.php" class="bdna-btn bdna-btn-outline">
        <i class="bi bi-arrow-left"></i> Voltar
      </a>
    </div>

    <div class="bdna-form-card">
      <h5><i class="bi bi-pencil-square" style="color:var(--bdna-primary)"></i> Editar Tarefa / Demanda</h5>

      <form action="update_tarefa.php" method="POST">
        <input type="hidden" name="id" value="<?= $id ?>">

        <div class="bdna-form-section-title">Informações Principais</div>

        <div class="bdna-form-row">
          <div>
            <label class="bdna-label">Categoria <span class="req">*</span></label>
            <select class="bdna-select" id="id_categoria" name="id_categoria" required onchange="toggleCampos()">
              <option value="">Selecione...</option>
              <?php while ($c = $categorias->fetch_assoc()): ?>
              <option value="<?= $c['id'] ?>" <?= $tarefa['id_categoria'] == $c['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($c['nome']) ?>
              </option>
              <?php endwhile; ?>
            </select>
          </div>
          <div>
            <label class="bdna-label">Responsável <span class="req">*</span></label>
            <select class="bdna-select" name="id_usuario" required>
              <?php while ($u = $responsaveis->fetch_assoc()): ?>
              <option value="<?= $u['id'] ?>" <?= $tarefa['id_usuario'] == $u['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($u['nome']) ?>
              </option>
              <?php endwhile; ?>
            </select>
          </div>
        </div>

        <div style="margin-top:14px">
          <label class="bdna-label">Tarefa / Demanda <span class="req">*</span></label>
          <textarea class="bdna-textarea" name="tarefa" rows="3" required><?= htmlspecialchars($tarefa['tarefa']) ?></textarea>
        </div>

        <div class="bdna-form-row" style="margin-top:14px">
          <div>
            <label class="bdna-label">Mês de Referência</label>
            <select class="bdna-select" name="mes_referencia">
              <option value="">Selecione...</option>
              <?php foreach ($meses as $m): ?>
              <option value="<?= $m ?>" <?= $tarefa['mes_referencia'] == $m ? 'selected' : '' ?>><?= $m ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="bdna-label">Deadline</label>
            <input type="date" class="bdna-input" name="deadline"
                   value="<?= htmlspecialchars($tarefa['deadline'] ?? '') ?>">
          </div>
        </div>

        <div class="bdna-form-row" style="margin-top:14px">
          <div>
            <label class="bdna-label">Prioridade <span class="req">*</span></label>
            <select class="bdna-select" name="prioridade" required>
              <?php foreach (['Alta','Media','Baixa'] as $pri): ?>
              <option value="<?= $pri ?>" <?= $tarefa['prioridade'] == $pri ? 'selected' : '' ?>>
                <?= $pri == 'Media' ? 'Média' : $pri ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="bdna-label">Status</label>
            <select class="bdna-select" name="status">
              <?php foreach (['Pendente','Em andamento','Produzindo','Aguardando','Enviado','Publicado','Done'] as $s): ?>
              <option value="<?= $s ?>" <?= $tarefa['status'] == $s ? 'selected' : '' ?>><?= $s ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <!-- Bloco Gestão -->
        <div class="bdna-form-section" id="bloco_gestao">
          <div class="bdna-form-section-title">Gestão &amp; Roadshow</div>
          <div class="bdna-form-row">
            <div>
              <label class="bdna-label">Ação</label>
              <input type="text" class="bdna-input" name="acao"
                     value="<?= htmlspecialchars($tarefa['acao'] ?? '') ?>">
            </div>
            <div>
              <label class="bdna-label">Observações</label>
              <input type="text" class="bdna-input" name="observacoes"
                     value="<?= htmlspecialchars($tarefa['observacoes'] ?? '') ?>">
            </div>
          </div>
          <div style="margin-top:14px">
            <label class="bdna-label">Notes</label>
            <textarea class="bdna-textarea" name="notes" rows="2"><?= htmlspecialchars($tarefa['notes'] ?? '') ?></textarea>
          </div>
        </div>

        <!-- Bloco Conteúdo -->
        <div class="bdna-form-section" id="bloco_conteudo">
          <div class="bdna-form-section-title">Detalhes do Conteúdo</div>
          <div class="bdna-form-row">
            <div>
              <label class="bdna-label">Tema / Conteúdo</label>
              <input type="text" class="bdna-input" name="tema_conteudo"
                     value="<?= htmlspecialchars($tarefa['tema_conteudo'] ?? '') ?>">
            </div>
            <div>
              <label class="bdna-label" id="label_data_acao">Data de Lançamento / Envio</label>
              <input type="date" class="bdna-input" name="data_acao"
                     value="<?= htmlspecialchars($tarefa['data_acao'] ?? '') ?>">
            </div>
          </div>
          <div style="margin-top:14px">
            <label class="bdna-label">Detalhes da Promoção</label>
            <textarea class="bdna-textarea" name="detalhes_promocao" rows="2"><?= htmlspecialchars($tarefa['detalhes_promocao'] ?? '') ?></textarea>
          </div>
          <div style="margin-top:14px">
            <label class="bdna-label">Link Externo</label>
            <input type="url" class="bdna-input" name="link_externo"
                   value="<?= htmlspecialchars($tarefa['link_externo'] ?? '') ?>">
          </div>
          <div style="margin-top:14px">
            <label class="bdna-label">Notes</label>
            <textarea class="bdna-textarea" name="notes" rows="2"><?= htmlspecialchars($tarefa['notes'] ?? '') ?></textarea>
          </div>
        </div>

        <!-- Parceiros -->
        <div class="bdna-form-section">
          <div class="bdna-form-section-title">Parceiros Envolvidos</div>
          <div class="bdna-checkboxes">
            <?php while ($p = $parceiros->fetch_assoc()): ?>
            <label>
              <input type="checkbox" name="parceiros[]" value="<?= $p['id'] ?>"
                     <?= in_array($p['id'], $parceirosVinculados) ? 'checked' : '' ?>>
              <span><?= htmlspecialchars($p['nome']) ?></span>
            </label>
            <?php endwhile; ?>
          </div>
        </div>

        <div style="display:flex; gap:12px; margin-top:28px; padding-top:20px; border-top:1px solid var(--bdna-border)">
          <button type="submit" class="bdna-btn bdna-btn-primary">
            <i class="bi bi-check-lg"></i> Salvar Alterações
          </button>
          <a href="../index.php" class="bdna-btn bdna-btn-outline">Cancelar</a>
        </div>

      </form>
    </div>
  </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const CAT_GESTAO   = [1, 6, 7, 8];
const CAT_CONTEUDO = [2, 3, 4, 5];

function toggleCampos() {
  const catId = parseInt(document.getElementById('id_categoria').value);
  document.getElementById('bloco_gestao').style.display   = CAT_GESTAO.includes(catId)   ? '' : 'none';
  document.getElementById('bloco_conteudo').style.display = CAT_CONTEUDO.includes(catId) ? '' : 'none';
}

toggleCampos(); // executar ao carregar com valor já selecionado
</script>

<?php include '../../pages/footer.php'; ?>
</body>
</html>
