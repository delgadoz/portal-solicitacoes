<?php

declare(strict_types=1);

namespace App\Core;

use App\Enums\Perfil;
use App\Exceptions\ForbiddenException;
use App\Exceptions\UnauthorizedException;

/**
 * Middleware de autenticação e autorização.
 *
 * Auth::protect() "embrulha" o handler de uma rota: antes de executá-lo, exige login,
 * valida o token CSRF em requisições que alteram dados e, se informado, exige um perfil.
 * O handler recebe o usuário logado como terceiro parâmetro.
 */
final class Auth
{
    private const METODOS_DE_ESCRITA = ['POST', 'PUT', 'PATCH', 'DELETE'];

    public static function protect(callable $handler, ?Perfil $perfilExigido = null): callable
    {
        return static function (Request $request, array $params) use ($handler, $perfilExigido): void {
            $usuario = Session::usuario();

            if ($usuario === null) {
                throw new UnauthorizedException();
            }

            if (in_array($request->method, self::METODOS_DE_ESCRITA, true)) {
                self::validarCsrf($request);
            }

            if ($perfilExigido !== null && $usuario->perfil !== $perfilExigido) {
                throw new ForbiddenException();
            }

            $handler($request, $params, $usuario);
        };
    }

    private static function validarCsrf(Request $request): void
    {
        $esperado = Session::csrfToken();
        $recebido = $request->header('X-CSRF-Token');

        // hash_equals compara em tempo constante: não vaza, pelo tempo de resposta, quantos caracteres acertou
        if ($esperado === null || $recebido === null || !hash_equals($esperado, $recebido)) {
            throw new ForbiddenException('Token CSRF ausente ou inválido.');
        }
    }
}
