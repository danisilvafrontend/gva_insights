<div class="container mt-4">
    <h2>Cadastrar Empresa</h2>
    <form action="../forms/processar_empresa.php" method="POST">
        <div class="form-group form-floating mt-2">
            <input type="text" class="form-control" id="empresa" name="empresa" required>
            <label for="empresa">Nome da Empresa:</label>
        </div>
        <div class="form-group form-floating mt-2">
            <input type="text" class="form-control" id="responsavel" name="responsavel" required>
            <label for="responsavel">Responsável:</label>
        </div>
        <button type="submit" class="btn btn-success mt-3">Cadastrar</button>
    </form>
</div>
