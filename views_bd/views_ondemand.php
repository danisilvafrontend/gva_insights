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
$filtro_canal   = isset($_GET['canal_id']) ? intval($_GET['canal_id']) : 0;
$filtro_mes     = isset($_GET['mes']) ? $_GET['mes'] : '';

// Combos: clientes e canais
$sql_clientes = "SELECT id, company FROM clientes ORDER BY company ASC";
$result_clientes = $conn->query($sql_clientes);

$sql_canais = "SELECT id, canal_parceiro FROM bm_canais ORDER BY canal_parceiro ASC";
$result_canais = $conn->query($sql_canais);
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
    <h2 class="my-4">Últimos On-Demand</h2>

    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-header">
            <h5><i class="fas fa-filter"></i> Filtros</h5>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <!-- Cliente -->
                <div class="col-md-4">
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

                <!-- Canal -->
                <div class="col-md-4">
                    <label class="form-label">Canal</label>
                    <select name="canal_id" class="form-select">
                        <option value="">Todos os canais</option>
                        <?php 
                        if ($result_canais && $result_canais->num_rows > 0) {
                            $result_canais->data_seek(0);
                            while ($canal = $result_canais->fetch_assoc()) {
                                $selected = ($filtro_canal == $canal['id']) ? 'selected' : '';
                                echo "<option value='{$canal['id']}' $selected>" . htmlspecialchars($canal['canal_parceiro']) . "</option>";
                            }
                        }
                        ?>
                    </select>
                </div>

                <!-- Mês -->
                <div class="col-md-4">
                    <label class="form-label">Mês</label>
                    <select name="mes" class="form-select">
                        <option value="">Todos os meses</option>
                        <?php
                        // últimos 12 meses com base em data_relatorio
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
    // Consulta com filtros
    $sql = "SELECT bm_ondemand.*, 
                   clientes.company AS empresa_nome, 
                   bm_canais.canal_parceiro AS canal_nome, 
                   bm_lives.evento AS evento_nome
            FROM bm_ondemand 
            JOIN clientes ON bm_ondemand.empresa_id = clientes.id 
            JOIN bm_canais ON bm_ondemand.canal_id = bm_canais.id 
            JOIN bm_lives ON bm_ondemand.evento_id = bm_lives.id
            WHERE 1=1";

    $params = [];
    $types  = '';

    if ($filtro_cliente > 0) {
        $sql .= " AND bm_ondemand.empresa_id = ?";
        $params[] = $filtro_cliente;
        $types .= 'i';
    }

    if ($filtro_canal > 0) {
        $sql .= " AND bm_ondemand.canal_id = ?";
        $params[] = $filtro_canal;
        $types .= 'i';
    }

    if (!empty($filtro_mes)) {
        // filtra por ano-mês da data_relatorio
        $sql .= " AND DATE_FORMAT(bm_ondemand.data_relatorio, '%Y-%m') = ?";
        $params[] = $filtro_mes;
        $types .= 's';
    }

    $sql .= " ORDER BY bm_ondemand.id DESC LIMIT 100";

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
                        <th>Evento</th>
                        <th>Cliente</th>
                        <th>Canal Parceiro</th>
                        <th>Mês</th>
                        <th>Ano</th>
                        <th>Visualizações</th>
                        <th>Ação</th>
                    </tr>
                </thead>
                <tbody>";
        while ($row = $result->fetch_assoc()) {
            $mes_relatorio = date("F", strtotime($row["data_relatorio"]));
            $ano_relatorio = date("Y", strtotime($row["data_relatorio"]));

            echo "<tr>
                    <td>{$row['id']}</td>
                    <td>" . htmlspecialchars($row['evento_nome']) . "</td>
                    <td>" . htmlspecialchars($row['empresa_nome']) . "</td>
                    <td>" . htmlspecialchars($row['canal_nome']) . "</td>
                    <td>$mes_relatorio</td>
                    <td>$ano_relatorio</td>
                    <td>{$row['visualizacoes']}</td>
                    <td><a href='../forms/edit_ondemand.php?id={$row['id']}' class='btn btn-primary btn-sm'>Editar</a></td> 
                  </tr>";
        }
        echo "</tbody></table>";
    } else {
        echo "<div class='alert alert-info'>Nenhum registro encontrado" .
             (!empty($filtro_cliente) || !empty($filtro_canal) || !empty($filtro_mes) ? " com os filtros aplicados." : ".") .
             "</div>";
    }

    $stmt->close();
    $conn->close();
    ?>
</div>

<?php include '../pages/footer.php'; ?>
