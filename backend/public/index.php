<?php

declare(strict_types=1);

use App\Core\ErrorHandler;
use App\Core\Request;
use App\Core\Router;
use Dotenv\Dotenv;

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();
$dotenv->required(['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS']);

date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'America/Fortaleza');

ErrorHandler::register();

$router = new Router();
require dirname(__DIR__) . '/routes/api.php';

$router->dispatch(Request::fromGlobals());