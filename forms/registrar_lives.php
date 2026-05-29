<div class="container">
    <h4 class=" text-left">Registrar Lives</h4>
    <form action="../forms/processar_lives.php" method="POST">
        <div class="form-group form-floating mt-2">
            <input type="text" class="form-control" id="evento" name="evento" required>
            <label for="evento">Evento:</label>
        </div>
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
            <select class="form-control" id="canal_id" name="canal_id" required>
                <?php
                include '../includes/db_connect.php';

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
            <input type="date" class="form-control" id="data_evento" name="data_evento" required> 
            <label for="data_evento">Data do Evento:</label> 
        </div>
        <div class="form-group form-floating mt-2">
            <input type="number" class="form-control" id="inscritos" name="inscritos" required>
            <label for="inscritos">Número de Inscritos:</label>
        </div>
        <div class="form-group form-floating mt-2">
            <input type="number" class="form-control" id="peak_views" name="peak_views" required>
            <label for="peak_views">Peak Views:</label>
        </div>
        <div class="form-group form-floating mt-2">
            <input type="number" class="form-control" id="visualizacoes" name="visualizacoes" required>
            <label for="visualizacoes">Views Live:</label>
        </div>
        <button type="submit" class="btn btn-success mt-2">Cadastrar</button>
    </form>
</div>
