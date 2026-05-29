<div class="container">
        <form action="../forms/processar_news_report.php" method="post">
            <div class="form-group form-floating mt-2">
                <select class="form-control" id="empresa_id" name="empresa_id" required>
                    <?php
                    include '../includes/config.php';
                    include '../includes/db_connect.php';
                    mysqli_set_charset($conn, "utf8mb4");

                    // Obter lista de empresas
                    $sql = "SELECT id, company FROM clientes";
                    $result = $conn->query($sql);

                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<option value='" . $row['id'] . "'>" . $row['company'] . "</option>";
                        }
                    }

                    $conn->close();
                    ?>
                </select>
                <label for="empresa_id">Cliente:</label>
            </div>

            <div class="form-group form-floating mt-2">
                <input type="date" class="form-control" id="data_envio" name="data_envio">
                <label for="data_envio">Data de Envio:</label> 
            </div>

            <div class="form-group form-floating mt-2">
                <input type="number" class="form-control" id="contatos" name="contatos">
                <label for="contatos">Contatos:</label> 
            </div>

            <div class="form-group form-floating mt-2">
                <input type="number" class="form-control" id="aberturas" name="aberturas">
                <label for="aberturas">Aberturas:</label>
            </div>

            <div class="form-group form-floating mt-2">
                <input type="number" class="form-control" id="cliques" name="cliques">
                <label for="cliques">Cliques:</label>
            </div>

            <button type="submit" class="btn btn-success mt-2">Cadastrar</button>
        </form>
    </div>