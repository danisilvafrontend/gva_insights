<div class="container">
    <h4 class=" text-left">Registrar Publicações Redes Sociais</h4>
    <form action="../forms/processar_media_report.php" method="post">
        <!-- Cliente -->
        <div class="form-group form-floating mt-2">
            <select class="form-control" id="cliente_id" name="cliente_id" required>
                <?php
                include '../includes/config.php';
                include '../includes/db_connect.php';
                mysqli_set_charset($conn, "utf8mb4");
                $sql = "SELECT id, company FROM clientes ORDER BY company ASC";
                $result = $conn->query($sql);

                while ($row = $result->fetch_assoc()) {
                    echo "<option value='" . $row['id'] . "'>" . $row['company'] . "</option>";
                }
                ?>
            </select>
            <label for="cliente_id">Cliente:</label>
        </div>

        <!-- Plataforma -->
        <div class="form-group form-floating mt-2">
            <select class="form-control" id="plataforma" name="plataforma" required>
                <option value="" disabled selected>Selecione a plataforma</option>
                <option value="Instagram">Instagram</option>
                <option value="Facebook">Facebook</option>
                <option value="Linkedin">Linkedin</option>
            </select>
            <label for="plataforma">Plataforma:</label>
        </div>

        <!-- Mês de Envio -->
        <div class="form-group form-floating mt-2">
            <input type="month" class="form-control" id="mes_relatorio" name="mes_relatorio" required>
            <label for="mes_relatorio">Mês Relatório:</label>
        </div>

        <!-- Semana -->
        <div class="form-group form-floating mt-2">
            <select class="form-control" id="semana" name="semana" required>
                <option value="" disabled selected>Selecione a semana</option>
                <option value="01">01</option>
                <option value="02">02</option>
                <option value="03">03</option>
                <option value="04">04</option>
                <option value="05">05</option>
            </select>
            <label for="semana">Semana:</label>
        </div>


        <!-- Métricas -->
        <div class="form-group form-floating mt-2">
            <input type="number" class="form-control" id="quantidade_post" name="quantidade_post">
            <label for="quantidade_post">Quantidade de Post:</label>
        </div>

        <div class="form-group form-floating mt-2">
            <input type="number" class="form-control" id="impressoes" name="impressoes">
            <label for="impressoes">Impressões:</label>
        </div>

        <div class="form-group form-floating mt-2">
            <input type="number" class="form-control" id="interacoes" name="interacoes">
            <label for="interacoes">Interações:</label>
        </div>

        <!-- Empresa -->
        <div class="form-group form-floating mt-2">
            <select class="form-select" id="empresa_id" name="empresa_id" required>
                <?php
                $sql_empresas = "SELECT id, empresa FROM empresas ORDER BY empresa ASC";
                $result_empresas = $conn->query($sql_empresas);

                while ($empresa = $result_empresas->fetch_assoc()) {
                    echo "<option value='" . $empresa['id'] . "'>" . $empresa['empresa'] . "</option>";
                }

                $conn->close();
                ?>
            </select>
            <label for="empresa_id">Empresa:</label>
        </div>

        <button type="submit" class="btn btn-success mt-2">Cadastrar</button>
    </form>
</div>
