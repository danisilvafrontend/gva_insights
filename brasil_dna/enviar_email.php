<?php
/**
 * enviar_email.php — Brasil DNA 2026
 * Envia e-mail de notificacao para o responsavel da tarefa
 * com convite .ics para adicionar automaticamente no Outlook/Teams Calendar.
 *
 * Requer no includes/config.php:
 *   define('MAIL_HOST',     'smtp.hostinger.com');  // ou seu SMTP
 *   define('MAIL_USER',     'noreply@gvacompany.com');
 *   define('MAIL_PASS',     'sua_senha_smtp');
 *   define('MAIL_FROM',     'noreply@gvacompany.com');
 *   define('MAIL_FROM_NAME','Brasil DNA 2026 | GVA');
 *   define('MAIL_PORT',     465);  // 465 SSL ou 587 TLS
 */

function enviarEmailTarefa(array $dados): bool {

    // --- Valida e-mail ---
    if (empty($dados['email']) || !filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $para      = $dados['email'];
    $nome      = $dados['responsavel'] ?? 'Responsavel';
    $tarefa    = $dados['tarefa']      ?? '';
    $categoria = $dados['categoria']   ?? '';
    $mes       = $dados['mes']         ?? '';
    $prioridade= $dados['prioridade']  ?? '';
    $status    = $dados['status']      ?? '';
    $obs       = $dados['observacoes'] ?? '';
    $link      = $dados['link_sistema'] ?? 'https://insights.gvacompany.com/brasil_dna/';
    $deadlineRaw = $dados['deadline']  ?? ''; // YYYY-MM-DD

    // --- Formata deadline para exibicao ---
    $deadlineExib = 'Nao definido';
    $deadlineICS  = '';
    if (!empty($deadlineRaw)) {
        $dt = DateTime::createFromFormat('Y-m-d', $deadlineRaw, new DateTimeZone('America/Sao_Paulo'));
        if ($dt) {
            $deadlineExib = $dt->format('d/m/Y');
            $deadlineICS  = $dt->format('Ymd'); // ex: 20260615
        }
    }

    // --- Emoji de prioridade ---
    $priEmoji = ['Alta' => '🔴', 'Media' => '🟡', 'Baixa' => '🟢'];
    $emoji = $priEmoji[$prioridade] ?? '⚪';

    // =========================================================
    // GERAR ARQUIVO .ICS (convite de calendario)
    // =========================================================
    $uid       = uniqid('brasildna-', true) . '@gvacompany.com';
    $dtStamp   = gmdate('Ymd\THis\Z');
    $dtStart   = !empty($deadlineICS) ? $deadlineICS : gmdate('Ymd', strtotime('+7 days'));
    $dtEnd     = $dtStart; // evento de dia inteiro
    $summaryICS = 'Tarefa Brasil DNA 2026: ' . $tarefa;
    $descICS    = 'Categoria: ' . $categoria . '\nMes: ' . $mes . '\nPrioridade: ' . $prioridade . '\nStatus: ' . $status . '\nObservacoes: ' . $obs . '\nSistema: ' . $link;
    $organizerEmail = defined('MAIL_FROM') ? MAIL_FROM : 'noreply@gvacompany.com';
    $organizerName  = defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : 'Brasil DNA 2026';

    $ics = "BEGIN:VCALENDAR\r\n";
    $ics .= "VERSION:2.0\r\n";
    $ics .= "PRODID:-//GVA Brasil DNA 2026//PT\r\n";
    $ics .= "METHOD:REQUEST\r\n";  // REQUEST = abre o dialogo Aceitar/Recusar no Outlook
    $ics .= "BEGIN:VEVENT\r\n";
    $ics .= "UID:{$uid}\r\n";
    $ics .= "DTSTAMP:{$dtStamp}\r\n";
    $ics .= "DTSTART;VALUE=DATE:{$dtStart}\r\n";
    $ics .= "DTEND;VALUE=DATE:{$dtEnd}\r\n";
    $ics .= "SUMMARY:{$summaryICS}\r\n";
    $ics .= "DESCRIPTION:{$descICS}\r\n";
    $ics .= "ORGANIZER;CN={$organizerName}:MAILTO:{$organizerEmail}\r\n";
    $ics .= "ATTENDEE;CN={$nome};RSVP=TRUE:MAILTO:{$para}\r\n";
    $ics .= "BEGIN:VALARM\r\n";
    $ics .= "TRIGGER:-PT1440M\r\n"; // lembrete 1 dia antes
    $ics .= "ACTION:DISPLAY\r\n";
    $ics .= "DESCRIPTION:Lembrete: {$summaryICS}\r\n";
    $ics .= "END:VALARM\r\n";
    $ics .= "END:VEVENT\r\n";
    $ics .= "END:VCALENDAR\r\n";

    // =========================================================
    // CORPO DO E-MAIL (HTML)
    // =========================================================
    $corpoHtml = "
<!DOCTYPE html>
<html lang='pt-BR'>
<head><meta charset='UTF-8'></head>
<body style='margin:0;padding:0;font-family:Arial,sans-serif;background:#f4f4f4;'>
  <table width='100%' cellpadding='0' cellspacing='0' style='background:#f4f4f4;padding:30px 0;'>
    <tr><td align='center'>
      <table width='600' cellpadding='0' cellspacing='0' style='background:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.1);'>

        <!-- HEADER -->
        <tr><td style='background:#1a3a5c;padding:24px 32px;'>
          <h1 style='margin:0;color:#ffffff;font-size:20px;'>📋 Nova Tarefa Atribuída</h1>
          <p style='margin:4px 0 0;color:#a8c4e0;font-size:13px;'>Brasil DNA 2026 &mdash; GVA Company</p>
        </td></tr>

        <!-- SAUDACAO -->
        <tr><td style='padding:28px 32px 0;'>
          <p style='margin:0;font-size:15px;color:#333;'>Olá, <strong>{$nome}</strong>!</p>
          <p style='margin:8px 0 0;font-size:14px;color:#555;'>Uma nova tarefa foi atribuída a você no sistema Brasil DNA 2026.</p>
        </td></tr>

        <!-- CARD TAREFA -->
        <tr><td style='padding:20px 32px;'>
          <table width='100%' cellpadding='0' cellspacing='0' style='background:#f8f9fa;border-left:4px solid #1a3a5c;border-radius:4px;'>
            <tr><td style='padding:16px 20px;'>
              <p style='margin:0 0 12px;font-size:16px;font-weight:bold;color:#1a3a5c;'>{$tarefa}</p>
              <table width='100%' cellpadding='4' cellspacing='0' style='font-size:13px;color:#444;'>
                <tr>
                  <td width='40%'><strong>📁 Categoria:</strong></td>
                  <td>{$categoria}</td>
                </tr>
                <tr>
                  <td><strong>📅 Mês de referência:</strong></td>
                  <td>{$mes}</td>
                </tr>
                <tr>
                  <td><strong>⏰ Deadline:</strong></td>
                  <td><strong style='color:#c0392b;'>{$deadlineExib}</strong></td>
                </tr>
                <tr>
                  <td><strong>{$emoji} Prioridade:</strong></td>
                  <td>{$prioridade}</td>
                </tr>
                <tr>
                  <td><strong>📌 Status:</strong></td>
                  <td>{$status}</td>
                </tr>
              </table>
            </td></tr>
          </table>
        </td></tr>

        <!-- OBSERVACOES -->
" . (!empty($obs) ? "
        <tr><td style='padding:0 32px 20px;'>
          <p style='margin:0 0 6px;font-size:13px;font-weight:bold;color:#555;'>Observações:</p>
          <p style='margin:0;font-size:13px;color:#666;background:#fffbe6;padding:12px;border-radius:4px;border:1px solid #ffe58f;'>{$obs}</p>
        </td></tr>
" : "") . "

        <!-- CALENDARIO -->
        <tr><td style='padding:0 32px 20px;'>
          <p style='margin:0;font-size:13px;color:#555;'>📎 <strong>Convite de calendário em anexo</strong> — abra o arquivo <code>.ics</code> para adicionar esta tarefa diretamente ao seu Outlook/Teams.</p>
        </td></tr>

        <!-- BOTAO -->
        <tr><td style='padding:0 32px 28px;'>
          <a href='{$link}' style='display:inline-block;background:#1a3a5c;color:#ffffff;text-decoration:none;padding:12px 24px;border-radius:6px;font-size:14px;font-weight:bold;'>🔗 Ver no Sistema</a>
        </td></tr>

        <!-- FOOTER -->
        <tr><td style='background:#f8f9fa;padding:16px 32px;border-top:1px solid #eee;'>
          <p style='margin:0;font-size:12px;color:#999;text-align:center;'>Este e-mail foi gerado automaticamente pelo sistema GVA Insights &mdash; Brasil DNA 2026.<br>Não responda este e-mail.</p>
        </td></tr>

      </table>
    </td></tr>
  </table>
</body>
</html>";

    // =========================================================
    // MONTAR E ENVIAR E-MAIL MULTIPART (HTML + ICS anexo)
    // =========================================================
    $boundary = '----=_Part_' . md5(uniqid());
    $icsBase64 = chunk_split(base64_encode($ics));

    $assunto = '=?UTF-8?B?' . base64_encode('📋 Nova Tarefa: ' . $tarefa . ' — Brasil DNA 2026') . '?=';

    $headers  = "From: " . (defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : 'Brasil DNA 2026') . " <" . (defined('MAIL_FROM') ? MAIL_FROM : 'noreply@gvacompany.com') . ">\r\n";
    $headers .= "Reply-To: " . (defined('MAIL_FROM') ? MAIL_FROM : 'noreply@gvacompany.com') . "\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/mixed; boundary=\"{$boundary}\"\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

    $body  = "--{$boundary}\r\n";
    $body .= "Content-Type: text/html; charset=UTF-8\r\n";
    $body .= "Content-Transfer-Encoding: quoted-printable\r\n\r\n";
    $body .= quoted_printable_encode($corpoHtml) . "\r\n";

    // Anexo .ics
    $body .= "--{$boundary}\r\n";
    $body .= "Content-Type: text/calendar; charset=UTF-8; method=REQUEST; name=\"tarefa_brasildna.ics\"\r\n";
    $body .= "Content-Transfer-Encoding: base64\r\n";
    $body .= "Content-Disposition: attachment; filename=\"tarefa_brasildna.ics\"\r\n\r\n";
    $body .= $icsBase64 . "\r\n";
    $body .= "--{$boundary}--";

    return mail($para, $assunto, $body, $headers);
}
