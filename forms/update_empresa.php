<?php
include '../includes/db_connect.php';
    mysqli_set_charset($conn, "utf8mb4");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];
    $empresa = $_POST['empresa'];
    $responsavel = $_POST['responsavel'];

    $sql = "UPDATE empresas SET empresa = ?, responsavel = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $empresa, $responsavel, $id);

    if ($stmt->execute()) {
        echo "Empresa atualizada com sucesso!";
    } else {
        echo "Erro ao atualizar empresa: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();

    // Redirecionar para a página de visualização das empresas
    header("Location: https://insights.gvacompany.com/views_bd/views_empresas.php");
    exit();
}
?>
