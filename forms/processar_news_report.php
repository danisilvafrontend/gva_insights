<?php
include '../includes/config.php';
include '../includes/db_connect.php';
    mysqli_set_charset($conn, "utf8mb4");

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $empresa_id = $_POST['empresa_id'];
    $data_envio = $_POST['data_envio'];
    $contatos = $_POST['contatos'];
    $aberturas = $_POST['aberturas'];
    $cliques = $_POST['cliques'];

    // Tentar converter a data do formato DD/MM/YYYY para YYYY-MM-DD 
    $date = DateTime::createFromFormat('d/m/Y', $data_envio); 
    if ($date === false) { 
        // Se a conversão falhar, tente o formato YYYY-MM-DD 
        $date = DateTime::createFromFormat('Y-m-d', $data_envio); 
    } 
    if ($date !== false) { 
        $data_envio = $date->format('Y-m-d'); 
    } else { 
        // Tratar erro na conversão da data 
        echo "<script>alert('Erro ao formatar a data. Verifique o formato DD/MM/YYYY ou YYYY-MM-DD.'); window.location.href='../pages/report_clientes.php';</script>"; 
        exit; 
    }

    $sql = "INSERT INTO report_newsletter (empresa_id, data_envio, contatos, aberturas, cliques) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);

    $stmt->bind_param("isiii", $empresa_id, $data_envio, $contatos, $aberturas, $cliques);

    if ($stmt->execute()) {
        echo "<script>alert('Relatório de newsletter cadastrado com sucesso!'); window.location.href='../pages/report_clientes.php';</script>";
    } else {
        echo "<script>alert('Erro ao cadastrar relatório de newsletter: " . $stmt->error . "'); window.location.href='../pages/report_clientes.php';</script>";
    }

    $stmt->close();
}
$conn->close();
?>
