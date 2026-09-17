<?php
declare(strict_types=1);

$autoload = __DIR__ . '/../vendor/autoload.php';
$builder = __DIR__ . '/../vendor/endroid/qr-code/src/Builder/Builder.php';
$psr4 = __DIR__ . '/../vendor/composer/autoload_psr4.php';

require_once $autoload;

echo '<pre>';
echo 'PHP: ' . PHP_VERSION . PHP_EOL;
echo 'Autoload: ' . (file_exists($autoload) ? 'OK' : 'NÃO ENCONTRADO') . PHP_EOL;
echo 'Pasta Endroid: ' . (is_dir(__DIR__ . '/../vendor/endroid/qr-code') ? 'OK' : 'NÃO ENCONTRADA') . PHP_EOL;
echo 'Arquivo Builder.php: ' . (file_exists($builder) ? 'OK' : 'NÃO ENCONTRADO') . PHP_EOL;
echo 'autoload_psr4.php: ' . (file_exists($psr4) ? 'OK' : 'NÃO ENCONTRADO') . PHP_EOL;
echo 'Classe Builder: ' . (class_exists(\Endroid\QrCode\Builder\Builder::class) ? 'OK' : 'NÃO CARREGADA') . PHP_EOL;
echo '</pre>';