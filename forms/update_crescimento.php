<?php
include '../includes/db_connect.php'; // Inclui o arquivo de conexão ao banco de dados
    mysqli_set_charset($conn, "utf8mb4");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Capturar os dados do formulário
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $empresa_id = isset($_POST['empresa_id']) ? intval($_POST['empresa_id']) : 0;
    $plataforma = isset($_POST['plataforma']) ? $_POST['plataforma'] : '';
    $semana = isset($_POST['semana']) ? intval($_POST['semana']) : 0;
    $numero_seguidores = isset($_POST['numero_seguidores']) ? intval($_POST['numero_seguidores']) : -1;
    $mes_relatorio = isset($_POST['mes_relatorio']) ? $_POST['mes_relatorio'] : '';

    // Validações básicas
    if ($id <= 0 || $empresa_id <= 0 || empty($plataforma) || $semana < 1 || $semana > 5 || $numero_seguidores < 0 || empty($mes_relatorio)) {
        echo "<script>alert('Por favor, preencha todos os campos corretamente.'); window.history.back();</script>";
        exit;
    }

    // Preparar a query de atualização
    $sql = "UPDATE crescimento_redes_sociais 
            SET empresa_id = ?, plataforma = ?, semana = ?, numero_seguidores = ?, mes_relatorio = ? 
            WHERE id = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        echo "<script>alert('Erro ao preparar a consulta: " . $conn->error . "'); window.history.back();</script>";
        exit;
    }

    // Bind dos parâmetros (empresa_id: int, plataforma: string, semana: int, numero_seguidores: int, mes_relatorio: string, id: int)
    $stmt->bind_param("isissi", $empresa_id, $plataforma, $semana, $numero_seguidores, $mes_relatorio, $id);

    // Executar a query e verificar sucesso
    if ($stmt->execute()) {
        echo "<script>alert('Dados atualizados com sucesso!'); window.location.href='https://insights.gvacompany.com/views_bd/views_crescimento.php';</script>";
    } else {
        echo "<script>alert('Erro ao atualizar dados: " . $stmt->error . "'); window.history.back();</script>";
    }

    $stmt->close();
} else {
    echo "<script>alert('Método inválido.'); window.history.back();</script>";
}

$conn->close();
?>
