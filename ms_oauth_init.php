<?php
/**
 * ms_oauth_init.php
 * Página de início do fluxo OAuth — redireciona para login Microsoft
 * Acesse UMA ÚNICA VEZ como administrador para gerar o refresh_token
 *
 * Após autorizar, você será redirecionado para ms_callback.php
 * e o MS_REFRESH_TOKEN será salvo automaticamente no .env
 */

require_once 'includes/ms_graph.php';

// Somente admins do GVA podem acessar esta página
require_once 'includes/auth.php';
require_login();
if (($_SESSION['usuario_perfil'] ?? '') !== 'admin') {
    http_response_code(403);
    die('Acesso restrito a administradores.');
}

$tenantId    = $_ENV['MS_TENANT_ID']    ?? '';
$clientId    = $_ENV['MS_CLIENT_ID']    ?? '';
$redirectUri = $_ENV['MS_REDIRECT_URI'] ?? '';

if (!$tenantId || !$clientId || !$redirectUri) {
    die('Erro: MS_TENANT_ID, MS_CLIENT_ID e MS_REDIRECT_URI precisam estar configurados no .env');
}

$state = bin2hex(random_bytes(16));
$_SESSION['ms_oauth_state'] = $state;

$params = http_build_query([
    'client_id'     => $clientId,
    'response_type' => 'code',
    'redirect_uri'  => $redirectUri,
    'response_mode' => 'query',
    'scope'         => 'offline_access Tasks.ReadWrite Group.ReadWrite.All',
    'state'         => $state,
]);

$authUrl = "https://login.microsoftonline.com/{$tenantId}/oauth2/v2.0/authorize?{$params}";

header('Location: ' . $authUrl);
exit;
