<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include '../pages/header.php';
?>
<meta charset="UTF-8">
    <div class="container">
        <div class="row">
            <div class="col-6">
                <a href="https://insights.gvacompany.com/views_bd/views_clientes.php">Voltar</a>
            </div>
        </div>
    </div>

    <div class="container">
        <h2 class="my-4">Editar Cliente</h2>
        <?php
        include '../includes/db_connect.php'; // Inclui o arquivo de conexão ao banco de dados

        // Verifique se o ID foi passado como parâmetro
        if (isset($_GET['id'])) {
            $id = $_GET['id'];

            // Consulta SQL para buscar o registro específico pelo ID
            $sql = "SELECT * FROM clientes WHERE id=$id";
            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                ?>
                <form action="../forms/update_clientes.php" method="POST">
                    <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                    <div class="form-group form-floating mt-2">
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo $row['name']; ?>" required>
                        <label for="name">Nome:</label>
                    </div>
                    <div class="form-group form-floating mt-2">
                        <input type="text" class="form-control" id="company" name="company" value="<?php echo $row['company']; ?>" required>
                        <label for="company">Empresa:</label>
                    </div>
                    <button type="submit" class="btn btn-success mt-2">Salvar</button>
                </form>
                <?php
            } else {
                echo "<div class='alert alert-info'>Registro não encontrado.</div>";
            }
        } else {
            echo "<div class='alert alert-info'>Nenhum ID fornecido</div>";
        }

        $conn->close();
        ?>
    </div>

<?php include '../pages/footer.php'; ?>
