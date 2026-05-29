<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

include '../includes/config.php';
include '../includes/db_connect.php';
mysqli_set_charset($conn, "utf8mb4");


function formatarValorBrasileiro($valor) {
    // Remove tudo que não é número, vírgula ou ponto
    $valor = preg_replace('/[^0-9,.]/', '', $valor);
    
    // Se tem vírgula, ela é o separador decimal
    if (strpos($valor, ',') !== false) {
        $valor = str_replace('.', '', $valor); // Remove pontos dos milhares
        $valor = str_replace(',', '.', $valor); // Vírgula → ponto
    }
    
    return floatval($valor);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['id']) || !isset($_POST['cliente_id']) || !isset($_POST['empresa_id'])) {
        echo "<script>alert('Campos obrigatórios não enviados!'); window.history.back();</script>";
        exit;
    }

    $id = intval($_POST['id']);
    $nome_anuncio = $conn->real_escape_string($_POST['nome_anuncio']);
    $plataforma = $_POST['plataforma'];
    $cliente_id = intval($_POST['cliente_id']);
    $empresa_id = intval($_POST['empresa_id']);
    $inicio_anuncio = $_POST['inicio_anuncio'];
    $termino_anuncio = $_POST['termino_anuncio'];
    $objetivo = $_POST['objetivo'];
    $alcance = intval($_POST['alcance'] ?? 0);
    $impressoes = intval($_POST['impressoes'] ?? 0);
    $cliques_interacoes = intval($_POST['cliques_interacoes'] ?? 0);
    
    // ✅ CORREÇÃO: Função robusta para valor brasileiro
    $valor_gasto = formatarValorBrasileiro($_POST['valor_gasto']);

    $sql = "UPDATE anuncios SET 
            nome_anuncio = ?, 
            plataforma = ?, 
            cliente_id = ?, 
            empresa_id = ?, 
            inicio_anuncio = ?, 
            termino_anuncio = ?, 
            objetivo = ?, 
            alcance = ?, 
            impressoes = ?, 
            cliques_interacoes = ?, 
            valor_gasto = ?
            WHERE id = ?";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo "<script>alert('Erro ao preparar a consulta: " . $conn->error . "'); window.location.href='../views_bd/views_anuncios.php';</script>";
        exit;
    }

    // ✅ TIPOS CORRETOS: 12 parâmetros (11 campos + 1 WHERE id)
    // s s i i s s s i i i d i
    $stmt->bind_param("ssiisssiiidi",
        $nome_anuncio, 
        $plataforma, 
        $cliente_id, 
        $empresa_id, 
        $inicio_anuncio, 
        $termino_anuncio, 
        $objetivo, 
        $alcance, 
        $impressoes, 
        $cliques_interacoes, 
        $valor_gasto, 
        $id
    );

    if ($stmt->execute()) {
        echo "<script>alert('Anúncio atualizado com sucesso!'); 
              window.location.href='../views_bd/views_anuncios.php';</script>";
    } else {
        echo "<script>alert('Erro ao atualizar: " . $stmt->error . "'); window.history.back();</script>";
    }

    $stmt->close();
}
$conn->close();
?>
