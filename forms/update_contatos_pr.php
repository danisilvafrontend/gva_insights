<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include '../includes/config.php';
include '../includes/db_connect.php';
mysqli_set_charset($conn, "utf8mb4");

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1); // Salva erros em log do servidor


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $empresa_id = isset($_POST['empresa_id']) ? intval($_POST['empresa_id']) : 0;
    $semana = isset($_POST['semana']) ? trim($_POST['semana']) : '';
    $mesrelatorio = isset($_POST['mesrelatorio']) ? $_POST['mesrelatorio'] : '';
    $classificacao = isset($_POST['classificacao']) ? $_POST['classificacao'] : '';
    $tipocontato = isset($_POST['tipocontato']) ? $_POST['tipocontato'] : '';
    $contatosrealizados = isset($_POST['contatosrealizados']) ? intval($_POST['contatosrealizados']) : -1;

    if ($empresa_id == 0 || strlen($semana) != 2 || !in_array($semana, ['01','02','03','04','05']) || empty($mesrelatorio) || empty($classificacao) || empty($tipocontato) || $contatosrealizados < 0) {
        echo '<script>alert("Por favor, preencha todos os campos corretamente."); window.history.back();</script>';
        exit;
    }

    $sql = "UPDATE contatos_pr 
            SET empresa_id = ?, semana = ?, mesrelatorio = ?, classificacao = ?, tipocontato = ?, contatosrealizados = ? 
            WHERE id = ?";
    $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            error_log("Prepare error: " . $conn->error); // Log para debug
            echo '<script>alert("Erro ao preparar: ' . $conn->error . '"); window.history.back();</script>';
            exit;
        }

    // Correção aqui
    if (!$stmt->bind_param('isssssi', $empresa_id, $semana, $mesrelatorio, $classificacao, $tipocontato, $contatosrealizados, $id)) {
    error_log("Bind error: " . $stmt->error);
    echo '<script>alert("Erro no bind_param: ' . $stmt->error . '"); window.history.back();</script>';
    exit;
}

    if ($stmt->execute()) {
        echo "<script>alert('Dados atualizados com sucesso!'); window.location.href='https://insights.gvacompany.com/views_bd/views_contatos_pr.php';</script>";
    } else {
        echo "<script>alert('Erro ao atualizar dados: " . $stmt->error . "'); window.history.back();</script>";
    }
    $stmt->close();
} else {
    echo '<script>alert("Método inválido."); window.history.back();</script>';
}
$conn->close();
?>