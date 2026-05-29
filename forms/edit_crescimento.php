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

// Buscar os dados do registro
$sql = "SELECT * FROM crescimento_redes_sociais WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<div class='alert alert-info'>Registro não encontrado.</div>";
    include '../pages/footer.php';
    exit;
}

$row = $result->fetch_assoc();
?>

<div class="container">
    <h2 class="my-4">Editar Dados de Crescimento</h2>
    <?php
    $sql_empresas = "SELECT id, empresa FROM empresas ORDER BY empresa ASC";
    $result_empresas = $conn->query($sql_empresas);
    ?>

    <form action="../forms/update_crescimento.php" method="POST">
        <input type="hidden" name="id" value="<?php echo $row['id']; ?>">

        <div class="form-group">
        <label for="empresa_id">Empresa:</label>
        <select id="empresa_id" name="empresa_id" class="form-control" required>
            <option value="" disabled>Selecione a empresa</option>
            <?php 
            if ($result_empresas->num_rows > 0) {
                while ($empresa_row = $result_empresas->fetch_assoc()) {
                    $selected = ($empresa_row['id'] == $row['empresa_id']) ? "selected" : "";
                    echo "<option value=\"" . $empresa_row['id'] . "\" $selected>" . htmlspecialchars($empresa_row['empresa']) . "</option>";
                }
            }
            ?>
        </select>
    </div>


        <div class="form-group">
            <label for="plataforma">Plataforma:</label>
            <select id="plataforma" name="plataforma" class="form-control" required>
                <?php
                $plataformas = ["Instagram", "Facebook", "Linkedin"];
                foreach ($plataformas as $plataforma) {
                    $selected = ($plataforma == $row['plataforma']) ? "selected" : "";
                    echo "<option value=\"$plataforma\" $selected>$plataforma</option>";
                }
                ?>
            </select>
        </div>

        <div class="form-group">
            <label for="semana">Semana:</label>
            <select id="semana" name="semana" class="form-control" required>
                <?php 
                for($i=1; $i<=5; $i++) {
                    $selected = ($i == $row['semana']) ? "selected" : "";
                    echo "<option value=\"$i\" $selected>0$i</option>";
                }
                ?>
            </select>
        </div>

        <div class="form-group">
            <label for="numero_seguidores">Número de Seguidores:</label>
            <input type="number" id="numero_seguidores" name="numero_seguidores" class="form-control" required min="0" value="<?php echo htmlspecialchars($row['numero_seguidores']); ?>">
        </div>

         <!-- Mês do Relatório -->
        <div class="form-group form-floating mt-2">
            <input type="month" class="form-control" id="mes_relatorio" name="mes_relatorio"
                value="<?php echo $row['mes_relatorio']; ?>" required>
            <label for="mes_relatorio">Mês do Relatório:</label>
        </div>

        <button type="submit" class="btn btn-success mt-3">Salvar Alterações</button>
    </form>
</div>

<?php
$conn->close();
include '../pages/footer.php';
?>
