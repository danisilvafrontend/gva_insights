
    <div class="container pt-3">

        <form action="../forms/processar_canal.php" method="POST">

            <div class="form-group form-floating mt-2">
                <input type="text" class="form-control" id="canal_parceiro" name="canal_parceiro" required>
                <label for="canal_parceiro">Canal Parceiro:</label>
            </div>

            <div class="form-group form-floating mt-2">
                <select class="form-control form-select" id="empresa_id" name="empresa_id"  required >
                    <?php
                    include '../includes/config.php';
                    include '../includes/db_connect.php';

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

            <button type="submit" class="btn btn-success mt-2">Cadastrar</button>

        </form>
    </div>
