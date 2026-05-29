<?php
include '../includes/config.php';
include '../includes/db_connect.php';
    mysqli_set_charset($conn, "utf8mb4");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $senha = $_POST['senha'];

    // Criptografa a senha com algoritmo seguro
    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

    // Verifica se o email já existe
    $sql = "SELECT id FROM usuarios WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if($stmt->num_rows > 0){
        echo "<script>alert('E-mail já cadastrado!'); window.location.href='../pages/cadastro_usuario.php';</script>";
        $stmt->close();
        $conn->close();
        exit;
    }

    $stmt->close();

    // Insere novo usuário
    $sql = "INSERT INTO usuarios (nome, email, senha) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $nome, $email, $senha_hash);

    if ($stmt->execute()) {
        echo "<script>alert('Usuário cadastrado com sucesso!'); window.location.href='../pages/cadastro_usuario.php';</script>";
    } else {
        echo "<script>alert('Erro ao cadastrar: {$stmt->error}'); window.location.href='../pages/cadastro_usuario.php';</script>";
    }

    $stmt->close();
}
$conn->close();
?>
