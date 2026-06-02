<?php
/**
 * agenda.php
 * Página intermediária para adicionar evento ao Outlook.
 * Usa links https:// oficiais da Microsoft — o Novo Outlook
 * intercepta automaticamente e abre no app quando configurado.
 *
 * Parâmetros GET:
 *   subject  — título do evento
 *   date     — YYYY-MM-DD
 *   desc     — descrição (opcional)
 */

$subject = $_GET['subject'] ?? 'Tarefa Brasil DNA 2026';
$date    = $_GET['date']    ?? '';
$desc    = $_GET['desc']    ?? '';

$startDt = '';
$endDt   = '';
$urlWork = ''; // Microsoft 365 / Outlook corporativo
$urlLive = ''; // Outlook.com / conta pessoal

if (!empty($date) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    $startDt = $date . 'T09:00:00';
    $endDt   = $date . 'T10:00:00';

    // Microsoft 365 corporativo (office.com)
    $urlWork = 'https://outlook.office.com/calendar/0/deeplink/compose?' . http_build_query([
        'path'    => '/calendar/action/compose',
        'rru'     => 'addevent',
        'subject' => $subject,
        'startdt' => $startDt,
        'enddt'   => $endDt,
        'body'    => $desc,
    ]);

    // Outlook.com (conta pessoal / fallback)
    $urlLive = 'https://outlook.live.com/calendar/0/deeplink/compose?' . http_build_query([
        'path'    => '/calendar/action/compose',
        'rru'     => 'addevent',
        'subject' => $subject,
        'startdt' => $startDt,
        'enddt'   => $endDt,
        'body'    => $desc,
    ]);
} else {
    $urlWork = 'https://outlook.office.com/calendar/0/deeplink/compose?rru=addevent&subject=' . rawurlencode($subject);
    $urlLive = 'https://outlook.live.com/calendar/0/deeplink/compose?rru=addevent&subject='   . rawurlencode($subject);
}

$deadlineFormatado = !empty($date) ? date('d/m/Y', strtotime($date)) : '';
$subjectSafe       = htmlspecialchars($subject);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adicionar à Agenda</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
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
            box-shadow: 0 2px 16px rgba(0,0,0,0.1);
            padding: 40px 32px;
            max-width: 460px;
            width: 100%;
            text-align: center;
        }
        .icon { font-size: 52px; margin-bottom: 16px; }
        h1 { font-size: 20px; color: #323130; margin-bottom: 6px; }
        .subtitle {
            font-size: 13px;
            color: #605e5c;
            margin-bottom: 28px;
            line-height: 1.5;
        }
        .subtitle strong { color: #323130; }
        .btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            padding: 12px 20px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: background 0.15s, box-shadow 0.15s;
            margin-bottom: 10px;
        }
        .btn-primary {
            background: #0078d4;
            color: #fff;
        }
        .btn-primary:hover { background: #106ebe; box-shadow: 0 2px 8px rgba(0,120,212,0.3); }
        .btn-secondary {
            background: #fff;
            color: #323130;
            border: 1px solid #d2d0ce;
        }
        .btn-secondary:hover { background: #f3f2f1; }
        .divider {
            font-size: 12px;
            color: #c8c6c4;
            margin: 6px 0 10px;
        }
        .note {
            margin-top: 20px;
            font-size: 11px;
            color: #a19f9d;
            line-height: 1.6;
        }
    </style>
</head>
<body>
<div class="card">
    <div class="icon">📅</div>
    <h1>Adicionar à Agenda</h1>
    <p class="subtitle">
        <strong><?= $subjectSafe ?></strong>
        <?= !empty($deadlineFormatado) ? '<br>Deadline: ' . $deadlineFormatado : '' ?>
    </p>

    <a href="<?= htmlspecialchars($urlWork) ?>" class="btn btn-primary" target="_blank">
        📘 Outlook 365 (conta corporativa)
    </a>

    <div class="divider">ou</div>

    <a href="<?= htmlspecialchars($urlLive) ?>" class="btn btn-secondary" target="_blank">
        📙 Outlook.com (conta pessoal)
    </a>

    <p class="note">
        ℹ️ Se você usa o <strong>Novo Outlook</strong> no Windows,
        o link 365 abre automaticamente no app.
    </p>
</div>
</body>
</html>
