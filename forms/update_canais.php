<?php
include '../includes/db_connect.php'; // Inclui o arquivo de conexão ao banco de dados
    mysqli_set_charset($conn, "utf8mb4");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];
    $canal_parceiro = $_POST['canal_parceiro'];
    $empresa_id = $_POST['empresa_id'];

    // Consulta SQL para atualizar o registro
    $sql = "UPDATE bm_canais SET canal_parceiro = ?, empresa_id = ? WHERE id = ?";

    // Preparar a declaração
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sii", $canal_parceiro, $empresa_id, $id);

    if ($stmt->execute()) {
        echo "Registro atualizado com sucesso!";
    } else {
        echo "Erro ao atualizar o registro: " . $stmt->error;
    }

    // Fechar a declaração e a conexão
    $stmt->close();
    $conn->close();

    // Redirecionar de volta para a página de visualização de canais
    header("Location: https://insights.gvacompany.com/views_bd/views_canais.php");
    exit();
}
?>
