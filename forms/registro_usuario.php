<meta charset="UTF-8">
<?php
require_once '../includes/auth.php';
require_nivel(1); // somente admin (nível 1) pode cadastrar usuários
?>

<h2>Cadastro de Usuário</h2>
<form action="../forms/processar_usuario.php" method="POST">

    <div class="form-group form-floating mt-2">
        <input type="text" class="form-control" name="nome" id="nome" placeholder="Nome" required>
        <label for="nome">Nome:</label>
    </div>

    <div class="form-group form-floating mt-2">
        <input type="email" class="form-control" name="email" id="email" placeholder="email@exemplo.com" required>
        <label for="email">Email:</label>
    </div>

    <div class="form-group form-floating mt-2">
        <input type="password" class="form-control" name="senha" id="senha" placeholder="Senha" required>
        <label for="senha">Senha:</label>
    </div>

    <div class="form-group form-floating mt-2">
        <select class="form-select" name="nivel_acesso" id="nivel_acesso" required>
            <option value="" disabled selected>Selecione o nível de acesso</option>
            <option value="1">Nível 1 — Administrador (acesso total)</option>
            <option value="2">Nível 2 — Operacional (cria e edita tarefas)</option>
            <option value="3" selected>Nível 3 — Restrito (somente suas tarefas)</option>
        </select>
        <label for="nivel_acesso">Nível de Acesso:</label>
    </div>

    <button type="submit" class="btn btn-success mt-3">Cadastrar</button>
</form>
