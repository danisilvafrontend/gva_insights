<?php

// Exibir erros para desenvolvimento (remova em produção)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include '../includes/db_connect.php'; // ajuste o caminho conforme necessário
    mysqli_set_charset($conn, "utf8mb4");
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Capturar os dados do formulário
    $empresa_id = isset($_POST['empresa_id']) ? intval($_POST['empresa_id']) : 0;
    $plataforma = isset($_POST['plataforma']) ? $_POST['plataforma'] : '';
    $semana = isset($_POST['semana']) ? intval($_POST['semana']) : 0;
    $numero_seguidores = isset($_POST['numero_seguidores']) ? intval($_POST['numero_seguidores']) : -1;
    $mes_relatorio = isset($_POST['mes_relatorio']) ? $_POST['mes_relatorio'] : '';


    // Validações básicas
    if ($empresa_id <= 0 || empty($plataforma) || $semana < 1 || $semana > 5 || $numero_seguidores < 0 || empty($mes_relatorio)) {
        echo "<script>alert('Por favor, preencha todos os campos corretamente.'); window.history.back();</script>";
        exit;
    }

    // Preparar a query de inserção
    $sql = "INSERT INTO crescimento_redes_sociais (empresa_id, plataforma, semana, numero_seguidores, mes_relatorio) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        echo "<script>alert('Erro ao preparar a consulta: " . $conn->error . "'); window.history.back();</script>";
        exit;
    }

    // Bind dos parâmetros (empresa_id: int, plataforma: string, semana: int, numero_seguidores: int, mes_relatorio: string)
    $stmt->bind_param("isiss", $empresa_id, $plataforma, $semana, $numero_seguidores, $mes_relatorio);

    // Executar e verificar sucesso
    if ($stmt->execute()) {
        echo "<script>alert('Dados de crescimento inseridos com sucesso!'); window.location.href='../pages/registros_bm.php#redes-sociais';</script>";
    } else {
        echo "<script>alert('Erro ao cadastrar crescimento: " . $stmt->error . "'); window.history.back();</script>";
    }

    $stmt->close();
} else {
    echo "<script>alert('Método inválido.'); window.history.back();</script>";
}
$conn->close();
?>
