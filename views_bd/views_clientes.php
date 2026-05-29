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
            <a href="https://insights.gvacompany.com/pages/home.php">Voltar</a>
        </div>
    </div>
</div>

<div class="container">
    <div class="row justify-content-center">
        <div class="col col-md-6">
        <?php
        include '../includes/db_connect.php'; // Inclui o arquivo de conexão ao banco de dados

        // Consulta SQL para buscar os 30 últimos registros da tabela clientes
        $sql = "SELECT * FROM clientes ORDER BY id DESC LIMIT 30";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            echo "<table class='table table-striped'>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Empresa</th>
                        <th>Ação</th>
                    </tr>";
            // Saída de dados de cada linha
            while ($row = $result->fetch_assoc()) {
                echo "<tr>
                        <td>" . $row["id"] . "</td>
                        <td>" . $row["name"] . "</td>
                        <td>" . $row["company"] . "</td>
                        <td><a href='../forms/edit_clientes.php?id=" . $row["id"] . "'>Editar</a></td>
                    </tr>";
            }
            echo "</table>";
        } else {
            echo "Nenhum registro encontrado.";
        }

        $conn->close();
        ?>
        </div>
    </div>
</div>

<?php include '../pages/footer.php'; ?>
