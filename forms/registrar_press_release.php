<?php include '../includes/config.php'; ?>
<?php include '../includes/db_connect.php';
mysqli_set_charset($conn, "utf8mb4"); 
?>
<meta charset="UTF-8">

<div class="container">
    <h4>Registro de Press Release</h4>
    <form action="../forms/processar_press_release.php" method="post">

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

        <!-- Data -->
        <div class="form-group form-floating mt-2">
            <input type="date" class="form-control" id="data_envio" name="data_envio" required>
            <label for="data_envio">Data de Envio:</label>
        </div>

        <!-- Métricas -->
        <div class="form-group form-floating mt-2">
            <input type="number" class="form-control" id="contatos" name="contatos" required>
            <label for="contatos">Contatos:</label>
        </div>

        <div class="form-group form-floating mt-2">
            <input type="number" class="form-control" id="aberturas" name="aberturas" required>
            <label for="aberturas">Aberturas:</label>
        </div>

        <div class="form-group form-floating mt-2">
            <input type="number" class="form-control" id="cliques" name="cliques" required>
            <label for="cliques">Cliques:</label>
        </div>

        <div class="form-group form-floating mt-2">
            <input type="text" class="form-control" id="ferramenta" name="ferramentas" required>
            <label for="ferramentas">Ferramentas:</label>
        </div>


        <!-- Clientes -->
        <div class="form-group mt-2">
            <label>Clientes:</label>
            <div class="row">
                <?php
                $sql_clientes = "SELECT id, company FROM clientes ORDER BY company ASC";
                $result_clientes = $conn->query($sql_clientes);
                while ($cliente = $result_clientes->fetch_assoc()) {
                    echo "<div class='col-md-4 mb-1'>
                            <div class='form-check'>
                                <input class='form-check-input' type='checkbox' name='clientes[]' value='{$cliente['id']}' id='cliente{$cliente['id']}'>
                                <label class='form-check-label' for='cliente{$cliente['id']}'>{$cliente['company']}</label>
                            </div>
                          </div>";
                }
                ?>
            </div>
        </div>

        <!-- Temas -->
        <div class="form-group mt-2">
            <label>Temas:</label>
            <div class="row">
                <?php
                $sql_temas = "SELECT id, tema FROM temas ORDER BY tema ASC";
                $result_temas = $conn->query($sql_temas);
                while ($tema = $result_temas->fetch_assoc()) {
                    echo "<div class='col-md-4 mb-1'>
                            <div class='form-check'>
                                <input class='form-check-input' type='checkbox' name='temas[]' value='{$tema['id']}' id='tema{$tema['id']}'>
                                <label class='form-check-label' for='tema{$tema['id']}'>{$tema['tema']}</label>
                            </div>
                          </div>";
                }
                ?>
            </div>
        </div>

        <button type="submit" class="btn btn-success mt-2">Cadastrar</button>
    </form>
</div>

<?php $conn->close(); ?>