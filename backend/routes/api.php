<?php

declare(strict_types=1);

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;

/** @var \App\Core\Router $router */

$router->get('/api/health', function (Request $request): void {
    Database::connection()->query('SELECT 1');
    Response::success(['status' => 'ok', 'horario' => date('c')]);
});