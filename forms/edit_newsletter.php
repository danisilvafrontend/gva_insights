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
            <a href="https://insights.gvacompany.com/views_bd/views_newsletter.php">Voltar</a>
        </div>
    </div>
</div>

<div class="container">
    <h2 class="my-4">Editar Newsletter</h2>
    <?php
    include '../includes/db_connect.php';

    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);

        $sql = "SELECT * FROM bm_newsletter WHERE id = $id";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();

            $sql_clientes = "SELECT id_cliente FROM newsletter_clientes WHERE id_newsletter = $id";
            $result_clientes = $conn->query($sql_clientes);
            $clientes_selecionados = [];
            while ($cliente = $result_clientes->fetch_assoc()) {
                $clientes_selecionados[] = $cliente['id_cliente'];
            }
            ?>
            <form action="../forms/update_newsletter.php" method="POST">
                <input type="hidden" name="id" value="<?php echo $row['id']; ?>">

                <div class="form-group form-floating mt-2">
                    <input type="text" class="form-control" id="nome_newsletter" name="nome_newsletter" value="<?php echo $row['nome_newsletter']; ?>" required>
                    <label for="nome_newsletter">Nome da Newsletter:</label>
                </div>

                <div class="form-group mt-2">
                    <label>Temas:</label>
                    <div class="row">
                        <?php
                        // Buscar temas vinculados à newsletter
                        $sql_temas_selecionados = "SELECT id_tema FROM newsletter_temas WHERE id_newsletter = $id";
                        $result_temas_sel = $conn->query($sql_temas_selecionados);
                        $temas_selecionados = [];
                        while ($tema_sel = $result_temas_sel->fetch_assoc()) {
                            $temas_selecionados[] = $tema_sel['id_tema'];
                        }

                        // Listar todos os temas
                        $sql_temas = "SELECT id, tema FROM temas ORDER BY tema ASC";
                        $result_temas = $conn->query($sql_temas);
                        if ($result_temas->num_rows > 0) {
                            while ($tema = $result_temas->fetch_assoc()) {
                                $checked = in_array($tema['id'], $temas_selecionados) ? 'checked' : '';
                                echo "<div class='col-md-4 mb-1'>
                                        <div class='form-check'>
                                            <input class='form-check-input' type='checkbox' name='temas[]' value='{$tema['id']}' id='tema{$tema['id']}' $checked>
                                            <label class='form-check-label' for='tema{$tema['id']}'>{$tema['tema']}</label>
                                        </div>
                                    </div>";
                            }
                        }
                        ?>
                    </div>
                </div>

                <div class="form-group form-floating mt-2">
                    <input type="date" class="form-control" id="data_envio" name="data_envio" value="<?php echo $row['data_envio']; ?>" required>
                    <label for="data_envio">Data de Envio:</label>
                </div>

                <div class="form-group form-floating mt-2">
                    <input type="number" class="form-control" id="emails_entregues" name="emails_entregues" value="<?php echo $row['emails_entregues']; ?>" required>
                    <label for="emails_entregues">E-mails Entregues:</label>
                </div>

                <div class="form-group form-floating mt-2">
                    <input type="number" class="form-control" id="aberturas_unicas" name="aberturas_unicas" value="<?php echo $row['aberturas_unicas']; ?>" required>
                    <label for="aberturas_unicas">Aberturas Únicas:</label>
                </div>

                <div class="form-group form-floating mt-2">
                    <input type="number" class="form-control" id="cliques_unicos" name="cliques_unicos" value="<?php echo $row['cliques_unicos']; ?>" required>
                    <label for="cliques_unicos">Cliques Únicos:</label>
                </div>

                <div class="form-group form-floating mt-2">
                    <input type="number" class="form-control" id="cancelamento" name="cancelamento" value="<?php echo $row['cancelamento']; ?>" required>
                    <label for="cancelamento">Cancelamento:</label>
                </div>

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
                    <label for="empresa_id">Empresa</label>
                </div>

                <div class="form-group form-floating mt-2">
                    <label>Clientes:</label><br>
                    <?php
                    $sql_clientes_lista = "SELECT id, company FROM clientes ORDER BY company ASC";
                    $result_clientes_lista = $conn->query($sql_clientes_lista);

                    if ($result_clientes_lista->num_rows > 0) {
                        while ($cliente = $result_clientes_lista->fetch_assoc()) {
                            $checked = in_array($cliente['id'], $clientes_selecionados) ? 'checked' : '';
                            echo "<div class='form-check'>
                                    <input class='form-check-input' type='checkbox' name='clientes[]' value='{$cliente['id']}' id='cliente{$cliente['id']}' $checked>
                                    <label class='form-check-label' for='cliente{$cliente['id']}'>{$cliente['company']}</label>
                                  </div>";
                        }
                    }
                    ?>
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