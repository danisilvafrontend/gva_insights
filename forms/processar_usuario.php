<?php
require_once '../includes/auth.php';
require_admin(); // somente admin pode cadastrar usuários

include '../includes/config.php';
include '../includes/db_connect.php';
mysqli_set_charset($conn, "utf8mb4");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nome         = trim($_POST['nome']  ?? '');
    $email        = trim($_POST['email'] ?? '');
    $senha        = $_POST['senha']      ?? '';
    $nivel_acesso = (int)($_POST['nivel_acesso'] ?? 2);

    // Garante que nivel_acesso seja 1 (Admin) ou 2 (Operacional)
    if (!in_array($nivel_acesso, [1, 2], true)) {
        $nivel_acesso = 2;
    }

    if (empty($nome) || empty($email) || empty($senha)) {
        echo "<script>alert('Preencha todos os campos obrigatórios.'); window.history.back();</script>";
        exit;
    }

    // Criptografa a senha
    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

    // Verifica se o e-mail já existe
    $stmtCheck = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmtCheck->bind_param("s", $email);
    $stmtCheck->execute();
    $stmtCheck->store_result();

    if ($stmtCheck->num_rows > 0) {
        echo "<script>alert('E-mail já cadastrado!'); window.location.href='../pages/cadastro_usuario.php';</script>";
        $stmtCheck->close();
        $conn->close();
        exit;
    }
    $stmtCheck->close();

    // Insere novo usuário
    $stmt = $conn->prepare("INSERT INTO usuarios (nome, email, senha, nivel_acesso) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sssi", $nome, $email, $senha_hash, $nivel_acesso);

    if ($stmt->execute()) {
        $label = ($nivel_acesso === 1) ? 'Admin' : 'Operacional';
        echo "<script>alert('Usuário cadastrado com sucesso! Nível: {$label}'); window.location.href='../pages/cadastro_usuario.php';</script>";
    } else {
        echo "<script>alert('Erro ao cadastrar: {$stmt->error}'); window.location.href='../pages/cadastro_usuario.php';</script>";
    }

    $stmt->close();
}

$conn->close();
