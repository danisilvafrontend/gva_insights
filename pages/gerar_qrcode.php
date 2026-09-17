<?php
declare(strict_types=1);

ini_set('display_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/../vendor/autoload.php';

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;

$erro = '';
$qrBase64 = '';
$url = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $url = trim($_POST['url'] ?? '');

    if ($url === '') {
        $erro = 'Informe uma URL para gerar o QR code.';
    } elseif (!filter_var($url, FILTER_VALIDATE_URL)) {
        $erro = 'Informe uma URL válida, incluindo https:// ou http://.';
    } else {
        $protocolo = parse_url($url, PHP_URL_SCHEME);

        if (!in_array($protocolo, ['http', 'https'], true)) {
            $erro = 'São aceitas somente URLs com http:// ou https://.';
        } else {
            try {
                $builder = new Builder(
                    writer: new PngWriter(),
                    writerOptions: [],
                    validateResult: false,
                    data: $url,
                    encoding: new Encoding('UTF-8'),
                    errorCorrectionLevel: ErrorCorrectionLevel::High,
                    size: 400,
                    margin: 12
                );

                $resultado = $builder->build();
                $qrBase64 = base64_encode($resultado->getString());
            } catch (Throwable $e) {
                error_log('Erro ao gerar QR code: ' . $e->getMessage());
                $erro = 'Erro técnico: ' . $e->getMessage();
            }
        }
    }
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gerador de QR Code | GVA Insights</title>
    <style>
        :root {
            --cor-principal: #1d4ed8;
            --cor-fundo: #f4f7fb;
            --cor-texto: #1f2937;
            --cor-erro: #b91c1c;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            padding: 32px 16px;
            font-family: Arial, Helvetica, sans-serif;
            color: var(--cor-texto);
            background: var(--cor-fundo);
        }

        .container {
            width: min(100%, 720px);
            margin: 0 auto;
            padding: 32px;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 12px 35px rgba(15, 23, 42, .10);
        }

        h1 { margin: 0 0 8px; font-size: 28px; }

        .descricao {
            margin: 0 0 28px;
            color: #64748b;
            line-height: 1.5;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 700;
        }

        input[type="url"] {
            width: 100%;
            padding: 13px 14px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 16px;
        }

        button, .botao-download {
            display: inline-block;
            margin-top: 16px;
            padding: 12px 18px;
            border: 0;
            border-radius: 8px;
            color: #fff;
            background: var(--cor-principal);
            font-size: 15px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
        }

        .erro {
            margin: 18px 0;
            padding: 12px;
            border-radius: 8px;
            color: var(--cor-erro);
            background: #fef2f2;
            overflow-wrap: anywhere;
        }

        .resultado {
            margin-top: 32px;
            padding-top: 24px;
            border-top: 1px solid #e2e8f0;
            text-align: center;
        }

        .resultado img {
            display: block;
            width: min(100%, 400px);
            height: auto;
            margin: 20px auto;
            padding: 10px;
            background: #fff;
            border: 1px solid #e2e8f0;
        }

        .url-gerada {
            overflow-wrap: anywhere;
            color: #475569;
        }
    </style>
</head>
<body>
    <main class="container">
        <h1>Gerador de QR Code</h1>

        <p class="descricao">
            Cole uma URL para gerar um QR code estático e funcional.
            Ele continuará válido enquanto a URL de destino estiver ativa.
        </p>

        <form method="post">
            <label for="url">URL de destino</label>

            <input
                type="url"
                id="url"
                name="url"
                value="<?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8') ?>"
                placeholder="https://exemplo.com"
                required
            >

            <button type="submit">Gerar QR code</button>
        </form>

        <?php if ($erro !== ''): ?>
            <div class="erro">
                <?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <?php if ($qrBase64 !== ''): ?>
            <section class="resultado">
                <h2>QR code gerado</h2>

                <p class="url-gerada">
                    <?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8') ?>
                </p>

                <img
                    src="data:image/png;base64,<?= $qrBase64 ?>"
                    alt="QR code para <?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8') ?>"
                >

                <a
                    class="botao-download"
                    href="data:image/png;base64,<?= $qrBase64 ?>"
                    download="qrcode-gva.png"
                >
                    Baixar PNG
                </a>
            </section>
        <?php endif; ?>
    </main>
</body>
</html>