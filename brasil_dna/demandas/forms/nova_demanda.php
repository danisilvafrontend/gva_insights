<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../../index.php');
    exit;
}

include '../../../includes/config.php';
include '../../../includes/db_connect.php';
mysqli_set_charset($conn, 'utf8mb4');

$userId      = (int)$_SESSION['user_id'];
$isAdmin     = isset($_SESSION['perfil']) && $_SESSION['perfil'] === 'admin';
$nomeUsuario = $_SESSION['nome'] ?? 'Usuário';

$categorias  = $conn->query('SELECT id, nome, cor_hex FROM bdna_categorias WHERE ativo=1 ORDER BY ordem');
$parceiros   = $conn->query('SELECT id, nome FROM bdna_parceiros WHERE ativo=1 ORDER BY nome');
$responsaveis = $isAdmin ? $conn->query('SELECT id, nome FROM usuarios WHERE ativo=1 ORDER BY nome') : null;
$meses       = ['Janeiro','Fevereiro','Marco','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro'];

$conn->close();
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
        <span>📋</span>
        Nova Demanda
        <small style="font-size:14px;font-weight:400;color:var(--bdna-text-muted)">— <?= htmlspecialchars($nomeUsuario) ?></small>
      </h1>
      <a href="../index.php" class="bdna-btn bdna-btn-outline">
        <i class="bi bi-arrow-left"></i> Voltar
      </a>
    </div>

    <form method="POST" action="processar_demanda.php" id="frmDemanda">
      <input type="hidden" name="id_usuario" value="<?= $isAdmin ? '' : $userId ?>" id="hdnUsuario">

      <div class="bdna-form-card">

        <!-- Barra de progresso visual -->
        <div class="demanda-progress"><div class="demanda-progress-bar" id="progressBar" style="width:20%"></div></div>

        <h5><i class="bi bi-pencil-square" style="color:var(--bdna-primary)"></i> Informações da Demanda</h5>

        <!-- Responsável (apenas admin vê) -->
        <?php if ($isAdmin && $responsaveis): ?>
        <div class="mb-3">
          <label class="bdna-label">Responsável <span class="req">*</span></label>
          <select name="id_usuario" class="bdna-select" required onchange="document.getElementById('hdnUsuario').value=this.value">
            <option value="">Selecionar responsável...</option>
            <?php while ($u = $responsaveis->fetch_assoc()): ?>
            <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['nome']) ?></option>
            <?php endwhile; ?>
          </select>
        </div>
        <?php endif; ?>

        <!-- Categoria -->
        <div class="mb-3">
          <label class="bdna-label">Categoria <span class="req">*</span></label>
          <select name="id_categoria" id="sltCategoria" class="bdna-select" required onchange="handleCategoria(this)">
            <option value="">Selecionar categoria...</option>
            <?php while ($c = $categorias->fetch_assoc()): ?>
            <option value="<?= $c['id'] ?>"
                    data-cor="<?= htmlspecialchars($c['cor_hex']) ?>"
                    data-nome="<?= htmlspecialchars($c['nome']) ?>">
              <?= htmlspecialchars($c['nome']) ?>
            </option>
            <?php endwhile; ?>
          </select>
          <div id="catPreview" class="demanda-cat-preview" style="display:none"></div>
        </div>

        <!-- Tarefa / Demanda -->
        <div class="mb-3">
          <label class="bdna-label">Descrição da Demanda <span class="req">*</span></label>
          <textarea name="tarefa" class="bdna-textarea" rows="3" placeholder="Descreva claramente a tarefa ou demanda..." required maxlength="500"></textarea>
          <div class="demanda-hint">Máximo 500 caracteres. Seja objetivo e específico.</div>
        </div>

        <!-- Linha: Mês + Deadline + Prioridade -->
        <div class="bdna-form-row">
          <div>
            <label class="bdna-label">Mês de Referência</label>
            <select name="mes_referencia" class="bdna-select">
              <option value="">Selecionar mês...</option>
              <?php foreach ($meses as $m): ?>
              <option value="<?= $m ?>"><?= $m ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="bdna-label">Deadline</label>
            <input type="date" name="deadline" class="bdna-input">
          </div>
        </div>

        <div class="mt-3">
          <label class="bdna-label">Prioridade</label>
          <div style="display:flex;gap:10px;margin-top:4px;flex-wrap:wrap">
            <?php foreach (['Alta'=>'pri-alta','Media'=>'pri-media','Baixa'=>'pri-baixa'] as $p => $cls): ?>
            <label style="display:flex;align-items:center;gap:6px;cursor:pointer">
              <input type="radio" name="prioridade" value="<?= $p ?>" <?= $p==='Media'?'checked':'' ?>>
              <span class="bdna-pri <?= $cls ?>"><?= $p === 'Media' ? 'Média' : $p ?></span>
            </label>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Seção condicional: Gestão / Roadshow -->
        <div id="secGestao" class="demanda-section-condicional bdna-form-section">
          <div class="bdna-form-section-title"><i class="bi bi-diagram-3"></i> Detalhes — Gestão / Roadshow</div>
          <div class="bdna-form-row">
            <div>
              <label class="bdna-label">Ação</label>
              <input type="text" name="acao" class="bdna-input" placeholder="Ex: Parcerias, Contratos, Estratégia...">
            </div>
            <div>
              <label class="bdna-label">Observações Importantes</label>
              <input type="text" name="detalhes" class="bdna-input" placeholder="Observações...">
            </div>
          </div>
        </div>

        <!-- Seção condicional: Conteúdo (Vídeos, Webinars, News, Posts) -->
        <div id="secConteudo" class="demanda-section-condicional bdna-form-section">
          <div class="bdna-form-section-title"><i class="bi bi-camera-video"></i> Detalhes — Conteúdo / Publicação</div>
          <div class="bdna-form-row">
            <div>
              <label class="bdna-label">Tema / Conteúdo</label>
              <input type="text" name="tema_conteudo" class="bdna-input" placeholder="Tema ou título do conteúdo...">
            </div>
            <div>
              <label class="bdna-label">Data de Publicação / Envio</label>
              <input type="date" name="data_publicacao" class="bdna-input">
            </div>
          </div>
          <div class="mt-3">
            <label class="bdna-label">Link Externo <small style="font-weight:400">(gravação, post, release...)</small></label>
            <input type="url" name="link_externo" class="bdna-input" placeholder="https://...">
          </div>
          <div class="mt-3">
            <label class="bdna-label">Detalhes / Observações</label>
            <textarea name="detalhes" class="bdna-textarea" rows="2" placeholder="Detalhes de promoção, distribuição, etc."></textarea>
          </div>
        </div>

        <!-- Parceiros envolvidos -->
        <?php if ($parceiros && $parceiros->num_rows > 0): ?>
        <div class="bdna-form-section">
          <div class="bdna-form-section-title"><i class="bi bi-people"></i> Parceiros Envolvidos <small style="font-weight:400;text-transform:none">(opcional)</small></div>
          <div class="demanda-parceiros-wrap bdna-checkboxes">
            <?php while ($p = $parceiros->fetch_assoc()): ?>
            <label>
              <input type="checkbox" name="parceiros[]" value="<?= $p['id'] ?>">
              <span><?= htmlspecialchars($p['nome']) ?></span>
            </label>
            <?php endwhile; ?>
          </div>
        </div>
        <?php endif; ?>

        <!-- Botões -->
        <div style="display:flex;gap:12px;margin-top:28px;padding-top:20px;border-top:1px solid var(--bdna-border);justify-content:flex-end">
          <a href="../index.php" class="bdna-btn bdna-btn-outline">Cancelar</a>
          <button type="submit" class="bdna-btn bdna-btn-primary">
            <i class="bi bi-check-lg"></i> Salvar Demanda
          </button>
        </div>

      </div><!-- .bdna-form-card -->
    </form>

  </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Mapeamento de categorias para seções condicionais
