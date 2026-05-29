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
$filtro_cliente = isset($_GET['cliente_id']) ? intval($_GET['cliente_id']) : 0;
$filtro_empresa = isset($_GET['empresa_id']) ? intval($_GET['empresa_id']) : 0;
$filtro_plataforma = isset($_GET['plataforma']) ? $_GET['plataforma'] : '';

// Buscar clientes para o filtro
$sql_clientes = "SELECT id, company FROM clientes ORDER BY company ASC";
$result_clientes = $conn->query($sql_clientes);

// Buscar empresas para o filtro
$sql_empresas = "SELECT id, empresa FROM empresas ORDER BY empresa ASC";
$result_empresas = $conn->query($sql_empresas);
?>

<div class="container">
    <div class="row">
        <div class="col-6">
            <a href="https://insights.gvacompany.com/pages/registros_bm.php" class="btn btn-secondary">Voltar</a>
        </div>
    </div>
</div>

<div class="container">
    <div class="row justify-content-center">
        <div class="col col-md-10">
            <!-- Formulário de Filtros -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5><i class="fas fa-filter"></i> Filtros</h5>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Cliente</label>
                            <select name="cliente_id" class="form-select">
                                <option value="">Todos os clientes</option>
                                <?php 
                                $result_clientes->data_seek(0);
                                while ($cliente = $result_clientes->fetch_assoc()) {
                                    $selected = ($cliente['id'] == $filtro_cliente) ? 'selected' : '';
                                    echo "<option value='{$cliente['id']}' $selected>" . htmlspecialchars($cliente['company']) . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label">Empresa</label>
                            <select name="empresa_id" class="form-select">
                                <option value="">Todas as empresas</option>
                                <?php 
                                $result_empresas->data_seek(0);
                                while ($empresa = $result_empresas->fetch_assoc()) {
                                    $selected = ($empresa['id'] == $filtro_empresa) ? 'selected' : '';
                                    echo "<option value='{$empresa['id']}' $selected>" . htmlspecialchars($empresa['empresa']) . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label">Plataforma</label>
                            <select name="plataforma" class="form-select">
                                <option value="">Todas as plataformas</option>
                                <option value="Facebook" <?php echo $filtro_plataforma == 'Facebook' ? 'selected' : ''; ?>>Facebook</option>
                                <option value="Instagram" <?php echo $filtro_plataforma == 'Instagram' ? 'selected' : ''; ?>>Instagram</option>
                                <option value="LinkedIn" <?php echo $filtro_plataforma == 'LinkedIn' ? 'selected' : ''; ?>>LinkedIn</option>
                            </select>
                        </div>
                        
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">Filtrar</button>
                            <a href="?<?php echo strtok($_SERVER["REQUEST_URI"], '?'); ?>" class="btn btn-outline-secondary">Limpar</a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tabela de Resultados -->
            <?php
            // Construir query com filtros
            $sql = "SELECT a.*, c.company AS nome_cliente, e.empresa AS nome_empresa 
                    FROM anuncios a
                    JOIN clientes c ON a.cliente_id = c.id
                    JOIN empresas e ON a.empresa_id = e.id
                    WHERE 1=1";
            
            $params = [];
            $types = "";
            
            if ($filtro_cliente > 0) {
                $sql .= " AND a.cliente_id = ?";
                $params[] = $filtro_cliente;
                $types .= "i";
            }
            
            if ($filtro_empresa > 0) {
                $sql .= " AND a.empresa_id = ?";
                $params[] = $filtro_empresa;
                $types .= "i";
            }
            
            if (!empty($filtro_plataforma)) {
                $sql .= " AND a.plataforma = ?";
                $params[] = $filtro_plataforma;
                $types .= "s";
            }
            
            $sql .= " ORDER BY a.inicio_anuncio DESC, a.id DESC";
            
            $stmt = $conn->prepare($sql);
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                echo "<div class='alert alert-info mb-3'>
                        <strong>{$result->num_rows}</strong> registro(s) encontrado(s)";
                
                if (!empty($filtro_cliente) || !empty($filtro_empresa) || !empty($filtro_plataforma)) {
                    echo " com os filtros aplicados.";
                }
                echo "</div>";
                
                echo "<div class='table-responsive'>
                        <table class='table table-striped table-hover'>
                            <thead class='table-dark'>
                                <tr>
                                    <th>ID</th>
                                    <th>Nome Anúncio</th>
                                    <th>Cliente</th>
                                    <th>Empresa</th>
                                    <th>Plataforma</th>
                                    <th>Objetivo</th>
                                    <th>Período</th>
                                    <th>Alcance</th>
                                    <th>Impressões</th>
                                    <th>Cliques</th>
                                    <th>Valor (R$)</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>";
                
                while ($row = $result->fetch_assoc()) {
                    $periodo = date('d/m/Y', strtotime($row['inicio_anuncio'])) . 
                              ' - ' . date('d/m/Y', strtotime($row['termino_anuncio']));
                    
                    echo "<tr>
                            <td>{$row['id']}</td>
                            <td>" . htmlspecialchars(substr($row['nome_anuncio'], 0, 30)) . (strlen($row['nome_anuncio']) > 30 ? '...' : '') . "</td>
                            <td>" . htmlspecialchars($row['nome_cliente']) . "</td>
                            <td>" . htmlspecialchars($row['nome_empresa']) . "</td>
                            <td>" . htmlspecialchars($row['plataforma']) . "</td>
                            <td>" . htmlspecialchars($row['objetivo']) . "</td>
                            <td title='$periodo'>" . substr($periodo, 0, 20) . (strlen($periodo) > 20 ? '...' : '') . "</td>
                            <td>" . number_format($row['alcance'], 0, ',', '.') . "</td>
                            <td>" . number_format($row['impressoes'], 0, ',', '.') . "</td>
                            <td>" . number_format($row['cliques_interacoes'], 0, ',', '.') . "</td>
                            <td>R$ " . number_format($row['valor_gasto'], 2, ',', '.') . "</td>
                            <td>
                                <a href='../forms/edit_anuncios.php?id={$row['id']}' 
                                   class='btn btn-sm btn-primary' title='Editar'>
                                    Editar
                                </a>
                            </td>
                          </tr>";
                }
                echo "</tbody></table></div>";
            } else {
                echo "<div class='alert alert-warning'>
                        <i class='fas fa-search'></i> Nenhum registro encontrado";
                
                if (!empty($filtro_cliente) || !empty($filtro_empresa) || !empty($filtro_plataforma)) {
                    echo " com os filtros aplicados.";
                } else {
                    echo ".";
                }
                echo "</div>";
            }
            
            $stmt->close();
            $conn->close();
            ?>
        </div>
    </div>
</div>

<?php include '../pages/footer.php'; ?>
