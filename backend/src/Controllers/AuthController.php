<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\UsuarioAutenticado;
use App\Exceptions\ValidationException;
use App\Repositories\LoginTentativaRepository;
use App\Repositories\UsuarioRepository;
use App\Services\AuthService;

final class AuthController
{
    public function __construct(private readonly AuthService $service)
    {
    }

    /**
     * Monta o controller com suas dependências (injeção de dependência manual).
     */
    public static function criar(): self
    {
        $pdo = Database::connection();

        return new self(new AuthService(
            new UsuarioRepository($pdo),
            new LoginTentativaRepository($pdo),
            (int) ($_ENV['LOGIN_MAX_TENTATIVAS'] ?? 5),
            (int) ($_ENV['LOGIN_JANELA_MINUTOS'] ?? 15)
        ));
    }

    public function login(Request $request): never
    {
        $usuario = $request->input('usuario');
        $senha = $request->input('senha');

        $erros = [];
        if (!is_string($usuario) || trim($usuario) === '') {
            $erros['usuario'] = 'Informe o usuário.';
        }
        if (!is_string($senha) || $senha === '') {
            $erros['senha'] = 'Informe a senha.';
        }
        if ($erros !== []) {
            throw new ValidationException($erros);
        }

        $autenticado = $this->service->login(trim($usuario), $senha, $request->ip, $request->userAgent);
        Session::login($autenticado);

        Response::success([
            'usuario' => $autenticado->toArray(),
            'csrf_token' => Session::csrfToken(),
        ]);
    }

    public function logout(): never
    {
        Session::logout();
        Response::noContent();
    }

    public function me(UsuarioAutenticado $usuario): never
    {
        Response::success([
            'usuario' => $usuario->toArray(),
            'csrf_token' => Session::csrfToken(),
        ]);
    }
}
