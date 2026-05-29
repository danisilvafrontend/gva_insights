<?php 
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

include '../pages/header.php'; 
include '../includes/db_connect.php';
mysqli_set_charset($conn, "utf8mb4");

?>

    <div class="container">
        <div class="row">
            <div class="col-6">
                <a href="https://insights.gvacompany.com/pages/registros_bm.php">Voltar</a>
            </div>
        </div>
    </div> 

    <div class="container">
        <h2 class="my-4">Últimas Lives</h2>
        <?php
        include '../includes/db_connect.php'; // Inclui o arquivo de conexão ao banco de dados

        // Consulta SQL para buscar os 30 últimos registros da tabela bm_canais e o nome da empresa
        $sql = "SELECT bm_canais.*, clientes.company AS empresa_nome
                FROM bm_canais 
                JOIN clientes ON bm_canais.empresa_id = clientes.id 
                ORDER BY bm_canais.id DESC LIMIT 30";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            echo "<table class='table table-striped'>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Canal Parceiro</th>
                            <th>Cliente</th>
                            <th>Ação</th>
                        </tr>
                    </thead>
                    <tbody>";
            // Saída de dados de cada linha
            while ($row = $result->fetch_assoc()) {
                echo "<tr>
                        <td>" . $row["id"] . "</td>
                        <td>" . $row["canal_parceiro"] . "</td>
                        <td>" . $row["empresa_nome"] . "</td>
                        <td><a href='../forms/edit_canais.php?id=" . $row["id"] . "' class='btn btn-primary'>Editar</a></td>
                      </tr>";
            }
            echo "</tbody></table>";
        } else {
            echo "<div class='alert alert-info'>Nenhum registro encontrado.</div>";
        }

        $conn->close();
        ?>

    </div>

    <?php include '../pages/footer.php'; ?>