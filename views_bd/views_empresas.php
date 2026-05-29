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
            <a href="cadastro_empresa.php" class="btn btn-primary ms-2">Cadastrar Nova Empresa</a>
        </div>
    </div>
</div>

<div class="container mt-4">
    <?php
    $sql = "SELECT * FROM empresas ORDER BY empresa ASC";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        echo "<table class='table table-striped'>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Empresa</th>
                        <th>Responsável</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>
                    <td>" . $row['id'] . "</td>
                    <td>" . htmlspecialchars($row['empresa']) . "</td>
                    <td>" . htmlspecialchars($row['responsavel']) . "</td>
                    <td>
                        <a href='../forms/edit_empresa.php?id=" . $row['id'] . "' class='btn btn-sm btn-primary'>Editar</a>
                    </td>
                </tr>";
        }
        echo "</tbody></table>";
    } else {
        echo "<div class='alert alert-info'>Nenhuma empresa cadastrada.</div>";
    }

    $conn->close();
    ?>
</div>

<?php include '../pages/footer.php'; ?>
