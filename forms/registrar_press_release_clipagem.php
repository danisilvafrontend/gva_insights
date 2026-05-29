<div class="container">
    <form action="../forms/processar_press_release_clipagem.php" method="post">

        <!-- Cliente -->
        <div class="form-group form-floating mt-2">
            <select class="form-control" id="cliente_id" name="cliente_id" required>
                <?php
                include '../includes/config.php';
                include '../includes/db_connect.php';
                mysqli_set_charset($conn, "utf8mb4");
                $sql_clientes = "SELECT id, company FROM clientes ORDER BY company ASC";
                $result_clientes = $conn->query($sql_clientes);
                while ($row = $result_clientes->fetch_assoc()) {
                    echo "<option value='{$row['id']}'>{$row['company']}</option>";
                }
                ?>
            </select>
            <label for="cliente_id">Cliente:</label>
        </div>
        
        <!-- Mês do Relatório -->
        <div class="form-group form-floating mt-2">
            <input type="month" class="form-control" id="mes_relatorio" name="mes_relatorio" required>
            <label for="mes_relatorio">Mês do Relatório:</label>
        </div>

        <!-- Publicações -->
        <div class="form-group form-floating mt-2">
            <input type="number" class="form-control" id="publicacoes" name="publicacoes" required>
            <label for="publicacoes">Publicações:</label>
        </div>

        <!-- Valor publicidade -->
        <div class="form-group form-floating mt-2">
            <input type="number" step="0.01" class="form-control" id="valor_publicidade" name="valor_publicidade" required>
            <label for="valor_publicidade">Valor Publicidade equivalente (R$):</label>
        </div>

        <!-- Alcance -->
        <div class="form-group form-floating mt-2">
            <input type="number" class="form-control" id="alcance" name="alcance" required>
            <label for="alcance">Alcance:</label>
        </div>

        <!-- Empresa -->
        <div class="form-group form-floating mt-2">
            <select class="form-select" id="empresa_id" name="empresa_id" required>
                <?php
                $sql_empresas = "SELECT id, empresa FROM empresas ORDER BY empresa ASC";
                $result_empresas = $conn->query($sql_empresas);
                while ($empresa = $result_empresas->fetch_assoc()) {
                    echo "<option value='{$empresa['id']}'>{$empresa['empresa']}</option>";
                }
                ?>
            </select>
            <label for="empresa_id">Empresa:</label>
        </div>


        <button type="submit" class="btn btn-success mt-2">Cadastrar</button>
    </form>
</div>