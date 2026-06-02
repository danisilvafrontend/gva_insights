<?php
require_once '../includes/auth.php';
require_nivel(1); // somente admin pode cadastrar usuários

include '../includes/config.php';
include '../includes/db_connect.php';
mysqli_set_charset($conn, "utf8mb4");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nome         = trim($_POST['nome'] ?? '');
    $email        = trim($_POST['email'] ?? '');
    $senha        = $_POST['senha'] ?? '';
    $nivel_acesso = (int)($_POST['nivel_acesso'] ?? 3);

    // Garante que nivel_acesso seja um valor válido (1, 2 ou 3)
    if (!in_array($nivel_acesso, [1, 2, 3], true)) {
        $nivel_acesso = 3;
    }

    // Criptografa a senha com algoritmo seguro
    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

    // Verifica se o e-mail já existe
    $sql  = "SELECT id FROM usuarios WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        echo "<script>alert('E-mail já cadastrado!'); window.location.href='../pages/cadastro_usuario.php';</script>";
        $stmt->close();
        $conn->close();
        exit;
    }

    $stmt->close();

    // Insere novo usuário com nivel_acesso
    $sql  = "INSERT INTO usuarios (nome, email, senha, nivel_acesso) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssi", $nome, $email, $senha_hash, $nivel_acesso);

    if ($stmt->execute()) {
        echo "<script>alert('Usuário cadastrado com sucesso!'); window.location.href='../pages/cadastro_usuario.php';</script>";
    } else {
        echo "<script>alert('Erro ao cadastrar: {$stmt->error}'); window.location.href='../pages/cadastro_usuario.php';</script>";
    }

    $stmt->close();
}

$conn->close();
