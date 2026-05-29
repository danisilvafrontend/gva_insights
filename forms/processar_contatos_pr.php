<?php
// Exibir erros para desenvolvimento (remova em produção)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include '../includes/config.php';
include '../includes/db_connect.php';
mysqli_set_charset($conn, "utf8mb4");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
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

    $sql = "INSERT INTO contatos_pr (empresa_id, semana, mesrelatorio, classificacao, tipocontato, contatosrealizados, data_registro) VALUES (?, ?, ?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        echo '<script>alert("Erro ao preparar a consulta: ' . $conn->error . '"); window.history.back();</script>';
        exit;
    }

    $stmt->bind_param('issssi', $empresa_id, $semana, $mesrelatorio, $classificacao, $tipocontato, $contatosrealizados);
    
// Executar e verificar sucesso
    if ($stmt->execute()) {
        echo "<script>alert('Dados inseridos com sucesso!'); window.location.href='../pages/registros_bm.php#contatos_pr';</script>";
    } else {
        echo "<script>alert('Erro ao cadastrar: " . $stmt->error . "'); window.history.back();</script>";
    }

    $stmt->close();
} else {
    echo "<script>alert('Método inválido.'); window.history.back();</script>";
}
$conn->close();
?>
