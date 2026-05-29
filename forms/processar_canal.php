<?php
include '../includes/config.php';
include '../includes/db_connect.php';
    mysqli_set_charset($conn, "utf8mb4");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $canal_parceiro = $_POST['canal_parceiro'];
    $empresa_id = $_POST['empresa_id'];

    // Verificar se o canal já está registrado para a mesma empresa
    $sql = "SELECT * FROM bm_canais WHERE canal_parceiro = ? AND empresa_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $canal_parceiro, $empresa_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo "<script>alert('Canal já está registrado para este cliente.'); window.location.href='../pages/registros_bm.php#canais_parceiros';</script>";
    } else {
        $sql = "INSERT INTO bm_canais (canal_parceiro, empresa_id) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $canal_parceiro, $empresa_id);

        if ($stmt->execute()) {
            echo "<script>alert('Canal cadastrado com sucesso!'); window.location.href='../pages/registros_bm.php#canais_parceiros';</script>";
        } else {
            echo "<script>alert('Erro ao cadastrar canal.'); window.location.href='../pages/registros_bm.php#canais_parceiros';</script>";
        }
    }

    $stmt->close();
}

$conn->close();
?>
