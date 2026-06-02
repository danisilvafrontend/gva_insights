<?php
/**
 * includes/auth.php
 * Controle de autenticação e autorização — GVA Insights
 *
 * Níveis de acesso (coluna nivel_acesso na tabela usuarios):
 *   1 → Admin       : acesso total a tudo na aplicação
 *   2 → Operacional : visualiza index e tarefas, edita apenas as próprias
 *
 * Uso básico:
 *   require_once '../includes/auth.php';
 *   require_login();                     // redireciona se não logado
 *   require_admin();                     // bloqueia não-admin
 *   pode_editar_tarefa($id_responsavel); // true/false por nível
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ---------------------------------------------------------------------------
// Verificação de sessão
// ---------------------------------------------------------------------------

/**
 * Redireciona para o login se o usuário não estiver autenticado.
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
    return $_SESSION['user_nome']
        ?? $_SESSION['nome']
        ?? 'Usuário';
}

/**
 * Nível de acesso do usuário logado.
 * Retorna 2 (operacional / mais restrito) como padrão seguro.
 *   1 = Admin
 *   2 = Operacional
 */
function usuario_nivel(): int
{
    return (int)($_SESSION['nivel_acesso'] ?? 2);
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
 * Alias mantido por compatibilidade com arquivos existentes.
 * Admin e Operacional podem criar registros — a diferença está em
 * QUAIS registros cada um pode editar/deletar.
 */
function can_manage_registros(): bool
{
    return usuario_logado();
}

/**
 * Verifica se o usuário logado pode editar determinada tarefa.
 *
 * Admin (1)       → edita qualquer tarefa.
 * Operacional (2) → edita apenas tarefas onde ele é o responsável.
 *
 * @param int $id_usuario_responsavel  ID do usuário responsável pela tarefa
 */
function pode_editar_tarefa(int $id_usuario_responsavel): bool
{
    if (!usuario_logado()) {
        return false;
    }

    // Admin edita tudo
    if (is_admin()) {
        return true;
    }

    // Operacional: apenas as próprias tarefas
    return usuario_id() === $id_usuario_responsavel;
}

/**
 * Bloqueia o acesso caso o usuário não seja admin.
 * Use nas páginas exclusivas de administração.
 */
function require_admin(string $caminho_login = '../index.php'): void
{
    require_login($caminho_login);

    if (!is_admin()) {
        http_response_code(403);
        exit('Acesso negado. Esta área é restrita a administradores.');
    }
}

/**
 * Bloqueia se o usuário não tiver o nível mínimo exigido.
 * Mantido por compatibilidade — prefira require_admin() quando possível.
 *
 * Exemplo: require_nivel(1) → somente admin
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
// Utilitário — popular sessão após login
// ---------------------------------------------------------------------------
/**
 * No processo de login (processar_login.php), grave na sessão:
 *
 *   $_SESSION['user_id']      = (int)$usuario['id'];
 *   $_SESSION['user_nome']    = $usuario['nome'];
 *   $_SESSION['nivel_acesso'] = (int)$usuario['nivel_acesso'];  // 1 ou 2
 *
 * SELECT necessário no login:
 *   SELECT id, nome, senha, nivel_acesso FROM usuarios WHERE email = ? AND ativo = 1
 */
