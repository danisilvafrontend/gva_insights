<?php 
header('Content-Type: text/html; charset=utf-8');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GVA Insights</title>
    <link href="../assets/style.css" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>

  </head>
</head>
<body>
    <div class="container mt-4 mb-4">
        <div class="d-flex align-items-center">
            <div class="p-2 flex-grow-1">
                <a href="https://insights.gvacompany.com/pages/home.php"> <img class="img-fluid logotipo" src="http://bureaumundo.com/wp-content/uploads/2023/11/GVA-COMPANY-COLOR-LOGO-SECUNDARY.png" alt="GVA Company"/></a>
            </div>
            <?php if (isset($_SESSION['user_id'])): ?>
            <div class="p-2">
                <a href="minha_conta.php" class="btn black align-self-center">Alterar Senha</a>
            </div>
            <div class="p-2">
                <a class="btn btn-danger  align-self-center" href="../includes/logout.php"> Logout</a>
            </div>
            <?php endif; ?>
        </div>
    </div>


