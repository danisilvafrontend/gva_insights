<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include '../pages/header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}
?>
<meta charset="UTF-8">
<div class="container">
    <div class="row">
        <div class="col-6">
            <a href="../views_bd/views_anuncios.php">Voltar</a>
        </div>
    </div>
</div>

<div class="container">
    <h2 class="my-4">Editar Anúncio</h2>
    <?php
    include '../includes/db_connect.php';
    mysqli_set_charset($conn, "utf8mb4");

    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $sql = "SELECT * FROM anuncios WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            ?>
            <form action="../forms/update_anuncios.php" method="POST">
                <input type="hidden" name="id" value="<?php echo $row['id']; ?>">

                <!-- Nome Anúncio -->
                <div class="form-group form-floating mt-2">
                    <input type="text" class="form-control" id="nome_anuncio" name="nome_anuncio" 
                           value="<?php echo htmlspecialchars($row['nome_anuncio']); ?>" required>
                    <label for="nome_anuncio">Nome do Anúncio:</label>
                </div>

                <!-- Plataforma -->
                <div class="form-group form-floating mt-2">
                    <select class="form-select" id="plataforma" name="plataforma" required>
                        <option value="Facebook" <?php echo $row['plataforma'] == 'Facebook' ? 'selected' : ''; ?>>Facebook</option>
                        <option value="Instagram" <?php echo $row['plataforma'] == 'Instagram' ? 'selected' : ''; ?>>Instagram</option>
                        <option value="LinkedIn" <?php echo $row['plataforma'] == 'LinkedIn' ? 'selected' : ''; ?>>LinkedIn</option>
                    </select>
                    <label for="plataforma">Plataforma:</label>
                </div>

                <!-- Cliente -->
                <div class="form-group form-floating mt-2">
                    <?php
                    $sql_clientes = "SELECT id, company FROM clientes ORDER BY company ASC";
                    $result_clientes = $conn->query($sql_clientes);
                    ?>
                    <select class="form-select" id="cliente_id" name="cliente_id" required>
                        <?php 
                        while ($cliente = $result_clientes->fetch_assoc()) {
                            $selected = ($cliente['id'] == $row['cliente_id']) ? 'selected' : '';
                            echo "<option value='{$cliente['id']}' $selected>" . htmlspecialchars($cliente['company']) . "</option>";
                        }
                        ?>
                    </select>
                    <label for="cliente_id">Cliente:</label>
                </div>

                <!-- Empresa -->
                <div class="form-group form-floating mt-2">
                    <?php
                    $sql_empresas = "SELECT id, empresa FROM empresas ORDER BY empresa ASC";
                    $result_empresas = $conn->query($sql_empresas);
                    ?>
                    <select class="form-select" id="empresa_id" name="empresa_id" required>
                        <?php 
                        while ($empresa = $result_empresas->fetch_assoc()) {
                            $selected = ($empresa['id'] == $row['empresa_id']) ? 'selected' : '';
                            echo "<option value='{$empresa['id']}' $selected>" . htmlspecialchars($empresa['empresa']) . "</option>";
                        }
                        ?>
                    </select>
                    <label for="empresa_id">Empresa:</label>
                </div>

                <div class="row">
                    <!-- Início Anúncio -->
                    <div class="col-md-6 form-group form-floating mt-2">
                        <input type="date" class="form-control" id="inicio_anuncio" name="inicio_anuncio" 
                               value="<?php echo $row['inicio_anuncio']; ?>" required>
                        <label for="inicio_anuncio">Início do Anúncio:</label>
                    </div>

                    <!-- Término Anúncio -->
                    <div class="col-md-6 form-group form-floating mt-2">
                        <input type="date" class="form-control" id="termino_anuncio" name="termino_anuncio" 
                               value="<?php echo $row['termino_anuncio']; ?>" required>
                        <label for="termino_anuncio">Término do Anúncio:</label>
                    </div>
                </div>

                <!-- Objetivo -->
                <div class="form-group form-floating mt-2">
                    <select class="form-select" id="objetivo" name="objetivo" required>
                        <option value="Engajamento" <?php echo $row['objetivo'] == 'Engajamento' ? 'selected' : ''; ?>>Engajamento</option>
                        <option value="Alcance" <?php echo $row['objetivo'] == 'Alcance' ? 'selected' : ''; ?>>Alcance</option>
                        <option value="Tráfego" <?php echo $row['objetivo'] == 'Tráfego' ? 'selected' : ''; ?>>Tráfego</option>
                    </select>
                    <label for="objetivo">Objetivo do Anúncio:</label>
                </div>

                <!-- Métricas -->
                <div class="row">
                    <div class="col-md-4 form-group form-floating mt-2">
                        <input type="number" class="form-control" id="alcance" name="alcance" 
                               value="<?php echo $row['alcance']; ?>" min="0">
                        <label for="alcance">Alcance:</label>
                    </div>
                    <div class="col-md-4 form-group form-floating mt-2">
                        <input type="number" class="form-control" id="impressoes" name="impressoes" 
                               value="<?php echo $row['impressoes']; ?>" min="0">
                        <label for="impressoes">Impressões:</label>
                    </div>
                    <div class="col-md-4 form-group form-floating mt-2">
                        <input type="number" class="form-control" id="cliques_interacoes" name="cliques_interacoes" 
                               value="<?php echo $row['cliques_interacoes']; ?>" min="0">
                        <label for="cliques_interacoes">Cliques/Interações:</label>
                    </div>
                </div>

                <!-- ✅ VALOR GASTO CORRIGIDO -->
                <div class="form-group form-floating mt-2">
                    <input type="text" class="form-control" id="valor_gasto" name="valor_gasto" 
                           value="<?php echo number_format($row['valor_gasto'], 2, ',', '.'); ?>" 
                           placeholder="0,00" required>
                    <label for="valor_gasto">Valor Gasto (R$):</label>
                </div>

                <button type="submit" class="btn btn-success mt-2">Salvar</button>
                <a href="../views_bd/views_anuncios.php" class="btn btn-secondary mt-2">Cancelar</a>
            </form>
            <?php
        } else {
            echo "<div class='alert alert-info'>Anúncio não encontrado.</div>";
        }
        $stmt->close();
    } else {
        echo "<div class='alert alert-info'>Nenhum ID fornecido.</div>";
    }

    $conn->close();
    ?>
</div>

<?php include '../pages/footer.php'; ?>