// Categorias 1,2 = Gestão/Roadshow | 3,4,5,6,7 = Conteúdo
// Ajuste os IDs conforme seu banco bdna_categorias
const CAT_GESTAO    = [1, 6, 7]; // Gestão & Planejamento, Roadshow Presencial, Roadshow Virtual
const CAT_CONTEUDO  = [2, 3, 4, 5]; // Vídeos, Webinars, News, Posts SoMe

function handleCategoria(sel) {
  const id     = parseInt(sel.value);
  const opt    = sel.options[sel.selectedIndex];
  const cor    = opt.getAttribute('data-cor') || '#0077B6';
  const nome   = opt.getAttribute('data-nome') || '';

  // Preview badge
  const prev = document.getElementById('catPreview');
  if (nome) {
    prev.style.display = 'inline-flex';
    prev.style.color   = cor;
    prev.innerHTML = `<span style="width:8px;height:8px;border-radius:50%;background:${cor};display:inline-block"></span> ${nome}`;
  } else {
    prev.style.display = 'none';
  }

  // Seções condicionais
  document.getElementById('secGestao').classList.toggle('ativo',   CAT_GESTAO.includes(id));
  document.getElementById('secConteudo').classList.toggle('ativo', CAT_CONTEUDO.includes(id));

  updateProgress();
}

function updateProgress() {
  const fields = document.querySelectorAll('#frmDemanda [required]');
  let filled = 0;
  fields.forEach(f => { if (f.value.trim()) filled++; });
  const pct = Math.min(100, Math.round((filled / fields.length) * 100));
  document.getElementById('progressBar').style.width = pct + '%';
}

document.querySelectorAll('#frmDemanda input, #frmDemanda select, #frmDemanda textarea')
  .forEach(el => el.addEventListener('change', updateProgress));
</script>

<?php include '../../../pages/footer.php'; ?>
</body>
</html>
