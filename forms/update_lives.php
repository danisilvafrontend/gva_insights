<?php
include '../includes/db_connect.php'; // Inclui o arquivo de conexão ao banco de dados
    mysqli_set_charset($conn, "utf8mb4");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];
    $evento = $_POST['evento'];
    $empresa_id = $_POST['empresa_id'];
    $canal_id = $_POST['canal_id'];
    $data_evento = $_POST['data_evento'];
    $inscritos = $_POST['inscritos'];
    $peak_views = $_POST['peak_views'];
    $visualizacoes = $_POST['visualizacoes'];

    // Consulta SQL para atualizar o registro
    $sql = "UPDATE bm_lives SET evento = ?, empresa_id = ?, canal_id = ?, data_evento = ?, inscritos = ?, peak_views = ?, visualizacoes = ? WHERE id = ?";

    // Preparar a declaração
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("siissiii", $evento, $empresa_id, $canal_id, $data_evento, $inscritos, $peak_views, $visualizacoes, $id);

    if ($stmt->execute()) {
        echo "Registro atualizado com sucesso!";
    } else {
        echo "Erro ao atualizar o registro: " . $stmt->error;
    }

    // Fechar a declaração e a conexão
    $stmt->close();
    $conn->close();

    // Redirecionar de volta para a página de visualização de lives
    header("Location: https://insights.gvacompany.com/views_bd/views_lives.php");
    exit();
}
?>
