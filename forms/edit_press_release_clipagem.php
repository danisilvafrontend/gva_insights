<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include '../pages/header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}
?>
<meta charset="UTF-8">
<div class="container">
    <div class="row">
        <div class="col-6">
            <a href="https://insights.gvacompany.com/views_bd/views_press_release_clipagem.php">Voltar</a>
        </div>
    </div>
</div>

<div class="container">
    <h2 class="my-4">Editar Clipagem</h2>
    <?php
    include '../includes/db_connect.php';

    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $sql = "SELECT * FROM press_release_clipagem WHERE id = $id";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            ?>
            <form action="../forms/update_press_release_clipagem.php" method="POST">
                <input type="hidden" name="id" value="<?php echo $row['id']; ?>">

                <!-- Cliente -->
                <div class="form-group form-floating mt-2">
                    <select class="form-control" id="cliente_id" name="cliente_id" required>
                        <?php
                        $sql_clientes = "SELECT id, company FROM clientes ORDER BY company ASC";
                        $result_clientes = $conn->query($sql_clientes);
                        while ($cliente = $result_clientes->fetch_assoc()) {
                            $selected = ($cliente['id'] == $row['cliente_id']) ? 'selected' : '';
                            echo "<option value='{$cliente['id']}' $selected>{$cliente['company']}</option>";
                        }
                        ?>
                    </select>
                    <label for="cliente_id">Cliente:</label>
                </div>

                <!-- Mês do Relatório -->
                <div class="form-group form-floating mt-2">
                    <input type="month" class="form-control" id="mes_relatorio" name="mes_relatorio"
                        value="<?php echo date('Y-m', strtotime($row['mes_relatorio'])); ?>" required>
                    <label for="mes_relatorio">Mês do Relatório:</label>
                </div>

                <!-- Publicações -->
                <div class="form-group form-floating mt-2">
                    <input type="number" class="form-control" id="publicacoes" name="publicacoes"
                           value="<?php echo $row['publicacoes']; ?>" required>
                    <label for="publicacoes">Publicações:</label>
                </div>

                <!-- Valor publicidade -->
                <div class="form-group form-floating mt-2">
                    <input type="number" step="0.01" class="form-control" id="valor_publicidade" name="valor_publicidade"
                           value="<?php echo $row['valor_publicidade']; ?>" required>
                    <label for="valor_publicidade">Valor Publicidade equivalente (R$):</label>
                </div>

                <!-- Alcance -->
                <div class="form-group form-floating mt-2">
                    <input type="number" class="form-control" id="alcance" name="alcance"
                           value="<?php echo $row['alcance']; ?>" required>
                    <label for="alcance">Alcance:</label>
                </div>

                <!-- Empresa -->
                <div class="form-group form-floating mt-2">
                    <select class="form-select" id="empresa_id" name="empresa_id" required>
                        <?php
                        $sql_empresas = "SELECT id, empresa FROM empresas ORDER BY empresa ASC";
                        $result_empresas = $conn->query($sql_empresas);
                        while ($empresa = $result_empresas->fetch_assoc()) {
                            $selected = ($empresa['id'] == $row['empresa_id']) ? 'selected' : '';
                            echo "<option value='{$empresa['id']}' $selected>{$empresa['empresa']}</option>";
                        }
                        ?>
                    </select>
                    <label for="empresa_id">Empresa:</label>
                </div>


                <button type="submit" class="btn btn-success mt-2">Salvar</button>
            </form>
            <?php
        } else {
            echo "<div class='alert alert-info'>Registro não encontrado.</div>";
        }
    } else {
        echo "<div class='alert alert-info'>Nenhum ID fornecido.</div>";
    }

    $conn->close();
    ?>
</div>

<?php include '../pages/footer.php'; ?>