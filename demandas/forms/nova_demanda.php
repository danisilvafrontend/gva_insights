<?php
session_start();
if (!isset($_SESSION['usuario_id'])) { header('Location: ../../index.php'); exit; }
require_once '../../config/db_connect.php';

$userId   = $_SESSION['usuario_id'];
$userNome = $_SESSION['usuario_nome'] ?? 'Usuário';
$isAdmin  = ($_SESSION['usuario_perfil'] ?? 'user') === 'admin';

// Busca usuários para admin
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
$meses = ['Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nova Demanda — GVA Insights</title>
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
                    <h4 class="mb-0"><i class="bi bi-plus-circle me-2 text-primary"></i>Nova Demanda</h4>
                    <small class="text-muted">Brasil DNA 2026</small>
                </div>
            </div>

            <div class="form-card">
                <form method="POST" action="processar_demanda.php">

                    <div class="section-title">Identificação</div>
                    <div class="row g-3">

                        <?php if ($isAdmin): ?>
                        <div class="col-md-6">
                            <label class="form-label">Responsável <span class="text-danger">*</span></label>
                            <select name="id_usuario" class="form-select" required>
                                <option value="">Selecione...</option>
                                <?php foreach ($usuarios as $u): ?>
                                <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php else: ?>
                        <input type="hidden" name="id_usuario" value="<?= $userId ?>">
                        <div class="col-md-6">
                            <label class="form-label">Responsável</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($userNome) ?>" disabled>
                        </div>
                        <?php endif; ?>

                        <div class="col-md-6">
                            <label class="form-label">Categoria <span class="text-danger">*</span></label>
                            <select name="categoria" id="categoria" class="form-select" required>
                                <option value="">Selecione...</option>
                                <?php foreach ($categorias as $cat): ?>
                                <option value="<?= $cat ?>"><?= $cat ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Mês de Referência</label>
                            <select name="mes" class="form-select">
                                <option value="">Selecione...</option>
                                <?php foreach ($meses as $m): ?>
                                <option value="<?= $m ?>"><?= $m ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Deadline</label>
                            <input type="date" name="deadline" class="form-control">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Prioridade</label>
                            <select name="prioridade" class="form-select">
                                <option value="Média" selected>Média</option>
                                <option value="Alta">Alta</option>
                                <option value="Baixa">Baixa</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Tarefa / Demanda <span class="text-danger">*</span></label>
                            <textarea name="tarefa" class="form-control" rows="3" required placeholder="Descreva a tarefa ou demanda..."></textarea>
                        </div>

                    </div>

                    <!-- Campos: Gestão & Roadshow -->
                    <div id="fields-gestao" class="fields-gestao d-none">
                        <div class="section-title">Detalhes — Gestão / Roadshow</div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Ação</label>
                                <input type="text" name="acao" class="form-control" placeholder="Ex: Parcerias, Contratos, Estratégia...">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Parceiros Envolvidos</label>
                                <input type="text" name="parceiros" class="form-control" placeholder="Ex: SEBRAE, APEX, Prefeitura...">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Observações Importantes</label>
                                <textarea name="detalhes" class="form-control" rows="3" placeholder="Observações, notas, instruções..."></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Campos: Conteúdo / Comunicação -->
                    <div id="fields-conteudo" class="fields-conteudo d-none">
                        <div class="section-title">Detalhes — Conteúdo / Comunicação</div>
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label">Tema / Conteúdo</label>
                                <input type="text" name="tipo_conteudo" class="form-control" placeholder="Tema do vídeo, webinar, post...">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Data de Publicação / Envio</label>
                                <input type="date" name="data_publicacao" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Parceiros Envolvidos</label>
                                <input type="text" name="parceiros" class="form-control" placeholder="Entidades, patrocinadores...">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Link Externo</label>
                                <input type="url" name="link_externo" class="form-control" placeholder="https://...">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Detalhes / Descrição</label>
                                <textarea name="detalhes" class="form-control" rows="3" placeholder="Detalhes de promoção, instruções de publicação..."></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="section-title">Finalização</div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Status Inicial</label>
                            <select name="status" class="form-select">
                                <option value="Pendente" selected>Pendente</option>
                                <option value="Em andamento">Em andamento</option>
                                <option value="Aguardando">Aguardando</option>
                            </select>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="../index.php" class="btn btn-outline-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Salvar Demanda</button>
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
    const gestao   = document.getElementById('fields-gestao');
    const conteudo = document.getElementById('fields-conteudo');
    gestao.classList.toggle('d-none',   !CAT_GESTAO.includes(cat));
    conteudo.classList.toggle('d-none', !CAT_CONTEUDO.includes(cat));
});
</script>
</body>
</html>
