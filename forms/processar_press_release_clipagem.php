<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

include '../includes/config.php';
include '../includes/db_connect.php';
    mysqli_set_charset($conn, "utf8mb4");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $cliente_id = intval($_POST['cliente_id']);
    $publicacoes = intval($_POST['publicacoes']);
    
    // Converter valor brasileiro para float
    $valor_publicidade_raw = $_POST['valor_publicidade'];
    $valor_publicidade = floatval(str_replace(',', '.', str_replace('.', '', $valor_publicidade_raw)));
    
    $alcance = intval($_POST['alcance']);
    $empresa_id = intval($_POST['empresa_id']); // ← MUDANÇA: ID ao invés de nome
    $created_at = date('Y-m-d H:i:s');
    $mes_relatorio_raw = $_POST['mes_relatorio'];
    $mes_relatorio = $mes_relatorio_raw . '-01';

    // Inserir com empresa_id
    $sql = "INSERT INTO press_release_clipagem 
            (cliente_id, publicacoes, valor_publicidade, alcance, empresa_id, created_at, mes_relatorio) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo "<script>alert('Erro ao preparar: " . $conn->error . "'); window.location.href='../pages/registros_bm.php';</script>";
        exit;
    }

    // Tipos: i i d i i s s → cliente_id(int), publicacoes(int), valor_publicidade(double), alcance(int), empresa_id(int), created_at(string), mes_relatorio(string)
    $stmt->bind_param("iidiiss", 
        $cliente_id, 
        $publicacoes, 
        $valor_publicidade, 
        $alcance, 
        $empresa_id,
        $created_at,
        $mes_relatorio
    );


    if ($stmt->execute()) {
        echo "<script>alert('Clipagem cadastrada com sucesso!'); window.location.href='../pages/registros_bm.php#clipagem';</script>";
    } else {
        echo "<script>alert('Erro ao cadastrar: " . $stmt->error . "'); window.history.back();</script>";
    }

    $stmt->close();
}
$conn->close();
?>
