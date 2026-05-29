<?php

include 'header.php'; ?>

<div class="container mt-5" id="top">
    <div class="row justify-content-center mt-4 align-middle">
        <div class="col col-md-6 text-center">
            <h1>Report Clientes</h1>
        </div>
        <div class="col col-md-2 text-right">
            <a href="home.php" class="btn black">Voltar</a>
        </div>
    </div>
</div>
<div class="container sticky-top bg-white mt-5">
    <nav class="navbar">
    <div class="container-fluid">
        <a class="navbar-brand" href="#newstrade">Newsletter Trade</a>
        <a class="navbar-brand" href="#pressRelease">PressRelease</a>
        <a class="navbar-brand" href="#midiasSociais">Mídias Sociais</a>
    </div>
    </nav>
</div>
<div class="container bg-cinza mt-5 mb-5 pt-5 pb-5" id="newstrade">
<div class="row justify-content-center mt-4 align-middle">
        <div class="col col-md-6">
            <h3 class=" text-center">Newsletters</h3>
            <?php include '../forms/registrar_newsletter.php'; ?>
        </div>
    </div>
    <div class="row justify-content-center">
        <div class="col col-md-6 mt-5">
            <a href="../views_bd/views_newsletter.php" class="btn black">Ver últimos Registros</a>
        </div>
    </div>
</div>

<div class="container bg-cinza mt-5 mb-5 pt-5 pb-5" id="pressRelease">
    <div class="row justify-content-center mt-4 align-middle">
        <div class="col col-md-6">
            <h3 class=" text-center">Press Release</h3>
            <p>And Travel Updates</p>
            <?php include '../forms/registrar_press_release.php'; ?>
        </div>
    </div>
    <div class="row justify-content-center">
        <div class="col col-md-6 mt-5">
            <a href="../views_bd/views_press_release.php" class="btn black">Ver últimos Registros</a>
        </div>
    </div>
</div>

<div class="container bg-cinza mt-5 mb-5 pt-5 pb-5" id="midiasSociais">
    <div class="row justify-content-center mt-4 align-middle">
        <div class="col col-md-6">
            <h3 class=" text-center">Mídias Sociais</h3>
            <?php include '../forms/registrar_media_report.php'; ?>
        </div>
    </div>
    <div class="row justify-content-center">
        <div class="col col-md-6 mt-5">
            <a href="../views_bd/views_media_report.php" class="btn black">Ver últimos Registros</a>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>