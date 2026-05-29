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

<div class="container mt-4">
    <div class="row mb-3">
        <div class="col-6">
            <a href="../pages/home.php" class="btn btn-secondary">Voltar</a>
        </div>
    </div>

    <h2 class="mb-4">Usuários Cadastrados</h2>

    <?php
    $sql = "SELECT nome, email FROM usuarios ORDER BY nome ASC";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        echo "<table class='table table-striped'>
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Email</th>
                    </tr>
                </thead>
                <tbody>";

        while ($row = $result->fetch_assoc()) {
            echo "<tr>
                    <td>" . htmlspecialchars($row['nome']) . "</td>
                    <td>" . htmlspecialchars($row['email']) . "</td>
                  </tr>";
        }

        echo "</tbody></table>";
    } else {
        echo "<div class='alert alert-info'>Nenhum usuário encontrado.</div>";
    }

    $conn->close();
    ?>
</div>

<?php include '../pages/footer.php'; ?>
