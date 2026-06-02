<?php
$servername = "127.0.0.1";
$username = "globalvisionacce_dani";
$password = "D2ewb@0001";
$dbname = "globalvisionacce_gva_database"; // Use o nome do seu banco de dados
$port = 3306;

// Criar a conexão
$conn = new mysqli($servername, $username, $password, $dbname, $port);

// Verificar a conexão
if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}

// ============================================================
// CONFIGURAÇÕES DE E-MAIL — Office365
// ============================================================
define('MAIL_HOST',      'smtp.office365.com');
define('MAIL_PORT',      587);
define('MAIL_USER',      'dani@globalvisionaccess.com');  // e-mail completo M365
define('MAIL_PASS',      'BQabz@O2');
define('MAIL_FROM',      'dani@globalvisionaccess.com');
define('MAIL_FROM_NAME', 'Brasil DNA 2026 | GVA');

define('TEAMS_WEBHOOK_URL', 'https://default9a4397a1034f4bfcac235b1105e773.39.environment.api.powerplatform.com:443/powerautomate/automations/direct/workflows/5887232953e84d998577ffa936a578a4/triggers/manual/paths/invoke?api-version=1&sp=%2Ftriggers%2Fmanual%2Frun&sv=1.0&sig=W3ZkaWcntdmfMWU_WKyuBH1ExAyFreyN33m87IJRVnY');

define('TEAMS_CHAT_WEBHOOKS', [
    4 => 'https://default9a4397a1034f4bfcac235b1105e773.39.environment.api.powerplatform.com:443/powerautomate/automations/direct/workflows/519a5232b5074b71a8302b439c3781c9/triggers/manual/paths/invoke?api-version=1&sp=%2Ftriggers%2Fmanual%2Frun&sv=1.0&sig=62ZKiQT5FN43RRPd_RJqjNja7BThR2DtV4ekNob-Ass',
    1 => 'https://default9a4397a1034f4bfcac235b1105e773.39.environment.api.powerplatform.com:443/powerautomate/automations/direct/workflows/83e653e57e7449149a414ed136222720/triggers/manual/paths/invoke?api-version=1&sp=%2Ftriggers%2Fmanual%2Frun&sv=1.0&sig=RbZV0p3NEOcn6GW8IX1c8zR5Qf1VYSKoT9DEZKJHrws',
    2 => 'https://default9a4397a1034f4bfcac235b1105e773.39.environment.api.powerplatform.com:443/powerautomate/automations/direct/workflows/101abcdb224f486c8a9192f4d7f5a337/triggers/manual/paths/invoke?api-version=1&sp=%2Ftriggers%2Fmanual%2Frun&sv=1.0&sig=x_ebrIVHbrYYqJFLeOY6dPCllI0V_MJiXxYpU8phxpg',
]);

// TESTE — remover após confirmar
$testUrl = TEAMS_CHAT_WEBHOOKS[4] ?? '';
$ch = curl_init($testUrl);
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode(['type' => 'message', 'text' => 'Teste Brasil DNA ✅']),
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 10,
]);
$r = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
error_log("TEAMS CHAT TEST — HTTP $code — $r");
?>
