<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

include '../pages/header.php';
include '../includes/db_connect.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    echo "<div class='alert alert-danger'>ID inválido.</div>";
    include '../pages/footer.php';
    exit;
}

$sql = "SELECT * FROM empresas WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<div class='alert alert-info'>Empresa não encontrada.</div>";
    include '../pages/footer.php';
    exit;
}

$row = $result->fetch_assoc();
?>

<div class="container">
    <h2 class="my-4">Editar Empresa</h2>
    <form action="../forms/update_empresa.php" method="POST">
        <input type="hidden" name="id" value="<?php echo $row['id']; ?>">

        <div class="form-group">
            <label for="empresa">Empresa:</label>
            <input type="text" id="empresa" name="empresa" class="form-control" value="<?php echo htmlspecialchars($row['empresa']); ?>" required>
        </div>

        <div class="form-group">
            <label for="responsavel">Responsável:</label>
            <input type="text" id="responsavel" name="responsavel" class="form-control" value="<?php echo htmlspecialchars($row['responsavel']); ?>" required>
        </div>

        <button type="submit" class="btn btn-success mt-3">Salvar</button>
    </form>
</div>

<?php
$conn->close();
include '../pages/footer.php';
?>
