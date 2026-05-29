<?php 
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

include '../includes/db_connect.php';  

$user_id = $_SESSION['user_id'];

// Busca o nome do usuário
$stmt = $conn->prepare("SELECT nome FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($nome_usuario);
$stmt->fetch();
$stmt->close();

include 'header.php'; 
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8 text-center">
            <h2 class="fw-bold text-primary">Bem-vindo(a), <?php echo htmlspecialchars($nome_usuario); ?> 👋</h2>
            <p class="text-muted">Aqui você acessa os relatórios interativos do <strong>GVA Insights</strong>.</p>
        </div>
    </div>
</div>

<div class="container-fluid mt-4" id="cadastraCliente">
    <div class="row">
        <!-- Menu lateral -->
        <div class="col-md-2 bg-dark text-white p-4 rounded">
            <?php include 'menu_lateral.php'; ?>
        </div>

        <!-- Painel principal -->
        <div class="col-md-10 bg-light p-4 rounded">
            <div class="card shadow-lg border-0">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0"><i class="bi bi-bar-chart-fill me-2"></i> Dashboard Power BI</h4>
                    <button class="btn btn-sm btn-outline-light" onclick="location.reload();">
                        <i class="bi bi-arrow-clockwise"></i> Atualizar
                    </button>
                </div>
                <div class="card-body text-center">
                    <!-- iframe responsivo -->
                    <div class="ratio ratio-16x9">
                        <iframe title="Relatório Geral" src="https://app.powerbi.com/view?r=eyJrIjoiYzExYzc1MDItYTJiZS00ODk2LTkxZTctODJhOGYwMTVlY2FhIiwidCI6IjlhNDM5N2ExLTAzNGYtNGJmYy1hYzIzLTViMTEwNWU3NzMzOSJ9" frameborder="0" allowFullScreen="true"></iframe>
                    </div>
                </div>
                <div class="card-footer text-muted text-center">
                    <small>Última atualização automática do Power BI</small>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>