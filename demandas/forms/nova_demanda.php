<?php
require_once '../../includes/auth.php';
require_login();
can_manage_registros() || (http_response_code(403) && exit('Acesso negado. Apenas níveis 1 e 2 podem criar demandas.'));
require_once '../../includes/db_connect.php';

$userId  = usuario_id();
$userNome = usuario_nome();
$isAdmin = is_admin();

// Lista de usuários (nível 1 pode cadastrar para qualquer um)
$usuarios = [];
if ($isAdmin) {
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
$meses = [
    'Janeiro','Fevereiro','Março','Abril','Maio','Junho',
    'Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'
];
$statusOpcoes = ['Pendente','Em andamento','Produzindo','Enviado','Publicado','Aguardando','Done','Atrasado'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nova Demanda — Brasil DNA 2026</title>
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

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="mb-0"><i class="bi bi-plus-circle me-2 text-primary"></i>Nova Demanda</h4>
                    <small class="text-muted">Brasil DNA 2026</small>
                </div>
                <a href="../index.php" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i> Voltar
                </a>
            </div>

            <?php if (!empty($_GET['erro'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($_GET['erro']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <i class="bi bi-clipboard-plus me-2"></i>Cadastrar Nova Demanda / Tarefa
                </div>
                <div class="card-body">
                    <form method="POST" action="processar_demanda.php" id="formDemanda">

                        <div class="row g-3">

                            <!-- Responsável (nível 1 vê select, demais vêem nome fixo) -->
                            <?php if ($isAdmin): ?>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Responsável <span class="text-danger">*</span></label>
                                <select name="id_usuario" class="form-select" required>
                                    <option value="">Selecione...</option>
                                    <?php foreach ($usuarios as $u): ?>
                                    <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['nome']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php else: ?>
                            <input type="hidden" name="id_usuario" value="<?= $userId ?>">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Responsável</label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($userNome) ?>" disabled>
                            </div>
                            <?php endif; ?>

                            <!-- Categoria -->
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Categoria <span class="text-danger">*</span></label>
                                <select name="categoria" id="selectCategoria" class="form-select" required>
                                    <option value="">Selecione...</option>
                                    <?php foreach ($categorias as $cat): ?>
                                    <option value="<?= $cat ?>"><?= $cat ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Mês -->
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Mês de Referência</label>
                                <select name="mes" class="form-select">
                                    <option value="">— Selecione —</option>
                                    <?php foreach ($meses as $m): ?>
                                    <option value="<?= $m ?>"><?= $m ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Ação (campo dinâmico) -->
                            <div class="col-md-4 campo-acao d-none">
                                <label class="form-label fw-semibold">Ação / Área</label>
                                <input type="text" name="acao" class="form-control" placeholder="Ex: Parcerias, Contratos, Estratégia...">
                            </div>

                            <!-- Tarefa -->
                            <div class="col-md-12">
                                <label class="form-label fw-semibold">Tarefa / Demanda <span class="text-danger">*</span></label>
                                <textarea name="tarefa" class="form-control" rows="3" required
                                    placeholder="Descreva a tarefa ou demanda..."></textarea>
                            </div>

                            <!-- Tema/Conteúdo (dinâmico) -->
                            <div class="col-md-6 campo-conteudo d-none">
                                <label class="form-label fw-semibold">Tema / Conteúdo</label>
                                <input type="text" name="tipo_conteudo" class="form-control" placeholder="Tema ou assunto do conteúdo">
                            </div>

                            <!-- Deadline -->
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Deadline</label>
                                <input type="date" name="deadline" class="form-control">
                            </div>

                            <!-- Prioridade -->
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Prioridade <span class="text-danger">*</span></label>
                                <select name="prioridade" class="form-select" required>
                                    <option value="Media" selected>Média</option>
                                    <option value="Alta">Alta 🔴</option>
                                    <option value="Baixa">Baixa 🟢</option>
                                </select>
                            </div>

                            <!-- Status -->
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                                <select name="status" class="form-select" required>
                                    <?php foreach ($statusOpcoes as $s): ?>
                                    <option value="<?= $s ?>" <?= $s === 'Pendente' ? 'selected' : '' ?>><?= $s ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Parceiros -->
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Parceiros / Entidades Envolvidas</label>
                                <input type="text" name="parceiros" class="form-control" placeholder="Ex: Sebrae, Senai, Governo do Estado...">
                            </div>

                            <!-- Link externo (dinâmico) -->
                            <div class="col-md-6 campo-link d-none">
                                <label class="form-label fw-semibold">Link Externo</label>
                                <input type="url" name="link_externo" class="form-control" placeholder="https://...">
                            </div>

                            <!-- Detalhes / Observações -->
                            <div class="col-md-12">
                                <label class="form-label fw-semibold">Detalhes / Observações</label>
                                <textarea name="detalhes" class="form-control" rows="2"
                                    placeholder="Informações adicionais, observações importantes..."></textarea>
                            </div>

                        </div>

                        <div class="mt-4 d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg me-1"></i> Salvar Demanda
                            </button>
                            <a href="../index.php" class="btn btn-outline-secondary">Cancelar</a>
                        </div>

                    </form>
                </div>
            </div>

        </div>
    </div>
</div>
<?php include '../../pages/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const categoriasComAcao     = ['Gestão & Planejamento','Roadshow Presencial','Roadshow Virtual / Eventos Especiais'];
const categoriasComConteudo = ['Videos Promo','Webinars','News & Releases','Posts SoMe'];
const categoriasComLink     = ['Videos Promo','Webinars','News & Releases','Posts SoMe'];

document.getElementById('selectCategoria').addEventListener('change', function () {
    const cat = this.value;
    document.querySelectorAll('.campo-acao').forEach(el     => el.classList.toggle('d-none', !categoriasComAcao.includes(cat)));
    document.querySelectorAll('.campo-conteudo').forEach(el => el.classList.toggle('d-none', !categoriasComConteudo.includes(cat)));
    document.querySelectorAll('.campo-link').forEach(el     => el.classList.toggle('d-none', !categoriasComLink.includes(cat)));
});
</script>
</body>
</html>
