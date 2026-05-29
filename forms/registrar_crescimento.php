<div class="container">
    <h4 class=" text-left">Registrar Crescimento Redes Sociais</h4>
    
    <?php
    include '../includes/db_connect.php';
    $sql_empresas = "SELECT id, empresa FROM empresas ORDER BY empresa ASC";
    $result_empresas = $conn->query($sql_empresas);
    ?>

    <form action="../forms/processar_crescimento.php" method="POST">
    <div class="form-group form-floating mt-2">
        <select class="form-select" id="empresa_id" name="empresa_id" required aria-label="Floating label select">
            <option value="" disabled selected>Selecione a empresa</option>
            <?php
            if ($result_empresas->num_rows > 0) {
                while ($row = $result_empresas->fetch_assoc()) {
                    echo "<option value=\"" . htmlspecialchars($row['id']) . "\">" . htmlspecialchars($row['empresa']) . "</option>";
                }
            }
            ?>
        </select>
        <label for="empresa">Empresa</label>
    </div>

        <div class="form-group form-floating mt-2">
            <select class="form-control" id="plataforma" name="plataforma" required>
                <option value="" disabled selected>Selecione a plataforma</option>
                <option value="Instagram">Instagram</option>
                <option value="Facebook">Facebook</option>
                <option value="Linkedin">Linkedin</option>
            </select>
            <label for="plataforma">Plataforma:</label>
        </div>

        <div class="form-group form-floating mt-2">
            <select class="form-control" id="semana" name="semana" required>
            <option value="1">01</option>
            <option value="2">02</option>
            <option value="3">03</option>
            <option value="4">04</option>
            <option value="5">05</option>
            </select>
            <label for="semana">Semana:</label>
        </div>

    <div class="form-group form-floating mt-2">
        <input type="number" class="form-control" id="numero_seguidores" name="numero_seguidores" required min="0">
        <label for="numero_seguidores">Número de Seguidores:</label>
    </div>


    <div class="form-group form-floating mt-2">
        <input type="month" class="form-control" id="mes_relatorio" name="mes_relatorio" required>
        <label for="mes_relatorio">Mês Relatório:</label>
    </div>


    <button type="submit" class="btn btn-success mt-2">Cadastrar</button>
    </form>

</div>
