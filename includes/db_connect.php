<?php
$servername = "127.0.0.1";
$username = "globalvisionacce_dani";
$password = "D2ewb@0001";
$dbname = "globalvisionacce_gva_database"; // Use o nome do seu banco de dados
$port = 3306;

// Criar a conexão
$conn = new mysqli($servername, $username, $password, $dbname, $port);

// Verificar a conexão
if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}
?>
