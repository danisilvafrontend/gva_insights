<meta charset="UTF-8">
<?php include '../includes/db_connect.php';
mysqli_set_charset($conn, "utf8mb4");
?>

<div class="container">
    <h4>Registro de Newsletters</h4>
    <form action="../forms/processar_newsletter.php" method="POST">
        <div class="form-group form-floating mt-2">
            <input type="text" class="form-control" id="nome_newsletter" name="nome_newsletter" required>
            <label for="nome_newsletter">Nome da Newsletter:</label>
        </div>

        <div class="form-group mt-2">
            <label>Temas:</label>
            <div class="row">
                <?php
                $sql_temas = "SELECT id, tema FROM temas ORDER BY tema ASC";
                    $result_temas = $conn->query($sql_temas);

                    if ($result_temas->num_rows > 0) {
                        while ($tema = $result_temas->fetch_assoc()) {
                            echo "<div class='col-md-4 mb-2'>
                                    <div class='form-check'>
                                        <input class='form-check-input' type='checkbox' name='temas[]' value='" . $tema['id'] . "' id='tema" . $tema['id'] . "'>
                                        <label class='form-check-label' for='tema" . $tema['id'] . "'>" . $tema['tema'] . "</label>
                                    </div>
                                </div>";
                        }
                    }
                ?>
            </div>
        </div>

        <div class="form-group form-floating mt-2">
            <input type="date" class="form-control" id="data_envio" name="data_envio" required>
            <label for="data_envio">Data de Envio:</label>
        </div>

        <div class="form-group form-floating mt-2">
            <input type="number" class="form-control" id="emails_entregues" name="emails_entregues" required>
            <label for="emails_entregues">E-mails Entregues:</label>
        </div>

        <div class="form-group form-floating mt-2">
            <input type="number" class="form-control" id="aberturas_unicas" name="aberturas_unicas" required>
            <label for="aberturas_unicas">Aberturas Únicas:</label>
        </div>

        <div class="form-group form-floating mt-2">
            <input type="number" class="form-control" id="cliques_unicos" name="cliques_unicos" required>
            <label for="cliques_unicos">Cliques Únicos:</label>
        </div>

        <div class="form-group form-floating mt-2">
            <input type="number" class="form-control" id="cancelamento" name="cancelamento" required>
            <label for="cancelamento">Cancelamento:</label>
        </div>

        <div class="form-group form-floating mt-2">
            <select class="form-select" id="empresa_id" name="empresa_id" required>
                <?php

                // Obter lista de empresas
                $sql_empresas = "SELECT id, empresa FROM empresas ORDER BY empresa ASC";
                $result_empresas = $conn->query($sql_empresas);

                if ($result_empresas->num_rows > 0) {
                    while ($empresa = $result_empresas->fetch_assoc()) {
                        echo "<option value='" . $empresa['id'] . "'>" . $empresa['empresa'] . "</option>";
                    }
                }
                ?>
            </select>
            <label for="empresa_id">Empresa</label>
        </div>

        <div class="form-group mt-2">
            <label>Clientes:</label>
            <div class="row">
                <?php
                // Obter lista de clientes
                $sql_clientes = "SELECT id, company FROM clientes ORDER BY company ASC";
                $result_clientes = $conn->query($sql_clientes);

                if ($result_clientes->num_rows > 0) {
                    while ($cliente = $result_clientes->fetch_assoc()) {
                        echo "<div class='col-md-4 mb-1'>
                                <div class='form-check'>
                                    <input class='form-check-input' type='checkbox' name='clientes[]' value='" . $cliente['id'] . "' id='cliente" . $cliente['id'] . "'>
                                    <label class='form-check-label' for='cliente" . $cliente['id'] . "'>" . $cliente['company'] . "</label>
                                </div>
                            </div>";
                    }
                }

                $conn->close();
                ?>
            </div>
        </div>

        <button type="submit" class="btn btn-success mt-2">Cadastrar</button>
    </form>
</div>