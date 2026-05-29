<?php
include '../includes/db_connect.php';
mysqli_set_charset($conn, "utf8mb4");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id']);
    $empresa_id = intval($_POST['empresa_id']);
    $data_envio = $_POST['data_envio'];
    $contatos = intval($_POST['contatos']);
    $aberturas = intval($_POST['aberturas']);
    $cliques = intval($_POST['cliques']);
    $ferramentas = $_POST['ferramentas'];
    $clientes = isset($_POST['clientes']) ? $_POST['clientes'] : [];
    $temas = isset($_POST['temas']) ? $_POST['temas'] : [];

    if ($id && $empresa_id && $data_envio) {
        // Atualizar dados principais
        $sql = "UPDATE press_release
                SET empresa_id=?, data_envio=?, contatos=?, aberturas=?, cliques=?, ferramentas=?
                WHERE id=?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isiiisi", $empresa_id, $data_envio, $contatos, $aberturas, $cliques, $ferramentas, $id);

        if ($stmt->execute()) {
            // Atualizar clientes
            $conn->query("DELETE FROM press_release_clientes WHERE id_press_release = $id");
            if (!empty($clientes)) {
                $sql_cliente = "INSERT INTO press_release_clientes (id_press_release, id_cliente) VALUES (?, ?)";
                $stmt_cliente = $conn->prepare($sql_cliente);
                foreach ($clientes as $cliente_id) {
                    $stmt_cliente->bind_param("ii", $id, $cliente_id);
                    $stmt_cliente->execute();
                }
                $stmt_cliente->close();
            }

            // Atualizar temas
            $conn->query("DELETE FROM press_release_temas WHERE id_press_release = $id");
            if (!empty($temas)) {
                $sql_tema = "INSERT INTO press_release_temas (id_press_release, id_tema) VALUES (?, ?)";
                $stmt_tema = $conn->prepare($sql_tema);
                foreach ($temas as $tema_id) {
                    $stmt_tema->bind_param("ii", $id, $tema_id);
                    $stmt_tema->execute();
                }
                $stmt_tema->close();
            }

            header("Location: ../views_bd/views_press_release.php?msg=sucesso");
            exit;
        } else {
            echo "<div class='alert alert-danger'>Erro: {$stmt->error}</div>";
        }
        $stmt->close();
    } else {
        echo "<div class='alert alert-danger'>Dados inválidos</div>";
    }
}
$conn->close();
?>