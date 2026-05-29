<?php
include '../includes/db_connect.php';
    mysqli_set_charset($conn, "utf8mb4");

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $tema = trim($_POST['tema']);

    if (empty($tema)) {
        echo "<script>alert('Por favor, preencha o campo Tema.'); window.history.back();</script>";
        exit;
    }

    // Verificar se o tema já existe
    $sql_verifica = "SELECT id FROM temas WHERE tema = ?";
    $stmt_verifica = $conn->prepare($sql_verifica);
    $stmt_verifica->bind_param("s", $tema);
    $stmt_verifica->execute();
    $stmt_verifica->store_result();

    if ($stmt_verifica->num_rows > 0) {
        echo "<script>alert('Este tema já está cadastrado.'); window.history.back();</script>";
        $stmt_verifica->close();
        $conn->close();
        exit;
    }
    $stmt_verifica->close();

    // Inserir novo tema
    $sql = "INSERT INTO temas (tema) VALUES (?)";
    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        echo "<script>alert('Erro ao preparar a consulta: " . $conn->error . "'); window.history.back();</script>";
        exit;
    }

    $stmt->bind_param("s", $tema);

    if ($stmt->execute()) {
        echo "<script>alert('Tema cadastrado com sucesso!'); window.location.href='../pages/cadastro_tema.php';</script>";
    } else {
        echo "<script>alert('Erro ao cadastrar tema: " . $stmt->error . "'); window.history.back();</script>";
    }

    $stmt->close();
    $conn->close();
} else {
    echo "<script>alert('Método inválido.'); window.history.back();</script>";
}
?>