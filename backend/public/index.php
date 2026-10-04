<?php

declare(strict_types=1);

use App\Core\ErrorHandler;
use App\Core\Request;
use App\Core\Router;
use Dotenv\Dotenv;

// Servidor embutido do PHP (composer serve): o que não for /api é arquivo estático do frontend.
// Retornar false faz o próprio servidor entregar o arquivo da pasta frontend (definida com -t).
if (PHP_SAPI === 'cli-server') {
    $caminho = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    if (!str_starts_with($caminho, '/api')) {
        return false;
    }
}

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();
$dotenv->required(['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS']);

date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'America/Fortaleza');

// Cabeçalhos de segurança em todas as respostas
header_remove('X-Powered-By');                 // não revela a versão do PHP
header('X-Content-Type-Options: nosniff');     // navegador respeita o Content-Type informado
header('X-Frame-Options: DENY');               // impede carregar a API dentro de iframes (clickjacking)
header('Referrer-Policy: no-referrer');
header("Content-Security-Policy: default-src 'none'; frame-ancestors 'none'");

ErrorHandler::register();

$router = new Router();
require dirname(__DIR__) . '/routes/api.php';

$router->dispatch(Request::fromGlobals());
