<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include '../includes/config.php';
include '../includes/db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    $stmt = $conn->prepare("SELECT id, nome, senha, nivel_acesso FROM usuarios WHERE email = ?");

    if ($stmt === false) {
        die('Erro na preparação da consulta: ' . htmlspecialchars($conn->error));
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $usuario = $result->fetch_assoc();

        if (password_verify($senha, $usuario['senha'])) {
            // Login bem-sucedido — popula sessão
            $_SESSION['user_id']      = (int)$usuario['id'];
            $_SESSION['user_nome']    = $usuario['nome'];
            $_SESSION['email']        = $email;
            // Fallback seguro: se não tiver nivel_acesso no banco, assume Operacional (2)
            $_SESSION['nivel_acesso'] = in_array((int)$usuario['nivel_acesso'], [1, 2], true)
                                        ? (int)$usuario['nivel_acesso']
                                        : 2;

            header("Location: ../pages/home.php");
            exit();
        } else {
            echo "<script>alert('Senha incorreta.'); window.location.href='../index.php';</script>";
        }
    } else {
        echo "<script>alert('Usuário não cadastrado.'); window.location.href='../index.php';</script>";
    }

    $stmt->close();
}

$conn->close();
