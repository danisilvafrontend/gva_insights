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

<?php include '../pages/header.php'; ?>
    <div class="container">
        <div class="row">
            <div class="col-6">
                <a href="https://insights.gvacompany.com/pages/report_clientes.php">Voltar</a>
            </div>
        </div>
    </div>
    <div class="container">
        <h2 class="my-4">Últimos Relatórios de Newsletter</h2>
        <?php
        // Consulta SQL para buscar os 100 últimos registros da tabela bm_newsletter
        $sql = "SELECT report_newsletter.*, clientes.company AS empresa_nome
                FROM report_newsletter 
                JOIN clientes ON report_newsletter.empresa_id = clientes.id 
                ORDER BY data_envio DESC LIMIT 100";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            echo "<table class='table table-striped'>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Cliente</th>
                            <th>Data de Envio</th>
                            <th>Contatos</th>
                            <th>Aberturas</th>
                            <th>Cliques</th>
                        </tr>
                    </thead>
                    <tbody>";
            while ($row = $result->fetch_assoc()) {
                // Converter a data para o formato dd/mm/aaaa 
                $data_envio = date("d/m/Y", strtotime($row["data_envio"])); 
                echo "<tr>
                        <td>" . $row["id"] . "</td>
                        <td>" . $row["empresa_nome"] . "</td>
                        <td>" . $data_envio . "</td>
                        <td>" . $row["contatos"] . "</td>
                        <td>" . $row["aberturas"] . "</td>
                        <td>" . $row["cliques"] . "</td>
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
