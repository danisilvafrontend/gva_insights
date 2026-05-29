<?php
include '../includes/db_connect.php';
    mysqli_set_charset($conn, "utf8mb4");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $empresa = trim($_POST['empresa']);
    $responsavel = trim($_POST['responsavel']);

    if (empty($empresa) || empty($responsavel)) {
        echo "<script>alert('Preencha todos os campos!'); window.history.back();</script>";
        exit;
    }

    $sql = "INSERT INTO empresas (empresa, responsavel) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $empresa, $responsavel);

    if ($stmt->execute()) {
        echo "<script>alert('Empresa cadastrada com sucesso!'); window.location.href='../pages/cadastro_empresa.php';</script>";
    } else {
        echo "<script>alert('Erro ao cadastrar empresa: " . $stmt->error . "'); window.history.back();</script>";
    }
    $stmt->close();
    $conn->close();
}
?>
