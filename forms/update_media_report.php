<?php
include '../includes/db_connect.php'; // Conexão com o banco
    mysqli_set_charset($conn, "utf8mb4");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = intval($_POST['id']);
    $cliente_id = intval($_POST['cliente_id']);
    $empresa_id = intval($_POST['empresa_id']);
    $mes_relatorio = $_POST['mes_relatorio']; // novo campo
    $semana = $_POST['semana'];               // novo campo
    $impressoes = intval($_POST['impressoes']);
    $interacoes = intval($_POST['interacoes']);
    $quantidade_post = intval($_POST['quantidade_post']);
    $empresa = $_POST['empresa'];
    $plataforma = $_POST['plataforma'];

    // Validação básica
    if (empty($mes_relatorio) || empty($semana)) {
        echo "<script>alert('Mês ou semana não informados.'); window.location.href='../views_bd/views_media_report.php';</script>";
        exit;
    }

    $sql = "UPDATE media_report 
        SET cliente_id = ?, empresa_id = ?, mes_relatorio = ?, semana = ?, impressoes = ?, interacoes = ?, quantidade_post = ?, plataforma = ? 
        WHERE id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iissiiisi", 
        $cliente_id, 
        $empresa_id, 
        $mes_relatorio, 
        $semana, 
        $impressoes, 
        $interacoes, 
        $quantidade_post, 
        $plataforma, 
        $id
    );

    if ($stmt->execute()) {
        echo "<script>alert('Relatório de mídia atualizado com sucesso!'); window.location.href='../views_bd/views_media_report.php';</script>";
        exit;
    } else {
        echo "<script>alert('Erro ao atualizar o relatório de mídia: " . $stmt->error . "'); window.location.href='../views_bd/views_media_report.php';</script>";
    }

    $stmt->close();
    $conn->close();
}
?>