<?php

declare(strict_types=1);

namespace App\Core;

use App\Exceptions\HttpException;

final class Request
{
    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly array $query,
        public readonly array $body,
        public readonly array $headers = [],
        public readonly string $ip = '',
        public readonly ?string $userAgent = null
    ) {
    }

    public static function fromGlobals(): self
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $path = rtrim($path, '/') ?: '/';

        $body = [];
        $raw = file_get_contents('php://input');

        if ($raw !== false && $raw !== '') {
            $decoded = json_decode($raw, true);

            if (!is_array($decoded)) {
                throw new HttpException(400, 'INVALID_JSON', 'O corpo da requisição não é um JSON válido.');
            }

            $body = $decoded;
        }

        // Cabeçalhos HTTP chegam em $_SERVER como HTTP_X_CSRF_TOKEN; normaliza para "x-csrf-token"
        $headers = [];
        foreach ($_SERVER as $chave => $valor) {
            if (str_starts_with($chave, 'HTTP_')) {
                $nome = strtolower(str_replace('_', '-', substr($chave, 5)));
                $headers[$nome] = (string) $valor;
            }
        }

        return new self(
            strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET'),
            $path,
            $_GET,
            $body,
            $headers,
            $_SERVER['REMOTE_ADDR'] ?? '',
            $_SERVER['HTTP_USER_AGENT'] ?? null
        );
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $default;
    }

    public function header(string $name): ?string
    {
        return $this->headers[strtolower($name)] ?? null;
    }
}
