<?php
/**
 * ms_callback.php
 * Callback OAuth — troca o authorization code pelo refresh_token
 * Configurar como Redirect URI no Azure AD:
 *   https://seudominio.com/ms_callback.php
 */

session_start();
require_once 'includes/ms_graph.php';

$code  = $_GET['code']  ?? '';
$state = $_GET['state'] ?? '';
$error = $_GET['error'] ?? '';

// Verifica state para prevenir CSRF
if ($state !== ($_SESSION['ms_oauth_state'] ?? '')) {
    http_response_code(400);
    die('State inválido. Possível ataque CSRF. Tente novamente.');
}
unset($_SESSION['ms_oauth_state']);

if ($error) {
    $desc = $_GET['error_description'] ?? $error;
    http_response_code(400);
    die('Erro OAuth: ' . htmlspecialchars($desc));
}

if (!$code) {
    http_response_code(400);
    die('Código de autorização não recebido.');
}

try {
    $data = ms_exchange_code($code);

    if (empty($data['refresh_token'])) {
        throw new RuntimeException('refresh_token não retornado pela Microsoft. Verifique se o escopo offline_access está habilitado.');
    }

    echo '<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Autorização concluída</title>
<style>
  body { font-family: sans-serif; display: flex; align-items: center; justify-content: center;
         min-height: 100vh; margin: 0; background: #f0fdf4; }
  .card { background: #fff; border-radius: 12px; padding: 40px 48px; box-shadow: 0 4px 24px rgba(0,0,0,.08);
          text-align: center; max-width: 480px; }
  .icon { font-size: 3rem; margin-bottom: 16px; }
  h1 { color: #166534; margin: 0 0 12px; }
  p  { color: #555; line-height: 1.6; }
  a  { display: inline-block; margin-top: 24px; padding: 10px 28px; background: #166534;
       color: #fff; border-radius: 8px; text-decoration: none; font-weight: 600; }
  a:hover { background: #14532d; }
</style>
</head>
<body>
<div class="card">
  <div class="icon">✅</div>
  <h1>Autorização concluída!</h1>
  <p>O <strong>refresh_token</strong> foi salvo com sucesso no <code>.env</code>.<br>
     A integração com o Microsoft Planner está pronta para uso.</p>
  <a href="/demandas/">Voltar para Demandas</a>
</div>
</body>
</html>';

} catch (RuntimeException $e) {
    http_response_code(500);
    echo '<h2 style="color:red">Erro: ' . htmlspecialchars($e->getMessage()) . '</h2>';
    echo '<p><a href="/ms_oauth_init.php">Tentar novamente</a></p>';
}
