<?php
/**
 * agenda.php
 * Página intermediária de redirecionamento para o Outlook.
 * Tenta abrir o app Outlook (ms-outlook://) via JS;
 * se falhar após 1,5s, redireciona para o Outlook Web.
 *
 * Parâmetros GET:
 *   subject  — título do evento
 *   date     — YYYY-MM-DD
 *   desc     — descrição (opcional)
 */

$subject = htmlspecialchars($_GET['subject'] ?? 'Tarefa Brasil DNA 2026');
$date    = $_GET['date'] ?? '';
$desc    = htmlspecialchars($_GET['desc'] ?? '');

// Monta URLs
$startDt = '';
$endDt   = '';
$webUrl  = '';
$appUrl  = '';

if (!empty($date) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    $startDt = $date . 'T09:00:00';
    $endDt   = $date . 'T10:00:00';

    // Outlook Web
    $webUrl = 'https://outlook.office.com/calendar/0/deeplink/compose?' . http_build_query([
        'path'    => '/calendar/action/compose',
        'rru'     => 'addevent',
        'subject' => urldecode($subject),
        'startdt' => $startDt,
        'enddt'   => $endDt,
        'body'    => urldecode($desc),
    ]);

    // Outlook App (protocolo ms-outlook — Windows e Mac)
    $appUrl = 'ms-outlook://events/new?'
            . 'subject='  . rawurlencode(urldecode($subject))
            . '&startdt=' . rawurlencode($startDt)
            . '&enddt='   . rawurlencode($endDt)
            . '&body='    . rawurlencode(urldecode($desc));
} else {
    // Sem data — vai direto para Outlook Web sem parâmetros
    $webUrl = 'https://outlook.office.com/calendar/0/deeplink/compose?rru=addevent&subject=' . rawurlencode(urldecode($subject));
    $appUrl = 'ms-outlook://events/new?subject=' . rawurlencode(urldecode($subject));
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Abrindo Agenda...</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #f3f2f1;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 24px;
        }
        .card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.1);
            padding: 40px 32px;
            max-width: 480px;
            width: 100%;
            text-align: center;
        }
        .icon { font-size: 48px; margin-bottom: 16px; }
        h1 { font-size: 20px; color: #323130; margin-bottom: 8px; }
        p  { font-size: 14px; color: #605e5c; margin-bottom: 24px; line-height: 1.5; }
        .btn {
            display: inline-block;
            padding: 10px 24px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: background 0.15s;
        }
        .btn-primary { background: #0078d4; color: #fff; margin-bottom: 12px; width: 100%; }
        .btn-primary:hover { background: #106ebe; }
        .btn-secondary { background: #f3f2f1; color: #323130; width: 100%; border: 1px solid #d2d0ce; }
        .btn-secondary:hover { background: #edebe9; }
        .divider { font-size: 12px; color: #a19f9d; margin: 12px 0; }
        #status { font-size: 12px; color: #a19f9d; margin-top: 16px; min-height: 18px; }
    </style>
</head>
<body>
<div class="card">
    <div class="icon">📅</div>
    <h1>Adicionar à Agenda</h1>
    <p><strong><?= $subject ?></strong><?= !empty($date) ? '<br>Deadline: ' . date('d/m/Y', strtotime($date)) : '' ?></p>

    <a href="<?= $appUrl ?>" class="btn btn-primary" id="btnApp" onclick="tryApp()">🖥️ Abrir no Outlook App</a>
    <div class="divider">ou</div>
    <a href="<?= $webUrl ?>" class="btn btn-secondary" target="_blank">🌐 Abrir no Outlook Web</a>

    <div id="status"></div>
</div>

<script>
function tryApp() {
    const status = document.getElementById('status');
    status.textContent = 'Tentando abrir o Outlook App...';
    // Se após 2s ainda estiver na página, o app não abriu
    setTimeout(function() {
        status.textContent = 'App não encontrado? Use o botão "Outlook Web" acima.';
    }, 2000);
}
</script>
</body>
</html>
