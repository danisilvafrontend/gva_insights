<?php include '../includes/config.php'; ?>
<?php include '../includes/db_connect.php';
mysqli_set_charset($conn, "utf8mb4"); 
?>
<meta charset="UTF-8">

<div class="container">
    <h4 class="text-left">Registrar Contatos de PR</h4>

    <form action="../forms/processar_contatos_pr.php" method="POST">
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
        <div class="form-group form-floating mt-2">
            <select id="semana"  class="form-control" name="semana" required>
                <option value="" disabled selected>Selecione a semana</option>
                <option value="01">Sem 1</option>
                <option value="02">Sem 2</option>
                <option value="03">Sem 3</option>
                <option value="04">Sem 4</option>
                <option value="05">Sem 5</option>
            </select>
            <label for="semana">Semana</label>
        </div>
        <div class="form-group form-floating mt-2">
            <input type="month" class="form-control" id="mesrelatorio" name="mesrelatorio" required>
            <label for="mesrelatorio">Mês do relatório</label>
        </div>
        <div class="form-group form-floating mt-2">
            <select class="form-control" id="classificacao" name="classificacao" required>
                <option value="" disabled selected>Selecione a classificação</option>
                <option value="Influenciador">Influenciador</option>
                <option value="Colunista">Colunista</option>
                <option value="Híbrido">Híbrido</option>
                <option value="Representante de Marketing">Representante de marketing</option>
                <option value="Trade BR">Trade BR</option>
                <option value="Trade EUA">Trade EUA</option>
            </select>
            <label for="classificacao">Classificação</label>
        </div>

        <div class="form-group mt-3">
            <label class="form-label">Tipo Contato</label>
            <div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="tipocontato" id="b2b" value="B2B" required>
                    <label class="form-check-label" for="b2b">B2B</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="tipocontato" id="b2c" value="B2C" required>
                    <label class="form-check-label" for="b2c">B2C</label>
                </div>
            </div>
        </div>

        <div class="form-group form-floating mt-2">
            <input type="number" class="form-control" id="contatosrealizados" name="contatosrealizados" required min="0">
            <label for="contatosrealizados">Contatos realizados</label>
        </div>
        <button type="submit" class="btn btn-success mt-2">Cadastrar</button>
    </form>
</div>

<?php $conn->close(); ?>