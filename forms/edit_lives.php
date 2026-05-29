<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include '../pages/header.php';  // aqui session_start() já é chamado se necessário

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

?>
<meta charset="UTF-8">
    <div class="container">
        <div class="row">
            <div class="col-6">
                <a href="https://insights.gvacompany.com/views_bd/views_lives.php">Voltar</a>
            </div>
        </div>
    </div>

    <div class="container">
        <h2 class="my-4">Editar Registro de Lives</h2>
        <?php
        include '../includes/db_connect.php'; // Inclui o arquivo de conexão ao banco de dados

        // Verifique se o ID foi passado como parâmetro
        if (isset($_GET['id'])) {
            $id = $_GET['id'];

            // Consulta SQL para buscar o registro específico pelo ID
            $sql = "SELECT * FROM bm_lives WHERE id = $id";
            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                ?>
                <form action="../forms/update_lives.php" method="POST">
                    <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                    <div class="form-group form-floating mt-2">
                        <input type="text" class="form-control" id="evento" name="evento" value="<?php echo $row['evento']; ?>" required>
                        <label for="evento">Evento:</label>
                    </div>
                    <div class="form-group form-floating mt-2">
                        <select class="form-control" id="empresa_id" name="empresa_id" required>
                            <?php
                            // Obter lista de empresas
                            $sql_empresas = "SELECT id, company FROM clientes";
                            $result_empresas = $conn->query($sql_empresas);

                            if ($result_empresas->num_rows > 0) {
                                while ($empresa = $result_empresas->fetch_assoc()) {
                                    echo "<option value='" . $empresa['id'] . "' " . ($empresa['id'] == $row['empresa_id'] ? 'selected' : '') . ">" . $empresa['company'] . "</option>";
                                }
                            }
                            ?>
                        </select>
                        <label for="empresa_id">Cliente:</label>
                    </div>
                    <div class="form-group form-floating mt-2">
                        <select class="form-control" id="canal_id" name="canal_id" required>
                            <?php
                            // Obter lista de canais parceiros
                            $sql_canais = "SELECT id, canal_parceiro FROM bm_canais";
                            $result_canais = $conn->query($sql_canais);

                            if ($result_canais->num_rows > 0) {
                                while ($canal = $result_canais->fetch_assoc()) {
                                    echo "<option value='" . $canal['id'] . "' " . ($canal['id'] == $row['canal_id'] ? 'selected' : '') . ">" . $canal['canal_parceiro'] . "</option>";
                                }
                            }
                            ?>
                        </select>
                        <label for="canal_id">Canal Parceiro:</label>
                    </div>
                    <div class="form-group form-floating mt-2">
                        <input type="date" class="form-control" id="data_evento" name="data_evento" value="<?php echo $row['data_evento']; ?>" required>
                        <label for="data_evento">Data do Evento:</label>
                    </div>
                    <div class="form-group form-floating mt-2">
                        <input type="number" class="form-control" id="inscritos" name="inscritos" value="<?php echo $row['inscritos']; ?>" required>
                        <label for="inscritos">Número de Inscritos:</label>
                    </div>
                    <div class="form-group form-floating mt-2">
                        <input type="number" class="form-control" id="peak_views" name="peak_views" value="<?php echo $row['peak_views']; ?>" required>
                        <label for="peak_views">Peak Views:</label>
                    </div>
                    <div class="form-group form-floating mt-2">
                        <input type="number" class="form-control" id="visualizacoes" name="visualizacoes" value="<?php echo $row['visualizacoes']; ?>" required>
                        <label for="visualizacoes">Views Live:</label>
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
