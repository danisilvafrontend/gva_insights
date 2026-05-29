<?php
include '../includes/db_connect.php'; // Inclui o arquivo de conexão ao banco de dados
    mysqli_set_charset($conn, "utf8mb4");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $company = $_POST['company'];

    // Consulta SQL para atualizar o registro
    $sql = "UPDATE clientes SET name = ?, company = ? WHERE id = ?";

    // Preparar a declaração
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $name, $company, $id);

    if ($stmt->execute()) {
        echo "Registro atualizado com sucesso!";
    } else {
        echo "Erro ao atualizar o registro: " . $stmt->error;
    }

    // Fechar a declaração e a conexão
    $stmt->close();
    $conn->close();

    // Redirecionar de volta para a página de registros
    header("Location: https://insights.gvacompany.com/views_bd/views_clientes.php");
    exit();
}
?>
