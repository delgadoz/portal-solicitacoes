<?php

declare(strict_types=1);

namespace App\Core;

final class Response
{
    public static function success(mixed $data, int $status = 200): never
    {
        self::json(['data' => $data], $status);
    }

    /**
     * Lista paginada: os itens em "data" e as informações de paginação em "meta".
     */
    public static function paginated(array $itens, array $meta): never
    {
        self::json(['data' => $itens, 'meta' => $meta], 200);
    }

    public static function noContent(): never
    {
        http_response_code(204);
        exit;
    }

    public static function error(string $code, string $message, int $status, array $fields = []): never
    {
        $error = ['code' => $code, 'message' => $message];

        if ($fields !== []) {
            $error['fields'] = $fields;
        }

        self::json(['error' => $error], $status);
    }

    private static function json(array $payload, int $status): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
