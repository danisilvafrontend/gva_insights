<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

include '../pages/header.php';
include '../includes/config.php';
include '../includes/db_connect.php';
?>
<meta charset="UTF-8">

<div class="container">
    <div class="row">
        <div class="col-6">
            <a href="https://insights.gvacompany.com/views_bd/views_press_release.php">Voltar</a>
        </div>
    </div>
</div>

<div class="container">
    <h2 class="my-4">Editar Press Release</h2>
    <?php
    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);

        // Buscar dados principais do press release
        $sql = "SELECT * FROM press_release WHERE id = $id";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            ?>
            <form action="../forms/update_press_release.php" method="POST">
                <input type="hidden" name="id" value="<?php echo $row['id']; ?>">

                <!-- Empresa -->
                <div class="form-group form-floating mt-2">
                    <select class="form-select" id="empresa_id" name="empresa_id" required>
                        <?php
                        $sql_empresas = "SELECT id, empresa FROM empresas ORDER BY empresa ASC";
                        $result_empresas = $conn->query($sql_empresas);
                        while ($empresa = $result_empresas->fetch_assoc()) {
                            $selected = ($empresa['id'] == $row['empresa_id']) ? 'selected' : '';
                            echo "<option value='{$empresa['id']}' $selected>{$empresa['empresa']}</option>";
                        }
                        ?>
                    </select>
                    <label for="empresa_id">Empresa:</label>
                </div>

                <!-- Data -->
                <div class="form-group form-floating mt-2">
                    <input type="date" class="form-control" id="data_envio" name="data_envio"
                        value="<?php echo $row['data_envio']; ?>" required>
                    <label for="data_envio">Data de Envio:</label>
                </div>

                <!-- Contatos -->
                <div class="form-group form-floating mt-2">
                    <input type="number" class="form-control" id="contatos" name="contatos"
                        value="<?php echo $row['contatos']; ?>" required>
                    <label for="contatos">Contatos:</label>
                </div>

                <!-- Aberturas -->
                <div class="form-group form-floating mt-2">
                    <input type="number" class="form-control" id="aberturas" name="aberturas"
                        value="<?php echo $row['aberturas']; ?>" required>
                    <label for="aberturas">Aberturas:</label>
                </div>

                <!-- Cliques -->
                <div class="form-group form-floating mt-2">
                    <input type="number" class="form-control" id="cliques" name="cliques"
                        value="<?php echo $row['cliques']; ?>" required>
                    <label for="cliques">Cliques:</label>
                </div>

                <!-- Cliques -->
                <div class="form-group form-floating mt-2">
                    <input type="text" class="form-control" id="ferramentas" name="ferramentas"
                        value="<?php echo $row['ferramentas']; ?>" required>
                    <label for="ferramentas">ferramentas:</label>
                </div>

                <!-- Clientes -->
                <div class="form-group mt-2">
                    <label>Clientes:</label>
                    <div class="row">
                        <?php
                        $sql_clientes_sel = "SELECT id_cliente FROM press_release_clientes WHERE id_press_release = $id";
                        $result_clientes_sel = $conn->query($sql_clientes_sel);
                        $clientes_selecionados = [];
                        while ($c = $result_clientes_sel->fetch_assoc()) {
                            $clientes_selecionados[] = $c['id_cliente'];
                        }

                        $sql_clientes = "SELECT id, company FROM clientes ORDER BY company ASC";
                        $result_clientes = $conn->query($sql_clientes);
                        while ($cliente = $result_clientes->fetch_assoc()) {
                            $checked = in_array($cliente['id'], $clientes_selecionados) ? 'checked' : '';
                            echo "<div class='col-md-4 mb-1'>
                                    <div class='form-check'>
                                        <input class='form-check-input' type='checkbox' name='clientes[]' value='{$cliente['id']}' id='cliente{$cliente['id']}' $checked>
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
                        $sql_temas_sel = "SELECT id_tema FROM press_release_temas WHERE id_press_release = $id";
                        $result_temas_sel = $conn->query($sql_temas_sel);
                        $temas_selecionados = [];
                        while ($t = $result_temas_sel->fetch_assoc()) {
                            $temas_selecionados[] = $t['id_tema'];
                        }

                        $sql_temas = "SELECT id, tema FROM temas ORDER BY tema ASC";
                        $result_temas = $conn->query($sql_temas);
                        while ($tema = $result_temas->fetch_assoc()) {
                            $checked = in_array($tema['id'], $temas_selecionados) ? 'checked' : '';
                            echo "<div class='col-md-4 mb-1'>
                                    <div class='form-check'>
                                        <input class='form-check-input' type='checkbox' name='temas[]' value='{$tema['id']}' id='tema{$tema['id']}' $checked>
                                        <label class='form-check-label' for='tema{$tema['id']}'>{$tema['tema']}</label>
                                    </div>
                                  </div>";
                        }
                        ?>
                    </div>
                </div>

                <button type="submit" class="btn btn-success mt-2">Salvar</button>
            </form>
            <?php
        } else {
            echo "<div class='alert alert-info'>Registro não encontrado.</div>";
        }
    } else {
        echo "<div class='alert alert-info'>Nenhum ID fornecido.</div>";
    }
    $conn->close();
    ?>
</div>

<?php include '../pages/footer.php'; ?>