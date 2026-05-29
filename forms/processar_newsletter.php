<?php
include '../includes/config.php';
include '../includes/db_connect.php';
    mysqli_set_charset($conn, "utf8mb4");

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Captura os dados do formulário
    $nome_newsletter = $_POST['nome_newsletter'];
    $data_envio = $_POST['data_envio'];
    $emails_entregues = intval($_POST['emails_entregues']);
    $aberturas_unicas = intval($_POST['aberturas_unicas']);
    $cliques_unicos = intval($_POST['cliques_unicos']);
    $cancelamento = intval($_POST['cancelamento']);
    $empresa_id = isset($_POST['empresa_id']) ? intval($_POST['empresa_id']) : 0;
    $clientes = isset($_POST['clientes']) ? $_POST['clientes'] : [];
    $temas = isset($_POST['temas']) ? $_POST['temas'] : [];

    // Verificação para garantir que a lista de clientes não está vazia
    if (empty($clientes)) {
        echo "<script>alert('Por favor, selecione pelo menos um cliente.'); window.location.href='registrar_newsletter.php';</script>";
        exit;
    }

    // Formatação da data para o formato correto (YYYY-MM-DD)
    $formatted_date = false;
    if (DateTime::createFromFormat('Y-m-d', $data_envio) !== false) {
        $formatted_date = $data_envio;
    } else {
        $date = DateTime::createFromFormat('d/m/Y', $data_envio);
        if ($date !== false) {
            $formatted_date = $date->format('Y-m-d');
        }
    }

    if ($formatted_date === false) {
        echo "<script>alert('Erro ao formatar a data. Verifique o formato DD/MM/YYYY ou YYYY-MM-DD.'); window.location.href='registrar_newsletter.php';</script>";
        exit;
    }

    // Inserir a newsletter na tabela bm_newsletter
    $sql = "INSERT INTO bm_newsletter (nome_newsletter, data_envio, emails_entregues, aberturas_unicas, cliques_unicos, cancelamento, empresa_id)
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssiiiii", $nome_newsletter, $formatted_date, $emails_entregues, $aberturas_unicas, $cliques_unicos, $cancelamento, $empresa_id);

    if ($stmt->execute()) {
        $newsletter_id = $stmt->insert_id;
        error_log("Newsletter cadastrada com sucesso! ID: " . $newsletter_id);

        // Inserir clientes na tabela intermediária
        $sql_cliente = "INSERT INTO newsletter_clientes (id_newsletter, id_cliente) VALUES (?, ?)";
        $stmt_cliente = $conn->prepare($sql_cliente);
        foreach ($clientes as $cliente_id) {
            $stmt_cliente->bind_param("ii", $newsletter_id, $cliente_id);
            $stmt_cliente->execute();
        }
        $stmt_cliente->close();

        // Inserir temas na tabela intermediária
        if (!empty($temas)) {
            $sql_tema = "INSERT INTO newsletter_temas (id_newsletter, id_tema) VALUES (?, ?)";
            $stmt_tema = $conn->prepare($sql_tema);
            foreach ($temas as $tema_id) {
                $stmt_tema->bind_param("ii", $newsletter_id, $tema_id);
                $stmt_tema->execute();
            }
            $stmt_tema->close();
        }

        echo "<script>alert('Newsletter, clientes e temas cadastrados com sucesso!'); window.location.href='../pages/registros_bm.php#newsletter';</script>";
    } else {
        echo "<script>alert('Erro ao cadastrar a newsletter: " . $stmt->error . "'); window.location.href='registrar_newsletter.php#newsletter';</script>";
    }

    $stmt->close();
    $conn->close();
} else {
    echo "<script>alert('Método inválido.'); window.history.back();</script>";
}
?>