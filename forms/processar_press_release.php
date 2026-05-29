<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include '../includes/config.php';
include '../includes/db_connect.php';
mysqli_set_charset($conn, "utf8mb4");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $empresa_id = $_POST['empresa_id'];
    $data_envio = $_POST['data_envio'];
    $contatos = $_POST['contatos'];
    $aberturas = $_POST['aberturas'];
    $cliques = $_POST['cliques'];
    $ferramentas = $_POST['ferramentas'];
    $clientes = isset($_POST['clientes']) ? $_POST['clientes'] : [];
    $temas = isset($_POST['temas']) ? $_POST['temas'] : [];

    // Converter data
    $date = DateTime::createFromFormat('d/m/Y', $data_envio);
    if ($date === false) {
        $date = DateTime::createFromFormat('Y-m-d', $data_envio);
    }
    if ($date !== false) {
        $data_envio = $date->format('Y-m-d');
    } else {
        echo "<script>alert('Erro ao formatar data'); window.location.href='../pages/registros_bm.php#press_release';</script>";
        exit;
    }

    // Inserir press release
    $sql = "INSERT INTO press_release 
        (empresa_id, data_envio, contatos, aberturas, cliques, ferramentas, data_registro) 
        VALUES (?, ?, ?, ?, ?, ?, NOW())";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo "<script>alert('Erro: {$conn->error}'); window.location.href='../pages/registros_bm.php#press_release';</script>";
        exit;
    }

    $stmt->bind_param("isiiis", $empresa_id, $data_envio, $contatos, $aberturas, $cliques, $ferramentas);

    if ($stmt->execute()) {
        $press_release_id = $stmt->insert_id;

        // Inserir clientes relacionados
        if (!empty($clientes)) {
            $sql_cliente = "INSERT INTO press_release_clientes (id_press_release, id_cliente) VALUES (?, ?)";
            $stmt_cliente = $conn->prepare($sql_cliente);
            foreach ($clientes as $cliente_id) {
                $stmt_cliente->bind_param("ii", $press_release_id, $cliente_id);
                $stmt_cliente->execute();
            }
            $stmt_cliente->close();
        }

        // Inserir temas relacionados
        if (!empty($temas)) {
            $sql_tema = "INSERT INTO press_release_temas (id_press_release, id_tema) VALUES (?, ?)";
            $stmt_tema = $conn->prepare($sql_tema);
            foreach ($temas as $tema_id) {
                $stmt_tema->bind_param("ii", $press_release_id, $tema_id);
                $stmt_tema->execute();
            }
            $stmt_tema->close();
        }

        echo "<script>alert('Press Release cadastrado com sucesso!'); window.location.href='../pages/registros_bm.php#press_release';</script>";
    } else {
        echo "<script>alert('Erro: {$stmt->error}'); window.location.href='../pages/registros_bm.php#press_release';</script>";
    }

    $stmt->close();
}
$conn->close();
?>