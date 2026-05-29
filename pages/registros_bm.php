<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

include 'header.php';
?>
<meta charset="UTF-8">

<div class="container mt-5" id="top">
    <div class="row justify-content-between align-items-center">
        <div class="col-md-8 text-center">
            <h1 class="fw-bold text-primary">📊 Registros para Relatórios</h1>
        </div>
        <div class="col-md-4 text-end">
            <a href="home.php" class="btn btn-outline-dark">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
        </div>
    </div>
</div>

<!-- Navbar fixa para navegação rápida -->
<div class="container sticky-top bg-light shadow-sm mt-4 rounded">
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container-fluid justify-content-center">
            <a class="nav-link fw-bold" href="#canais_parceiros">📡 Canais</a>
            <a class="nav-link fw-bold" href="#lives_eventos">🎥 Lives & Eventos</a>
            <a class="nav-link fw-bold" href="#newsletter">📧 Newsletter</a>
            <a class="nav-link fw-bold" href="#redes-sociais">📱 Mídias Sociais</a>
            <a class="nav-link fw-bold" href="#anuncios">💰 Anúncios</a>
            <a class="nav-link fw-bold" href="#press_release">📰 Press Release</a>
            <a class="nav-link fw-bold" href="#contatos_pr">🤝 Contatos PR</a>
            <a class="nav-link fw-bold" href="#press_release_clipagem">📑 Clipagem</a>
        </div>
    </nav>
</div>

<div class="container-fluid mt-4">
    <div class="row">
        <!-- Menu lateral -->
        <div class="col-md-2 bg-dark text-white p-4 pt-2">
            <?php include 'menu_lateral.php'; ?>
        </div>

        <!-- Painel principal -->
        <div class="col-md-10 bg-light p-4 rounded">
            
            <!-- Cada relatório como card -->
            <div class="card shadow-sm mb-5" id="canais_parceiros">
                <div class="card-header bg-primary text-white text-center">
                    <h3>📡 Canais Parceiros Bureau Mundo</h3>
                </div>
                <div class="card-body">
                    <div class="row justify-content-center">
                        <div class="col-md-5">
                            <?php include '../forms/registrar_canal.php'; ?>
                        </div>
                    </div>

                    <div class="text-center mt-4">
                        <a href="../views_bd/views_canais.php" class="btn btn-outline-primary">Ver últimos Registros</a>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-5" id="lives_eventos">
                <div class="card-header bg-success text-white text-center">
                    <h3>🎥 Lives e Eventos Bureau Mundo</h3>
                </div>
                <div class="card-body row">
                    <div class="col-md-6">
                        <?php include '../forms/registrar_lives.php'; ?>
                        <div class="mt-4">
                            <a href="../views_bd/views_lives.php" class="btn btn-outline-success">Ver últimos Registros Lives</a>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <?php include '../forms/registrar_ondemand.php'; ?>
                        <div class="mt-4">
                            <a href="../views_bd/views_ondemand.php" class="btn btn-outline-success">Ver últimos Registros Ondemand</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-5" id="newsletter">
                <div class="card-header bg-warning text-dark text-center">
                    <h3>📧 Newsletters</h3>
                </div>
                <div class="card-body">
                    <div class="row justify-content-center">
                        <div class="col-md-5">
                            <?php include '../forms/registrar_newsletter.php'; ?>
                        </div>
                    </div>
                    <div class="text-center mt-4">
                        <a href="../views_bd/views_newsletter.php" class="btn btn-outline-warning">Ver últimos Registros</a>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-5" id="redes-sociais">
                <div class="card-header bg-info text-white text-center">
                    <h3>📱 Mídias Sociais</h3>
                </div>
                <div class="card-body row">
                    <div class="col-md-6">
                        <?php include '../forms/registrar_media_report.php'; ?>
                        <div class="mt-4">
                            <a href="../views_bd/views_media_report.php" class="btn btn-outline-info">Ver últimos Registros</a>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <?php include '../forms/registrar_crescimento.php'; ?>
                        <div class="mt-4">
                            <a href="../views_bd/views_crescimento.php" class="btn btn-outline-info">Ver últimos Registros</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-5" id="anuncios">
                <div class="card-header bg-danger text-white text-center">
                    <h3>💰 Anúncios Redes Sociais</h3>
                </div>
                <div class="card-body">                    
                    <div class="row justify-content-center">
                        <div class="col-md-5">
                            <?php include '../forms/registrar_anuncios.php'; ?>
                        </div>
                    </div>                    
                    <div class="text-center mt-4">
                        <a href="../views_bd/views_anuncios.php" class="btn btn-outline-danger">Ver últimos Registros</a>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-5" id="press_release">
                <div class="card-header bg-secondary text-white text-center">
                    <h3>📰 Press Release</h3>
                </div>
                <div class="card-body">                    
                    <div class="row justify-content-center">
                        <div class="col-md-5">
                            <?php include '../forms/registrar_press_release.php'; ?>
                        </div>
                    </div>    
                    
                    <div class="text-center mt-4">
                        <a href="../views_bd/views_press_release.php" class="btn btn-outline-secondary">Ver últimos Registros</a>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-5" id="contatos_pr">
                <div class="card-header bg-dark text-white text-center">
                    <h3>🤝 Relações Públicas</h3>
                </div>
                <div class="card-body">                    
                    <div class="row justify-content-center">
                        <div class="col-md-5">
                            <?php include '../forms/registrar_contatos_pr.php'; ?>
                        </div>
                    </div>    
                    
                    <div class="text-center mt-4">
                        <a href="../views_bd/views_contatos_pr.php" class="btn btn-outline-dark">Ver últimos Registros</a>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-5" id="press_release_clipagem">
                <div class="card-header bg-primary text-white text-center">
                    <h3>📑 Press Release Clipagem</h3>
                </div>
                <div class="card-body">                    
                    <div class="row justify-content-center">
                        <div class="col-md-5">
                            <?php include '../forms/registrar_press_release_clipagem.php'; ?>
                        </div>
                    </div>    
                    
                    <div class="text-center mt-4">
                        <a href="../views_bd/views_press_release_clipagem.php" class="btn btn-outline-primary">Ver últimos Registros</a>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php include 'footer.php'; ?>