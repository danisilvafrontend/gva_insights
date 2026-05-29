<?php
include '../includes/config.php';
include '../includes/db_connect.php';
    mysqli_set_charset($conn, "utf8mb4");

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $evento = $_POST['evento'];
    $empresa_id = $_POST['empresa_id'];
    $canal_id = $_POST['canal_id'];
    $data_evento = $_POST['data_evento'];  // Remova o uso de strtotime
    $inscritos = $_POST['inscritos'];
    $peak_views = $_POST['peak_views'];
    $visualizacoes = $_POST['visualizacoes'];

    // Tentar converter a data do formato DD/MM/YYYY para YYYY-MM-DD 
    $date = DateTime::createFromFormat('d/m/Y', $data_evento); 
    if ($date === false) { 
        // Se a conversão falhar, tente o formato YYYY-MM-DD 
        $date = DateTime::createFromFormat('Y-m-d', $data_evento); 
    } 
    if ($date !== false) { 
        $data_evento = $date->format('Y-m-d'); 
    } else { 
        // Tratar erro na conversão da data 
        echo "<script>alert('Erro ao formatar a data. Verifique o formato DD/MM/YYYY ou YYYY-MM-DD.'); window.location.href='../pages/registros_bm.php';</script>"; 
        exit; 
    }

    // Verificar se o evento já está registrado (apenas pelo nome do evento)
    $sql = "SELECT * FROM bm_lives WHERE evento = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $evento);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo "<script>alert('Evento já está registrado.'); window.location.href='../pages/registros_bm.php#lives_eventos';</script>";
    } else {
        $sql = "INSERT INTO bm_lives (evento, empresa_id, canal_id, data_evento, inscritos, peak_views, visualizacoes) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("siissii", $evento, $empresa_id, $canal_id, $data_evento, $inscritos, $peak_views, $visualizacoes);

        if ($stmt->execute()) {
            echo "<script>alert('Live cadastrada com sucesso!'); window.location.href='../pages/registros_bm.php#lives_eventos';</script>";
        } else {
            echo "<script>alert('Erro ao cadastrar live.'); window.location.href='../pages/registros_bm.php#lives_eventos';</script>";
        }
    }

    $stmt->close();
}
$conn->close();
?>
