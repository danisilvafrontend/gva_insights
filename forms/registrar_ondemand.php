    <div class="container">
        <h4>Registro Eventos On-Demand</h4>
        <form action="../forms/processar_ondemand.php" method="POST">
            <div class="form-group form-floating mt-2">
                <select class="form-control" id="evento_id" name="evento_id" required>
                    <?php
                    include '../includes/config.php';
                    include '../includes/db_connect.php';
                    mysqli_set_charset($conn, "utf8mb4");

                    // Obter lista de eventos
                    $sql = "SELECT id, evento FROM bm_lives";
                    $result = $conn->query($sql);

                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<option value='" . $row['id'] . "'>" . $row['evento'] . "</option>";
                        }
                    }
                    ?>
                </select>
                <label for="evento_id">Evento:</label>
            </div>
            <div class="form-group form-floating mt-2">
                <select class="form-control" id="empresa_id" name="empresa_id" required>
                    <?php
                    // Obter lista de empresas
                    $sql = "SELECT id, company FROM clientes";
                    $result = $conn->query($sql);

                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<option value='" . $row['id'] . "'>" . $row['company'] . "</option>";
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
                    $sql = "SELECT id, canal_parceiro FROM bm_canais";
                    $result = $conn->query($sql);

                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<option value='" . $row['id'] . "'>" . $row['canal_parceiro'] . "</option>";
                        }
                    }

                    $conn->close();
                    ?>
                </select>
                <label for="canal_id">Canal Parceiro:</label>
            </div>
            <div class="form-group form-floating mt-2">
                <input type="date" class="form-control" id="data_relatorio" name="data_relatorio" required>
                <label for="data_relatorio">Data do relatório:</label>
            </div>
            <div class="form-group form-floating mt-2">
                <input type="number" class="form-control" id="visualizacoes" name="visualizacoes" required>
                <label for="visualizacoes">Visualizações On Demand:</label>
            </div>
            <button type="submit" class="btn btn-success mt-2">Cadastrar</button>
        </form>
    </div>
