<?php 
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

include '../includes/db_connect.php';  // Inclua antes do uso do $conn

$user_id = $_SESSION['user_id'];

// Busca o nome do usuário no banco
$stmt = $conn->prepare("SELECT nome FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($nome_usuario);
$stmt->fetch();
$stmt->close();

include 'header.php'; 
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-2 bg-dark text-white p-4 pt-2">
            <?php include 'menu_lateral.php'; ?>
        </div>
        <div class="col-md-10 bg-light p-4 rounded">
        <!-- Cada relatório como card -->
            <div class="card shadow-sm mb-5" id="canais_parceiros">
                <div class="card-header bg-primary text-white text-center">
                    <h3><i class="bi bi-person-plus me-2"></i> Clientes GVA Company</h3>
                </div>
                <div class="card-body">
                    <div class="row justify-content-center">
                        <div class="col-md-5">
                            <?php include '../forms/registrar_cliente.php'; ?>
                        </div>
                    </div>

                    <div class="text-center mt-4">
                        <a href="../views_bd/views_clientes.php" class="btn btn-outline-primary">Ver últimos Registros</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
</div>



<?php include 'footer.php'; ?>