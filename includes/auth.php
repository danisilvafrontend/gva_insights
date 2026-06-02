<?php
/**
 * includes/auth.php
 * Controle de autenticação e autorização — GVA Insights
 *
 * Níveis de acesso (coluna nivel_acesso na tabela usuarios):
 *   1 → Admin total  : acesso a tudo na aplicação (Daniela - id 1)
 *   2 → Operacional  : cria/edita qualquer tarefa e subtarefa
 *   3 → Restrito     : visualiza e edita apenas tarefas destinadas a si
 *
 * Uso básico:
 *   require_once '../includes/auth.php';
 *   require_login();               // redireciona se não logado
 *   require_nivel(2);              // bloqueia nível 3
 *   pode_editar_tarefa($id_resp);  // true/false por nível
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ---------------------------------------------------------------------------
// Verificação de sessão
// ---------------------------------------------------------------------------

/**
 * Redireciona para o login se o usuário não estiver autenticado.
 * Ajuste o caminho de acordo com a profundidade do arquivo que inclui este.
 */
function require_login(string $caminho_login = '../index.php'): void
{
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . $caminho_login);
        exit;
    }
}

/** Retorna true se o usuário está logado. */
function usuario_logado(): bool
{
    return isset($_SESSION['user_id']);
}

// ---------------------------------------------------------------------------
// Getters de sessão
// ---------------------------------------------------------------------------

/** ID do usuário logado (int). */
function usuario_id(): int
{
    return (int)($_SESSION['user_id'] ?? 0);
}

/** Nome do usuário logado. */
function usuario_nome(): string
{
    return $_SESSION['user_nome']   // chave preferencial
        ?? $_SESSION['nome']        // fallback legado
        ?? 'Usuário';
}

/**
 * Nível de acesso do usuário logado.
 * Retorna 3 (mais restrito) como padrão seguro se não estiver na sessão.
 */
function usuario_nivel(): int
{
    return (int)($_SESSION['nivel_acesso'] ?? 3);
}

// ---------------------------------------------------------------------------
// Helpers de autorização
// ---------------------------------------------------------------------------

/** Nível 1: administrador com acesso total. */
function is_admin(): bool
{
    return usuario_nivel() === 1;
}

/**
 * Níveis 1 e 2: pode criar, editar e deletar qualquer tarefa/subtarefa.
 * Nível 3 não passa nesta verificação.
 */
function can_manage_registros(): bool
{
    return in_array(usuario_nivel(), [1, 2], true);
}

/**
 * Verifica se o usuário logado pode editar determinada tarefa.
 *
 * @param int $id_usuario_responsavel  ID do responsável pela tarefa
 * @return bool
 */
function pode_editar_tarefa(int $id_usuario_responsavel): bool
{
    if (!usuario_logado()) {
        return false;
    }

    $nivel = usuario_nivel();

    // Níveis 1 e 2 editam qualquer tarefa
    if ($nivel === 1 || $nivel === 2) {
        return true;
    }

    // Nível 3: apenas tarefas atribuídas ao próprio usuário
    return usuario_id() === $id_usuario_responsavel;
}

/**
 * Bloqueia o acesso caso o usuário não tenha o nível mínimo exigido.
 * Menor número = maior privilégio (1 > 2 > 3).
 *
 * Exemplos:
 *   require_nivel(1)  → somente admin
 *   require_nivel(2)  → níveis 1 e 2
 *
 * @param int    $nivel_minimo     Nível máximo permitido (1=mais restrito ao admin)
 * @param string $caminho_login    Para onde redirecionar usuários não logados
 */
function require_nivel(int $nivel_minimo, string $caminho_login = '../index.php'): void
{
    require_login($caminho_login);

    if (usuario_nivel() > $nivel_minimo) {
        http_response_code(403);
        exit('Acesso negado. Você não tem permissão para acessar esta página.');
    }
}

// ---------------------------------------------------------------------------
// Utilitário para popular a sessão após login
// ---------------------------------------------------------------------------
/**
 * Exemplo de uso no processo de login (login.php / processar_login.php):
 *
 *   $_SESSION['user_id']      = (int)$usuario['id'];
 *   $_SESSION['user_nome']    = $usuario['nome'];
 *   $_SESSION['nivel_acesso'] = (int)$usuario['nivel_acesso'];
 *
 * Lembre de buscar o campo nivel_acesso no SELECT do login:
 *   SELECT id, nome, senha, nivel_acesso FROM usuarios WHERE email = ? AND ativo = 1
 */
