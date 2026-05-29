<div class="container">
    <h4 class="text-left">Registrar Anúncios</h4>
    
    <?php
    include '../includes/db_connect.php';
    mysqli_set_charset($conn, "utf8mb4");
    
    $sql_clientes = "SELECT id, company FROM clientes ORDER BY company ASC";
    $result_clientes = $conn->query($sql_clientes);
    
    $sql_empresas = "SELECT id, empresa FROM empresas ORDER BY empresa ASC";
    $result_empresas = $conn->query($sql_empresas);
    ?>

    <form action="../forms/processar_anuncios.php" method="POST">
        
        <!-- Nome Anúncio -->
        <div class="form-group form-floating mt-2">
            <input type="text" class="form-control" id="nome_anuncio" name="nome_anuncio" required>
            <label for="nome_anuncio">Nome do Anúncio:</label>
        </div>

        <!-- Plataforma -->
        <div class="form-group form-floating mt-2">
            <select class="form-select" id="plataforma" name="plataforma" required>
                <option value="" disabled selected>Selecione a plataforma</option>
                <option value="Facebook">Facebook</option>
                <option value="Instagram">Instagram</option>
                <option value="LinkedIn">LinkedIn</option>
            </select>
            <label for="plataforma">Plataforma:</label>
        </div>

        <!-- Cliente -->
        <div class="form-group form-floating mt-2">
            <select class="form-select" id="cliente_id" name="cliente_id" required>
                <option value="" disabled selected>Selecione o cliente</option>
                <?php
                if ($result_clientes->num_rows > 0) {
                    while ($row = $result_clientes->fetch_assoc()) {
                        echo "<option value=\"" . htmlspecialchars($row['id']) . "\">" . htmlspecialchars($row['company']) . "</option>";
                    }
                }
                ?>
            </select>
            <label for="cliente_id">Cliente:</label>
        </div>

        <!-- Empresa -->
        <div class="form-group form-floating mt-2">
            <select class="form-select" id="empresa_id" name="empresa_id" required>
                <option value="" disabled selected>Selecione a empresa</option>
                <?php
                if ($result_empresas->num_rows > 0) {
                    while ($row = $result_empresas->fetch_assoc()) {
                        echo "<option value=\"" . htmlspecialchars($row['id']) . "\">" . htmlspecialchars($row['empresa']) . "</option>";
                    }
                }
                ?>
            </select>
            <label for="empresa_id">Empresa:</label>
        </div>

        <div class="row">
            <!-- Início Anúncio -->
            <div class="col-md-6">
                <div class="form-group form-floating mt-2">
                    <input type="date" class="form-control" id="inicio_anuncio" name="inicio_anuncio" required>
                    <label for="inicio_anuncio">Início do Anúncio:</label>
                </div>
            </div>

            <!-- Término Anúncio -->
            <div class="col-md-6">
                <div class="form-group form-floating mt-2">
                    <input type="date" class="form-control" id="termino_anuncio" name="termino_anuncio" required>
                    <label for="termino_anuncio">Término do Anúncio:</label>
                </div>
            </div>
        </div>

        <!-- Objetivo -->
        <div class="form-group form-floating mt-2">
            <select class="form-select" id="objetivo" name="objetivo" required>
                <option value="" disabled selected>Selecione o objetivo</option>
                <option value="Engajamento">Engajamento</option>
                <option value="Alcance">Alcance</option>
                <option value="Tráfego">Tráfego</option>
            </select>
            <label for="objetivo">Objetivo do Anúncio:</label>
        </div>

        <!-- Métricas -->
        <div class="row">
            <div class="col-md-4">
                <div class="form-group form-floating mt-2">
                    <input type="number" class="form-control" id="alcance" name="alcance" min="0" value="0">
                    <label for="alcance">Alcance:</label>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group form-floating mt-2">
                    <input type="number" class="form-control" id="impressoes" name="impressoes" min="0" value="0">
                    <label for="impressoes">Impressões:</label>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group form-floating mt-2">
                    <input type="number" class="form-control" id="cliques_interacoes" name="cliques_interacoes" min="0" value="0">
                    <label for="cliques_interacoes">Cliques/Interações:</label>
                </div>
            </div>
        </div>

        <!-- ✅ CORRETO: type="text" com máscara brasileira -->
        <div class="form-group form-floating mt-2">
            <input type="text" class="form-control" id="valor_gasto" name="valor_gasto" 
                placeholder="0,00" 
                value="<?php echo $row ? number_format($row['valor_gasto'], 2, ',', '.') : ''; ?>" 
                required>
            <label for="valor_gasto">Valor Gasto (R$):</label>
        </div>



        <button type="submit" class="btn btn-success mt-2">Cadastrar</button>
        <a href="../pages/registros_bm.php" class="btn btn-secondary mt-2">Cancelar</a>
    </form>
</div>

<?php $conn->close(); ?>
