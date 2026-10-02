<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\UsuarioAutenticado;

/** @var \App\Core\Router $router */

// Públicas
$router->get('/api/health', function (Request $request): void {
    Database::connection()->query('SELECT 1');
    Response::success(['status' => 'ok', 'horario' => date('c')]);
});

$router->post('/api/login', fn (Request $request) => AuthController::criar()->login($request));

// Protegidas: exigem login (e token CSRF em POST/PUT/PATCH/DELETE)
$router->post('/api/logout', Auth::protect(
    fn () => AuthController::criar()->logout()
));

$router->get('/api/me', Auth::protect(
    fn (Request $request, array $params, UsuarioAutenticado $usuario) => AuthController::criar()->me($usuario)
));
