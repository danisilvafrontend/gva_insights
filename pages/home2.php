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

<div class="container">
    <div class="row justify-content-center ">
        <div class="col col-md-6 text-center mt-4 mb-4">
            <h2>Bem-vindo(a), <?php echo htmlspecialchars($nome_usuario); ?> ao GVA Insights</h2>
            <p>Escolha uma opção para começar.</p>
        </div>
    </div>
</div>

<div class="container p-5" id="cadastraCliente">

    <div class="row">
        <div class="col-4 col-md-2 bg-black p-4">
            <?php
            include 'menu_lateral.php';
            ?>
        </div>
        <div class="col-8 col-md-10 painel bg-cinza">
            <div class="row">
                <iframe title="Relatório Geral" width="1024" height="612" src="https://app.powerbi.com/view?r=eyJrIjoiYzExYzc1MDItYTJiZS00ODk2LTkxZTctODJhOGYwMTVlY2FhIiwidCI6IjlhNDM5N2ExLTAzNGYtNGJmYy1hYzIzLTViMTEwNWU3NzMzOSJ9" frameborder="0" allowFullScreen="true"></iframe>
            </div>
        </div>
    </div>
    
</div>



<?php include 'footer.php'; ?>