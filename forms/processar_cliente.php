<?php
include '../includes/config.php';
include '../includes/db_connect.php';
    mysqli_set_charset($conn, "utf8mb4");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $company = $_POST['company'];

    // Verificar se a empresa já está cadastrada
    $sql = "SELECT * FROM clientes WHERE company = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $company);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo "<script>alert('Empresa já está registrada.'); window.location.href='../pages/home.php';</script>";
    } else {
        $sql = "INSERT INTO clientes (name, company) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $name, $company);

        if ($stmt->execute()) {
            echo "<script>alert('Cliente cadastrado com sucesso!'); window.location.href='../pages/home.php';</script>";
        } else {
            echo "<script>alert('Erro ao cadastrar cliente.'); window.location.href='../pages/home.php';</script>";
        }
    }

    $stmt->close();
}

$conn->close();
?>
