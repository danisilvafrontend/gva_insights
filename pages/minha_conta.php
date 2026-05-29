<?php
session_start();
include '../includes/config.php';
include '../includes/db_connect.php';

include 'header.php'; 

// Verifica se está logado
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $senha_atual = $_POST['senha_atual'];
    $nova_senha = $_POST['nova_senha'];
    $confirmar_senha = $_POST['confirmar_senha'];

    if ($nova_senha !== $confirmar_senha) {
        $msg = 'A nova senha e a confirmação não coincidem.';
    } else {
        // Busca hash da senha atual no banco
        $stmt = $conn->prepare("SELECT senha FROM usuarios WHERE id = ?");
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $stmt->bind_result($senha_hash);
        $stmt->fetch();
        $stmt->close();

        // Verifica se senha atual confere
        if (!password_verify($senha_atual, $senha_hash)) {
            $msg = 'Senha atual incorreta.';
        } else {
            // Atualiza com nova senha
            $novo_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE usuarios SET senha = ? WHERE id = ?");
            $stmt->bind_param('si', $novo_hash, $user_id);
            if ($stmt->execute()) {
                $msg = 'Senha alterada com sucesso!';
            } else {
                $msg = 'Erro ao alterar senha.';
            }
            $stmt->close();
        }
    }
}
$conn->close();
?>
    
<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-2 bg-dark text-white p-4 pt-2">
            <?php include 'menu_lateral.php'; ?>
        </div>
        <div class="col-md-10 bg-light p-4 rounded">
        <!-- Cada relatório como card -->
            <div class="card shadow-sm mb-5" id="canais_parceiros">
                <div class="card-header bg-danger text-white text-center">
                    <h3><i class="bi bi-key"></i> Alterar Senha</h3>
                </div>
                <div class="card-body">
                    <div class="row justify-content-center">
                        <div class="col-md-5">
                            <?php if ($msg): ?>
                            <p><?php echo htmlspecialchars($msg); ?></p>
                            <?php endif; ?>
                            <form method="POST" action="minha_conta.php">
                                <div class="form-group form-floating mt-2">
                                    <input type="password" class="form-control" id="senha_atual" name="senha_atual" placeholder="Senha Atual" required>
                                    <label for="senha_atual">Senha Atual:</label>
                                </div>
                                <div class="form-group form-floating mt-2">
                                    <input type="password" class="form-control" id="nova_senha" name="nova_senha" placeholder="Nova Senha" required>
                                    <label for="nova_senha">Nova Senha:</label>
                                </div>
                                <div class="form-group form-floating mt-2">
                                    <input type="password" class="form-control" id="confirmar_senha" name="confirmar_senha" placeholder="Confirmar Nova Senha" required>
                                    <label for="confirmar_senha">Confirmar Nova Senha:</label>
                                </div>
                                <button type="submit" class="btn btn-success mt-2">Alterar Senha</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
</div>

<?php include 'footer.php'; ?>