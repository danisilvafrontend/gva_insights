<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function require_login(): void
{
    if (!isset($_SESSION['user_id'])) {
        header('Location: ../index.php');
        exit;
    }
}

function usuario_logado(): bool
{
    return isset($_SESSION['user_id']);
}

function usuario_id(): int
{
    return (int)($_SESSION['user_id'] ?? 0);
}

function usuario_nome(): string
{
    return $_SESSION['user_nome'] ?? $_SESSION['nome'] ?? 'Usuário';
}

function usuario_nivel(): int
{
    return (int)($_SESSION['nivel_acesso'] ?? 2);
}

/**
 * Nível 1 = Admin (Daniela) — acesso total
 * Nível 2 = Operacional — acesso completo a registros
 */
function is_admin(): bool
{
    return usuario_nivel() === 1;
}

function can_manage_registros(): bool
{
    // Todos os usuários logados (nível 1 e 2) podem gerenciar registros
    return usuario_logado();
}

/**
 * Verifica se o usuário pode editar uma tarefa.
 * Com apenas 2 níveis, qualquer usuário logado pode editar qualquer tarefa.
 * A única restrição real é que somente o admin pode trocar o responsável.
 */
function pode_editar_tarefa(int $idUsuarioResponsavel): bool
{
    return usuario_logado();
}

/**
 * Bloqueia acesso caso o usuário não seja admin (nível 1).
 * Usar no topo de páginas exclusivas de administração.
 */
function require_admin(): void
{
    require_login();
    if (!is_admin()) {
        http_response_code(403);
        exit('Acesso restrito ao administrador.');
    }
}
