<?php
include '../includes/db_connect.php';
    mysqli_set_charset($conn, "utf8mb4");

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = intval($_POST['id']);
    $nome_newsletter = $_POST['nome_newsletter'];
    $data_envio = $_POST['data_envio'];
    $emails_entregues = intval($_POST['emails_entregues']);
    $aberturas_unicas = intval($_POST['aberturas_unicas']);
    $cliques_unicos = intval($_POST['cliques_unicos']);
    $cancelamento = intval($_POST['cancelamento']);
    $empresa_id = isset($_POST['empresa_id']) ? intval($_POST['empresa_id']) : 0;
    $clientes = isset($_POST['clientes']) ? $_POST['clientes'] : [];
    $temas = isset($_POST['temas']) ? $_POST['temas'] : [];

    // Atualizar os dados da newsletter
    $sql = "UPDATE bm_newsletter 
            SET nome_newsletter = ?, data_envio = ?, emails_entregues = ?, aberturas_unicas = ?, cliques_unicos = ?, cancelamento = ?, empresa_id = ?
            WHERE id = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        error_log("Erro ao preparar a declaração: " . $conn->error);
        echo "<script>alert('Erro ao preparar a declaração: " . $conn->error . "'); window.location.href='edit_newsletter.php?id=$id';</script>";
        exit();
    }

    if (!$stmt->bind_param("ssiiiiii", $nome_newsletter, $data_envio, $emails_entregues, $aberturas_unicas, $cliques_unicos, $cancelamento, $empresa_id, $id)) {
        error_log("Erro ao vincular parâmetros: " . $stmt->error);
        echo "<script>alert('Erro ao vincular parâmetros: " . $stmt->error . "'); window.location.href='edit_newsletter.php?id=$id';</script>";
        exit();
    }

    if ($stmt->execute()) {
        // Atualizar clientes
        $sql_delete_clientes = "DELETE FROM newsletter_clientes WHERE id_newsletter = ?";
        $stmt_delete_clientes = $conn->prepare($sql_delete_clientes);
        $stmt_delete_clientes->bind_param("i", $id);
        $stmt_delete_clientes->execute();

        if (!empty($clientes)) {
            $sql_insert_clientes = "INSERT INTO newsletter_clientes (id_newsletter, id_cliente) VALUES (?, ?)";
            $stmt_insert_clientes = $conn->prepare($sql_insert_clientes);
            foreach ($clientes as $cliente_id) {
                $stmt_insert_clientes->bind_param("ii", $id, $cliente_id);
                $stmt_insert_clientes->execute();
            }
            $stmt_insert_clientes->close();
        }

        // Atualizar temas
        $sql_delete_temas = "DELETE FROM newsletter_temas WHERE id_newsletter = ?";
        $stmt_delete_temas = $conn->prepare($sql_delete_temas);
        $stmt_delete_temas->bind_param("i", $id);
        $stmt_delete_temas->execute();

        if (!empty($temas)) {
            $sql_insert_temas = "INSERT INTO newsletter_temas (id_newsletter, id_tema) VALUES (?, ?)";
            $stmt_insert_temas = $conn->prepare($sql_insert_temas);
            foreach ($temas as $tema_id) {
                $stmt_insert_temas->bind_param("ii", $id, $tema_id);
                $stmt_insert_temas->execute();
            }
            $stmt_insert_temas->close();
        }

        echo "<script>alert('Registro atualizado com sucesso!'); window.location.href='../views_bd/views_newsletter.php';</script>";
    } else {
        error_log("Erro ao executar a declaração: " . $stmt->error);
        echo "<script>alert('Erro ao atualizar o registro: " . $stmt->error . "'); window.location.href='edit_newsletter.php?id=$id';</script>";
    }

    $stmt->close();
    $conn->close();
}
?>