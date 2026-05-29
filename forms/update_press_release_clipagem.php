<?php
include '../includes/db_connect.php';
    mysqli_set_charset($conn, "utf8mb4");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = intval($_POST['id']);
    $cliente_id = intval($_POST['cliente_id']);
    $publicacoes = intval($_POST['publicacoes']);
    $valor_publicidade = floatval($_POST['valor_publicidade']);
    $alcance = intval($_POST['alcance']);
    $empresa_id = intval($_POST['empresa_id']);
    $mes_relatorio_raw = $_POST['mes_relatorio'];
    $mes_relatorio = $mes_relatorio_raw . '-01';

    // Atualizar os dados
    $sql = "UPDATE press_release_clipagem 
        SET cliente_id = ?, publicacoes = ?, valor_publicidade = ?, alcance = ?, empresa_id = ?, mes_relatorio = ? 
        WHERE id = ?";


    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        echo "<script>alert('Erro ao preparar a consulta: " . $conn->error . "'); window.location.href='../views_bd/views_press_release_clipagem.php';</script>";
        exit;
    }

    $stmt->bind_param("iidiisi", 
    $cliente_id, 
    $publicacoes, 
    $valor_publicidade, 
    $alcance, 
    $empresa_id, 
    $mes_relatorio,
    $id
);

    if ($stmt->execute()) {
        echo "<script>alert('Clipagem atualizada com sucesso!'); window.location.href='../views_bd/views_press_release_clipagem.php';</script>";
    } else {
        echo "<script>alert('Erro ao atualizar clipagem: " . $stmt->error . "'); window.location.href='../views_bd/views_press_release_clipagem.php';</script>";
    }

    $stmt->close();
}
$conn->close();
?>