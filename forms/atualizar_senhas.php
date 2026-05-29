<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include '../includes/config.php';
include '../includes/db_connect.php';

$sql = "SELECT id FROM usuarios WHERE senha IS NULL OR senha = ''";
$result = $conn->query($sql);

$senha_padrao = 'senha123';
$senha_hash = password_hash($senha_padrao, PASSWORD_DEFAULT);

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $id = $row['id'];
        $stmt = $conn->prepare("UPDATE usuarios SET senha = ? WHERE id = ?");
        $stmt->bind_param("si", $senha_hash, $id);
        $stmt->execute();
        $stmt->close();
    }
    echo "Senhas padrão adicionadas para usuários sem senha.";
} else {
    echo "Todos os usuários já possuem senha.";
}

$conn->close();

?>
