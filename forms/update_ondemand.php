<?php
include '../includes/db_connect.php'; // Inclui o arquivo de conexão ao banco de dados
    mysqli_set_charset($conn, "utf8mb4");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];
    $evento_id = $_POST['evento_id'];
    $empresa_id = $_POST['empresa_id'];
    $canal_id = $_POST['canal_id'];
    $data_relatorio = $_POST['data_relatorio'];
    $visualizacoes = $_POST['visualizacoes'];

    // Consulta SQL para atualizar o registro
    $sql = "UPDATE bm_ondemand SET evento_id = ?, empresa_id = ?, canal_id = ?, data_relatorio = ?, visualizacoes = ? WHERE id = ?";

    // Preparar a declaração
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiissi", $evento_id, $empresa_id, $canal_id, $data_relatorio, $visualizacoes, $id);

    if ($stmt->execute()) {
        echo "Registro atualizado com sucesso!";
    } else {
        echo "Erro ao atualizar o registro: " . $stmt->error;
    }

    // Fechar a declaração e a conexão
    $stmt->close();
    $conn->close();

    // Redirecionar de volta para a página de registros
    header("Location: https://insights.gvacompany.com/views_bd/views_ondemand.php");
    exit();
}
?>
