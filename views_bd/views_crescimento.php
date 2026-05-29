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
$filtro_empresa = isset($_GET['empresa_id']) ? intval($_GET['empresa_id']) : 0;
$filtro_plataforma = isset($_GET['plataforma']) ? $_GET['plataforma'] : '';
$filtro_mes = isset($_GET['mes_relatorio']) ? $_GET['mes_relatorio'] : '';

// Buscar empresas para o filtro
$sql_empresas = "SELECT id, empresa FROM empresas ORDER BY empresa ASC";
$result_empresas = $conn->query($sql_empresas);

// Buscar meses disponíveis para filtro (últimos 12 meses + todos)
$sql_meses = "
    SELECT DISTINCT mes_relatorio 
    FROM crescimento_redes_sociais 
    WHERE mes_relatorio IS NOT NULL 
    ORDER BY mes_relatorio DESC 
    LIMIT 12";
$result_meses = $conn->query($sql_meses);
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
                            <label class="form-label">Empresa</label>
                            <select name="empresa_id" class="form-select">
                                <option value="">Todas as empresas</option>
                                <?php 
                                $result_empresas->data_seek(0); // Resetar ponteiro
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
                                <option value="Instagram" <?php echo $filtro_plataforma == 'Instagram' ? 'selected' : ''; ?>>Instagram</option>
                                <option value="Facebook" <?php echo $filtro_plataforma == 'Facebook' ? 'selected' : ''; ?>>Facebook</option>
                                <option value="Linkedin" <?php echo $filtro_plataforma == 'Linkedin' ? 'selected' : ''; ?>>Linkedin</option>
                            </select>
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label">Mês/Ano</label>
                            <select name="mes_relatorio" class="form-select">
                                <option value="">Todos os meses</option>
                                <?php 
                                while ($mes = $result_meses->fetch_assoc()) {
                                    $mesFormatado = DateTime::createFromFormat('Y-m', $mes['mes_relatorio'])->format('m/Y');
                                    $selected = ($mes['mes_relatorio'] == $filtro_mes) ? 'selected' : '';
                                    echo "<option value='{$mes['mes_relatorio']}' $selected>$mesFormatado</option>";
                                }
                                ?>
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
            $sql = "SELECT crs.*, e.empresa 
                    FROM crescimento_redes_sociais crs
                    JOIN empresas e ON crs.empresa_id = e.id
                    WHERE 1=1";
            
            $params = [];
            $types = "";
            
            if ($filtro_empresa > 0) {
                $sql .= " AND crs.empresa_id = ?";
                $params[] = $filtro_empresa;
                $types .= "i";
            }
            
            if (!empty($filtro_plataforma)) {
                $sql .= " AND crs.plataforma = ?";
                $params[] = $filtro_plataforma;
                $types .= "s";
            }
            
            if (!empty($filtro_mes)) {
                $sql .= " AND crs.mes_relatorio = ?";
                $params[] = $filtro_mes;
                $types .= "s";
            }
            
            $sql .= " ORDER BY crs.mes_relatorio DESC, crs.empresa_id, crs.plataforma, crs.semana";
            
            $stmt = $conn->prepare($sql);
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                echo "<div class='alert alert-info mb-3'>
                        <strong>{$result->num_rows}</strong> registro(s) encontrado(s)";
                
                if (!empty($filtro_empresa) || !empty($filtro_plataforma) || !empty($filtro_mes)) {
                    echo " com os filtros aplicados.";
                }
                echo "</div>";
                echo '<div class="mb-3">
                    <button id="exportCsv" class="btn btn-success">Exportar CSV</button>
                </div>';

                echo "<div class='table-responsive'>
                        <table class='table table-striped table-hover'>
                            <thead class='table-dark'>
                                <tr>
                                    <th>ID</th>
                                    <th>Empresa</th>
                                    <th>Plataforma</th>
                                    <th>Semana</th>
                                    <th>Número de Seguidores</th>
                                    <th>Mês Relatório</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>";
                
                while ($row = $result->fetch_assoc()) {
                    $mesAno = DateTime::createFromFormat('Y-m', $row['mes_relatorio']);
                    $mesAnoFormatado = $mesAno ? $mesAno->format('m/Y') : $row['mes_relatorio'];
                    
                    echo "<tr>
                            <td>{$row["id"]}</td>
                            <td>" . htmlspecialchars($row["empresa"]) . "</td>
                            <td>" . htmlspecialchars($row["plataforma"]) . "</td>
                            <td>0{$row["semana"]}</td>
                            <td>" . number_format($row["numero_seguidores"], 0, ',', '.') . "</td>
                            <td>{$mesAnoFormatado}</td>
                            <td>
                                <a href='../forms/edit_crescimento.php?id=" . $row["id"] . "' 
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
                
                if (!empty($filtro_empresa) || !empty($filtro_plataforma) || !empty($filtro_mes)) {
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
