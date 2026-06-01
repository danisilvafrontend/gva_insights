<?php
/**
 * enviar_email.php — Brasil DNA 2026
 * Envia e-mail HTML + convite .ics via PHPMailer (Office365 / STARTTLS)
 *
 * Requer no includes/db_connect.php:
 *   define('MAIL_HOST',      'smtp.office365.com');
 *   define('MAIL_PORT',      587);
 *   define('MAIL_USER',      'seuemail@gvacompany.com');
 *   define('MAIL_PASS',      'sua_senha');
 *   define('MAIL_FROM',      'seuemail@gvacompany.com');
 *   define('MAIL_FROM_NAME', 'Brasil DNA 2026 | GVA');
 *
 * Instalar PHPMailer (uma vez no servidor via SSH):
 *   cd /home2/globalvisionacce/insights.gvacompany.com
 *   composer require phpmailer/phpmailer
 */

// Autoload Composer — carrega apenas se existir (não quebra o sistema se faltar)
$_composerAutoload = __DIR__ . '/../../vendor/autoload.php';
if (file_exists($_composerAutoload)) {
    require_once $_composerAutoload;
}

function enviarEmailTarefa(array $dados): bool {

    // Se PHPMailer não estiver disponível, loga e retorna sem quebrar
    if (!class_exists('\\PHPMailer\\PHPMailer\\PHPMailer')) {
        error_log('enviarEmailTarefa: PHPMailer não encontrado. Rode: composer require phpmailer/phpmailer');
        return false;
    }

    if (empty($dados['email']) || !filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $para        = $dados['email'];
    $nome        = $dados['responsavel']  ?? 'Responsável';
    $tarefa      = $dados['tarefa']       ?? '';
    $categoria   = $dados['categoria']   ?? '';
    $mes         = $dados['mes']          ?? '';
    $prioridade  = $dados['prioridade']  ?? '';
    $status      = $dados['status']       ?? '';
    $obs         = $dados['observacoes'] ?? '';
    $link        = $dados['link_sistema'] ?? 'https://insights.gvacompany.com/brasil_dna/';
    $deadlineRaw = $dados['deadline']     ?? '';

    // Formata deadline
    $deadlineExib = 'Não definido';
    $deadlineICS  = '';
    if (!empty($deadlineRaw)) {
        $dt = DateTime::createFromFormat('Y-m-d', $deadlineRaw, new DateTimeZone('America/Sao_Paulo'));
        if ($dt) {
            $deadlineExib = $dt->format('d/m/Y');
            $deadlineICS  = $dt->format('Ymd');
        }
    }

    $priEmoji = ['Alta' => '🔴', 'Media' => '🟡', 'Baixa' => '🟢'];
    $emoji    = $priEmoji[$prioridade] ?? '⚪';

    // =========================================================
    // GERAR .ICS
    // =========================================================
    $uid        = uniqid('brasildna-', true) . '@gvacompany.com';
    $dtStamp    = gmdate('Ymd\THis\Z');
    $dtStart    = !empty($deadlineICS) ? $deadlineICS : gmdate('Ymd', strtotime('+7 days'));
    $orgEmail   = defined('MAIL_FROM')      ? MAIL_FROM      : 'noreply@gvacompany.com';
    $orgName    = defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : 'Brasil DNA 2026';
    $summaryICS = 'Tarefa Brasil DNA 2026: ' . $tarefa;
    $descICS    = 'Categoria: '  . $categoria  . '\nMes: '        . $mes
                . '\nPrioridade: ' . $prioridade . '\nStatus: '    . $status
                . '\nObs: '      . $obs         . '\nSistema: '   . $link;

    $ics  = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\n";
    $ics .= "PRODID:-//GVA Brasil DNA 2026//PT\r\nMETHOD:REQUEST\r\n";
    $ics .= "BEGIN:VEVENT\r\n";
    $ics .= "UID:{$uid}\r\nDTSTAMP:{$dtStamp}\r\n";
    $ics .= "DTSTART;VALUE=DATE:{$dtStart}\r\nDTEND;VALUE=DATE:{$dtStart}\r\n";
    $ics .= "SUMMARY:{$summaryICS}\r\nDESCRIPTION:{$descICS}\r\n";
    $ics .= "ORGANIZER;CN={$orgName}:MAILTO:{$orgEmail}\r\n";
    $ics .= "ATTENDEE;CN={$nome};RSVP=TRUE:MAILTO:{$para}\r\n";
    $ics .= "BEGIN:VALARM\r\nTRIGGER:-PT1440M\r\nACTION:DISPLAY\r\n";
    $ics .= "DESCRIPTION:Lembrete: {$summaryICS}\r\nEND:VALARM\r\n";
    $ics .= "END:VEVENT\r\nEND:VCALENDAR\r\n";

    // =========================================================
    // CORPO HTML
    // =========================================================
    $obsHtml = !empty($obs)
        ? "<tr><td style='padding:0 32px 20px;'>
             <p style='margin:0 0 6px;font-size:13px;font-weight:bold;color:#555;'>Observações:</p>
             <p style='margin:0;font-size:13px;color:#666;background:#fffbe6;padding:12px;border-radius:4px;border:1px solid #ffe58f;'>{$obs}</p>
           </td></tr>"
        : '';

    $html = "
<!DOCTYPE html><html lang='pt-BR'>
<head><meta charset='UTF-8'></head>
<body style='margin:0;padding:0;font-family:Arial,sans-serif;background:#f4f4f4;'>
<table width='100%' cellpadding='0' cellspacing='0' style='background:#f4f4f4;padding:30px 0;'>
<tr><td align='center'>
<table width='600' cellpadding='0' cellspacing='0' style='background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.1);'>
  <tr><td style='background:#1a3a5c;padding:24px 32px;'>
    <h1 style='margin:0;color:#fff;font-size:20px;'>📋 Nova Tarefa Atribuída</h1>
    <p style='margin:4px 0 0;color:#a8c4e0;font-size:13px;'>Brasil DNA 2026 &mdash; GVA Company</p>
  </td></tr>
  <tr><td style='padding:28px 32px 0;'>
    <p style='margin:0;font-size:15px;color:#333;'>Olá, <strong>{$nome}</strong>!</p>
    <p style='margin:8px 0 0;font-size:14px;color:#555;'>Uma nova tarefa foi atribuída a você no sistema Brasil DNA 2026.</p>
  </td></tr>
  <tr><td style='padding:20px 32px;'>
    <table width='100%' cellpadding='0' cellspacing='0' style='background:#f8f9fa;border-left:4px solid #1a3a5c;border-radius:4px;'>
    <tr><td style='padding:16px 20px;'>
      <p style='margin:0 0 12px;font-size:16px;font-weight:bold;color:#1a3a5c;'>{$tarefa}</p>
      <table width='100%' cellpadding='4' cellspacing='0' style='font-size:13px;color:#444;'>
        <tr><td width='40%'><strong>📁 Categoria:</strong></td><td>{$categoria}</td></tr>
        <tr><td><strong>📅 Mês:</strong></td><td>{$mes}</td></tr>
        <tr><td><strong>⏰ Deadline:</strong></td><td><strong style='color:#c0392b;'>{$deadlineExib}</strong></td></tr>
        <tr><td><strong>{$emoji} Prioridade:</strong></td><td>{$prioridade}</td></tr>
        <tr><td><strong>📌 Status:</strong></td><td>{$status}</td></tr>
      </table>
    </td></tr>
    </table>
  </td></tr>
  {$obsHtml}
  <tr><td style='padding:0 32px 16px;'>
    <p style='margin:0;font-size:13px;color:#555;'>📎 <strong>Convite de calendário em anexo</strong> — abra o arquivo <code>.ics</code> para adicionar ao seu Outlook/Teams.</p>
  </td></tr>
  <tr><td style='padding:0 32px 28px;'>
    <a href='{$link}' style='display:inline-block;background:#1a3a5c;color:#fff;text-decoration:none;padding:12px 24px;border-radius:6px;font-size:14px;font-weight:bold;'>🔗 Ver no Sistema</a>
  </td></tr>
  <tr><td style='background:#f8f9fa;padding:16px 32px;border-top:1px solid #eee;'>
    <p style='margin:0;font-size:12px;color:#999;text-align:center;'>E-mail automático — GVA Insights &mdash; Brasil DNA 2026. Não responda.</p>
  </td></tr>
</table>
</td></tr></table>
</body></html>";

    // =========================================================
    // ENVIAR VIA PHPMAILER (Office365 / STARTTLS)
    // =========================================================
    try {
        // Usa nome completo da classe — sem depender do "use" global
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

        $mail->isSMTP();
        $mail->Host       = defined('MAIL_HOST') ? MAIL_HOST : 'smtp.office365.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = defined('MAIL_USER') ? MAIL_USER : '';
        $mail->Password   = defined('MAIL_PASS') ? MAIL_PASS : '';
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = defined('MAIL_PORT') ? MAIL_PORT : 587;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom(
            defined('MAIL_FROM')      ? MAIL_FROM      : '',
            defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : 'Brasil DNA 2026'
        );
        $mail->addAddress($para, $nome);
        $mail->addReplyTo(defined('MAIL_FROM') ? MAIL_FROM : '', 'No Reply');

        $mail->isHTML(true);
        $mail->Subject = '📋 Nova Tarefa: ' . $tarefa . ' — Brasil DNA 2026';
        $mail->Body    = $html;
        $mail->AltBody = 'Nova tarefa atribuída: ' . $tarefa . ' | Deadline: ' . $deadlineExib;

        $mail->addStringAttachment(
            $ics,
            'tarefa_brasildna.ics',
            \PHPMailer\PHPMailer\PHPMailer::ENCODING_BASE64,
            'text/calendar; method=REQUEST'
        );

        $mail->send();
        return true;

    } catch (\Exception $e) {
        error_log('enviarEmailTarefa erro: ' . $e->getMessage());
        return false;
    }
}
