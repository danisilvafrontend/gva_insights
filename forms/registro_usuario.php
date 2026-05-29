
<meta charset="UTF-8">
<h2>Cadastro de Usuário</h2>
    <form action="../forms/processar_usuario.php" method="POST">
        <div class="form-group form-floating mt-2">
            <input type="text" class="form-control" name="nome" id="nome" required>  
            <label for="nome">Nome:</label>      
        </div>
        <div class="form-group form-floating mt-2">
            <input type="email" class="form-control" name="email" id="email" required> 
            <label for="email">Email:</label> 
        </div>  

        <div class="form-group form-floating mt-2">
            <input type="password" class="form-control" name="senha" id="senha" required>
            <label for="senha">Senha:</label>
        </div> 

        <button type="submit" class="btn btn-success mt-2">Cadastrar</button>
    </form>

