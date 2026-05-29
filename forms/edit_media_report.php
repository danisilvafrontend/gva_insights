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
            <a href="https://insights.gvacompany.com/views_bd/views_media_report.php">Voltar</a>
        </div>
    </div>
</div>

<div class="container">
    <h2 class="my-4">Editar Relatório de Mídia</h2>
    <?php
    include '../includes/db_connect.php';

    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $sql = "SELECT * FROM media_report WHERE id = $id";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            ?>
            <form action="../forms/update_media_report.php" method="POST">
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

                <!-- Plataforma -->
                <div class="form-group form-floating mt-2">
                    <select class="form-control" id="plataforma" name="plataforma" required>
                        <option value="" disabled <?php if (empty($row['plataforma'])) echo 'selected'; ?>>Selecione a plataforma</option>
                        <option value="Instagram" <?php if($row['plataforma'] == 'Instagram') echo 'selected'; ?>>Instagram</option>
                        <option value="Facebook" <?php if($row['plataforma'] == 'Facebook') echo 'selected'; ?>>Facebook</option>
                        <option value="Linkedin" <?php if($row['plataforma'] == 'Linkedin') echo 'selected'; ?>>Linkedin</option>
                    </select>
                    <label for="plataforma">Plataforma:</label>
                </div>

                <!-- Mês do Relatório -->
                <div class="form-group form-floating mt-2">
                    <input type="month" class="form-control" id="mes_relatorio" name="mes_relatorio"
                        value="<?php echo $row['mes_relatorio']; ?>" required>
                    <label for="mes_relatorio">Mês do Relatório:</label>
                </div>

                <!-- Semana -->
                <div class="form-group form-floating mt-2">
                    <select class="form-control" id="semana" name="semana" required>
                        <option value="" disabled>Selecione a semana</option>
                        <?php
                        for ($i = 1; $i <= 5; $i++) {
                            $semana_valor = str_pad($i, 2, '0', STR_PAD_LEFT);
                            $selected = ($row['semana'] == $semana_valor) ? 'selected' : '';
                            echo "<option value='$semana_valor' $selected>Semana $semana_valor</option>";
                        }
                        ?>
                    </select>
                    <label for="semana">Semana:</label>
                </div>


                <!-- Quantidade de Post -->
                <div class="form-group form-floating mt-2">
                    <input type="number" class="form-control" id="quantidade_post" name="quantidade_post"
                           value="<?php echo $row['quantidade_post']; ?>" required>
                    <label for="quantidade_post">Quantidade de Post:</label>
                </div>

                <!-- Impressões -->
                <div class="form-group form-floating mt-2">
                    <input type="number" class="form-control" id="impressoes" name="impressoes"
                           value="<?php echo $row['impressoes']; ?>" required>
                    <label for="impressoes">Impressões:</label>
                </div>

                <!-- Interações -->
                <div class="form-group form-floating mt-2">
                    <input type="number" class="form-control" id="interacoes" name="interacoes"
                           value="<?php echo $row['interacoes']; ?>" required>
                    <label for="interacoes">Interações:</label>
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