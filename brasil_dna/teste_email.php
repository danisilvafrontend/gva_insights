<?php
/**
 * ARQUIVO TEMPORARIO DE DIAGNOSTICO
 * Acesse: https://insights.gvacompany.com/brasil_dna/teste_email.php
 * APAGAR apos confirmar que o e-mail funciona!
 */
if (session_status() === PHP_SESSION_NONE) session_start();

include '../includes/config.php';
include '../includes/db_connect.php';

echo '<h2>Diagnóstico de E-mail — Brasil DNA 2026</h2>';
echo '<pre style="background:#f4f4f4;padding:16px;border-radius:6px;font-size:13px;">';

// 1. Verifica defines
echo "=== DEFINES DE E-MAIL ===\n";
echo 'MAIL_HOST : ' . (defined('MAIL_HOST') ? MAIL_HOST      : 'NAO DEFINIDO') . "\n";
echo 'MAIL_PORT : ' . (defined('MAIL_PORT') ? MAIL_PORT      : 'NAO DEFINIDO') . "\n";
echo 'MAIL_USER : ' . (defined('MAIL_USER') ? MAIL_USER      : 'NAO DEFINIDO') . "\n";
echo 'MAIL_PASS : ' . (defined('MAIL_PASS') ? str_repeat('*', strlen(MAIL_PASS)) : 'NAO DEFINIDO') . "\n";
echo 'MAIL_FROM : ' . (defined('MAIL_FROM') ? MAIL_FROM      : 'NAO DEFINIDO') . "\n\n";

// 2. Verifica autoload
$autoload = __DIR__ . '/../vendor/autoload.php';
echo "=== AUTOLOAD COMPOSER ===\n";
echo 'Caminho : ' . $autoload . "\n";
echo 'Existe  : ' . (file_exists($autoload) ? 'SIM ✅' : 'NAO ❌') . "\n\n";

if (file_exists($autoload)) {
    require_once $autoload;
    echo 'PHPMailer carregado: ' . (class_exists('\\PHPMailer\\PHPMailer\\PHPMailer') ? 'SIM ✅' : 'NAO ❌') . "\n\n";
} else {
    echo "ERRO: vendor/autoload.php nao encontrado.\n";
    echo "Verifique se o composer foi instalado na raiz do projeto.\n\n";
}

// 3. Tenta enviar e-mail de teste
if (file_exists($autoload) && class_exists('\\PHPMailer\\PHPMailer\\PHPMailer') && defined('MAIL_USER')) {
    echo "=== TENTATIVA DE ENVIO ===\n";
    echo 'Enviando para: ' . MAIL_USER . "\n";

    try {
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        $mail->SMTPDebug  = 2; // mostra todo o log SMTP
        $mail->Debugoutput = function($str, $level) { echo htmlspecialchars($str) . "\n"; };

        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USER;
        $mail->Password   = MAIL_PASS;
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = MAIL_PORT;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom(MAIL_FROM, defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : 'Teste');
        $mail->addAddress(MAIL_USER); // envia para si mesmo
        $mail->Subject = 'Teste PHPMailer — Brasil DNA 2026';
        $mail->Body    = 'E-mail de teste enviado com sucesso pelo sistema Brasil DNA 2026.';

        $mail->send();
        echo "\n✅ E-MAIL ENVIADO COM SUCESSO!\n";
    } catch (\Exception $e) {
        echo "\n❌ ERRO AO ENVIAR: " . $e->getMessage() . "\n";
    }
}

echo '</pre>';
echo '<p style="color:red;font-weight:bold;">APAGUE este arquivo após o diagnóstico!</p>';