<?php 
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

include '../pages/header.php'; 
include '../includes/db_connect.php';
mysqli_set_charset($conn, "utf8mb4");

// Capturar filtros do GET
$cliente_id = isset($_GET['cliente_id']) ? intval($_GET['cliente_id']) : '';
$mes_relatorio = isset($_GET['mes_relatorio']) ? $_GET['mes_relatorio'] : '';
$empresa_id = isset($_GET['empresa_id']) ? intval($_GET['empresa_id']) : '';

// Queries para os selects de filtro
$sql_clientes = "SELECT id, company FROM clientes ORDER BY company ASC";
$result_clientes = $conn->query($sql_clientes);

$sql_empresas = "SELECT id, empresa FROM empresas ORDER BY empresa ASC";
$result_empresas = $conn->query($sql_empresas);
?>

<meta charset="UTF-8">
<div class="container">
    <div class="row">
        <div class="col-6">
            <a href="https://insights.gvacompany.com/pages/registros_bm.php" class="btn btn-secondary">Voltar</a>
        </div>
    </div>
</div>

<div class="container">
    <h2 class="my-4">Últimos Registros de Clipagem</h2>
    
    <!-- FORMULÁRIO DE FILTROS -->
    <form method="GET" class="row g-3 mb-4 p-3 border rounded">
        <div class="col-md-3">
            <label class="form-label">Cliente</label>
            <select name="cliente_id" class="form-select" onchange="this.form.submit()">
                <option value="">Todos os Clientes</option>
                <?php 
                if ($result_clientes->num_rows > 0) {
                    $result_clientes->data_seek(0); // Reset pointer
                    while ($cliente = $result_clientes->fetch_assoc()) {
                        $selected = ($cliente_id == $cliente['id']) ? 'selected' : '';
                        echo "<option value='{$cliente['id']}' $selected>{$cliente['company']}</option>";
                    }
                }
                ?>
            </select>
        </div>
        
        <div class="col-md-3">
            <label class="form-label">Mês Relatório</label>
            <select name="mes_relatorio" class="form-select" onchange="this.form.submit()">
                <option value="">Todos os Meses</option>
                <?php
                $meses = [
                    '2025-01', '2025-02', '2025-03', '2025-04', '2025-05', '2025-06',
                    '2025-07', '2025-08', '2025-09', '2025-10', '2025-11', '2025-12'
                ];
                foreach ($meses as $mes) {
                    $selected = ($mes_relatorio == $mes) ? 'selected' : '';
                    echo "<option value='$mes' $selected>" . date("m/Y", strtotime($mes . '-01')) . "</option>";
                }
                ?>
            </select>
        </div>
        
        <div class="col-md-3">
            <label class="form-label">Empresa</label>
            <select name="empresa_id" class="form-select" onchange="this.form.submit()">
                <option value="">Todas as Empresas</option>
                <?php 
                if ($result_empresas->num_rows > 0) {
                    $result_empresas->data_seek(0); // Reset pointer
                    while ($empresa = $result_empresas->fetch_assoc()) {
                        $selected = ($empresa_id == $empresa['id']) ? 'selected' : '';
                        echo "<option value='{$empresa['id']}' $selected>{$empresa['empresa']}</option>";
                    }
                }
                ?>
            </select>
        </div>
        
        <div class="col-md-3 d-flex align-items-end">
            <button type="button" class="btn btn-secondary me-2" onclick="limparFiltros()">Limpar Filtros</button>
        </div>
    </form>

    <?php
    // Query base corrigida
    $sql_base = "SELECT 
                    pc.*, 
                    c.company AS nome_cliente,
                    e.empresa AS nome_empresa
                FROM press_release_clipagem pc
                LEFT JOIN clientes c ON pc.cliente_id = c.id
                LEFT JOIN empresas e ON pc.empresa_id = e.id";

    $where_conditions = [];

    if ($cliente_id) {
        $where_conditions[] = "pc.cliente_id = $cliente_id";
    }
    if ($mes_relatorio) {
        $where_conditions[] = "pc.mes_relatorio = '" . $conn->real_escape_string($mes_relatorio) . "'";
    }
    if ($empresa_id) {
        $where_conditions[] = "pc.empresa_id = $empresa_id";
    }

    $sql = $sql_base;
    if (!empty($where_conditions)) {
        $sql .= " WHERE " . implode(' AND ', $where_conditions);
    }
    $sql .= " ORDER BY pc.created_at DESC LIMIT 30";

    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        echo "<table class='table table-striped'>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Cliente</th>
                        <th>Mês Relatório</th>
                        <th>Empresa</th>
                        <th>Publicações</th>
                        <th>Valor Publicidade (R$)</th>
                        <th>Alcance</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>
                    <td>{$row['id']}</td>
                    <td>{$row['nome_cliente']}</td>
                    <td>" . date("m/Y", strtotime($row['mes_relatorio'])) . "</td>
                    <td>{$row['nome_empresa']}</td>
                    <td>{$row['publicacoes']}</td>
                    <td>R$ " . number_format($row['valor_publicidade'], 2, ',', '.') . "</td>
                    <td>" . number_format($row['alcance'], 0, ',', '.') . "</td>
                    <td>
                        <a href='../forms/edit_press_release_clipagem.php?id={$row['id']}' class='btn btn-primary btn-sm'>Editar</a>
                    </td>
                  </tr>";
        }
        echo "</tbody></table>";
    } else {
        echo "<div class='alert alert-info'>Nenhum registro encontrado" . 
             (!empty($where_conditions) ? " com os filtros aplicados." : ".") . "</div>";
    }

    $conn->close();
    ?>
</div>

<script>
function limparFiltros() {
    const url = new URL(window.location);
    url.searchParams.delete('cliente_id');
    url.searchParams.delete('mes_relatorio');
    url.searchParams.delete('empresa_id');
    window.location.href = url.toString();
}
</script>

<?php include '../pages/footer.php'; ?>
