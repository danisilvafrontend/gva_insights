<?php 
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

include '../pages/header.php'; 
include '../includes/db_connect.php';
mysqli_set_charset($conn, "utf8mb4");
?>

<div class="container">
    <div class="row">
        <div class="col-6">
            <a href="https://insights.gvacompany.com/pages/registros_bm.php" class="btn btn-secondary">Voltar</a>
        </div>
    </div>
</div>

<div class="container">
    <h2 class="my-4">Últimos Relatórios de Mídia</h2>
    
    <!-- Filtros -->
    <form method="get" class="row g-3 mb-4">
        <div class="col-md-4">
            <label for="filtro_mes" class="form-label">Mês do Relatório</label>
            <input type="month" class="form-control" id="filtro_mes" name="filtro_mes" value="<?= isset($_GET['filtro_mes']) ? $_GET['filtro_mes'] : '' ?>">
        </div>
        <div class="col-md-4">
            <label for="filtro_cliente" class="form-label">Cliente</label>
            <select class="form-select" id="filtro_cliente" name="filtro_cliente">
                <option value="">Todos</option>
                <?php
                $clientes = $conn->query("SELECT id, company FROM clientes ORDER BY company ASC");
                while ($cliente = $clientes->fetch_assoc()) {
                    $selected = (isset($_GET['filtro_cliente']) && $_GET['filtro_cliente'] == $cliente['id']) ? 'selected' : '';
                    echo "<option value='{$cliente['id']}' $selected>" . htmlspecialchars($cliente['company']) . "</option>";
                }
                ?>
            </select>
        </div>
        <div class="col-md-4">
            <label for="filtro_empresa" class="form-label">Empresa</label>
            <select class="form-select" id="filtro_empresa" name="filtro_empresa">
                <option value="">Todas</option>
                <?php
                $empresas = $conn->query("SELECT id, empresa FROM empresas ORDER BY empresa ASC");
                while ($empresa = $empresas->fetch_assoc()) {
                    $selected = (isset($_GET['filtro_empresa']) && $_GET['filtro_empresa'] == $empresa['id']) ? 'selected' : '';
                    echo "<option value='{$empresa['id']}' $selected>" . htmlspecialchars($empresa['empresa']) . "</option>";
                }
                ?>
            </select>
        </div>
        <div class="col-md-4 d-flex align-items-end">
            <button type="submit" class="btn btn-primary">Filtrar</button>
            <a href="<?= strtok($_SERVER["REQUEST_URI"], '?') ?>" class="btn btn-outline-secondary ms-2">Limpar</a>
        </div>
    </form>
    
    <?php
    $filtro_mes = isset($_GET['filtro_mes']) ? $_GET['filtro_mes'] : '';
    $filtro_cliente = isset($_GET['filtro_cliente']) ? $_GET['filtro_cliente'] : '';
    $filtro_empresa = isset($_GET['filtro_empresa']) ? $_GET['filtro_empresa'] : '';

    $sql = "SELECT 
                mr.*, 
                c.company AS nome_cliente,
                e.empresa AS nome_empresa
            FROM media_report mr
            LEFT JOIN clientes c ON mr.cliente_id = c.id
            LEFT JOIN empresas e ON mr.empresa_id = e.id
            WHERE 1=1";

    if (!empty($filtro_mes)) {
        $sql .= " AND mr.mes_relatorio = '" . $conn->real_escape_string($filtro_mes) . "'";
    }
    if (!empty($filtro_cliente)) {
        $sql .= " AND mr.cliente_id = '" . intval($filtro_cliente) . "'";
    }
    if (!empty($filtro_empresa)) {
        $sql .= " AND mr.empresa_id = '" . intval($filtro_empresa) . "'";
    }

    $sql .= " ORDER BY mr.mes_relatorio DESC, mr.semana ASC LIMIT 30";

    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        echo "<table class='table table-striped'>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Cliente</th>
                        <th>Plataforma</th>
                        <th>Mês</th>
                        <th>Semana</th>
                        <th>Quantidade de Post</th>
                        <th>Impressões</th>
                        <th>Interações</th>
                        <th>Empresa</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>";
        while ($row = $result->fetch_assoc()) {
            $mesFormatado = date("m/Y", strtotime($row['mes_relatorio'] . '-01'));
            echo "<tr>
                    <td>{$row['id']}</td>
                    <td>" . htmlspecialchars($row['nome_cliente']) . "</td>
                    <td>" . htmlspecialchars($row['plataforma']) . "</td>
                    <td>{$mesFormatado}</td>
                    <td>Semana {$row['semana']}</td>
                    <td>" . number_format($row['quantidade_post'], 0, ',', '.') . "</td>
                    <td>" . number_format($row['impressoes'], 0, ',', '.') . "</td>
                    <td>" . number_format($row['interacoes'], 0, ',', '.') . "</td>
                    <td>" . htmlspecialchars($row['nome_empresa']) . "</td>
                    <td>
                        <a href='../forms/edit_media_report.php?id={$row['id']}' class='btn btn-primary btn-sm'>Editar</a>
                    </td>
                </tr>";
        }
        echo "</tbody></table>";
    } else {
        echo "<div class='alert alert-info'>Nenhum registro encontrado.</div>";
    }

    $conn->close();
    ?>
</div>

<?php include '../pages/footer.php'; ?>
