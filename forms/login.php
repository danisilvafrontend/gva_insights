<div class="container mt-4 mb-4">
    <div class="row justify-content-center">
        <div class="col col-md-6 text-center">                                
            <h2>Por favor insira seu e-mail e senha:</h2>                                
        </div>
    </div>

    <div class="row justify-content-center mt-4">
        <div class="col col-md-4 text-center">                                
            <form action="../forms/verificar_login.php" method="POST">
                <div class="form-group form-floating">
                    <input type="email" id="email" class="form-control" name="email" placeholder="name@example.com" required>
                    <label for="email">E-mail</label>
                </div>
                <div class="form-group form-floating mt-3">
                    <input type="password" id="senha" class="form-control" name="senha" placeholder="Senha" required>
                    <label for="senha">Senha</label>
                </div>
                <button type="submit" class="btn btn-primary mt-3">Entrar</button>
            </form>                             
        </div>
    </div>
</div>
