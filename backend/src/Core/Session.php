<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Centraliza o uso da sessão PHP: cookie seguro, expiração por inatividade e token CSRF.
 */
final class Session
{
    private const CHAVE_USUARIO = 'usuario';
    private const CHAVE_CSRF = 'csrf_token';
    private const CHAVE_ATIVIDADE = 'ultima_atividade';

    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        ini_set('session.use_strict_mode', '1');   // rejeita IDs de sessão que o servidor não criou
        ini_set('session.use_only_cookies', '1');  // nunca aceita o ID pela URL

        session_name('PORTALSESSID');
        session_set_cookie_params([
            'lifetime' => 0,                       // cookie some ao fechar o navegador
            'path' => '/',
            'secure' => self::isHttps(),           // em produção (HTTPS), só trafega criptografado
            'httponly' => true,                    // JavaScript não lê o cookie (mitiga roubo via XSS)
            'samesite' => 'Strict',                // não é enviado por requisições de outros sites (mitiga CSRF)
        ]);

        session_start();
        self::expirarPorInatividade();
    }

    public static function login(UsuarioAutenticado $usuario): void
    {
        self::start();

        // Novo ID após o login: impede fixação de sessão
        session_regenerate_id(true);

        $_SESSION[self::CHAVE_USUARIO] = $usuario->toArray();
        $_SESSION[self::CHAVE_CSRF] = bin2hex(random_bytes(32));
    }

    public static function logout(): void
    {
        self::start();

        $_SESSION = [];

        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 3600,
            'path' => $params['path'],
            'secure' => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => $params['samesite'],
        ]);

        session_destroy();
    }

    public static function usuario(): ?UsuarioAutenticado
    {
        self::start();

        $dados = $_SESSION[self::CHAVE_USUARIO] ?? null;

        return is_array($dados) ? UsuarioAutenticado::fromArray($dados) : null;
    }

    public static function csrfToken(): ?string
    {
        self::start();

        return $_SESSION[self::CHAVE_CSRF] ?? null;
    }

    private static function expirarPorInatividade(): void
    {
        $limite = (int) ($_ENV['SESSION_LIFETIME'] ?? 1800);
        $ultima = $_SESSION[self::CHAVE_ATIVIDADE] ?? null;

        if ($ultima !== null && time() - (int) $ultima > $limite) {
            $_SESSION = [];
            session_regenerate_id(true);
        }

        $_SESSION[self::CHAVE_ATIVIDADE] = time();
    }

    private static function isHttps(): bool
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || ($_SERVER['SERVER_PORT'] ?? null) == 443;
    }
}
