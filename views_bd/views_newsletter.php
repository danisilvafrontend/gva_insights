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
<meta charset="UTF-8">

<div class="container">
    <div class="row">
        <div class="col-6">
            <a href="https://insights.gvacompany.com/pages/registros_bm.php" class="btn btn-secondary">Voltar</a>
        </div>
    </div>
</div>

<div class="container">
    <h2 class="my-4">Últimas Newsletters</h2>
    
    <!-- Formulário de filtros -->
    <form method="GET" class="mb-4 row g-3">
        <!-- Filtro Empresa -->
        <div class="col-md-3">
            <label class="form-label">Empresa</label>
            <select name="filter_empresa" class="form-select">
                <option value="">Todas as Empresas</option>
                <?php
                $empresas_query = $conn->query("SELECT DISTINCT id, empresa FROM empresas ORDER BY empresa");
                while ($empresa = $empresas_query->fetch_assoc()) {
                    $selected = (isset($_GET['filter_empresa']) && $_GET['filter_empresa'] == $empresa['id']) ? 'selected' : '';
                    echo "<option value='{$empresa['id']}' $selected>{$empresa['empresa']}</option>";
                }
                ?>
            </select>
        </div>

        <!-- Filtro Cliente -->
        <div class="col-md-3">
            <label class="form-label">Cliente</label>
            <select name="filter_cliente" class="form-select">
                <option value="">Todos os Clientes</option>
                <?php
                $clientes_query = $conn->query("SELECT DISTINCT id, company FROM clientes ORDER BY company");
                while ($cliente = $clientes_query->fetch_assoc()) {
                    $selected = (isset($_GET['filter_cliente']) && $_GET['filter_cliente'] == $cliente['id']) ? 'selected' : '';
                    echo "<option value='{$cliente['id']}' $selected>{$cliente['company']}</option>";
                }
                ?>
            </select>
        </div>

        <!-- Filtro Tema -->
        <div class="col-md-3">
            <label class="form-label">Tema</label>
            <select name="filter_tema" class="form-select">
                <option value="">Todos os Temas</option>
                <?php
                $temas_query = $conn->query("SELECT DISTINCT id, tema FROM temas ORDER BY tema");
                while ($tema = $temas_query->fetch_assoc()) {
                    $selected = (isset($_GET['filter_tema']) && $_GET['filter_tema'] == $tema['id']) ? 'selected' : '';
                    echo "<option value='{$tema['id']}' $selected>{$tema['tema']}</option>";
                }
                ?>
            </select>
        </div>

        <!-- Filtro Mês/Ano -->
        <div class="col-md-2">
            <label class="form-label">Mês/Ano</label>
            <input type="month" name="filter_mesano" class="form-control" 
                   value="<?php echo isset($_GET['filter_mesano']) ? $_GET['filter_mesano'] : ''; ?>">
        </div>

        <!-- Botões -->
        <div class="col-md-1 d-flex align-items-end">
            <button type="submit" class="btn btn-primary me-2">Filtrar</button>
            <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn btn-secondary">Limpar</a>
        </div>
    </form>

    <?php
    // Processar filtros
    $where_conditions = [];
    $params = [];

    if (!empty($_GET['filter_empresa'])) {
        $where_conditions[] = "n.empresa_id = ?";
        $params[] = intval($_GET['filter_empresa']);
    }

    if (!empty($_GET['filter_cliente'])) {
        $where_conditions[] = "nc.id_cliente = ?";
        $params[] = intval($_GET['filter_cliente']);
    }

    if (!empty($_GET['filter_tema'])) {
        $where_conditions[] = "nt.id_tema = ?";
        $params[] = intval($_GET['filter_tema']);
    }

    if (!empty($_GET['filter_mesano'])) {
        $ano = substr($_GET['filter_mesano'], 0, 4);
        $mes = substr($_GET['filter_mesano'], 5, 2);
        $where_conditions[] = "(YEAR(n.data_envio) = ? AND MONTH(n.data_envio) = ?)";
        $params[] = $ano;
        $params[] = $mes;
    }

    $where_sql = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

    // Consulta SQL principal com prepared statement
    $sql = "SELECT 
                n.*, 
                e.empresa AS nome_empresa, 
                GROUP_CONCAT(DISTINCT c.company SEPARATOR ', ') AS clientes_nomes,
                GROUP_CONCAT(DISTINCT t.tema SEPARATOR ', ') AS temas_nomes
            FROM bm_newsletter n
            LEFT JOIN empresas e ON n.empresa_id = e.id
            LEFT JOIN newsletter_clientes nc ON n.id = nc.id_newsletter
            LEFT JOIN clientes c ON nc.id_cliente = c.id
            LEFT JOIN newsletter_temas nt ON n.id = nt.id_newsletter
            LEFT JOIN temas t ON nt.id_tema = t.id
            $where_sql
            GROUP BY n.id
            ORDER BY n.data_envio DESC 
            LIMIT 100";

    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $types = str_repeat('i', count($params)); // Todos parâmetros são inteiros
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo "<div class='alert alert-info mb-3'>
                <strong>{$result->num_rows}</strong> registro(s) encontrado(s).
              </div>";
        
        echo "<table class='table table-striped table-hover'>
                <thead class='table-dark'>
                    <tr>
                        <th>ID</th>
                        <th>Nome da Newsletter</th>
                        <th>Temas</th>
                        <th>Empresa</th>
                        <th>Data de Envio</th>
                        <th>E-mails Entregues</th>
                        <th>Aberturas Únicas</th>
                        <th>Cliques Únicos</th>
                        <th>Cancelamentos</th>
                        <th>Clientes</th>
                        <th>Ação</th>
                    </tr>
                </thead>
                <tbody>";
        
        while ($row = $result->fetch_assoc()) {
            $data_envio = date("d/m/Y", strtotime($row["data_envio"])); 
            
            echo "<tr>
                    <td>{$row['id']}</td>
                    <td>{$row['nome_newsletter']}</td>
                    <td>" . (empty($row['temas_nomes']) ? '-' : $row['temas_nomes']) . "</td>
                    <td>{$row['nome_empresa']}</td>
                    <td>{$data_envio}</td>
                    <td>{$row['emails_entregues']}</td>
                    <td>{$row['aberturas_unicas']}</td>
                    <td>{$row['cliques_unicos']}</td>
                    <td>{$row['cancelamento']}</td>
                    <td>" . (empty($row['clientes_nomes']) ? '-' : $row['clientes_nomes']) . "</td>
                    <td>
                        <a href='../forms/edit_newsletter.php?id={$row['id']}' class='btn btn-primary btn-sm'>Editar</a>
                    </td>
                  </tr>";
        }
        echo "</tbody></table>";
    } else {
        echo "<div class='alert alert-info'>
                <h5>Nenhum registro encontrado.</h5>
                <p>Tente ajustar os filtros ou remova alguns para ver mais resultados.</p>
              </div>";
    }

    $stmt->close();
    $conn->close();
    ?>
</div>

<?php include '../pages/footer.php'; ?>
