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
$filtro_tipocontato = isset($_GET['tipocontato']) ? $_GET['tipocontato'] : '';
$filtro_mes = isset($_GET['mesrelatorio']) ? $_GET['mesrelatorio'] : '';

// Buscar empresas para o filtro
$sql_empresas = "SELECT id, empresa FROM empresas ORDER BY empresa ASC";
$result_empresas = $conn->query($sql_empresas);

// Buscar meses disponíveis para filtro (últimos 12 meses + todos)
$sql_meses = "
    SELECT DISTINCT mesrelatorio 
    FROM contatos_pr 
    WHERE mesrelatorio IS NOT NULL 
    ORDER BY mesrelatorio DESC 
    LIMIT 12";
$result_meses = $conn->query($sql_meses);
?>

<div class="container">
    <div class="row">
        <div class="col-6">
            <a href="../pages/registros_bm.php" class="btn btn-secondary">Voltar</a>
            <a href="../pages/registros_bm.php#contatos_pr" class="btn btn-success ms-2">+ Novo</a>
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
                                $result_empresas->data_seek(0); 
                                while ($empresa = $result_empresas->fetch_assoc()) {
                                    $selected = ($empresa['id'] == $filtro_empresa) ? 'selected' : '';
                                    echo "<option value='{$empresa['id']}' $selected>" . htmlspecialchars($empresa['empresa']) . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label">Tipo Contato</label>
                            <select name="tipocontato" class="form-select">
                                <option value="">Todos</option>
                                <option value="B2B" <?php echo $filtro_tipocontato == 'B2B' ? 'selected' : ''; ?>>B2B</option>
                                <option value="B2C" <?php echo $filtro_tipocontato == 'B2C' ? 'selected' : ''; ?>>B2C</option>
                            </select>
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label">Mês/Ano</label>
                            <select name="mesrelatorio" class="form-select">
                                <option value="">Todos os meses</option>
                                <?php 
                                while ($mes = $result_meses->fetch_assoc()) {
                                    $mesFormatado = DateTime::createFromFormat('Y-m', $mes['mesrelatorio'])->format('m/Y');
                                    $selected = ($mes['mesrelatorio'] == $filtro_mes) ? 'selected' : '';
                                    echo "<option value='{$mes['mesrelatorio']}' $selected>$mesFormatado</option>";
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
            $sql = "SELECT c.*, e.empresa 
                    FROM contatos_pr c
                    JOIN empresas e ON c.empresa_id = e.id
                    WHERE 1=1";
            
            $params = [];
            $types = "";
            
            if ($filtro_empresa > 0) {
                $sql .= " AND c.empresa_id = ?";
                $params[] = $filtro_empresa;
                $types .= "i";
            }
            
            if (!empty($filtro_tipocontato)) {
                $sql .= " AND c.tipocontato = ?";
                $params[] = $filtro_tipocontato;
                $types .= "s";
            }
            
            if (!empty($filtro_mes)) {
                $sql .= " AND c.mesrelatorio = ?";
                $params[] = $filtro_mes;
                $types .= "s";
            }
            
            $sql .= " ORDER BY c.mesrelatorio DESC, c.semana DESC, c.empresa_id";
            
            $stmt = $conn->prepare($sql);
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                echo "<div class='alert alert-info mb-3'>
                        <strong>{$result->num_rows}</strong> registro(s) encontrado(s)";
                
                if (!empty($filtro_empresa) || !empty($filtro_tipocontato) || !empty($filtro_mes)) {
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
                                    <th>Semana</th>
                                    <th>Mês Relatório</th>
                                    <th>Classificação</th>
                                    <th>Tipo Contato</th>
                                    <th>Contatos Realizados</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>";
                
                while ($row = $result->fetch_assoc()) {
                    $mesAno = DateTime::createFromFormat('Y-m', $row['mesrelatorio']);
                    $mesAnoFormatado = $mesAno ? $mesAno->format('m/Y') : $row['mesrelatorio'];
                    
                    echo "<tr>
                            <td>{$row["id"]}</td>
                            <td>" . htmlspecialchars($row["empresa"]) . "</td>
                            <td>{$row["semana"]}</td>
                            <td>{$mesAnoFormatado}</td>
                            <td>" . htmlspecialchars($row["classificacao"]) . "</td>
                            <td>" . htmlspecialchars($row["tipocontato"]) . "</td>
                            <td>" . number_format($row["contatosrealizados"], 0, ',', '.') . "</td>
                            <td>
                                <a href='../forms/edit_contatos_pr.php?id=" . $row["id"] . "' 
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
                
                if (!empty($filtro_empresa) || !empty($filtro_tipocontato) || !empty($filtro_mes)) {
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