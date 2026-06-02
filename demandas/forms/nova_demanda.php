<?php
require_once '../../includes/auth.php';
require_login();
require_once '../../includes/db_connect.php';

$userId   = usuario_id();
$userNome = usuario_nome();
$isAdmin  = is_admin();

// Lista de usuários — admin pode cadastrar para qualquer um
$usuarios = [];
if ($isAdmin) {
    $resU = $conn->query("SELECT id, nome FROM usuarios ORDER BY nome");
    while ($u = $resU->fetch_assoc()) $usuarios[] = $u;
}

// Empresas e Clientes para os chips
$empresas = [];
$resEmp = $conn->query("SELECT id, empresa FROM empresas ORDER BY empresa ASC");
while ($e = $resEmp->fetch_assoc()) $empresas[] = $e;

$clientes = [];
$resCli = $conn->query("SELECT id, company FROM clientes ORDER BY company ASC");
while ($c = $resCli->fetch_assoc()) $clientes[] = $c;

// Categorias com subcategorias (espelhadas no checklist Brasil DNA 2026)
$categorias = [
    'Gestão & Planejamento' => [
        'Cronograma',
        'Locais',
        'Metas (clientes, marcas, números de pessoas)',
        'Parcerias',
        'Controle e Follow Up',
    ],
    'Comunicação' => [
        'Videos Promo',
        'Webinars',
        'Releases Brasil',
        'Releases EUA',
        'Newsletter',
        'Posts SoMe',
        'Plataforma',
    ],
    'Documentação' => [
        'Contratos',
        'Invoice',
        'Acordos',
        'Clientes',
        'Parceiros',
        'Fornecedores',
    ],
    'Organização e Execução' => [
        'Roadshow Presencial',
        'Roadshow Virtual',
        'Eventos Especiais',
        'Agenda B2B',
        'Travel Arrangements',
        'Promoção e RSVP',
    ],
    'Relatórios' => [
        'Template de Relatórios',
        'Atualização de Dados',
        'Entrega de Relatórios',
    ],
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
    <title>Nova Demanda</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/demandas.css">
    <style>
        .chip-select-group {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            padding: 6px 0;
        }
        .chip-select-group input[type="checkbox"] { display: none; }
        .chip-select-group label {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 14px;
            border: 1px solid #ccc;
            border-radius: 999px;
            font-size: 0.83rem;
            cursor: pointer;
            background: #fff;
            color: #555;
            transition: all 0.15s ease;
            user-select: none;
            line-height: 1.4;
        }
        .chip-select-group label::before {
            content: '';
            display: inline-block;
            width: 13px;
            height: 13px;
            border: 1.5px solid #aaa;
            border-radius: 3px;
            background: #fff;
            flex-shrink: 0;
            transition: all 0.15s ease;
        }
        .chip-select-group input[type="checkbox"]:checked + label {
            background: #e8f0fe;
            border-color: #0d6efd;
            color: #0d6efd;
            font-weight: 500;
        }
        .chip-select-group input[type="checkbox"]:checked + label::before {
            background: #0d6efd;
            border-color: #0d6efd;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 12 10' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M1 5l3.5 3.5L11 1' stroke='white' stroke-width='2' fill='none' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: center;
            background-size: 9px;
        }
        .chip-label-section {
            font-size: 0.78rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: #6c757d;
            margin-bottom: 2px;
        }
        #selectCategoria optgroup { font-weight: 700; color: #343a40; }
        #selectCategoria option   { font-weight: 400; color: #495057; }
    </style>
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
                    <small class="text-muted">Cadastro de nova demanda / tarefa</small>
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

                            <!-- Responsável -->
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

                            <!-- Categoria + Subcategoria -->
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Categoria <span class="text-danger">*</span></label>
                                <select name="categoria" id="selectCategoria" class="form-select" required>
                                    <option value="">Selecione...</option>
                                    <?php foreach ($categorias as $grupo => $subs): ?>
                                        <?php if (empty($subs)): ?>
                                            <option value="<?= htmlspecialchars($grupo) ?>"><?= htmlspecialchars($grupo) ?></option>
                                        <?php else: ?>
                                            <optgroup label="<?= htmlspecialchars($grupo) ?>">
                                                <?php foreach ($subs as $sub): ?>
                                                <option value="<?= htmlspecialchars($grupo . ' › ' . $sub) ?>">
                                                    <?= htmlspecialchars($sub) ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </optgroup>
                                        <?php endif; ?>
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

                            <!-- Tarefa -->
                            <div class="col-md-12">
                                <label class="form-label fw-semibold">Tarefa / Demanda <span class="text-danger">*</span></label>
                                <textarea name="tarefa" class="form-control" rows="3" required
                                    placeholder="Descreva a tarefa ou demanda..."></textarea>
                            </div>

                            <!-- Tema/Conteúdo (dinâmico para Comunicação) -->
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

                            <!-- EMPRESAS ENVOLVIDAS -->
                            <div class="col-md-12">
                                <div class="chip-label-section mb-1"><i class="bi bi-building me-1"></i>Empresas Envolvidas</div>
                                <div class="chip-select-group">
                                    <?php foreach ($empresas as $emp): ?>
                                        <input type="checkbox" name="empresas_envolvidas[]" id="emp_<?= $emp['id'] ?>" value="<?= $emp['id'] ?>">
                                        <label for="emp_<?= $emp['id'] ?>"><?= htmlspecialchars($emp['empresa']) ?></label>
                                    <?php endforeach; ?>
                                    <?php if (empty($empresas)): ?>
                                        <span class="text-muted small">Nenhuma empresa cadastrada.</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- CLIENTES ENVOLVIDOS -->
                            <div class="col-md-12">
                                <div class="chip-label-section mb-1"><i class="bi bi-person-badge me-1"></i>Clientes Envolvidos</div>
                                <div class="chip-select-group">
                                    <?php foreach ($clientes as $cli): ?>
                                        <input type="checkbox" name="clientes_envolvidos[]" id="cli_<?= $cli['id'] ?>" value="<?= $cli['id'] ?>">
                                        <label for="cli_<?= $cli['id'] ?>"><?= htmlspecialchars($cli['company']) ?></label>
                                    <?php endforeach; ?>
                                    <?php if (empty($clientes)): ?>
                                        <span class="text-muted small">Nenhum cliente cadastrado.</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Link externo (dinâmico para Comunicação) -->
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
// Subcategorias de Comunicação que exibem campo Tema/Conteúdo e Link
const CAT_CONTEUDO = [
    'Comunicação › Videos Promo',
    'Comunicação › Webinars',
    'Comunicação › Releases Brasil',
    'Comunicação › Releases EUA',
    'Comunicação › Newsletter',
    'Comunicação › Posts SoMe',
    'Comunicação › Plataforma'
];

document.getElementById('selectCategoria').addEventListener('change', function () {
    const cat = this.value;
    const isConteudo = CAT_CONTEUDO.includes(cat);
    document.querySelectorAll('.campo-conteudo').forEach(el => el.classList.toggle('d-none', !isConteudo));
    document.querySelectorAll('.campo-link').forEach(el     => el.classList.toggle('d-none', !isConteudo));
});
</script>
</body>
</html>
