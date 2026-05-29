<?php  
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

include '../pages/header.php';  
include '../includes/db_connect.php';
mysqli_set_charset($conn, "utf8mb4");

// Filtros
$filtro_cliente = isset($_GET['cliente_id']) ? intval($_GET['cliente_id']) : 0;
$filtro_empresa = isset($_GET['empresa_id']) ? intval($_GET['empresa_id']) : 0;
$filtro_tema    = isset($_GET['tema_id']) ? intval($_GET['tema_id']) : 0;
$filtro_mes     = isset($_GET['mes']) ? $_GET['mes'] : '';

// Combos
$sql_clientes = "SELECT id, company FROM clientes ORDER BY company ASC";
$result_clientes = $conn->query($sql_clientes);

$sql_empresas = "SELECT id, empresa FROM empresas ORDER BY empresa ASC";
$result_empresas = $conn->query($sql_empresas);

$sql_temas = "SELECT id, tema FROM temas ORDER BY tema ASC";
$result_temas = $conn->query($sql_temas);
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
    <h2 class="my-4">Últimos Press Releases</h2>

    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-header">
            <h5><i class="fas fa-filter"></i> Filtros</h5>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <!-- Cliente -->
                <div class="col-md-3">
                    <label class="form-label">Cliente</label>
                    <select name="cliente_id" class="form-select">
                        <option value="">Todos os clientes</option>
                        <?php
                        if ($result_clientes && $result_clientes->num_rows > 0) {
                            $result_clientes->data_seek(0);
                            while ($cli = $result_clientes->fetch_assoc()) {
                                $selected = ($filtro_cliente == $cli['id']) ? 'selected' : '';
                                echo "<option value='{$cli['id']}' $selected>" . htmlspecialchars($cli['company']) . "</option>";
                            }
                        }
                        ?>
                    </select>
                </div>

                <!-- Empresa -->
                <div class="col-md-3">
                    <label class="form-label">Empresa</label>
                    <select name="empresa_id" class="form-select">
                        <option value="">Todas as empresas</option>
                        <?php
                        if ($result_empresas && $result_empresas->num_rows > 0) {
                            $result_empresas->data_seek(0);
                            while ($emp = $result_empresas->fetch_assoc()) {
                                $selected = ($filtro_empresa == $emp['id']) ? 'selected' : '';
                                echo "<option value='{$emp['id']}' $selected>" . htmlspecialchars($emp['empresa']) . "</option>";
                            }
                        }
                        ?>
                    </select>
                </div>

                <!-- Tema -->
                <div class="col-md-3">
                    <label class="form-label">Tema</label>
                    <select name="tema_id" class="form-select">
                        <option value="">Todos os temas</option>
                        <?php
                        if ($result_temas && $result_temas->num_rows > 0) {
                            $result_temas->data_seek(0);
                            while ($tema = $result_temas->fetch_assoc()) {
                                $selected = ($filtro_tema == $tema['id']) ? 'selected' : '';
                                echo "<option value='{$tema['id']}' $selected>" . htmlspecialchars($tema['tema']) . "</option>";
                            }
                        }
                        ?>
                    </select>
                </div>

                <!-- Mês -->
                <div class="col-md-3">
                    <label class="form-label">Mês</label>
                    <select name="mes" class="form-select">
                        <option value="">Todos os meses</option>
                        <?php
                        // últimos 12 meses baseados em data_envio
                        for ($i = 0; $i < 12; $i++) {
                            $mesRef = date('Y-m', strtotime("-$i month"));
                            $label = date('m/Y', strtotime($mesRef . '-01'));
                            $selected = ($filtro_mes == $mesRef) ? 'selected' : '';
                            echo "<option value='$mesRef' $selected>$label</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="col-12 d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary me-2">Filtrar</button>
                    <a href="<?php echo strtok($_SERVER["REQUEST_URI"], '?'); ?>" class="btn btn-outline-secondary">Limpar</a>
                </div>
            </form>
        </div>
    </div>

    <?php
    // Monta SQL com filtros
    $sql = "SELECT 
                pr.*, 
                e.empresa AS nome_empresa,
                GROUP_CONCAT(DISTINCT c.company SEPARATOR ', ') AS clientes_nomes,
                GROUP_CONCAT(DISTINCT t.tema SEPARATOR ', ') AS temas_nomes
            FROM press_release pr
            LEFT JOIN empresas e ON pr.empresa_id = e.id
            LEFT JOIN press_release_clientes prc ON pr.id = prc.id_press_release
            LEFT JOIN clientes c ON prc.id_cliente = c.id
            LEFT JOIN press_release_temas prt ON pr.id = prt.id_press_release
            LEFT JOIN temas t ON prt.id_tema = t.id
            WHERE 1=1";

    $params = [];
    $types  = '';

    if ($filtro_cliente > 0) {
        $sql .= " AND prc.id_cliente = ?";
        $params[] = $filtro_cliente;
        $types .= 'i';
    }

    if ($filtro_empresa > 0) {
        $sql .= " AND pr.empresa_id = ?";
        $params[] = $filtro_empresa;
        $types .= 'i';
    }

    if ($filtro_tema > 0) {
        $sql .= " AND prt.id_tema = ?";
        $params[] = $filtro_tema;
        $types .= 'i';
    }

    if (!empty($filtro_mes)) {
        // filtra por ano-mês de data_envio
        $sql .= " AND DATE_FORMAT(pr.data_envio, '%Y-%m') = ?";
        $params[] = $filtro_mes;
        $types .= 's';
    }

    $sql .= " GROUP BY pr.id
              ORDER BY pr.data_envio DESC
              LIMIT 50";

    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        echo "<table class='table table-striped'>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Clientes</th>
                        <th>Temas</th>
                        <th>Data de Envio</th>
                        <th>Contatos</th>
                        <th>Aberturas</th>
                        <th>Cliques</th>
                        <th>Ferramentas</th>
                        <th>Empresa</th>
                        <th>Ação</th>
                    </tr>
                </thead>
                <tbody>";
        while ($row = $result->fetch_assoc()) {
            $data_envio = date("d/m/Y", strtotime($row["data_envio"])); 
            echo "<tr>
                    <td>{$row['id']}</td>
                    <td>" . htmlspecialchars($row['clientes_nomes']) . "</td>
                    <td>" . htmlspecialchars($row['temas_nomes']) . "</td>
                    <td>{$data_envio}</td>
                    <td>{$row['contatos']}</td>
                    <td>{$row['aberturas']}</td>
                    <td>{$row['cliques']}</td>
                    <td>" . htmlspecialchars($row['ferramentas']) . "</td>
                    <td>" . htmlspecialchars($row['nome_empresa']) . "</td>
                    <td><a href='../forms/edit_press_release.php?id={$row['id']}' class='btn btn-primary btn-sm'>Editar</a></td>
                  </tr>";
        }
        echo "</tbody></table>";
    } else {
        echo "<div class='alert alert-info'>Nenhum registro encontrado" .
             (!empty($filtro_cliente) || !empty($filtro_empresa) || !empty($filtro_tema) || !empty($filtro_mes) ? " com os filtros aplicados." : ".") .
             "</div>";
    }

    $stmt->close();
    $conn->close();
    ?>
</div>

<?php include '../pages/footer.php'; ?>
