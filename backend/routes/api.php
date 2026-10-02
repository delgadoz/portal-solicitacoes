<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\CategoriaController;
use App\Controllers\SolicitacaoController;
use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\UsuarioAutenticado;
use App\Enums\Perfil;

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

$router->get('/api/categorias', Auth::protect(
    fn () => CategoriaController::index()
));

// Solicitações: visibilidade e permissões por perfil são aplicadas no SolicitacaoService
$router->get('/api/solicitacoes', Auth::protect(
    fn (Request $r, array $p, UsuarioAutenticado $u) => SolicitacaoController::criar()->index($u)
));

$router->post('/api/solicitacoes', Auth::protect(
    fn (Request $r, array $p, UsuarioAutenticado $u) => SolicitacaoController::criar()->store($r, $u),
    Perfil::Solicitante
));

$router->get('/api/solicitacoes/{id}', Auth::protect(
    fn (Request $r, array $p, UsuarioAutenticado $u) => SolicitacaoController::criar()->show($p, $u)
));

$router->put('/api/solicitacoes/{id}', Auth::protect(
    fn (Request $r, array $p, UsuarioAutenticado $u) => SolicitacaoController::criar()->update($r, $p, $u),
    Perfil::Solicitante
));

$router->delete('/api/solicitacoes/{id}', Auth::protect(
    fn (Request $r, array $p, UsuarioAutenticado $u) => SolicitacaoController::criar()->destroy($p, $u),
    Perfil::Solicitante
));
