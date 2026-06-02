<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_id'])) { header('Location: ../../index.php'); exit; }

include '../../includes/config.php';
include '../../includes/db_connect.php';
require_once '../teams_webhook.php';
mysqli_set_charset($conn, 'utf8mb4');

$categorias = $conn->query('SELECT id, nome, cor_hex FROM bdna_categorias WHERE ativo=1 ORDER BY ordem');
$parceiros  = $conn->query('SELECT id, nome, tipo FROM bdna_parceiros WHERE ativo=1 ORDER BY nome');
$responsaveis = $conn->query('SELECT id, nome FROM usuarios WHERE ativo=1 ORDER BY nome');
$meses = ['Janeiro','Fevereiro','Marco','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro'];
$conn->close();
?>
<?php include '../../pages/header.php'; ?>
<link rel="stylesheet" href="../../brasil_dna/assets/brasil_dna.css">

<body class="bdna-page">
<div class="bdna-wrapper">
  <div class="bg-dark text-white p-4 rounded">
    <?php include '../../pages/menu_lateral.php'; ?>  
  </div>

  <main class="bdna-main">
    <div class="bdna-page-header">
      <h1 class="bdna-page-title">
        <span class="bdna-flag">🇧🇷</span> Nova Tarefa — Brasil DNA
      </h1>
      <a href="../index.php" class="bdna-btn bdna-btn-outline">
        <i class="bi bi-arrow-left"></i> Voltar
      </a>
    </div>

    <div class="bdna-form-card">
      <h5><i class="bi bi-plus-circle" style="color:var(--bdna-primary)"></i> Registrar Tarefa / Demanda</h5>

      <form action="processar_tarefa.php" method="POST">

        <!-- BLOCO 1: Informações principais -->
        <div class="bdna-form-section-title">Informações Principais</div>

        <div class="bdna-form-row">
          <div>
            <label class="bdna-label">Categoria <span class="req">*</span></label>
            <select class="bdna-select" id="id_categoria" name="id_categoria" required onchange="toggleCampos()">
              <option value="">Selecione...</option>
              <?php while ($c = $categorias->fetch_assoc()): ?>
              <option value="<?= $c['id'] ?>" data-cor="<?= $c['cor_hex'] ?>">
                <?= htmlspecialchars($c['nome']) ?>
              </option>
              <?php endwhile; ?>
            </select>
          </div>
          <div>
            <label class="bdna-label">Responsável <span class="req">*</span></label>
            <select class="bdna-select" name="id_usuario" required>
              <option value="">Selecione...</option>
              <?php while ($u = $responsaveis->fetch_assoc()): ?>
              <option value="<?= $u['id'] ?>"
                <?= ($_SESSION['user_id'] == $u['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($u['nome']) ?>
              </option>
              <?php endwhile; ?>
            </select>
          </div>
        </div>

        <div style="margin-top:14px">
          <label class="bdna-label">Tarefa / Demanda <span class="req">*</span></label>
          <textarea class="bdna-textarea" name="tarefa" rows="3"
                    placeholder="Descreva a tarefa ou demanda..." required></textarea>
        </div>

        <div class="bdna-form-row" style="margin-top:14px">
          <div>
            <label class="bdna-label">Mês de Referência</label>
            <select class="bdna-select" name="mes_referencia">
              <option value="">Selecione...</option>
              <?php foreach ($meses as $m): ?>
              <option value="<?= $m ?>"><?= $m ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="bdna-label">Deadline</label>
            <input type="date" class="bdna-input" name="deadline">
          </div>
        </div>

        <div class="bdna-form-row" style="margin-top:14px">
          <div>
            <label class="bdna-label">Prioridade <span class="req">*</span></label>
            <select class="bdna-select" name="prioridade" required>
              <option value="Media" selected>Média</option>
              <option value="Alta">Alta</option>
              <option value="Baixa">Baixa</option>
            </select>
          </div>
          <div>
            <label class="bdna-label">Status</label>
            <select class="bdna-select" name="status">
              <?php foreach (['Pendente','Em andamento','Produzindo','Aguardando','Enviado','Publicado','Done'] as $s): ?>
              <option value="<?= $s ?>" <?= $s=='Pendente' ? 'selected':'' ?>><?= $s ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <!-- BLOCO 2: Gestão / Roadshow -->
        <div class="bdna-form-section" id="bloco_gestao" style="display:none">
          <div class="bdna-form-section-title">Gestão &amp; Roadshow</div>
          <div class="bdna-form-row">
            <div>
              <label class="bdna-label">Ação</label>
              <input type="text" class="bdna-input" name="acao"
                     placeholder="Ex: Parcerias, Contratos, Estratégia...">
            </div>
            <div>
              <label class="bdna-label">Observações Importantes</label>
              <input type="text" class="bdna-input" name="observacoes"
                     placeholder="Observações relevantes">
            </div>
          </div>
          <div style="margin-top:14px">
            <label class="bdna-label">Notes</label>
            <textarea class="bdna-textarea" name="notes" rows="2" placeholder="Notas adicionais..."></textarea>
          </div>
        </div>

        <!-- BLOCO 3: Conteúdo (Vídeos, Webinars, Posts, News) -->
        <div class="bdna-form-section" id="bloco_conteudo" style="display:none">
          <div class="bdna-form-section-title">Detalhes do Conteúdo</div>
          <div class="bdna-form-row">
            <div>
              <label class="bdna-label">Tema / Conteúdo</label>
              <input type="text" class="bdna-input" name="tema_conteudo"
                     placeholder="Ex: Destinos do Brasil no verão...">
            </div>
            <div>
              <label class="bdna-label" id="label_data_acao">Data de Lançamento / Envio</label>
              <input type="date" class="bdna-input" name="data_acao">
            </div>
          </div>
          <div style="margin-top:14px">
            <label class="bdna-label">Detalhes da Promoção / Distribuição</label>
            <textarea class="bdna-textarea" name="detalhes_promocao" rows="2"
                      placeholder="Como será promovido / distribuído..."></textarea>
          </div>
          <div style="margin-top:14px">
            <label class="bdna-label">Link (gravação, post, release...)</label>
            <input type="url" class="bdna-input" name="link_externo"
                   placeholder="https://...">
          </div>
          <div style="margin-top:14px">
            <label class="bdna-label">Notes</label>
            <textarea class="bdna-textarea" name="notes" rows="2" placeholder="Notas adicionais..."></textarea>
          </div>
        </div>

        <!-- BLOCO 4: Parceiros (para todas as categorias) -->
        <div class="bdna-form-section">
          <div class="bdna-form-section-title">Parceiros Envolvidos</div>
          <div class="bdna-checkboxes" id="lista_parceiros">
            <?php while ($p = $parceiros->fetch_assoc()): ?>
            <label>
              <input type="checkbox" name="parceiros[]" value="<?= $p['id'] ?>">
              <span><?= htmlspecialchars($p['nome']) ?></span>
            </label>
            <?php endwhile; ?>
          </div>
        </div>

        <!-- Botões -->
        <div style="display:flex; gap:12px; margin-top:28px; padding-top:20px; border-top:1px solid var(--bdna-border)">
          <button type="submit" class="bdna-btn bdna-btn-primary">
            <i class="bi bi-check-lg"></i> Salvar Tarefa
          </button>
          <a href="../index.php" class="bdna-btn bdna-btn-outline">
            Cancelar
          </a>
        </div>

      </form>
    </div>
  </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Categorias que usam bloco gestão
const CAT_GESTAO    = [1, 6, 7, 8]; // IDs de Gestão, Roadshow Presencial, Virtual, Eventos
const CAT_CONTEUDO  = [2, 3, 4, 5]; // Vídeos, Webinars, News, Posts

function toggleCampos() {
  const catId = parseInt(document.getElementById('id_categoria').value);
  const bG = document.getElementById('bloco_gestao');
  const bC = document.getElementById('bloco_conteudo');

  bG.style.display = CAT_GESTAO.includes(catId)   ? '' : 'none';
  bC.style.display = CAT_CONTEUDO.includes(catId) ? '' : 'none';

  // Labels dinâmicos
  const lbl = document.getElementById('label_data_acao');
  if (lbl) {
    if (catId === 4) lbl.textContent = 'Data de Envio';
    else if (catId === 5) lbl.textContent = 'Data da Postagem';
    else if (catId === 3) lbl.textContent = 'Data da Gravação';
    else lbl.textContent = 'Data de Lançamento';
  }
}
</script>

<?php include '../../pages/footer.php'; ?>
</body>
</html>
