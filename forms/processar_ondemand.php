<?php
include '../includes/config.php';
include '../includes/db_connect.php';
    mysqli_set_charset($conn, "utf8mb4");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $evento_id = $_POST['evento_id'];
    $empresa_id = $_POST['empresa_id'];
    $canal_id = $_POST['canal_id'];
    $data_relatorio = $_POST['data_relatorio'];
    $visualizacoes = $_POST['visualizacoes'];

    // Detectar e formatar a data corretamente para YYYY-MM-DD
    $formatted_date = false;
    if (DateTime::createFromFormat('Y-m-d', $data_relatorio) !== false) {
        $formatted_date = $data_relatorio;
    } else {
        $date = DateTime::createFromFormat('d/m/Y', $data_relatorio);
        if ($date !== false) {
            $formatted_date = $date->format('Y-m-d');
        }
    }

    if ($formatted_date === false) {
        // Tratar erro na conversão da data
        echo "<script>alert('Erro ao formatar a data. Verifique o formato DD/MM/YYYY ou YYYY-MM-DD.'); window.location.href='../pages/registros_bm.php#lives_eventos';</script>";
        exit;
    }

    $sql = "INSERT INTO bm_ondemand (evento_id, empresa_id, canal_id, data_relatorio, visualizacoes) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiisi", $evento_id, $empresa_id, $canal_id, $formatted_date, $visualizacoes);

    if ($stmt->execute()) {
        echo "<script>alert('Registro on-demand cadastrado com sucesso!'); window.location.href='../pages/registros_bm.php#lives_eventos';</script>";
    } else {
        echo "<script>alert('Erro ao cadastrar registro on-demand.'); window.location.href='../pages/registros_bm.php#lives_eventos';</script>";
    }

    $stmt->close();
}

$conn->close();
?>
