
    <div class="container">
        <h2>Cadastro de Clientes</h2>
        <form action="../forms/processar_cliente.php" method="POST">
            <div class="form-group form-floating mt-2">
                <input type="text" class="form-control" id="name" name="name" placeholder="Nome do Responsável" required>
                <label for="name">Nome:</label>
            </div>
            <div class="form-group form-floating mt-2">
                <input type="text" class="form-control" id="company" name="company" placeholder="Empresa" required>
                <label for="company">Empresa:</label>
            </div>
            <button type="submit" class="btn btn-success mt-2">Cadastrar</button>
        </form>
    </div>
