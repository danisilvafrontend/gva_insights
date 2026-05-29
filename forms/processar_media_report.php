<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include '../includes/config.php';
include '../includes/db_connect.php';
    mysqli_set_charset($conn, "utf8mb4");

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $cliente_id = $_POST['cliente_id'];
    $empresa_id = $_POST['empresa_id'];
    $mes_relatorio = $_POST['mes_relatorio']; // novo campo (ex: 2025-11)
    $semana = $_POST['semana'];               // novo campo (ex: 01, 02, etc.)
    $impressoes = $_POST['impressoes'];
    $interacoes = $_POST['interacoes'];
    $quantidade_post = $_POST['quantidade_post'];
    $plataforma = $_POST['plataforma'];

    // Obter data atual para registro
    $data_registro = date('Y-m-d');

    // Inserir dados no banco
    $sql = "INSERT INTO media_report (
                cliente_id, empresa_id, mes_relatorio, semana,
                impressoes, interacoes, quantidade_post, plataforma, data_registro
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        echo "<script>alert('Erro ao preparar a consulta de inserção: " . $conn->error . "'); window.location.href='../pages/registros_bm.php';</script>";
        exit;
    }

    $stmt->bind_param("iissiiiss",
        $cliente_id,
        $empresa_id,
        $mes_relatorio,
        $semana,
        $impressoes,
        $interacoes,
        $quantidade_post,
        $plataforma,
        $data_registro
    );

    if ($stmt->execute()) {
        echo "<script>alert('Relatório de mídia cadastrado com sucesso!'); window.location.href='../pages/registros_bm.php#redes-sociais';</script>";
    } else {
        echo "<script>alert('Erro ao cadastrar relatório de mídia: " . $stmt->error . "'); window.location.href='../pages/registros_bm.php#redes-sociais';</script>";
    }

    $stmt->close();
}
$conn->close();
?>