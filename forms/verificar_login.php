<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include '../includes/config.php';
include '../includes/db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $senha = $_POST['senha'];

    $sql = "SELECT id, senha FROM usuarios WHERE email = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        die('Erro na preparação da consulta: ' . htmlspecialchars($conn->error));
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $usuario = $result->fetch_assoc();

        if (password_verify($senha, $usuario['senha'])) {
            // Login bem-sucedido
            $_SESSION['user_id'] = $usuario['id'];
            $_SESSION['email'] = $email;

            header("Location: ../pages/home.php");
            exit();
        } else {
            echo "<script>alert('Senha incorreta.'); window.location.href='../forms/login.php';</script>";
        }
    } else {
        echo "<script>alert('Usuário não cadastrado.'); window.location.href='../forms/login.php';</script>";
    }

    $stmt->close();
}

$conn->close();
?>
