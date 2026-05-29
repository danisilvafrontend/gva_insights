<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

include '../pages/header.php'; 
include '../includes/db_connect.php';
mysqli_set_charset($conn, "utf8mb4"); 
?>
<meta charset="UTF-8">

<?php
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    echo "<div class='alert alert-danger'>ID inválido.</div>";
    include '../pages/footer.php';
    exit;
}

$sql = "SELECT * FROM contatos_pr WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows == 0) {
    echo '<div class="alert alert-info">Registro não encontrado.</div>'; 
    $conn->close(); 
    exit; 
}
$row = $result->fetch_assoc();
$stmt->close();
?>

<div class="container">
    <h2 class="my-4">Editar Contatos de PR</h2>

    <form action="../forms/update_contatos_pr.php" method="POST">
        <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
        
        <div class="form-group form-floating mt-2">
            <select class="form-select" id="empresa_id" name="empresa_id" required>
                <?php
                $sql_empresas = "SELECT id, empresa FROM empresas ORDER BY empresa ASC";
                $result_empresas = $conn->query($sql_empresas);
                while ($empresa = $result_empresas->fetch_assoc()) {
                    $selected = ($empresa['id'] == $row['empresa_id']) ? 'selected' : '';
                    echo "<option value='{$empresa['id']}' {$selected}>{$empresa['empresa']}</option>";
                }
                ?>
            </select>
            <label for="empresa_id">Empresa:</label>
        </div>
        
        <div class="form-group form-floating mt-2">
            <select class="form-control" id="semana" name="semana" required>
                <option value="" disabled <?php echo empty($row['semana']) ? 'selected' : ''; ?>>Selecione a semana</option>
                <option value="01" <?php echo ($row['semana'] == '01') ? 'selected' : ''; ?>>Sem 1</option>
                <option value="02" <?php echo ($row['semana'] == '02') ? 'selected' : ''; ?>>Sem 2</option>
                <option value="03" <?php echo ($row['semana'] == '03') ? 'selected' : ''; ?>>Sem 3</option>
                <option value="04" <?php echo ($row['semana'] == '04') ? 'selected' : ''; ?>>Sem 4</option>
                <option value="05" <?php echo ($row['semana'] == '05') ? 'selected' : ''; ?>>Sem 5</option>
            </select>
            <label for="semana">Semana</label>
        </div>
        
        <div class="form-group form-floating mt-2">
            <input type="month" class="form-control" id="mesrelatorio" name="mesrelatorio" value="<?php echo htmlspecialchars($row['mesrelatorio']); ?>" required>
            <label for="mesrelatorio">Mês do relatório</label>
        </div>
        
        <div class="form-group form-floating mt-2">
            <select class="form-control" id="classificacao" name="classificacao" required>
                <option value="" disabled <?php echo empty($row['classificacao']) ? 'selected' : ''; ?>>Selecione a classificação</option>
                <option value="Influenciador" <?php echo ($row['classificacao'] == 'Influenciador') ? 'selected' : ''; ?>>Influenciador</option>
                <option value="Colunista" <?php echo ($row['classificacao'] == 'Colunista') ? 'selected' : ''; ?>>Colunista</option>
                <option value="Híbrido" <?php echo ($row['classificacao'] == 'Híbrido') ? 'selected' : ''; ?>>Híbrido</option>
                <option value="Representante de Marketing" <?php echo ($row['classificacao'] == 'rRepresentante de Marketing') ? 'selected' : ''; ?>>Representante de marketing</option>
                <option value="Trade BR" <?php echo ($row['classificacao'] == 'Trade BR') ? 'selected' : ''; ?>>Trade BR</option>
                <option value="Trade EUA" <?php echo ($row['classificacao'] == 'Trade EUA') ? 'selected' : ''; ?>>Trade EUA</option>
            </select>
            <label for="classificacao">Classificação</label>
        </div>
        
        <div class="form-group mt-3">
            <label class="form-label">Tipo Contato</label>
            <div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="tipocontato" id="b2b" value="B2B" <?php echo ($row['tipocontato'] == 'B2B') ? 'checked' : ''; ?> required>
                    <label class="form-check-label" for="b2b">B2B</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="tipocontato" id="b2c" value="B2C" <?php echo ($row['tipocontato'] == 'B2C') ? 'checked' : ''; ?> required>
                    <label class="form-check-label" for="b2c">B2C</label>
                </div>
            </div>
        </div>
        
        <div class="form-group form-floating mt-2">
            <input type="number" class="form-control" id="contatosrealizados" name="contatosrealizados" value="<?php echo htmlspecialchars($row['contatosrealizados']); ?>" required min="0">
            <label for="contatosrealizados">Contatos realizados</label>
        </div>
        
        <button type="submit" class="btn btn-success mt-2">Salvar Alterações</button>
    </form>
</div>

<?php
$conn->close();
include '../pages/footer.php';
?>

